<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BlockedUser;
use App\Models\FailedLoginAttempt;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\DeviceSessionService;
use App\Services\Security\CaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        protected DeviceSessionService $deviceSessionService,
        protected CaptchaService $captchaService
    ) {}

    /**
     * Handle session-based Web Login.
     */
    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
            'remember' => ['nullable', 'boolean'],
            'captcha_key' => ['nullable', 'string'],
            'captcha_answer' => ['nullable', 'string'],
        ], [
            'identifier.required' => 'ইমেইল, ইউজারনেম বা মোবাইল নম্বর প্রদান করা আবশ্যক।',
            'password.required' => 'পাসওয়ার্ড প্রদান করা আবশ্যক।',
            'password.min' => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।',
        ]);

        $identifier = strtolower(trim($credentials['identifier']));
        $ip = $request->ip();
        $rateLimitKey = 'auth:'.$identifier.'|'.$ip;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $message = "অত্যধিক লগইন চেষ্টা করা হয়েছে। অনুগ্রহ করে {$seconds} সেকেন্ড পর চেষ্টা করুন।";

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 429);
            }

            return back()->withInput($request->only('identifier', 'remember'))->withErrors(['identifier' => $message]);
        }

        // Check if account was soft-deleted
        $trashedUser = User::onlyTrashed()
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('username', $identifier)
                    ->orWhere('phone', $identifier);
            })
            ->first();

        if ($trashedUser) {
            $message = 'আপনার অ্যাকাউন্টটি মুছে ফেলা (deleted) হয়েছে। অ্যাকাউন্ট পুনরায় সক্রিয় করতে সাপোর্টে যোগাযোগ করুন।';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return back()->withInput($request->only('identifier', 'remember'))->withErrors(['identifier' => $message]);
        }

        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        // Check if user account is locked
        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            $diff = $user->locked_until->diffForHumans();
            $message = "নিরাপত্তাজনিত কারণে আপনার অ্যাকাউন্টটি সাময়িকভাবে লক করা হয়েছে। {$diff} পরে আবার চেষ্টা করুন।";

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 423);
            }

            return back()->withInput($request->only('identifier', 'remember'))->withErrors(['identifier' => $message]);
        }

        $meta = $this->deviceSessionService->parseRequest($request);

        // Validate password
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($rateLimitKey, 60);

            if ($user) {
                $user->increment('failed_login_attempts');
                if ($user->failed_login_attempts >= 5) {
                    $user->update([
                        'locked_until' => now()->addMinutes(15),
                        'failed_login_attempts' => 0,
                    ]);

                    BlockedUser::create([
                        'user_id' => $user->id,
                        'identifier' => $user->email ?? $user->username ?? $identifier,
                        'ip_address' => $ip,
                        'reason' => 'অতিরিক্ত ৫ বার ব্যর্থ লগইন প্রচেষ্টার কারণে অ্যাকাউন্ট সাময়িকভাবে লক করা হয়েছে।',
                        'blocked_type' => 'account_lock',
                        'blocked_at' => now(),
                        'expires_at' => now()->addMinutes(15),
                        'is_active' => true,
                    ]);

                    AuditLog::create([
                        'user_id' => $user->id,
                        'action' => 'ACCOUNT_LOCKED_FAILED_ATTEMPTS',
                        'entity_type' => User::class,
                        'entity_id' => $user->id,
                        'ip_address' => $ip,
                        'user_agent' => $request->userAgent(),
                    ]);
                }
            }

            FailedLoginAttempt::create([
                'user_id' => $user?->id,
                'identifier' => $identifier,
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
                'failure_reason' => 'ভুল পাসওয়ার্ড বা ইউজারনেম',
                'attempted_at' => now(),
            ]);

            LoginHistory::create([
                'user_id' => $user?->id,
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
                'browser' => $meta['browser'],
                'os' => $meta['os'],
                'device_type' => $meta['device_type'],
                'country' => $meta['country'],
                'city' => $request->header('CF-IPCity', null),
                'status' => 'failed',
                'failure_reason' => 'ভুল পাসওয়ার্ড বা ইউজারনেম',
            ]);

            $errorMessage = 'প্রদত্ত লগইন তথ্যাদি আমাদের রেকর্ডের সাথে মিলছে না।';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 401);
            }

            return back()->withInput($request->only('identifier', 'remember'))->withErrors(['identifier' => $errorMessage]);
        }

        // Account status checks
        if ($user->status === 'suspended' || $user->status === 'banned') {
            $message = "আপনার অ্যাকাউন্টটি বর্তমানে {$user->status} অবস্থায় রয়েছে। বিস্তারিত জানতে অ্যাডমিনের সাথে যোগাযোগ করুন।";
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return back()->withInput($request->only('identifier', 'remember'))->withErrors(['identifier' => $message]);
        }

        if ($user->status === 'inactive' || $user->status === 'pending') {
            $message = 'আপনার অ্যাকাউন্টটি নিষ্ক্রিয় (inactive) অবস্থায় রয়েছে। পুনরায় সক্রিয় করতে সাপোর্টে যোগাযোগ করুন।';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return back()->withInput($request->only('identifier', 'remember'))->withErrors(['identifier' => $message]);
        }

        // Reset rate limiter and failed attempts
        RateLimiter::clear($rateLimitKey);
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('last_activity_time', time());

        /** @var DeviceSessionService $deviceService */
        $deviceService = app(DeviceSessionService::class);
        $deviceService->registerSession($user, $request);

        if ($remember) {
            $deviceService->markDeviceTrusted($user, $request, null, 30);
        }

        $deviceService->recordLoginHistory($user, $request, 'success');

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'USER_LOGIN_SUCCESS',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $request->userAgent(),
        ]);

        $warning = null;
        if ($user->email_verified_at === null) {
            $warning = 'আপনার ইমেইল এখনও যাচাই করা হয়নি। অনুগ্রহ করে ইনবক্স চেক করে ইমেইল যাচাই করুন।';
            $request->session()->flash('warning', $warning);
        }

        $token = $user->createToken('jugajug_web')->plainTextToken;
        $cookie = cookie('jugajug_token', $token, 60 * 24 * 30, '/', null, false, false);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'লগইন সফল হয়েছে।',
                'warning' => $warning,
                'email_verified' => $user->email_verified_at !== null,
                'user' => $user,
                'token' => $token,
                'redirect' => '/feed',
            ])->withCookie($cookie);
        }

        return redirect()->intended('/feed')->withCookie($cookie);
    }

    /**
     * Handle session-based Web Logout for the current device.
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::guard('web')->user() ?? $request->user();

        if (! $user && ($request->hasCookie('bondhoo_token') || $request->hasCookie('jugajug_token'))) {
            $rawToken = (string) ($request->cookie('bondhoo_token') ?: $request->cookie('jugajug_token'));
            $pat = PersonalAccessToken::findToken($rawToken);
            if ($pat && $pat->tokenable instanceof User) {
                $user = $pat->tokenable;
            }
        }

        if ($user) {
            $this->deviceSessionService->logoutCurrentDevice($user, $request);
        } else {
            $tokens = array_filter([
                $request->bearerToken(),
                (string) $request->cookie('bondhoo_token'),
                (string) $request->cookie('jugajug_token'),
            ]);
            foreach ($tokens as $t) {
                PersonalAccessToken::findToken($t)?->delete();
            }
        }

        Auth::guard('web')->logout();
        Auth::guard('admin')->logout();
        try {
            Auth::guard('sanctum')->forgetUser();
        } catch (\Throwable) {
        }
        Auth::forgetGuards();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $cookies = DeviceSessionService::getLogoutCookies();

        if ($request->expectsJson()) {
            $res = response()->json([
                'success' => true,
                'message' => 'সফলভাবে লগআউট সম্পন্ন হয়েছে।',
            ]);
            foreach ($cookies as $cookie) {
                $res->withCookie($cookie);
            }
            $res->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $res->headers->set('Pragma', 'no-cache');

            return $res;
        }

        $res = redirect('/login')->with('success', 'সফলভাবে লগআউট সম্পন্ন হয়েছে।');
        foreach ($cookies as $cookie) {
            $res->withCookie($cookie);
        }
        $res->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $res->headers->set('Pragma', 'no-cache');

        return $res;
    }

    /**
     * Logout from all devices across the platform.
     */
    public function logoutAll(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('web')->user() ?? $request->user();

        if (! $user && ($request->hasCookie('bondhoo_token') || $request->hasCookie('jugajug_token'))) {
            $rawToken = (string) ($request->cookie('bondhoo_token') ?: $request->cookie('jugajug_token'));
            $pat = PersonalAccessToken::findToken($rawToken);
            if ($pat && $pat->tokenable instanceof User) {
                $user = $pat->tokenable;
            }
        }

        if ($user) {
            $this->deviceSessionService->logoutAllDevices($user, $request);
        }

        Auth::guard('web')->logout();
        Auth::guard('admin')->logout();
        try {
            Auth::guard('sanctum')->forgetUser();
        } catch (\Throwable) {
        }
        Auth::forgetGuards();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $cookies = DeviceSessionService::getLogoutCookies();

        if ($request->expectsJson()) {
            $res = response()->json([
                'success' => true,
                'message' => 'সবগুলো ডিভাইস থেকে সফলভাবে লগআউট সম্পন্ন হয়েছে।',
            ]);
            foreach ($cookies as $cookie) {
                $res->withCookie($cookie);
            }
            $res->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $res->headers->set('Pragma', 'no-cache');

            return $res;
        }

        $res = redirect()->route('login')->with('success', 'সবগুলো ডিভাইস থেকে সফলভাবে লগআউট সম্পন্ন হয়েছে।');
        foreach ($cookies as $cookie) {
            $res->withCookie($cookie);
        }
        $res->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $res->headers->set('Pragma', 'no-cache');

        return $res;
    }

    /**
     * Current authenticated user info.
     */
    public function me(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'অননুমোদিত অ্যাক্সেস। অনুগ্রহ করে লগইন করুন।',
                ], 401);
            }

            return redirect()->route('login');
        }

        return response()->json([
            'success' => true,
            'user' => $user->load('profile'),
        ]);
    }

    /**
     * Rotate current session ID to protect against session fixation.
     */
    public function rotateSession(Request $request): JsonResponse|RedirectResponse
    {
        /** @var DeviceSessionService $deviceService */
        $deviceService = app(DeviceSessionService::class);
        $deviceService->rotateSession($request);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'সেশন সফলভাবে রোটেট করা হয়েছে।',
                'session_id' => $request->session()->getId(),
                'csrf_token' => csrf_token(),
            ]);
        }

        return back()->with('success', 'সেশন সফলভাবে রোটেট করা হয়েছে।');
    }

    /**
     * Refresh web session & CSRF token.
     */
    public function refreshSession(Request $request): JsonResponse
    {
        $request->session()->regenerate();
        $request->session()->put('last_activity_time', time());

        return response()->json([
            'success' => true,
            'message' => 'সেশন সফলভাবে রিফ্রেশ করা হয়েছে।',
            'csrf_token' => csrf_token(),
        ]);
    }
}
