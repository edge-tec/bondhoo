<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EmailLog;
use App\Models\EmailVerification;
use App\Models\FailedLogin;
use App\Models\FailedLoginAttempt;
use App\Models\LoginHistory;
use App\Models\OtpCode;
use App\Models\OtpLog;
use App\Models\PasswordHistory;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuthManagementController extends Controller
{
    /**
     * Get Authentication Dashboard Statistics.
     * Incorporates all 8 Step 14 Widgets while preserving backward-compatible metrics.
     */
    public function dashboardStats(): JsonResponse
    {
        $totalUsers = User::withTrashed()->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->orWhereNotNull('phone_verified_at')->count();
        $pendingVerification = User::whereNull('email_verified_at')->whereNull('phone_verified_at')->where('status', 'pending')->count();
        $suspendedUsers = User::where('status', 'suspended')->count();
        $blockedUsers = User::where('status', 'banned')->count();
        $deletedUsers = User::onlyTrashed()->count();

        // 1. Online Users (active within 15 minutes)
        $onlineUsers = User::where('last_login_at', '>=', now()->subMinutes(15))->count();

        // 2. Failed Logins (24h, total, recent)
        $failedLogins24h = FailedLoginAttempt::where('attempted_at', '>=', now()->subDay())->count()
            + FailedLogin::where('attempted_at', '>=', now()->subDay())->count();
        $failedLoginsTotal = FailedLoginAttempt::count() + FailedLogin::count();
        $recentFailed = FailedLoginAttempt::latest('attempted_at')->take(10)->get();
        if ($recentFailed->isEmpty()) {
            $recentFailed = FailedLogin::latest('attempted_at')->take(10)->get();
        }

        // 3. Locked Accounts
        $lockedAccounts = User::whereNotNull('locked_until')->where('locked_until', '>', now())->count();

        // 4. Active Sessions
        $activeSessions = UserSession::count();

        // 5. OTP Logs
        $otpTotal = OtpCode::count() + OtpLog::count();
        $otpUsed = OtpCode::where('is_used', true)->count() + OtpLog::where('is_used', true)->count();
        $otpExpired = OtpCode::where('expires_at', '<', now())->where('is_used', false)->count()
            + OtpLog::where('expires_at', '<', now())->where('is_used', false)->count();

        // 6. Email Verification Logs
        $emailVerificationsTotal = EmailVerification::count() + EmailLog::count();
        $emailVerified = EmailVerification::whereNotNull('verified_at')->count();
        $emailPending = EmailVerification::whereNull('verified_at')->where('expires_at', '>', now())->count();

        // 7. Password Reset Logs
        $passwordResetsTotal = OtpCode::where('purpose', 'password_reset')->count()
            + PasswordHistory::count()
            + DB::table('password_reset_tokens')->count();
        $passwordChangesCount = PasswordHistory::count();

        // 8. Login Analytics
        $totalLogins = LoginHistory::count();
        $byStatus = LoginHistory::select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->toArray();
        $byBrowser = LoginHistory::whereNotNull('browser')
            ->select('browser', DB::raw('count(*) as aggregate'))
            ->groupBy('browser')
            ->orderByDesc('aggregate')
            ->take(5)
            ->pluck('aggregate', 'browser')
            ->toArray();
        $byOs = LoginHistory::whereNotNull('os')
            ->select('os', DB::raw('count(*) as aggregate'))
            ->groupBy('os')
            ->orderByDesc('aggregate')
            ->take(5)
            ->pluck('aggregate', 'os')
            ->toArray();
        $byDevice = LoginHistory::whereNotNull('device_type')
            ->select('device_type', DB::raw('count(*) as aggregate'))
            ->groupBy('device_type')
            ->pluck('aggregate', 'device_type')
            ->toArray();
        $byCountry = LoginHistory::whereNotNull('country')
            ->select('country', DB::raw('count(*) as aggregate'))
            ->groupBy('country')
            ->orderByDesc('aggregate')
            ->take(5)
            ->pluck('aggregate', 'country')
            ->toArray();

        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $last7Days[$date] = LoginHistory::whereDate('created_at', $date)->count();
        }

        return $this->successResponse([
            // 8 Required Dashboard Widgets
            'online_users' => $onlineUsers,
            'failed_logins' => [
                'last_24h' => $failedLogins24h,
                'total' => $failedLoginsTotal,
                'recent' => $recentFailed,
            ],
            'locked_accounts' => $lockedAccounts,
            'active_sessions' => $activeSessions,
            'otp_logs' => [
                'total' => $otpTotal,
                'used' => $otpUsed,
                'expired' => $otpExpired,
            ],
            'email_verification_logs' => [
                'total' => $emailVerificationsTotal,
                'verified' => $emailVerified,
                'pending' => $emailPending,
            ],
            'password_reset_logs' => [
                'total' => $passwordResetsTotal,
                'password_changes' => $passwordChangesCount,
            ],
            'login_analytics' => [
                'total' => $totalLogins,
                'by_status' => $byStatus,
                'by_browser' => $byBrowser,
                'by_os' => $byOs,
                'by_device' => $byDevice,
                'by_country' => $byCountry,
                'last_7_days' => $last7Days,
            ],

            // Backward-compatible metrics for existing tests
            'total_users' => $totalUsers,
            'verified_users' => $verifiedUsers,
            'pending_verification' => $pendingVerification,
            'suspended_users' => $suspendedUsers,
            'blocked_users' => $blockedUsers,
            'deleted_users' => $deletedUsers,
            'failed_logins_24h' => $failedLogins24h,
        ]);
    }

    /**
     * Search & Filter Users for Auth Management.
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::with(['profile', 'roles', 'sessions']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->boolean('locked_only')) {
            $query->whereNotNull('locked_until')->where('locked_until', '>', now());
        }

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $users = $query->latest()->paginate(15);

        return $this->successResponse($users);
    }

    /**
     * Active Sessions List with Search & Filters.
     */
    public function sessions(Request $request): JsonResponse
    {
        $query = UserSession::with('user');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('device_name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('browser', 'like', "%{$search}%")
                    ->orWhere('os', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('browser')) {
            $query->where('browser', $request->input('browser'));
        }

        if ($request->filled('os')) {
            $query->where('os', $request->input('os'));
        }

        if ($request->boolean('is_current')) {
            $query->where('is_current', true);
        }

        $sessions = $query->latest('last_active_at')->paginate(20);

        return $this->successResponse($sessions);
    }

    /**
     * Revoke an individual user device session.
     */
    public function revokeSession(Request $request, int $id): JsonResponse
    {
        $session = UserSession::with('user')->findOrFail($id);

        if ($session->token_id) {
            PersonalAccessToken::find($session->token_id)?->delete();
        }

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'admin.revoke_session',
            'entity_type' => UserSession::class,
            'entity_id' => $session->id,
            'old_values' => [
                'user_id' => $session->user_id,
                'device_name' => $session->device_name,
                'ip_address' => $session->ip_address,
            ],
            'new_values' => ['revoked_at' => now()->toDateTimeString()],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $session->delete();

        return $this->successResponse(['revoked' => true], 'ব্যবহারকারীর ডিভাইস সেশন সফলভাবে বাতিল করা হয়েছে।');
    }

    /**
     * Email Verification Logs with Search & Filters.
     */
    public function emailVerificationLogs(Request $request): JsonResponse
    {
        $query = EmailVerification::with('user');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'verified') {
                $query->whereNotNull('verified_at');
            } elseif ($request->input('status') === 'pending') {
                $query->whereNull('verified_at')->where('expires_at', '>', now());
            } elseif ($request->input('status') === 'expired') {
                $query->whereNull('verified_at')->where('expires_at', '<=', now());
            }
        }

        $logs = $query->latest()->paginate(20);

        return $this->successResponse($logs);
    }

    /**
     * Password Reset & History Logs with Search.
     */
    public function passwordResetLogs(Request $request): JsonResponse
    {
        $query = PasswordHistory::with('user');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($uq) use ($search) {
                $uq->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest('created_at')->paginate(20);

        return $this->successResponse($logs);
    }

    /**
     * Admin override: verify email.
     */
    public function verifyEmail(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update([
            'email_verified_at' => now(),
            'status' => $user->status === 'pending' ? 'active' : $user->status,
        ]);

        $this->logAdminAction($request, 'admin.verify_email', $user);

        return $this->successResponse(['verified' => true], 'ইমেইল ম্যানুয়ালি ভেরিফাই করা হয়েছে।');
    }

    /**
     * Admin override: verify mobile.
     */
    public function verifyMobile(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update([
            'phone_verified_at' => now(),
            'status' => $user->status === 'pending' ? 'active' : $user->status,
        ]);

        $this->logAdminAction($request, 'admin.verify_mobile', $user);

        return $this->successResponse(['verified' => true], 'মোবাইল নম্বর ম্যানুয়ালি ভেরিফাই করা হয়েছে।');
    }

    /**
     * Admin action: activate user.
     */
    public function activateUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);

        $this->logAdminAction($request, 'admin.activate_user', $user);

        return $this->successResponse(['status' => 'active'], 'ব্যবহারকারীর অ্যাকাউন্ট সচল করা হয়েছে।');
    }

    /**
     * Admin action: suspend user.
     */
    public function suspendUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'suspended']);
        $user->tokens()->delete();

        $this->logAdminAction($request, 'admin.suspend_user', $user);

        return $this->successResponse(['status' => 'suspended'], 'ব্যবহারকারীর অ্যাকাউন্ট স্থগিত করা হয়েছে।');
    }

    /**
     * Admin action: ban user.
     */
    public function banUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'banned']);
        $user->tokens()->delete();

        $this->logAdminAction($request, 'admin.ban_user', $user);

        return $this->successResponse(['status' => 'banned'], 'ব্যবহারকারীকে ব্যান করা হয়েছে।');
    }

    /**
     * Admin action: soft delete.
     */
    public function softDeleteUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        $this->logAdminAction($request, 'admin.soft_delete_user', $user);

        return $this->successResponse(['deleted' => true], 'ব্যবহারকারীকে সফলভাবে ডিলিট করা হয়েছে।');
    }

    /**
     * Admin action: restore user.
     */
    public function restoreUser(Request $request, int $id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        $this->logAdminAction($request, 'admin.restore_user', $user);

        return $this->successResponse(['restored' => true], 'ব্যবহারকারীকে সফলভাবে রিস্টোর করা হয়েছে।');
    }

    /**
     * Admin action: force logout.
     */
    public function forceLogout(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->tokens()->delete();
        $user->sessions()->delete();

        $this->logAdminAction($request, 'admin.force_logout', $user);

        return $this->successResponse(['logged_out' => true], 'ব্যবহারকারীকে সমস্ত ডিভাইস থেকে ফোর্স লগআউট করা হয়েছে।');
    }

    /**
     * Admin action: reset password.
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $request->validate(['password' => ['required', 'string', 'min:8']]);
        $user = User::findOrFail($id);
        $user->update([
            'password' => Hash::make($request->input('password')),
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);
        $user->tokens()->delete();

        $this->logAdminAction($request, 'admin.reset_password', $user);

        return $this->successResponse(['reset' => true], 'পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে।');
    }

    /**
     * Admin action: reset 2FA.
     */
    public function reset2fa(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $this->logAdminAction($request, 'admin.reset_2fa', $user);

        return $this->successResponse(['reset_2fa' => true], 'টু-ফ্যাক্টর অথেন্টিকেশন রিসেট করা হয়েছে।');
    }

    /**
     * Admin action: unlock account.
     */
    public function unlockAccount(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->unlockAccount();

        $this->logAdminAction($request, 'admin.unlock_account', $user);

        return $this->successResponse(['unlocked' => true], 'লকড অ্যাকাউন্ট সফলভাবে আনলক করা হয়েছে।');
    }

    /**
     * View user login history.
     */
    public function loginHistory(int $id): JsonResponse
    {
        $history = LoginHistory::where('user_id', $id)->latest()->take(50)->get();

        return $this->successResponse($history);
    }

    /**
     * View OTP History / Logs with Search & Filters.
     */
    public function otpHistory(Request $request): JsonResponse
    {
        return $this->otpLogs($request);
    }

    /**
     * View OTP Logs.
     */
    public function otpLogs(Request $request): JsonResponse
    {
        $query = OtpCode::with('user');

        if ($request->filled('search') || $request->filled('identifier')) {
            $search = $request->input('search', $request->input('identifier'));
            $query->where(function ($q) use ($search) {
                $q->where('identifier', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('purpose')) {
            $query->where('purpose', $request->input('purpose'));
        }

        if ($request->has('is_used')) {
            $query->where('is_used', $request->boolean('is_used'));
        }

        $otps = $query->latest()->paginate(20);

        return $this->successResponse($otps);
    }

    /**
     * View Audit Logs.
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::with('user');
        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->input('action')}%");
        }
        $logs = $query->latest()->paginate(25);

        return $this->successResponse($logs);
    }

    /**
     * View Failed Logins Monitor.
     */
    public function failedLogins(): JsonResponse
    {
        $failed = FailedLoginAttempt::with('user')->latest('attempted_at')->take(50)->get();
        if ($failed->isEmpty()) {
            $failed = FailedLogin::latest('attempted_at')->take(50)->get();
        }

        return $this->successResponse($failed);
    }

    /**
     * CSV Export of Users, Sessions, or Login History.
     */
    public function exportUsers(?Request $request = null): StreamedResponse
    {
        $request = $request ?? request();
        $type = $request->query('type', 'users');

        if ($type === 'sessions') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="jugajug_sessions_'.date('Y-m-d').'.csv"',
            ];

            return response()->stream(function () {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['ID', 'User ID', 'Device Name', 'Browser', 'OS', 'IP Address', 'Location', 'Is Current', 'Last Active At', 'Created At']);

                UserSession::chunk(100, function ($sessions) use ($handle) {
                    foreach ($sessions as $session) {
                        fputcsv($handle, [
                            $session->id,
                            $session->user_id,
                            $session->device_name,
                            $session->browser,
                            $session->os,
                            $session->ip_address,
                            $session->location,
                            $session->is_current ? 'Yes' : 'No',
                            $session->last_active_at?->toDateTimeString(),
                            $session->created_at?->toDateTimeString(),
                        ]);
                    }
                });

                fclose($handle);
            }, 200, $headers);
        }

        if ($type === 'logins' || $type === 'login_history') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="jugajug_logins_'.date('Y-m-d').'.csv"',
            ];

            return response()->stream(function () {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['ID', 'User ID', 'IP Address', 'Browser', 'OS', 'Device Type', 'Country', 'City', 'Status', 'Is Suspicious', 'Created At']);

                LoginHistory::chunk(100, function ($logins) use ($handle) {
                    foreach ($logins as $login) {
                        fputcsv($handle, [
                            $login->id,
                            $login->user_id,
                            $login->ip_address,
                            $login->browser,
                            $login->os,
                            $login->device_type,
                            $login->country,
                            $login->city,
                            $login->status,
                            $login->is_suspicious ? 'Yes' : 'No',
                            $login->created_at?->toDateTimeString(),
                        ]);
                    }
                });

                fclose($handle);
            }, 200, $headers);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="jugajug_users_'.date('Y-m-d').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Username', 'Email', 'Phone', 'Country', 'Status', 'Email Verified', 'Created At']);

            User::chunk(100, function ($users) use ($handle) {
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->id,
                        $user->name,
                        $user->username,
                        $user->email,
                        $user->phone,
                        $user->country,
                        $user->status,
                        $user->email_verified_at ? 'Yes' : 'No',
                        $user->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Helper to log admin actions.
     */
    protected function logAdminAction(Request $request, string $action, User $target): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => User::class,
            'entity_id' => $target->id,
            'new_values' => ['target_username' => $target->username, 'status' => $target->status],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
