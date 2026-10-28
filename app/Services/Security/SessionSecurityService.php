<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SessionSecurityService
{
    /**
     * Get Active Session & Token Inventory for Privileged and Monitored Accounts.
     * NEVER exposes raw session IDs, cookies, password reset tokens, MFA secrets, or access tokens.
     */
    public function getSessionSecurityWorkspace(int $limit = 30): array
    {
        $sessions = [];
        $key = (string) config('app.key', 'session_fingerprint_key');

        // 1. Inspect active database sessions if available
        if (Schema::hasTable('sessions')) {
            $dbSessions = DB::table('sessions')
                ->whereNotNull('user_id')
                ->orderByDesc('last_activity')
                ->limit($limit)
                ->get();

            $userIds = $dbSessions->pluck('user_id')->unique()->filter()->values()->all();
            $users = User::whereIn('id', $userIds)
                ->with('roles:id,name')
                ->get()
                ->keyBy('id');

            foreach ($dbSessions as $row) {
                $user = $users->get($row->user_id);
                if (! $user) {
                    continue;
                }

                // Compute a one-way keyed HMAC handle so raw session ID is NEVER exposed
                $safeHandle = 'SES-'.strtoupper(substr(hash_hmac('sha256', (string) $row->id, $key), 0, 12));
                $lastActive = Carbon::createFromTimestamp((int) $row->last_activity);
                $suspiciousSignals = $this->detectSessionSignals($user, (string) $row->ip_address, (string) $row->user_agent);

                $sessions[] = [
                    'session_handle' => $safeHandle,
                    'channel' => 'web_session',
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'roles' => $user->roles->pluck('name')->values()->all(),
                    'ip_address' => $row->ip_address ?? 'unknown',
                    'device_label' => $this->parseSafeDeviceLabel((string) ($row->user_agent ?? '')),
                    'last_activity_at' => $lastActive->toIso8601String(),
                    'last_activity_human' => $lastActive->diffForHumans(),
                    'is_suspicious' => ! empty($suspiciousSignals),
                    'suspicious' => ! empty($suspiciousSignals),
                    'suspicious_indicators' => $suspiciousSignals,
                    'mfa_enrolled' => ! empty($user->two_factor_confirmed_at),
                ];
            }
        }

        // 2. Inspect active Sanctum API tokens (safe metadata only, never token hash or plaintext)
        if (Schema::hasTable('personal_access_tokens')) {
            $tokens = DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderByDesc('id')
                ->limit($limit)
                ->get();

            $userIds = $tokens->pluck('tokenable_id')->unique()->filter()->values()->all();
            $users = User::whereIn('id', $userIds)
                ->with('roles:id,name')
                ->get()
                ->keyBy('id');

            foreach ($tokens as $tok) {
                $user = $users->get($tok->tokenable_id);
                if (! $user) {
                    continue;
                }

                $safeHandle = 'TOK-'.$tok->id.'-'.strtoupper(substr(hash_hmac('sha256', 'tok_'.$tok->id, $key), 0, 8));
                $suspiciousSignals = $this->detectSessionSignals($user, (string) $user->last_login_ip, '');

                $sessions[] = [
                    'session_handle' => $safeHandle,
                    'token_id' => (int) $tok->id,
                    'channel' => 'api_token',
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'roles' => $user->roles->pluck('name')->values()->all(),
                    'ip_address' => $user->last_login_ip ?? 'api-client',
                    'device_label' => 'API Token: '.($tok->name ?: 'default'),
                    'created_at' => $tok->created_at ? Carbon::parse($tok->created_at)->toIso8601String() : null,
                    'last_activity_at' => $tok->last_used_at
                        ? Carbon::parse($tok->last_used_at)->toIso8601String()
                        : ($tok->created_at ? Carbon::parse($tok->created_at)->toIso8601String() : now()->toIso8601String()),
                    'expires_at' => $tok->expires_at ? Carbon::parse($tok->expires_at)->toIso8601String() : null,
                    'is_suspicious' => ! empty($suspiciousSignals),
                    'suspicious_indicators' => $suspiciousSignals,
                    'mfa_enrolled' => ! empty($user->two_factor_confirmed_at),
                ];
            }
        }

        // 3. Audited Session Termination History
        $terminationHistory = AuditLog::with('user:id,name,email')
            ->whereIn('action', [
                'admin_revoke_user_sessions',
                'admin_session_handle_revoked',
                'admin_force_logout_user',
            ])
            ->latest('id')
            ->take(15)
            ->get();

        $adminRoles = Role::whereIn('name', ['ADMIN', 'SUPER_ADMIN'])->pluck('id');
        $privilegedUsersCount = User::whereHas('roles', fn ($q) => $q->whereIn('roles.id', $adminRoles))
            ->orWhere('status', 'super_admin')
            ->count();

        $mfaEnrolledAdminsCount = User::where(function ($q) use ($adminRoles) {
            $q->whereHas('roles', fn ($r) => $r->whereIn('roles.id', $adminRoles))
                ->orWhere('status', 'super_admin');
        })->whereNotNull('two_factor_confirmed_at')->count();

        return [
            'sessions' => $sessions,
            'active_sessions' => $sessions,
            'termination_history' => $terminationHistory,
            'summary' => [
                'active_count' => count($sessions),
                'suspicious_count' => count(array_filter($sessions, fn (array $s) => $s['is_suspicious'])),
                'privileged_accounts_count' => $privilegedUsersCount,
                'privileged_mfa_enrolled_count' => $mfaEnrolledAdminsCount,
                'supported_factors' => ['password_bcrypt', 'totp_rfc6238', 'recovery_codes_sha256'],
                'unimplemented_factors_disclosed' => ['webauthn_passkeys_not_installed'],
            ],
        ];
    }

    /**
     * Revoke a specific session or API token by its safe handle, or invalidate all sessions for a target user.
     */
    public function revokeSessionOrUserSessions(
        ?int $targetUserId,
        ?string $sessionHandle,
        User $admin,
        string $reason
    ): array {
        return DB::transaction(function () use ($targetUserId, $sessionHandle, $admin, $reason) {
            $key = (string) config('app.key', 'session_fingerprint_key');
            $revokedCount = 0;

            if ($sessionHandle) {
                if (str_starts_with($sessionHandle, 'TOK-')) {
                    $parts = explode('-', $sessionHandle);
                    $tokenId = isset($parts[1]) ? (int) $parts[1] : 0;
                    if ($tokenId > 0 && Schema::hasTable('personal_access_tokens')) {
                        $revokedCount += DB::table('personal_access_tokens')->where('id', $tokenId)->delete();
                    }
                } elseif (str_starts_with($sessionHandle, 'SES-') && Schema::hasTable('sessions')) {
                    $rows = DB::table('sessions')->select('id', 'user_id')->get();
                    foreach ($rows as $row) {
                        $computed = 'SES-'.strtoupper(substr(hash_hmac('sha256', (string) $row->id, $key), 0, 12));
                        if (hash_equals($computed, $sessionHandle)) {
                            $revokedCount += DB::table('sessions')->where('id', $row->id)->delete();
                            $targetUserId = $targetUserId ?: (int) $row->user_id;
                        }
                    }
                }

                AuditLog::create([
                    'user_id' => $admin->id,
                    'action' => 'admin_session_handle_revoked',
                    'entity_type' => User::class,
                    'entity_id' => $targetUserId ?? 0,
                    'new_values' => [
                        'session_handle' => $sessionHandle,
                        'revoked_count' => $revokedCount,
                        'reason' => $reason,
                    ],
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                ]);

                return [
                    'terminated' => $revokedCount > 0,
                    'revoked_count' => $revokedCount,
                    'session_handle' => $sessionHandle,
                    'target_user_id' => $targetUserId,
                ];
            }

            if ($targetUserId) {
                $targetUser = User::where('id', $targetUserId)->lockForUpdate()->firstOrFail();
                $tokensRevoked = $targetUser->tokens()->delete();
                $dbSessionsRevoked = 0;

                if (Schema::hasTable('sessions')) {
                    try {
                        $dbSessionsRevoked = DB::table('sessions')->where('user_id', $targetUser->id)->delete();
                    } catch (Throwable) {
                    }
                }

                $revokedCount = $tokensRevoked + $dbSessionsRevoked;

                AuditLog::create([
                    'user_id' => $admin->id,
                    'action' => 'admin_revoke_user_sessions',
                    'entity_type' => User::class,
                    'entity_id' => $targetUser->id,
                    'new_values' => [
                        'target_email' => $targetUser->email,
                        'tokens_revoked_count' => $tokensRevoked,
                        'web_sessions_revoked_count' => $dbSessionsRevoked,
                        'reason' => $reason,
                    ],
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                ]);

                return [
                    'terminated' => true,
                    'revoked_count' => $revokedCount,
                    'tokens_revoked' => $tokensRevoked,
                    'web_sessions_revoked' => $dbSessionsRevoked,
                    'target_user_id' => $targetUser->id,
                ];
            }

            return ['revoked_count' => 0];
        });
    }

    /**
     * Detect suspicious session indicators using real measured application telemetry.
     *
     * @return array<int, string>
     */
    protected function detectSessionSignals(User $user, string $ipAddress, string $userAgent): array
    {
        $signals = [];

        // 1. Privileged account without MFA confirmed
        $isPrivileged = $user->status === 'super_admin'
            || $user->roles->whereIn('name', ['ADMIN', 'SUPER_ADMIN'])->isNotEmpty();

        if ($isPrivileged && empty($user->two_factor_confirmed_at)) {
            $signals[] = 'privileged_session_without_mfa';
        }

        // 2. Recent failed logins from the same IP in the last 1 hour
        if ($ipAddress !== '' && $ipAddress !== 'unknown' && Schema::hasTable('failed_login_attempts')) {
            $recentFailures = DB::table('failed_login_attempts')
                ->where('ip_address', $ipAddress)
                ->where('attempted_at', '>=', now()->subHour())
                ->count();

            if ($recentFailures >= 5) {
                $signals[] = "high_failed_logins_from_ip ({$recentFailures})";
            }
        }

        // 3. Multiple distinct login IPs for this user in the last 1 hour
        if (Schema::hasTable('login_histories')) {
            try {
                $distinctIps = DB::table('login_histories')
                    ->where('user_id', $user->id)
                    ->where('login_at', '>=', now()->subHour())
                    ->distinct()
                    ->count('ip_address');

                if ($distinctIps >= 3) {
                    $signals[] = "rapid_ip_switching ({$distinctIps} IPs/1h)";
                }
            } catch (Throwable) {
            }
        }

        if ($user->risk_score >= 60) {
            $signals[] = "elevated_user_risk_score ({$user->risk_score})";
        }

        return $signals;
    }

    /**
     * Convert raw User-Agent into a safe, human-readable device/browser label.
     */
    protected function parseSafeDeviceLabel(string $userAgent): string
    {
        if ($userAgent === '') {
            return 'Unknown Client';
        }

        $os = 'OS';
        if (stripos($userAgent, 'Macintosh') !== false || stripos($userAgent, 'Mac OS') !== false) {
            $os = 'macOS';
        } elseif (stripos($userAgent, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($userAgent, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
            $os = 'iOS';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            $os = 'Linux';
        }

        $browser = 'Browser';
        if (stripos($userAgent, 'Edg/') !== false) {
            $browser = 'Edge';
        } elseif (stripos($userAgent, 'Chrome/') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($userAgent, 'Firefox/') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($userAgent, 'Safari/') !== false) {
            $browser = 'Safari';
        } elseif (stripos($userAgent, 'Symfony') !== false || stripos($userAgent, 'PHPUnit') !== false) {
            $browser = 'TestRunner';
        }

        return "{$browser} on {$os}";
    }
}
