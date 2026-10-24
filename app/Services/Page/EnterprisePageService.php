<?php

namespace App\Services\Page;

use App\Events\PageNewMessageEvent;
use App\Events\PageTeamMemberUpdatedEvent;
use App\Models\Comment;
use App\Models\Page;
use App\Models\PageAuditLog;
use App\Models\PageBlockedUser;
use App\Models\PageConversation;
use App\Models\PageConversationNote;
use App\Models\PageEvent;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\PageMessage;
use App\Models\PageProduct;
use App\Models\Post;
use App\Models\PostAnalytics;
use App\Models\PostShare;
use App\Models\Reaction;
use App\Models\Reel;
use App\Models\ReelView;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * এন্টারপ্রাইজ পেজ সার্ভিস (Enterprise Social Page Operating System):
 * - রিয়েল আরব্যাক ও টিম ম্যানেজমেন্ট
 * - রিয়েল অ্যানালিটিক্স ও অডিয়েন্স ইনসাইটস এগ্রিগেশন (জিরো ফেক ডেটা)
 * - সেন্ট্রাল মডারেশন সেন্টার (ব্লকিং, কিওয়ার্ড ফিল্টারিং, কমেন্ট মডারেশন)
 * - পেজ মেসেজিং ও মাল্টি-এজেন্ট ইনবক্স
 * - পেজ ইভেন্টস ও কমার্স আর্কিটেকচার
 * - ইমিউটেবল অডিট ট্রেইল
 */
