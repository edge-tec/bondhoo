<?php

namespace App\Services;

use App\Jobs\RecordProfileViewJob;
use App\Models\ProfileView;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProfileAnalyticsService
{
    /**
     * Anti-inflation cooldown window in seconds (30 minutes).
     */
    public const COOLDOWN_SECONDS = 1800;

    /**
     * Record a profile view with anti-inflation cooldown and asynchronous job dispatch.
     */
    public function recordView(
        User $owner,
        ?User $viewer = null,
        ?Request $request = null,
        bool $isAnonymous = false
    ): bool {
        // 1. Self-views are never recorded
        if ($viewer && $viewer->id === $owner->id) {
            return false;
        }

        $request = $request ?? request();
        $ip = (string) $request->ip();
        $userAgent = (string) $request->userAgent();
        $referer = (string) $request->header('referer', '');

        // 2. Compute secure IP HMAC hash (irreversible, protects user privacy)
        $secret = config('app.key') ?: 'jugajug_secure_hmac_secret';
        $ipHash = ! empty($ip) ? hash_hmac('sha256', $ip, $secret) : null;

        // 3. Determine unique cooldown key to prevent artificial view inflation
        $viewerKey = $viewer ? "user_{$viewer->id}" : ($ipHash ? "anon_{$ipHash}" : 'anon_'.md5($userAgent));
        $cooldownKey = "profile_view_cooldown:{$owner->id}:{$viewerKey}";

        if (Cache::has($cooldownKey)) {
            // Already viewed within cooldown window -> prevent inflation
            return false;
        }

        // Set cooldown lock
        Cache::put($cooldownKey, true, self::COOLDOWN_SECONDS);

        // 4. Determine anonymous status based on viewer privacy settings
        if ($viewer && ! $isAnonymous) {
            $privacy = $viewer->privacySettings;
            if ($privacy && $privacy->profile_view_visibility === 'only_me') {
                $isAnonymous = true;
            }
        } elseif (! $viewer) {
            $isAnonymous = true;
        }

        // 5. Parse device category
        $deviceType = $this->parseDeviceCategory($request);

        // 6. Parse discovery source
        $source = $this->parseDiscoverySource($request);

        // Mask IP for storage (only /24 prefix or null)
        $maskedIp = $this->maskIp($ip);

        // 7. Dispatch asynchronous background job for database persistence & Redis aggregation
        RecordProfileViewJob::dispatch(
            ownerId: $owner->id,
            viewerId: $viewer?->id,
            ipHash: $ipHash,
            ipAddress: $maskedIp ?: $ip,
            userAgent: $userAgent,
            deviceType: $deviceType,
            source: $source,
            referer: $referer,
            isAnonymous: $isAnonymous,
            viewedAt: now()
        );

        return true;
    }

    /**
     * Get aggregated profile analytics for owner dashboard.
     *
     * @return array{total_views: int, views_today: int, views_this_week: int, unique_viewers_count: int, views_trend: array, device_breakdown: array, discovery_sources: array, recent_viewers: array}
     */
    public function getDashboardAnalytics(User $owner, string $timeframe = '30d'): array
    {
        $cacheKey = "profile_analytics_{$owner->id}_{$timeframe}";

        return Cache::remember($cacheKey, 300, function () use ($owner, $timeframe) {
            return $this->computeAnalytics($owner, $timeframe);
        });
    }

    /**
     * Compute analytics metrics directly from database.
     */
    public function computeAnalytics(User $owner, string $timeframe = '30d'): array
    {
        $days = match ($timeframe) {
            '7d' => 7,
            '14d' => 14,
            default => 30,
        };

        $sinceDate = Carbon::now()->subDays($days)->startOfDay();
        $todayStart = Carbon::today()->startOfDay();
        $weekStart = Carbon::now()->startOfWeek();

        // 1. Core counters
        $totalViews = ProfileView::where('user_id', $owner->id)->count();
        $viewsToday = ProfileView::where('user_id', $owner->id)
            ->where('viewed_at', '>=', $todayStart)
            ->count();
        $viewsThisWeek = ProfileView::where('user_id', $owner->id)
            ->where('viewed_at', '>=', $weekStart)
            ->count();

        // 2. Unique viewers count (unique logged-in viewers + unique anon ip_hashes)
        $uniqueLoggedIn = ProfileView::where('user_id', $owner->id)
            ->whereNotNull('viewer_id')
            ->distinct('viewer_id')
            ->count('viewer_id');

        $uniqueAnonymous = ProfileView::where('user_id', $owner->id)
            ->whereNull('viewer_id')
            ->whereNotNull('ip_hash')
            ->distinct('ip_hash')
            ->count('ip_hash');

        $uniqueViewersCount = $uniqueLoggedIn + $uniqueAnonymous;

        // 3. Daily trend for the timeframe
        $dailyRecords = ProfileView::where('user_id', $owner->id)
            ->where('viewed_at', '>=', $sinceDate)
            ->selectRaw('DATE(viewed_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $trend[] = [
                'date' => $date,
                'views' => (int) ($dailyRecords[$date] ?? 0),
            ];
        }

        // 4. Device category breakdown
        $devices = ProfileView::where('user_id', $owner->id)
            ->where('viewed_at', '>=', $sinceDate)
            ->select('device_type', DB::raw('count(*) as count'))
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        $deviceBreakdown = [
            'desktop' => (int) ($devices['desktop'] ?? 0),
            'mobile' => (int) ($devices['mobile'] ?? 0),
            'tablet' => (int) ($devices['tablet'] ?? 0),
        ];

        // 5. Discovery sources breakdown
        $sources = ProfileView::where('user_id', $owner->id)
            ->where('viewed_at', '>=', $sinceDate)
            ->select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        $discoverySources = [
            'feed' => (int) ($sources['feed'] ?? 0),
            'search' => (int) ($sources['search'] ?? 0),
            'direct' => (int) ($sources['direct'] ?? 0),
            'external' => (int) ($sources['external'] ?? 0),
        ];

        // 6. Recent viewers list (with privacy masking)
        $recentViews = ProfileView::with(['viewer.profile', 'viewer.privacySettings'])
            ->where('user_id', $owner->id)
            ->latest('viewed_at')
            ->limit(15)
            ->get();

        $recentViewers = $recentViews->map(function (ProfileView $view) {
            $viewer = $view->viewer;

            $isPrivate = $view->is_anonymous || ! $viewer;
            if ($viewer && $viewer->privacySettings?->profile_view_visibility === 'only_me') {
                $isPrivate = true;
            }

            if ($isPrivate) {
                return [
                    'id' => null,
                    'is_anonymous' => true,
                    'name' => 'Bondhoo Member',
                    'username' => null,
                    'avatar_url' => null,
                    'device_type' => $view->device_type,
                    'source' => $view->source,
                    'viewed_at' => $view->viewed_at?->toIso8601String(),
                ];
            }

            return [
                'id' => $viewer->id,
                'is_anonymous' => false,
                'name' => $viewer->name ?: $viewer->username,
                'username' => $viewer->username,
                'avatar_url' => $viewer->profile?->avatar_url,
                'device_type' => $view->device_type,
                'source' => $view->source,
                'viewed_at' => $view->viewed_at?->toIso8601String(),
            ];
        })->toArray();

        return [
            'total_views' => $totalViews,
            'views_today' => $viewsToday,
            'views_this_week' => $viewsThisWeek,
            'unique_viewers_count' => $uniqueViewersCount,
            'timeframe' => "{$days}d",
            'views_trend' => $trend,
            'device_breakdown' => $deviceBreakdown,
            'discovery_sources' => $discoverySources,
            'recent_viewers' => $recentViewers,
        ];
    }

    /**
     * Categorize device into desktop, mobile, or tablet.
     */
    public function parseDeviceCategory(Request $request): string
    {
        if ($request->header('Sec-CH-UA-Mobile') === '?1') {
            return 'mobile';
        }

        $ua = strtolower((string) $request->userAgent());

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/(mobi|iphone|ipod|android|blackberry|opera mini|windows phone)/i', $ua)) {
            return 'mobile';
        }

        if (preg_match('/(bot|crawler|spider|slurp|curl|wget)/i', $ua)) {
            return 'bot';
        }

        return 'desktop';
    }

    /**
     * Categorize discovery source into feed, search, direct, or external.
     */
    public function parseDiscoverySource(Request $request): string
    {
        $explicit = $request->input('source');
        if (! empty($explicit) && in_array($explicit, ['feed', 'search', 'direct', 'external'], true)) {
            return $explicit;
        }

        $referer = strtolower((string) $request->header('referer', ''));
        if (empty($referer)) {
            return 'direct';
        }

        $appUrl = strtolower(config('app.url', 'jugajug.com'));
        $appHost = parse_url($appUrl, PHP_URL_HOST) ?: 'jugajug';

        if (str_contains($referer, 'feed') || str_contains($referer, 'timeline')) {
            return 'feed';
        }

        if (str_contains($referer, 'search') || str_contains($referer, 'query')) {
            return 'search';
        }

        if (! str_contains($referer, $appHost) && ! str_contains($referer, 'localhost') && ! str_contains($referer, '127.0.0.1')) {
            return 'external';
        }

        return 'direct';
    }

    /**
     * Mask IP address for privacy protection (e.g. 192.168.1.0).
     */
    protected function maskIp(string $ip): ?string
    {
        if (empty($ip)) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return "{$parts[0]}.{$parts[1]}.{$parts[2]}.0";
            }
        }

        return null;
    }
}
