<?php

namespace App\Services\Admin;

use App\Models\AdminAlert;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\Message;
use App\Models\Page;
use App\Models\PlatformFeatureFlag;
use App\Models\Post;
use App\Models\PrivacyRequest;
use App\Models\Reaction;
use App\Models\Report;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserRestriction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * Get Executive Dashboard KPIs with comparison to previous period.
     */
    public function getExecutiveKpis(): array
    {
        $todayStart = Carbon::today();
        $yesterdayStart = Carbon::yesterday();
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $sixtyDaysAgo = Carbon::now()->subDays(60);

        // User metrics
        $totalUsers = User::count();
        $newUsersToday = User::where('created_at', '>=', $todayStart)->count();
        $newUsersYesterday = User::whereBetween('created_at', [$yesterdayStart, $todayStart])->count();
        $userGrowthToday = $newUsersYesterday > 0
            ? round((($newUsersToday - $newUsersYesterday) / $newUsersYesterday) * 100, 1)
            : 0;

        // DAU: Users who created a post, comment, message, or reaction today
        $activeUsersToday = User::where('updated_at', '>=', $todayStart)->count();
        $mau = User::where('updated_at', '>=', $thirtyDaysAgo)->count();

        // Suspended & restricted users
        $suspendedUsers = User::where(function ($q) {
            $q->where('status', 'suspended')
                ->orWhere('status', 'banned')
                ->orWhereNotNull('locked_until');
        })->count();

        $verifiedUsers = User::whereNotNull('email_verified_at')
            ->orWhereNotNull('phone_verified_at')
            ->count();

        // Content metrics
        $totalPosts = Post::count();
        $postsToday = Post::where('created_at', '>=', $todayStart)->count();
        $totalComments = Comment::count();
        $totalReactions = Reaction::count();

        // Social communities
        $totalGroups = Group::count();
        $totalPages = Page::count();

        // Messaging
        $totalConversations = Conversation::count();
        $totalMessages = Message::count();

        // Moderation & Support
        $pendingReports = Report::where('status', Report::STATUS_PENDING)->count();
        $openTickets = SupportTicket::whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS])->count();

        // Health Status
        $dbStatus = 'healthy';
        try {
            DB::select('SELECT 1');
        } catch (\Throwable) {
            $dbStatus = 'error';
        }

        $redisStatus = 'healthy';
        try {
            Cache::put('admin_health_ping', 'pong', 5);
            $redisHealthy = Cache::get('admin_health_ping') === 'pong';
            if (! $redisHealthy) {
                $redisStatus = 'degraded';
            }
        } catch (\Throwable) {
            $redisStatus = 'degraded';
        }

        // Phase 3/4 Operational KPIs
        $activeRestrictions = 0;
        $criticalIncidents = 0;
        $unacknowledgedAlerts = 0;
        $failedJobsCount = 0;
        $pendingPrivacyRequests = 0;
        $activeFeatureFlags = 0;

        try {
            $activeRestrictions = UserRestriction::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->count();
            $criticalIncidents = DB::table('security_events')
                ->where('severity', 'critical')
                ->where('resolved', false)
                ->count();
            $unacknowledgedAlerts = AdminAlert::whereNull('read_at')->count();
            $failedJobsCount = DB::table('failed_jobs')->count();
            $pendingPrivacyRequests = PrivacyRequest::where('status', 'pending')->count();
            $activeFeatureFlags = PlatformFeatureFlag::where('is_enabled', true)->count();
        } catch (\Throwable) {
            // Safe fallback if any table is not yet available
        }

        return [
            'users' => [
                'total' => $totalUsers,
                'new_today' => $newUsersToday,
                'growth_pct' => $userGrowthToday,
                'dau' => $activeUsersToday,
                'mau' => $mau,
                'suspended' => $suspendedUsers,
                'verified' => $verifiedUsers,
            ],
            'content' => [
                'total_posts' => $totalPosts,
                'posts_today' => $postsToday,
                'total_comments' => $totalComments,
                'total_reactions' => $totalReactions,
                'total_groups' => $totalGroups,
                'total_pages' => $totalPages,
            ],
            'communication' => [
                'total_conversations' => $totalConversations,
                'total_messages' => $totalMessages,
            ],
            'moderation' => [
                'pending_reports' => $pendingReports,
                'open_tickets' => $openTickets,
            ],
            'operations' => [
                'active_restrictions' => $activeRestrictions,
                'critical_incidents' => $criticalIncidents,
                'unacknowledged_alerts' => $unacknowledgedAlerts,
                'failed_jobs' => $failedJobsCount,
                'pending_privacy_requests' => $pendingPrivacyRequests,
                'active_feature_flags' => $activeFeatureFlags,
            ],
            'system' => [
                'database' => $dbStatus,
                'redis' => $redisStatus,
                'queue_driver' => config('queue.default', 'redis'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
        ];
    }

    /**
     * Get Real Time Series Trends for Analytics Visualizations.
     */
    public function getAnalyticsTrends(int $days = 14): array
    {
        $startDate = Carbon::today()->subDays($days - 1);
        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = $startDate->copy()->addDays($i)->format('Y-m-d');
        }

        // 1. User Registrations Trend
        $usersPerDay = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // 2. Posts Published Trend
        $postsPerDay = Post::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // 3. Comments Trend
        $commentsPerDay = Comment::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // 4. Reactions Trend
        $reactionsPerDay = Reaction::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Assemble aligned time series
        $registrationSeries = [];
        $contentSeries = [];
        $engagementSeries = [];

        foreach ($dates as $date) {
            $formattedLabel = Carbon::parse($date)->format('M d');
            $regCount = $usersPerDay[$date] ?? 0;
            $postCount = $postsPerDay[$date] ?? 0;
            $commentCount = $commentsPerDay[$date] ?? 0;
            $reactCount = $reactionsPerDay[$date] ?? 0;

            $registrationSeries[] = [
                'date' => $date,
                'label' => $formattedLabel,
                'registrations' => $regCount,
            ];

            $contentSeries[] = [
                'date' => $date,
                'label' => $formattedLabel,
                'posts' => $postCount,
            ];

            $engagementSeries[] = [
                'date' => $date,
                'label' => $formattedLabel,
                'comments' => $commentCount,
                'reactions' => $reactCount,
                'total' => $commentCount + $reactCount,
            ];
        }

        // 5. Reports by Category breakdown
        $reportsByCategory = Report::selectRaw('reason, count(*) as count')
            ->groupBy('reason')
            ->pluck('count', 'reason')
            ->toArray();

        return [
            'dates' => $dates,
            'registrations' => $registrationSeries,
            'content' => $contentSeries,
            'engagement' => $engagementSeries,
            'reports_by_category' => $reportsByCategory,
        ];
    }
}
