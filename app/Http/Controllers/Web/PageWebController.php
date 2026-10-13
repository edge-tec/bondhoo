<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageCreationDraft;
use App\Models\PageEvent;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\PageProduct;
use App\Models\PageType;
use App\Models\Post;
use App\Models\User;
use App\Services\Page\EnterprisePageService;
use App\Services\PageService;
use App\Services\ReactionService;
use Carbon\Carbon;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class PageWebController extends Controller
{
    public function __construct(
        protected PageService $pageService
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

        // Time-range performance analytics datasets
        $calcReach = function (int $days) use ($managedPosts, $totalFollowersManaged): int {
            if ($totalFollowersManaged === 0 && $managedPosts->isEmpty()) {
                return 0;
            }
            $since = now()->subDays($days);
            $recent = $managedPosts->where('created_at', '>=', $since);
            $est = $recent->sum('likes_count') * 12 + $recent->count() * 85 + $totalFollowersManaged * 3;

            return max(0, $est);
        };

        $calcReachPeriod = function (int $fromDays, int $toDays) use ($managedPosts, $totalFollowersManaged): int {
            if ($totalFollowersManaged === 0 && $managedPosts->isEmpty()) {
                return 0;
            }
            $recent = $managedPosts->whereBetween('created_at', [now()->subDays($fromDays), now()->subDays($toDays)]);
            $est = $recent->sum('likes_count') * 12 + $recent->count() * 85;

            return max(0, $est);
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
            $inviterName = $inv->inviter->name ?? 'Ahnaf Hasan';
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
            abort(404, 'পেইজটি খুঁজে পাওয়া যায়নি বা এটি মুছে ফেলা হয়েছে।');
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
            Page::ROLE_CONTENT_MANAGER,
            Page::ROLE_MODERATOR,
        ], true);

        if ($page->visibility === 'private' && ! $canManage) {
            abort(403, 'এই পেইজটি প্রাইভেট এবং শুধুমাত্র অনুমোদিত মেম্বারদের জন্য উন্মুক্ত।');
        }

        // Posts Stream
        $posts = Post::where('page_id', $page->id)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'published');
            })
            ->with(['user.profile', 'media', 'reactions'])
            ->latest('id')
            ->paginate(15);

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

        // Real Events
        $events = PageEvent::where('page_id', $page->id)
            ->where('status', 'active')
            ->orderBy('start_time')
            ->take(12)
            ->get();

        // Real Products
        $products = PageProduct::where('page_id', $page->id)
            ->where('is_active', true)
            ->latest('id')
            ->take(12)
            ->get();

        $activeTab = $request->query('tab', 'posts');

        return view('pages.show', [
            'currentUser' => $user,
            'page' => $page,
            'isFollowing' => $isFollowing,
            'isOwner' => $isOwner,
            'canManage' => $canManage,
            'userRole' => $userRole ?? ($isOwner ? Page::ROLE_OWNER : null),
            'posts' => $posts,
            'followers' => $followers,
            'mediaItems' => $mediaItems,
            'events' => $events,
            'products' => $products,
            'activeTab' => $activeTab,
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

        // Recent activities related to this page
        $recentActivities = collect();
        $recentFollowers = PageFollower::where('page_id', $page->id)
            ->with(['user.profile'])
            ->latest('id')
            ->take(5)
            ->get();

        foreach ($recentFollowers as $f) {
            $recentActivities->push([
                'type' => 'follower',
                'icon' => 'user-plus',
                'title' => 'New Follower',
                'description' => ($f->user->name ?? 'A user').' followed your page',
                'time_ago' => $f->created_at ? $f->created_at->diffForHumans() : 'Recently',
                'created_at' => $f->created_at ?? now(),
                'user' => $f->user,
            ]);
        }

        $likedPosts = Post::where('page_id', $page->id)
            ->where('likes_count', '>', 0)
            ->latest('updated_at')
            ->take(5)
            ->get();

        foreach ($likedPosts as $p) {
            $recentActivities->push([
                'type' => 'reaction',
                'icon' => 'thumbs-up',
                'title' => 'Post Engagement',
                'description' => 'Someone liked your post "'.Str::limit($p->content, 35).'"',
                'time_ago' => $p->updated_at ? $p->updated_at->diffForHumans() : 'Recently',
                'created_at' => $p->updated_at ?? now(),
                'post_id' => $p->id,
            ]);
        }

        $recentActivities = $recentActivities->sortByDesc('created_at')->values()->take(6);

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

        $isOwner = (int) $page->owner_id === (int) $user->id;
        $userRole = $role ?? ($isOwner ? Page::ROLE_OWNER : Page::ROLE_VIEWER);
        $canManageRoles = $isOwner || in_array($userRole, [Page::ROLE_OWNER, Page::ROLE_ADMIN], true);
        $activeTab = $request->query('tab', 'posts');

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
        if (! $role || in_array($role, [Page::ROLE_VIEWER, Page::ROLE_ANALYST], true)) {
            abort(403, 'You do not have permission to manage posts for this page.');
        }
    }

    /**
     * Create a new post from the Page Posts Dashboard.
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
            'media' => ['nullable', 'file', 'max:51200'],
        ]);

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

        $post = Post::create([
            'user_id' => $user->id,
            'page_id' => $page->id,
            'content' => $validated['content'],
            'audience' => $validated['audience'] ?? 'public',
            'type' => $type,
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'link_preview' => $linkPreview,
        ]);

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $path = $file->store('posts/media', 'public');
            $isVid = str_starts_with($mime, 'video/');

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

            if ($isVid) {
                $post->update(['type' => 'video']);
            } else {
                $post->update(['type' => 'media']);
            }
        }

        if ($status === 'published') {
            $page->increment('posts_count');
        }

        return response()->json([
            'success' => true,
            'message' => $status === 'scheduled' ? 'Post scheduled successfully.' : ($status === 'draft' ? 'Draft saved successfully.' : 'Post published successfully.'),
            'post' => $post->fresh(['media', 'author.profile']),
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
            'content' => ['required', 'string', 'max:10000'],
            'audience' => ['nullable', 'string', 'in:public,followers,only_me'],
            'status' => ['nullable', 'string', 'in:published,draft,scheduled'],
        ]);

        $post->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully.',
            'post' => $post->fresh(['media', 'author.profile']),
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
        $post->delete();

        if ($page->posts_count > 0) {
            $page->decrement('posts_count');
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

        $post->update(['audience' => $validated['audience']]);

        return response()->json([
            'success' => true,
            'audience' => $validated['audience'],
            'message' => 'Audience visibility updated.',
        ]);
    }

    /**
     * React to a post from the web dashboard.
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
     * Update page general details (name, category, bio, contact, website, location).
     */
    public function updateDetails(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'page.edit');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $page->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Page information updated successfully.',
            'page' => $page->fresh(),
        ]);
    }

    /**
     * Upload Avatar or Cover Photo for the page.
     */
    public function uploadBranding(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'page.edit');

        $request->validate([
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
            'cover_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:15360'],
        ]);

        $type = null;
        $url = null;

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('pages/avatars', 'public');
            $url = '/storage/'.$path;
            $page->update(['avatar_url' => $url]);
            $type = 'avatar';
        } elseif ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('pages/covers', 'public');
            $url = '/storage/'.$path;
            $page->update(['cover_image_url' => $url]);
            $type = 'cover';
        }

        if (! $type) {
            return response()->json([
                'success' => false,
                'message' => 'No image file uploaded.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'type' => $type,
            'url' => $url,
            'message' => ucfirst($type).' photo updated successfully.',
        ]);
    }

    /**
     * Update page settings (bio, messaging auto-reply, etc.).
     */
    public function updateSettings(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();
        $this->authorizePageManager($page, $user, 'page.edit');

        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:1000'],
            'settings' => ['nullable', 'array'],
        ]);

        if (array_key_exists('bio', $validated)) {
            $page->bio = $validated['bio'];
        }

        if (! empty($validated['settings'])) {
            $currentSettings = $page->settings ?? [];
            $page->settings = array_replace_recursive($currentSettings, $validated['settings']);
        }

        $page->save();

        return response()->json([
            'success' => true,
            'message' => 'Page settings updated successfully.',
            'page' => $page->fresh(),
        ]);
    }

    /**
     * Invite or add administrator/team member.
     */
    public function inviteTeamMember(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $isOwner = (int) $page->owner_id === (int) $user->id;
        $role = $page->getMemberRole($user->id);
        if (! $isOwner && $role !== Page::ROLE_ADMIN) {
            abort(403, 'Only page owners or administrators can manage team roles.');
        }

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:120'],
            'role' => ['required', 'string', 'in:admin,editor,moderator,analyst'],
        ]);

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

        if ((int) $targetUser->id === (int) $page->owner_id) {
            return response()->json([
                'success' => false,
                'message' => 'This user is already the owner of this page.',
            ], 422);
        }

        $member = PageMember::updateOrCreate(
            ['page_id' => $page->id, 'user_id' => $targetUser->id],
            [
                'role' => $validated['role'],
                'status' => 'active',
                'assigned_by' => $user->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "{$targetUser->name} has been added as {$validated['role']}.",
            'member' => $member->load('user.profile'),
        ]);
    }

    /**
     * Update existing team member's role.
     */
    public function updateTeamMemberRole(Request $request, string $slug, int $memberId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $isOwner = (int) $page->owner_id === (int) $user->id;
        $role = $page->getMemberRole($user->id);
        if (! $isOwner && $role !== Page::ROLE_ADMIN) {
            abort(403, 'Only page owners or administrators can change team roles.');
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,editor,moderator,analyst'],
        ]);

        $member = PageMember::where('page_id', $page->id)->where('id', $memberId)->firstOrFail();
        $member->update(['role' => $validated['role']]);

        return response()->json([
            'success' => true,
            'message' => 'Team member role updated successfully.',
            'member' => $member,
        ]);
    }

    /**
     * Remove team member from the page.
     */
    public function removeTeamMember(Request $request, string $slug, int $memberId): JsonResponse
    {
        $user = $this->resolveUser($request);
        $page = Page::where('slug', $slug)->firstOrFail();

        $isOwner = (int) $page->owner_id === (int) $user->id;
        $role = $page->getMemberRole($user->id);
        if (! $isOwner && $role !== Page::ROLE_ADMIN) {
            abort(403, 'Only page owners or administrators can remove team members.');
        }

        $member = PageMember::where('page_id', $page->id)->where('id', $memberId)->firstOrFail();
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Team member removed successfully.',
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

        return response()->json([
            'success' => true,
            'message' => 'Follower removed from page.',
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
}
