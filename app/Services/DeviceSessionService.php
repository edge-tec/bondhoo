<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Models\UserSession;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use App\Services\Messenger\SyncEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Cookie;

class DeviceSessionService
{
    /**
     * Parse User-Agent and request headers to extract device metadata.
     *
     * @return array{browser: string, os: string, device_type: string, country: string, ip: string, fingerprint: string}
     */
    public function parseRequest(Request $request): array
    {
        $userAgent = $request->userAgent() ?: 'Unknown Browser';
        $ip = $request->ip() ?: '127.0.0.1';
        $country = $request->header('CF-IPCountry', $request->header('X-Country-Code', 'BD'));

        // OS detection
        $os = 'Unknown OS';
        if (preg_match('/iphone/i', $userAgent)) {
            $os = 'iOS (iPhone)';
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $os = 'iPadOS';
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/windows nt 10/i', $userAgent)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
        }

        // Browser detection
        $browser = 'Unknown Browser';
        if (preg_match('/edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/chrome|crios/i', $userAgent) && ! preg_match('/opr|opera/i', $userAgent)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/firefox|fxios/i', $userAgent)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/safari/i', $userAgent) && ! preg_match('/chrome|crios/i', $userAgent)) {
            $browser = 'Apple Safari';
        } elseif (preg_match('/opera|opr/i', $userAgent)) {
            $browser = 'Opera';
        }

        // Device type detection
        $deviceType = 'desktop';
        if (preg_match('/mobile|iphone|android.*mobile/i', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/ipad|tablet|android(?!.*mobile)/i', $userAgent)) {
            $deviceType = 'tablet';
        }

        // Detailed device model/name detection
        $device = 'Desktop PC';
        if (preg_match('/iphone/i', $userAgent)) {
            $device = 'Apple iPhone';
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $device = 'Apple iPad';
        } elseif (preg_match('/android/i', $userAgent)) {
            $device = 'Android Device';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $device = 'Macintosh';
        } elseif (preg_match('/windows/i', $userAgent)) {
            $device = 'Windows PC';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $device = 'Linux PC';
        }

        $city = $request->header('CF-IPCity', $request->header('X-City', 'Dhaka'));

        // Compute fingerprint
        $fingerprint = hash('sha256', implode('|', [
            $userAgent,
            $os,
            $browser,
            $request->header('Accept-Language', 'en'),
        ]));

        return [
            'browser' => $browser,
            'device' => $device,
            'os' => $os,
            'device_type' => $deviceType,
            'country' => $country,
            'city' => $city,
            'ip' => $ip,
            'fingerprint' => $fingerprint,
        ];
    }