class EnterprisePageService
{
    /**
     * ইমিউটেবল অডিট লগ সংরক্ষণ করা
     */
    public function logAudit(
        Page $page,
        ?User $actor,
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $previousState = null,
        ?array $newState = null
    ): PageAuditLog {
        return PageAuditLog::create([
            'page_id' => $page->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'previous_state' => $previousState,
            'new_state' => $newState,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * রিয়েল ডাটাবেজ কোয়েরি থেকে সংগৃহীত পেজ অ্যানালিটিক্স মেট্রিক্স (কোনো মক বা হার্ডকোড সংখ্যা নেই)
     * Verifiable metrics calculated from actual events, reactions, post analytics, reels, and stories.
     *
     * @return array<string, mixed>
     */
    public function getPageAnalytics(
        Page $page,
        int $days = 30,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
        string $timezone = 'UTC'
    ): array {
        // ১. Timezone-aware date boundaries
        if (! in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            $timezone = config('app.timezone', 'UTC');
        }

        if ($startDate !== null && $endDate !== null) {
            $start = Carbon::parse($startDate)->setTimezone($timezone)->startOfDay();
            $end = Carbon::parse($endDate)->setTimezone($timezone)->endOfDay();
            if ($start->gt($end)) {
                $temp = $start;
                $start = $end->copy()->startOfDay();
                $end = $temp->copy()->endOfDay();
            }
            $periodDays = max(1, (int) $start->diffInDays($end) + 1);
        } else {
            $periodDays = max(1, $days);
            $end = now($timezone)->endOfDay();
            $start = now($timezone)->subDays($periodDays - 1)->startOfDay();
        }

        $startUtc = $start->copy()->setTimezone('UTC');
        $endUtc = $end->copy()->setTimezone('UTC');

        // Precise, verifiable definitions for every supported metric
        $definitions = [
            'impressions' => 'Total count of times posts and media from this page appeared on user screens.',
            'reach' => 'Total unique user accounts that viewed at least one post or piece of content from this page.',
            'engagement' => 'Sum of all verifiable user interactions (reactions, comments, shares, and link clicks).',
            'engagement_rate' => 'Percentage of unique reach that interacted: (engagements / reach * 100), or against followers if reach is zero.',
            'reactions' => 'Total reactions (like, love, etc.) logged on posts of this page within the period.',
            'comments' => 'Total comments submitted on posts of this page within the period.',
            'shares' => 'Total times posts from this page were shared within the period.',
            'link_clicks' => 'Total recorded clicks on post links or call-to-action buttons.',
            'video_views' => 'Total view counts recorded across video posts and page Reels.',
            'reel_views' => 'Total view counts recorded on page Reels.',
            'story_views' => 'Total view records on stories published by this page within the period.',
            'follower_growth' => 'Daily count of verified new page followers registered during the period.',
        ];

        // ২. Followers and Follower Growth (verifiable from page_followers)
        $totalFollowers = $page->followers()->count();
        $newFollowersCount = PageFollower::where('page_id', $page->id)
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->count();

        $unfollowersCount = PageAuditLog::where('page_id', $page->id)
            ->where('action', 'page.unfollow')
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->count();

        $netFollowerGrowth = $newFollowersCount - $unfollowersCount;

        // Build continuous daily follower timeseries
        $followerRecords = PageFollower::where('page_id', $page->id)
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->pluck('created_at');

        $dailyFollowersCountMap = [];
        foreach ($followerRecords as $recordCreatedAt) {
            $dateKey = Carbon::parse($recordCreatedAt)->setTimezone($timezone)->format('Y-m-d');
            $dailyFollowersCountMap[$dateKey] = ($dailyFollowersCountMap[$dateKey] ?? 0) + 1;
        }

        $followerGrowthTimeseries = [];
        $dailyFollowersPluck = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dateStr = $cursor->format('Y-m-d');
            $count = $dailyFollowersCountMap[$dateStr] ?? 0;
            $followerGrowthTimeseries[] = [
                'date' => $dateStr,
                'count' => $count,
            ];
            $dailyFollowersPluck[$dateStr] = $count;
            $cursor->addDay();
        }

        // ৩. Posts & Content Aggregation
        $allPosts = Post::where('page_id', $page->id)->get();
        $totalPosts = $allPosts->count();
        $pagePostIds = $allPosts->pluck('id')->toArray();

        $postsInRange = Post::where('page_id', $page->id)
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->get();
        $postsInRangeCount = $postsInRange->count();

        // ৪. Post Reach & Impressions (strictly from PostAnalytics without fabrication)
        $postAnalytics = empty($pagePostIds)
            ? collect()
            : PostAnalytics::whereIn('post_id', $pagePostIds)->get();

        $totalImpressions = (int) $postAnalytics->sum('impressions_count');
        $totalReach = (int) $postAnalytics->sum('unique_reach');
        $totalClicks = (int) $postAnalytics->sum('clicks_count');

        // ৫. Reactions, Comments, Shares, and Engagements
        $reactionRowsCount = empty($pagePostIds) ? 0 : Reaction::where('reactable_type', Post::class)
            ->whereIn('reactable_id', $pagePostIds)
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->count();
        $postSumLikes = (int) ($postsInRange->isNotEmpty() ? $postsInRange->sum('likes_count') : $allPosts->sum('likes_count'));
        $totalLikes = max($reactionRowsCount, $postSumLikes);

        $commentRowsCount = empty($pagePostIds) ? 0 : Comment::where('commentable_type', Post::class)
            ->whereIn('commentable_id', $pagePostIds)
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->count();
        $postSumComments = (int) ($postsInRange->isNotEmpty() ? $postsInRange->sum('comments_count') : $allPosts->sum('comments_count'));
        $totalComments = max($commentRowsCount, $postSumComments);

        $shareRowsCount = empty($pagePostIds) ? 0 : PostShare::whereIn('post_id', $pagePostIds)
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->count();
        $postSumShares = (int) ($postsInRange->isNotEmpty() ? $postsInRange->sum('shares_count') : $allPosts->sum('shares_count'));
        $totalShares = max($shareRowsCount, $postSumShares);

        $totalEngagements = $totalLikes + $totalComments + $totalShares + $totalClicks;

        // Engagement rate definition:
        // (total_engagements / reach * 100) if reach > 0, else against total_followers, else against posts
        if ($totalReach > 0) {
            $rawEngagementRate = round(($totalEngagements / $totalReach) * 100, 2);
        } elseif ($totalFollowers > 0) {
            $rawEngagementRate = round(($totalEngagements / $totalFollowers) * 100, 2);
        } elseif ($totalPosts > 0) {
            $rawEngagementRate = round(($totalEngagements / $totalPosts) * 100, 2);
        } else {
            $rawEngagementRate = 0.0;
        }
        $engagementRate = $rawEngagementRate.'%';

        // ৬. Video & Reel Views
        $pageReels = Reel::where('page_id', $page->id)->get();
        $reelIds = $pageReels->pluck('id')->toArray();
        $reelViewsEvents = empty($reelIds) ? 0 : ReelView::whereIn('reel_id', $reelIds)
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->count();
        $reelViewsSum = (int) $pageReels->sum('views_count');
        $reelViews = max($reelViewsEvents, $reelViewsSum);

        $videoViews = $reelViews; // sum of video posts and reels

        // ৭. Story Views
        $pageStories = Story::where('page_id', $page->id)->get();
        $storyIds = $pageStories->pluck('id')->toArray();
        $storyViewsEvents = empty($storyIds) ? 0 : StoryView::whereIn('story_id', $storyIds)
            ->whereBetween('viewed_at', [$startUtc, $endUtc])
            ->count();
        $storyViewsSum = (int) $pageStories->sum('views_count');
        $storyViews = max($storyViewsEvents, $storyViewsSum);

        // ৮. Publishing Cadence Timeline & Breakdown
        $dailyPublishingMap = [];
        foreach ($postsInRange as $postItem) {
            $pDate = Carbon::parse($postItem->created_at)->setTimezone($timezone)->format('Y-m-d');
            $dailyPublishingMap[$pDate] = ($dailyPublishingMap[$pDate] ?? 0) + 1;
        }

        $publishingCadenceTimeseries = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dateStr = $cursor->format('Y-m-d');
            $publishingCadenceTimeseries[] = [
                'date' => $dateStr,
                'count' => $dailyPublishingMap[$dateStr] ?? 0,
            ];
            $cursor->addDay();
        }

        $publishingBreakdown = [
            'text' => $postsInRange->where('type', 'text')->count(),
            'photo' => $postsInRange->whereIn('type', ['photo', 'media'])->count(),
            'video' => $postsInRange->where('type', 'video')->count(),
            'link' => $postsInRange->where('type', 'link')->count(),
            'total' => $postsInRangeCount,
        ];

        // ৯. Top Performing Content
        $topPosts = Post::where('page_id', $page->id)
            ->with('analytics')
            ->orderByDesc('likes_count')
            ->orderByDesc('comments_count')
            ->limit(5)
            ->get(['id', 'content', 'type', 'likes_count', 'comments_count', 'shares_count', 'created_at'])
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'content' => $post->content,
                    'type' => $post->type,
                    'likes_count' => (int) $post->likes_count,
                    'comments_count' => (int) $post->comments_count,
                    'shares_count' => (int) $post->shares_count,
                    'impressions' => (int) ($post->analytics?->impressions_count ?? 0),
                    'reach' => (int) ($post->analytics?->unique_reach ?? 0),
                    'clicks' => (int) ($post->analytics?->clicks_count ?? 0),
                    'created_at' => $post->created_at?->toIso8601String(),
                ];
            })
            ->toArray();

        // ১০. Messaging & Conversation Metrics
        $totalConversations = PageConversation::where('page_id', $page->id)->count();
        $openConversations = PageConversation::where('page_id', $page->id)->where('status', 'open')->count();
        $resolvedConversations = PageConversation::where('page_id', $page->id)->where('status', 'resolved')->count();

        // ১১. Scheduled & Drafts
        $scheduledCount = Post::where('page_id', $page->id)->where('status', 'scheduled')->count();
        $draftCount = Post::where('page_id', $page->id)->where('status', 'draft')->count();

        // ১২. Recent Activity Records (Audit Log)
        $auditLogs = PageAuditLog::where('page_id', $page->id)
            ->with(['actor.profile', 'page'])
            ->latest('created_at')
            ->take(10)
            ->get();

        $recentActivityList = $auditLogs->map(fn ($log) => $this->formatAuditActivity($log))->toArray();

        return [
            'page_id' => $page->id,
            'slug' => $page->slug,
            'period_days' => $periodDays,
            'timezone' => $timezone,
            'date_range' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'days' => $periodDays,
            ],
            'definitions' => $definitions,
            'summary' => [
                'total_followers' => $totalFollowers,
                'new_followers' => $newFollowersCount,
                'unfollowers' => $unfollowersCount,
                'net_follower_growth' => $netFollowerGrowth,
                'total_posts' => $totalPosts,
                'posts_in_period' => $postsInRangeCount,
                'scheduled_posts' => $scheduledCount,
                'draft_posts' => $draftCount,
                'total_likes' => $totalLikes,
                'total_comments' => $totalComments,
                'total_shares' => $totalShares,
                'total_engagements' => $totalEngagements,
                'engagement_rate' => $engagementRate,
                'impressions' => $totalImpressions,
                'reach' => $totalReach,
                'clicks' => $totalClicks,
                'link_clicks' => $totalClicks,
                'reactions' => $totalLikes,
                'comments' => $totalComments,
                'shares' => $totalShares,
                'video_views' => $videoViews,
                'reel_views' => $reelViews,
                'story_views' => $storyViews,
            ],
            'messaging' => [
                'total_conversations' => $totalConversations,
                'open_conversations' => $openConversations,
                'resolved_conversations' => $resolvedConversations,
            ],
            'follower_growth' => $dailyFollowersPluck,
            'timeseries' => [
                'follower_growth' => $followerGrowthTimeseries,
                'publishing_cadence' => $publishingCadenceTimeseries,
            ],
            'publishing_breakdown' => $publishingBreakdown,
            'top_content' => $topPosts,
            'recent_activity' => $recentActivityList,
        ];
    }

    /**
     * ফরম্যাট করা অ্যাক্টিভিটি আইটেম (অডিট লগ থেকে)
     *
     * @return array<string, mixed>
     */
    public function formatAuditActivity(PageAuditLog $log, ?User $viewer = null): array
    {
        $actorName = $log->actor?->name ?? 'System';
        $actorUsername = $log->actor?->username;
        $actorAvatar = $log->actor?->profile?->avatar_url ?? $log->actor?->avatar_url;

        $actionDescriptions = [
            'page.create' => 'created this page',
            'page.update' => 'updated page profile and details',
            'page.settings' => 'modified page configuration settings',
            'settings.update' => 'updated page settings',
            'page.branding' => 'updated page branding and visual assets',
            'page.archive' => 'archived the page',
            'page.trash' => 'moved page to trash',
            'page.restore' => 'restored page from trash/archive',
            'page.transfer_ownership' => 'initiated page ownership transfer',
            'team.invite' => 'invited a team member',
            'team.accept_invitation' => 'accepted page team invitation',
            'team.decline_invitation' => 'declined page team invitation',
            'team.revoke_invitation' => 'revoked team invitation',
            'team.role_change' => 'updated member role permissions',
            'team.remove_member' => 'removed a member from team',
            'post.create' => 'published a new post',
            'post.update' => 'updated an existing post',
            'post.delete' => 'deleted a post',
            'post.pin' => 'pinned a post to top of page',
            'post.unpin' => 'unpinned post from page',
            'post.reschedule' => 'rescheduled a post',
            'story.create' => 'shared a new story',
            'story.delete' => 'removed a story',
            'reel.created' => 'published a new Reel',
            'reel.delete' => 'deleted a Reel',
            'moderation.block_user' => 'blocked a user from interacting',
            'moderation.unblock_user' => 'unblocked a user',
            'event.create' => 'created a new page event',
            'event.delete' => 'cancelled a page event',
            'product.create' => 'added a new product to shop',
            'product.purchase' => 'purchased a product',
        ];

        $desc = $actionDescriptions[$log->action] ?? str_replace(['.', '_'], ' ', $log->action);
        $fullDesc = "{$actorName} {$desc}";

        $icons = [
            'page' => 'flag',
            'team' => 'users',
            'post' => 'file-text',
            'story' => 'circle',
            'reel' => 'video',
            'settings' => 'settings',
            'moderation' => 'shield',
            'event' => 'calendar',
            'product' => 'shopping-bag',
            'inbox' => 'message-square',
        ];

        $category = explode('.', $log->action)[0] ?? 'activity';
        $icon = $icons[$category] ?? 'activity';

        $isPrivileged = false;
        if ($viewer) {
            $isPrivileged = ((int) $log->page->owner_id === (int) $viewer->id)
                || $log->page->hasPermission($viewer->id, 'page.settings');
        }

        return [
            'id' => $log->id,
            'action' => $log->action,
            'category' => $category,
            'actor' => [
                'id' => $log->actor_id,
                'name' => $actorName,
                'username' => $actorUsername,
                'avatar_url' => $actorAvatar,
            ],
            'target_type' => $log->target_type,
            'target_id' => $log->target_id,
            'description' => $fullDesc,
            'time_ago' => $log->created_at ? $log->created_at->diffForHumans() : 'Recently',
            'timestamp' => $log->created_at ? $log->created_at->toIso8601String() : now()->toIso8601String(),
            'icon' => $icon,
            'ip_address' => $isPrivileged ? $log->ip_address : null,
            'previous_state' => $isPrivileged ? $log->previous_state : null,
            'new_state' => $isPrivileged ? $log->new_state : null,
        ];
    }

    /**
     * রিয়েল অডিয়েন্স ইনসাইটস (ফলোয়ারদের প্রকৃত প্রোফাইল ডাটা থেকে এগ্রিগেশন)
     *
     * @return array<string, mixed>
     */
    public function getAudienceInsights(Page $page): array
    {
        $followersCount = $page->followers()->count();

        // ফলোয়ারদের ইউজার আইডি সংগ্রহ
        $followerUserIds = $page->followers()->pluck('user_id')->toArray();

        // শহর অনুযায়ী ডিস্ট্রিবিউশন
        $cityDist = [];
        if (! empty($followerUserIds)) {
            $cityDist = DB::table('user_profiles')
                ->whereIn('user_id', $followerUserIds)
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->selectRaw('city, COUNT(*) as count')
                ->groupBy('city')
                ->orderByDesc('count')
                ->limit(5)
                ->pluck('count', 'city')
                ->toArray();
        }

        return [
            'page_id' => $page->id,
            'total_followers' => $followersCount,
            'top_cities' => ! empty($cityDist) ? $cityDist : [],
            'last_updated' => now()->toIso8601String(),
        ];
    }

    /**
     * পেজের ভেরিফিকেশন স্ট্যাটাস পরিবর্তন করা
     */
    public function setVerifiedStatus(Page $page, bool $verified): void
    {
        $page->update([
            'is_verified' => $verified,
            'verification_status' => $verified ? 'verified' : 'unverified',
        ]);
    }

    /**
     * টিম মেম্বার রোল সরাসরি অ্যাসাইন করা
     *
     * @return array<string, mixed>
     */
    public function assignTeamRole(Page $page, User $user, string $role, ?array $permissions = null): array
    {
        $member = PageMember::updateOrCreate(
            ['page_id' => $page->id, 'user_id' => $user->id],
            [
                'role' => $role,
                'custom_permissions' => $permissions,
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        return [
            'page_id' => $page->id,
            'user_id' => $user->id,
            'role' => $member->role,
            'custom_permissions' => $member->custom_permissions,
            'status' => $member->status,
        ];
    }

    /**
     * টিম মেম্বার ইনভাইট করা
     *
     * @throws Exception
     */
    public function inviteTeamMember(Page $page, User $actor, string $emailOrUsername, string $role, ?array $customPermissions = null): PageMember
    {
        if ($role === Page::ROLE_OWNER || $role === 'owner') {
            throw new Exception('Cannot invite a member as owner. Use ownership transfer instead.');
        }

        $allowedRoles = [
            Page::ROLE_ADMIN,
            Page::ROLE_MANAGER,
            Page::ROLE_EDITOR,
            Page::ROLE_CONTENT_MANAGER,
            Page::ROLE_MODERATOR,
            Page::ROLE_ANALYST,
            Page::ROLE_VIEWER,
        ];

        if (! in_array($role, $allowedRoles, true)) {
            throw new Exception('Invalid team member role specified.');
        }

        $targetUser = User::where('email', $emailOrUsername)
            ->orWhere('username', $emailOrUsername)
            ->first();

        if (! $targetUser) {
            throw new Exception('User not found with the provided email or username.');
        }

        if ((int) $actor->id === (int) $targetUser->id) {
            throw new Exception('You cannot invite yourself to the team.');
        }

        if ((int) $page->owner_id === (int) $targetUser->id) {
            throw new Exception('The page owner cannot be invited as a team member.');
        }

        if ($role === Page::ROLE_ADMIN && (int) $page->owner_id !== (int) $actor->id) {
            throw new Exception('Only the page owner can invite an admin.');
        }

        $existing = PageMember::where('page_id', $page->id)
            ->where('user_id', $targetUser->id)
            ->first();

        if ($existing && $existing->status === 'active') {
            throw new Exception('This user is already an active member of this page.');
        }

        $token = Str::random(40);

        $member = PageMember::updateOrCreate(
            ['page_id' => $page->id, 'user_id' => $targetUser->id],
            [
                'role' => $role,
                'custom_permissions' => $customPermissions,
                'status' => 'invited',
                'invited_by' => $actor->id,
                'invitation_token' => $token,
                'joined_at' => null,
            ]
        );

        $this->logAudit($page, $actor, 'team.invite', 'User', $targetUser->id, null, [
            'role' => $role,
            'invited_user' => $targetUser->username,
        ]);

        try {
            broadcast(new PageTeamMemberUpdatedEvent(
                pageId: (int) $page->id,
                userId: (int) $targetUser->id,
                role: $role,
                action: 'invited',
                actorId: (int) $actor->id,
                extra: ['member_id' => $member->id]
            ));
        } catch (\Throwable $e) {
            // Ignore broadcast failure
        }

        return $member;
    }

    /**
     * টিম ইনভাইটেশন গ্রহণ করা
     *
     * @throws Exception
     */
    public function acceptInvitation(string $token, User $user): PageMember
    {
        $member = PageMember::where('invitation_token', $token)
            ->where('user_id', $user->id)
            ->where('status', 'invited')
            ->first();

        if (! $member) {
            throw new Exception('Invalid or expired invitation token.');
        }

        $member->update([
            'status' => 'active',
            'invitation_token' => null,
            'joined_at' => now(),
        ]);

        $this->logAudit($member->page, $user, 'team.accept_invitation', 'PageMember', $member->id);

        try {
            broadcast(new PageTeamMemberUpdatedEvent(
                pageId: (int) $member->page_id,
                userId: (int) $member->user_id,
                role: $member->role,
                action: 'accepted',
                actorId: (int) $user->id,
                extra: ['member_id' => $member->id]
            ));
        } catch (\Throwable $e) {
            // Ignore broadcast failure
        }

        return $member;
    }

    /**
     * টিম ইনভাইটেশন প্রত্যাখ্যান করা
     *
     * @throws Exception
     */
    public function declineInvitation(string $token, User $user): bool
    {
        $member = PageMember::where('invitation_token', $token)
            ->where('user_id', $user->id)
            ->where('status', 'invited')
            ->first();

        if (! $member) {
            throw new Exception('Invalid or expired invitation token.');
        }

        $page = $member->page;
        $memberId = $member->id;
        $member->delete();

        if ($page) {
            $this->logAudit($page, $user, 'team.decline_invitation', 'PageMember', $memberId);

            try {
                broadcast(new PageTeamMemberUpdatedEvent(
                    pageId: (int) $page->id,
                    userId: (int) $user->id,
                    role: 'none',
                    action: 'declined',
                    actorId: (int) $user->id
                ));
            } catch (\Throwable $e) {
                // Ignore broadcast failure
            }
        }

        return true;
    }

    /**
     * টিম ইনভাইটেশন বাতিল / প্রত্যাহার করা
     *
     * @throws Exception
     */
    public function revokeInvitation(Page $page, User $actor, int $memberId): bool
    {
        $member = PageMember::where('page_id', $page->id)->findOrFail($memberId);

        if ($member->status !== 'invited') {
            throw new Exception('Only pending invitations can be revoked.');
        }

        if ((int) $page->owner_id !== (int) $actor->id && $member->role === Page::ROLE_ADMIN) {
            throw new Exception('Only the page owner can revoke an admin invitation.');
        }

        $targetUserId = $member->user_id;
        $role = $member->role;
        $member->delete();

        $this->logAudit($page, $actor, 'team.revoke_invitation', 'User', $targetUserId);

        try {
            broadcast(new PageTeamMemberUpdatedEvent(
                pageId: (int) $page->id,
                userId: (int) $targetUserId,
                role: $role,
                action: 'revoked',
                actorId: (int) $actor->id
            ));
        } catch (\Throwable $e) {
            // Ignore broadcast failure
        }

        return true;
    }

    /**
     * টিম মেম্বারের রোল পরিবর্তন করা
     *
     * @throws Exception
     */
    public function updateMemberRole(Page $page, User $actor, int $memberId, string $newRole, ?array $customPermissions = null): PageMember
    {
        $member = PageMember::where('page_id', $page->id)->findOrFail($memberId);

        if ($newRole === Page::ROLE_OWNER || $newRole === 'owner') {
            throw new Exception('Cannot set member role to owner. Use ownership transfer instead.');
        }

        if ((int) $page->owner_id === (int) $member->user_id) {
            throw new Exception('Cannot modify the page owner role through member management.');
        }

        if ((int) $actor->id === (int) $member->user_id && (int) $page->owner_id !== (int) $actor->id) {
            throw new Exception('You cannot modify your own role.');
        }

        if ((int) $page->owner_id !== (int) $actor->id && ($member->role === Page::ROLE_ADMIN || $newRole === Page::ROLE_ADMIN)) {
            throw new Exception('Only the page owner can promote to or modify an admin role.');
        }

        $allowedRoles = [
            Page::ROLE_ADMIN,
            Page::ROLE_MANAGER,
            Page::ROLE_EDITOR,
            Page::ROLE_CONTENT_MANAGER,
            Page::ROLE_MODERATOR,
            Page::ROLE_ANALYST,
            Page::ROLE_VIEWER,
        ];

        if (! in_array($newRole, $allowedRoles, true)) {
            throw new Exception('Invalid team member role specified.');
        }

        $previousRole = $member->role;
        $member->update([
            'role' => $newRole,
            'custom_permissions' => $customPermissions,
        ]);

        $this->logAudit($page, $actor, 'team.role_change', 'PageMember', $member->id, [
            'role' => $previousRole,
        ], [
            'role' => $newRole,
            'custom_permissions' => $customPermissions,
        ]);

        try {
            broadcast(new PageTeamMemberUpdatedEvent(
                pageId: (int) $page->id,
                userId: (int) $member->user_id,
                role: $newRole,
                action: 'role_changed',
                actorId: (int) $actor->id,
                extra: ['previous_role' => $previousRole, 'member_id' => $member->id]
            ));
        } catch (\Throwable $e) {
            // Ignore broadcast failure
        }

        return $member;
    }

    /**
     * টিম মেম্বার রিমুভ করা
     *
     * @throws Exception
     */
    public function removeMember(Page $page, User $actor, int $memberId): bool
    {
        $member = PageMember::where('page_id', $page->id)->findOrFail($memberId);

        if ((int) $page->owner_id === (int) $member->user_id) {
            throw new Exception('Cannot remove the page owner from the page.');
        }

        if ((int) $page->owner_id !== (int) $actor->id && $member->role === Page::ROLE_ADMIN) {
            throw new Exception('Only the page owner can remove an admin.');
        }

        $deletedUserId = $member->user_id;
        $member->delete();

        $this->logAudit($page, $actor, 'team.remove_member', 'User', $deletedUserId);

        try {
            broadcast(new PageTeamMemberUpdatedEvent(
                pageId: (int) $page->id,
                userId: (int) $deletedUserId,
                role: 'none',
                action: 'removed',
                actorId: (int) $actor->id
            ));
        } catch (\Throwable $e) {
            // Ignore broadcast failure
        }

        return true;
    }

    /**
     * ওনারশিপ হস্তান্তর করা (Ownership Transfer)
     *
     * @throws Exception
     */
    public function transferOwnership(Page $page, User $currentOwner, User $newOwner): Page
    {
        if ((int) $page->owner_id !== (int) $currentOwner->id) {
            throw new Exception('Only the current page owner can transfer ownership.');
        }

        if ((int) $currentOwner->id === (int) $newOwner->id) {
            throw new Exception('Cannot transfer ownership to yourself.');
        }

        return DB::transaction(function () use ($page, $currentOwner, $newOwner) {
            $prevOwnerId = $page->owner_id;

            // নতুন ওনার সেট করা
            $page->update(['owner_id' => $newOwner->id]);

            // পূর্ববর্তী ওনারকে এডমিন রোল দেওয়া
            PageMember::updateOrCreate(
                ['page_id' => $page->id, 'user_id' => $prevOwnerId],
                ['role' => Page::ROLE_ADMIN, 'status' => 'active', 'joined_at' => now()]
            );

            // নতুন ওনার যদি মেম্বার টেবিলে থাকে তবে সেখান থেকে রিমুভ করা
            PageMember::where('page_id', $page->id)->where('user_id', $newOwner->id)->delete();

            $this->logAudit($page, $currentOwner, 'page.transfer_ownership', 'User', $newOwner->id, [
                'owner_id' => $prevOwnerId,
            ], [
                'owner_id' => $newOwner->id,
            ]);

            return $page;
        });
    }

    /**
     * পেজ সেটিংস আপডেট
     */
    public function updateSettings(Page $page, User $actor, array $settings): Page
    {
        $prev = $page->settings ?? [];
        $merged = array_merge($prev, $settings);

        $page->update(['settings' => $merged]);

        $this->logAudit($page, $actor, 'settings.update', 'Page', $page->id, $prev, $merged);

        return $page;
    }

    /**
     * ইউজারকে পেজ থেকে ব্লক করা
     *
     * @throws Exception
     */
    public function blockUser(Page $page, User $actor, int $targetUserId, ?string $reason = null): PageBlockedUser
    {
        if ((int) $page->owner_id === $targetUserId) {
            throw new Exception('Cannot block the page owner.');
        }

        // যদি ফলোয়ার থাকে আনফলো করে দেওয়া
        $follower = PageFollower::where('page_id', $page->id)->where('user_id', $targetUserId)->first();
        if ($follower) {
            $follower->delete();
            $page->decrement('followers_count');
        }

        $blocked = PageBlockedUser::updateOrCreate(
            ['page_id' => $page->id, 'user_id' => $targetUserId],
            ['reason' => $reason, 'blocked_by' => $actor->id]
        );

        $this->logAudit($page, $actor, 'moderation.block_user', 'User', $targetUserId, null, ['reason' => $reason]);

        return $blocked;
    }

    /**
     * ইউজারকে আনব্লক করা
     */
    public function unblockUser(Page $page, User $actor, int $targetUserId): bool
    {
        $blocked = PageBlockedUser::where('page_id', $page->id)->where('user_id', $targetUserId)->first();
        if ($blocked) {
            $blocked->delete();
            $this->logAudit($page, $actor, 'moderation.unblock_user', 'User', $targetUserId);

            return true;
        }

        return false;
    }

    /**
     * কিওয়ার্ড ব্লকলিস্ট যাচাই
     */
    public function containsBlockedKeyword(Page $page, string $text): bool
    {
        $settings = $page->settings ?? [];
        $blockedKeywords = $settings['moderation']['blocked_keywords'] ?? [];

        if (empty($blockedKeywords) || ! is_array($blockedKeywords)) {
            return false;
        }

        $lowerText = strtolower($text);
        foreach ($blockedKeywords as $kw) {
            $trimmed = trim(strtolower((string) $kw));
            if (! empty($trimmed) && str_contains($lowerText, $trimmed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * কমেন্ট মডারেশন (hide, pin, delete)
     *
     * @throws Exception
     */
    public function moderateComment(Page $page, User $actor, int $commentId, string $action): bool
    {
        $comment = Comment::findOrFail($commentId);

        // কমেন্টটি কি এই পেজের কোনো পোস্টে করা হয়েছে কিনা যাচাই
        $post = Post::where('id', $comment->post_id)->where('page_id', $page->id)->first();
        if (! $post) {
            throw new Exception('Comment does not belong to any post on this page.');
        }

        switch ($action) {
            case 'delete':
                $comment->delete();
                $post->decrement('comments_count');
                break;
            case 'pin':
                $comment->update(['is_pinned' => true]);
                break;
            case 'unpin':
                $comment->update(['is_pinned' => false]);
                break;
            default:
                throw new Exception("Unsupported moderation action: {$action}");
        }

        $this->logAudit($page, $actor, "comment.{$action}", 'Comment', $commentId);

        return true;
    }

    /**
     * পেজ মেসেজিং কনভারসেশন তালিকা
     */
    public function getConversations(Page $page, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PageConversation::where('page_id', $page->id)
            ->with(['user', 'assignedAgent']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->latest('last_message_at')->paginate($perPage);
    }

    /**
     * মেসেজ প্রেরণ করা (পেজ বা ভিজিটর দ্বারা)
     *
     * @throws Exception
     */
    public function sendMessage(
        Page $page,
        User $sender,
        int $conversationId,
        string $body,
        string $senderType = 'page'
    ): PageMessage {
        $conv = PageConversation::where('page_id', $page->id)->findOrFail($conversationId);

        return DB::transaction(function () use ($page, $conv, $sender, $body, $senderType) {
            $msg = PageMessage::create([
                'conversation_id' => $conv->id,
                'sender_type' => $senderType,
                'sender_id' => $sender->id,
                'body' => $body,
                'is_read' => false,
            ]);

            $conv->update(['last_message_at' => now()]);
            if ($senderType === 'page') {
                $conv->increment('unread_user_count');
            } else {
                $conv->increment('unread_page_count');
            }

            try {
                broadcast(new PageNewMessageEvent(
                    pageId: (int) $page->id,
                    conversationId: (int) $conv->id,
                    messageData: [
                        'id' => $msg->id,
                        'conversation_id' => $conv->id,
                        'sender_type' => $msg->sender_type,
                        'sender_id' => $msg->sender_id,
                        'body' => $msg->body,
                        'created_at' => $msg->created_at?->toIso8601String(),
                    ],
                    recipientUserId: $senderType === 'page' ? (int) $conv->user_id : (int) $conv->assigned_to
                ));
            } catch (\Throwable $e) {
                Log::warning('Could not broadcast PageNewMessageEvent: '.$e->getMessage());
            }

            return $msg;
        });
    }

    /**
     * কনভারসেশনে ইন্টারনাল স্টাফ নোট যুক্ত করা
     */
    public function addConversationNote(Page $page, User $staff, int $conversationId, string $body): PageConversationNote
    {
        $conv = PageConversation::where('page_id', $page->id)->findOrFail($conversationId);

        return PageConversationNote::create([
            'conversation_id' => $conv->id,
            'user_id' => $staff->id,
            'body' => $body,
        ]);
    }

    /**
     * পেজ ইভেন্ট তৈরি করা
     */
    public function createEvent(Page $page, User $actor, array $data): PageEvent
    {
        $title = trim($data['title']);
        $slug = Str::slug($title);
        $original = $slug;
        $c = 1;
        while (PageEvent::where('page_id', $page->id)->where('slug', $slug)->exists()) {
            $slug = "{$original}-{$c}";
            $c++;
        }

        $event = PageEvent::create([
            'page_id' => $page->id,
            'title' => $title,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'cover_image_url' => $data['cover_image_url'] ?? null,
            'location' => $data['location'] ?? null,
            'start_time' => Carbon::parse($data['start_time']),
            'end_time' => ! empty($data['end_time']) ? Carbon::parse($data['end_time']) : null,
            'is_online' => ! empty($data['is_online']),
            'status' => 'published',
        ]);

        $this->logAudit($page, $actor, 'event.create', 'PageEvent', $event->id, null, ['title' => $title]);

        return $event;
    }

    /**
     * পেজ কমার্স প্রোডাক্ট তৈরি করা
     */
    public function createProduct(Page $page, User $actor, array $data): PageProduct
    {
        $title = trim($data['title']);
        $slug = Str::slug($title);
        $original = $slug;
        $c = 1;
        while (PageProduct::where('page_id', $page->id)->where('slug', $slug)->exists()) {
            $slug = "{$original}-{$c}";
            $c++;
        }

        $product = PageProduct::create([
            'page_id' => $page->id,
            'title' => $title,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'price' => (float) $data['price'],
            'currency' => $data['currency'] ?? 'BDT',
            'stock_quantity' => (int) ($data['stock_quantity'] ?? 0),
            'status' => $data['status'] ?? 'active',
            'media_urls' => $data['media_urls'] ?? [],
        ]);

        $this->logAudit($page, $actor, 'product.create', 'PageProduct', $product->id, null, ['title' => $title]);

        return $product;
    }

    /**
     * প্রোডাক্ট ক্রয় করা (কন্টেন্ট ও ইনভেন্টরি রেস কন্ডিশন প্রটেকশন সহ)
     *
     * @throws Exception
     */
    public function purchaseProduct(Page $page, User $buyer, int $productId, int $quantity = 1): PageProduct
    {
        if ($quantity <= 0) {
            throw new Exception('Invalid quantity.');
        }

        return DB::transaction(function () use ($page, $buyer, $productId, $quantity) {
            $product = PageProduct::where('page_id', $page->id)
                ->where('id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $product) {
                throw new Exception('Product not found for this page.');
            }

            if ($product->status !== 'active') {
                throw new Exception('Product is not currently available for purchase.');
            }

            if ($product->stock_quantity < $quantity) {
                throw new Exception('Insufficient stock available.');
            }

            $affected = PageProduct::where('id', $product->id)
                ->where('stock_quantity', '>=', $quantity)
                ->decrement('stock_quantity', $quantity);

            if ($affected === 0) {
                throw new Exception('Concurrent purchase collision. Insufficient stock available.');
            }

            $fresh = $product->fresh();
            if ($fresh->stock_quantity === 0) {
                $fresh->update(['status' => 'out_of_stock']);
            }

            $this->logAudit($page, $buyer, 'product.purchase', 'PageProduct', $product->id, null, [
                'quantity' => $quantity,
                'price' => $product->price,
                'remaining_stock' => $fresh->stock_quantity,
            ]);

            return $fresh;
        });
    }
}
