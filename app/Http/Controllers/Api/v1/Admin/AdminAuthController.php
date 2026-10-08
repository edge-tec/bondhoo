<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminLoginHistory;
use App\Models\AdminSession;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AdminLoginNotification;
use App\Services\DeviceSessionService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    /**
     * POST /api/v1/admin/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'identifier.required' => 'ইমেইল, ইউজারনেম বা মোবাইল নম্বর প্রদান করা আবশ্যক।',
            'password.required' => 'পাসওয়ার্ড প্রদান করা আবশ্যক।',
            'password.min' => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।',
        ]);

        $identifier = strtolower(trim($validated['identifier']));
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        $admin = Admin::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        // Check if locked
        if ($admin && $admin->locked_until && $admin->locked_until->isFuture()) {
            return $this->errorResponse(
                message: "অ্যাকাউন্ট সাময়িকভাবে লক করা হয়েছে। {$admin->locked_until->diffForHumans()} পরে চেষ্টা করুন।",
                statusCode: 423
            );
        }

        // IP allowlist check
        if ($admin && ! $admin->isIpAllowed($ip)) {
            AdminLoginHistory::create([
                'admin_id' => $admin->id,
                'identifier' => $identifier,
                'ip' => $ip,
                'browser' => $userAgent,
                'device' => $validated['device_name'] ?? 'Admin Workstation',
                'login_at' => now(),
                'success' => false,
                'failure_reason' => 'অননুমোদিত আইপি অ্যাড্রেস থেকে প্রবেশের চেষ্টা',
            ]);

            return $this->errorResponse(
                message: 'আপনার আইপি ঠিকানা থেকে অ্যাডমিন প্যানেলে প্রবেশাধিকার নেই।',
                statusCode: 403
            );
        }

        // Verify credentials
        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
            if ($admin) {
                $admin->increment('failed_login_attempts');
                if ($admin->failed_login_attempts >= 5) {
                    $admin->update([
                        'locked_until' => now()->addMinutes(15),
                        'failed_login_attempts' => 0,
                    ]);

                    AuditLog::create([
                        'user_id' => null,
                        'action' => 'ADMIN_ACCOUNT_LOCKED',
                        'entity_type' => Admin::class,
                        'entity_id' => $admin->id,
                        'ip_address' => $ip,
                        'user_agent' => $userAgent,
                    ]);
                }
            }

            AdminLoginHistory::create([
                'admin_id' => $admin?->id,
                'identifier' => $identifier,
                'ip' => $ip,
                'browser' => $userAgent,
                'device' => $validated['device_name'] ?? 'Admin Workstation',
                'login_at' => now(),
                'success' => false,
                'failure_reason' => 'ভুল আইডেন্টিফায়ার বা পাসওয়ার্ড',
            ]);

            AuditLog::create([
                'user_id' => null,
                'action' => 'ADMIN_LOGIN_FAILED',
                'entity_type' => Admin::class,
                'entity_id' => $admin?->id,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            return $this->errorResponse(
                message: 'ভুল লগইন তথ্য প্রদান করা হয়েছে।',
                statusCode: 401
            );
        }

        if ($admin->status !== 'active') {
            return $this->errorResponse(
                message: "অ্যাডমিন অ্যাকাউন্টটি বর্তমানে {$admin->status} অবস্থায় রয়েছে।",
                statusCode: 403
            );
        }

        // Reset failed login attempts
        $admin->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);

        $fingerprint = $request->header('X-Device-Fingerprint') ?: hash('sha256', $ip.($userAgent ?? ''));
        $isTrusted = $admin->isTrustedDevice($fingerprint);

        // Check if Two-Factor Authentication is required (bypassed if device is already trusted)
        if ($admin->two_factor_enabled && $admin->two_factor_secret && ! $isTrusted) {
            $challengeToken = Str::random(64);
            Cache::put("admin_2fa_challenge:{$challengeToken}", [
                'admin_id' => $admin->id,
                'device_name' => $validated['device_name'] ?? 'Admin Workstation',
            ], now()->addMinutes(5));

            return $this->successResponse(
                data: [
                    'requires_2fa' => true,
                    'challenge_token' => $challengeToken,
                    'role' => $admin->role,
                ],
                message: 'দ্বি-স্তরীয় নিরাপত্তা (2FA) কোড যাচাই করুন।'
            );
        }

        // Trust device if requested
        if ($request->boolean('trust_device') || $request->boolean('remember')) {
            $admin->trustDevice($fingerprint, [
                'device_name' => $validated['device_name'] ?? 'Admin Workstation',
                'browser' => $userAgent,
                'ip_address' => $ip,
            ]);
        }

        // Successful Admin Login
        $token = $admin->createToken($validated['device_name'] ?? 'Admin Workstation', ['admin', 'role:'.$admin->role])->plainTextToken;

        // Create Admin Session
        $sessionId = Str::uuid()->toString();
        AdminSession::create([
            'id' => $sessionId,
            'admin_id' => $admin->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_name' => $validated['device_name'] ?? 'Admin Workstation',
            'last_activity' => time(),
        ]);

        AdminLoginHistory::create([
            'admin_id' => $admin->id,
            'identifier' => $identifier,
            'ip' => $ip,
            'browser' => $userAgent,
            'device' => $validated['device_name'] ?? 'Admin Workstation',
            'login_at' => now(),
            'success' => true,
        ]);

        AuditLog::create([
            'user_id' => null,
            'action' => 'ADMIN_LOGIN_SUCCESS',
            'entity_type' => Admin::class,
            'entity_id' => $admin->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        try {
            $admin->notify(new AdminLoginNotification([
                'ip' => $ip,
                'browser' => $userAgent,
                'device' => $validated['device_name'] ?? 'Admin Workstation',
            ]));
        } catch (\Throwable $e) {
            // Log mail exception gracefully
        }

        return $this->successResponse(
            data: [
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'username' => $admin->username,
                    'email' => $admin->email,
                    'role' => $admin->role,
                    'is_super_admin' => $admin->isSuperAdmin(),
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'session_id' => $sessionId,
            ],
            message: 'অ্যাডমিন লগইন সফল হয়েছে।'
        );
    }

    /**
     * POST /api/v1/admin/verify-2fa
     */
    public function verify2fa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'string', 'min:6', 'max:10'],
        ]);

        $cached = Cache::get("admin_2fa_challenge:{$validated['challenge_token']}");
        if (! $cached) {
            return $this->errorResponse('2FA চ্যালেঞ্জ সেশনের মেয়াদ শেষ হয়েছে। পুনরায় লগইন করুন।', 400);
        }

        $admin = Admin::findOrFail($cached['admin_id']);

        // Verify TOTP code
        $isValid = $this->twoFactorService->verifyTotpCode($admin->two_factor_secret, $validated['code']);
        if (! $isValid) {
            // Check recovery codes
            $recoveryCodes = json_decode($admin->two_factor_recovery_codes ?? '[]', true) ?: [];
            if (in_array($validated['code'], $recoveryCodes, true)) {
                $isValid = true;
                $recoveryCodes = array_diff($recoveryCodes, [$validated['code']]);
                $admin->update(['two_factor_recovery_codes' => json_encode(array_values($recoveryCodes))]);
            }
        }

        if (! $isValid) {
            return $this->errorResponse('ভুল ২-ফ্যাক্টর সিকিউরিটি কোড প্রদান করা হয়েছে।', 422);
        }

        Cache::forget("admin_2fa_challenge:{$validated['challenge_token']}");

        $token = $admin->createToken($cached['device_name'] ?? 'Admin Workstation', ['admin', 'role:'.$admin->role])->plainTextToken;

        $sessionId = Str::uuid()->toString();
        AdminSession::create([
            'id' => $sessionId,
            'admin_id' => $admin->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_name' => $cached['device_name'] ?? 'Admin Workstation',
            'last_activity' => time(),
        ]);

        if ($request->boolean('trust_device') || $request->boolean('remember')) {
            $fingerprint = $request->header('X-Device-Fingerprint') ?: hash('sha256', $request->ip().($request->userAgent() ?? ''));
            $admin->trustDevice($fingerprint, [
                'device_name' => $cached['device_name'] ?? 'Admin Workstation',
                'browser' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ]);
        }

        return $this->successResponse(
            data: [
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'username' => $admin->username,
                    'email' => $admin->email,
                    'role' => $admin->role,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'session_id' => $sessionId,
            ],
            message: '২-ফ্যাক্টর যাচাইকরণ সফল হয়েছে।'
        );
    }

    /**
     * POST /api/v1/admin/logout
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = $request->user('admin') ?? $request->user('admin-api') ?? $request->user();

        if ($admin) {
            if (method_exists($admin, 'currentAccessToken')) {
                $admin->currentAccessToken()?->delete();
            }

            if ($admin instanceof Admin) {
                AdminLoginHistory::where('admin_id', $admin->id)
                    ->whereNull('logout_at')
                    ->latest()
                    ->first()
                    ?->update(['logout_at' => now()]);
            }

            AuditLog::create([
                'user_id' => null,
                'action' => 'ADMIN_LOGOUT',
                'entity_type' => Admin::class,
                'entity_id' => $admin->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::guard('admin')->logout();
        Auth::guard('web')->logout();
        try {
            Auth::guard('admin-api')->forgetUser();
            Auth::guard('sanctum')->forgetUser();
        } catch (\Throwable) {
        }
        Auth::forgetGuards();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $res = $this->successResponse(message: 'সফলভাবে অ্যাডমিন লগআউট সম্পন্ন হয়েছে।');

        foreach (DeviceSessionService::getLogoutCookies() as $cookie) {
            $res->withCookie($cookie);
        }
        $res->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $res->headers->set('Pragma', 'no-cache');

        return $res;
    }

    /**
     * GET /api/v1/admin/sessions
     */
    public function sessions(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $sessions = $admin->sessions()->latest('last_activity')->get();

        return $this->successResponse(data: $sessions);
    }

    /**
     * DELETE /api/v1/admin/session/{id}
     */
    public function revokeSession(Request $request, string $id): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $admin->sessions()->where('id', $id)->delete();

        return $this->successResponse(message: 'অ্যাডমিন সেশন সফলভাবে প্রত্যাহার করা হয়েছে।');
    }

    /**
     * GET /api/v1/admin/trusted-devices
     */
    public function trustedDevices(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $devices = $admin->trustedDevices()->latest('last_used_at')->get();

        return $this->successResponse(data: $devices);
    }

    /**
     * DELETE /api/v1/admin/trusted-device/{id}
     */
    public function revokeTrustedDevice(Request $request, int $id): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $admin->trustedDevices()->where('id', $id)->delete();

        return $this->successResponse(message: 'বিশ্বস্ত ডিভাইস সফলভাবে বাতিল করা হয়েছে।');
    }

    /**
     * POST /admin/login (Web session-based Admin Login)
     */
    public function webLogin(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'identifier.required' => 'ইমেইল, ইউজারনেম বা মোবাইল নম্বর প্রদান করা আবশ্যক।',
            'password.required' => 'পাসওয়ার্ড প্রদান করা আবশ্যক।',
            'password.min' => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।',
        ]);

        $identifier = strtolower(trim($validated['identifier']));
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        $admin = Admin::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if ($admin && ! $admin->isIpAllowed($ip)) {
            $msg = 'আপনার আইপি ঠিকানা থেকে অ্যাডমিন প্যানেলে প্রবেশাধিকার নেই।';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }

            return back()->withErrors(['identifier' => $msg]);
        }

        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
            // Check User model for accounts with administrative roles
            $userAdmin = User::where('email', $identifier)
                ->orWhere('username', $identifier)
                ->orWhere('phone', $identifier)
                ->first();

            if ($userAdmin && $userAdmin->isAdmin() && Hash::check($validated['password'], $userAdmin->password)) {
                $admin = Admin::firstOrCreate(
                    ['username' => $userAdmin->username],
                    [
                        'name' => $userAdmin->name,
                        'email' => $userAdmin->email,
                        'phone' => $userAdmin->phone,
                        'password' => $userAdmin->password,
                        'role' => 'admin',
                        'status' => 'active',
                    ]
                );
            }
        }

        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
            $msg = 'ভুল অ্যাডমিন লগইন তথ্য প্রদান করা হয়েছে।';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 401);
            }

            return back()->withErrors(['identifier' => $msg]);
        }

        Auth::guard('admin')->login($admin, (bool) ($validated['remember'] ?? false));
        $request->session()->regenerate();

        $apiToken = $admin->createToken('admin_web_session', ['admin', 'manage.users', 'auth.admin'])->plainTextToken;
        $request->session()->put('admin_api_token', $apiToken);

        $sessionId = Str::uuid()->toString();
        AdminSession::create([
            'id' => $sessionId,
            'admin_id' => $admin->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_name' => 'Web Browser',
            'last_activity' => time(),
        ]);

        AdminLoginHistory::create([
            'admin_id' => $admin->id,
            'identifier' => $identifier,
            'ip' => $ip,
            'browser' => $userAgent,
            'device' => 'Web Browser',
            'login_at' => now(),
            'success' => true,
        ]);

        AuditLog::create([
            'user_id' => null,
            'action' => 'ADMIN_LOGIN_SUCCESS',
            'entity_type' => Admin::class,
            'entity_id' => $admin->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        try {
            $admin->notify(new AdminLoginNotification([
                'ip' => $ip,
                'browser' => $userAgent,
                'device' => 'Web Browser',
            ]));
        } catch (\Throwable $e) {
            // Ignore email fail in testing
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'অ্যাডমিন লগইন সফল হয়েছে।',
                'redirect' => '/admin/auth-management',
            ]);
        }

        return redirect()->intended('/admin/auth-management');
    }

    /**
     * GET /admin/login (Dedicated Separate Admin Login Screen)
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check() || (Auth::guard('web')->user()?->isAdmin())) {
            return redirect('/admin/auth-management');
        }

        return view('admin.auth.login');
    }

    /**
     * POST /admin/logout (Dedicated Web Admin Logout)
     */
    public function webLogout(Request $request): RedirectResponse
    {
        /** @var Admin|null $admin */
        $admin = Auth::guard('admin')->user() ?? $request->user('admin') ?? $request->user();

        if ($admin) {
            if (method_exists($admin, 'currentAccessToken')) {
                $admin->currentAccessToken()?->delete();
            }

            if ($admin instanceof Admin) {
                AdminLoginHistory::where('admin_id', $admin->id)
                    ->whereNull('logout_at')
                    ->latest()
                    ->first()
                    ?->update(['logout_at' => now()]);
            }

            AuditLog::create([
                'user_id' => null,
                'action' => 'ADMIN_WEB_LOGOUT',
                'entity_type' => Admin::class,
                'entity_id' => $admin->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::guard('admin')->logout();
        Auth::guard('web')->logout();
        try {
            Auth::guard('admin-api')->forgetUser();
            Auth::guard('sanctum')->forgetUser();
        } catch (\Throwable) {
        }
        Auth::forgetGuards();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $response = redirect()->route('admin.login')->with('success', 'সফলভাবে অ্যাডমিন লগআউট সম্পন্ন হয়েছে।');

        foreach (DeviceSessionService::getLogoutCookies() as $cookie) {
            $response->withCookie($cookie);
        }

        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