    /**
     * Record a login attempt into login histories.
     */
    public function recordLoginHistory(
        User $user,
        Request $request,
        string $status = 'success',
        ?string $failureReason = null,
        bool $isSuspicious = false
    ): LoginHistory {
        $meta = $this->parseRequest($request);
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        return LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => $meta['ip'],
            'user_agent' => $request->userAgent(),
            'device' => $meta['device'],
            'browser' => $meta['browser'],
            'os' => $meta['os'],
            'device_type' => $meta['device_type'],
            'country' => $meta['country'],
            'city' => $meta['city'],
            'status' => $status,
            'failure_reason' => $failureReason,
            'is_suspicious' => $isSuspicious,
            'session_id' => $sessionId,
            'login_at' => now(),
            'logout_at' => null,
        ]);
    }

    /**
     * Register or update active user session.
     */
    public function registerSession(User $user, Request $request, ?int $tokenId = null, ?string $deviceName = null): UserSession
    {
        $meta = $this->parseRequest($request);
        $name = $deviceName ?: ($meta['browser'].' on '.$meta['os']);

        // Mark existing sessions as not current
        UserSession::where('user_id', $user->id)->update(['is_current' => false]);

        return UserSession::create([
            'user_id' => $user->id,
            'token_id' => $tokenId,
            'device_name' => $name,
            'device_fingerprint' => $meta['fingerprint'],
            'browser' => $meta['browser'],
            'os' => $meta['os'],
            'ip_address' => $meta['ip'],
            'location' => $meta['country'],
            'is_current' => true,
            'last_active_at' => now(),
        ]);
    }

    /**
     * Check if device is trusted or mark as trusted.
     */
    public function markDeviceTrusted(User $user, Request $request, ?string $name = null, int $days = 30): TrustedDevice
    {
        $meta = $this->parseRequest($request);

        return TrustedDevice::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_fingerprint' => $meta['fingerprint'],
            ],
            [
                'device_name' => $name ?: ($meta['browser'].' on '.$meta['os']),
                'browser' => $meta['browser'],
                'os' => $meta['os'],
                'ip_address' => $meta['ip'],
                'trusted_until' => now()->addDays($days),
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Check if the device is currently trusted.
     */
    public function isDeviceTrusted(User $user, Request $request): bool
    {
        $meta = $this->parseRequest($request);

        $trusted = TrustedDevice::where('user_id', $user->id)
            ->where('device_fingerprint', $meta['fingerprint'])
            ->first();

        return $trusted && $trusted->isValid();
    }

    /**
     * Revoke single session.
     */
    public function revokeSession(User $user, int $sessionId): bool
    {
        $session = UserSession::where('user_id', $user->id)->find($sessionId);
        if (! $session) {
            return false;
        }

        if ($session->token_id) {
            DB::table('personal_access_tokens')->where('id', $session->token_id)->delete();
        }

        // Mark logout_at in LoginHistory for this session/IP
        LoginHistory::where('user_id', $user->id)
            ->whereNull('logout_at')
            ->where(function ($q) use ($session) {
                if ($session->ip_address) {
                    $q->where('ip_address', $session->ip_address);
                }
            })
            ->latest('id')
            ->first()
            ?->update(['logout_at' => now()]);

        $session->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'session.revoked',
            'entity_type' => UserSession::class,
            'entity_id' => $sessionId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    /**
     * Revoke all other sessions except current.
     */
    public function revokeOtherSessions(User $user, ?int $currentTokenId = null): int
    {
        $query = UserSession::where('user_id', $user->id);
        if ($currentTokenId) {
            $query->where('token_id', '!=', $currentTokenId);
            DB::table('personal_access_tokens')
                ->where('tokenable_id', $user->id)
                ->where('tokenable_type', get_class($user))
                ->where('id', '!=', $currentTokenId)
                ->delete();
        } else {
            DB::table('personal_access_tokens')
                ->where('tokenable_id', $user->id)
                ->where('tokenable_type', get_class($user))
                ->delete();
        }

        $count = $query->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'session.revoke_all_others',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $count;
    }

    /**
     * List all active sessions of user.
     */
    public function getUserSessions(User $user): Collection
    {
        return UserSession::where('user_id', $user->id)
            ->latest('last_active_at')
            ->get();
    }

    /**
     * Logout from the current device and clean up session records.
     */
    public function logoutCurrentDevice(User $user, Request $request): void
    {
        $meta = $this->parseRequest($request);

        // Delete from database sessions table if session ID is available
        if ($request->hasSession()) {
            $sessionId = $request->session()->getId();
            if ($sessionId && Schema::hasTable('sessions')) {
                DB::table('sessions')->where('id', $sessionId)->delete();
            }
        }

        // Delete matching UserSession
        UserSession::where('user_id', $user->id)
            ->where(function ($q) use ($meta) {
                $q->where('is_current', true)
                    ->orWhere('device_fingerprint', $meta['fingerprint']);
            })
            ->delete();

        // Mark logout_at in LoginHistory for this session
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        $historyQuery = LoginHistory::where('user_id', $user->id)->whereNull('logout_at');
        if ($sessionId) {
            $historyQuery->where(function ($q) use ($sessionId, $meta) {
                $q->where('session_id', $sessionId)
                    ->orWhere('ip_address', $meta['ip']);
            });
        } else {
            $historyQuery->where('ip_address', $meta['ip']);
        }
        $historyQuery->latest('id')->first()?->update(['logout_at' => now()]);

        // Revoke current Sanctum token if present
        if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        // Revoke bearer token if present
        if ($request->bearerToken()) {
            PersonalAccessToken::findToken($request->bearerToken())?->delete();
        }

        // Revoke authentication cookie tokens if present
        foreach (['bondhoo_token', 'jugajug_token'] as $cookieName) {
            if ($request->hasCookie($cookieName)) {
                PersonalAccessToken::findToken((string) $request->cookie($cookieName))?->delete();
            }
        }

        // Revoke web-session tokens for this user
        if (method_exists($user, 'tokens')) {
            $user->tokens()->whereIn('name', ['jugajug_web', 'bondhoo_web', 'web_profile', 'token'])->delete();
        }

        // Invalidate remember_token for this user
        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();

        // Presence state recalculation on device logout
        try {
            $cacheService = app(CacheServiceInterface::class);
            $pSessionId = (string) ($request->input('session_id')
                ?? $request->header('X-Session-ID')
                ?? ($request->hasSession() ? $request->session()->getId() : '')
                ?? md5($user->id.$meta['ip'].($request->userAgent() ?? '')));
            $transitioned = $cacheService->removeSessionHeartbeat($user->id, $pSessionId);
            if ($transitioned) {
                $lastSeen = $cacheService->getUserLastSeen($user->id);
                app(RealtimeServiceInterface::class)->broadcastPresence($user->id, false, $lastSeen);
                app(SyncEventService::class)->recordEvent('presence.offline', [
                    'user_id' => $user->id,
                    'is_online' => false,
                    'last_seen' => $lastSeen,
                ], $user->id);
            }
        } catch (\Throwable $e) {
            Log::warning('Presence logout cleanup error: '.$e->getMessage());
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'AUTH_LOGOUT_CURRENT_DEVICE',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'ip_address' => $meta['ip'],
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Get expired cookies collection for exhaustive logout termination.
     *
     * @return array<int, Cookie>
     */
    public static function getLogoutCookies(): array
    {
        $cookies = [];
        $names = [
            'bondhoo_token',
            'jugajug_token',
            'bondhoo_admin',
            'jugajug_admin',
            'admin_token',
            config('session.cookie'),
            Auth::guard('web')->getRecallerName(),
            Auth::guard('admin')->getRecallerName(),
            'XSRF-TOKEN',
        ];

        foreach (array_unique(array_filter($names)) as $name) {
            $c = cookie()->forget($name, '/', null);
            $cookies[] = $c;
            cookie()->queue($c);
        }

        return $cookies;
    }

    /**
     * Logout user from all devices: purge sessions, revoke all tokens, and rotate remember_token.
     */
    public function logoutAllDevices(User $user, ?Request $request = null): void
    {
        $ip = $request?->ip() ?? '127.0.0.1';
        $userAgent = $request?->userAgent() ?? 'System';

        // 1. Purge all user sessions from Laravel sessions table
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        // 2. Delete all UserSession records
        UserSession::where('user_id', $user->id)->delete();

        // 3. Mark all active LoginHistories as logged out
        LoginHistory::where('user_id', $user->id)
            ->whereNull('logout_at')
            ->update(['logout_at' => now()]);

        // 4. Revoke all Personal Access Tokens (Sanctum)
        $user->tokens()->delete();

        // 5. Invalidate all Remember Me cookies by rotating remember_token
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        // 6. Set presence immediately to offline across all devices
        try {
            $cacheService = app(CacheServiceInterface::class);
            $cacheService->setUserOffline($user->id);
            $lastSeen = $cacheService->getUserLastSeen($user->id);
            app(RealtimeServiceInterface::class)->broadcastPresence($user->id, false, $lastSeen);
            app(SyncEventService::class)->recordEvent('presence.offline', [
                'user_id' => $user->id,
                'is_online' => false,
                'last_seen' => $lastSeen,
            ], $user->id);
        } catch (\Throwable $e) {
            Log::warning('Presence logoutAll cleanup error: '.$e->getMessage());
        }

        // 6. Remove trusted devices
        TrustedDevice::where('user_id', $user->id)->delete();

        // 7. Record Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'AUTH_LOGOUT_ALL_DEVICES',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Rotate current session ID to protect against session fixation attacks.
     */
    public function rotateSession(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $oldId = $request->session()->getId();
        $request->session()->regenerate(true);
        $newId = $request->session()->getId();

        $request->session()->put('last_activity_time', time());

        /** @var User|null $user */
        $user = $request->user('web') ?? $request->user();
        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'AUTH_SESSION_ROTATED',
                'entity_type' => User::class,
                'entity_id' => $user->id,
                'new_values' => ['old_session_id' => $oldId, 'new_session_id' => $newId],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return true;
    }

    /**
     * Revoke a single trusted device.
     */
    public function revokeTrustedDevice(User $user, int $trustedDeviceId): bool
    {
        $device = TrustedDevice::where('user_id', $user->id)->find($trustedDeviceId);
        if (! $device) {
            return false;
        }

        $device->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'AUTH_TRUSTED_DEVICE_REVOKED',
            'entity_type' => TrustedDevice::class,
            'entity_id' => $trustedDeviceId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    /**
     * Get login history for the user.
     */
    public function getLoginHistory(User $user, int $limit = 20): Collection
    {
        return $user->loginHistories()
            ->latest('login_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
