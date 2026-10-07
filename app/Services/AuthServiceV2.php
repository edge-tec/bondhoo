<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BlockedUser;
use App\Models\EmailVerification;
use App\Models\FailedLogin;
use App\Models\FailedLoginAttempt;
use App\Models\MobileVerification;
use App\Models\NotificationSetting;
use App\Models\PhoneVerification;
use App\Models\PrivacySetting;
use App\Models\Referral;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use App\Models\Wallet;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\PasswordResetOtpNotification;
use App\Notifications\SmsOtpNotification;
use App\Notifications\SuspiciousLoginNotification;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WelcomeEmailNotification;
use App\Services\Security\CaptchaService;
use App\Services\Security\EnterpriseSecurityService;
use App\Services\Sms\SmsGatewayManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthServiceV2
{
    /**
     * Reserved usernames that cannot be registered by standard users.
     */
    public const RESERVED_USERNAMES = [
        'admin', 'administrator', 'superadmin', 'root', 'jugajug', 'system',
        'support', 'help', 'moderator', 'manager', 'staff', 'security',
        'api', 'auth', 'null', 'undefined', 'login', 'register', 'settings',
        'official', 'contact', 'terms', 'privacy', 'about', 'blog', 'news',
        'dashboard', 'mail', 'email', 'webmaster', 'hostmaster', 'postmaster',
    ];

    /**
     * Common disposable / throwaway email domains.
     */
    public const DISPOSABLE_EMAIL_DOMAINS = [
        'mailinator.com', '10minutemail.com', 'tempmail.com', 'guerrillamail.com',
        'sharklasers.com', 'throwawaymail.com', 'getairmail.com', 'temp-mail.org',
        'trashmail.com', 'yopmail.com', 'dispostable.com', 'fakemailgenerator.com',
        'burnermail.io', 'mohmal.com', 'crazymailing.com',
    ];

    public function __construct(
        protected OtpService $otpService,
        protected PasswordHistoryService $passwordHistoryService,
        protected DeviceSessionService $deviceSessionService,
        protected TwoFactorService $twoFactorService,
        protected EnterpriseSecurityService $securityService,
        protected SmsGatewayManager $smsManager,
        protected CaptchaService $captchaService
    ) {}

    /**
     * Register a new user with full Facebook-grade fields, OTP generation, and audit logging.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: ?string, requires_verification: bool}
     */
    public function register(array $data, Request $request): array
    {
        // 1. Honeypot check
        if (! empty($data['website_hp'] ?? null) || ! empty($data['hp_company'] ?? null)) {
            throw ValidationException::withMessages(['hp' => ['বট রিকোয়েস্ট শনাক্ত হয়েছে।']]);
        }

        // 2. Reserved username check
        if (in_array(strtolower($data['username'] ?? ''), self::RESERVED_USERNAMES, true)) {
            throw ValidationException::withMessages([
                'username' => ['এই ইউজারনেমটি সিস্টেমের জন্য সংরক্ষিত। অনুগ্রহ করে অন্য ইউজারনেম বেছে নিন।'],
            ]);
        }

        // 3. Disposable email check
        if (! empty($data['email'])) {
            $emailDomain = strtolower(substr(strrchr($data['email'], '@'), 1));
            if (in_array($emailDomain, self::DISPOSABLE_EMAIL_DOMAINS, true)) {
                throw ValidationException::withMessages([
                    'email' => ['ডিসপোজেবল বা অস্থায়ী ইমেইল অ্যাড্রেস গ্রহণ করা হয় না। অনুগ্রহ করে একটি স্থায়ী ইমেইল ব্যবহার করুন।'],
                ]);
            }
        }

        // 4. Age validation check (minimum 13 years old)
        if (! empty($data['birth_date'])) {
            $dob = Carbon::parse($data['birth_date']);
            if ($dob->diffInYears(now()) < 13) {
                throw ValidationException::withMessages([
                    'birth_date' => ['ব্যবহারকারীর বয়স ন্যূনতম ১৩ বছর হতে হবে।'],
                ]);
            }
        }

        // 5. Referral check
        $referralUsername = null;
        if (! empty($data['referred_by'])) {
            $referrer = User::where('username', strtolower($data['referred_by']))->first();
            if ($referrer) {
                $referralUsername = $referrer->username;
            }
        }

        return DB::transaction(function () use ($data, $request, $referralUsername) {
            $meta = $this->deviceSessionService->parseRequest($request);

            $firstName = $data['first_name'] ?? null;
            $lastName = $data['last_name'] ?? null;
            $fullName = $data['name'] ?? trim("{$firstName} {$lastName}");
            if (empty($firstName) && ! empty($fullName)) {
                $parts = explode(' ', $fullName, 2);
                $firstName = $parts[0] ?? null;
                $lastName = $parts[1] ?? null;
            }

            // User creation
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $fullName,
                'username' => strtolower($data['username']),
                'email' => isset($data['email']) ? strtolower($data['email']) : null,
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? $meta['country'] ?? 'BD',
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'referred_by' => $referralUsername,
                'password' => $data['password'], // cast to hashed in model
                'status' => 'pending', // Pending OTP verification
                'terms_accepted_at' => now(),
                'last_login_at' => now(),
                'last_login_ip' => $meta['ip'],
            ]);

            // Assign standard USER role
            $userRole = Role::firstOrCreate(
                ['name' => 'USER'],
                ['label' => 'Standard User', 'description' => 'Regular platform participant']
            );
            $user->roles()->syncWithoutDetaching([$userRole->id]);

            // Record initial password in password histories
            $this->passwordHistoryService->recordPassword($user, $user->password);

            // 1. Create Profile
            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'display_name' => $user->name ?? $user->username,
                'location' => $user->country,
                'birth_date' => $user->birth_date,
                'gender' => $user->gender,
                'joined_date' => now(),
            ]);

            // 2. Create Privacy Settings
            PrivacySetting::create([
                'user_id' => $user->id,
                'profile_visibility' => 'public',
                'post_default_privacy' => 'public',
                'friends_list_visibility' => 'public',
                'search_engine_indexing' => true,
                'phone_visibility' => 'friends',
                'email_visibility' => 'only_me',
                'birthday_visibility' => 'friends',
            ]);

            // 3. Create Notification Settings
            NotificationSetting::create([
                'user_id' => $user->id,
                'email_notifications' => true,
                'sms_notifications' => false,
                'push_notifications' => true,
                'friend_request_alerts' => true,
                'comment_alerts' => true,
                'mention_alerts' => true,
                'security_alerts' => true,
            ]);

            // 4. Create User Settings (backward compatibility)
            UserSetting::create([
                'user_id' => $user->id,
                'who_can_see_posts' => 'public',
                'who_can_send_friend_requests' => 'everyone',
                'who_can_follow' => 'everyone',
                'who_can_message' => 'everyone',
                'who_can_see_friends' => 'public',
                'story_visibility' => 'friends',
            ]);

            // 5. Create Wallet
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0.00,
                'pending_balance' => 0.00,
                'currency' => 'BDT',
                'status' => 'active',
            ]);

            // 6. Create Referral
            $referrerUser = $referralUsername ? User::where('username', $referralUsername)->first() : null;
            Referral::create([
                'user_id' => $user->id,
                'referrer_id' => $referrerUser?->id,
                'referral_code' => Referral::generateUniqueCode(strtoupper(substr($user->username, 0, 3))),
                'reward_claimed' => false,
                'reward_amount' => 0.00,
                'status' => 'active',
            ]);

            // 7. Audit log
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.registered',
                'entity_type' => User::class,
                'entity_id' => $user->id,
                'new_values' => [
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'country' => $user->country,
                ],
                'ip_address' => $meta['ip'],
                'user_agent' => $request->userAgent(),
            ]);

            // Dispatch OTPs & Verification links
            if ($user->email) {
                $emailOtp = $this->otpService->generateOtp($user->email, 'verify_email', $user, 60, $meta['ip']);
                $token = Str::random(64);
                EmailVerification::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'otp_hash' => $emailOtp['otp_model']->code_hash,
                    'token' => $token,
                    'expires_at' => now()->addMinutes(60),
                    'ip_address' => $meta['ip'],
                    'user_agent' => $request->userAgent(),
                ]);
                $user->notify(new VerifyEmailNotification($emailOtp['plain_otp'], $token, $meta['ip'], $request->userAgent()));
            }

            if ($user->phone) {
                $phoneOtp = $this->otpService->generateOtp($user->phone, 'verify_phone', $user, 10, $meta['ip']);
                $gateway = config('sms.default', 'log');

                MobileVerification::create([
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'otp_hash' => $phoneOtp['otp_model']->code_hash,
                    'gateway' => $gateway,
                    'expires_at' => now()->addMinutes(10),
                    'ip_address' => $meta['ip'],
                ]);

                PhoneVerification::create([
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'otp_hash' => $phoneOtp['otp_model']->code_hash,
                    'gateway' => $gateway,
                    'attempts' => 0,
                    'max_attempts' => 3,
                    'expires_at' => now()->addMinutes(10),
                    'ip_address' => $meta['ip'],
                    'user_agent' => $request->userAgent(),
                ]);

                (new SmsOtpNotification($phoneOtp['plain_otp'], 'অ্যাকাউন্ট ভেরিফিকেশন'))->sendSms($user->phone, $this->smsManager);
            }

            return [
                'user' => $user->fresh(['profile', 'settings', 'privacySettings', 'notificationSettings', 'wallet', 'referral', 'roles']),
                'token' => null,
                'requires_verification' => true,
            ];
        });
    }

    /**
     * Authenticate user with Email, Username or Phone, brute-force locking and 2FA support.
     *
     * @return array{user?: User, token?: string, requires_2fa?: bool, challenge_token?: string, message?: string}
     */
    public function login(string $identifier, string $password, Request $request, bool $rememberMe = false, ?string $deviceName = null): array
    {
        // 1. Honeypot check
        if (! empty($request->input('website_hp')) || ! empty($request->input('hp_company'))) {
            throw ValidationException::withMessages(['hp' => ['বট রিকোয়েস্ট প্রতিহত করা হয়েছে।']]);
        }

        $meta = $this->deviceSessionService->parseRequest($request);

        // Check if account was soft-deleted
        $trashedUser = User::onlyTrashed()
            ->where(function ($query) use ($identifier) {
                $cleanId = strtolower(trim($identifier));
                $query->where('email', $cleanId)
                    ->orWhere('username', $cleanId)
                    ->orWhere('phone', trim($identifier));
            })
            ->first();

        if ($trashedUser) {
            throw ValidationException::withMessages([
                'identifier' => ['আপনার অ্যাকাউন্টটি মুছে ফেলা (deleted) হয়েছে। অ্যাকাউন্ট পুনরুদ্ধার করতে সাপোর্টে যোগাযোগ করুন।'],
            ]);
        }

        // 2. Check if user or IP is blocked in blocked_users table
        $activeBlock = BlockedUser::active()
            ->where(function ($q) use ($identifier, $meta) {
                $q->where('identifier', $identifier)
                    ->orWhere('ip_address', $meta['ip']);
            })
            ->first();

        if ($activeBlock) {
            throw ValidationException::withMessages([
                'identifier' => ['নিরাপত্তাজনিত কারণে আপনার অ্যাকাউন্ট বা আইপি সাময়িকভাবে ব্লক করা হয়েছে।'],
            ]);
        }

        // 3. Find user by email, username, or phone
        $user = User::query()
            ->where(function ($query) use ($identifier) {
                $cleanId = strtolower(trim($identifier));
                $query->where('email', $cleanId)
                    ->orWhere('username', $cleanId)
                    ->orWhere('phone', trim($identifier));
            })
            ->first();

        // 4. Account lock check before password verification
        if ($user && $user->isLocked()) {
            $minsLeft = ceil(now()->diffInMinutes($user->locked_until, false));
            throw ValidationException::withMessages([
                'identifier' => ["আপনার অ্যাকাউন্টটি লক অবস্থায় রয়েছে। অনুগ্রহ করে আরো {$minsLeft} মিনিট পর চেষ্টা করুন অথবা সাপোর্টে যোগাযোগ করুন।"],
            ]);
        }

        // 5. CAPTCHA verification after failed attempts (>= 3 attempts)
        $requiresCaptcha = $this->captchaService->requiresCaptcha($identifier, $meta['ip']);
        if ($requiresCaptcha) {
            $captchaKey = $request->input('captcha_key');
            $captchaAnswer = $request->input('captcha_answer');

            if (! $this->captchaService->verifyCaptcha($captchaKey, $captchaAnswer)) {
                // Record failed login attempt
                FailedLoginAttempt::create([
                    'user_id' => $user?->id,
                    'identifier' => $identifier,
                    'ip_address' => $meta['ip'],
                    'user_agent' => $request->userAgent(),
                    'failure_reason' => 'invalid_or_missing_captcha',
                    'attempted_at' => now(),
                ]);

                if ($user) {
                    $attempts = $user->incrementFailedLogins();
                    if ($user->isLocked()) {
                        BlockedUser::create([
                            'user_id' => $user->id,
                            'identifier' => $user->email ?? $user->username ?? $identifier,
                            'ip_address' => $meta['ip'],
                            'reason' => 'অতিরিক্ত ব্যর্থ লগইন চেষ্টার কারণে অ্যাকাউন্ট সাময়িকভাবে লক করা হয়েছে।',
                            'blocked_type' => 'account_lock',
                            'blocked_at' => now(),
                            'expires_at' => $user->locked_until,
                            'is_active' => true,
                        ]);

                        throw ValidationException::withMessages([
                            'identifier' => ['অতিরিক্ত ভুল চেষ্টার কারণে আপনার অ্যাকাউন্টটি আগামী ১৫ মিনিটের জন্য লক করা হয়েছে।'],
                            'captcha' => ['অতিরিক্ত ভুল চেষ্টার কারণে আপনার অ্যাকাউন্টটি আগামী ১৫ মিনিটের জন্য লক করা হয়েছে।'],
                        ]);
                    }
                }

                throw ValidationException::withMessages([
                    'identifier' => ['ক্যাপচা সঠিক নয় অথবা ক্যাপচা যাচাইকরণ প্রয়োজন।'],
                    'captcha' => ['ক্যাপচা সঠিক নয় অথবা ক্যাপচা যাচাইকরণ প্রয়োজন।'],
                ]);
            }
        }

        // 6. Failed credential handling
        if (! $user || ! Hash::check($password, $user->password)) {
            FailedLogin::create([
                'identifier' => $identifier,
                'ip_address' => $meta['ip'],
                'user_agent' => $request->userAgent(),
                'reason' => 'invalid_credentials',
                'attempted_at' => now(),
            ]);

            FailedLoginAttempt::create([
                'user_id' => $user?->id,
                'identifier' => $identifier,
                'ip_address' => $meta['ip'],
                'user_agent' => $request->userAgent(),
                'failure_reason' => 'invalid_credentials',
                'attempted_at' => now(),
            ]);

            if ($user) {
                $attempts = $user->incrementFailedLogins();
                $this->deviceSessionService->recordLoginHistory(
                    $user,
                    $request,
                    'failed',
                    'Invalid password'
                );

                if ($user->isLocked()) {
                    BlockedUser::create([
                        'user_id' => $user->id,
                        'identifier' => $user->email ?? $user->username ?? $identifier,
                        'ip_address' => $meta['ip'],
                        'reason' => 'অতিরিক্ত ৫ বার ভুল পাসওয়ার্ড দেওয়ার কারণে অ্যাকাউন্ট সাময়িকভাবে লক করা হয়েছে।',
                        'blocked_type' => 'account_lock',
                        'blocked_at' => now(),
                        'expires_at' => $user->locked_until,
                        'is_active' => true,
                    ]);

                    SecurityEvent::create([
                        'user_id' => $user->id,
                        'event_type' => 'failed_login',
                        'severity' => 'high',
                        'ip_address' => $meta['ip'],
                        'details' => ['attempts' => $attempts, 'action' => 'account_locked'],
                    ]);

                    AuditLog::create([
                        'user_id' => $user->id,
                        'action' => 'auth.account_locked',
                        'entity_type' => User::class,
                        'entity_id' => $user->id,
                        'ip_address' => $meta['ip'],
                        'user_agent' => $request->userAgent(),
                    ]);

                    throw ValidationException::withMessages([
                        'identifier' => ['অতিরিক্ত ভুল চেষ্টার কারণে আপনার অ্যাকাউন্টটি আগামী ১৫ মিনিটের জন্য লক করা হয়েছে।'],
                    ]);
                }

                $remaining = max(0, 5 - $attempts);
                $messages = [
                    'identifier' => ["ইউজারনেম বা পাসওয়ার্ড সঠিক নয়। আর {$remaining} বার ভুল করলে অ্যাকাউন্ট সাময়িক লক হয়ে যাবে।"],
                ];

                if ($attempts >= 3) {
                    $messages['requires_captcha'] = true;
                    $messages['captcha'] = ['অতিরিক্ত ব্যর্থ চেষ্টার কারণে পরবর্তী লগইনে ক্যাপচা পূরণ করা আবশ্যক।'];
                }

                throw ValidationException::withMessages($messages);
            }

            throw ValidationException::withMessages([
                'identifier' => ['প্রদত্ত তথ্যের সাথে কোনো অ্যাকাউন্টের মিল পাওয়া যায়নি।'],
            ]);
        }

        // 5. Account status check
        if ($user->status === 'suspended' || $user->status === 'banned') {
            throw ValidationException::withMessages([
                'identifier' => ["আপনার অ্যাকাউন্টটি বর্তমানে {$user->status} অবস্থায় রয়েছে। বিস্তারিত জানতে অ্যাডমিনের সাথে যোগাযোগ করুন।"],
            ]);
        }

        if ($user->status === 'inactive' || $user->status === 'pending') {
            throw ValidationException::withMessages([
                'identifier' => ['আপনার অ্যাকাউন্টটি নিষ্ক্রিয় (inactive) অবস্থায় রয়েছে। অ্যাকাউন্ট চালু করতে সাপোর্টে যোগাযোগ করুন।'],
            ]);
        }

        // 6. Reset failed counter on valid credentials
        $user->resetFailedLogins();

        // 7. Suspicious login & Risk Analysis
        $riskAssessment = $this->securityService->evaluateLoginRisk($user, $request);
        $isSuspicious = $riskAssessment['requires_mfa'] || ($riskAssessment['risk_level'] === 'critical');

        $this->deviceSessionService->recordLoginHistory(
            $user,
            $request,
            $isSuspicious ? 'suspicious' : 'success',
            null,
            $isSuspicious
        );

        if ($isSuspicious) {
            $user->notify(new SuspiciousLoginNotification([
                'ip' => $meta['ip'],
                'location' => $meta['country'],
                'reason' => implode(', ', $riskAssessment['flags']),
            ]));
        }

        // 8. Two Factor Authentication Check
        if ($user->two_factor_enabled || $isSuspicious) {
            $challengeToken = Str::random(60);
            Cache::put('2fa_challenge_'.$challengeToken, [
                'user_id' => $user->id,
                'remember_me' => $rememberMe,
                'device_name' => $deviceName,
            ], now()->addMinutes(10));

            $challengeData = $this->twoFactorService->sendChallengeOtp($user);

            return [
                'requires_2fa' => true,
                'challenge_token' => $challengeToken,
                'method' => $challengeData['method'] ?? $user->two_factor_type,
                'destination' => $challengeData['destination'] ?? '',
                'message' => 'টু-ফ্যাক্টর অথেন্টিকেশন কোড প্রয়োজন।',
            ];
        }

        // 9. Finalize Login & Issue Token
        return $this->issueLoginCredentials($user, $request, $rememberMe, $deviceName);
    }

    /**
     * Complete login session creation after 2FA challenge is solved.
     */
    public function complete2faLogin(string $challengeToken, string $code, Request $request): array
    {
        $cached = Cache::get('2fa_challenge_'.$challengeToken);
        if (! $cached || ! isset($cached['user_id'])) {
            throw ValidationException::withMessages([
                'code' => ['টু-ফ্যাক্টর চ্যালেঞ্জের মেয়াদ শেষ হয়ে গেছে। অনুগ্রহ করে পুনরায় লগইন করুন।'],
            ]);
        }

        $user = User::findOrFail($cached['user_id']);
        $this->twoFactorService->verifyChallenge($user, $code);

        Cache::forget('2fa_challenge_'.$challengeToken);

        return $this->issueLoginCredentials(
            $user,
            $request,
            $cached['remember_me'] ?? false,
            $cached['device_name'] ?? null
        );
    }

    /**
     * Issue Sanctum token, create session, update last login, and dispatch login alerts.
     */
    protected function issueLoginCredentials(User $user, Request $request, bool $rememberMe = false, ?string $deviceName = null): array
    {
        $meta = $this->deviceSessionService->parseRequest($request);

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $meta['ip'],
        ]);

        $tokenName = $deviceName ?: ($meta['browser'].' ('.$meta['os'].')');
        $token = $user->createToken($tokenName)->plainTextToken;

        // Extract token ID if possible
        $tokenId = null;
        if (str_contains($token, '|')) {
            $tokenId = (int) explode('|', $token)[0];
        }

        // Register session
        $this->deviceSessionService->registerSession($user, $request, $tokenId, $tokenName);

        // Trust device if requested
        if ($rememberMe) {
            $this->deviceSessionService->markDeviceTrusted($user, $request, $tokenName, 30);
        }

        // Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.login_success',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'ip_address' => $meta['ip'],
            'user_agent' => $request->userAgent(),
        ]);

        // Send login alert notification
        $warning = null;
        if ($user->email_verified_at === null) {
            $warning = 'আপনার ইমেইল ঠিকানা এখনো যাচাই করা হয়নি। অনুগ্রহ করে আপনার ইনবক্স চেক করে ইমেইল যাচাই করুন।';
        }

        return [
            'user' => $user->load(['profile', 'settings', 'roles']),
            'token' => $token,
            'token_type' => 'Bearer',
            'warning' => $warning,
            'email_verified' => $user->email_verified_at !== null,
        ];
    }

    /**
     * Verify email with OTP or Token.
     */
    public function verifyEmail(string $email, ?string $otp = null, ?Request $request = null, ?string $token = null): bool
    {
        $user = User::where('email', strtolower($email))->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'identifier' => ['অ্যাকাউন্টটি খুঁজে পাওয়া যায়নি।'],
            ]);
        }

        // 1. Verify via Token
        if (! empty($token)) {
            $record = EmailVerification::where('user_id', $user->id)
                ->where('token', $token)
                ->first();

            if (! $record) {
                throw ValidationException::withMessages([
                    'token' => ['অবৈধ ভেরিফিকেশন লিঙ্ক বা টোকেন।'],
                ]);
            }

            if ($record->expires_at < now()) {
                throw ValidationException::withMessages([
                    'token' => ['ভেরিফিকেশন লিঙ্কের মেয়াদ শেষ হয়ে গেছে। অনুগ্রহ করে পুনরায় কোড পাঠান।'],
                ]);
            }

            $record->update(['verified_at' => now()]);
        }
        // 2. Verify via OTP
        elseif (! empty($otp)) {
            $this->otpService->verifyOtp($user->email, 'verify_email', $otp, $request?->ip());

            EmailVerification::where('user_id', $user->id)
                ->whereNull('verified_at')
                ->latest()
                ->first()
                ?->update(['verified_at' => now()]);
        } else {
            throw ValidationException::withMessages([
                'otp' => ['ওটিপি কোড অথবা ভেরিফিকেশন টোকেন প্রদান করুন।'],
            ]);
        }

        $user->update([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $user->notify(new WelcomeEmailNotification($user->name ?: $user->username));

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.email_verified',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'new_values' => ['email_verified_at' => (string) $user->email_verified_at],
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        return true;
    }

    /**
     * Verify email via temporary signed URL.
     */
    public function verifySignedUrl(Request $request, int $id, string $hash): User
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'ভেরিফিকেশন লিঙ্কটির মেয়াদ শেষ অথবা লিঙ্কটি পরিবর্তিত হয়েছে।');
        }

        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            abort(403, 'অবৈধ ভেরিফিকেশন হ্যাশ।');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->update(['status' => 'active']);

            EmailVerification::where('user_id', $user->id)
                ->whereNull('verified_at')
                ->update(['verified_at' => now()]);

            $user->notify(new WelcomeEmailNotification($user->name ?: $user->username));

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.email_verified',
                'entity_type' => User::class,
                'entity_id' => $user->id,
                'new_values' => ['email_verified_at' => (string) $user->email_verified_at],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $user;
    }

    /**
     * Resend verification email with a fresh 64-char token & 6-digit OTP.
     */
    public function resendEmailVerification(string $email, Request $request): array
    {
        $user = User::where('email', strtolower($email))->first();

        $meta = $this->deviceSessionService->parseRequest($request);
        $otp = $this->otpService->generateOtp($email, 'verify_email', $user, 60, $meta['ip']);
        $token = Str::random(64);

        EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => $otp['otp_model']->code_hash,
            'token' => $token,
            'expires_at' => now()->addMinutes(60),
            'ip_address' => $meta['ip'],
            'user_agent' => $request->userAgent(),
        ]);

        $user->notify(new VerifyEmailNotification($otp['plain_otp'], $token, $meta['ip'], $request->userAgent()));

        return [
            'success' => true,
            'sent' => true,
            'message' => 'নতুন ভেরিফিকেশন লিঙ্ক ও ওটিপি আপনার ইমেইলে পাঠানো হয়েছে।',
        ];
    }

    /**
     * Get email verification status.
     */
    public function getVerificationStatus(string $email): array
    {
        $user = User::where('email', strtolower($email))->first();

        if (! $user) {
            return [
                'exists' => false,
                'is_verified' => false,
            ];
        }

        return [
            'exists' => true,
            'is_verified' => ! is_null($user->email_verified_at),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'status' => $user->status,
        ];
    }

    /**
     * Verify mobile with SMS OTP.
     */
    public function verifyMobile(string $phone, string $otp, Request $request): bool
    {
        $this->otpService->verifyOtp($phone, 'verify_phone', $otp, $request->ip());

        $user = User::where('phone', $phone)->firstOrFail();
        $user->update([
            'phone_verified_at' => now(),
            'status' => 'active',
        ]);

        MobileVerification::where('user_id', $user->id)
            ->where('phone', $user->phone)
            ->whereNull('verified_at')
            ->update(['verified_at' => now()]);

        PhoneVerification::where('user_id', $user->id)
            ->where('phone', $user->phone)
            ->whereNull('verified_at')
            ->update(['verified_at' => now()]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.phone_verified',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'new_values' => ['phone_verified_at' => (string) $user->phone_verified_at],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return true;
    }

    /**
     * Send or resend Phone OTP verification.
     */
    public function sendPhoneOtp(string $phone, Request $request, ?string $gateway = null): array
    {
        $user = User::where('phone', $phone)->first();
        $meta = $this->deviceSessionService->parseRequest($request);

        $otp = $this->otpService->generateOtp($phone, 'verify_phone', $user, 10, $meta['ip']);
        $resolvedGateway = $gateway ?: config('sms.default', 'log');

        PhoneVerification::create([
            'user_id' => $user?->id,
            'phone' => $phone,
            'otp_hash' => $otp['otp_model']->code_hash,
            'gateway' => $resolvedGateway,
            'attempts' => 0,
            'max_attempts' => 3,
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $meta['ip'],
            'user_agent' => $request->userAgent(),
        ]);

        MobileVerification::create([
            'user_id' => $user?->id,
            'phone' => $phone,
            'otp_hash' => $otp['otp_model']->code_hash,
            'gateway' => $resolvedGateway,
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $meta['ip'],
        ]);

        $smsResult = (new SmsOtpNotification($otp['plain_otp'], 'মোবাইল যাচাইকরণ'))->sendSms($phone, $this->smsManager);

        return [
            'success' => true,
            'sent' => true,
            'gateway' => $smsResult['gateway'] ?? $resolvedGateway,
            'message' => 'নতুন ভেরিফিকেশন ওটিপি আপনার মোবাইলে পাঠানো হয়েছে।',
        ];
    }

    /**
     * Initiate forgot password flow via Email or SMS.
     */
    public function sendPasswordResetOtp(string $identifier, Request $request): array
    {
        $user = User::where('email', strtolower($identifier))
            ->orWhere('username', strtolower($identifier))
            ->orWhere('phone', $identifier)
            ->first();

        if (! $user) {
            // For security, don't leak user existence
            return ['status' => 'success', 'message' => 'যদি অ্যাকাউন্টটি নিবন্ধিত থাকে, তবে ওটিপি পাঠানো হয়েছে।'];
        }

        $meta = $this->deviceSessionService->parseRequest($request);

        // Store in password_reset_tokens table
        $resetToken = Str::random(64);
        if ($user->email) {
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => strtolower($user->email)],
                [
                    'token' => Hash::make($resetToken),
                    'created_at' => now(),
                ]
            );
        }
        $resetUrl = url("/reset-password?token={$resetToken}&email=".urlencode($user->email ?: $identifier));

        // Decide whether to send via Email or Phone
        if ($user->email && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $otp = $this->otpService->generateOtp($user->email, 'password_reset', $user, 15, $meta['ip']);
            Cache::put('pwd_reset_token_'.$resetToken, ['user_id' => $user->id, 'email' => $user->email], now()->addMinutes(15));

            $user->notify(new PasswordResetOtpNotification($otp['plain_otp'], $resetUrl, [
                'ip' => $meta['ip'],
                'device' => $meta['user_agent'] ?? request()->userAgent(),
            ]));

            return [
                'status' => 'success',
                'channel' => 'email',
                'reset_token' => $resetToken,
                'reset_url' => $resetUrl,
                'message' => 'পাসওয়ার্ড রিসেট ওটিপি ও লিঙ্ক আপনার ইমেইলে পাঠানো হয়েছে।',
            ];
        }

        $phone = $user->phone ?: $identifier;
        $otp = $this->otpService->generateOtp($phone, 'password_reset', $user, 10, $meta['ip']);
        (new SmsOtpNotification($otp['plain_otp'], 'পাসওয়ার্ড রিসেট'))->sendSms($phone, $this->smsManager);

        return [
            'status' => 'success',
            'channel' => 'sms',
            'reset_token' => $resetToken,
            'message' => 'পাসওয়ার্ড রিসেট ওটিপি আপনার মোবাইলে এসএমএস করা হয়েছে।',
        ];
    }

    /**
     * Complete password reset.
     */
    public function resetPassword(string $identifier, ?string $otp, string $newPassword, Request $request, ?string $token = null): bool
    {
        $user = User::where('email', strtolower($identifier))
            ->orWhere('username', strtolower($identifier))
            ->orWhere('phone', $identifier)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'identifier' => ['অ্যাকাউন্টটি খুঁজে পাওয়া যায়নি।'],
            ]);
        }

        // 1. Verify Token OR OTP
        if (! empty($token)) {
            $record = DB::table('password_reset_tokens')
                ->where('email', strtolower($user->email))
                ->first();

            if (! $record || ! Hash::check($token, $record->token)) {
                throw ValidationException::withMessages([
                    'token' => ['অবৈধ বা মেয়াদোত্তীর্ণ পাসওয়ার্ড রিসেট টোকেন।'],
                ]);
            }

            // Expiration check (60 minutes)
            if (now()->subMinutes(60)->gt($record->created_at)) {
                DB::table('password_reset_tokens')->where('email', strtolower($user->email))->delete();
                throw ValidationException::withMessages([
                    'token' => ['পাসওয়ার্ড রিসেট টোকেনের মেয়াদ শেষ হয়ে গেছে। পুনরায় অনুরোধ করুন।'],
                ]);
            }

            // One-time use token: delete after verification
            DB::table('password_reset_tokens')->where('email', strtolower($user->email))->delete();
        } elseif (! empty($otp)) {
            $targetIdentifier = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? $user->email : ($user->phone ?: $identifier);
            $this->otpService->verifyOtp($targetIdentifier, 'password_reset', $otp, $request->ip());

            if ($user->email) {
                DB::table('password_reset_tokens')->where('email', strtolower($user->email))->delete();
            }
        } else {
            throw ValidationException::withMessages([
                'token' => ['টোকেন অথবা ওটিপি কোড প্রদান করা আবশ্যক।'],
            ]);
        }

        // 2. Prevent reusing recent passwords
        $this->passwordHistoryService->assertNotRecentlyUsed($user, $newPassword);

        // 3. Update password & record in history
        $hashed = Hash::make($newPassword);
        $user->update([
            'password' => $hashed,
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);
        $this->passwordHistoryService->recordPassword($user, $hashed);

        // 4. Revoke all existing sessions for security
        $user->tokens()->delete();

        // 5. Notify user
        try {
            $user->notify(new PasswordChangedNotification);
        } catch (\Throwable $e) {
            // Ignore notification failure
        }

        // 6. Audit log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.password_reset_success',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return true;
    }
}
