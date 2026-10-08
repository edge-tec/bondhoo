<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\FriendRequestAcceptedMail;
use App\Mail\FriendRequestMail;
use App\Mail\NewLoginAlertMail;
use App\Mail\PasswordChangedMail;
use App\Mail\PasswordResetMail;
use App\Mail\SecurityAlertMail;
use App\Mail\SmtpTestMail;
use App\Mail\SocialNotificationMail;
use App\Mail\VerifyEmailMail;
use App\Mail\WelcomeMail;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\Email\EmailService;
use App\Services\Email\SmtpConfigService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EmailManagementController extends Controller
{
    public function __construct(
        protected SmtpConfigService $smtpConfigService,
        protected EmailService $emailService
    ) {}

    /**
     * GET /api/v1/admin/smtp/settings
     * Get current sanitized SMTP configuration.
     */
    public function getSettings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->smtpConfigService->getSafeSettings(),
        ]);
    }

    /**
     * POST /api/v1/admin/smtp/settings
     * Update and save SMTP configuration.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        if ($request->has('mail_encryption') && is_string($request->input('mail_encryption'))) {
            $request->merge(['mail_encryption' => strtolower(trim($request->input('mail_encryption')))]);
        }
        if ($request->has('mail_reply_to') && trim((string) $request->input('mail_reply_to')) === '') {
            $request->merge(['mail_reply_to' => null]);
        }
        if ($request->has('mail_username') && trim((string) $request->input('mail_username')) === '') {
            $request->merge(['mail_username' => null]);
        }

        $validated = $request->validate([
            'mail_mailer' => ['nullable', 'string', 'max:50'],
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'string', 'in:tls,ssl,starttls,none,null'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
            'mail_reply_to' => ['nullable', 'email', 'max:255'],
            'smtp_auth' => ['nullable', 'boolean'],
            'timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $setting = $this->smtpConfigService->saveSettings($validated);

        return response()->json([
            'success' => true,
            'message' => 'SMTP কনফিগারেশন সফলভাবে সংরক্ষিত ও কার্যকর করা হয়েছে।',
            'data' => $this->smtpConfigService->getSafeSettings(),
        ]);
    }

    /**
     * POST /api/v1/admin/smtp/test
     * Dispatch an authentic test email to verify SMTP credentials.
     */
    public function testConnection(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'to_email' => ['required', 'email', 'max:255'],
                'mail_host' => ['nullable', 'string', 'max:255'],
                'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
                'mail_username' => ['nullable', 'string', 'max:255'],
                'mail_password' => ['nullable', 'string', 'max:255'],
                'mail_encryption' => ['nullable', 'string'],
                'mail_from_address' => ['nullable', 'email', 'max:255'],
                'mail_from_name' => ['nullable', 'string', 'max:255'],
            ]);

            $toEmail = $validated['to_email'];
            unset($validated['to_email']);

            $result = $this->smtpConfigService->testConnection($toEmail, $validated);

            return response()->json($result, $result['success'] ? 200 : 422);
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'SMTP সংযোগ ব্যর্থ: '.$e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    /**
     * GET /api/v1/admin/smtp/logs
     * Paginated email delivery logs with multi-field search and filters.
     */
    public function getLogs(Request $request): JsonResponse
    {
        $query = EmailLog::with('user:id,name,username,email')->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('email_type')) {
            $query->where('email_type', $request->input('email_type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('idempotency_key', 'like', "%{$search}%");
            });
        }

        $perPage = min(100, max(5, (int) $request->input('per_page', 15)));
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * POST /api/v1/admin/smtp/logs/{id}/retry
     * Retry sending a failed or queued email from the delivery log.
     */
    public function retryLog(int $id): JsonResponse
    {
        $log = EmailLog::findOrFail($id);

        if (empty($log->mail_class) || ! class_exists($log->mail_class)) {
            return response()->json([
                'success' => false,
                'message' => 'এই ইমেইলটির জন্য রিট্রাই উপযোগী Mailable ক্লাস পাওয়া যায়নি।',
            ], 422);
        }

        $log->increment('attempts');
        $log->update(['status' => 'queued', 'error_message' => null]);

        try {
            $this->smtpConfigService->applyToMailer();

            $mailable = null;
            $user = $log->user ?: User::where('email', $log->recipient)->first() ?: new User(['name' => 'User', 'email' => $log->recipient]);

            // Instantiate corresponding mailable
            switch ($log->email_type) {
                case 'smtp_test':
                    $mailable = new SmtpTestMail(
                        toEmail: $log->recipient,
                        smtpHost: config('mail.mailers.smtp.host', '127.0.0.1'),
                        smtpPort: (int) config('mail.mailers.smtp.port', 587),
                        encryption: config('mail.mailers.smtp.encryption', 'tls') ?: 'tls'
                    );
                    break;
                case 'verify_email':
                    $mailable = new VerifyEmailMail(
                        user: $user,
                        otp: '123456',
                        verificationUrl: url('/verify-email?token=retry')
                    );
                    break;
                case 'welcome':
                    $mailable = new WelcomeMail($user);
                    break;
                case 'password_reset':
                    $mailable = new PasswordResetMail($user, url('/reset-password?token=retry'));
                    break;
                case 'password_changed':
                    $mailable = new PasswordChangedMail($user);
                    break;
                case 'new_login':
                    $mailable = new NewLoginAlertMail(
                        user: $user,
                        device: 'Web Browser (OS)',
                        ip: $log->ip_address ?? '127.0.0.1',
                        location: 'Dhaka, Bangladesh'
                    );
                    break;
                case 'security_alert':
                    $mailable = new SecurityAlertMail(
                        user: $user,
                        actionTitle: 'Account Security Alert',
                        actionDescription: 'A security action was retried by administrator.',
                        ip: $log->ip_address ?? '127.0.0.1',
                        device: 'Unknown Device'
                    );
                    break;
                default:
                    $mailable = new SocialNotificationMail(
                        recipient: $user,
                        notificationTitle: $log->subject,
                        notificationMessage: 'Retried platform notification.',
                        actionUrl: url('/dashboard')
                    );
                    break;
            }

            Mail::to($log->recipient)->send($mailable);

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ইমেইল সফলভাবে পুনরায় পাঠানো হয়েছে।',
                'data' => $log->fresh(),
            ]);
        } catch (Exception $e) {
            $safeError = $e->getMessage();
            $decryptedPassword = $this->smtpConfigService->getActiveSettings()->getDecryptedPassword();
            if (! empty($decryptedPassword)) {
                $safeError = str_replace($decryptedPassword, '********', $safeError);
            }

            $log->update([
                'status' => 'failed',
                'error_message' => $safeError,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'পুনরায় প্রেরণে ব্যর্থ: '.$safeError,
                'data' => $log->fresh(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/admin/smtp/stats
     * Real-time metrics and delivery statistics for the email subsystem.
     */
    public function getStats(): JsonResponse
    {
        $total = EmailLog::count();
        $sent = EmailLog::where('status', 'sent')->count();
        $failed = EmailLog::where('status', 'failed')->count();
        $queued = EmailLog::where('status', 'queued')->count();
        $totalAttempts = (int) EmailLog::sum('attempts');

        $deliveryRate = ($sent + $failed) > 0 ? round(($sent / ($sent + $failed)) * 100, 2) : 100.0;

        $last24hSent = EmailLog::where('status', 'sent')->where('created_at', '>=', now()->subDay())->count();
        $last24hFailed = EmailLog::where('status', 'failed')->where('created_at', '>=', now()->subDay())->count();

        $activeSettings = $this->smtpConfigService->getActiveSettings();

        return response()->json([
            'success' => true,
            'data' => [
                'total_emails' => $total,
                'sent_count' => $sent,
                'failed_count' => $failed,
                'queued_count' => $queued,
                'total_attempts' => $totalAttempts,
                'delivery_rate_percent' => $deliveryRate,
                'last_24h' => [
                    'sent' => $last24hSent,
                    'failed' => $last24hFailed,
                ],
                'is_system_enabled' => $activeSettings->is_enabled,
                'active_host' => $activeSettings->mail_host,
                'active_port' => $activeSettings->mail_port,
                'active_encryption' => $activeSettings->mail_encryption,
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/smtp/templates
     * List all supported system email templates.
     */
    public function getTemplates(): JsonResponse
    {
        $templates = [
            [
                'key' => 'verify_email',
                'name' => 'Email Verification',
                'description' => 'নতুন ব্যবহারকারী নিবন্ধনের সময় ৬-সংখ্যার OTP এবং ভেরিফিকেশন লিঙ্ক সম্বলিত মেইল।',
                'mailable' => VerifyEmailMail::class,
                'is_mandatory' => true,
            ],
            [
                'key' => 'welcome',
                'name' => 'Welcome & Account Activated',
                'description' => 'ইমেইল যাচাইকরণের পর ব্যবহারকারীকে প্ল্যাটফর্মে স্বাগত জানানো।',
                'mailable' => WelcomeMail::class,
                'is_mandatory' => true,
            ],
            [
                'key' => 'password_reset',
                'name' => 'Password Reset',
                'description' => 'পাসওয়ার্ড ভুলে গেলে সিকিউর সিঙ্গেল-ইউজ রিসেট লিঙ্ক ও OTP কোড প্রেরণ।',
                'mailable' => PasswordResetMail::class,
                'is_mandatory' => true,
            ],
            [
                'key' => 'password_changed',
                'name' => 'Password Changed Alert',
                'description' => 'পাসওয়ার্ড সফলভাবে পরিবর্তিত হলে তাৎক্ষণিক নিরাপত্তা নোটিফিকেশন।',
                'mailable' => PasswordChangedMail::class,
                'is_mandatory' => true,
            ],
            [
                'key' => 'new_login',
                'name' => 'New Device / Login Alert',
                'description' => 'অপরিচিত বা নতুন ডিভাইস থেকে সফল লগইন হলে নিরাপত্তা সতর্কতা।',
                'mailable' => NewLoginAlertMail::class,
                'is_mandatory' => true,
            ],
            [
                'key' => 'security_alert',
                'name' => 'Suspicious Activity / Security Alert',
                'description' => 'সন্দেহজনক কার্যকলাপ বা অ্যাকাউন্ট সুরক্ষাজনিত সতর্কবার্তা।',
                'mailable' => SecurityAlertMail::class,
                'is_mandatory' => true,
            ],
            [
                'key' => 'friend_request',
                'name' => 'Friend Request Received',
                'description' => 'কোনো ব্যবহারকারী ফ্রেন্ড রিকোয়েস্ট পাঠালে প্রেরকের তথ্যসহ ইমেইল।',
                'mailable' => FriendRequestMail::class,
                'is_mandatory' => false,
            ],
            [
                'key' => 'friend_accepted',
                'name' => 'Friend Request Accepted',
                'description' => 'প্রেরিত ফ্রেন্ড রিকোয়েস্ট গৃহীত হলে নোটিফিকেশন মেইল।',
                'mailable' => FriendRequestAcceptedMail::class,
                'is_mandatory' => false,
            ],
            [
                'key' => 'social_notification',
                'name' => 'Social Activity (Comments, Mentions, Likes)',
                'description' => 'কমেন্ট, মেনশন, মেসেজ, লাইক বা সোশ্যাল কর্মকাণ্ডের সমন্বিত নোটিফিকেশন।',
                'mailable' => SocialNotificationMail::class,
                'is_mandatory' => false,
            ],
            [
                'key' => 'smtp_test',
                'name' => 'Admin SMTP Configuration Test',
                'description' => 'অ্যাডমিন কর্তৃক SMTP সার্ভার সংযোগ পরীক্ষার ডায়াগনস্টিক মেইল।',
                'mailable' => SmtpTestMail::class,
                'is_mandatory' => true,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * GET /api/v1/admin/smtp/templates/{key}/preview
     * Live rendered HTML preview of the specified email template.
     */
    public function previewTemplate(string $key): Response
    {
        $previewUser = new User([
            'id' => 1,
            'name' => 'মোহাম্মদ মিজানুর রহমান',
            'username' => 'mizan',
            'email' => 'mizan@bondhoo.com',
        ]);

        $previewSender = new User([
            'id' => 2,
            'name' => 'আরিফুল ইসলাম',
            'username' => 'ariful',
            'email' => 'ariful@bondhoo.com',
        ]);

        $mailable = match ($key) {
            'verify_email' => new VerifyEmailMail(
                user: $previewUser,
                otp: '849201',
                verificationUrl: url('/verify-email?token=preview_token_sample_12345'),
                expiresMinutes: 60
            ),
            'welcome' => new WelcomeMail($previewUser),
            'password_reset' => new PasswordResetMail(
                user: $previewUser,
                resetUrl: url('/reset-password?token=preview_reset_token_67890'),
                otp: '592314',
                expiresMinutes: 15
            ),
            'password_changed' => new PasswordChangedMail($previewUser),
            'new_login' => new NewLoginAlertMail(
                user: $previewUser,
                device: 'MacBook Pro - Google Chrome 132.0 (macOS)',
                ip: '103.145.118.42',
                location: 'Dhaka, Bangladesh'
            ),
            'security_alert' => new SecurityAlertMail(
                user: $previewUser,
                actionTitle: 'অ্যাকাউন্টে অস্বাভাবিক লগইন প্রচেষ্টা সনাক্ত হয়েছে',
                actionDescription: 'আপনার পাসওয়ার্ড ব্যবহার করে একটি নতুন স্থান থেকে লগইন করার চেষ্টা করা হয়েছিল। আমরা অ্যাকাউন্টটি সাময়িকভাবে সুরক্ষিত করেছি।',
                ip: '103.205.71.18',
                device: 'Windows 11 PC (Firefox)'
            ),
            'friend_request' => new FriendRequestMail(
                sender: $previewSender,
                recipient: $previewUser,
                requestUrl: url('/friends/requests')
            ),
            'friend_accepted' => new FriendRequestAcceptedMail(
                friend: $previewSender,
                recipient: $previewUser,
                profileUrl: url('/profile/ariful')
            ),
            'social_notification' => new SocialNotificationMail(
                recipient: $previewUser,
                notificationTitle: 'আরিফুল ইসলাম আপনার পোস্টে কমেন্ট করেছেন',
                notificationMessage: 'দারুণ উদ্যোগ! শুভকামনা রইলো সবসময়।',
                actionUrl: url('/post/12345'),
                actorName: 'আরিফুল ইসলাম'
            ),
            default => new SmtpTestMail(
                toEmail: 'admin@bondhoo.com',
                smtpHost: config('mail.mailers.smtp.host', 'mail.bondhoo.com'),
                smtpPort: (int) config('mail.mailers.smtp.port', 587),
                encryption: 'TLS',
                fromAddress: 'noreply@bondhoo.com',
                fromName: 'Bondhoo Enterprise'
            ),
        };

        return response($mailable->render(), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
