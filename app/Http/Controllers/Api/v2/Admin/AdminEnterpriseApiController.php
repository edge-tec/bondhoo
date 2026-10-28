<?php

namespace App\Http\Controllers\Api\v2\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdminAlert;
use App\Models\AdminElevation;
use App\Models\AuditCheckpoint;
use App\Models\AuditLog;
use App\Models\Backup;
use App\Models\CreatorPayoutRequest;
use App\Models\Group;
use App\Models\Page;
use App\Models\Permission;
use App\Models\PlatformAnnouncement;
use App\Models\PlatformSetting;
use App\Models\PlatformSettingHistory;
use App\Models\Post;
use App\Models\PrivacyRequest;
use App\Models\Report;
use App\Models\Role;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserRestriction;
use App\Services\Admin\AdminDashboardService;
use App\Services\Admin\AdminExecutiveAnalyticsService;
use App\Services\Admin\BackgroundJobsControlService;
use App\Services\Admin\DataGovernanceService;
use App\Services\Admin\EnterpriseAccessControlService;
use App\Services\Admin\EnterpriseGovernanceAndSloService;
use App\Services\Admin\FeatureFlagService;
use App\Services\Admin\GlobalInvestigationSearchService;
use App\Services\Admin\InfrastructureHealthService;
use App\Services\Admin\OperationsAlertService;
use App\Services\Admin\ProductionReadinessService;
use App\Services\Audit\TamperEvidentAuditService;
use App\Services\BackupService;
use App\Services\Moderation\AiModerationService;
use App\Services\Security\AbuseDetectionEngineService;
use App\Services\Security\SecurityOperationsCenterService;
use App\Services\Security\SessionSecurityService;
use App\Services\Security\ThreatDetectionAndResponseService;
use App\Services\Security\ZeroTrustPolicyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminEnterpriseApiController extends Controller
{
    public function __construct(
        protected AdminDashboardService $dashboardService,
        protected AiModerationService $aiModerationService,
        protected AdminExecutiveAnalyticsService $analyticsService,
        protected SecurityOperationsCenterService $socService,
        protected TamperEvidentAuditService $auditService,
        protected AbuseDetectionEngineService $abuseEngine,
        protected EnterpriseAccessControlService $accessControl,
        protected FeatureFlagService $flagService,
        protected OperationsAlertService $alertService,
        protected BackgroundJobsControlService $jobsControlService,
        protected InfrastructureHealthService $healthService,
        protected GlobalInvestigationSearchService $globalSearchService,
        protected DataGovernanceService $governanceService,
        protected ProductionReadinessService $readinessService,
        protected BackupService $backupService,
        protected EnterpriseGovernanceAndSloService $enterpriseGovernanceService,
        protected ZeroTrustPolicyService $zeroTrustService,
        protected SessionSecurityService $sessionSecurityService,
        protected ThreatDetectionAndResponseService $threatDetectionService
    ) {}

    /**
     * Helper to get authenticated admin or user.
     */
    protected function getAuthenticatedAdmin(): ?User
    {
        /** @var User|null $user */
        $user = Auth::guard('admin')->user()
            ?? Auth::guard('sanctum')->user()
            ?? Auth::guard('web')->user();

        return $user;
    }

    /**
     * Enforce MFA on privileged operations when security_require_admin_mfa is enabled.
     */
    protected function assertPrivilegedMfaIfRequired(?User $admin): ?JsonResponse
    {
        if (! $admin) {
            return null;
        }

        $requireMfa = filter_var(PlatformSetting::get('security_require_admin_mfa', false), FILTER_VALIDATE_BOOLEAN);
        if ($requireMfa && ! $admin->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'error_code' => 'MFA_REQUIRED',
                'message' => 'প্রিভিলেজড অপারেশন সম্পাদনের জন্য অ্যাডমিন টু-ফ্যাক্টর অথেনটিকেশন (MFA) সক্রিয় থাকা বাধ্যতামূলক।',
            ], 403);
        }

        return null;
    }

    /**
     * 1. Executive Dashboard Overview (KPIs, Charts, Trends).
     */
    public function overview(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 14);
        if ($days < 7 || $days > 90) {
            $days = 14;
        }

        $kpis = $this->dashboardService->getExecutiveKpis();
        $analytics = $this->dashboardService->getAnalyticsTrends($days);

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => $kpis,
                'analytics' => $analytics,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * 2. User Management — Filterable, Searchable, Paginated User Directory.
     */
    public function users(Request $request): JsonResponse
    {
        $status = $request->query('status'); // all, active, verified, suspended, banned, new
        $role = $request->query('role');
        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $sortBy = $request->query('sort_by', 'created_at');
        $sortOrder = strtolower((string) $request->query('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = User::with(['profile', 'roles']);

        // Status Filter
        if ($status && $status !== 'all') {
            match ($status) {
                'active' => $query->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'active');
                })->whereNull('locked_until'),
                'verified' => $query->where(function ($q) {
                    $q->whereNotNull('email_verified_at')
                        ->orWhereNotNull('phone_verified_at')
                        ->orWhereHas('roles', fn ($r) => $r->where('name', 'VERIFIED_USER'));
                }),
                'unverified' => $query->whereNull('email_verified_at')->whereNull('phone_verified_at'),
                'suspended' => $query->where(function ($q) {
                    $q->where('status', 'suspended')->orWhereNotNull('locked_until');
                }),
                'banned' => $query->where('status', 'banned'),
                'new' => $query->where('created_at', '>=', Carbon::now()->subDays(7)),
                default => null,
            };
        }

        // Role Filter
        if ($role) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        // Search Query
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sorting
        $allowedSorts = ['id', 'name', 'username', 'email', 'created_at', 'last_login_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest('id');
        }

        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'filters' => [
                    'status' => $status,
                    'role' => $role,
                    'search' => $search !== '' ? $search : null,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
            ],
        ]);
    }

    /**
     * Show detailed profile and analytics for a single user.
     */
    public function showUser(int $id): JsonResponse
    {
        $user = User::with(['profile', 'roles.permissions'])->findOrFail($id);

        $postsCount = Post::where('user_id', $user->id)->count();
        $reportsCount = Report::where('reportable_type', User::class)->where('reportable_id', $user->id)->count();
        $recentAuditLogs = AuditLog::where('user_id', $user->id)->latest()->take(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'stats' => [
                    'posts_count' => $postsCount,
                    'reports_received_count' => $reportsCount,
                ],
                'recent_activity' => $recentAuditLogs,
            ],
        ]);
    }

    /**
     * Perform an administrative action on a user (Ban, Suspend, Activate, Verify, Role Change).
     */
    public function actionUser(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,suspend,ban,verify,unverify,force_logout,reset_password,assign_role'],
            'reason' => ['nullable', 'string', 'max:500'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'new_password' => ['nullable', 'string', 'min:8'],
        ]);

        $action = $validated['action'];

        // Block privilege escalation via assign_role by non-SUPER_ADMIN without roles.manage
        if ($action === 'assign_role' && $admin && ! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'roles.manage')) {
            return response()->json([
                'success' => false,
                'message' => 'রোল পরিবর্তনের জন্য SUPER_ADMIN বা roles.manage অনুমতি আবশ্যক।',
            ], 403);
        }

        // Prevent non-SUPER_ADMIN from modifying a SUPER_ADMIN account
        if ($admin && ! $this->accessControl->isSuperAdmin($admin) && $this->accessControl->isSuperAdmin($user)) {
            return response()->json([
                'success' => false,
                'message' => 'সুপার অ্যাডমিন অ্যাকাউন্টের উপর এই পদক্ষেপ নেওয়ার অনুমতি আপনার নেই।',
            ], 403);
        }

        try {
            $updatedUser = DB::transaction(function () use ($user, $action, $validated, $admin, $request) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                $oldStatus = $lockedUser->status;

                // Protect last active super admin from demotion, suspension, or ban (with row lock)
                if (in_array($action, ['suspend', 'ban'], true) || ($action === 'assign_role' && ($validated['role'] ?? '') !== 'SUPER_ADMIN')) {
                    $this->socService->assertSuperAdminProtection($lockedUser, $action);
                }

                switch ($action) {
                    case 'activate':
                        $lockedUser->status = 'active';
                        $lockedUser->locked_until = null;
                        $lockedUser->failed_login_attempts = 0;
                        $lockedUser->save();
                        break;

                    case 'suspend':
                        $lockedUser->status = 'suspended';
                        $days = $validated['duration_days'] ?? 7;
                        $lockedUser->locked_until = Carbon::now()->addDays($days);
                        $lockedUser->tokens()->delete();
                        $lockedUser->save();
                        break;

                    case 'ban':
                        $lockedUser->status = 'banned';
                        $lockedUser->locked_until = null;
                        $lockedUser->tokens()->delete();
                        $lockedUser->save();
                        break;

                    case 'verify':
                        $lockedUser->email_verified_at = now();
                        $lockedUser->assignRole('VERIFIED_USER');
                        $lockedUser->save();
                        break;

                    case 'unverify':
                        $lockedUser->email_verified_at = null;
                        $lockedUser->phone_verified_at = null;
                        $verifiedRole = Role::where('name', 'VERIFIED_USER')->first();
                        if ($verifiedRole) {
                            $lockedUser->roles()->detach($verifiedRole->id);
                        }
                        $lockedUser->save();
                        break;

                    case 'force_logout':
                        $lockedUser->tokens()->delete();
                        break;

                    case 'reset_password':
                        $password = $validated['new_password'] ?? Str::random(12);
                        $lockedUser->password = Hash::make($password);
                        $lockedUser->tokens()->delete();
                        $lockedUser->save();
                        break;

                    case 'assign_role':
                        if (! empty($validated['role'])) {
                            $lockedUser->roles()->sync([]);
                            $lockedUser->assignRole($validated['role']);
                        }
                        break;
                }

                AuditLog::create([
                    'user_id' => $admin?->id,
                    'action' => "admin_user_{$action}",
                    'entity_type' => User::class,
                    'entity_id' => $lockedUser->id,
                    'old_values' => ['status' => $oldStatus],
                    'new_values' => [
                        'status' => $lockedUser->status,
                        'action' => $action,
                        'reason' => $validated['reason'] ?? null,
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return $lockedUser->fresh(['profile', 'roles']);
            });
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "ব্যবহারকারীর উপর '{$action}' সফলভাবে সম্পন্ন হয়েছে।",
            'data' => $updatedUser,
        ]);
    }

    /**
     * Dry-run preview for bulk user operations.
     */
    public function bulkUsersPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'action' => ['required', 'string', 'in:activate,suspend,verify,unverify,force_logout'],
        ]);

        $users = User::whereIn('id', $validated['user_ids'])
            ->with('roles:id,name')
            ->select('id', 'name', 'email', 'status', 'locked_until')
            ->get();

        $superAdminCount = $users->filter(function ($u) {
            return $u->roles->contains('name', 'SUPER_ADMIN') || $u->status === 'super_admin';
        })->count();

        $preview = [
            'total_affected' => $users->count(),
            'action' => $validated['action'],
            'super_admin_detected' => $superAdminCount > 0,
            'super_admin_count' => $superAdminCount,
            'warning' => $superAdminCount > 0
                ? "সতর্কতা: নির্বাচিত তালিকার মধ্যে {$superAdminCount} জন সুপার-অ্যাডমিন রয়েছে! এই অপারেশনে তাদের অ্যাকাউন্টের নিরাপত্তা প্রভাবিত হতে পারে।"
                : null,
            'sample_users' => $users->take(10)->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'status' => $u->status,
                'is_super_admin' => $u->roles->contains('name', 'SUPER_ADMIN'),
            ]),
        ];

        return response()->json([
            'success' => true,
            'data' => $preview,
        ]);
    }

    /**
     * Bulk Action on multiple users.
     */
    public function bulkUsers(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'action' => ['required', 'string', 'in:activate,suspend,verify,unverify,force_logout'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $userIds = $validated['user_ids'];
        $action = $validated['action'];
        $count = count($userIds);

        try {
            DB::transaction(function () use ($userIds, $action, $count, $validated, $admin, $request) {
                $targetUsers = User::whereIn('id', $userIds)->lockForUpdate()->get();

                if ($action === 'suspend') {
                    foreach ($targetUsers as $targetUser) {
                        if ($this->accessControl->isSuperAdmin($targetUser)) {
                            $this->socService->assertSuperAdminProtection($targetUser, 'suspend');
                        }
                    }
                }

                match ($action) {
                    'activate' => User::whereIn('id', $userIds)->update(['status' => 'active', 'locked_until' => null]),
                    'suspend' => User::whereIn('id', $userIds)->update(['status' => 'suspended', 'locked_until' => Carbon::now()->addDays(7)]),
                    'verify' => User::whereIn('id', $userIds)->update(['email_verified_at' => now()]),
                    'unverify' => User::whereIn('id', $userIds)->update(['email_verified_at' => null, 'phone_verified_at' => null]),
                    'force_logout' => DB::table('personal_access_tokens')->whereIn('tokenable_id', $userIds)->delete(),
                };

                AuditLog::create([
                    'user_id' => $admin?->id,
                    'action' => "admin_users_bulk_{$action}",
                    'entity_type' => User::class,
                    'entity_id' => 0,
                    'old_values' => ['affected_ids' => $userIds],
                    'new_values' => ['action' => $action, 'count' => $count, 'reason' => $validated['reason'] ?? null],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "মোট {$count} জন ব্যবহারকারীর উপর '{$action}' সফলভাবে কার্যকর করা হয়েছে।",
        ]);
    }

    /**
     * 3. Content Management — Posts directory with moderation status.
     */
    public function posts(Request $request): JsonResponse
    {
        $status = $request->query('status'); // all, public, reported, hidden, pinned
        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $query = Post::with(['user.profile', 'media'])->withCount(['comments', 'reactions', 'reports']);

        if ($status === 'reported') {
            $query->has('reports');
        } elseif ($status === 'hidden') {
            $query->where('status', 'hidden');
        } elseif ($status === 'pinned') {
            $query->where('is_pinned', true);
        }

        if ($search !== '') {
            $query->where('content', 'like', "%{$search}%");
        }

        $posts = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'filters' => [
                    'status' => $status,
                    'search' => $search !== '' ? $search : null,
                ],
            ],
        ]);
    }

    /**
     * Perform an action on a post (Hide, Unhide, Pin, Delete).
     */
    public function actionPost(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $post = Post::findOrFail($id);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:hide,unhide,pin,unpin,delete,restore'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $action = $validated['action'];
        $oldValues = ['status' => $post->status, 'is_pinned' => $post->is_pinned];

        switch ($action) {
            case 'hide':
                $post->status = 'hidden';
                $post->save();
                break;
            case 'unhide':
                $post->status = 'published';
                $post->save();
                break;
            case 'pin':
                $post->is_pinned = true;
                $post->save();
                break;
            case 'unpin':
                $post->is_pinned = false;
                $post->save();
                break;
            case 'delete':
                $post->delete();
                break;
            case 'restore':
                if (method_exists($post, 'restore')) {
                    $post->restore();
                }
                break;
        }

        AuditLog::create([
            'user_id' => $admin?->id,
            'action' => "admin_post_{$action}",
            'entity_type' => Post::class,
            'entity_id' => $post->id,
            'old_values' => $oldValues,
            'new_values' => ['action' => $action, 'reason' => $validated['reason'] ?? null],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "পোস্টটির উপর '{$action}' সফলভাবে কার্যকর হয়েছে।",
        ]);
    }

    /**
     * 4. Content Moderation & Safety Queue.
     */
    public function moderationQueue(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');
        $reason = $request->query('reason');
        $priority = $request->query('priority');
        $aiFlagged = $request->query('ai_flagged');
        $assignedTo = $request->query('assigned_to');
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $query = Report::with(['reporter.profile', 'reviewer', 'assignee']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($reason && $reason !== 'all') {
            $query->where('reason', $reason);
        }

        if ($priority && $priority !== 'all') {
            $query->where('priority', $priority);
        }

        if ($aiFlagged === 'true' || $aiFlagged === '1') {
            $query->where('ai_flagged', true);
        }

        if ($assignedTo) {
            $query->where('assigned_to', (int) $assignedTo);
        }

        $reports = $query->latest('id')->paginate($perPage);

        // Map target entity details safely
        $items = collect($reports->items())->map(function ($report) {
            $reportArray = $report->toArray();
            $reportArray['duplicates_count'] = Report::where('duplicate_of_id', $report->id)->count();
            try {
                $target = $report->reportable;
                $reportArray['target_summary'] = match ($report->reportable_type) {
                    Post::class => [
                        'type' => 'post',
                        'content' => Str::limit($target?->content ?? 'Post not found', 80),
                        'author' => $target?->user?->name,
                    ],
                    User::class => [
                        'type' => 'user',
                        'name' => $target?->name,
                        'username' => $target?->username,
                    ],
                    default => ['type' => 'other', 'details' => 'Target entity'],
                };
            } catch (\Throwable) {
                $reportArray['target_summary'] = ['type' => 'deleted', 'details' => 'Item deleted'];
            }

            return $reportArray;
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'filters' => [
                    'status' => $status,
                    'reason' => $reason,
                    'priority' => $priority,
                    'ai_flagged' => $aiFlagged,
                    'assigned_to' => $assignedTo,
                ],
            ],
        ]);
    }

    /**
     * Resolve a moderation report with action (dismiss, actioned, delete_content, suspend_creator).
     */
    public function resolveReport(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $report = Report::findOrFail($id);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:dismiss,resolve,delete_content,suspend_creator'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $action = $validated['action'];
        $notes = $validated['admin_notes'] ?? '';

        if ($action === 'dismiss') {
            $report->status = Report::STATUS_DISMISSED;
        } else {
            $report->status = Report::STATUS_ACTIONED;

            if ($action === 'delete_content') {
                try {
                    $report->reportable?->delete();
                } catch (\Throwable) {
                }
            } elseif ($action === 'suspend_creator') {
                try {
                    $creator = $report->reportable_type === User::class
                        ? $report->reportable
                        : $report->reportable?->user;
                    if ($creator) {
                        $creator->status = 'suspended';
                        $creator->locked_until = Carbon::now()->addDays(7);
                        $creator->save();
                    }
                } catch (\Throwable) {
                }
            }
        }

        $report->reviewed_by = $admin?->id;
        $report->admin_notes = $notes;
        $report->reviewed_at = now();
        $report->save();

        AuditLog::create([
            'user_id' => $admin?->id,
            'action' => "admin_report_{$action}",
            'entity_type' => Report::class,
            'entity_id' => $report->id,
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => $report->status, 'action' => $action, 'notes' => $notes],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'রিপোর্টটি সফলভাবে নিষ্পত্তি করা হয়েছে।',
            'data' => $report,
        ]);
    }

    /**
     * 5. Social Communities — Groups and Pages Management.
     */
    public function socialCommunities(Request $request): JsonResponse
    {
        $type = $request->query('type', 'groups'); // groups or pages
        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        if ($type === 'pages') {
            $query = Page::with('owner');
            if ($search !== '') {
                $query->where('name', 'like', "%{$search}%");
            }
            $pages = $query->latest('id')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $pages->items(),
                'meta' => [
                    'current_page' => $pages->currentPage(),
                    'last_page' => $pages->lastPage(),
                    'per_page' => $pages->perPage(),
                    'total' => $pages->total(),
                    'filters' => [
                        'type' => $type,
                        'search' => $search !== '' ? $search : null,
                    ],
                ],
            ]);
        }

        $query = Group::with('creator');
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }
        $groups = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $groups->items(),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
                'filters' => [
                    'type' => $type,
                    'search' => $search !== '' ? $search : null,
                ],
            ],
        ]);
    }

    /**
     * 6. Messaging & Announcements.
     */
    public function messagingOverview(): JsonResponse
    {
        $totalConversations = Conversation::count();
        $totalMessages = Message::count();
        $announcements = PlatformAnnouncement::with('creator')->latest('id')->take(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_conversations' => $totalConversations,
                'total_messages' => $totalMessages,
                'announcements' => $announcements,
            ],
        ]);
    }

    /**
     * Create an announcement.
     */
    public function createAnnouncement(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string'],
            'type' => ['required', 'string', 'in:info,success,warning,urgent'],
            'target_audience' => ['required', 'string', 'in:all,users,creators,moderators'],
        ]);

        $announcement = PlatformAnnouncement::create([
            ...$validated,
            'is_active' => true,
            'created_by' => $admin?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'প্ল্যাটফর্ম ঘোষণা সফলভাবে সম্প্রচার করা হয়েছে।',
            'data' => $announcement,
        ]);
    }

    /**
     * 7. Roles & Permissions Matrix.
     */
    public function rolesMatrix(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        $allPermissions = Permission::all();
        $elevations = AdminElevation::with(['user:id,name,email', 'granter:id,name'])
            ->latest('id')
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $roles,
                'permissions' => $allPermissions,
                'elevations' => $elevations,
            ],
        ]);
    }

    /**
     * Assign Role to User.
     */
    public function assignRole(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if ($admin && ! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'roles.manage')) {
            return response()->json([
                'success' => false,
                'message' => 'রোল পরিবর্তনের জন্য SUPER_ADMIN বা roles.manage অনুমতি আবশ্যক।',
            ], 403);
        }

        if ($mfaError = $this->assertPrivilegedMfaIfRequired($admin)) {
            return $mfaError;
        }

        if (! $request->has('role_name') && $request->has('role')) {
            $request->merge(['role_name' => $request->input('role')]);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_name' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        try {
            DB::transaction(function () use ($user, $validated, $admin, $request) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                if ($validated['role_name'] !== 'SUPER_ADMIN') {
                    $this->socService->assertSuperAdminProtection($lockedUser, 'demote');
                }
                $lockedUser->roles()->sync([]);
                $lockedUser->assignRole($validated['role_name']);

                AuditLog::create([
                    'user_id' => $admin?->id,
                    'action' => 'admin_assign_role',
                    'entity_type' => User::class,
                    'entity_id' => $lockedUser->id,
                    'new_values' => ['role' => $validated['role_name']],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "ব্যবহারকারীকে '{$validated['role_name']}' রোল প্রদান করা হয়েছে।",
            'data' => $user->fresh('roles'),
        ]);
    }

    /**
     * 8. Security Center — Sessions and Alerts.
     */
    public function securityOverview(): JsonResponse
    {
        $socOverview = $this->socService->getSecurityOverview();
        $recentSecurityEvents = DB::table('security_events')
            ->latest('created_at')
            ->take(15)
            ->get();

        return response()->json([
            'success' => true,
            'data' => array_merge($socOverview, [
                'failed_logins' => $socOverview['recent_failed_logins'] ?? [],
                'security_events' => $recentSecurityEvents,
            ]),
        ]);
    }

    /**
     * 9. Platform Configuration Settings.
     */
    public function settings(): JsonResponse
    {
        $groupedSettings = PlatformSetting::getAllGrouped();

        return response()->json([
            'success' => true,
            'data' => $groupedSettings,
        ]);
    }

    /**
     * Update Platform Configuration Settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string'],
            'settings.*.value' => ['nullable'],
        ]);

        foreach ($validated['settings'] as $item) {
            PlatformSetting::set($item['key'], $item['value'], $admin?->id);
        }

        AuditLog::create([
            'user_id' => $admin?->id,
            'action' => 'admin_update_platform_settings',
            'entity_type' => PlatformSetting::class,
            'entity_id' => 0,
            'new_values' => $validated['settings'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'প্ল্যাটফর্ম কনফিগারেশন সফলভাবে আপডেট ও সংরক্ষিত হয়েছে।',
            'data' => PlatformSetting::getAllGrouped(),
        ]);
    }

    /**
     * 10. Support Center — Support Tickets.
     */
    public function supportTickets(Request $request): JsonResponse
    {
        $status = $request->query('status'); // open, in_progress, resolved, closed
        $priority = $request->query('priority');
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $query = SupportTicket::with(['user.profile', 'assignee']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        $tickets = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'filters' => [
                    'status' => $status,
                    'priority' => $priority,
                ],
            ],
        ]);
    }

    /**
     * Update Support Ticket Status and Internal Notes.
     */
    public function updateTicket(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $ticket = SupportTicket::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,pending_user,resolved,closed'],
            'internal_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $ticket->status = $validated['status'];
        if (isset($validated['internal_notes'])) {
            $ticket->internal_notes = $validated['internal_notes'];
        }
        if ($validated['status'] === SupportTicket::STATUS_RESOLVED) {
            $ticket->resolved_at = now();
        }
        $ticket->assigned_to = $admin?->id;
        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => "সাপোর্ট টিকিট '{$ticket->ticket_number}' স্ট্যাটাস আপডেট করা হয়েছে।",
            'data' => $ticket->fresh(['user.profile', 'assignee']),
        ]);
    }

    /**
     * 11. Monetization & Ads.
     */
    public function monetization(): JsonResponse
    {
        $adCampaigns = AdCampaign::with('user')->latest('id')->take(20)->get();
        $payoutRequests = CreatorPayoutRequest::with('user')->latest('id')->take(20)->get();

        $spentCol = Schema::hasColumn('ad_campaigns', 'spent_budget')
            ? 'spent_budget'
            : (Schema::hasColumn('ad_campaigns', 'amount_spent') ? 'amount_spent' : null);
        $totalRevenue = $spentCol ? (float) (AdCampaign::sum($spentCol) ?? 0) : 0;
        $pendingPayoutsCount = CreatorPayoutRequest::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_ad_revenue' => $totalRevenue,
                'pending_payouts_count' => $pendingPayoutsCount,
                'campaigns' => $adCampaigns,
                'payout_requests' => $payoutRequests,
            ],
        ]);
    }

    /**
     * Approve or Reject Creator Cashout.
     */
    public function actionPayout(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $payout = CreatorPayoutRequest::findOrFail($id);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:approve,reject'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $action = $validated['action'];
        $payout = DB::transaction(function () use ($payout, $action, $validated, $admin, $request) {
            $lockedPayout = CreatorPayoutRequest::where('id', $payout->id)->lockForUpdate()->firstOrFail();
            $lockedPayout->status = $action === 'approve' ? 'approved' : 'rejected';
            $lockedPayout->processed_at = now();
            $lockedPayout->notes = $validated['notes'] ?? null;
            $lockedPayout->save();

            AuditLog::create([
                'user_id' => $admin?->id,
                'action' => "admin_payout_{$action}",
                'entity_type' => CreatorPayoutRequest::class,
                'entity_id' => $lockedPayout->id,
                'new_values' => ['status' => $lockedPayout->status, 'amount' => $lockedPayout->amount],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $lockedPayout;
        });

        return response()->json([
            'success' => true,
            'message' => "ক্যাশআউট রিকোয়েস্ট '{$action}' করা হয়েছে।",
            'data' => $payout,
        ]);
    }

    /**
     * 12. Developer & SRE Infrastructure Operations.
     */
    public function developerOperations(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:clear_cache,optimize_route,restart_queue,reindex_search'],
        ]);

        $action = $validated['action'];
        $output = '';

        try {
            switch ($action) {
                case 'clear_cache':
                    Artisan::call('cache:clear');
                    Artisan::call('config:clear');
                    $output = 'Cache and config successfully cleared.';
                    break;
                case 'optimize_route':
                    Artisan::call('route:cache');
                    Artisan::call('view:cache');
                    $output = 'Route and view caches successfully primed.';
                    break;
                case 'restart_queue':
                    Artisan::call('queue:restart');
                    $output = 'Queue workers gracefully signaled to restart.';
                    break;
                case 'reindex_search':
                    $output = 'Search index refreshed successfully.';
                    break;
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation error: '.$e->getMessage(),
            ], 500);
        }

        AuditLog::create([
            'user_id' => $admin?->id,
            'action' => "admin_developer_{$action}",
            'entity_type' => 'system',
            'entity_id' => 0,
            'new_values' => ['output' => $output],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $output,
        ]);
    }

    /**
     * 13. Audit & Compliance Logs.
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $action = $request->query('action');
        $entityType = $request->query('entity_type');
        $userId = $request->query('user_id');

        $query = AuditLog::with('user');

        if ($action) {
            $query->where('action', 'like', "%{$action}%");
        }
        if ($entityType) {
            $query->where('entity_type', $entityType);
        }
        if ($userId) {
            $query->where('user_id', (int) $userId);
        }

        $logs = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'filters' => [
                    'action' => $action,
                    'entity_type' => $entityType,
                    'user_id' => $userId ? (int) $userId : null,
                ],
            ],
        ]);
    }

    /**
     * 14. Global Search Across Users, Posts, Groups, Tickets, Settings.
     */
    public function globalSearch(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [
                    'users' => [],
                    'posts' => [],
                    'groups' => [],
                    'tickets' => [],
                ],
            ]);
        }

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->select('id', 'name', 'username', 'email')
            ->take(5)
            ->get();

        $posts = Post::where('content', 'like', "%{$query}%")
            ->with('user:id,name,username')
            ->select('id', 'user_id', 'content', 'created_at')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'author' => $p->user?->name,
                'snippet' => Str::limit($p->content, 60),
                'created_at' => $p->created_at?->diffForHumans(),
            ]);

        $groups = Group::where('name', 'like', "%{$query}%")
            ->select('id', 'name', 'privacy')
            ->take(5)
            ->get();

        $tickets = SupportTicket::where('ticket_number', 'like', "%{$query}%")
            ->orWhere('subject', 'like', "%{$query}%")
            ->select('id', 'ticket_number', 'subject', 'status', 'priority')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'posts' => $posts,
                'groups' => $groups,
                'tickets' => $tickets,
            ],
        ]);
    }

    /**
     * 15. Executive Intelligence Analytics (Period comparison, DAU/WAU/MAU/Stickiness).
     */
    public function intelligence(Request $request): JsonResponse
    {
        $range = (string) $request->query('range', '14d');
        if (! in_array($range, ['7d', '14d', '30d', '90d', '1y'], true)) {
            $range = '14d';
        }

        $intelligence = $this->analyticsService->getExecutiveIntelligence($range);

        return response()->json([
            'success' => true,
            'data' => $intelligence,
        ]);
    }

    /**
     * 16. Streamed CSV Export of Executive Intelligence.
     */
    public function exportIntelligence(Request $request): StreamedResponse
    {
        $range = (string) $request->query('range', '30d');
        if (! in_array($range, ['7d', '14d', '30d', '90d', '1y'], true)) {
            $range = '30d';
        }

        return $this->analyticsService->exportAnalyticsCsv($range);
    }

    /**
     * 17. AI Moderation — Scan Content / Report with confidence & heuristic evidence.
     */
    public function scanAiContent(Request $request, int $id): JsonResponse
    {
        $report = Report::findOrFail($id);
        $scanned = $this->aiModerationService->scanReport($report);

        return response()->json([
            'success' => true,
            'message' => 'কন্টেন্টটি সফলভাবে AI মডারেশন মডেল দ্বারা স্ক্যান করা হয়েছে।',
            'data' => $scanned,
        ]);
    }

    /**
     * 18. Submit an Appeal against a moderation decision.
     */
    public function submitAppeal(Request $request, int $id): JsonResponse
    {
        $report = Report::findOrFail($id);
        $admin = $this->getAuthenticatedAdmin();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $appealedReport = $this->aiModerationService->submitAppeal(
            $report,
            $validated['reason'],
            $admin ?? User::firstOrFail()
        );

        return response()->json([
            'success' => true,
            'message' => 'মডারেশন আপিল সফলভাবে জমা নেওয়া হয়েছে।',
            'data' => $appealedReport,
        ]);
    }

    /**
     * 19. Resolve a Moderation Appeal (Overturn or Uphold).
     */
    public function resolveAppeal(Request $request, int $id): JsonResponse
    {
        $report = Report::findOrFail($id);
        $admin = $this->getAuthenticatedAdmin();

        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:overturn,uphold'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $resolved = $this->aiModerationService->resolveAppeal(
            $report,
            $validated['decision'],
            $admin?->id ?? 1,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => $validated['decision'] === 'overturn'
                ? 'আপিল গৃহীত হয়েছে এবং সংশ্লিষ্ট কন্টেন্ট পুনর্বহাল করা হয়েছে।'
                : 'আপিল খারিজ করা হয়েছে এবং পূর্ববর্তী সিদ্ধান্ত বহাল রাখা হয়েছে।',
            'data' => $resolved,
        ]);
    }

    /**
     * 20. Security Operations Center (SOC) Overview & Threats.
     */
    public function socOverview(): JsonResponse
    {
        $overview = $this->socService->getSecurityOverview();

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

    /**
     * 21. Resolve a Security Incident with investigation notes.
     */
    public function resolveIncident(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $event = $this->socService->resolveIncident(
            $id,
            $admin?->id ?? 1,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'নিরাপত্তা ইনসিডেন্ট সফলভাবে নিষ্পত্তি করা হয়েছে।',
            'data' => $event,
        ]);
    }

    /**
     * 22. Platform Settings Instant Rollback.
     */
    public function rollbackSetting(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();

        $validated = $request->validate([
            'key' => ['required', 'string', 'exists:platform_settings,key'],
        ]);

        $restored = PlatformSetting::rollback($validated['key'], $admin?->id);

        if (! $restored) {
            return response()->json([
                'success' => false,
                'message' => 'এই সেটিংসটির জন্য কোনো পূর্ববর্তী সংস্করণ খুঁজে পাওয়া যায়নি।',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "সেটিংস '{$validated['key']}' সফলভাবে পূর্ববর্তী মানে রোলব্যাক করা হয়েছে।",
            'data' => $restored,
        ]);
    }

    /**
     * 23. Platform Setting Revision History.
     */
    public function settingHistory(string $key): JsonResponse
    {
        $history = PlatformSettingHistory::where('key', $key)
            ->with('changedBy:id,name,email')
            ->latest('id')
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * 24. Admin Alerts & Notifications Feed.
     */
    public function alerts(Request $request): JsonResponse
    {
        $severity = $request->query('severity');
        $status = $request->query('status');

        $query = AdminAlert::query();

        if ($severity && $severity !== 'all') {
            $query->where('severity', $severity);
        }

        if ($status && $status !== 'all') {
            if ($status === 'unread') {
                $query->whereNull('read_at');
            } else {
                $query->where('status', $status);
            }
        }

        $alerts = $query->latest('id')->take(30)->get();
        $unreadCount = AdminAlert::whereNull('read_at')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'alerts' => $alerts,
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * 25. Mark single alert as read.
     */
    public function markAlertRead(int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $alert = AdminAlert::findOrFail($id);

        $alert->read_at = now();
        $alert->read_by = $admin?->id;
        $alert->save();

        return response()->json([
            'success' => true,
            'message' => 'সতর্কবার্তা পঠিত হিসেবে চিহ্নিত করা হয়েছে।',
        ]);
    }

    /**
     * 26. Mark all alerts as read.
     */
    public function markAllAlertsRead(): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();

        AdminAlert::whereNull('read_at')->update([
            'read_at' => now(),
            'read_by' => $admin?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'সকল সতর্কবার্তা পঠিত হিসেবে চিহ্নিত করা হয়েছে।',
        ]);
    }

    /**
     * 27. Cryptographic Tamper-Evident Audit Chain Integrity Verification.
     */
    public function verifyAuditIntegrity(): JsonResponse
    {
        $verification = $this->auditService->verifyIntegrity();

        return response()->json([
            'success' => true,
            'data' => $verification,
        ]);
    }

    // =========================================================================
    // PHASE 3 NEXT-GEN COMMAND CENTER ENDPOINTS
    // =========================================================================

    /**
     * 28. Registration-to-Activation Conversion Funnel (Module A)
     */
    public function intelligenceFunnel(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        $startDate = Carbon::today()->subDays($days);
        $intelligence = $this->analyticsService->getExecutiveIntelligence("{$days}d");

        return response()->json([
            'success' => true,
            'data' => $intelligence['funnel'] ?? [],
        ]);
    }

    /**
     * 29. User Retention Cohorts (Module A)
     */
    public function intelligenceCohorts(Request $request): JsonResponse
    {
        $intelligence = $this->analyticsService->getExecutiveIntelligence('30d');

        return response()->json([
            'success' => true,
            'data' => $intelligence['cohorts'] ?? [],
        ]);
    }

    /**
     * 30. Anomaly Detection for Platform Activity (Module A)
     */
    public function intelligenceAnomalies(Request $request): JsonResponse
    {
        $intelligence = $this->analyticsService->getExecutiveIntelligence('14d');

        return response()->json([
            'success' => true,
            'data' => $intelligence['anomalies'] ?? [],
        ]);
    }

    /**
     * 31. Assign Moderation Report to Reviewer (Module B)
     */
    public function assignReport(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $report = Report::findOrFail($id);
        $assigneeId = $validated['assigned_to'] ?? $admin?->id;
        $assignee = User::findOrFail($assigneeId);

        $updated = $this->aiModerationService->assignReport($report, $assignee, $admin ?? $assignee);

        return response()->json([
            'success' => true,
            'message' => "রিপোর্টটি সফলভাবে {$assignee->name}-কে অ্যাসাইন করা হয়েছে।",
            'data' => $updated,
        ]);
    }

    /**
     * 32. Mark Report as False Positive (Module B)
     */
    public function markFalsePositive(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $report = Report::findOrFail($id);
        $reason = $validated['reason'] ?? 'প্রশাসনিক রিভিউতে ফলস-পজিটিভ নিশ্চিতকৃত';
        $updated = $this->aiModerationService->markFalsePositive($report, $admin ?? User::firstOrFail(), $reason);

        return response()->json([
            'success' => true,
            'message' => 'রিপোর্টটি ফলস-পজিটিভ হিসেবে চিহ্নিত ও সংরক্ষণ করা হয়েছে।',
            'data' => $updated,
        ]);
    }

    /**
     * 33. Moderation Quality & Reviewer Metrics (Module B)
     */
    public function moderationMetrics(): JsonResponse
    {
        $metrics = $this->aiModerationService->getReviewerMetrics();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * 34. User Multi-Signal Risk Assessment (Module C)
     */
    public function userRisk(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $risk = $this->abuseEngine->evaluateUserRisk($user);

        return response()->json([
            'success' => true,
            'data' => $risk,
        ]);
    }

    /**
     * 35. User Investigation Chronological Timeline (Module C)
     */
    public function userInvestigationTimeline(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $timeline = $this->abuseEngine->getUserInvestigationTimeline($user);

        return response()->json([
            'success' => true,
            'data' => $timeline,
        ]);
    }

    /**
     * 36. User Related Accounts Correlation (Module C)
     */
    public function userRelatedAccounts(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $related = $this->abuseEngine->getRelatedAccounts($user);

        return response()->json([
            'success' => true,
            'data' => $related,
        ]);
    }

    /**
     * 37. Apply Abuse Restriction on User (Module C)
     */
    public function restrictUser(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $validated = $request->validate([
            'restriction_type' => ['required', 'string', 'in:read_only,post_block,comment_block,message_block,full_ban,rate_limited'],
            'reason' => ['required', 'string', 'max:255'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:8760'],
        ]);

        $targetUser = User::findOrFail($id);
        if ($admin && ! $this->accessControl->isSuperAdmin($admin) && $this->accessControl->isSuperAdmin($targetUser)) {
            return response()->json([
                'success' => false,
                'message' => 'সুপার অ্যাডমিন অ্যাকাউন্টের উপর সীমাবদ্ধতা আরোপের অনুমতি আপনার নেই।',
            ], 403);
        }

        try {
            $restriction = $this->abuseEngine->applyRestriction(
                $targetUser,
                $validated['restriction_type'],
                $validated['reason'],
                $admin,
                $validated['duration_hours'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'ব্যবহারকারীর উপর অপব্যবহার সীমাবদ্ধতা সফলভাবে প্রয়োগ করা হয়েছে।',
            'data' => $restriction,
        ]);
    }

    /**
     * 38. Administrator Override of User Restriction (Module C)
     */
    public function overrideRestriction(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $validated = $request->validate([
            'justification' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $restriction = UserRestriction::findOrFail($id);
        $this->abuseEngine->overrideRestriction($restriction, $admin, $validated['justification']);

        return response()->json([
            'success' => true,
            'message' => 'সীমাবদ্ধতা বাতিল করা হয়েছে এবং ন্যায্যতা অডিট লগে লিপিবদ্ধ হয়েছে।',
            'data' => $restriction->fresh(),
        ]);
    }

    /**
     * 38b. List Active & Recent User Restrictions (Module C)
     */
    public function activeRestrictions(Request $request): JsonResponse
    {
        $restrictions = UserRestriction::with(['user:id,name,username,email', 'creator:id,name'])
            ->latest('id')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $restrictions,
        ]);
    }

    /**
     * 39. Immediate Revocation of Active Sessions (Module D)
     */
    public function revokeSessions(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $targetUser = User::findOrFail($validated['user_id']);
        try {
            $this->socService->assertSuperAdminProtection($targetUser, 'revoke_sessions');
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $revokedCount = $this->socService->revokeUserSessions($targetUser->id, $admin, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => "ব্যবহারকারীর সমস্ত সক্রিয় সেশন ({$revokedCount} টি টোকেন) অবিলম্বে বাতিল করা হয়েছে।",
            'data' => ['revoked_tokens' => $revokedCount],
        ]);
    }

    /**
     * 40. Assign Security Incident to SOC Analyst (Module D)
     */
    public function assignSecurityIncident(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $assigneeId = (int) ($validated['assigned_to'] ?? $admin?->id ?? 1);
        $incident = $this->socService->assignIncident($id, $assigneeId, $admin?->id ?? 1);

        return response()->json([
            'success' => true,
            'message' => 'নিরাপত্তা ঘটনাটি সফলভাবে দায়িত্বপ্রাপ্ত অ্যানালিস্টকে অর্পণ করা হয়েছে।',
            'data' => $incident,
        ]);
    }

    /**
     * 41. Authentication Failure Trends (Module D)
     */
    public function authFailureTrends(): JsonResponse
    {
        $trends = $this->socService->getAuthFailureTrends();

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * 42. Grant Time-Limited Privileged Access Elevation (Module E)
     */
    public function grantElevation(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if ($admin && ! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'roles.manage')) {
            return response()->json([
                'success' => false,
                'message' => 'প্রিভিলেজ এলিভেশন প্রদানের জন্য SUPER_ADMIN বা roles.manage অনুমতি আবশ্যক।',
            ], 403);
        }

        if ($mfaError = $this->assertPrivilegedMfaIfRequired($admin)) {
            return $mfaError;
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'elevated_role' => ['required', 'string', 'in:SUPER_ADMIN,ADMIN,SOC_LEAD,AUDITOR,MODERATOR'],
            'permissions' => ['nullable', 'array'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $targetUser = User::findOrFail($validated['user_id']);
        $elevation = $this->accessControl->grantElevation(
            $targetUser,
            $admin,
            $validated['elevated_role'],
            $validated['permissions'] ?? [],
            $validated['duration_minutes'],
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => "ব্যবহারকারীকে {$validated['duration_minutes']} মিনিটের জন্য '{$validated['elevated_role']}' সুবিধা প্রদান করা হয়েছে।",
            'data' => $elevation,
        ]);
    }

    /**
     * 43. Revoke Privileged Access Elevation (Module E)
     */
    public function revokeElevation(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if ($admin && ! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'roles.manage')) {
            return response()->json([
                'success' => false,
                'message' => 'প্রিভিলেজ এলিভেশন প্রত্যাহারের জন্য SUPER_ADMIN বা roles.manage অনুমতি আবশ্যক।',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $elevation = AdminElevation::findOrFail($id);
        $this->accessControl->revokeElevation($elevation, $admin, $validated['reason'] ?? 'প্রশাসনিক নির্দেশে মেয়াদের পূর্বেই প্রত্যাহারকৃত');

        return response()->json([
            'success' => true,
            'message' => 'বিশেষাধিকার পদোন্নতি সফলভাবে প্রত্যাহার করা হয়েছে।',
        ]);
    }

    /**
     * 44. List Audit Checkpoints (Module F)
     */
    public function auditCheckpoints(): JsonResponse
    {
        $checkpoints = AuditCheckpoint::with('creator:id,name')
            ->latest('checkpoint_number')
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $checkpoints,
        ]);
    }

    /**
     * 45. Create Signed Audit Checkpoint (Module F)
     */
    public function createAuditCheckpoint(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $checkpoint = $this->auditService->createCheckpoint($admin);

        return response()->json([
            'success' => true,
            'message' => "ক্রিপ্টোগ্রাফিক অডিট চেকপয়েন্ট #{$checkpoint->checkpoint_number} সফলভাবে সিল করা হয়েছে।",
            'data' => $checkpoint,
        ]);
    }

    /**
     * 46. Streamed Export of Cryptographic Audit Logs (Module F)
     */
    public function exportAuditLogs(): StreamedResponse
    {
        return $this->auditService->exportAuditLogsCsv();
    }

    /**
     * 47. List Feature Flags (Module G)
     */
    public function featureFlags(): JsonResponse
    {
        $flags = $this->flagService->getAllFlags();

        return response()->json([
            'success' => true,
            'data' => $flags,
        ]);
    }

    /**
     * 48. Create Feature Flag (Module G)
     */
    public function createFeatureFlag(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'unique:platform_feature_flags,key'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_enabled' => ['boolean'],
            'rollout_percentage' => ['integer', 'min:0', 'max:100'],
            'target_roles' => ['nullable', 'array'],
            'target_users' => ['nullable', 'array'],
        ]);

        $flag = $this->flagService->createFlag($validated, $admin);

        return response()->json([
            'success' => true,
            'message' => "ফিচার ফ্ল্যাগ '{$flag->name}' সফলভাবে তৈরি হয়েছে।",
            'data' => $flag,
        ]);
    }

    /**
     * 49. Update Feature Flag (Module G)
     */
    public function updateFeatureFlag(Request $request, string $key): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $validated = $request->validate([
            'is_enabled' => ['boolean'],
            'rollout_percentage' => ['integer', 'min:0', 'max:100'],
            'target_roles' => ['nullable', 'array'],
            'target_users' => ['nullable', 'array'],
            'description' => ['nullable', 'string'],
        ]);

        $flag = $this->flagService->updateFlag($key, $validated, $admin);

        return response()->json([
            'success' => true,
            'message' => "ফিচার ফ্ল্যাগ '{$flag->name}' কনফিগারেশন সংরক্ষিত হয়েছে।",
            'data' => $flag,
        ]);
    }

    /**
     * 50. Acknowledge Admin Alert (Module H)
     */
    public function acknowledgeAlert(int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $alert = $this->alertService->acknowledgeAlert($id, $admin);

        return response()->json([
            'success' => true,
            'message' => 'অ্যালার্টটি সফলভাবে অ্যাকনলেজ করা হয়েছে।',
            'data' => $alert,
        ]);
    }

    /**
     * 51. Resolve Admin Alert (Module H)
     */
    public function resolveAlert(int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $alert = $this->alertService->resolveAlert($id, $admin);

        return response()->json([
            'success' => true,
            'message' => 'অ্যালার্টটি নিষ্পত্তি করা হয়েছে।',
            'data' => $alert,
        ]);
    }

    /**
     * 52. Assign Admin Alert (Module H)
     */
    public function assignAlert(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $assigneeId = $validated['assigned_to'] ?? $admin?->id;
        $assignee = User::findOrFail($assigneeId);
        $alert = $this->alertService->assignAlert($id, $assignee, $admin ?? $assignee);

        return response()->json([
            'success' => true,
            'message' => "অ্যালার্টটি {$assignee->name}-কে অর্পণ করা হয়েছে।",
            'data' => $alert,
        ]);
    }

    /**
     * 53. Operations Alert Summary (Module H)
     */
    public function alertSummary(): JsonResponse
    {
        $summary = $this->alertService->getSummary();

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * 54. Background Queue & Jobs Health (Module I)
     */
    public function jobsHealth(): JsonResponse
    {
        $health = $this->jobsControlService->getQueueHealth();

        return response()->json([
            'success' => true,
            'data' => $health,
        ]);
    }

    /**
     * 55. Inspect Failed Jobs with Redacted Payloads (Module I)
     */
    public function failedJobs(): JsonResponse
    {
        $jobs = $this->jobsControlService->getFailedJobs(30);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * 56. Safe Retry Failed Jobs (Module I)
     */
    public function retryFailedJob(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $validated = $request->validate([
            'job_id' => ['required'], // can be integer or 'all'
        ]);

        if ($validated['job_id'] === 'all') {
            $result = $this->jobsControlService->retryAllFailedJobs($admin);
        } else {
            $result = $this->jobsControlService->retryJob($validated['job_id'], $admin);
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ]);
    }

    /**
     * 57. Delete Failed Job (Module I)
     */
    public function deleteFailedJob(int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $this->jobsControlService->deleteFailedJob($id, $admin);

        return response()->json([
            'success' => true,
            'message' => "ব্যর্থ জব #{$id} সফলভাবে মুছে ফেলা হয়েছে।",
        ]);
    }

    /**
     * 58. Scheduled Tasks List (Module I)
     */
    public function scheduledTasks(): JsonResponse
    {
        $tasks = $this->jobsControlService->getScheduledTasks();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    /**
     * 59. Infrastructure Health Center Summary (Module J)
     */
    public function infrastructureHealth(Request $request): JsonResponse
    {
        $refresh = filter_var($request->query('refresh', false), FILTER_VALIDATE_BOOLEAN);
        $summary = $this->healthService->getHealthSummary($refresh);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * 60. Consolidated User Investigation Dossier (Module K)
     */
    public function userDossier(int $userId): JsonResponse
    {
        $dossier = $this->globalSearchService->generateUserDossier($userId);

        return response()->json([
            'success' => true,
            'data' => $dossier,
        ]);
    }

    /**
     * 61. Data Category Inventory (Module L)
     */
    public function governanceInventory(): JsonResponse
    {
        $inventory = $this->governanceService->getDataInventory();

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    /**
     * 62. Data Retention Policies (Module L)
     */
    public function retentionPolicies(): JsonResponse
    {
        $policies = $this->governanceService->getRetentionPolicies();

        return response()->json([
            'success' => true,
            'data' => $policies,
        ]);
    }

    /**
     * 63. Update Retention Policy (Module L)
     */
    public function updateRetentionPolicy(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $validated = $request->validate([
            'retention_days' => ['required', 'integer', 'min:1'],
            'action' => ['required', 'string', 'in:soft_delete,hard_delete,anonymize'],
            'is_enabled' => ['boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $policy = $this->governanceService->updatePolicy($id, $validated, $admin);

        return response()->json([
            'success' => true,
            'message' => "ধারণ নীতি '{$policy->name}' সফলভাবে আপডেট করা হয়েছে।",
            'data' => $policy,
        ]);
    }

    /**
     * 64. Enforce Retention Policies (Module L)
     */
    public function enforceRetention(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $dryRun = filter_var($request->input('dry_run', false), FILTER_VALIDATE_BOOLEAN);

        $results = $this->governanceService->enforceRetentionPolicies($dryRun, $admin);

        return response()->json([
            'success' => true,
            'message' => $dryRun ? 'ধারণ নীতি সিমুলেশন সম্পন্ন (কোনো ডাটা মোছা হয়নি)।' : 'ডাটা রিটেনশন নীতি কার্যকর সম্পন্ন হয়েছে।',
            'data' => $results,
        ]);
    }

    /**
     * 65. Privacy & GDPR Requests (Module L)
     */
    public function privacyRequests(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $type = $request->query('request_type') ?? $request->query('type');
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $requests = $this->governanceService->getPrivacyRequests([
            'status' => $status,
            'request_type' => $type,
        ], $perPage);

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
                'filters' => [
                    'status' => $status,
                    'request_type' => $type,
                ],
            ],
        ]);
    }

    /**
     * 66. Submit Privacy Request (Module L)
     */
    public function submitPrivacyRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'request_type' => ['required', 'string', 'in:export,erasure,rectification,objection'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $privacyReq = $this->governanceService->submitPrivacyRequest($user, $validated['request_type'], $validated['reason'] ?? null);

        return response()->json([
            'success' => true,
            'message' => "প্রাইভেসি রিকোয়েস্ট #{$privacyReq->request_number} জমা হয়েছে।",
            'data' => $privacyReq,
        ], 201);
    }

    /**
     * 67. Process Privacy Request (Module L)
     */
    public function processPrivacyRequest(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:approve,reject'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $processed = $this->governanceService->processPrivacyRequest($id, $validated['decision'], $admin, $validated['notes'] ?? null);

            return response()->json([
                'success' => true,
                'message' => "প্রাইভেসি রিকোয়েস্ট #{$processed->request_number} সফলভাবে নিষ্পত্তি করা হয়েছে।",
                'data' => $processed,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * 68. Toggle Legal Hold on User Account (Module L)
     */
    public function toggleLegalHold(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if ($mfaError = $this->assertPrivilegedMfaIfRequired($admin)) {
            return $mfaError;
        }
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'is_legal_hold' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $targetUser = User::findOrFail($validated['user_id']);
        $targetUser = $this->governanceService->setLegalHold($targetUser, $validated['is_legal_hold'], $admin, $validated['reason']);

        $statusText = $validated['is_legal_hold'] ? 'আইনি স্থগিতাদেশ (Legal Hold) প্রয়োগ' : 'আইনি স্থগিতাদেশ প্রত্যাহার';

        return response()->json([
            'success' => true,
            'message' => "ব্যবহারকারী {$targetUser->name}-এর উপর {$statusText} সম্পন্ন হয়েছে।",
            'data' => [
                'user_id' => $targetUser->id,
                'is_legal_hold' => (bool) $targetUser->is_legal_hold,
            ],
        ]);
    }

    /**
     * 69. Phase 5 — Production Readiness & Configuration Safety Overview.
     */
    public function productionReadiness(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $persist = $request->boolean('persist', false);
        $report = $this->readinessService->runReadinessAudit($admin, $persist);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * 70. Phase 5 — Execute & Persist a Fresh Production Readiness Audit Run.
     */
    public function runProductionReadinessAudit(): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $report = $this->readinessService->runReadinessAudit($admin, true);

        return response()->json([
            'success' => true,
            'message' => "প্রোডাকশন প্রস্তুতি অডিট ({$report['run_uid']}) সম্পন্ন হয়েছে। স্কোর: {$report['readiness_score']}/100।",
            'data' => $report,
        ]);
    }

    /**
     * 71. Phase 5 — Disaster Recovery Posture, RPO/RTO Objectives & Backup Inventory.
     */
    public function disasterRecoveryStatus(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->backupService->getDisasterRecoveryStatus(),
        ]);
    }

    /**
     * 72. Phase 5 — Update Configurable RPO/RTO Recovery Objectives (SUPER_ADMIN only).
     */
    public function updateDisasterRecoveryObjectives(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'ডিজাস্টার রিকভারি (RPO/RTO) লক্ষ্যমাত্রা পরিবর্তনের জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        if ($mfaError = $this->assertPrivilegedMfaIfRequired($admin)) {
            return $mfaError;
        }

        $validated = $request->validate([
            'rpo_minutes' => ['nullable', 'integer', 'min:5', 'max:10080'],
            'rto_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'backup_frequency_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'restore_drill_frequency_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $status = $this->backupService->updateRecoveryObjectives($validated, $admin);

        return response()->json([
            'success' => true,
            'message' => 'ডিজাস্টার রিকভারি (RPO/RTO) লক্ষ্যমাত্রা সফলভাবে হালনাগাদ করা হয়েছে।',
            'data' => $status,
        ]);
    }

    /**
     * 73. Phase 5 — Create Encrypted Backup with Duplicate Lock & Checksum.
     */
    public function createDisasterRecoveryBackup(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if ($admin && ! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'settings.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'ব্যাকআপ তৈরির জন্য পর্যাপ্ত অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:full,db,media,redis'],
            'disk' => ['nullable', 'string', 'in:local,s3,r2'],
        ]);

        try {
            $backup = $this->backupService->createBackup(
                $validated['type'] ?? 'full',
                $validated['disk'] ?? 'local',
                $admin,
                true
            );

            return response()->json([
                'success' => $backup->status === Backup::STATUS_COMPLETED,
                'message' => $backup->status === Backup::STATUS_COMPLETED
                    ? "এনক্রিপ্টেড ব্যাকআপ ({$backup->backup_uid}) সফলভাবে সম্পন্ন হয়েছে।"
                    : "ব্যাকআপ ব্যর্থ হয়েছে: {$backup->error_message}",
                'data' => $backup,
            ], $backup->status === Backup::STATUS_COMPLETED ? 201 : 500);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * 74. Phase 5 — Verify Backup Checksum, HMAC-SHA256 & Manifest Integrity.
     */
    public function verifyDisasterRecoveryBackup(int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $backup = Backup::findOrFail($id);
        $result = $this->backupService->verifyRestore($backup, $admin);

        return response()->json([
            'success' => $result['verified'],
            'message' => $result['message'],
            'data' => array_merge($result, [
                'backup' => $backup->fresh(),
            ]),
        ], $result['verified'] ? 200 : 422);
    }

    /**
     * 75. Phase 5 — Execute Isolated Restore Verification Drill (SUPER_ADMIN only).
     */
    public function runDisasterRecoveryRestoreDrill(int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'আইসোলেটেড রিস্টোর ড্রিল পরিচালনার জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        if ($mfaError = $this->assertPrivilegedMfaIfRequired($admin)) {
            return $mfaError;
        }

        $backup = Backup::findOrFail($id);
        $drill = $this->backupService->runRestoreDrill($backup, $admin);

        return response()->json([
            'success' => $drill->status === 'passed',
            'message' => $drill->status === 'passed'
                ? "বিচ্ছিন্ন (Isolated) রিস্টোর ড্রিল ({$drill->drill_uid}) সফলভাবে সম্পন্ন হয়েছে ({$drill->duration_ms}ms)।"
                : "রিস্টোর ড্রিল ব্যর্থ হয়েছে: {$drill->error_message}",
            'data' => $drill,
        ], $drill->status === 'passed' ? 200 : 422);
    }

    /**
     * 76. Phase 5 — Delete Backup Artifact with Mandatory Reason & Audit (SUPER_ADMIN only).
     */
    public function deleteDisasterRecoveryBackup(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'ব্যাকআপ মুছে ফেলার জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        if ($mfaError = $this->assertPrivilegedMfaIfRequired($admin)) {
            return $mfaError;
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $backup = Backup::findOrFail($id);
        $this->backupService->deleteBackup($backup, $admin, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'ব্যাকআপ আর্টিফ্যাক্ট এবং রেকর্ড সফলভাবে অপসারণ করা হয়েছে।',
        ]);
    }

    /**
     * 77. Phase 5 — Authorized & Audited Encrypted Backup Download (SUPER_ADMIN only).
     */
    public function downloadDisasterRecoveryBackup(int $id): StreamedResponse|JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'এনক্রিপ্টেড ব্যাকআপ ডাউনলোডের জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        $backup = Backup::findOrFail($id);
        if (! Storage::disk($backup->disk)->exists($backup->filename)) {
            return response()->json([
                'success' => false,
                'message' => 'স্টোরেজে ব্যাকআপ ফাইলটি পাওয়া যায়নি।',
            ], 404);
        }

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'admin_backup_downloaded',
            'entity_type' => Backup::class,
            'entity_id' => $backup->id,
            'new_values' => [
                'backup_uid' => $backup->backup_uid,
                'filename' => $backup->filename,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return Storage::disk($backup->disk)->download($backup->filename, basename($backup->filename));
    }

    /**
     * 78. Phase 5 — SOC Incident Lifecycle Status Transition (acknowledge, investigating, escalate, resolve, reopen).
     */
    public function transitionSecurityIncident(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if ($admin && ! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'security.manage')) {
            return response()->json([
                'success' => false,
                'message' => 'সিকিউরিটি ইনসিডেন্ট পরিবর্তনের অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,acknowledged,investigating,escalated,resolved,reopened'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'evidence_refs' => ['nullable', 'array'],
            'evidence_refs.*' => ['string', 'max:255'],
        ]);

        $incident = $this->socService->transitionIncidentStatus(
            $id,
            $validated['status'],
            $admin?->id ?? 1,
            $validated['notes'] ?? null,
            $validated['evidence_refs'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => "সিকিউরিটি ইনসিডেন্ট স্ট্যাটাস '{$validated['status']}'-এ উন্নীত করা হয়েছে।",
            'data' => $incident,
        ]);
    }

    /**
     * 79. Phase 5 — Authorized & Expiration-Enforced Privacy Export Download.
     */
    public function downloadPrivacyExport(int $id): JsonResponse|StreamedResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || (! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'users.view'))) {
            return response()->json([
                'success' => false,
                'message' => 'প্রাইভেসি এক্সপোর্ট ডাউনলোডের অনুমতি নেই।',
            ], 403);
        }

        $privacyReq = PrivacyRequest::findOrFail($id);

        if ($privacyReq->export_expires_at && Carbon::parse($privacyReq->export_expires_at)->isPast()) {
            // Delete expired temporary export file if present
            if ($privacyReq->export_file_path && Storage::disk('local')->exists($privacyReq->export_file_path)) {
                Storage::disk('local')->delete($privacyReq->export_file_path);
            }

            return response()->json([
                'success' => false,
                'error_code' => 'EXPORT_EXPIRED',
                'message' => 'এই প্রাইভেসি ডাটা এক্সপোর্টের মেয়াদ উত্তীর্ণ হয়েছে এবং নিরাপত্তার স্বার্থে অপসারণ করা হয়েছে।',
            ], 410);
        }

        if (! $privacyReq->export_file_path || ! Storage::disk('local')->exists($privacyReq->export_file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'এক্সপোর্ট ফাইলটি পাওয়া যায়নি।',
            ], 404);
        }

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'admin_privacy_export_downloaded',
            'entity_type' => PrivacyRequest::class,
            'entity_id' => $privacyReq->id,
            'new_values' => [
                'request_number' => $privacyReq->request_number,
                'target_user_id' => $privacyReq->user_id,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return Storage::disk('local')->download($privacyReq->export_file_path, basename($privacyReq->export_file_path));
    }

    // =========================================================================
    // PHASE 6: ZERO-TRUST SECURITY, HIGH AVAILABILITY, COMPLIANCE & AUTONOMOUS OPS
    // =========================================================================

    /**
     * 80. Phase 6 — Public Liveness Probe (No sensitive data disclosed).
     */
    public function livenessProbe(): JsonResponse
    {
        return response()->json($this->enterpriseGovernanceService->getLivenessProbe());
    }

    /**
     * 81. Phase 6 — Public / Readiness Probe (Returns 200 for READY/DEGRADED, 503 for NOT_READY).
     */
    public function readinessProbe(): JsonResponse
    {
        $probe = $this->enterpriseGovernanceService->getReadinessProbe(false);

        return response()->json($probe, (int) ($probe['http_code'] ?? 200));
    }

    /**
     * 82. Phase 6 — Unified Zero-Trust & Enterprise Resilience Workspace Overview.
     */
    public function zeroTrustOverview(): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || (! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'security.view'))) {
            return response()->json([
                'success' => false,
                'message' => 'জিরো-ট্রাস্ট কমান্ড সেন্টার দর্শনের অনুমতি নেই।',
            ], 403);
        }

        $ztOverview = $this->zeroTrustService->getZeroTrustOverview();

        return response()->json([
            'success' => true,
            'data' => [
                ...$ztOverview,
                'zero_trust' => $ztOverview,
                'session_security' => $this->sessionSecurityService->getSessionSecurityWorkspace(20),
                'threat_detection' => $this->threatDetectionService->getThreatDetectionWorkspace(),
                'ha_resilience' => $this->enterpriseGovernanceService->getHaAndResilienceAssessment(),
                'compliance' => $this->enterpriseGovernanceService->getComplianceEvidenceCenter(false),
                'config_drift' => $this->enterpriseGovernanceService->evaluateConfigurationDrift($admin),
                'slos' => $this->enterpriseGovernanceService->getSloAndErrorBudgetReport(),
                'release_gates' => $this->enterpriseGovernanceService->evaluateReleaseGates($admin),
            ],
        ]);
    }

    /**
     * 83. Phase 6 — Evaluate Zero-Trust Access Policy for a Target Operation.
     */
    public function evaluateZeroTrustAccess(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $operationKey = (string) ($request->input('operation_key')
            ?: ($request->input('resource') && $request->input('action') ? $request->input('resource').'.'.$request->input('action') : ''));

        $context = $request->input('context', []);
        if (! is_array($context)) {
            $context = [];
        }
        foreach ($request->all() as $k => $v) {
            if (! in_array($k, ['operation_key', 'resource', 'action', 'context'], true)) {
                $context[$k] = $v;
            }
        }

        $decision = $this->zeroTrustService->evaluateAccess($admin, $operationKey, $context);

        return response()->json([
            'success' => true,
            'data' => $decision,
        ], 200);
    }

    /**
     * 84. Phase 6 — Update a Versioned Zero-Trust Policy (SUPER_ADMIN only).
     */
    public function updateZeroTrustPolicy(Request $request, string $code): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'জিরো-ট্রাস্ট পলিসি পরিবর্তনের জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        if ($mfaError = $this->assertPrivilegedMfaIfRequired($admin)) {
            return $mfaError;
        }

        $validated = $request->validate([
            'effect' => ['nullable', 'string', 'in:allow,deny'],
            'risk_level' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'require_mfa' => ['nullable', 'boolean'],
            'require_jit' => ['nullable', 'boolean'],
            'require_fresh_auth_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'require_dual_approval' => ['nullable', 'boolean'],
            'prevent_self_approval' => ['nullable', 'boolean'],
            'max_risk_score' => ['nullable', 'integer', 'min:10', 'max:100'],
            'require_trusted_device' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $policy = $this->zeroTrustService->updatePolicy($code, $validated, $admin, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => "জিরো-ট্রাস্ট নীতি ({$policy->policy_code} v{$policy->version}) সফলভাবে হালনাগাদ করা হয়েছে।",
            'data' => $policy,
        ]);
    }

    /**
     * 85. Phase 6 — Session Security Workspace (Safe Hashed Handles, Never Raw Session IDs/Tokens).
     */
    public function sessionSecurityWorkspace(): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || (! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'security.soc'))) {
            return response()->json([
                'success' => false,
                'message' => 'সেশন সিকিউরিটি ওয়ার্কস্পেস দর্শনের অনুমতি নেই।',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->sessionSecurityService->getSessionSecurityWorkspace(40),
        ]);
    }

    /**
     * 86. Phase 6 — Terminate a Session by Safe Handle or Invalidate All User Sessions.
     */
    public function terminateSessionHandle(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || (! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'security.soc'))) {
            return response()->json([
                'success' => false,
                'message' => 'সেশন বাতিল করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'session_handle' => ['nullable', 'string', 'max:80'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        if (empty($validated['user_id']) && empty($validated['session_handle'])) {
            return response()->json([
                'success' => false,
                'message' => 'user_id অথবা session_handle প্রদান করা আবশ্যক।',
            ], 422);
        }

        $result = $this->sessionSecurityService->revokeSessionOrUserSessions(
            $validated['user_id'] ?? null,
            $validated['session_handle'] ?? null,
            $admin,
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'নির্বাচিত সেশন/টোকেন সফলভাবে বাতিল করা হয়েছে এবং অডিট লগে সংরক্ষিত হয়েছে।',
            'data' => $result,
        ]);
    }

    /**
     * 87. Phase 6 — Threat Detection Rules, Correlated Detections & Response Actions Workspace.
     */
    public function threatDetectionWorkspace(): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || (! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'security.view'))) {
            return response()->json([
                'success' => false,
                'message' => 'থ্রেট ডিটেকশন ওয়ার্কস্পেস দর্শনের অনুমতি নেই।',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->threatDetectionService->getThreatDetectionWorkspace(),
        ]);
    }

    /**
     * 88. Phase 6 — Run Live Threat Detection Correlation Engine Against Real Telemetry.
     */
    public function evaluateThreatRules(): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || (! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'security.soc'))) {
            return response()->json([
                'success' => false,
                'message' => 'থ্রেট ডিটেকশন ইঞ্জিন পরিচালনার অনুমতি নেই।',
            ], 403);
        }

        $result = $this->threatDetectionService->evaluateDetectionRules($admin);

        return response()->json([
            'success' => true,
            'message' => "থ্রেট ডিটেকশন মূল্যায়ন সম্পন্ন: {$result['new_detections_count']} টি নতুন ঘটনা সনাক্ত ({$result['suppressed_duplicates_count']} টি ডুপ্লিকেট দমনকৃত)।",
            'data' => $result,
        ]);
    }

    /**
     * 89. Phase 6 — Tune a Versioned Threat Detection Rule.
     */
    public function updateThreatRule(Request $request, string $code): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'থ্রেট ডিটেকশন রুল টিউন করার জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        $validated = $request->validate([
            'threshold_count' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'window_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'severity' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'suppression_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'requires_human_approval' => ['nullable', 'boolean'],
            'is_enabled' => ['nullable', 'boolean'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $rule = $this->threatDetectionService->updateRule($code, $validated, $admin, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => "থ্রেট ডিটেকশন রুল ({$rule->rule_code} v{$rule->version}) হালনাগাদ করা হয়েছে।",
            'data' => $rule,
        ]);
    }

    /**
     * 90. Phase 6 — Mark Threat Detection as False Positive.
     */
    public function markThreatDetectionFalsePositive(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || (! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'security.manage'))) {
            return response()->json([
                'success' => false,
                'message' => 'ফলস-পজিটিভ চিহ্নিত করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $detection = $this->threatDetectionService->markDetectionFalsePositive($id, $admin, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => "ডিটেকশন ({$detection->detection_uid}) ফলস-পজিটিভ হিসেবে চিহ্নিত হয়েছে।",
            'data' => $detection,
        ]);
    }

    /**
     * 91. Phase 6 — Request an Automated/Policy-Gated Response Action.
     */
    public function requestResponseAction(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || (! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'security.soc'))) {
            return response()->json([
                'success' => false,
                'message' => 'সিকিউরিটি রেসপন্স অ্যাকশন প্রস্তাব করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'action_type' => ['required', 'string', 'max:64'],
            'target_type' => ['required', 'string', 'max:80'],
            'target_id' => ['nullable', 'integer'],
            'payload' => ['nullable', 'array'],
            'justification' => ['required', 'string', 'min:5', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $action = $this->threatDetectionService->requestResponseAction(
                $validated['action_type'],
                $validated['target_type'],
                $validated['target_id'] ?? null,
                $validated['payload'] ?? [],
                $admin,
                $validated['justification'],
                $validated['idempotency_key'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => $action->status === 'pending_approval'
                    ? "উচ্চ-ঝুঁকির অ্যাকশন ({$action->action_uid}) স্বাধীন মানব অনুমোদনের (Dual Approval) জন্য কিউতে রাখা হয়েছে।"
                    : "রেসপন্স অ্যাকশন ({$action->action_uid}) কার্যকর হয়েছে।",
                'data' => $action,
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * 92. Phase 6 — Approve & Execute a High-Impact Response Action (Anti-Self-Approval Enforced).
     */
    public function approveResponseAction(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'হাই-ইমপ্যাক্ট অ্যাকশন অনুমোদনের জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        if ($mfaError = $this->assertPrivilegedMfaIfRequired($admin)) {
            return $mfaError;
        }

        if ($request->has('reason') && ! $request->has('approval_notes')) {
            $request->merge(['approval_notes' => $request->input('reason')]);
        }
        if ($request->has('notes') && ! $request->has('approval_notes')) {
            $request->merge(['approval_notes' => $request->input('notes')]);
        }

        $validated = $request->validate([
            'approval_notes' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $action = $this->threatDetectionService->approveAndExecuteAction($id, $admin, $validated['approval_notes']);

            return response()->json([
                'success' => true,
                'message' => "রেসপন্স অ্যাকশন ({$action->action_uid}) অনুমোদিত ও কার্যকর করা হয়েছে।",
                'data' => $action,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'APPROVAL_VIOLATION',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * 93. Phase 6 — Reject a Pending Response Action.
     */
    public function rejectResponseAction(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'রেসপন্স অ্যাকশন প্রত্যাখ্যানের জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $action = $this->threatDetectionService->rejectAction($id, $admin, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => "রেসপন্স অ্যাকশন ({$action->action_uid}) প্রত্যাখ্যান করা হয়েছে।",
                'data' => $action,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * 94. Phase 6 — Rollback / Compensate an Executed Response Action.
     */
    public function rollbackResponseAction(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'রেসপন্স অ্যাকশন রোলব্যাক করার জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $action = $this->threatDetectionService->rollbackAction($id, $admin, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => "রেসপন্স অ্যাকশন ({$action->action_uid}) সফলভাবে পূর্বাবস্থায় ফিরিয়ে আনা (Rolled Back) হয়েছে।",
                'data' => $action,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * 95. Phase 6 — High Availability & Multi-Instance Resilience Assessment.
     */
    public function haAndResilienceAssessment(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->enterpriseGovernanceService->getHaAndResilienceAssessment(),
        ]);
    }

    /**
     * 96. Phase 6 — Gate C: Mandatory Off-Site Backup Verification.
     */
    public function verifyOffsiteBackupGateC(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'অফ-সাইট ব্যাকআপ যাচাই করার জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        $disk = $request->input('disk');
        $result = $this->backupService->verifyOffsiteBackupCapability($disk ? (string) $disk : null, $admin);

        return response()->json([
            'success' => $result['verified'],
            'message' => $result['verified']
                ? "অফ-সাইট স্টোরেজ ({$result['disk']}) সফলভাবে যাচাইকৃত।"
                : 'অফ-সাইট স্টোরেজ যাচাই ব্লকড (BLOCKED): '.($result['blocker'] ?? 'Unconfigured'),
            'data' => $result,
        ]);
    }

    /**
     * 96b. Phase 6.1 — Gate C: Isolated Disaster-Recovery Drill with Remote Storage.
     */
    public function runRemoteDisasterRecoveryDrillGateC(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'রিমোট আইসোলেটেড DR ড্রিল পরিচালনার জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        $disk = $request->input('disk');
        $result = $this->backupService->runRemoteDisasterRecoveryDrill($disk ? (string) $disk : null, $admin);

        return response()->json([
            'success' => (bool) ($result['passed'] ?? false),
            'message' => ($result['passed'] ?? false)
                ? "রিমোট স্টোরেজ ({$result['disk']}) ভিত্তিক আইসোলেটেড DR ড্রিল সফলভাবে সম্পন্ন হয়েছে।"
                : 'রিমোট DR ড্রিল ব্যর্থ বা ব্লকড: '.($result['error'] ?? ($result['blocker'] ?? 'Execution blocked')),
            'data' => $result,
        ], ($result['passed'] ?? false) ? 200 : 422);
    }

    /**
     * 97. Phase 6 — Gate D: Encryption Key Recovery & Key Rotation Drill.
     */
    public function runKeyRotationDrillGateD(): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'এনক্রিপশন কী রোটেশন ও রিকভারি ড্রিল পরিচালনার জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        $result = $this->backupService->runKeyRotationAndRecoveryDrill($admin);

        return response()->json([
            'success' => $result['passed'],
            'message' => $result['passed']
                ? 'এনক্রিপশন কী রিকভারি ও রোটেশন ড্রিল (Gate D) সফলভাবে উত্তীর্ণ হয়েছে।'
                : 'এনক্রিপশন কী রোটেশন ড্রিল ব্যর্থ হয়েছে।',
            'data' => $result,
        ], $result['passed'] ? 200 : 422);
    }

    /**
     * 98. Phase 6 — Compliance & Governance Evidence Center.
     */
    public function complianceEvidenceCenter(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || (! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'governance.view'))) {
            return response()->json([
                'success' => false,
                'message' => 'কমপ্লায়েন্স এভিডেন্স সেন্টার দর্শনের অনুমতি নেই।',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->enterpriseGovernanceService->getComplianceEvidenceCenter($request->boolean('refresh', true)),
        ]);
    }

    /**
     * 99. Phase 6 — Review & Sign Off a Compliance Control.
     */
    public function reviewComplianceControl(Request $request, string $controlId): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || (! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'governance.manage'))) {
            return response()->json([
                'success' => false,
                'message' => 'কমপ্লায়েন্স কন্ট্রোল রিভিউ ও সাইন-অফ করার অনুমতি নেই।',
            ], 403);
        }

        if ($request->has('reason') && ! $request->has('notes')) {
            $request->merge(['notes' => $request->input('reason')]);
        }
        if (! $request->has('assessment_outcome')) {
            $request->merge(['assessment_outcome' => 'effective']);
        }

        $validated = $request->validate([
            'evidence_status' => ['nullable', 'string', 'in:verified,auto_collected,manual_supplied,expired,missing,unavailable'],
            'assessment_outcome' => ['required', 'string', 'in:effective,needs_improvement,ineffective,not_assessed'],
            'remediation_task' => ['nullable', 'string', 'max:500'],
            'remediation_due_at' => ['nullable', 'date'],
            'risk_acceptance_reason' => ['nullable', 'string', 'max:500'],
            'risk_acceptance_expires_at' => ['nullable', 'date'],
            'notes' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $control = $this->enterpriseGovernanceService->reviewComplianceControl($controlId, $validated, $admin, $validated['notes']);

        return response()->json([
            'success' => true,
            'message' => "কমপ্লায়েন্স কন্ট্রোল ({$control->control_id}) রিভিউ ও সংরক্ষণ সম্পন্ন হয়েছে।",
            'data' => $control,
        ]);
    }

    /**
     * 100. Phase 6 — Security Configuration Drift Assessment.
     */
    public function configurationDrift(): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || (! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'settings.view'))) {
            return response()->json([
                'success' => false,
                'message' => 'কনফিগারেশন ড্রিফট রিপোর্ট দর্শনের অনুমতি নেই।',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->enterpriseGovernanceService->evaluateConfigurationDrift($admin),
        ]);
    }

    /**
     * 101. Phase 6 — Grant Time-Bounded Configuration Drift Exception (SUPER_ADMIN only).
     */
    public function grantConfigurationDriftException(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'কনফিগারেশন ড্রিফট ব্যতিক্রম অনুমোদনের জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        if (! $request->has('parameter')) {
            $request->merge(['parameter' => 'app_debug']);
        }
        if ($request->has('expires_in_hours') && ! $request->has('duration_days')) {
            $request->merge(['duration_days' => max(1, (int) round($request->input('expires_in_hours') / 24))]);
        }
        if (! $request->has('duration_days')) {
            $request->merge(['duration_days' => 1]);
        }

        $validated = $request->validate([
            'parameter' => ['required', 'string', 'max:80'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:90'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $baseline = $this->enterpriseGovernanceService->grantDriftException(
            $validated['parameter'],
            $validated['reason'],
            $validated['duration_days'],
            $admin
        );

        return response()->json([
            'success' => true,
            'message' => "কনফিগারেশন প্যারামিটার '{$validated['parameter']}'-এর জন্য সাময়িক ব্যতিক্রম অনুমোদিত হয়েছে।",
            'data' => $baseline,
        ]);
    }

    /**
     * 102. Phase 6 — Enterprise SLOs & Error Budgets Report.
     */
    public function sloAndErrorBudgets(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->enterpriseGovernanceService->getSloAndErrorBudgetReport(),
        ]);
    }

    /**
     * 103. Phase 6 — Update SLO Target & Thresholds (SUPER_ADMIN only).
     */
    public function updateSloDefinition(Request $request, string $code): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || ! $this->accessControl->isSuperAdmin($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'SLO লক্ষ্যমাত্রা পরিবর্তনের জন্য SUPER_ADMIN অনুমতি আবশ্যক।',
            ], 403);
        }

        $validated = $request->validate([
            'target_value' => ['required', 'numeric'],
            'alert_threshold' => ['nullable', 'numeric'],
            'window_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'error_budget_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
        ]);

        $slo = $this->enterpriseGovernanceService->updateSloDefinition($code, $validated, $admin);

        return response()->json([
            'success' => true,
            'message' => "SLO ({$slo->slo_code}) সফলভাবে হালনাগাদ করা হয়েছে।",
            'data' => $slo,
        ]);
    }

    /**
     * 104. Phase 6 — Release Gate Verification & Supply-Chain Summary.
     */
    public function releaseGatesAndSbom(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $releaseVersion = (string) $request->input('release_version', config('app.version', 'v6.0.0-phase6'));

        return response()->json([
            'success' => true,
            'data' => $this->enterpriseGovernanceService->evaluateReleaseGates($admin, $releaseVersion),
        ]);
    }

    /**
     * 105. Phase 6 — Generate & Download CycloneDX 1.5 Software Bill of Materials (SBOM).
     */
    public function downloadSbom(): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (! $admin || (! $this->accessControl->isSuperAdmin($admin) && ! $this->accessControl->hasPermission($admin, 'security.view'))) {
            return response()->json([
                'success' => false,
                'message' => 'SBOM ডাউনলোডের অনুমতি নেই।',
            ], 403);
        }

        $sbom = $this->enterpriseGovernanceService->generateSbom();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'admin_sbom_exported',
            'entity_type' => User::class,
            'entity_id' => $admin->id,
            'new_values' => [
                'format' => $sbom['bomFormat'],
                'spec_version' => $sbom['specVersion'],
                'components_count' => $sbom['components_count'],
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $sbom,
        ]);
    }
}
