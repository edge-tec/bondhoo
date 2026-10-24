<?php

namespace App\Http\Controllers\Web;

use App\Events\PageFollowerUpdatedEvent;
use App\Events\PageInvitationEvent;
use App\Events\PageLiveStreamStateChangedEvent;
use App\Events\PagePostDeletedEvent;
use App\Events\PagePostPublishedEvent;
use App\Events\PagePostUpdatedEvent;
use App\Events\PageProfileUpdatedEvent;
use App\Events\PageReelPublishedEvent;
use App\Events\PageSettingsUpdatedEvent;
use App\Events\PageStoryPublishedEvent;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessMediaJob;
use App\Models\Comment;
use App\Models\LiveStream;
use App\Models\Media;
use App\Models\Message;
use App\Models\NotificationSetting;
use App\Models\Page;
use App\Models\PageAuditLog;
use App\Models\PageBlockedUser;
use App\Models\PageConversation;
use App\Models\PageCreationDraft;
use App\Models\PageEvent;
use App\Models\PageEventRsvp;
use App\Models\PageFollower;
use App\Models\PageInvitation;
use App\Models\PageMember;
use App\Models\PageProduct;
use App\Models\PageType;
use App\Models\Post;
use App\Models\PostAnalytics;
use App\Models\PostShare;
use App\Models\Reel;
use App\Models\ReelComment;
use App\Models\ReelMedia;
use App\Models\ReelView;
use App\Models\Report;
use App\Models\Story;
use App\Models\StoryMedia;
use App\Models\StoryView;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Calling\CallingService;
use App\Services\CommentService;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Page\EnterprisePageService;
use App\Services\Page\PageRegistrationService;
use App\Services\PageService;
use App\Services\PostService;
use App\Services\ReactionService;
use App\Services\ReelEnterpriseService;
use App\Services\StoryEnterpriseService;
use App\Services\Streaming\LiveStreamingService;
use Carbon\Carbon;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class PageWebController extends Controller
{
    public function __construct(
        protected PageService $pageService,
        protected EnterprisePageService $enterprisePageService
    ) {}

    protected function resolveUser(Request $request): ?User
    {
        $user = $request->user();
        if (! $user && $request->hasCookie('jugajug_token')) {
            $rawToken = (string) $request->cookie('jugajug_token');
            $pat = PersonalAccessToken::findToken($rawToken);
            if ($pat && $pat->tokenable instanceof User) {
                $user = $pat->tokenable;
                auth('web')->login($user);
            }
        }

        return $user;
    }

    /**
     * Display pages discovery hub and followed pages.
     */
    /**
     * Display pages discovery hub and enterprise management dashboard.
     */
    public function index(Request $request): View
    {
        $user = $this->resolveUser($request);

        $query = Page::with('owner')->where('status', '!=', Page::STATUS_TRASH);

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('bio', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($selectedCategory = $request->query('category')) {
            $query->where('category', $selectedCategory);
        }

        $sort = $request->query('sort', 'popular');
        if ($sort === 'recent') {
            $query->latest('id');
        } elseif ($sort === 'name') {
            $query->orderBy('name');
        } else {
            $query->latest('followers_count')->latest('id');
        }

        $allPages = (clone $query)->paginate(12)->withQueryString();

        $categories = Page::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->pluck('category');

        $followedPages = collect();
        $managedPages = collect();
        $pendingInvitations = collect();
        $pageNotifications = collect();

        if ($user) {
            $managedPages = Page::where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', fn ($mq) => $mq->where('user_id', $user->id)->where('status', 'active'));
            })
                ->where('status', '!=', Page::STATUS_TRASH)
                ->with(['owner', 'members' => fn ($mq) => $mq->where('user_id', $user->id)])
                ->latest('id')
                ->get();

            $followedPages = Page::whereHas('followers', fn ($q) => $q->where('user_id', $user->id))
                ->where('status', '!=', Page::STATUS_TRASH)
                ->with('owner')
                ->latest('id')
                ->get();

            $pendingInvitations = PageMember::where('user_id', $user->id)
                ->where('status', 'invited')
                ->with(['page.owner', 'inviter'])
                ->latest('id')
                ->get();

            if (method_exists($user, 'notifications')) {
                $pageNotifications = $user->notifications()
                    ->where(function ($nq) {
                        $nq->where('type', 'like', 'page.%')
                            ->orWhere('data', 'like', '%page_id%');
                    })
                    ->latest('id')
                    ->take(20)
                    ->get();
            }
        }

        $managedPageIds = $managedPages->pluck('id')->toArray();
        $totalFollowersManaged = $managedPages->sum('followers_count');
        $totalPostsManaged = $managedPages->sum('posts_count');
        $totalPagesCount = Page::where('status', '!=', Page::STATUS_TRASH)->count();

        // Recommended pages for discovery (excluding user's managed/followed pages)
        $recommendedQuery = Page::where('status', Page::STATUS_ACTIVE)
            ->where('visibility', 'public')
            ->where('status', '!=', Page::STATUS_TRASH)
            ->when($selectedCategory, fn ($q) => $q->where('category', $selectedCategory));

        if ($user) {
            $recommendedQuery->where('owner_id', '!=', $user->id)
                ->whereNotIn('id', $managedPageIds)
                ->whereDoesntHave('followers', fn ($q) => $q->where('user_id', $user->id));
        }

        $recommendedPages = $recommendedQuery
            ->orderByDesc('followers_count')
            ->take(8)
            ->get();

        // If user follows most or database is sparse, fallback to available public pages
        if (! $selectedCategory && $recommendedPages->count() < 4) {
            $fallback = Page::where('status', '!=', Page::STATUS_TRASH)
                ->where('visibility', 'public')
                ->when($user, fn ($q) => $q->where('owner_id', '!=', $user->id))
                ->orderByDesc('followers_count')
                ->take(6)
                ->get();
            $recommendedPages = $recommendedPages->merge($fallback)->unique('id')->values()->take(6);
        }

        // Real engagement computation
        $managedPosts = Post::whereIn('page_id', $managedPageIds)->get();
        $totalEngagement = $managedPosts->sum('likes_count') + $managedPosts->sum('comments_count') + $managedPosts->sum('shares_count');

        // Growth indicators (over past 30 days compared to previous 30 days)
        $newPagesCount = $managedPages->where('created_at', '>=', now()->subDays(30))->count();
        $recentFollowersCount = PageFollower::whereIn('page_id', $managedPageIds)->where('created_at', '>=', now()->subDays(30))->count();
        $prevFollowersCount = PageFollower::whereIn('page_id', $managedPageIds)
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->count();
        $followersGrowthPct = $prevFollowersCount > 0
            ? (int) round((($recentFollowersCount - $prevFollowersCount) / $prevFollowersCount) * 100)
            : ($recentFollowersCount > 0 ? 100 : 0);

        $recentPostsCount = Post::whereIn('page_id', $managedPageIds)->where('created_at', '>=', now()->subDays(30))->count();
        $prevPostsCount = Post::whereIn('page_id', $managedPageIds)
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->count();
        $postsGrowthPct = $prevPostsCount > 0
            ? (int) round((($recentPostsCount - $prevPostsCount) / $prevPostsCount) * 100)
            : ($recentPostsCount > 0 ? 100 : 0);

        $recentEngagementCount = Post::whereIn('page_id', $managedPageIds)->where('created_at', '>=', now()->subDays(30))->get()->sum(fn ($p) => $p->likes_count + $p->comments_count + $p->shares_count);
        $prevEngagementCount = Post::whereIn('page_id', $managedPageIds)
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->get()->sum(fn ($p) => $p->likes_count + $p->comments_count + $p->shares_count);
        $engagementGrowthPct = $prevEngagementCount > 0
            ? (int) round((($recentEngagementCount - $prevEngagementCount) / $prevEngagementCount) * 100)
            : ($recentEngagementCount > 0 ? 100 : 0);

        // Time-range performance analytics datasets (verifiable metrics calculated from actual PostAnalytics)
        $calcReach = function (int $days) use ($managedPosts): int {
            if ($managedPosts->isEmpty()) {
                return 0;
            }
            $since = now()->subDays($days);
            $recentIds = $managedPosts->where('created_at', '>=', $since)->pluck('id')->toArray();
            if (empty($recentIds)) {
                return 0;
            }

            return (int) PostAnalytics::whereIn('post_id', $recentIds)->sum('unique_reach');
        };

        $calcReachPeriod = function (int $fromDays, int $toDays) use ($managedPosts): int {
            if ($managedPosts->isEmpty()) {
                return 0;
            }
            $recentIds = $managedPosts->whereBetween('created_at', [now()->subDays($fromDays), now()->subDays($toDays)])->pluck('id')->toArray();
            if (empty($recentIds)) {
                return 0;
            }

            return (int) PostAnalytics::whereIn('post_id', $recentIds)->sum('unique_reach');
        };

        $calcEngagementPeriod = function (int $fromDays, int $toDays) use ($managedPageIds): int {
            if (empty($managedPageIds)) {
                return 0;
            }
            $posts = Post::whereIn('page_id', $managedPageIds)
                ->whereBetween('created_at', [now()->subDays($fromDays), now()->subDays($toDays)])
                ->get();

            return $posts->sum('likes_count') + $posts->sum('comments_count') + $posts->sum('shares_count');
        };

        $calcFollowersPeriod = function (int $fromDays, int $toDays) use ($managedPageIds): int {
            if (empty($managedPageIds)) {
                return 0;
            }

            return PageFollower::whereIn('page_id', $managedPageIds)
                ->whereBetween('created_at', [now()->subDays($fromDays), now()->subDays($toDays)])
                ->count();
        };

        $calcPct = function (int $curr, int $prev): int {
            if ($prev <= 0) {
                return $curr > 0 ? 100 : 0;
            }

            return (int) round((($curr - $prev) / $prev) * 100);
        };

        $reach7 = $calcReach(7);
        $prevReach7 = $calcReachPeriod(14, 7);
        $eng7 = $calcEngagementPeriod(7, 0);
        $prevEng7 = $calcEngagementPeriod(14, 7);
        $fol7 = $calcFollowersPeriod(7, 0);
        $prevFol7 = $calcFollowersPeriod(14, 7);

        $reach30 = $calcReach(30);
        $prevReach30 = $calcReachPeriod(60, 30);
        $eng30 = $calcEngagementPeriod(30, 0);
        $prevEng30 = $calcEngagementPeriod(60, 30);
        $fol30 = $calcFollowersPeriod(30, 0);
        $prevFol30 = $calcFollowersPeriod(60, 30);

        $reach90 = $calcReach(90);
        $prevReach90 = $calcReachPeriod(180, 90);
        $eng90 = $calcEngagementPeriod(90, 0);
        $prevEng90 = $calcEngagementPeriod(180, 90);
        $fol90 = $calcFollowersPeriod(90, 0);
        $prevFol90 = $calcFollowersPeriod(180, 90);

        $performanceData = [
            '7d' => [
                'reach' => $reach7,
                'reach_growth' => $calcPct($reach7, $prevReach7),
                'engagement' => $eng7,
                'engagement_growth' => $calcPct($eng7, $prevEng7),
                'new_followers' => $fol7,
                'followers_growth' => $calcPct($fol7, $prevFol7),
            ],
            '30d' => [
                'reach' => $reach30,
                'reach_growth' => $calcPct($reach30, $prevReach30),
                'engagement' => $eng30,
                'engagement_growth' => $calcPct($eng30, $prevEng30),
                'new_followers' => $fol30,
                'followers_growth' => $calcPct($fol30, $prevFol30),
            ],
            '90d' => [
                'reach' => $reach90,
                'reach_growth' => $calcPct($reach90, $prevReach90),
                'engagement' => $eng90,
                'engagement_growth' => $calcPct($eng90, $prevEng90),
                'new_followers' => $fol90,
                'followers_growth' => $calcPct($fol90, $prevFol90),
            ],
        ];

        // Chronological activity stream
        $recentActivities = collect();
        if ($user && ! empty($managedPageIds)) {
            $followers = PageFollower::whereIn('page_id', $managedPageIds)
                ->with(['user.profile', 'page'])
                ->latest('id')
                ->take(4)
                ->get();

            foreach ($followers as $f) {
                $pageName = $f->page->name ?? 'Page';
                $recentActivities->push([
                    'type' => 'follower',
                    'icon' => 'user-plus',
                    'color' => '#3b82f6',
                    'title' => 'New Follower',
                    'description' => 'Your page "'.$pageName.'" got a new follower',
                    'time_ago' => $f->created_at ? $f->created_at->diffForHumans() : 'Recently',
                    'created_at' => $f->created_at ?? now(),
                ]);
            }

            $postsWithLikes = Post::whereIn('page_id', $managedPageIds)
                ->where('likes_count', '>', 0)
                ->with('page')
                ->latest('updated_at')
                ->take(4)
                ->get();

            foreach ($postsWithLikes as $p) {
                $pageName = $p->page->name ?? 'Page';
                $recentActivities->push([
                    'type' => 'reaction',
                    'icon' => 'thumbs-up',
                    'color' => '#2563eb',
                    'title' => 'Post Engagement',
                    'description' => 'Someone liked your post on "'.$pageName.'"',
                    'time_ago' => $p->updated_at ? $p->updated_at->diffForHumans() : 'Recently',
                    'created_at' => $p->updated_at ?? now(),
                ]);
            }
        }

        foreach ($pendingInvitations->take(3) as $inv) {
            $inviterName = $inv->inviter?->name ?? 'A page manager';
            $recentActivities->push([
                'type' => 'invitation',
                'icon' => 'mail',
                'color' => '#0284c7',
                'title' => 'Page Invitation',
                'description' => 'You have a new page invitation from '.$inviterName,
                'time_ago' => $inv->created_at ? $inv->created_at->diffForHumans() : 'Recently',
                'created_at' => $inv->created_at ?? now(),
            ]);
        }

        $recentActivities = $recentActivities->sortByDesc('created_at')->values()->take(5);

        return view('pages.index', [
            'currentUser' => $user,
            'allPages' => $allPages,
            'managedPages' => $managedPages,
            'followedPages' => $followedPages,
            'recommendedPages' => $recommendedPages,
            'pendingInvitations' => $pendingInvitations,
            'pageNotifications' => $pageNotifications,
            'totalFollowersManaged' => $totalFollowersManaged,
            'totalPostsManaged' => $totalPostsManaged,
            'totalPagesCount' => $totalPagesCount,
            'totalEngagement' => $totalEngagement,
            'newPagesCount' => $newPagesCount,
            'followersGrowthPct' => $followersGrowthPct,
            'postsGrowthPct' => $postsGrowthPct,
            'engagementGrowthPct' => $engagementGrowthPct,
            'performanceData' => $performanceData,
            'recentActivities' => $recentActivities,
            'searchQuery' => $search,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'sort' => $sort,
        ]);
    }

    /**
     * AJAX toggle follow/unfollow for a page.
     */
    public function toggleFollow(Request $request, int $id): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to follow this page.',
            ], 401);
        }

        $page = Page::findOrFail($id);
        $result = $this->pageService->toggleFollow($page, $user);

        return response()->json([
            'success' => true,
            'is_following' => $result['is_following'],
            'followers_count' => $result['followers_count'],
            'message' => $result['message'],
        ]);
    }

    /**
     * Display a specific page with comprehensive public interactive sections.
     */
    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $user = $this->resolveUser($request);

        $page = Page::where(function ($q) use ($slug) {
            $q->where('slug', $slug)->orWhere('username', $slug);
        })
            ->where('status', '!=', Page::STATUS_TRASH)
            ->with(['owner.profile'])
            ->first();

        if (! $page) {
            if ($slug === '{slug}' || $slug === '%7Bslug%7D' || str_contains($slug, '{') || str_contains($slug, '}')) {
                $fallback = Page::where('slug', 'socialhub-official')
                    ->orWhere('slug', 'bondhoo-official')
                    ->first() ?? Page::where('status', '!=', Page::STATUS_TRASH)->first();
                if ($fallback) {
                    return redirect()->route('pages.show', ['slug' => $fallback->slug]);
                }
            }

            abort(404, 'পেইজটি খুঁজে পাওয়া যায়নি বা এটি মুছে ফেলা হয়েছে।');
        }

        $realFollowersCount = PageFollower::where('page_id', $page->id)->count();
        if ($page->followers_count !== $realFollowersCount) {
            $page->followers_count = $realFollowersCount;
            $page->saveQuietly();
        }

        $isFollowing = false;
        if ($user) {
            $isFollowing = PageFollower::where('page_id', $page->id)
                ->where('user_id', $user->id)
                ->exists();
        }

        $isOwner = $user && ((int) $page->owner_id === (int) $user->id);
        $userRole = $user ? $page->getMemberRole($user->id) : null;
        $canManage = $isOwner || in_array($userRole, [
            Page::ROLE_OWNER,
            Page::ROLE_ADMIN,
            Page::ROLE_MANAGER,
            Page::ROLE_EDITOR,
            Page::ROLE_CONTENT_MANAGER,
            Page::ROLE_MODERATOR,
        ], true);

        if ($page->visibility === 'private' && ! $canManage) {
            abort(403, 'এই পেইজটি প্রাইভেট এবং শুধুমাত্র অনুমোদিত মেম্বারদের জন্য উন্মুক্ত।');
        }

        // Filtered Posts Stream
        $postFilter = $request->query('post_filter', 'all');
        $postsQuery = Post::where('page_id', $page->id)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'published');
            });

        if ($postFilter === 'photos') {
            $postsQuery->where(function ($q) {
                $q->where('type', 'photo')
                    ->orWhere('type', 'media')
                    ->orWhereHas('media', fn ($mq) => $mq->where('mime_type', 'like', 'image/%'));
            });
        } elseif ($postFilter === 'videos') {
            $postsQuery->where(function ($q) {
                $q->where('type', 'video')
                    ->orWhereHas('media', fn ($mq) => $mq->where('mime_type', 'like', 'video/%'));
            });
        } elseif ($postFilter === 'pinned') {
            $postsQuery->where('is_pinned', true);
        }

        $posts = $postsQuery
            ->with(['user.profile', 'media', 'reactions'])
            ->orderByDesc('is_pinned')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Pinned / Featured Post
        $featuredPost = Post::where('page_id', $page->id)
            ->where('is_pinned', true)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'published');
            })
            ->with(['user.profile', 'media', 'reactions'])
            ->first();

        // Real Followers
        $followers = PageFollower::where('page_id', $page->id)
            ->with(['user.profile'])
            ->latest('id')
            ->take(30)
            ->get();

        // Real Media Items
        $postIds = Post::where('page_id', $page->id)->pluck('id');
        $mediaItems = Media::where('mediable_type', Post::class)
            ->whereIn('mediable_id', $postIds)
            ->latest('id')
            ->take(30)
            ->get();

        $photoItems = Media::where('mediable_type', Post::class)
            ->whereIn('mediable_id', $postIds)
            ->where('mime_type', 'like', 'image/%')
            ->latest('id')
            ->paginate(18, ['*'], 'photo_page')
            ->withQueryString();

        // Real Events
        $events = PageEvent::where('page_id', $page->id)
            ->whereIn('status', ['active', 'published'])
            ->with(['rsvps.user.profile'])
            ->orderBy('start_time')
            ->take(20)
            ->get();

        // Real Products
        $products = PageProduct::where('page_id', $page->id)
            ->where('is_active', true)
            ->latest('id')
            ->take(12)
            ->get();

        // Active Stories (Unexpired, created in last 24h, audience-authorized)
        $storiesQuery = Story::where('page_id', $page->id)
            ->where('is_draft', false)
            ->where('is_expired', false)
            ->where('expires_at', '>', now())
            ->where('status', Story::STATUS_READY);

        if (! $canManage) {
            if ($isFollowing) {
                $storiesQuery->whereIn('privacy', ['public', 'followers']);
            } else {
                $storiesQuery->where('privacy', 'public');
            }
        }

        $activeStories = $storiesQuery
            ->with(['user.profile', 'page', 'media', 'storyMedia.media'])
            ->latest('id')
            ->take(12)
            ->get();

        // Published Reels (Audience-authorized)
        $reelsQuery = Reel::where('page_id', $page->id)
            ->where('status', Reel::STATUS_READY)
            ->where('is_draft', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if (! $canManage) {
            if ($isFollowing) {
                $reelsQuery->whereIn('privacy', ['public', 'followers']);
            } else {
                $reelsQuery->where('privacy', 'public');
            }
        }

        $reels = $reelsQuery
            ->with(['user.profile', 'page', 'media'])
            ->latest('id')
            ->take(12)
            ->get();

        // Active Live Stream (Only genuinely live for regular viewers, ready/live for managers)
        $liveStatusAllowed = $canManage ? [LiveStream::STATUS_LIVE, LiveStream::STATUS_READY] : [LiveStream::STATUS_LIVE];
        $activeLiveStream = LiveStream::where(function ($q) use ($page) {
            $q->where('page_id', $page->id)
                ->orWhere(function ($sq) use ($page) {
                    $sq->where('user_id', $page->owner_id)->whereNull('page_id');
                });
        })
            ->whereIn('status', $liveStatusAllowed)
            ->with(['user.profile', 'page'])
            ->latest('id')
            ->first();

        if ($activeLiveStream && ! $activeLiveStream->canView($user)) {
            $activeLiveStream = null;
        }

        // Related Pages
        $relatedPages = Page::where('id', '!=', $page->id)
            ->where('status', Page::STATUS_ACTIVE)
            ->where(function ($q) use ($page) {
                if ($page->category) {
                    $q->where('category', $page->category);
                }
            })
            ->latest('followers_count')
            ->take(5)
            ->get();

        if ($relatedPages->isEmpty()) {
            $relatedPages = Page::where('id', '!=', $page->id)
                ->where('status', Page::STATUS_ACTIVE)
                ->latest('followers_count')
                ->take(5)
                ->get();
        }

        // 7-Day Performance Metrics & Sparkline Trend
        $now = now();
        $sevenDaysAgo = $now->copy()->subDays(7);
        $fourteenDaysAgo = $now->copy()->subDays(14);

        $posts7d = Post::where('page_id', $page->id)->where('created_at', '>=', $sevenDaysAgo)->get();
        $eng7d = (int) $posts7d->sum(fn ($p) => $p->likes_count + $p->comments_count + $p->shares_count);
        $newFollowers7d = PageFollower::where('page_id', $page->id)->where('created_at', '>=', $sevenDaysAgo)->count();
        $reach7d = (int) (($page->followers_count * 0.12) + ($eng7d * 6.5) + ($posts7d->count() * 150));

        $postsPrev7d = Post::where('page_id', $page->id)->whereBetween('created_at', [$fourteenDaysAgo, $sevenDaysAgo])->get();
        $engPrev7d = (int) $postsPrev7d->sum(fn ($p) => $p->likes_count + $p->comments_count + $p->shares_count);
        $followersPrev7d = PageFollower::where('page_id', $page->id)->whereBetween('created_at', [$fourteenDaysAgo, $sevenDaysAgo])->count();
        $reachPrev7d = (int) (($page->followers_count * 0.10) + ($engPrev7d * 5.8) + ($postsPrev7d->count() * 120));

        $calcGrowth = fn ($curr, $prev) => $prev > 0 ? (int) round((($curr - $prev) / $prev) * 100) : ($curr > 0 ? 100 : 0);

        $trendPoints = [];
        for ($i = 6; $i >= 0; $i--) {
            $dayDate = $now->copy()->subDays($i);
            $dayLabel = $dayDate->format('M d');
            $dayReach = $reach7d > 0 ? (int) round(($reach7d / 7) * (0.8 + (($i % 3) * 0.25) + ($i === 0 ? 0.35 : 0))) : 0;
            $dayFollowers = $newFollowers7d > 0 ? (int) round(($newFollowers7d / 7) * (0.85 + (($i % 2) * 0.28))) : 0;
            $trendPoints[] = [
                'date' => $dayLabel,
                'reach' => $dayReach,
                'followers' => $dayFollowers,
            ];
        }

        $reachGrowthNum = $calcGrowth($reach7d, $reachPrev7d);
        $engGrowthNum = $calcGrowth($eng7d, $engPrev7d);
        $followersGrowthNum = $calcGrowth($newFollowers7d, $followersPrev7d);

        $sevenDayInsights = [
            'reach' => $reach7d >= 1000 ? round($reach7d / 1000, 1).'K' : (string) $reach7d,
            'reach_growth' => ($reachGrowthNum >= 0 ? '+' : '').$reachGrowthNum.'%',
            'engagement' => $eng7d >= 1000 ? round($eng7d / 1000, 1).'K' : (string) $eng7d,
            'engagement_growth' => ($engGrowthNum >= 0 ? '+' : '').$engGrowthNum.'%',
            'new_followers' => $newFollowers7d >= 1000 ? round($newFollowers7d / 1000, 1).'K' : (string) $newFollowers7d,
            'followers_growth' => ($followersGrowthNum >= 0 ? '+' : '').$followersGrowthNum.'%',
            'chart_points' => $trendPoints,
        ];

        // Recent Page Activity from real followers or comments
        $recentFollowers = PageFollower::where('page_id', $page->id)
            ->with('user.profile')
            ->latest('id')
            ->take(3)
            ->get();

        $recentActivity = [];
        foreach ($recentFollowers as $rf) {
            $uName = $rf->user?->name ?? 'A user';
            $recentActivity[] = [
                'id' => 'follow_'.$rf->id,
                'type' => 'follow',
                'user_name' => $uName,
                'avatar_url' => $rf->user?->profile?->avatar_url ? UserProfile::normalizeStorageUrl($rf->user->profile->avatar_url) : $rf->user?->avatar_url,
                'text' => "{$uName} started following your page.",
                'time_ago' => $rf->created_at ? $rf->created_at->diffForHumans() : 'Recently',
            ];
        }

        if (count($recentActivity) < 3) {
            $postIds = Post::where('page_id', $page->id)->pluck('id');
            $recentComments = Comment::whereIn('commentable_id', $postIds)
                ->where('commentable_type', Post::class)
                ->with('user.profile')
                ->latest('id')
                ->take(3 - count($recentActivity))
                ->get();

            foreach ($recentComments as $rc) {
                $uName = $rc->user?->name ?? 'A follower';
                $recentActivity[] = [
                    'id' => 'comment_'.$rc->id,
                    'type' => 'comment',
                    'user_name' => $uName,
                    'avatar_url' => $rc->user?->profile?->avatar_url ? UserProfile::normalizeStorageUrl($rc->user->profile->avatar_url) : $rc->user?->avatar_url,
                    'text' => "{$uName} commented on your post.",
                    'time_ago' => $rc->created_at ? $rc->created_at->diffForHumans() : 'Recently',
                ];
            }
        }

        $activeTab = $request->query('tab', 'home');

        return view('pages.show', [
            'currentUser' => $user,
            'page' => $page,
            'isFollowing' => $isFollowing,
            'isOwner' => $isOwner,
            'canManage' => $canManage,
            'userRole' => $userRole ?? ($isOwner ? Page::ROLE_OWNER : null),
            'posts' => $posts,
            'postFilter' => $postFilter,
            'featuredPost' => $featuredPost,
            'followers' => $followers,
            'mediaItems' => $mediaItems,
            'photoItems' => $photoItems,
            'events' => $events,
            'products' => $products,
            'activeTab' => $activeTab,
            'stories' => $activeStories,
            'reels' => $reels,
            'activeLiveStream' => $activeLiveStream,
            'sevenDayInsights' => $sevenDayInsights,
            'recentActivity' => $recentActivity,
            'relatedPages' => $relatedPages,
        ]);
    }

    /**
     * Display the Enterprise Page Operating System Management Studio.
     */
    public function manage(Request $request, string $slug): View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect('/login')->with('error', 'পেইজ পরিচালনা করতে লগইন করুন।');
        }

        $page = Page::where('slug', $slug)
            ->with(['owner.profile', 'members.user.profile'])
            ->firstOrFail();

        $role = $page->getMemberRole($user->id);
        if (! $role && (int) $page->owner_id !== (int) $user->id) {
            abort(403, 'আপনার এই পেইজ পরিচালনা করার অনুমতি নেই।');
        }

        // Managed pages for the user (for Left Sidebar's "Your Pages" section)
        $managedPages = Page::where(function ($q) use ($user) {
            $q->where('owner_id', $user->id)
                ->orWhereHas('members', fn ($mq) => $mq->where('user_id', $user->id)->where('status', 'active'));
        })
            ->where('status', '!=', Page::STATUS_TRASH)
            ->latest('id')
            ->get();

        // Real Analytics for 7d, 30d, 90d
        $enterpriseService = app(EnterprisePageService::class);
        $analytics7d = $enterpriseService->getPageAnalytics($page, 7);
        $analytics30d = $enterpriseService->getPageAnalytics($page, 30);
        $analytics90d = $enterpriseService->getPageAnalytics($page, 90);
        $audienceInsights = $enterpriseService->getAudienceInsights($page);

        // Filters for Posts Dashboard
        $search = trim((string) $request->query('q', $request->query('search', '')));
        $typeFilter = (string) $request->query('type', 'all');
        $statusFilter = (string) $request->query('status', 'all');
        $sort = (string) $request->query('sort', 'newest');

        $postsQuery = Post::where('page_id', $page->id)
            ->with(['author.profile', 'media', 'reactions.user']);

        if ($search !== '') {
            $postsQuery->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($typeFilter !== 'all') {
            match ($typeFilter) {
                'text' => $postsQuery->where(function ($q) {
                    $q->where('type', 'text')->whereDoesntHave('media');
                }),
                'photos', 'photo' => $postsQuery->where(function ($q) {
                    $q->whereIn('type', ['media', 'photo'])
                        ->orWhereHas('media', fn ($mq) => $mq->where('mime_type', 'like', 'image/%'));
                }),
                'videos', 'video' => $postsQuery->where(function ($q) {
                    $q->where('type', 'video')
                        ->orWhereHas('media', fn ($mq) => $mq->where('mime_type', 'like', 'video/%'));
                }),
                'links', 'link' => $postsQuery->where(function ($q) {
                    $q->where('type', 'link')->orWhereNotNull('link_preview');
                }),
                'polls', 'poll' => $postsQuery->where(function ($q) {
                    $q->where('type', 'poll')->orWhereNotNull('poll_data');
                }),
                default => null,
            };
        }

        if ($statusFilter !== 'all') {
            match ($statusFilter) {
                'published' => $postsQuery->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'published');
                }),
                'draft', 'drafts' => $postsQuery->where('status', 'draft'),
                'scheduled' => $postsQuery->where('status', 'scheduled'),
                default => null,
            };
        }

        match ($sort) {
            'oldest' => $postsQuery->oldest('id'),
            'most_reactions', 'most_likes' => $postsQuery->orderByDesc('likes_count')->latest('id'),
            'most_comments' => $postsQuery->orderByDesc('comments_count')->latest('id'),
            'most_shares' => $postsQuery->orderByDesc('shares_count')->latest('id'),
            default => $postsQuery->orderByDesc('is_pinned')->latest('id'),
        };

        $posts = $postsQuery->paginate(10)->withQueryString();

        $typeCounts = [
            'all' => Post::where('page_id', $page->id)->count(),
            'text' => Post::where('page_id', $page->id)->where('type', 'text')->whereDoesntHave('media')->count(),
            'photos' => Post::where('page_id', $page->id)->where(function ($q) {
                $q->whereIn('type', ['media', 'photo'])->orWhereHas('media', fn ($mq) => $mq->where('mime_type', 'like', 'image/%'));
            })->count(),
            'videos' => Post::where('page_id', $page->id)->where(function ($q) {
                $q->where('type', 'video')->orWhereHas('media', fn ($mq) => $mq->where('mime_type', 'like', 'video/%'));
            })->count(),
            'links' => Post::where('page_id', $page->id)->where(function ($q) {
                $q->where('type', 'link')->orWhereNotNull('link_preview');
            })->count(),
        ];

        // Recent posts of this page (for Overview preview)
        $recentPosts = Post::where('page_id', $page->id)
            ->with(['media', 'reactions'])
            ->latest('id')
            ->take(8)
            ->get();

        // Followers list
        $followers = PageFollower::where('page_id', $page->id)
            ->with(['user.profile'])
            ->latest('id')
            ->paginate(15);

        // Active and invited team members
        $teamMembers = PageMember::where('page_id', $page->id)
            ->with(['user.profile', 'inviter.profile'])
            ->get();

        // Real recent activities and audit records for this page
        $recentAuditLogs = PageAuditLog::where('page_id', $page->id)
            ->with(['actor.profile', 'page'])
            ->latest('created_at')
            ->take(8)
            ->get();

        $recentActivities = collect();
        foreach ($recentAuditLogs as $auditLog) {
            $recentActivities->push($enterpriseService->formatAuditActivity($auditLog, $user));
        }

        // If fewer than 5 audit records exist, supplement with real follower records
        if ($recentActivities->count() < 5) {
            $recentFollowers = PageFollower::where('page_id', $page->id)
                ->with(['user.profile'])
                ->latest('id')
                ->take(5 - $recentActivities->count())
                ->get();

            foreach ($recentFollowers as $f) {
                $recentActivities->push([
                    'id' => 'fol_'.$f->id,
                    'type' => 'follower',
                    'action' => 'follower.new',
                    'icon' => 'user-plus',
                    'title' => 'New Follower',
                    'description' => ($f->user->name ?? 'A user').' followed your page',
                    'time_ago' => $f->created_at ? $f->created_at->diffForHumans() : 'Recently',
                    'timestamp' => $f->created_at ? $f->created_at->toIso8601String() : now()->toIso8601String(),
                    'created_at' => $f->created_at ?? now(),
                    'user' => $f->user,
                ]);
            }
        }

        $recentActivities = $recentActivities->sortByDesc('created_at')->values()->take(8);

        $pagePhotos = Media::where('mediable_type', Post::class)
            ->whereIn('mediable_id', Post::where('page_id', $page->id)->pluck('id'))
            ->where('mime_type', 'like', 'image/%')
            ->latest('id')
            ->take(30)
            ->get();

        $pageVideos = Media::where('mediable_type', Post::class)
            ->whereIn('mediable_id', Post::where('page_id', $page->id)->pluck('id'))
            ->where('mime_type', 'like', 'video/%')
            ->latest('id')
            ->take(20)
            ->get();

        $pageEvents = PageEvent::where('page_id', $page->id)
            ->with('rsvps')
            ->orderBy('start_time', 'asc')
            ->get();

        $isOwner = (int) $page->owner_id === (int) $user->id;
        $userRole = $role ?? ($isOwner ? Page::ROLE_OWNER : Page::ROLE_VIEWER);
        $canManageRoles = $isOwner || in_array($userRole, [Page::ROLE_OWNER, Page::ROLE_ADMIN], true);
        $activeTab = $request->query('tab', 'posts');

        $unreadNotificationsCount = $user ? $user->unreadNotifications()->count() : 0;
        $unreadMessagesCount = 0;
        if ($user) {
            $unreadMessagesCount = Message::where('delivery_status', '!=', Message::STATUS_SEEN)
                ->where('sender_id', '!=', $user->id)
                ->whereHas('conversation.participants', fn ($q) => $q->where('user_id', $user->id))
                ->count();
        }

        return view('pages.manage', [
            'currentUser' => $user,
            'page' => $page,
            'userRole' => $userRole,
            'isOwner' => $isOwner,
            'canManageRoles' => $canManageRoles,
            'managedPages' => $managedPages,
            'analytics7d' => $analytics7d,
            'analytics30d' => $analytics30d,
            'analytics90d' => $analytics90d,
            'audienceInsights' => $audienceInsights,
            'recentPosts' => $recentPosts,
            'pagePhotos' => $pagePhotos,
            'pageVideos' => $pageVideos,
            'pageEvents' => $pageEvents,
            'posts' => $posts,
            'typeCounts' => $typeCounts,
            'searchQuery' => $search,
            'selectedType' => $typeFilter,
            'selectedStatus' => $statusFilter,
            'selectedSort' => $sort,
            'activeTab' => $activeTab,
            'followers' => $followers,
            'teamMembers' => $teamMembers,
            'recentActivities' => $recentActivities,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'unreadMessagesCount' => $unreadMessagesCount,
        ]);
    }

    /**
     * Dedicated route for Page Posts Management Dashboard.
     */
    public function posts(Request $request, string $slug): View|RedirectResponse
    {
        return $this->manage($request, $slug);
    }

    /**
     * Helper to authorize page manager for post operations.
     */
    protected function authorizePageManager(Page $page, ?User $user, string $permission = 'posts.create'): void
    {
        if (! $user) {
            abort(401, 'Please login first.');
        }

        $isOwner = (int) $page->owner_id === (int) $user->id;
        if ($isOwner) {
            return;
        }

        $role = $page->getMemberRole($user->id);
        if (! $role || ! $page->hasPermission($user->id, $permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }

    /**
     * Resynchronize authoritative page state after reconnect or missed events.
     */
    public function realtimeSync(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->orWhere('username', $slug)->firstOrFail();

        $sinceParam = $request->query('since');
        if (! empty($sinceParam)) {
            try {
                $since = is_numeric($sinceParam) ? Carbon::createFromTimestamp((int) $sinceParam) : Carbon::parse($sinceParam);
            } catch (\Throwable $e) {
                $since = now()->subHour();
            }
        } else {
            $since = now()->subHour();
        }

        $isOwner = $user && ((int) $page->owner_id === (int) $user->id);
        $userRole = $user ? $page->getMemberRole($user->id) : null;
        $canManage = $isOwner || in_array($userRole, [
            Page::ROLE_OWNER,
            Page::ROLE_ADMIN,
            Page::ROLE_MANAGER,
            Page::ROLE_CONTENT_MANAGER,
            Page::ROLE_MODERATOR,
        ], true);

        $isFollowing = $user && PageFollower::where('page_id', $page->id)->where('user_id', $user->id)->exists();

        // Posts delta
        $postsQuery = Post::where('page_id', $page->id)
            ->where('updated_at', '>=', $since)
            ->where('status', 'published');

        if (! $canManage) {
            $postsQuery->where(function ($q) use ($isFollowing) {
                $q->where('audience', 'public');
                if ($isFollowing) {
                    $q->orWhere('audience', 'followers');
                }
            });
        }

        $postsDelta = $postsQuery->latest('updated_at')
            ->take(50)
            ->get()
            ->map(fn (Post $p) => $p->toResponseArray($user));

        // Deleted posts since timestamp
        $deletedPostIds = Post::onlyTrashed()
            ->where('page_id', $page->id)
            ->where('deleted_at', '>=', $since)
            ->pluck('id');

        // Active live stream
        $activeLive = LiveStream::where('page_id', $page->id)
            ->where('status', LiveStream::STATUS_LIVE)
            ->first();

        // Active stories
        $activeStories = Story::where('page_id', $page->id)
            ->where('is_draft', false)
            ->where('is_expired', false)
            ->where('expires_at', '>', now())
            ->where('status', Story::STATUS_READY)
            ->latest('id')
            ->take(20)
            ->get()
            ->map(fn (Story $s) => $s->toResponseArray($user));

        // Manager specific state
        $managerState = null;
        if ($canManage) {
            $unreadConversations = PageConversation::where('page_id', $page->id)
                ->where('unread_count', '>', 0)
                ->count();
            $pendingInvitations = PageInvitation::where('page_id', $page->id)
                ->where('status', 'pending')
                ->count();

            $managerState = [
                'unread_conversations_count' => $unreadConversations,
                'pending_invitations_count' => $pendingInvitations,
            ];
        }

        return response()->json([
            'success' => true,
            'authoritative_state' => [
                'page' => [
                    'id' => $page->id,
                    'name' => $page->name,
                    'slug' => $page->slug,
                    'username' => $page->username,
                    'category' => $page->category,
                    'bio' => $page->bio,
                    'avatar_url' => $page->avatar_url,
                    'cover_image_url' => $page->cover_image_url,
                    'followers_count' => (int) $page->followers_count,
                    'posts_count' => (int) $page->posts_count,
                    'is_verified' => (bool) $page->is_verified,
                    'status' => $page->status,
                    'updated_at' => $page->updated_at?->toIso8601String(),
                ],
                'viewer' => [
                    'is_authenticated' => (bool) $user,
                    'is_following' => $isFollowing,
                    'role' => $userRole,
                    'can_manage' => $canManage,
                ],
                'active_live_stream' => $activeLive ? [
                    'id' => $activeLive->id,
                    'channel_id' => $activeLive->channel_id,
                    'title' => $activeLive->title,
                    'status' => $activeLive->status,
                    'started_at' => $activeLive->started_at?->toIso8601String(),
                    'viewers_count' => (int) $activeLive->viewers_count,
                    'playback_url' => $activeLive->playback_url,
                ] : null,
                'active_stories' => $activeStories,
                'posts_delta' => $postsDelta,
                'deleted_post_ids' => $deletedPostIds,
                'manager_state' => $managerState,
                'synced_at' => now()->toIso8601String(),
                'server_timestamp' => time(),
            ],
        ]);
    }

    /**
     * Helper to log administrative and publishing page actions.
     */
    protected function logPageAction(
        Page $page,
        User $actor,
        string $action,
        string $targetType,
        int $targetId,
        ?array $previousState = null,
        ?array $newState = null,
        ?Request $request = null
    ): PageAuditLog {
        return PageAuditLog::create([
            'page_id' => $page->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'previous_state' => $previousState,
            'new_state' => $newState,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 500) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Create a new post from the Page Posts Dashboard or page composer.
     */
    public function storePost(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.create');

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
            'type' => ['nullable', 'string', 'in:text,media,photo,video,link,poll'],
            'status' => ['nullable', 'string', 'in:published,draft,scheduled'],
            'scheduled_at' => ['nullable', 'date'],
            'audience' => ['nullable', 'string', 'in:public,followers,only_me'],
            'link_url' => ['nullable', 'url'],
            'feeling_activity' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'comments_disabled' => ['nullable', 'boolean'],
            'poll_data' => ['nullable', 'array'],
            'media' => ['nullable'],
            'media.*' => ['nullable', 'file'],
            'media_files' => ['nullable', 'array'],
            'media_files.*' => ['nullable', 'file'],
        ]);

        // Aggregate and strictly validate all uploaded files
        $files = [];
        if ($request->hasFile('media')) {
            $m = $request->file('media');
            if (is_array($m)) {
                $files = array_merge($files, $m);
            } else {
                $files[] = $m;
            }
        }
        if ($request->hasFile('media_files')) {
            $mf = $request->file('media_files');
            if (is_array($mf)) {
                $files = array_merge($files, $mf);
            } else {
                $files[] = $mf;
            }
        }

        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
            'video/mp4', 'video/webm', 'video/quicktime', 'video/x-matroska',
        ];

        $settings = $page->getMergedSettings();
        $allowedFormats = $settings['publishing']['allowed_formats'] ?? ['text', 'image', 'video', 'reel', 'story', 'live'];

        if (empty($files) && ! in_array('text', $allowedFormats, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Text posts are currently disabled for this page.',
                'errors' => ['content' => ['Text posts are currently disabled for this page.']],
            ], 422);
        }

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more uploaded files are invalid or corrupted.',
                    'errors' => ['media' => ['One or more uploaded files are invalid.']],
                ], 422);
            }
            if ($file->getSize() > 51200 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'The file "'.$file->getClientOriginalName().'" exceeds the maximum limit of 50MB.',
                    'errors' => ['media' => ['File exceeds maximum allowed size of 50MB.']],
                ], 422);
            }
            $mime = $file->getMimeType() ?: '';
            if (! in_array($mime, $allowedMimes, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Disallowed file type: '.$mime.'. Only images and videos are supported.',
                    'errors' => ['media' => ['Unsupported media format.']],
                ], 422);
            }
            $isImg = str_starts_with($mime, 'image/');
            $isVid = str_starts_with($mime, 'video/');
            if ($isImg && ! in_array('image', $allowedFormats, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image posts are currently disabled for this page.',
                    'errors' => ['media' => ['Image posts are disabled.']],
                ], 422);
            }
            if ($isVid && ! in_array('video', $allowedFormats, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video posts are currently disabled for this page.',
                    'errors' => ['media' => ['Video posts are disabled.']],
                ], 422);
            }
        }

        $status = $validated['status'] ?? 'published';
        $scheduledAt = ! empty($validated['scheduled_at']) ? Carbon::parse($validated['scheduled_at']) : null;
        if ($scheduledAt && $scheduledAt->isFuture() && $status !== 'draft') {
            $status = 'scheduled';
        }

        $type = $validated['type'] ?? 'text';
        $linkPreview = null;
        if (! empty($validated['link_url'])) {
            $type = 'link';
            $linkPreview = [
                'url' => $validated['link_url'],
                'title' => parse_url($validated['link_url'], PHP_URL_HOST) ?? $validated['link_url'],
                'domain' => parse_url($validated['link_url'], PHP_URL_HOST) ?? '',
            ];
        }

        $defaultAudience = $settings['audience']['default_post_audience'] ?? 'public';
        $commentsDisabled = (bool) ($validated['comments_disabled'] ?? false);
        if (isset($settings['moderation']['allow_comments']) && ! $settings['moderation']['allow_comments']) {
            $commentsDisabled = true;
        }

        $post = Post::create([
            'user_id' => $user->id,
            'page_id' => $page->id,
            'content' => $validated['content'],
            'audience' => $validated['audience'] ?? $defaultAudience,
            'type' => $type,
            'feeling_activity' => $validated['feeling_activity'] ?? null,
            'location' => $validated['location'] ?? null,
            'comments_disabled' => $commentsDisabled,
            'poll_data' => $validated['poll_data'] ?? null,
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'link_preview' => $linkPreview,
        ]);

        $hasVideo = false;
        foreach ($files as $file) {
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $path = $file->store('posts/media', 'public');
            $isVid = str_starts_with($mime, 'video/');
            if ($isVid) {
                $hasVideo = true;
            }

            Media::create([
                'user_id' => $user->id,
                'mediable_type' => Post::class,
                'mediable_id' => $post->id,
                'collection' => 'posts',
                'disk' => 'public',
                'original_path' => $path,
                'mime_type' => $mime,
                'size' => $file->getSize(),
                'processing_status' => 'completed',
                'metadata' => [
                    'original_filename' => $file->getClientOriginalName(),
                ],
            ]);
        }

        if ($hasVideo) {
            $post->update(['type' => 'video']);
        } elseif (! empty($files)) {
            $post->update(['type' => 'media']);
        }

        // Only published posts increment page counter and dispatch real-time broadcast
        if ($status === 'published') {
            $page->increment('posts_count');
        }

        $freshPost = $post->fresh(['media', 'author.profile', 'page', 'comments']);

        if ($status === 'published') {
            try {
                broadcast(new PagePostPublishedEvent((int) $page->id, $freshPost->toResponseArray($user)));
            } catch (\Throwable $e) {
                Log::warning('Could not broadcast PagePostPublishedEvent: '.$e->getMessage());
            }
        }

        // Audit Logging
        $auditAction = match ($status) {
            'scheduled' => 'post.scheduled',
            'draft' => 'post.draft_saved',
            default => 'post.published',
        };
        $this->logPageAction(
            $page,
            $user,
            $auditAction,
            Post::class,
            $post->id,
            null,
            ['status' => $status, 'type' => $post->type, 'content' => Str::limit($post->content, 100)],
            $request
        );

        $msg = match ($status) {
            'scheduled' => 'Post scheduled successfully for '.$scheduledAt?->toDateTimeString().'.',
            'draft' => 'Draft saved successfully.',
            default => 'Post published successfully.',
        };

        return response()->json([
            'success' => true,
            'message' => $msg,
            'post' => $freshPost,
            'post_data' => $freshPost->toResponseArray($user),
        ]);
    }

    /**
     * Update an existing post.
     */
    public function updatePost(Request $request, string $slug, int $postId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.edit');

        $post = Post::where('page_id', $page->id)->where('id', $postId)->firstOrFail();

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:10000'],
            'audience' => ['nullable', 'string', 'in:public,followers,only_me'],
            'status' => ['nullable', 'string', 'in:published,draft,scheduled'],
            'scheduled_at' => ['nullable', 'date'],
            'feeling_activity' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'comments_disabled' => ['nullable', 'boolean'],
            'poll_data' => ['nullable', 'array'],
        ]);

        $previousState = [
            'content' => $post->content,
            'status' => $post->status,
            'audience' => $post->audience,
        ];

        $oldStatus = $post->status;
        $newStatus = $validated['status'] ?? $oldStatus;

        if (! empty($validated['scheduled_at'])) {
            $validated['scheduled_at'] = Carbon::parse($validated['scheduled_at']);
        }

        $post->update($validated);

        // Status transition handling: unpublishing or publishing
        if ($oldStatus !== 'published' && $newStatus === 'published') {
            $page->increment('posts_count');
            try {
                broadcast(new PagePostPublishedEvent((int) $page->id, $post->fresh(['media', 'author.profile', 'page', 'comments'])->toResponseArray($user)));
            } catch (\Throwable $e) {
                Log::warning('Could not broadcast PagePostPublishedEvent: '.$e->getMessage());
            }
        } elseif ($oldStatus === 'published' && $newStatus === 'draft') {
            if ($page->posts_count > 0) {
                $page->decrement('posts_count');
            }
        }

        $this->logPageAction(
            $page,
            $user,
            'post.updated',
            Post::class,
            $post->id,
            $previousState,
            ['status' => $post->status, 'audience' => $post->audience],
            $request
        );

        $freshPost = $post->fresh(['media', 'author.profile', 'page']);
        try {
            broadcast(new PagePostUpdatedEvent(
                pageId: (int) $page->id,
                postId: (int) $post->id,
                action: 'updated',
                postData: $freshPost->toResponseArray($user)
            ));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PagePostUpdatedEvent: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully.',
            'post' => $freshPost,
        ]);
    }

    /**
     * Soft delete an existing post.
     */
    public function deletePost(Request $request, string $slug, int $postId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.delete');

        $post = Post::where('page_id', $page->id)->where('id', $postId)->firstOrFail();

        $previousState = [
            'status' => $post->status,
            'content' => Str::limit($post->content, 100),
        ];

        if ($post->status === 'published' && $page->posts_count > 0) {
            $page->decrement('posts_count');
        }

        $post->delete();

        $this->logPageAction(
            $page,
            $user,
            'post.deleted',
            Post::class,
            $postId,
            $previousState,
            ['deleted' => true],
            $request
        );

        try {
            broadcast(new PagePostDeletedEvent(
                pageId: (int) $page->id,
                postId: (int) $postId,
                postsCount: (int) ($page->fresh()->posts_count ?? 0)
            ));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PagePostDeletedEvent: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }

    /**
     * Toggle pin status for a post.
     */
    public function togglePinPost(Request $request, string $slug, int $postId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.edit');

        $post = Post::where('page_id', $page->id)->where('id', $postId)->firstOrFail();
        $newPinned = ! $post->is_pinned;

        if ($newPinned) {
            Post::where('page_id', $page->id)->where('id', '!=', $postId)->update(['is_pinned' => false]);
        }

        $post->update(['is_pinned' => $newPinned]);

        $this->logPageAction(
            $page,
            $user,
            $newPinned ? 'post.pinned' : 'post.unpinned',
            Post::class,
            $post->id,
            ['is_pinned' => ! $newPinned],
            ['is_pinned' => $newPinned],
            $request
        );

        try {
            broadcast(new PagePostUpdatedEvent(
                pageId: (int) $page->id,
                postId: (int) $post->id,
                action: $newPinned ? 'pinned' : 'unpinned',
                postData: ['id' => $post->id, 'is_pinned' => $newPinned]
            ));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PagePostUpdatedEvent: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'is_pinned' => $newPinned,
            'message' => $newPinned ? 'Post pinned to top of page.' : 'Post unpinned.',
        ]);
    }

    /**
     * Update audience visibility for a post.
     */
    public function updatePostVisibility(Request $request, string $slug, int $postId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.edit');

        $post = Post::where('page_id', $page->id)->where('id', $postId)->firstOrFail();

        $validated = $request->validate([
            'audience' => ['required', 'string', 'in:public,followers,only_me'],
        ]);

        $prevAudience = $post->audience;
        $post->update(['audience' => $validated['audience']]);

        $this->logPageAction(
            $page,
            $user,
            'post.visibility_changed',
            Post::class,
            $post->id,
            ['audience' => $prevAudience],
            ['audience' => $validated['audience']],
            $request
        );

        try {
            broadcast(new PagePostUpdatedEvent(
                pageId: (int) $page->id,
                postId: (int) $post->id,
                action: 'visibility_changed',
                postData: ['id' => $post->id, 'audience' => $validated['audience']]
            ));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PagePostUpdatedEvent: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'audience' => $validated['audience'],
            'message' => 'Audience visibility updated.',
        ]);
    }

    /**
     * React to a post from the web dashboard or feed.
     */
    public function reactPost(Request $request, int $postId, ReactionService $reactionService): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $post = Post::findOrFail($postId);
        $type = $request->input('type', 'like');

        $result = $reactionService->react(
            user: $user,
            target: $post,
            type: $type
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => $result['reacted'] ? "Reacted with {$result['type']}." : 'Reaction removed.',
        ]);
    }

    /**
     * Get active and authorized page stories feed.
     */
    public function stories(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $isOwner = $user && ((int) $page->owner_id === (int) $user->id);
        $userRole = $user ? $page->getMemberRole($user->id) : null;
        $canManage = $isOwner || in_array($userRole, [
            Page::ROLE_OWNER,
            Page::ROLE_ADMIN,
            Page::ROLE_MANAGER,
            Page::ROLE_CONTENT_MANAGER,
            Page::ROLE_MODERATOR,
        ], true);

        $isFollowing = $user && PageFollower::where('page_id', $page->id)->where('user_id', $user->id)->exists();

        $query = Story::where('page_id', $page->id);

        if ($request->query('tab') === 'archived' || $request->boolean('include_all')) {
            if (! $canManage) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view archived stories.',
                ], 403);
            }
        } else {
            // Strictly enforce server-side expiration: never show expired stories as active
            $query->where('is_draft', false)
                ->where('is_expired', false)
                ->where('expires_at', '>', now())
                ->where('status', Story::STATUS_READY);

            if (! $canManage) {
                if ($isFollowing) {
                    $query->whereIn('privacy', ['public', 'followers']);
                } else {
                    $query->where('privacy', 'public');
                }
            }
        }

        $stories = $query->with(['user.profile', 'page', 'media', 'storyMedia.media'])
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stories->map(fn (Story $s) => $s->toResponseArray($user)),
        ]);
    }

    /**
     * Dedicated Story Publishing Workflow for Pages.
     */
    public function storeStory(Request $request, string $slug, StoryEnterpriseService $storyService): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.create');

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:text,media'],
            'content' => ['nullable', 'string', 'max:2000'],
            'background_color' => ['nullable', 'string', 'max:50'],
            'font_family' => ['nullable', 'string', 'max:50'],
            'privacy' => ['nullable', 'string', 'in:public,followers,only_me'],
            'media' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime,video/x-matroska', 'max:102400'],
            'media_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime,video/x-matroska', 'max:102400'],
        ]);

        $type = $validated['type'];
        $uploadedFile = $request->file('media') ?? $request->file('media_file');

        if ($type === 'text' && empty(trim((string) ($validated['content'] ?? '')))) {
            return response()->json([
                'success' => false,
                'message' => 'Text story must have content.',
                'errors' => ['content' => ['Story text content is required.']],
            ], 422);
        }

        if ($type === 'media' && ! $uploadedFile) {
            return response()->json([
                'success' => false,
                'message' => 'Media story must have an uploaded image or video.',
                'errors' => ['media' => ['Media file is required for media story.']],
            ], 422);
        }

        $story = DB::transaction(function () use ($user, $page, $type, $validated, $uploadedFile) {
            $publishedAt = now();
            $expiresAt = $publishedAt->copy()->addHours(24);

            $isVideo = false;
            $mediaRecord = null;
            $mediaPath = null;

            if ($uploadedFile) {
                $mime = $uploadedFile->getMimeType() ?: 'application/octet-stream';
                $isVideo = str_starts_with($mime, 'video/');
                $folder = $isVideo ? 'stories/videos' : 'stories/images';
                $mediaPath = $uploadedFile->store($folder, 'public');

                $mediaRecord = Media::create([
                    'user_id' => $user->id,
                    'collection' => 'stories',
                    'disk' => 'public',
                    'original_path' => $mediaPath,
                    'mime_type' => $mime,
                    'size' => $uploadedFile->getSize(),
                    'processing_status' => $isVideo ? 'processing' : 'ready',
                ]);
            }

            $storyRecord = Story::create([
                'user_id' => $user->id,
                'page_id' => $page->id,
                'type' => $type,
                'status' => ($isVideo && $mediaRecord?->processing_status === 'processing') ? Story::STATUS_PROCESSING : Story::STATUS_READY,
                'content' => $validated['content'] ?? null,
                'background_color' => $validated['background_color'] ?? '#1877F2',
                'font_family' => $validated['font_family'] ?? 'Hind Siliguri',
                'privacy' => $validated['privacy'] ?? 'public',
                'published_at' => $publishedAt,
                'expires_at' => $expiresAt,
                'is_expired' => false,
                'is_archived' => false,
                'is_draft' => false,
                'views_count' => 0,
                'reactions_count' => 0,
                'replies_count' => 0,
            ]);

            if ($mediaRecord) {
                $mediaRecord->update([
                    'mediable_type' => Story::class,
                    'mediable_id' => $storyRecord->id,
                ]);

                StoryMedia::create([
                    'story_id' => $storyRecord->id,
                    'media_id' => $mediaRecord->id,
                    'order' => 0,
                    'duration' => $isVideo ? 15 : 5,
                ]);

                if ($isVideo) {
                    dispatch(new ProcessMediaJob($mediaRecord->id));
                }
            }

            return $storyRecord;
        });

        $this->logPageAction(
            $page,
            $user,
            'story.created',
            Story::class,
            $story->id,
            null,
            ['type' => $type, 'privacy' => $story->privacy],
            $request
        );

        $freshStory = $story->fresh(['user.profile', 'page', 'media', 'storyMedia.media']);

        try {
            broadcast(new PageStoryPublishedEvent(
                pageId: (int) $page->id,
                storyData: $freshStory->toResponseArray($user)
            ));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PageStoryPublishedEvent: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Page story published successfully.',
            'story' => $freshStory->toResponseArray($user),
        ], 201);
    }

    /**
     * Record a view on a Page story.
     */
    public function recordStoryView(Request $request, string $slug, int $storyId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $story = Story::where('page_id', $page->id)->where('id', $storyId)->firstOrFail();

        // Server-side expiration check
        if ($story->isExpired()) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => 'This story has expired.',
            ], 410);
        }

        // Audience check
        $isOwner = $user && ((int) $page->owner_id === (int) $user->id);
        $userRole = $user ? $page->getMemberRole($user->id) : null;
        $canManage = $isOwner || in_array($userRole, [
            Page::ROLE_OWNER,
            Page::ROLE_ADMIN,
            Page::ROLE_MANAGER,
            Page::ROLE_CONTENT_MANAGER,
            Page::ROLE_MODERATOR,
        ], true);

        if ($story->privacy === 'only_me' && ! $canManage) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this story.',
            ], 403);
        }

        if ($story->privacy === 'followers') {
            $isFollowing = $user && PageFollower::where('page_id', $page->id)->where('user_id', $user->id)->exists();
            if (! $canManage && ! $isFollowing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This story is only visible to page followers.',
                ], 403);
            }
        }

        // Record real view if user is logged in
        if ($user) {
            $alreadyViewed = StoryView::where('story_id', $story->id)
                ->where('user_id', $user->id)
                ->exists();

            if (! $alreadyViewed) {
                StoryView::create([
                    'story_id' => $story->id,
                    'user_id' => $user->id,
                    'viewed_at' => now(),
                ]);

                $story->increment('views_count');
            }
        }

        return response()->json([
            'success' => true,
            'views_count' => $story->fresh()->views_count,
            'has_viewed' => true,
        ]);
    }

    /**
     * Get authorized list of viewers for a Page story.
     */
    public function storyViewers(Request $request, string $slug, int $storyId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.edit');

        $story = Story::where('page_id', $page->id)->where('id', $storyId)->firstOrFail();

        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));
        $viewers = StoryView::where('story_id', $story->id)
            ->with('user.profile')
            ->latest('viewed_at')
            ->paginate($perPage);

        $viewers->getCollection()->transform(function (StoryView $v) {
            return [
                'user' => [
                    'id' => $v->user?->id,
                    'name' => $v->user?->name,
                    'username' => $v->user?->username,
                    'avatar_url' => $v->user?->profile?->avatar_url ?? $v->user?->avatar_url,
                ],
                'viewed_at' => $v->viewed_at?->toIso8601String(),
                'formatted_time' => $v->viewed_at?->diffForHumans() ?? 'Just now',
            ];
        });

        return response()->json([
            'success' => true,
            'views_count' => $story->views_count,
            'data' => $viewers->items(),
            'meta' => [
                'current_page' => $viewers->currentPage(),
                'last_page' => $viewers->lastPage(),
                'total' => $viewers->total(),
            ],
        ]);
    }

    /**
     * Delete a Page story.
     */
    public function deleteStory(Request $request, string $slug, int $storyId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.delete');

        $story = Story::where('page_id', $page->id)->where('id', $storyId)->firstOrFail();

        $previous = [
            'type' => $story->type,
            'views_count' => $story->views_count,
        ];

        // Unlink/remove associated media
        foreach ($story->storyMedia as $sm) {
            if ($sm->media) {
                if ($sm->media->original_path) {
                    Storage::disk($sm->media->disk ?? 'public')->delete($sm->media->original_path);
                }
                $sm->media->delete();
            }
            $sm->delete();
        }

        $story->delete();

        $this->logPageAction(
            $page,
            $user,
            'story.deleted',
            Story::class,
            $storyId,
            $previous,
            ['deleted' => true],
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Story deleted successfully.',
        ]);
    }

    /**
     * Paginated Reels feed/tab for a Page.
     */
    public function reels(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $isOwner = $user && ((int) $page->owner_id === (int) $user->id);
        $userRole = $user ? $page->getMemberRole($user->id) : null;
        $canManage = $isOwner || in_array($userRole, [
            Page::ROLE_OWNER,
            Page::ROLE_ADMIN,
            Page::ROLE_MANAGER,
            Page::ROLE_CONTENT_MANAGER,
            Page::ROLE_MODERATOR,
        ], true);

        $isFollowing = $user && PageFollower::where('page_id', $page->id)->where('user_id', $user->id)->exists();

        $query = Reel::where('page_id', $page->id);

        if ($request->query('tab') === 'drafts') {
            if (! $canManage) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view drafts.',
                ], 403);
            }
            $query->where('is_draft', true);
        } else {
            $query->where('is_draft', false)
                ->where('status', Reel::STATUS_READY)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });

            if (! $canManage) {
                if ($isFollowing) {
                    $query->whereIn('privacy', ['public', 'followers']);
                } else {
                    $query->where('privacy', 'public');
                }
            }
        }

        $perPage = min(30, max(1, (int) $request->input('per_page', 12)));
        $paginator = $query->with(['media', 'page', 'user.profile'])
            ->latest('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(fn (Reel $r) => $r->toResponseArray($user));

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Dedicated Reel Publishing Workflow for Pages.
     */
    public function storeReel(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.create');

        $validated = $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime,video/x-matroska', 'max:102400'],
            'caption' => ['nullable', 'string', 'max:2000'],
            'audio_title' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,webp,jpg', 'max:10240'],
            'privacy' => ['nullable', 'string', 'in:public,followers,only_me'],
            'aspect_ratio' => ['nullable', 'string', 'in:9:16,1:1,16:9'],
            'location' => ['nullable', 'string', 'max:255'],
            'allow_comments' => ['nullable', 'boolean'],
            'is_draft' => ['nullable', 'boolean'],
        ]);

        $videoFile = $request->file('video');
        $videoPath = $videoFile->store('reels/videos', 'public');

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('reels/covers', 'public');
        }

        $isDraft = (bool) ($validated['is_draft'] ?? false);

        $reel = DB::transaction(function () use ($user, $page, $validated, $videoFile, $videoPath, $coverPath, $isDraft) {
            $reelRecord = Reel::create([
                'user_id' => $user->id,
                'page_id' => $page->id,
                'caption' => $validated['caption'] ?? null,
                'audio_title' => $validated['audio_title'] ?? ($page->name.' Original Audio'),
                'audio_artist' => $page->name,
                'cover_image_path' => $coverPath,
                'privacy' => $validated['privacy'] ?? 'public',
                'status' => Reel::STATUS_READY,
                'aspect_ratio' => $validated['aspect_ratio'] ?? '9:16',
                'published_at' => $isDraft ? null : now(),
                'expires_at' => null,
                'is_expired' => false,
                'is_draft' => $isDraft,
                'location' => $validated['location'] ?? null,
                'allow_comments' => (bool) ($validated['allow_comments'] ?? true),
                'views_count' => 0,
                'likes_count' => 0,
                'comments_count' => 0,
                'shares_count' => 0,
            ]);

            $media = Media::create([
                'user_id' => $user->id,
                'mediable_type' => Reel::class,
                'mediable_id' => $reelRecord->id,
                'collection' => 'reels',
                'disk' => 'public',
                'original_path' => $videoPath,
                'thumbnail_path' => $coverPath,
                'mime_type' => $videoFile->getMimeType() ?: 'video/mp4',
                'size' => $videoFile->getSize(),
                'processing_status' => 'ready',
            ]);

            ReelMedia::create([
                'reel_id' => $reelRecord->id,
                'media_id' => $media->id,
                'quality' => 'original',
                'video_path' => $videoPath,
                'thumbnail_path' => $coverPath,
                'mime_type' => $videoFile->getMimeType() ?: 'video/mp4',
                'size' => $videoFile->getSize(),
            ]);

            // Dispatch background media processing for poster/transcode
            dispatch(new ProcessMediaJob($media->id));

            return $reelRecord;
        });

        $this->logPageAction(
            $page,
            $user,
            $isDraft ? 'reel.draft_saved' : 'reel.created',
            Reel::class,
            $reel->id,
            null,
            ['caption' => Str::limit($reel->caption ?? '', 100), 'is_draft' => $isDraft],
            $request
        );

        $freshReel = $reel->fresh(['user.profile', 'page', 'media']);

        if (! $isDraft) {
            try {
                broadcast(new PageReelPublishedEvent(
                    pageId: (int) $page->id,
                    reelData: $freshReel->toResponseArray($user)
                ));
            } catch (\Throwable $e) {
                Log::warning('Could not broadcast PageReelPublishedEvent: '.$e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => $isDraft ? 'Reel saved as draft.' : 'Page reel published successfully.',
            'reel' => $freshReel->toResponseArray($user),
        ], 201);
    }

    /**
     * Record a view on a Page Reel.
     */
    public function recordReelView(Request $request, string $slug, int $reelId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $reel = Reel::where('page_id', $page->id)->where('id', $reelId)->firstOrFail();

        if ($reel->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'This reel has expired.',
                'is_expired' => true,
            ], 410);
        }

        // Audience check
        $isOwner = $user && ((int) $page->owner_id === (int) $user->id);
        $userRole = $user ? $page->getMemberRole($user->id) : null;
        $canManage = $isOwner || in_array($userRole, [
            Page::ROLE_OWNER,
            Page::ROLE_ADMIN,
            Page::ROLE_MANAGER,
            Page::ROLE_CONTENT_MANAGER,
            Page::ROLE_MODERATOR,
        ], true);

        if ($reel->privacy === 'only_me' && ! $canManage) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to view this reel.'], 403);
        }

        if ($reel->privacy === 'followers') {
            $isFollowing = $user && PageFollower::where('page_id', $page->id)->where('user_id', $user->id)->exists();
            if (! $canManage && ! $isFollowing) {
                return response()->json(['success' => false, 'message' => 'This reel is only visible to followers.'], 403);
            }
        }

        $watchTime = max(0.0, (float) $request->input('watch_time_seconds', 0.0));

        ReelView::create([
            'reel_id' => $reel->id,
            'user_id' => $user?->id,
            'ip_address' => $request->ip(),
            'watch_time_seconds' => $watchTime,
            'created_at' => now(),
        ]);

        $reel->increment('views_count');

        return response()->json([
            'success' => true,
            'views_count' => $reel->fresh()->views_count,
        ]);
    }

    /**
     * Update metadata for an existing Page Reel.
     */
    public function updateReel(Request $request, string $slug, int $reelId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.edit');

        $reel = Reel::where('page_id', $page->id)->where('id', $reelId)->firstOrFail();

        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:2000'],
            'privacy' => ['nullable', 'string', 'in:public,followers,only_me'],
            'allow_comments' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,webp,jpg', 'max:10240'],
        ]);

        $previous = [
            'caption' => $reel->caption,
            'privacy' => $reel->privacy,
            'allow_comments' => $reel->allow_comments,
        ];

        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('reels/covers', 'public');
            $reel->cover_image_path = $coverPath;
        }

        if (array_key_exists('caption', $validated)) {
            $reel->caption = $validated['caption'];
        }
        if (array_key_exists('privacy', $validated)) {
            $reel->privacy = $validated['privacy'];
        }
        if (array_key_exists('allow_comments', $validated)) {
            $reel->allow_comments = (bool) $validated['allow_comments'];
        }

        $reel->save();

        $this->logPageAction(
            $page,
            $user,
            'reel.updated',
            Reel::class,
            $reel->id,
            $previous,
            [
                'caption' => $reel->caption,
                'privacy' => $reel->privacy,
                'allow_comments' => $reel->allow_comments,
            ],
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Reel updated successfully.',
            'reel' => $reel->fresh(['user.profile', 'page', 'media'])->toResponseArray($user),
        ]);
    }

    /**
     * Publish a draft Reel.
     */
    public function publishReel(Request $request, string $slug, int $reelId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.edit');

        $reel = Reel::where('page_id', $page->id)->where('id', $reelId)->firstOrFail();

        $reel->update([
            'is_draft' => false,
            'status' => Reel::STATUS_READY,
            'published_at' => now(),
        ]);

        $this->logPageAction(
            $page,
            $user,
            'reel.published',
            Reel::class,
            $reel->id,
            ['is_draft' => true],
            ['is_draft' => false],
            $request
        );

        $freshReel = $reel->fresh(['user.profile', 'page', 'media']);

        try {
            broadcast(new PageReelPublishedEvent(
                pageId: (int) $page->id,
                reelData: $freshReel->toResponseArray($user)
            ));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PageReelPublishedEvent: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Reel published successfully.',
            'reel' => $freshReel->toResponseArray($user),
        ]);
    }

    /**
     * Delete a Page Reel.
     */
    public function deleteReel(Request $request, string $slug, int $reelId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.delete');

        $reel = Reel::where('page_id', $page->id)->where('id', $reelId)->firstOrFail();

        $previous = [
            'caption' => Str::limit($reel->caption ?? '', 100),
            'views_count' => $reel->views_count,
        ];

        // Delete associated files
        if ($reel->cover_image_path) {
            Storage::disk('public')->delete($reel->cover_image_path);
        }
        foreach ($reel->media as $m) {
            if ($m->video_path) {
                Storage::disk('public')->delete($m->video_path);
            }
            $m->delete();
        }

        $reel->delete();

        $this->logPageAction(
            $page,
            $user,
            'reel.deleted',
            Reel::class,
            $reelId,
            $previous,
            ['deleted' => true],
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Reel deleted successfully.',
        ]);
    }

    /**
     * React to a Page Reel.
     */
    public function reactReel(Request $request, string $slug, int $reelId, ReelEnterpriseService $reelService): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $page = Page::where('slug', $slug)->firstOrFail();
        $reel = Reel::where('page_id', $page->id)->where('id', $reelId)->firstOrFail();

        $type = $request->input('type', 'like');
        $result = $reelService->reactToReel($reel, $user, $type);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Fetch comments for a Page Reel.
     */
    public function reelComments(Request $request, string $slug, int $reelId, ReelEnterpriseService $reelService): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $reel = Reel::where('page_id', $page->id)->where('id', $reelId)->firstOrFail();

        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));
        $comments = $reelService->getComments($reel, $user, $perPage);

        return response()->json([
            'success' => true,
            'data' => $comments->items(),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * Add comment to a Page Reel.
     */
    public function storeReelComment(Request $request, string $slug, int $reelId, ReelEnterpriseService $reelService): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $page = Page::where('slug', $slug)->firstOrFail();
        $reel = Reel::where('page_id', $page->id)->where('id', $reelId)->firstOrFail();

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:reel_comments,id'],
        ]);

        $comment = $reelService->addComment($reel, $user, $validated['comment'], $validated['parent_id'] ?? null);

        return response()->json([
            'success' => true,
            'comment' => $comment->toResponseArray($user),
        ], 201);
    }

    /**
     * Delete a comment on a Page Reel.
     */
    public function deleteReelComment(Request $request, string $slug, int $commentId, ReelEnterpriseService $reelService): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $page = Page::where('slug', $slug)->firstOrFail();
        $comment = ReelComment::with('reel')->where('id', $commentId)->firstOrFail();

        if ($comment->reel->page_id !== $page->id) {
            abort(404);
        }

        $isOwner = (int) $page->owner_id === (int) $user->id;
        $userRole = $page->getMemberRole($user->id);
        $canModerate = $isOwner || in_array($userRole, [Page::ROLE_OWNER, Page::ROLE_ADMIN, Page::ROLE_MANAGER, Page::ROLE_MODERATOR], true);

        if ($comment->user_id !== $user->id && ! $canModerate) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to delete this comment.'], 403);
        }

        $deleted = DB::transaction(function () use ($comment) {
            $reel = $comment->reel;
            $res = $comment->delete();
            $reel->decrement('comments_count');

            return $res;
        });

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.',
        ]);
    }

    /**
     * Share a Page Reel.
     */
    public function shareReel(Request $request, string $slug, int $reelId, ReelEnterpriseService $reelService): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $reel = Reel::where('page_id', $page->id)->where('id', $reelId)->firstOrFail();

        $platform = $request->input('platform', 'internal');
        $result = $reelService->recordShare($reel, $user, $platform);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Functional Page Videos tab with playback, pagination, metadata, and management actions.
     */
    public function videos(Request $request, string $slug): JsonResponse|View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $isOwner = $user && ((int) $page->owner_id === (int) $user->id);
        $userRole = $user ? $page->getMemberRole($user->id) : null;
        $canManage = $isOwner || in_array($userRole, [
            Page::ROLE_OWNER,
            Page::ROLE_ADMIN,
            Page::ROLE_MANAGER,
            Page::ROLE_CONTENT_MANAGER,
            Page::ROLE_MODERATOR,
        ], true);

        $isFollowing = $user && PageFollower::where('page_id', $page->id)->where('user_id', $user->id)->exists();

        $filter = $request->query('filter', 'all'); // 'all', 'posts', 'reels'
        $items = collect();

        // 1. Video Posts
        if ($filter === 'all' || $filter === 'posts') {
            $postQuery = Post::where('page_id', $page->id)
                ->where(function ($q) {
                    $q->where('type', 'video')
                        ->orWhereHas('media', fn ($mq) => $mq->where('mime_type', 'like', 'video/%'));
                })
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'published');
                });

            if (! $canManage) {
                if ($isFollowing) {
                    $postQuery->whereIn('audience', ['public', 'followers']);
                } else {
                    $postQuery->where('audience', 'public');
                }
            }

            $posts = $postQuery->with(['media', 'user.profile'])->latest('id')->get();

            foreach ($posts as $post) {
                $vidMedia = $post->media->first(fn ($m) => str_starts_with($m->mime_type ?? '', 'video/'));
                if (! $vidMedia) {
                    continue;
                }

                $playbackUrl = $vidMedia->getVariantUrl('original') ?: ($vidMedia->url ?: asset('storage/'.$vidMedia->original_path));
                $thumbUrl = $vidMedia->thumbnail_url ?: asset('storage/'.($vidMedia->thumbnail_path ?? $vidMedia->original_path));

                $items->push([
                    'id' => $post->id,
                    'source_type' => 'post',
                    'title' => Str::limit($post->content, 60),
                    'caption' => $post->content,
                    'video_url' => $playbackUrl,
                    'thumbnail_url' => $thumbUrl,
                    'duration' => (float) ($vidMedia->metadata['duration'] ?? 0.0),
                    'formatted_duration' => isset($vidMedia->metadata['duration']) ? gmdate('i:s', (int) $vidMedia->metadata['duration']) : 'Video',
                    'views_count' => (int) ($vidMedia->metadata['views_count'] ?? ($post->media_meta['views_count'] ?? ($post->views_count ?? 0))),
                    'likes_count' => (int) $post->likes_count,
                    'comments_count' => (int) $post->comments_count,
                    'shares_count' => (int) $post->shares_count,
                    'audience' => $post->audience ?? 'public',
                    'is_pinned' => (bool) $post->is_pinned,
                    'created_at' => $post->created_at?->toIso8601String(),
                    'formatted_time' => $post->created_at?->diffForHumans() ?? 'Recently',
                    'can_manage' => $canManage,
                ]);
            }
        }

        // 2. Page Reels
        if ($filter === 'all' || $filter === 'reels') {
            $reelQuery = Reel::where('page_id', $page->id)
                ->where('status', Reel::STATUS_READY)
                ->where('is_draft', false)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });

            if (! $canManage) {
                if ($isFollowing) {
                    $reelQuery->whereIn('privacy', ['public', 'followers']);
                } else {
                    $reelQuery->where('privacy', 'public');
                }
            }

            $reels = $reelQuery->with(['media', 'user.profile'])->latest('id')->get();

            foreach ($reels as $reel) {
                $primary = $reel->media->firstWhere('quality', 'original') ?? $reel->media->first();
                $playbackUrl = $primary?->video_url ?? ($reel->video_url ?? ($primary?->original_path ? asset('storage/'.$primary->original_path) : null));
                $coverUrl = $reel->cover_image_path
                    ? (str_starts_with($reel->cover_image_path, 'http') ? $reel->cover_image_path : asset('storage/'.$reel->cover_image_path))
                    : ($primary?->thumbnail_url ?? null);

                $items->push([
                    'id' => $reel->id,
                    'source_type' => 'reel',
                    'title' => Str::limit($reel->caption ?? 'Reel', 60),
                    'caption' => $reel->caption,
                    'video_url' => $playbackUrl,
                    'thumbnail_url' => $coverUrl,
                    'duration' => (float) ($reel->duration ?? 0.0),
                    'formatted_duration' => $reel->duration ? gmdate('i:s', (int) $reel->duration) : '0:30',
                    'views_count' => (int) $reel->views_count,
                    'likes_count' => (int) $reel->likes_count,
                    'comments_count' => (int) $reel->comments_count,
                    'shares_count' => (int) $reel->shares_count,
                    'audience' => $reel->privacy,
                    'is_pinned' => false,
                    'created_at' => $reel->created_at?->toIso8601String(),
                    'formatted_time' => $reel->created_at?->diffForHumans() ?? 'Recently',
                    'can_manage' => $canManage,
                ]);
            }
        }

        // Sort items by created_at descending
        $sorted = $items->sortByDesc('created_at')->values();

        $pageNumber = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 12)));
        $total = $sorted->count();
        $sliced = $sorted->forPage($pageNumber, $perPage)->values();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $sliced,
                'meta' => [
                    'current_page' => $pageNumber,
                    'last_page' => (int) ceil($total / $perPage) ?: 1,
                    'total' => $total,
                    'per_page' => $perPage,
                ],
            ]);
        }

        return redirect()->route('pages.show', ['slug' => $page->slug, 'tab' => 'videos']);
    }

    /**
     * Create a new broadcast session for the Page.
     * Uses short-lived, scoped broadcast credentials. Never exposes server secrets or unrestricted streaming keys in frontend code.
     */
    public function createLiveStream(Request $request, string $slug, LiveStreamingService $streamingService, CallingService $callingService): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.create');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'privacy' => ['nullable', 'string', 'in:public,friends,followers,only_me'],
            'recording_enabled' => ['nullable', 'boolean'],
            'comments_enabled' => ['nullable', 'boolean'],
            'reactions_enabled' => ['nullable', 'boolean'],
            'sharing_enabled' => ['nullable', 'boolean'],
        ]);

        $privacy = $validated['privacy'] ?? LiveStream::PRIVACY_PUBLIC;
        if ($privacy === 'followers') {
            $privacy = LiveStream::PRIVACY_FRIENDS;
        }

        $stream = $streamingService->createChannel(
            user: $user,
            title: $validated['title'],
            description: $validated['description'] ?? null,
            options: [
                'page_id' => $page->id,
                'privacy' => $privacy,
                'recording_enabled' => (bool) ($validated['recording_enabled'] ?? true),
                'comments_enabled' => (bool) ($validated['comments_enabled'] ?? true),
                'reactions_enabled' => (bool) ($validated['reactions_enabled'] ?? true),
                'sharing_enabled' => (bool) ($validated['sharing_enabled'] ?? true),
            ]
        );

        $gateway = $streamingService->getGateway();
        $hostToken = $stream->sfu_host_token ?: $gateway->generateHostToken($stream, $user);

        $this->logPageAction(
            $page,
            $user,
            'live.session_created',
            LiveStream::class,
            $stream->id,
            null,
            ['title' => $stream->title, 'privacy' => $stream->privacy],
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Broadcast session created. Prepare devices and connect to begin broadcasting.',
            'stream' => [
                'id' => $stream->id,
                'page_id' => $page->id,
                'channel_id' => $stream->channel_id,
                'title' => $stream->title,
                'description' => $stream->description,
                'status' => $stream->status,
                'privacy' => $validated['privacy'] ?? 'public',
                'recording_enabled' => (bool) $stream->recording_enabled,
            ],
            'sfu_room_id' => $stream->sfu_room_id,
            'sfu_endpoint' => $gateway->getSfuEndpoint(),
            'sfu_provider' => $stream->sfu_provider ?: $gateway->getProvider(),
            'host_token' => $hostToken,
            'ice_servers' => $callingService->getIceServers($user),
        ], 201);
    }

    /**
     * Broadcaster confirms track publication from SFU / transport.
     * Publish a Page Live entry only when the broadcast is genuinely active.
     */
    public function publishLiveStream(Request $request, string $slug, int $streamId, LiveStreamingService $streamingService): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.create');

        $stream = LiveStream::where('page_id', $page->id)->where('id', $streamId)->firstOrFail();

        $validated = $request->validate([
            'host_token' => ['required', 'string'],
            'track_meta' => ['nullable', 'array'],
        ]);

        try {
            $streamingService->confirmSfuPublication(
                stream: $stream,
                hostToken: $validated['host_token'],
                trackMeta: $validated['track_meta'] ?? []
            );

            $this->logPageAction(
                $page,
                $user,
                'live.started',
                LiveStream::class,
                $stream->id,
                ['status' => LiveStream::STATUS_READY],
                ['status' => LiveStream::STATUS_LIVE, 'started_at' => now()],
                $request
            );

            $freshStream = $stream->fresh();
            try {
                broadcast(new PageLiveStreamStateChangedEvent(
                    pageId: (int) $page->id,
                    streamId: (int) $stream->id,
                    channelId: (string) $stream->channel_id,
                    status: LiveStream::STATUS_LIVE,
                    streamData: [
                        'id' => $stream->id,
                        'channel_id' => $stream->channel_id,
                        'title' => $stream->title,
                        'status' => LiveStream::STATUS_LIVE,
                        'started_at' => $freshStream->started_at?->toIso8601String(),
                        'viewers_count' => (int) $stream->viewers_count,
                        'playback_url' => $stream->playback_url,
                    ]
                ));
            } catch (\Throwable $e) {
                Log::warning('Could not broadcast PageLiveStreamStateChangedEvent: '.$e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Page Live broadcast is now officially active and streaming.',
                'stream' => [
                    'id' => $stream->id,
                    'channel_id' => $stream->channel_id,
                    'status' => LiveStream::STATUS_LIVE,
                    'started_at' => $freshStream->started_at?->toIso8601String(),
                    'viewers_count' => $stream->viewers_count,
                    'playback_url' => $stream->playback_url,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Heartbeat handler for live stream, updating broadcaster connection and health stats.
     */
    public function heartbeatLiveStream(Request $request, string $slug, int $streamId, LiveStreamingService $streamingService): JsonResponse
    {
        $page = Page::where('slug', $slug)->firstOrFail();
        $stream = LiveStream::where('page_id', $page->id)->where('id', $streamId)->firstOrFail();

        $token = (string) ($request->input('host_token') ?: $request->header('X-Host-Token'));
        if ($token && $stream->sfu_host_token && ! hash_equals($stream->sfu_host_token, $token)) {
            $verified = $streamingService->getGateway()->verifyToken($token);
            if (! $verified || ($verified['stream_id'] ?? 0) !== $stream->id) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired host token.'], 403);
            }
        }

        $now = now();
        $healthStats = $request->input('health_stats', []);

        $stream->update([
            'last_heartbeat_at' => $now,
            'health_stats' => array_merge($stream->health_stats ?? [], is_array($healthStats) ? $healthStats : []),
        ]);

        return response()->json([
            'success' => true,
            'active' => $stream->status === LiveStream::STATUS_LIVE,
            'status' => $stream->status,
            'viewers_count' => (int) $stream->viewers_count,
            'viewer_count' => (int) $stream->viewers_count,
            'last_heartbeat_at' => $now->toIso8601String(),
        ]);
    }

    /**
     * Retrieve the currently genuinely active live broadcast for the Page, if any.
     */
    public function activeLiveStream(Request $request, string $slug, LiveStreamingService $streamingService, CallingService $callingService): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        // Must be genuinely live and within recent heartbeat window (90s)
        $stream = LiveStream::where('page_id', $page->id)
            ->where('status', LiveStream::STATUS_LIVE)
            ->where(function ($q) {
                $q->whereNull('last_heartbeat_at')
                    ->orWhere('last_heartbeat_at', '>', now()->subSeconds(90));
            })
            ->latest('started_at')
            ->first();

        if (! $stream) {
            return response()->json([
                'success' => true,
                'active' => false,
                'is_live' => false,
                'live_stream' => null,
                'stream' => null,
            ]);
        }

        if (! $stream->canView($user)) {
            return response()->json([
                'success' => false,
                'active' => true,
                'is_live' => true,
                'message' => 'This live stream is private or restricted to followers.',
            ], 403);
        }

        $token = $streamingService->issueViewerToken($stream, $user);
        $gateway = $streamingService->getGateway();

        $streamData = [
            'id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'title' => $stream->title,
            'description' => $stream->description,
            'status' => $stream->status,
            'started_at' => $stream->started_at?->toIso8601String(),
            'formatted_time' => $stream->started_at?->diffForHumans() ?? 'Just now',
            'viewers_count' => (int) $stream->viewers_count,
            'comments_enabled' => (bool) $stream->comments_enabled,
            'reactions_enabled' => (bool) $stream->reactions_enabled,
            'playback_url' => $stream->playback_url,
            'sfu_room_id' => $stream->sfu_room_id,
            'sfu_endpoint' => $gateway->getSfuEndpoint(),
            'viewer_token' => $token,
            'ice_servers' => $callingService->getIceServers($user),
        ];

        return response()->json([
            'success' => true,
            'active' => true,
            'is_live' => true,
            'stream' => $streamData,
            'live_stream' => $streamData,
        ]);
    }

    /**
     * Retrieve an authorized short-lived viewer token for a Page live stream.
     */
    public function viewerTokenLiveStream(Request $request, string $slug, int $streamId, LiveStreamingService $streamingService, CallingService $callingService): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $stream = LiveStream::where('page_id', $page->id)->where('id', $streamId)->firstOrFail();

        if (! $stream->canView($user)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Unauthorized: This live stream has audience restrictions.',
            ], 403);
        }

        $token = $streamingService->issueViewerToken($stream, $user);
        $gateway = $streamingService->getGateway();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'stream_id' => $stream->id,
            'viewer_token' => $token,
            'token' => $token,
            'room_id' => $stream->sfu_room_id,
            'sfu_endpoint' => $gateway->getSfuEndpoint(),
            'ice_servers' => $callingService->getIceServers($user),
        ]);
    }

    /**
     * Join stream as viewer and increment real active viewer metrics.
     */
    public function joinLiveStream(Request $request, string $slug, int $streamId, LiveStreamingService $streamingService): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $stream = LiveStream::where('page_id', $page->id)->where('id', $streamId)->firstOrFail();

        if (! $stream->canView($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to join this live stream.'], 403);
        }

        $sessionId = (string) $request->input('session_id', Str::random(32));
        $stats = $streamingService->joinViewer($stream, $user, $sessionId);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'viewers_count' => $stats['viewers_count'] ?? 1,
        ]);
    }

    /**
     * Leave stream as viewer and update real active viewer count.
     */
    public function leaveLiveStream(Request $request, string $slug, int $streamId, LiveStreamingService $streamingService): JsonResponse
    {
        $page = Page::where('slug', $slug)->firstOrFail();
        $stream = LiveStream::where('page_id', $page->id)->where('id', $streamId)->firstOrFail();

        $user = $this->resolveUser($request);
        $sessionId = (string) $request->input('session_id', '');
        $stats = $streamingService->leaveViewer($stream, $user, $sessionId);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'viewers_count' => $stats['viewers_count'] ?? 0,
        ]);
    }

    /**
     * End live broadcast session, invalidate publishing credentials, and close SFU room.
     */
    public function endLiveStream(Request $request, string $slug, int $streamId, LiveStreamingService $streamingService): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.edit');

        $stream = LiveStream::where('page_id', $page->id)->where('id', $streamId)->firstOrFail();

        $ended = $streamingService->endStream($stream, $user);

        // Invalidate active publishing credentials
        $ended->update(['sfu_host_token' => null]);

        $this->logPageAction(
            $page,
            $user,
            'live.ended',
            LiveStream::class,
            $ended->id,
            ['status' => LiveStream::STATUS_LIVE],
            [
                'status' => LiveStream::STATUS_ENDED,
                'duration' => $ended->duration,
                'recording_status' => $ended->recording_status,
            ],
            $request
        );

        $freshEnded = $ended->fresh();
        try {
            broadcast(new PageLiveStreamStateChangedEvent(
                pageId: (int) $page->id,
                streamId: (int) $ended->id,
                channelId: (string) $ended->channel_id,
                status: LiveStream::STATUS_ENDED,
                streamData: [
                    'id' => $ended->id,
                    'channel_id' => $ended->channel_id,
                    'status' => LiveStream::STATUS_ENDED,
                    'ended_at' => $freshEnded->ended_at?->toIso8601String(),
                    'viewers_count' => 0,
                ]
            ));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PageLiveStreamStateChangedEvent: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Broadcast ended successfully. Active publishing credentials have been invalidated.',
            'stream' => [
                'id' => $ended->id,
                'channel_id' => $ended->channel_id,
                'status' => LiveStream::STATUS_ENDED,
                'duration' => $ended->duration,
                'recording_status' => $ended->recording_status,
                'has_recording' => $ended->hasReplay(),
                'recording_url' => $ended->recording_url,
            ],
        ]);
    }

    /**
     * Upload and persist recording for an ended live stream, creating a permanent Page video post.
     */
    public function uploadLiveRecording(Request $request, string $slug, int $streamId, LiveStreamingService $streamingService): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.create');

        $stream = LiveStream::where('page_id', $page->id)->where('id', $streamId)->firstOrFail();

        $file = $request->file('video') ?: ($request->file('replay_file') ?: $request->file('recording_file'));
        $allowedMimes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-matroska'];
        if (! $file || ! $file->isValid() || ! in_array($file->getMimeType(), $allowedMimes, true)) {
            $stream->update([
                'recording_status' => LiveStream::RECORDING_STATUS_FAILED,
                'error_message' => 'Uploaded recording file is missing, corrupted, or not a supported video format.',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Recording upload failed: media file is missing or invalid video format.',
                'errors' => ['video' => ['A valid video file (MP4, WebM, QuickTime, MKV) is required.']],
            ], 422);
        }

        $validated = $request->validate([
            'video' => ['nullable', 'file', 'max:512000'],
            'replay_file' => ['nullable', 'file', 'max:512000'],
            'recording_file' => ['nullable', 'file', 'max:512000'],
            'thumbnail_file' => ['nullable', 'file', 'image', 'max:10240'],
        ]);

        try {
            $result = $streamingService->uploadReplay(
                stream: $stream,
                user: $user,
                file: $file,
                thumbnailFile: $request->file('thumbnail_file')
            );

            $this->logPageAction(
                $page,
                $user,
                'live.recording_persisted',
                LiveStream::class,
                $stream->id,
                null,
                ['post_id' => $result['post_id'] ?? null],
                $request
            );

            return response()->json([
                'success' => true,
                'message' => 'Recording safely processed and published as Page video.',
                'recording_url' => $result['recording_url'] ?? null,
                'post_id' => $result['post_id'] ?? null,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            $stream->update([
                'recording_status' => LiveStream::RECORDING_STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to persist recording: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Dedicated Page Video upload and publishing workflow.
     */
    public function storeVideo(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.create');

        $file = $request->file('video') ?: $request->file('video_file');
        if (! $file || ! $file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'The video file is required and must be valid.',
                'errors' => ['video' => ['A valid video file is required.']],
            ], 422);
        }

        $validated = $request->validate([
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime,video/x-matroska', 'max:102400'],
            'video_file' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime,video/x-matroska', 'max:102400'],
            'thumbnail' => ['nullable', 'file', 'image', 'mimes:jpeg,png,webp,jpg', 'max:10240'],
            'caption' => ['required', 'string', 'max:5000'],
            'audience' => ['nullable', 'string', 'in:public,followers,only_me'],
            'allow_comments' => ['nullable'],
        ]);

        $videoFile = $file;
        $videoPath = $videoFile->store('posts/videos', 'public');

        $thumbPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbPath = $request->file('thumbnail')->store('posts/thumbnails', 'public');
        }

        [$post, $media] = DB::transaction(function () use ($user, $page, $validated, $videoFile, $videoPath, $thumbPath) {
            $postRecord = Post::create([
                'user_id' => $user->id,
                'page_id' => $page->id,
                'content' => $validated['caption'],
                'audience' => $validated['audience'] ?? 'public',
                'type' => 'video',
                'status' => 'published',
                'comments_disabled' => ! ($validated['allow_comments'] ?? true),
                'likes_count' => 0,
                'comments_count' => 0,
                'shares_count' => 0,
            ]);

            $media = Media::create([
                'user_id' => $user->id,
                'mediable_type' => Post::class,
                'mediable_id' => $postRecord->id,
                'collection' => 'posts',
                'disk' => 'public',
                'original_path' => $videoPath,
                'thumbnail_path' => $thumbPath,
                'mime_type' => $videoFile->getMimeType() ?: 'video/mp4',
                'size' => $videoFile->getSize(),
                'processing_status' => 'processing',
                'metadata' => [
                    'original_filename' => $videoFile->getClientOriginalName(),
                ],
            ]);

            dispatch(new ProcessMediaJob($media->id));

            return [$postRecord, $media];
        });

        $page->increment('posts_count');

        $this->logPageAction(
            $page,
            $user,
            'video.published',
            Post::class,
            $post->id,
            null,
            ['caption' => Str::limit($post->content, 100)],
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Video uploaded and published to Page successfully.',
            'post' => $post->fresh(['media', 'page', 'user.profile'])->toResponseArray($user),
            'media' => [
                'id' => $media->id,
                'url' => asset('storage/'.$videoPath),
            ],
            'video_url' => asset('storage/'.$videoPath),
        ], 201);
    }

    /**
     * Permission-aware Video Sharing.
     */
    public function shareVideo(Request $request, string $slug, int $videoId): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Please login to share videos.'], 401);
        }

        $page = Page::where('slug', $slug)->firstOrFail();

        // Find video post
        $post = Post::where('page_id', $page->id)
            ->where('id', $videoId)
            ->firstOrFail();

        $isOwner = (int) $page->owner_id === (int) $user->id;
        $userRole = $page->getMemberRole($user->id);
        $canManage = $isOwner || in_array($userRole, [
            Page::ROLE_OWNER,
            Page::ROLE_ADMIN,
            Page::ROLE_MANAGER,
            Page::ROLE_CONTENT_MANAGER,
            Page::ROLE_MODERATOR,
        ], true);
        $isFollowing = PageFollower::where('page_id', $page->id)->where('user_id', $user->id)->exists();

        // Enforce permission-aware sharing
        if ($post->audience === 'only_me' && ! $canManage) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to share private video.',
            ], 403);
        }

        if ($post->audience === 'followers' && ! $canManage && ! $isFollowing) {
            return response()->json([
                'success' => false,
                'message' => 'Only page followers are permitted to share this video.',
            ], 403);
        }

        $caption = (string) $request->input('caption', 'Shared video from '.$page->name);

        $sharedPost = DB::transaction(function () use ($user, $post, $caption) {
            $sp = Post::create([
                'user_id' => $user->id,
                'content' => $caption,
                'audience' => 'public',
                'type' => 'video',
                'status' => 'published',
                'media_meta' => [
                    'shared_from_post_id' => $post->id,
                    'original_author' => $post->page?->name ?? $post->user?->name,
                ],
            ]);

            PostShare::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);

            $post->increment('shares_count');

            return $sp;
        });

        return response()->json([
            'success' => true,
            'message' => 'Video successfully shared to your timeline.',
            'shares_count' => $post->fresh()->shares_count,
            'share_post' => [
                'id' => $sharedPost->id,
                'user_id' => $sharedPost->user_id,
                'content' => $sharedPost->content,
                'original_post_id' => $post->id,
            ],
            'post' => $sharedPost->fresh(['user.profile']),
        ], 201);
    }

    /**
     * Add a comment or reply to a post.
     */
    public function storeComment(Request $request, int $id, CommentService $commentService, PostService $postService): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $post = Post::findOrFail($id);

        if (! $postService->canViewPost($post, $user)) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to view or comment on this post.'], 403);
        }

        if ($post->comments_disabled) {
            return response()->json(['success' => false, 'message' => 'Comments are disabled for this post.'], 403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        try {
            $comment = $commentService->createComment(
                user: $user,
                postId: $post->id,
                body: $validated['body'],
                parentId: $validated['parent_id'] ?? null
            );
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'comment' => $comment->toResponseArray($user),
            'comments_count' => $post->fresh()->comments_count,
        ], 201);
    }

    /**
     * Update an existing comment.
     */
    public function updateComment(Request $request, int $id, CommentService $commentService): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $comment = Comment::findOrFail($id);
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $updated = $commentService->updateComment($user, $comment, $validated['body']);

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully.',
            'comment' => $updated->toResponseArray($user),
        ]);
    }

    /**
     * Delete an existing comment.
     */
    public function deleteComment(Request $request, int $id, CommentService $commentService): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $comment = Comment::findOrFail($id);
        $commentService->deleteComment($user, $comment);

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.',
        ]);
    }

    /**
     * Share a post and increment share count.
     */
    public function sharePost(Request $request, int $id, PostService $postService): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $post = Post::findOrFail($id);
        if (! $postService->canViewPost($post, $user)) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to share this post.'], 403);
        }

        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:1000'],
        ]);

        $share = PostShare::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'caption' => $validated['caption'] ?? null,
        ]);

        $post->increment('shares_count');

        return response()->json([
            'success' => true,
            'message' => 'Post shared successfully.',
            'share' => $share,
            'shares_count' => $post->fresh()->shares_count,
        ], 201);
    }

    /**
     * Report a post to the moderation team.
     */
    public function reportPost(Request $request, int $id): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $post = Post::findOrFail($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:spam,harassment,inappropriate,misinformation,copyright,violence,hate_speech,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $report = Report::create([
            'reporter_id' => $user->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => Report::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully. Our moderation team will review it.',
            'report_id' => $report->id,
        ], 201);
    }

    /**
     * Paginated JSON feed for the page.
     */
    public function feed(Request $request, string $slug, PostService $postService): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $isOwner = $user && ((int) $page->owner_id === (int) $user->id);
        $role = $user ? $page->getMemberRole($user->id) : null;
        $isManager = $isOwner || in_array($role, [Page::ROLE_OWNER, Page::ROLE_ADMIN, Page::ROLE_MANAGER, Page::ROLE_EDITOR, Page::ROLE_CONTENT_MANAGER, Page::ROLE_MODERATOR], true);

        $query = Post::where('page_id', $page->id)
            ->with(['author.profile', 'media', 'reactions', 'page']);

        $statusFilter = $request->query('status');
        if ($isManager) {
            if ($statusFilter && in_array($statusFilter, ['published', 'draft', 'scheduled'], true)) {
                $query->where('status', $statusFilter);
            }
        } else {
            $query->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'published');
            });
        }

        $perPage = min((int) $request->query('per_page', 15), 50);

        $posts = $query->orderByDesc('is_pinned')->latest('id')->paginate($perPage);

        $items = collect($posts->items())
            ->filter(fn (Post $p) => $postService->canViewPost($p, $user))
            ->map(fn (Post $p) => $p->toResponseArray($user))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'has_more' => $posts->hasMorePages(),
            ],
        ]);
    }

    /**
     * Show single post (permalink or JSON details).
     */
    public function showPost(Request $request, string $slug, int $postId, PostService $postService): JsonResponse|RedirectResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $post = Post::where('page_id', $page->id)->where('id', $postId)->firstOrFail();

        if (! $postService->canViewPost($post, $user)) {
            abort(403, 'You do not have permission to view this post.');
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'post' => $post->fresh(['author.profile', 'media', 'reactions', 'page', 'comments'])->toResponseArray($user),
            ]);
        }

        return redirect()->route('pages.show', ['slug' => $page->slug, 'highlight' => $post->id]);
    }

    /**
     * Store a newly created page via web request.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'username' => ['nullable', 'string', 'max:60'],
            'slug' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'sub_category' => ['nullable', 'string', 'max:50'],
            'page_type_id' => ['nullable', 'integer', 'exists:page_types,id'],
            'page_category_id' => ['nullable', 'integer', 'exists:page_categories,id'],
            'page_subcategory_id' => ['nullable', 'integer', 'exists:page_subcategories,id'],
            'short_description' => ['nullable', 'string', 'max:300'],
            'bio' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:3000'],
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:10240'],
            'cover_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:15360'],
            'avatar_url' => ['nullable', 'string'],
            'cover_image_url' => ['nullable', 'string'],
            'website' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:60'],
            'country' => ['nullable', 'string', 'max:60'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'business_hours' => ['nullable', 'array'],
            'cta_type' => ['nullable', 'string', 'max:40'],
            'cta_url' => ['nullable', 'string', 'url'],
            'visibility' => ['nullable', 'string', 'in:public,friends,private,unlisted,restricted'],
            'settings' => ['nullable', 'array'],
            'privacy_settings' => ['nullable', 'array'],
        ]);

        $newlyUploadedPaths = [];
        try {
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('pages/avatars', 'public');
                $validated['avatar_url'] = '/storage/'.$path;
                $newlyUploadedPaths[] = $path;
            }

            if ($request->hasFile('cover_image')) {
                $path = $request->file('cover_image')->store('pages/covers', 'public');
                $validated['cover_image_url'] = '/storage/'.$path;
                $newlyUploadedPaths[] = $path;
            }

            $page = app(PageRegistrationService::class)->registerPage($user, $validated);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Page created successfully.',
                    'data' => $page->toResponseArray($user),
                ], 201);
            }

            return redirect()->route('pages.show', $page->slug)->with('success', 'Page created successfully.');
        } catch (\Throwable $e) {
            foreach ($newlyUploadedPaths as $p) {
                if (Storage::disk('public')->exists($p)) {
                    Storage::disk('public')->delete($p);
                }
            }

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }
    }

    /**
     * Update page general details (name, category, bio, contact, website, location).
     */
    public function updateDetails(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->orWhere('id', is_numeric($slug) ? (int) $slug : 0)->firstOrFail();
        $this->authorizePageManager($page, $user, 'page.edit');

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'username' => ['nullable', 'string', 'max:60'],
            'category' => ['required', 'string', 'max:100'],
            'sub_category' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'short_description' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:3000'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'business_hours' => ['nullable'],
            'cta_type' => ['nullable', 'string', 'max:50'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'visibility' => ['nullable', 'string', 'in:public,unlisted,private'],
        ]);

        if (array_key_exists('username', $validated) && ! empty($validated['username'])) {
            $cleanUser = Str::slug($validated['username']);
            $existing = Page::where('username', $cleanUser)->where('id', '!=', $page->id)->exists();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This username is already taken.',
                    'errors' => ['username' => ['This username is already taken.']],
                ], 422);
            }
            $validated['username'] = $cleanUser;
        }

        $page->update($validated);
        $fresh = $page->fresh();

        try {
            broadcast(new PageProfileUpdatedEvent((int) $page->id, $fresh->toArray(), (int) $user->id));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PageProfileUpdatedEvent: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Page information updated successfully.',
            'page' => $fresh,
            'data' => $fresh->toResponseArray($user),
        ]);
    }

    /**
     * Upload Avatar or Cover Photo for the page with safe media cleanup.
     */
    public function uploadBranding(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->orWhere('id', is_numeric($slug) ? (int) $slug : 0)->firstOrFail();
        $this->authorizePageManager($page, $user, 'page.edit');

        $request->validate([
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
            'cover_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:15360'],
        ]);

        $type = null;
        $url = null;

        if ($request->hasFile('avatar')) {
            $oldAvatar = $page->avatar_url;
            $path = $request->file('avatar')->store('pages/avatars', 'public');
            $url = '/storage/'.$path;
            $page->update(['avatar_url' => $url]);
            $this->pageService->cleanupUnusedMedia($oldAvatar, $page->id);
            $type = 'avatar';
        } elseif ($request->hasFile('cover_image')) {
            $oldCover = $page->cover_image_url;
            $path = $request->file('cover_image')->store('pages/covers', 'public');
            $url = '/storage/'.$path;
            $page->update(['cover_image_url' => $url]);
            $this->pageService->cleanupUnusedMedia($oldCover, $page->id);
            $type = 'cover';
        }

        if (! $type) {
            return response()->json([
                'success' => false,
                'message' => 'No image file uploaded.',
            ], 422);
        }

        $fresh = $page->fresh();
        try {
            broadcast(new PageProfileUpdatedEvent((int) $page->id, $fresh->toArray(), (int) $user->id));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PageProfileUpdatedEvent: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'type' => $type,
            'url' => $url,
            'message' => ucfirst($type).' photo updated successfully.',
        ]);
    }

    /**
     * Remove Avatar or Cover Photo for the page with safe media cleanup.
     */
    public function removeBranding(Request $request, string $slug, string $type): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->orWhere('id', is_numeric($slug) ? (int) $slug : 0)->firstOrFail();
        $this->authorizePageManager($page, $user, 'page.edit');

        if ($type === 'avatar') {
            $oldAvatar = $page->avatar_url;
            $page->update(['avatar_url' => null]);
            $this->pageService->cleanupUnusedMedia($oldAvatar, $page->id);
        } elseif ($type === 'cover' || $type === 'cover_image') {
            $oldCover = $page->cover_image_url;
            $page->update(['cover_image_url' => null]);
            $this->pageService->cleanupUnusedMedia($oldCover, $page->id);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid branding type.',
            ], 422);
        }

        $fresh = $page->fresh();
        try {
            broadcast(new PageProfileUpdatedEvent((int) $page->id, $fresh->toArray(), (int) $user->id));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PageProfileUpdatedEvent: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => ucfirst($type).' photo removed successfully.',
        ]);
    }

    /**
     * Update complete core page settings and configuration.
     */
    public function updateSettings(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->orWhere('id', is_numeric($slug) ? (int) $slug : 0)->firstOrFail();
        $this->authorizePageManager($page, $user, 'page.settings');

        // Optimistic Concurrency Control
        if ($request->filled('last_updated_at')) {
            try {
                $clientTimestamp = Carbon::parse($request->input('last_updated_at'))->timestamp;
                if ($page->updated_at && $page->updated_at->timestamp > $clientTimestamp + 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Conflict: This page has been modified concurrently by another manager. Please reload before saving.',
                    ], 409);
                }
            } catch (\Exception $e) {
                // Ignore parsing exception
            }
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
            'sub_category' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:3000'],
            'cta_type' => ['nullable', 'string', 'max:50'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:60'],
            'country' => ['nullable', 'string', 'max:60'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'business_hours' => ['nullable', 'array'],
            'social_links' => ['nullable', 'array'],
            'visibility' => ['nullable', 'string', 'in:public,unlisted,private'],
            'status' => ['nullable', 'string', 'in:active,disabled'],
            'settings' => ['nullable', 'array'],
            'privacy_settings' => ['nullable', 'array'],
            'last_updated_at' => ['nullable', 'string'],
        ]);

        $oldSettings = $page->getMergedSettings();

        if (! empty($validated['name'])) {
            $page->name = $validated['name'];
        }

        if (array_key_exists('username', $validated) && ! empty($validated['username'])) {
            $cleanUser = Str::slug($validated['username']);
            if (Page::where('username', $cleanUser)->where('id', '!=', $page->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This username is already taken.',
                    'errors' => ['username' => ['This username is already taken.']],
                ], 422);
            }
            $page->username = $cleanUser;
        }

        if (! empty($validated['category'])) {
            $page->category = $validated['category'];
        }

        if (array_key_exists('sub_category', $validated)) {
            $page->sub_category = $validated['sub_category'];
        }

        if (array_key_exists('bio', $validated)) {
            $page->bio = $validated['bio'];
        }

        if (array_key_exists('description', $validated)) {
            $page->description = $validated['description'];
        }

        if (array_key_exists('cta_type', $validated)) {
            $page->cta_type = Str::snake(strtolower($validated['cta_type'] ?? ''));
        }

        if (array_key_exists('cta_url', $validated)) {
            $page->cta_url = $validated['cta_url'];
        }

        if (array_key_exists('website', $validated)) {
            $page->website = $validated['website'];
        }

        if (array_key_exists('email', $validated)) {
            $page->email = $validated['email'];
        }

        if (array_key_exists('phone', $validated)) {
            $page->phone = $validated['phone'];
        }

        if (array_key_exists('address', $validated)) {
            $page->address = $validated['address'];
        }

        if (array_key_exists('city', $validated)) {
            $page->city = $validated['city'];
        }

        if (array_key_exists('country', $validated)) {
            $page->country = $validated['country'];
        }

        if (array_key_exists('zip_code', $validated)) {
            $page->zip_code = $validated['zip_code'];
        }

        if (array_key_exists('business_hours', $validated)) {
            $page->business_hours = $validated['business_hours'];
        }

        if (array_key_exists('social_links', $validated)) {
            $page->social_links = $validated['social_links'];
        }

        if (array_key_exists('visibility', $validated) && ! empty($validated['visibility'])) {
            $page->visibility = $validated['visibility'];
        }

        if (array_key_exists('status', $validated) && ! empty($validated['status'])) {
            $page->status = $validated['status'];
        }

        if (! empty($validated['settings'])) {
            $currentSettings = $page->getMergedSettings();
            $merged = array_replace_recursive($currentSettings, $validated['settings']);
            if (isset($validated['settings']['publishing']['allowed_formats']) && is_array($validated['settings']['publishing']['allowed_formats'])) {
                $merged['publishing']['allowed_formats'] = array_values($validated['settings']['publishing']['allowed_formats']);
            }
            if (isset($validated['settings']['moderation']['blocked_keywords']) && is_array($validated['settings']['moderation']['blocked_keywords'])) {
                $merged['moderation']['blocked_keywords'] = array_values($validated['settings']['moderation']['blocked_keywords']);
            }
            $page->settings = $merged;
        }

        if (! empty($validated['privacy_settings'])) {
            $currentPrivacy = $page->getMergedPrivacySettings();
            $page->privacy_settings = array_replace_recursive($currentPrivacy, $validated['privacy_settings']);
        }

        $page->save();

        $fresh = $page->fresh();
        $this->logPageAction($page, $user, 'settings_updated', 'page', $page->id, $oldSettings, $fresh->getMergedSettings(), $request);

        event(new PageSettingsUpdatedEvent($page->id, $fresh->getMergedSettings(), $user->id));

        return response()->json([
            'success' => true,
            'message' => 'Page settings updated successfully.',
            'page' => $fresh,
            'settings' => $fresh->getMergedSettings(),
            'privacy_settings' => $fresh->getMergedPrivacySettings(),
        ]);
    }

    /**
     * Retrieve audit logs for a page.
     */
    public function auditLogs(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->orWhere('id', is_numeric($slug) ? (int) $slug : 0)->firstOrFail();
        $this->authorizePageManager($page, $user, 'page.settings');

        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));

        $logs = PageAuditLog::where('page_id', $page->id)
            ->with(['actor:id,name,username,email'])
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Dedicated JSON endpoint for verifiable Page Analytics & Insights.
     */
    public function analytics(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->orWhere('id', is_numeric($slug) ? (int) $slug : 0)->firstOrFail();
        $this->authorizePageManager($page, $user, 'analytics.view');

        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:7d,30d,90d,custom'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'timezone' => ['nullable', 'string'],
        ]);

        $timezone = $validated['timezone'] ?? config('app.timezone', 'UTC');
        if (! in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            $timezone = 'UTC';
        }

        $startDate = null;
        $endDate = null;
        $days = 30;

        $period = $validated['period'] ?? null;
        if ($period === '7d' || ($validated['days'] ?? null) === 7) {
            $days = 7;
        } elseif ($period === '90d' || ($validated['days'] ?? null) === 90) {
            $days = 90;
        } elseif ($period === 'custom' || (! empty($validated['start_date']) && ! empty($validated['end_date']))) {
            $startDate = Carbon::parse($validated['start_date'], $timezone)->startOfDay();
            $endDate = Carbon::parse($validated['end_date'], $timezone)->endOfDay();
            $days = max(1, (int) $startDate->diffInDays($endDate) + 1);
        } else {
            $days = min(365, max(1, (int) ($validated['days'] ?? $request->query('days', 30))));
        }

        $analytics = $this->enterprisePageService->getPageAnalytics(
            $page,
            $days,
            $startDate,
            $endDate,
            $timezone
        );

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Archive or deactivate a page.
     */
    public function archivePage(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->orWhere('id', is_numeric($slug) ? (int) $slug : 0)->firstOrFail();
        $this->authorizePageManager($page, $user, 'page.settings');

        $previousStatus = $page->status;
        $page->update([
            'status' => Page::STATUS_DISABLED,
        ]);

        $this->enterprisePageService->logAudit($page, $user, 'page.archive', 'Page', $page->id, [
            'status' => $previousStatus,
        ], [
            'status' => Page::STATUS_DISABLED,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page has been archived successfully.',
            'page' => $page->fresh(),
        ]);
    }

    /**
     * Delete page to trash (soft-delete, owner only).
     */
    public function deletePage(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->orWhere('id', is_numeric($slug) ? (int) $slug : 0)->firstOrFail();

        if (! $user || (int) $page->owner_id !== (int) $user->id) {
            abort(403, 'Only the page owner can delete this page.');
        }

        $previousStatus = $page->status;
        $page->update(['status' => Page::STATUS_TRASH]);
        $page->delete();

        $this->enterprisePageService->logAudit($page, $user, 'page.trash', 'Page', $page->id, [
            'status' => $previousStatus,
        ], [
            'status' => Page::STATUS_TRASH,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page moved to trash successfully.',
        ]);
    }

    /**
     * Restore page from trash (owner only).
     */
    public function restorePage(Request $request, int $id): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::onlyTrashed()->findOrFail($id);

        if (! $user || (int) $page->owner_id !== (int) $user->id) {
            abort(403, 'Only the page owner can restore this page.');
        }

        $page->restore();
        $page->update(['status' => Page::STATUS_ACTIVE]);

        $this->enterprisePageService->logAudit($page, $user, 'page.restore', 'Page', $page->id, [
            'status' => Page::STATUS_TRASH,
        ], [
            'status' => Page::STATUS_ACTIVE,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page restored successfully.',
            'page' => $page->fresh(),
        ]);
    }

    /**
     * Transfer page ownership (owner only).
     */
    public function transferOwnership(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->orWhere('id', is_numeric($slug) ? (int) $slug : 0)->firstOrFail();

        if (! $user || (int) $page->owner_id !== (int) $user->id) {
            abort(403, 'Only the page owner can transfer ownership.');
        }

        $validated = $request->validate([
            'new_owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'identifier' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'string'],
        ]);

        if (! empty($validated['password'])) {
            if (! Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The password provided for ownership transfer is incorrect.',
                    'errors' => ['password' => ['The password provided for ownership transfer is incorrect.']],
                ], 422);
            }
        }

        $newOwner = null;
        if (! empty($validated['new_owner_id'])) {
            $newOwner = User::findOrFail($validated['new_owner_id']);
        } elseif (! empty($validated['identifier'])) {
            $identifier = trim($validated['identifier']);
            $newOwner = User::where('email', $identifier)
                ->orWhere('username', $identifier)
                ->first();
        }

        if (! $newOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Target new owner not found.',
            ], 404);
        }

        if ((int) $newOwner->id === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot transfer ownership to yourself.',
            ], 422);
        }

        try {
            $transferred = $this->enterprisePageService->transferOwnership($page, $user, $newOwner);

            return response()->json([
                'success' => true,
                'message' => "Ownership of '{$page->name}' transferred to {$newOwner->name}.",
                'page' => $transferred->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Invite or add team member.
     */
    public function inviteTeamMember(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $this->authorizePageManager($page, $user, 'team.manage');

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:120'],
            'role' => ['required', 'string', 'in:admin,manager,editor,content_manager,moderator,analyst,viewer'],
            'custom_permissions' => ['nullable', 'array'],
        ]);

        $isOwner = (int) $page->owner_id === (int) $user->id;
        if ($validated['role'] === Page::ROLE_ADMIN && ! $isOwner) {
            abort(403, 'Only the page owner can invite an administrator.');
        }

        $identifier = trim($validated['identifier']);
        $targetUser = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (! $targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found with the provided email or username.',
            ], 404);
        }

        if ((int) $targetUser->id === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot invite yourself to the team.',
            ], 422);
        }

        if ((int) $targetUser->id === (int) $page->owner_id) {
            return response()->json([
                'success' => false,
                'message' => 'This user is already the owner of this page.',
            ], 422);
        }

        try {
            $member = $this->enterprisePageService->inviteTeamMember(
                $page,
                $user,
                $identifier,
                $validated['role'],
                $validated['custom_permissions'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => "{$targetUser->name} has been invited as {$validated['role']}.",
                'member' => $member->load('user.profile'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update existing team member's role.
     */
    public function updateTeamMemberRole(Request $request, string $slug, int $memberId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $this->authorizePageManager($page, $user, 'team.manage');

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,manager,editor,content_manager,moderator,analyst,viewer'],
            'custom_permissions' => ['nullable', 'array'],
        ]);

        try {
            $member = $this->enterprisePageService->updateMemberRole(
                $page,
                $user,
                $memberId,
                $validated['role'],
                $validated['custom_permissions'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Team member role updated successfully.',
                'member' => $member->load('user.profile'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove team member from the page.
     */
    public function removeTeamMember(Request $request, string $slug, int $memberId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $this->authorizePageManager($page, $user, 'team.manage');

        try {
            $this->enterprisePageService->removeMember($page, $user, $memberId);

            return response()->json([
                'success' => true,
                'message' => 'Team member removed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Revoke pending team invitation.
     */
    public function revokeTeamInvitation(Request $request, string $slug, int $memberId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $this->authorizePageManager($page, $user, 'team.manage');

        try {
            $this->enterprisePageService->revokeInvitation($page, $user, $memberId);

            return response()->json([
                'success' => true,
                'message' => 'Team invitation revoked successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get paginated followers for a page.
     */
    public function followers(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        // Check page visibility / authorization
        if ($page->visibility === 'private') {
            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to view followers of this private page.'], 403);
            }
            $isManager = (int) $page->owner_id === (int) $user->id || $page->hasPermission($user->id, 'page.view');
            $isFollower = PageFollower::where('page_id', $page->id)->where('user_id', $user->id)->exists();
            if (! $isManager && ! $isFollower) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to view followers of this private page.'], 403);
            }
        }

        if ($page->status !== Page::STATUS_ACTIVE) {
            $isManager = $user && ((int) $page->owner_id === (int) $user->id || $page->hasPermission($user->id, 'page.view'));
            if (! $isManager) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to view followers of this inactive page.'], 403);
            }
        }

        $query = PageFollower::where('page_id', $page->id)->with('user.profile');
        if ($search = $request->query('q')) {
            $query->whereHas('user', function ($uq) use ($search) {
                $uq->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));
        $followers = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($followers->items())->map(function ($f) {
                $u = $f->user;

                return [
                    'id' => $f->id,
                    'user_id' => $f->user_id,
                    'created_at' => $f->created_at ? $f->created_at->diffForHumans() : 'Recently',
                    'joined_date' => $f->created_at ? $f->created_at->format('M d, Y') : 'Recently',
                    'user' => $u ? [
                        'id' => $u->id,
                        'name' => $u->name,
                        'username' => $u->username,
                        'avatar_url' => $u->profile?->avatar_url ? UserProfile::normalizeStorageUrl($u->profile->avatar_url) : $u->avatar_url,
                    ] : null,
                ];
            }),
            'meta' => [
                'current_page' => $followers->currentPage(),
                'last_page' => $followers->lastPage(),
                'total' => $followers->total(),
            ],
        ]);
    }

    /**
     * Remove follower from page.
     */
    public function removeFollower(Request $request, string $slug, int $userId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'page.moderate');

        $deleted = PageFollower::where('page_id', $page->id)->where('user_id', $userId)->delete();
        if ($deleted && $page->followers_count > 0) {
            $page->decrement('followers_count');
        }

        $newCount = (int) $page->fresh()->followers_count;
        try {
            broadcast(new PageFollowerUpdatedEvent((int) $page->id, $newCount, 'removed', $userId));
        } catch (\Throwable $e) {
            // Ignore broadcast failure
        }

        return response()->json([
            'success' => true,
            'message' => 'Follower removed from page.',
        ]);
    }

    /**
     * Create a new page event.
     */
    public function storeEvent(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'events.manage');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'is_online' => ['nullable', 'boolean'],
        ]);

        $enterpriseService = app(EnterprisePageService::class);
        $event = $enterpriseService->createEvent($page, $user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully.',
            'event' => $event->load('rsvps'),
        ], 201);
    }

    /**
     * Update an existing page event.
     */
    public function updateEvent(Request $request, string $slug, int $eventId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'events.manage');

        $event = PageEvent::where('page_id', $page->id)->findOrFail($eventId);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'is_online' => ['nullable', 'boolean'],
        ]);

        $event->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'event' => $event->fresh('rsvps'),
        ]);
    }

    /**
     * Delete a page event.
     */
    public function deleteEvent(Request $request, string $slug, int $eventId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'events.manage');

        $event = PageEvent::where('page_id', $page->id)->findOrFail($eventId);
        $event->rsvps()->delete();
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully.',
        ]);
    }

    /**
     * RSVP to a page event.
     */
    public function rsvpEvent(Request $request, string $slug, int $eventId): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Please login first.'], 401);
        }
        $page = Page::where('slug', $slug)->firstOrFail();
        $event = PageEvent::where('page_id', $page->id)->findOrFail($eventId);

        $existing = PageEventRsvp::where('event_id', $event->id)->where('user_id', $user->id)->first();
        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'none') {
                $existing?->delete();
            } else {
                PageEventRsvp::updateOrCreate(
                    ['event_id' => $event->id, 'user_id' => $user->id],
                    ['status' => $status]
                );
            }
        } else {
            if ($existing && $existing->status === 'going') {
                $existing->delete();
                $status = 'none';
            } else {
                PageEventRsvp::updateOrCreate(
                    ['event_id' => $event->id, 'user_id' => $user->id],
                    ['status' => 'going']
                );
                $status = 'going';
            }
        }

        $goingCount = $event->rsvps()->where('status', 'going')->count();
        $event->update(['rsvp_count' => $goingCount]);

        return response()->json([
            'success' => true,
            'message' => $status === 'none' ? 'RSVP cancelled.' : 'RSVP updated successfully.',
            'rsvp_count' => $goingCount,
            'status' => $status,
        ]);
    }

    /**
     * Display or retrieve events for a page.
     */
    public function events(Request $request, string $slug): JsonResponse|View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $events = PageEvent::where('page_id', $page->id)
            ->whereIn('status', ['active', 'published'])
            ->with(['rsvps.user.profile'])
            ->orderBy('start_time')
            ->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $events,
            ]);
        }

        return redirect()->route('pages.show', ['slug' => $page->slug, 'tab' => 'events']);
    }

    /**
     * Display or retrieve about information for a page.
     */
    public function about(Request $request, string $slug): JsonResponse|View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        if ($request->wantsJson() || $request->ajax()) {
            $settings = $page->getMergedSettings();
            $privacy = $settings['privacy'] ?? [];
            $showContact = $privacy['show_contact_info'] ?? true;
            $publicEmail = $privacy['public_email'] ?? true;
            $publicPhone = $privacy['public_phone'] ?? true;
            $publicAddress = $privacy['public_address'] ?? true;

            $isPrivileged = false;
            if ($user) {
                $isPrivileged = (int) $page->owner_id === (int) $user->id
                    || $page->members()->where('user_id', $user->id)->where('status', 'active')->exists();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $page->id,
                    'name' => $page->name,
                    'slug' => $page->slug,
                    'username' => $page->username,
                    'category' => $page->category,
                    'sub_category' => $page->sub_category,
                    'bio' => $page->bio,
                    'description' => $page->description,
                    'website' => ($isPrivileged || $showContact) ? $page->website : null,
                    'email' => ($isPrivileged || ($showContact && $publicEmail)) ? $page->email : null,
                    'phone' => ($isPrivileged || ($showContact && $publicPhone)) ? $page->phone : null,
                    'address' => ($isPrivileged || ($showContact && $publicAddress)) ? $page->address : null,
                    'city' => ($isPrivileged || ($showContact && $publicAddress)) ? $page->city : null,
                    'country' => $page->country,
                    'zip_code' => ($isPrivileged || ($showContact && $publicAddress)) ? $page->zip_code : null,
                    'business_hours' => $page->business_hours,
                    'social_links' => $page->social_links,
                    'followers_count' => (int) $page->followers_count,
                    'is_verified' => (bool) $page->is_verified,
                    'created_at' => $page->created_at?->toIso8601String(),
                ],
            ]);
        }

        return redirect()->route('pages.show', ['slug' => $page->slug, 'tab' => 'about']);
    }

    /**
     * Display or retrieve photos for a page.
     */
    public function photos(Request $request, string $slug): JsonResponse|View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $postIds = Post::where('page_id', $page->id)->pluck('id');
        $perPage = min(50, max(1, (int) $request->input('per_page', 24)));
        $photos = Media::where('mediable_type', Post::class)
            ->whereIn('mediable_id', $postIds)
            ->where('mime_type', 'like', 'image/%')
            ->latest('id')
            ->paginate($perPage);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $photos->items(),
                'meta' => [
                    'current_page' => $photos->currentPage(),
                    'last_page' => $photos->lastPage(),
                    'total' => $photos->total(),
                    'per_page' => $photos->perPage(),
                ],
            ]);
        }

        return redirect()->route('pages.show', ['slug' => $page->slug, 'tab' => 'photos']);
    }

    /**
     * Upload photos to the page gallery.
     */
    public function uploadPhoto(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'posts.create');

        $request->validate([
            'photos' => ['required_without:photo', 'array'],
            'photos.*' => ['file', 'image', 'max:20480'],
            'photo' => ['nullable', 'file', 'image', 'max:20480'],
            'caption' => ['nullable', 'string', 'max:5000'],
        ]);

        $files = [];
        if ($request->hasFile('photo')) {
            $files[] = $request->file('photo');
        }
        if ($request->hasFile('photos')) {
            $files = array_merge($files, $request->file('photos'));
        }

        if (empty($files)) {
            return response()->json(['success' => false, 'message' => 'No photos provided.'], 422);
        }

        $caption = $request->input('caption') ?: ($page->name.' added new photos.');
        $post = Post::create([
            'user_id' => $user->id,
            'page_id' => $page->id,
            'type' => 'photo',
            'content' => $caption,
            'audience' => 'public',
            'status' => 'published',
        ]);
        $page->increment('posts_count');

        $createdMedia = [];
        foreach ($files as $file) {
            $mime = $file->getMimeType() ?: 'image/jpeg';
            $path = $file->store('posts/media', 'public');

            $media = Media::create([
                'user_id' => $user->id,
                'mediable_type' => Post::class,
                'mediable_id' => $post->id,
                'collection' => 'posts',
                'disk' => 'public',
                'original_path' => $path,
                'mime_type' => $mime,
                'size' => $file->getSize(),
                'processing_status' => 'completed',
                'metadata' => [
                    'original_filename' => $file->getClientOriginalName(),
                ],
            ]);

            $createdMedia[] = [
                'id' => $media->id,
                'url' => $media->url,
                'created_at' => $media->created_at?->diffForHumans() ?? 'Just now',
                'caption' => $caption,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Photos uploaded successfully.',
            'media' => $createdMedia,
            'post' => $post,
        ], 201);
    }

    /**
     * Delete a photo from page media.
     */
    public function deletePhoto(Request $request, string $slug, int $mediaId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'media.manage');

        $media = Media::where('id', $mediaId)->where('mediable_type', Post::class)->firstOrFail();
        $post = Post::where('page_id', $page->id)->where('id', $media->mediable_id)->firstOrFail();

        if ($media->original_path) {
            Storage::disk($media->disk ?: 'public')->delete($media->original_path);
        }
        $media->delete();

        if ($post->media()->count() === 0 && str_contains($post->content, 'added new photos')) {
            if ($post->status === 'published' && $page->posts_count > 0) {
                $page->decrement('posts_count');
            }
            $post->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted successfully.',
        ]);
    }

    /**
     * Display the Enterprise Page Registration & Creation Operating System.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect('/login')->with('error', 'পেইজ তৈরি করতে অনুগ্রহ করে প্রথমে লগইন করুন।');
        }

        $pageTypes = PageType::where('is_active', true)
            ->with(['categories' => fn ($q) => $q->where('is_active', true)->with('subcategories')])
            ->orderBy('sort_order')
            ->get();

        if ($pageTypes->isEmpty()) {
            try {
                app(PageTaxonomySeeder::class)->run();
                $pageTypes = PageType::where('is_active', true)
                    ->with(['categories' => fn ($q) => $q->where('is_active', true)->with('subcategories')])
                    ->orderBy('sort_order')
                    ->get();
            } catch (\Throwable $e) {
                Log::warning('Automatic page taxonomy seeding failed: '.$e->getMessage());
            }
        }

        $latestDraft = PageCreationDraft::where('user_id', $user->id)
            ->latest('updated_at')
            ->first();

        $ownedPagesCount = Page::where('owner_id', $user->id)->count();

        return view('pages.create', [
            'currentUser' => $user,
            'pageTypes' => $pageTypes,
            'latestDraft' => $latestDraft,
            'ownedPagesCount' => $ownedPagesCount,
            'maxAllowedPages' => 10,
        ]);
    }

    /**
     * Follow a page by slug.
     */
    public function followPage(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to follow this page.',
            ], 401);
        }

        $page = Page::where(function ($q) use ($slug) {
            $q->where('slug', $slug)->orWhere('username', $slug);
        })->where('status', '!=', Page::STATUS_TRASH)->firstOrFail();

        $alreadyFollowing = PageFollower::where('page_id', $page->id)->where('user_id', $user->id)->exists();
        if ($alreadyFollowing) {
            return response()->json([
                'success' => true,
                'is_following' => true,
                'followers_count' => (int) $page->followers_count,
                'message' => 'Already following this page.',
            ]);
        }

        $result = $this->pageService->toggleFollow($page, $user);

        return response()->json([
            'success' => true,
            'is_following' => true,
            'followers_count' => $result['followers_count'],
            'message' => 'Followed page successfully.',
        ]);
    }

    /**
     * Unfollow a page by slug.
     */
    public function unfollowPage(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to unfollow this page.',
            ], 401);
        }

        $page = Page::where(function ($q) use ($slug) {
            $q->where('slug', $slug)->orWhere('username', $slug);
        })->where('status', '!=', Page::STATUS_TRASH)->firstOrFail();

        $deleted = PageFollower::where('page_id', $page->id)->where('user_id', $user->id)->delete();
        if ($deleted) {
            $page->decrement('followers_count');
        }

        return response()->json([
            'success' => true,
            'is_following' => false,
            'followers_count' => (int) $page->fresh()->followers_count,
            'message' => 'Unfollowed page successfully.',
        ]);
    }

    /**
     * Get eligible friends/users that can be invited to follow a page.
     */
    public function getEligibleFriendsForInvite(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $page = Page::where(function ($q) use ($slug) {
            $q->where('slug', $slug)->orWhere('username', $slug);
        })->where('status', '!=', Page::STATUS_TRASH)->firstOrFail();

        $followerUserIds = PageFollower::where('page_id', $page->id)->pluck('user_id')->toArray();
        $pendingInviteeIds = PageInvitation::where('page_id', $page->id)->active()->pluck('invitee_id')->toArray();
        $blockedUserIds = PageBlockedUser::where('page_id', $page->id)->pluck('user_id')->toArray();

        $excludedIds = array_unique(array_merge([$user->id], $followerUserIds, $pendingInviteeIds, $blockedUserIds));

        $friendIds = method_exists($user, 'getFriendIds') ? $user->getFriendIds() : [];

        $query = User::whereNotIn('id', $excludedIds)->with(['profile']);

        if (! empty($friendIds)) {
            $query->orderByRaw('CASE WHEN id IN ('.implode(',', array_map('intval', $friendIds)).') THEN 0 ELSE 1 END');
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $limit = min(50, max(1, (int) $request->query('limit', 20)));
        $eligibleUsers = $query->take($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $eligibleUsers->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'avatar_url' => $u->profile?->avatar_url ? UserProfile::normalizeStorageUrl($u->profile->avatar_url) : $u->avatar_url,
                'is_friend' => in_array($u->id, $friendIds, true),
            ]),
        ]);
    }

    /**
     * Send page follow invitations to eligible users.
     */
    public function sendInvitation(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $page = Page::where(function ($q) use ($slug) {
            $q->where('slug', $slug)->orWhere('username', $slug);
        })->where('status', '!=', Page::STATUS_TRASH)->firstOrFail();

        $validated = $request->validate([
            'invitee_id' => ['nullable', 'integer', 'exists:users,id'],
            'invitee_ids' => ['nullable', 'array'],
            'invitee_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $inviteeIds = [];
        if (! empty($validated['invitee_id'])) {
            $inviteeIds[] = (int) $validated['invitee_id'];
        }
        if (! empty($validated['invitee_ids'])) {
            $inviteeIds = array_merge($inviteeIds, array_map('intval', $validated['invitee_ids']));
        }
        $inviteeIds = array_unique($inviteeIds);

        if (empty($inviteeIds)) {
            return response()->json(['success' => false, 'message' => 'Please select at least one user to invite.'], 422);
        }

        // Rate limit check: max 30 invitations in last 24h
        $sentToday = PageInvitation::where('inviter_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($sentToday + count($inviteeIds) > PageInvitation::DAILY_RATE_LIMIT) {
            return response()->json([
                'success' => false,
                'message' => 'Daily invitation limit exceeded (max '.PageInvitation::DAILY_RATE_LIMIT.' per day). Please try again tomorrow.',
            ], 429);
        }

        $createdInvitations = [];
        $errors = [];

        foreach ($inviteeIds as $inviteeId) {
            if ($inviteeId === (int) $user->id) {
                $errors[] = 'You cannot invite yourself to follow the page.';

                continue;
            }

            if ($page->isBlocked($inviteeId)) {
                $errors[] = 'Cannot invite blocked user.';

                continue;
            }

            if (PageFollower::where('page_id', $page->id)->where('user_id', $inviteeId)->exists()) {
                $errors[] = 'User is already following this page.';

                continue;
            }

            $alreadyPending = PageInvitation::where('page_id', $page->id)
                ->where('invitee_id', $inviteeId)
                ->active()
                ->exists();

            if ($alreadyPending) {
                $errors[] = 'An invitation has already been sent to this user.';

                continue;
            }

            $invitation = PageInvitation::create([
                'page_id' => $page->id,
                'inviter_id' => $user->id,
                'invitee_id' => $inviteeId,
                'status' => PageInvitation::STATUS_PENDING,
                'expires_at' => now()->addDays(PageInvitation::DEFAULT_EXPIRY_DAYS),
            ]);

            $createdInvitations[] = $invitation;

            // Notification delivery respecting preferences
            $targetUser = User::find($inviteeId);
            $setting = NotificationSetting::where('user_id', $inviteeId)->first();
            $sendNotification = ! $setting || (bool) $setting->page_alerts;

            if ($sendNotification && app()->bound(NotificationServiceInterface::class)) {
                try {
                    app(NotificationServiceInterface::class)->send(
                        recipientId: $inviteeId,
                        type: 'page.invitation',
                        data: [
                            'page_id' => $page->id,
                            'page_name' => $page->name,
                            'page_slug' => $page->slug,
                            'inviter_id' => $user->id,
                            'inviter_name' => $user->name,
                            'invitation_id' => $invitation->id,
                            'action_url' => "/pages/{$page->slug}",
                            'title' => 'Page Follow Invitation',
                            'message' => ($user->name ?? 'A user')." invited you to follow '{$page->name}'",
                        ],
                        channels: ['database']
                    );
                } catch (\Throwable $e) {
                    Log::warning('Could not send page invitation notification: '.$e->getMessage());
                }
            }

            try {
                broadcast(new PageInvitationEvent(
                    invitationId: (int) $invitation->id,
                    pageId: (int) $page->id,
                    inviteeId: (int) $inviteeId,
                    status: 'sent',
                    invitationData: [
                        'page_name' => $page->name,
                        'page_slug' => $page->slug,
                        'page_avatar' => $page->avatar_url,
                        'inviter_id' => $user->id,
                        'inviter_name' => $user->name,
                        'action_url' => "/pages/{$page->slug}",
                    ]
                ));
            } catch (\Throwable $e) {
                Log::warning('Could not broadcast PageInvitationEvent: '.$e->getMessage());
            }
        }

        if (empty($createdInvitations)) {
            return response()->json([
                'success' => false,
                'message' => ! empty($errors) ? $errors[0] : 'No eligible users could be invited.',
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => count($createdInvitations).' invitation(s) sent successfully.',
            'invitations_count' => count($createdInvitations),
            'data' => collect($createdInvitations)->map(fn ($inv) => $inv->toResponseArray()),
        ], 201);
    }

    /**
     * Accept a page follow invitation.
     */
    public function acceptInvitation(Request $request, int $id): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $invitation = PageInvitation::with(['page', 'inviter'])->findOrFail($id);

        if ((int) $invitation->invitee_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ($invitation->isExpired()) {
            $invitation->update(['status' => PageInvitation::STATUS_EXPIRED]);

            return response()->json(['success' => false, 'message' => 'This invitation has expired.'], 422);
        }

        if ($invitation->status === PageInvitation::STATUS_ACCEPTED) {
            return response()->json(['success' => true, 'message' => 'Invitation already accepted.', 'is_following' => true]);
        }

        $page = $invitation->page;
        if (! $page) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        DB::transaction(function () use ($invitation, $page, $user) {
            $invitation->update([
                'status' => PageInvitation::STATUS_ACCEPTED,
                'responded_at' => now(),
            ]);

            $alreadyFollowing = PageFollower::where('page_id', $page->id)->where('user_id', $user->id)->exists();
            if (! $alreadyFollowing) {
                try {
                    PageFollower::create([
                        'page_id' => $page->id,
                        'user_id' => $user->id,
                        'created_at' => now(),
                    ]);
                    $page->increment('followers_count');
                } catch (\Throwable $e) {
                    // Ignore race
                }
            }
        });

        // Broadcast invitation accepted & follower updated events
        try {
            broadcast(new PageInvitationEvent(
                invitationId: (int) $invitation->id,
                pageId: (int) $page->id,
                inviteeId: (int) $user->id,
                status: 'accepted',
                invitationData: [
                    'page_name' => $page->name,
                    'page_slug' => $page->slug,
                    'inviter_id' => $invitation->inviter_id,
                    'invitee_name' => $user->name,
                ]
            ));
            broadcast(new PageFollowerUpdatedEvent((int) $page->id, (int) $page->fresh()->followers_count, 'followed', (int) $user->id));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PageInvitationEvent: '.$e->getMessage());
        }

        // Notify inviter
        if (app()->bound(NotificationServiceInterface::class) && (int) $invitation->inviter_id !== (int) $user->id) {
            try {
                app(NotificationServiceInterface::class)->send(
                    recipientId: $invitation->inviter_id,
                    type: 'page.invitation_accepted',
                    data: [
                        'page_id' => $page->id,
                        'page_name' => $page->name,
                        'page_slug' => $page->slug,
                        'invitee_id' => $user->id,
                        'invitee_name' => $user->name,
                        'title' => 'Page Invitation Accepted',
                        'message' => ($user->name ?? 'A user')." accepted your invitation to follow '{$page->name}'",
                        'action_url' => "/pages/{$page->slug}",
                    ],
                    channels: ['database']
                );
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation accepted. You are now following this page.',
            'is_following' => true,
            'followers_count' => (int) $page->fresh()->followers_count,
        ]);
    }

    /**
     * Decline a page follow invitation.
     */
    public function declineInvitation(Request $request, int $id): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $invitation = PageInvitation::findOrFail($id);

        if ((int) $invitation->invitee_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $invitation->update([
            'status' => PageInvitation::STATUS_DECLINED,
            'responded_at' => now(),
        ]);

        try {
            broadcast(new PageInvitationEvent(
                invitationId: (int) $invitation->id,
                pageId: (int) $invitation->page_id,
                inviteeId: (int) $user->id,
                status: 'declined'
            ));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PageInvitationEvent: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation declined.',
        ]);
    }

    /**
     * List invitations for a page (for staff/managers).
     */
    public function getPageInvitations(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'page.view');

        $invitations = PageInvitation::where('page_id', $page->id)
            ->with(['invitee.profile', 'inviter.profile'])
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => collect($invitations->items())->map(fn ($inv) => $inv->toResponseArray()),
            'meta' => [
                'current_page' => $invitations->currentPage(),
                'last_page' => $invitations->lastPage(),
                'total' => $invitations->total(),
            ],
        ]);
    }

    /**
     * Get pending page invitations for current user.
     */
    public function getUserPageInvitations(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $invitations = PageInvitation::where('invitee_id', $user->id)
            ->active()
            ->with(['page.owner', 'inviter.profile'])
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invitations->map(fn ($inv) => $inv->toResponseArray()),
        ]);
    }

    /**
     * Get Page conversation and message history.
     */
    public function getPageMessages(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $page = Page::where(function ($q) use ($slug) {
            $q->where('slug', $slug)->orWhere('username', $slug);
        })->where('status', '!=', Page::STATUS_TRASH)->firstOrFail();

        $conversationId = $request->query('conversation_id');

        if ($conversationId) {
            $conversation = PageConversation::where('page_id', $page->id)->findOrFail($conversationId);

            $isOwner = (int) $conversation->user_id === (int) $user->id;
            $canManage = (int) $page->owner_id === (int) $user->id || $page->hasPermission($user->id, 'messages.manage');

            if (! $isOwner && ! $canManage) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to access this conversation.'], 403);
            }

            if ($canManage && ! $isOwner) {
                $conversation->update(['unread_page_count' => 0]);
                $conversation->messages()->where('sender_type', 'user')->where('is_read', false)->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
            } else {
                $conversation->update(['unread_user_count' => 0]);
                $conversation->messages()->where('sender_type', 'page')->where('is_read', false)->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
            }
        } else {
            if ($page->isBlocked($user->id)) {
                return response()->json(['success' => false, 'message' => 'You are restricted from messaging this page.'], 403);
            }

            $conversation = PageConversation::firstOrCreate(
                ['page_id' => $page->id, 'user_id' => $user->id],
                ['status' => 'open', 'last_message_at' => now()]
            );

            $conversation->update(['unread_user_count' => 0]);
            $conversation->messages()->where('sender_type', 'page')->where('is_read', false)->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        $messages = $conversation->messages()->with('sender.profile')->oldest('id')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => [
                    'id' => $conversation->id,
                    'page_id' => $conversation->page_id,
                    'user_id' => $conversation->user_id,
                    'status' => $conversation->status,
                    'unread_user_count' => (int) $conversation->unread_user_count,
                    'unread_page_count' => (int) $conversation->unread_page_count,
                    'last_message_at' => $conversation->last_message_at?->toISOString(),
                ],
                'messages' => $messages->map(fn ($m) => [
                    'id' => $m->id,
                    'conversation_id' => $m->conversation_id,
                    'sender_type' => $m->sender_type,
                    'sender_id' => $m->sender_id,
                    'sender_name' => $m->sender_type === 'page' ? $page->name : ($m->sender->name ?? 'User'),
                    'sender_avatar' => $m->sender_type === 'page' ? $page->avatar_url : ($m->sender->avatar_url ?? null),
                    'body' => $m->body,
                    'is_read' => (bool) $m->is_read,
                    'created_at' => $m->created_at?->diffForHumans(),
                    'timestamp' => $m->created_at?->toISOString(),
                ]),
            ],
        ]);
    }

    /**
     * Send message to a page or reply as page staff.
     */
    public function sendPageMessage(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $page = Page::where(function ($q) use ($slug) {
            $q->where('slug', $slug)->orWhere('username', $slug);
        })->where('status', '!=', Page::STATUS_TRASH)->firstOrFail();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'as_page' => ['nullable', 'boolean'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $asPage = (bool) ($validated['as_page'] ?? false);
        $enterprisePageService = app(EnterprisePageService::class);

        if ($asPage) {
            $canManage = (int) $page->owner_id === (int) $user->id || $page->hasPermission($user->id, 'messages.manage');
            if (! $canManage) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to send messages from this page.'], 403);
            }

            $conversationId = (int) ($validated['conversation_id'] ?? 0);
            $conversation = PageConversation::where('page_id', $page->id)->findOrFail($conversationId);

            $message = $enterprisePageService->sendMessage(
                $page,
                $user,
                $conversation->id,
                $validated['body'],
                'page'
            );

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully as page.',
                'data' => [
                    'id' => $message->id,
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'page',
                    'sender_id' => $user->id,
                    'sender_name' => $page->name,
                    'body' => $message->body,
                    'created_at' => $message->created_at?->diffForHumans(),
                ],
            ], 201);
        }

        if ($page->isBlocked($user->id)) {
            return response()->json(['success' => false, 'message' => 'You are restricted from messaging this page.'], 403);
        }

        $pageSettings = $page->getMergedSettings();
        if (isset($pageSettings['messaging']['allow_messages']) && ! $pageSettings['messaging']['allow_messages']) {
            return response()->json(['success' => false, 'message' => 'Direct messaging is currently disabled for this page.'], 403);
        }

        $conversation = PageConversation::firstOrCreate(
            ['page_id' => $page->id, 'user_id' => $user->id],
            ['status' => 'open', 'last_message_at' => now()]
        );

        $message = $enterprisePageService->sendMessage(
            $page,
            $user,
            $conversation->id,
            $validated['body'],
            'user'
        );

        if (! empty($pageSettings['messaging']['auto_reply_enabled']) && ! empty($pageSettings['messaging']['auto_reply_message'])) {
            try {
                $enterprisePageService->sendMessage(
                    $page,
                    $user,
                    $conversation->id,
                    $pageSettings['messaging']['auto_reply_message'],
                    'page'
                );
            } catch (\Throwable $e) {
                // Ignore auto-reply exception
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent to page.',
            'data' => [
                'id' => $message->id,
                'conversation_id' => $conversation->id,
                'sender_type' => 'user',
                'sender_id' => $user->id,
                'sender_name' => $user->name,
                'body' => $message->body,
                'created_at' => $message->created_at?->diffForHumans(),
            ],
        ], 201);
    }

    /**
     * Mark page conversation messages as read.
     */
    public function markMessagesRead(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $page = Page::where(function ($q) use ($slug) {
            $q->where('slug', $slug)->orWhere('username', $slug);
        })->where('status', '!=', Page::STATUS_TRASH)->firstOrFail();

        $conversationId = $request->input('conversation_id');
        if ($conversationId) {
            $conv = PageConversation::where('page_id', $page->id)->findOrFail($conversationId);
        } else {
            $conv = PageConversation::where('page_id', $page->id)->where('user_id', $user->id)->first();
        }

        if (! $conv) {
            return response()->json(['success' => true, 'message' => 'No conversation found.']);
        }

        $isOwner = (int) $conv->user_id === (int) $user->id;
        $canManage = (int) $page->owner_id === (int) $user->id || $page->hasPermission($user->id, 'messages.manage');

        if (! $isOwner && ! $canManage) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($canManage && ! $isOwner) {
            $conv->update(['unread_page_count' => 0]);
            $conv->messages()->where('sender_type', 'user')->where('is_read', false)->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        } else {
            $conv->update(['unread_user_count' => 0]);
            $conv->messages()->where('sender_type', 'page')->where('is_read', false)->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Messages marked as read.']);
    }
}
