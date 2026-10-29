<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMember;
use App\Models\LiveStream;
use App\Models\MarketplaceProduct;
use App\Models\Media;
use App\Models\Message;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageEvent;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\PageProduct;
use App\Models\Post;
use App\Models\Reel;
use App\Models\Story;
use App\Models\User;
use App\Models\UserFollower;
use App\Models\UsernameHistory;
use App\Models\UserProfile;
use App\Services\Contracts\MessengerServiceInterface;
use App\Services\FeedService;
use App\Services\FriendshipService;
use App\Services\GroupService;
use App\Services\Marketplace\MarketplaceService;
use App\Services\NotificationService;
use App\Services\ProfilePrivacyService;
use App\Services\ProfileService;
use App\Services\StoryEnterpriseService;
use App\Services\Streaming\LiveGatewayService;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class SocialWebController extends Controller
{
    public function __construct(
        protected FeedService $feedService,
        protected FriendshipService $friendshipService,
        protected NotificationService $notificationService,
        protected ProfilePrivacyService $privacyService,
        protected StoryEnterpriseService $storyService,
        protected MessengerServiceInterface $messengerService,
        protected GroupService $groupService,
        protected MarketplaceService $marketplaceService
    ) {}

    /**
     * Resolve the viewer deterministically from web session, cookie token, or local fallback.
     */
    protected function resolveViewer(Request $request): ?User
    {
        $sessionUser = $request->user('web') ?? $request->user();
        $tokenUser = null;

        if ($request->hasCookie('jugajug_token') || $request->hasCookie('bondhoo_token')) {
            $rawToken = (string) ($request->cookie('jugajug_token') ?? $request->cookie('bondhoo_token'));
            $pat = PersonalAccessToken::findToken($rawToken);
            if (! $pat) {
                try {
                    $decrypted = decrypt($rawToken, false);
                    if (is_string($decrypted)) {
                        if (preg_match('/^[a-f0-9]{40}\|(.+)$/', $decrypted, $m)) {
                            $decrypted = $m[1];
                        }
                        $pat = PersonalAccessToken::findToken($decrypted);
                    }
                } catch (\Throwable) {
                    // Ignore decryption failure
                }
            }
            if ($pat && $pat->tokenable instanceof User && (! $pat->expires_at || $pat->expires_at->isFuture())) {
                $tokenUser = $pat->tokenable;
            }
        }

        if ($tokenUser) {
            if (! $sessionUser || (int) $sessionUser->id !== (int) $tokenUser->id) {
                if ($sessionUser) {
                    auth('web')->logout();
                    $request->session()->invalidate();
                }
                auth('web')->login($tokenUser);
                $request->session()->regenerate();
            }

            return $tokenUser;
        }

        if ($sessionUser) {
            return $sessionUser;
        }

        if (app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);

                return $devUser;
            }
        }

        return null;
    }

    /**
     * Display the responsive social network news feed.
     */
    public function feed(Request $request, ?int $highlightPostId = null): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);

        if (! $viewer) {
            return redirect()->route('login');
        }

        $feedPaginator = $this->feedService->getFeed($viewer, 'smart', 15);

        $postsData = collect($feedPaginator->items())->map(function (Post $post) use ($viewer) {
            return $post->toResponseArray($viewer);
        })->values()->all();

        if ($highlightPostId) {
            $highlightPost = Post::find($highlightPostId);
            if ($highlightPost && ! collect($postsData)->contains('id', $highlightPost->id)) {
                array_unshift($postsData, $highlightPost->toResponseArray($viewer));
            }
        }

        $stories = $this->storyService->getActiveFeed($viewer)->toArray();
        $sidebarData = $this->getSidebarData($viewer);

        $reels = Reel::with(['user.profile', 'media', 'musicTrack', 'page'])
            ->where('is_draft', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (Reel $r) => $r->toResponseArray($viewer))
            ->values()
            ->all();

        $activeLiveStreams = LiveStream::whereIn('status', [LiveStream::STATUS_LIVE, LiveStream::STATUS_READY])
            ->with(['user.profile', 'page'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (LiveStream $stream) {
                return [
                    'id' => $stream->id,
                    'title' => $stream->title,
                    'description' => $stream->description,
                    'status' => $stream->status,
                    'viewer_count' => $stream->current_viewers ?? 0,
                    'user' => [
                        'id' => $stream->user?->id,
                        'name' => $stream->user?->name,
                        'username' => $stream->user?->username,
                        'avatar_url' => $stream->user?->profile?->avatar_url ?? $stream->user?->avatar_url,
                    ],
                    'created_at' => $stream->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Feed', [
            'posts' => [
                'data' => $postsData,
                'next_cursor' => $feedPaginator->nextCursor()?->encode(),
            ],
            'stories' => $stories,
            'reels' => $reels,
            'activeLiveStreams' => $activeLiveStreams,
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
            'highlightPostId' => $highlightPostId,
        ]);
    }

    /**
     * Display the dedicated Reels immersive feed page.
     */
    public function reels(Request $request, ?int $id = null): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);

        if (! $viewer) {
            return redirect()->route('login');
        }

        $tab = $request->query('tab', 'for_you'); // 'for_you', 'following', 'saved', 'my_reels'

        $query = Reel::with(['user.profile', 'media', 'reactions', 'musicTrack', 'page', 'saves'])
            ->where('is_draft', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($tab === 'saved') {
            $query->whereHas('saves', function ($sq) use ($viewer) {
                $sq->where('user_id', $viewer->id);
            });
        } elseif ($tab === 'my_reels') {
            $query->where('user_id', $viewer->id);
        } elseif ($tab === 'following') {
            $friendIds = Friendship::where('user_id', $viewer->id)
                ->where('status', Friendship::STATUS_ACCEPTED)
                ->pluck('friend_id')
                ->all();
            $followingIds = UserFollower::where('follower_id', $viewer->id)
                ->pluck('user_id')
                ->all();
            $allFollowedIds = array_unique(array_merge($friendIds, $followingIds, [$viewer->id]));
            $query->whereIn('user_id', $allFollowedIds);
        } else {
            // For You / Public Feed
            $query->where('privacy', 'public');
        }

        $reelsPaginator = $query->latest('id')->paginate(15);

        $reelsData = collect($reelsPaginator->items())->map(function (Reel $reel) use ($viewer) {
            return $reel->toResponseArray($viewer);
        })->values()->all();

        // If a specific reel ID was requested in the URL, ensure it is present at the front of the list
        if ($id) {
            $highlightReel = Reel::with(['user.profile', 'media', 'reactions', 'musicTrack', 'page', 'saves'])->find($id);
            if ($highlightReel) {
                $highlightArr = $highlightReel->toResponseArray($viewer);
                $existingIndex = collect($reelsData)->search(fn ($r) => $r['id'] === $highlightReel->id);
                if ($existingIndex !== false) {
                    $item = array_splice($reelsData, $existingIndex, 1)[0];
                    array_unshift($reelsData, $item);
                } else {
                    array_unshift($reelsData, $highlightArr);
                }
            }
        }

        $sidebarData = $this->getSidebarData($viewer);

        return Inertia::render('Reels', [
            'reels' => [
                'data' => $reelsData,
                'current_page' => $reelsPaginator->currentPage(),
                'last_page' => $reelsPaginator->lastPage(),
                'total' => $reelsPaginator->total(),
            ],
            'initialReelId' => $id,
            'activeTab' => $tab,
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Display the user's social profile.
     */
    public function profile(Request $request, string $username): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);

        $cleanUsername = ltrim(trim($username), '@');
        $targetUser = User::where('username', strtolower($cleanUsername))
            ->with(['profile'])
            ->first();

        if (! $targetUser && is_numeric($cleanUsername)) {
            $targetUser = User::with(['profile'])->find((int) $cleanUsername);
            if ($targetUser) {
                return redirect()->route('profile.u', ['username' => $targetUser->username], 301);
            }
        }

        if (! $targetUser) {
            $history = UsernameHistory::where('username', strtolower($cleanUsername))->latest('id')->first();
            if ($history && $history->user) {
                return redirect()->route('profile.u', ['username' => $history->user->username], 301);
            }
            abort(404, 'User profile not found.');
        }

        if ($viewer && $this->privacyService->isBlocked($targetUser, $viewer)) {
            abort(404, 'User profile not found.');
        }

        $isOwner = (bool) ($viewer && (int) $viewer->id === (int) $targetUser->id);

        $relationship = $viewer
            ? strtolower(
                $viewer->id === $targetUser->id
                    ? 'self'
                    : $this->friendshipService->getRelationshipState($viewer, $targetUser)
            )
            : 'none';

        $userPostsPaginator = $this->feedService->getUserPosts($targetUser, $viewer, 15);

        $postsData = collect($userPostsPaginator->items())->map(function (Post $post) use ($viewer) {
            return $post->toResponseArray($viewer);
        })->values()->all();

        $friendsPaginator = $this->friendshipService->getFriends($targetUser, 12);
        $friendsList = collect($friendsPaginator->items())->map(function (User $friend) use ($viewer) {
            $data = $this->transformUser($friend);
            $data['mutual_count'] = ($viewer && $viewer->id !== $friend->id)
                ? $this->friendshipService->getMutualCount($viewer, $friend)
                : 0;

            return $data;
        })->values()->all();

        $sidebarData = $viewer
            ? $this->getSidebarData($viewer)
            : [
                'pendingRequests' => [],
                'contacts' => [],
                'unreadCount' => 0,
                'unreadMessagesCount' => 0,
            ];

        $profile = $targetUser->profile;
        $profileDetails = [
            'work' => $profile?->work ?: $profile?->headline,
            'education' => $profile?->education,
            'city' => $profile?->city ?: $profile?->location,
            'hometown' => $profile?->hometown,
            'relationship_status' => $profile?->relationship_status,
            'birth_date' => $profile?->birth_date ? $profile->birth_date->format('M d, Y') : null,
            'gender' => $profile?->gender,
            'website' => $profile?->website,
            'interests' => is_array($profile?->interests) ? $profile->interests : [],
            'joined_date' => $targetUser->created_at ? $targetUser->created_at->format('F Y') : null,
        ];

        $mutualCount = ($viewer && $viewer->id !== $targetUser->id)
            ? $this->friendshipService->getMutualCount($viewer, $targetUser)
            : 0;

        $isFollowing = ($viewer && $viewer->id !== $targetUser->id)
            ? $viewer->isFollowing($targetUser)
            : false;

        $photos = app(ProfileService::class)->getUserPhotos($targetUser, $viewer, 12);
        $videos = app(ProfileService::class)->getUserVideos($targetUser, $viewer, 12);

        view()->share([
            'isOwner' => $isOwner,
            'viewer' => $viewer,
            'user' => $targetUser,
            'profile' => $profile,
            'authToken' => null,
        ]);

        return Inertia::render('Profile', [
            'user' => $this->transformUser($targetUser),
            'relationship' => $relationship,
            'is_following' => $isFollowing,
            'profile_details' => $profileDetails,
            'mutual_friends_count' => $mutualCount,
            'photos' => $photos,
            'videos' => $videos,
            'posts' => [
                'data' => $postsData,
                'next_cursor' => $userPostsPaginator->nextCursor()?->encode(),
            ],
            'friends' => $friendsList,
            'stats' => [
                'posts_count' => Post::where('user_id', $targetUser->id)->count(),
                'friends_count' => count($targetUser->getFriendIds()),
                'followers_count' => $targetUser->followers()->count(),
            ],
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'] ?? 0,
        ]);
    }

    /**
     * Display friends management page.
     */
    public function friends(Request $request): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);

        if (! $viewer) {
            return redirect()->route('login');
        }

        $pendingRequestsPaginator = $this->friendshipService->getPendingRequests($viewer, 20);
        $requestsData = collect($pendingRequestsPaginator->items())->map(function ($req) {
            $sender = $req->user ?? $req->sender;

            return [
                'id' => $req->id,
                'user_id' => $req->user_id,
                'sender' => $sender ? $this->transformUser($sender) : null,
                'created_at' => $req->created_at?->toIso8601String(),
            ];
        })->values()->all();

        $friendsPaginator = $this->friendshipService->getFriends($viewer, 30);
        $friendsData = collect($friendsPaginator->items())->map(function (User $friend) {
            return $this->transformUser($friend);
        })->values()->all();

        $suggestionsCollection = $this->friendshipService->getSuggestions($viewer, 12);
        $suggestionsData = $suggestionsCollection->map(function (User $sugg) {
            return $this->transformUser($sugg);
        })->values()->all();

        $sidebarData = $this->getSidebarData($viewer);

        return Inertia::render('Friends', [
            'requests' => $requestsData,
            'friends' => $friendsData,
            'suggestions' => $suggestionsData,
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'unreadCount' => $sidebarData['unreadCount'],
        ]);
    }

    /**
     * Display notifications center.
     */
    public function notifications(Request $request): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);

        if (! $viewer) {
            return redirect()->route('login');
        }

        $notificationsPaginator = $this->notificationService->getNotifications($viewer, 25);
        $notificationsData = collect($notificationsPaginator->items())->map(function ($n) {
            $data = is_string($n->data) ? json_decode($n->data, true) : (array) $n->data;

            return [
                'id' => (string) $n->id,
                'type' => (string) $n->type,
                'data' => (array) $data,
                'message' => $data['message'] ?? null,
                'read_at' => $n->read_at ? (string) $n->read_at : null,
                'created_at' => $n->created_at ? (string) $n->created_at : null,
            ];
        })->values()->all();

        $sidebarData = $this->getSidebarData($viewer);

        return Inertia::render('Notifications', [
            'notifications' => [
                'data' => $notificationsData,
            ],
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'unreadCount' => $sidebarData['unreadCount'],
        ]);
    }

    /**
     * Display the Enterprise Pages Dashboard & Discover Directory (Vue 3 / Inertia).
     */
    public function pages(Request $request): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);

        $sidebarData = $viewer ? $this->getSidebarData($viewer) : [
            'pendingRequests' => [],
            'contacts' => [],
            'unreadCount' => 0,
            'unreadMessagesCount' => 0,
            'suggestions' => [],
            'upcomingEvents' => [],
            'activeGroupCount' => 0,
            'userPagesCount' => 0,
        ];

        // 1. Managed Pages (owned or active team member)
        $managedPagesCollection = $viewer ? Page::where(function ($q) use ($viewer) {
            $q->where('owner_id', $viewer->id)
                ->orWhereHas('members', fn ($mq) => $mq->where('user_id', $viewer->id)->where('status', 'active'));
        })
            ->where('status', '!=', Page::STATUS_TRASH)
            ->with(['owner', 'members' => fn ($mq) => $mq->where('user_id', $viewer->id)])
            ->latest('id')
            ->get() : collect();

        $managedPageIds = $managedPagesCollection->pluck('id')->toArray();

        // 2. Followed Pages
        $followedPagesCollection = $viewer ? Page::whereHas('followers', fn ($q) => $q->where('user_id', $viewer->id))
            ->where('status', '!=', Page::STATUS_TRASH)
            ->with('owner')
            ->latest('id')
            ->get() : collect();

        // 3. Pending Invitations
        $pendingInvitations = $viewer ? PageMember::where('user_id', $viewer->id)
            ->where('status', 'invited')
            ->with(['page.owner', 'inviter'])
            ->latest('id')
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'page_id' => $inv->page_id,
                'role' => $inv->role,
                'status' => $inv->status,
                'invitation_token' => $inv->invitation_token,
                'created_at' => $inv->created_at ? $inv->created_at->diffForHumans() : 'Recently',
                'page' => $inv->page ? [
                    'id' => $inv->page->id,
                    'name' => $inv->page->name,
                    'slug' => $inv->page->slug,
                    'avatar_url' => $inv->page->avatar_url,
                    'category' => $inv->page->category,
                    'followers_count' => (int) $inv->page->followers_count,
                    'owner' => $inv->page->owner ? [
                        'id' => $inv->page->owner->id,
                        'name' => $inv->page->owner->name,
                    ] : null,
                ] : null,
                'inviter' => $inv->inviter ? [
                    'id' => $inv->inviter->id,
                    'name' => $inv->inviter->name,
                ] : null,
            ]) : collect();

        // 4. Overall metrics across all managed pages
        $totalFollowersManaged = (int) $managedPagesCollection->sum('followers_count');
        $totalPostsManaged = (int) $managedPagesCollection->sum('posts_count');
        $managedPosts = ! empty($managedPageIds) ? Post::whereIn('page_id', $managedPageIds)->get() : collect();
        $totalEngagement = (int) ($managedPosts->sum('likes_count') + $managedPosts->sum('comments_count') + $managedPosts->sum('shares_count'));

        // Growth metrics (past 30d vs previous 30d)
        $recentFollowers = ! empty($managedPageIds) ? PageFollower::whereIn('page_id', $managedPageIds)->where('created_at', '>=', now()->subDays(30))->count() : 0;
        $prevFollowers = ! empty($managedPageIds) ? PageFollower::whereIn('page_id', $managedPageIds)->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count() : 0;
        $followersGrowthPct = $prevFollowers > 0
            ? (int) round((($recentFollowers - $prevFollowers) / $prevFollowers) * 100)
            : ($recentFollowers > 0 ? 12 : 0);

        $recentPosts = ! empty($managedPageIds) ? Post::whereIn('page_id', $managedPageIds)->where('created_at', '>=', now()->subDays(30))->count() : 0;
        $prevPosts = ! empty($managedPageIds) ? Post::whereIn('page_id', $managedPageIds)->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count() : 0;
        $postsGrowthPct = $prevPosts > 0
            ? (int) round((($recentPosts - $prevPosts) / $prevPosts) * 100)
            : ($recentPosts > 0 ? 8 : 0);

        $recentEng = ! empty($managedPageIds) ? Post::whereIn('page_id', $managedPageIds)->where('created_at', '>=', now()->subDays(30))->get()->sum(fn ($p) => $p->likes_count + $p->comments_count + $p->shares_count) : 0;
        $prevEng = ! empty($managedPageIds) ? Post::whereIn('page_id', $managedPageIds)->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->get()->sum(fn ($p) => $p->likes_count + $p->comments_count + $p->shares_count) : 0;
        $engagementGrowthPct = $prevEng > 0
            ? (int) round((($recentEng - $prevEng) / $prevEng) * 100)
            : ($recentEng > 0 ? 15 : 0);

        // 5. Selected Page for Management Dashboard
        $selectedPage = null;
        $targetSlugOrId = $request->query('page');
        if ($targetSlugOrId) {
            $selectedPage = $managedPagesCollection->first(fn ($p) => $p->slug === $targetSlugOrId || (string) $p->id === (string) $targetSlugOrId);
            if (! $selectedPage) {
                $selectedPage = Page::where('slug', $targetSlugOrId)
                    ->orWhere('id', is_numeric($targetSlugOrId) ? (int) $targetSlugOrId : 0)
                    ->first();
            }
        }
        if (! $selectedPage) {
            $selectedPage = $managedPagesCollection->first();
        }

        $selectedPageData = null;
        if ($selectedPage) {
            $isOwner = (int) $selectedPage->owner_id === (int) $viewer->id;
            $memberRole = $selectedPage->getMemberRole($viewer->id);

            // Recent posts on selected page
            $pageRecentPosts = Post::where('page_id', $selectedPage->id)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'published');
                })
                ->with(['author.profile', 'media', 'reactions'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn (Post $p) => $p->toResponseArray($viewer))
                ->values()
                ->all();

            // Top content by engagement/reach
            $topContent = Post::where('page_id', $selectedPage->id)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'published');
                })
                ->orderByDesc('likes_count')
                ->take(3)
                ->get()
                ->map(function (Post $p) use ($selectedPage) {
                    $reach = max(1, (int) ($p->likes_count * 12 + $p->comments_count * 25 + ($selectedPage->followers_count > 0 ? $selectedPage->followers_count * 0.4 : 50)));
                    $reachStr = $reach >= 1000 ? round($reach / 1000, 1).'K reach' : $reach.' reach';

                    return [
                        'id' => $p->id,
                        'content' => Str::limit($p->content, 60),
                        'reach' => $reach,
                        'formatted_reach' => $reachStr,
                        'likes_count' => $p->likes_count,
                        'comments_count' => $p->comments_count,
                        'image' => $p->media->first()?->url ?? null,
                        'created_at' => $p->created_at ? $p->created_at->diffForHumans() : 'Recently',
                    ];
                })
                ->values()
                ->all();

            // Estimated Reach and Page Views
            $pagePosts = Post::where('page_id', $selectedPage->id)->get();
            $pageEngagement = (int) ($pagePosts->sum('likes_count') + $pagePosts->sum('comments_count') + $pagePosts->sum('shares_count'));
            $pageReach = max(1, (int) ($selectedPage->followers_count * 0.45 + $pagePosts->sum('likes_count') * 8 + $pagePosts->count() * 40));
            $pageViews = max(1, (int) ($pageReach * 0.68 + 15));

            // Time series chart (Last 7 days data points)
            $trendData = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i);
                $dayStr = $day->format('M d');
                $c = PageFollower::where('page_id', $selectedPage->id)->where('created_at', '<=', $day->endOfDay())->count();
                if ($c === 0 && $selectedPage->followers_count > 0) {
                    $scale = 0.85 + ((6 - $i) * 0.025);
                    $c = min($selectedPage->followers_count, max(1, (int) ($selectedPage->followers_count * $scale)));
                }
                $r = max(1, (int) ($c * 0.4 + ($i * 12)));
                $trendData[] = [
                    'date' => $dayStr,
                    'followers' => $c,
                    'reach' => $r,
                    'engagement' => max(0, (int) ($r * 0.12)),
                ];
            }

            // Team members
            $teamMembers = $selectedPage->members()->with(['user.profile', 'inviter'])->get()->map(function ($m) {
                return [
                    'id' => $m->id,
                    'user_id' => $m->user_id,
                    'role' => $m->role,
                    'status' => $m->status,
                    'joined_at' => $m->joined_at ? $m->joined_at->format('M d, Y') : ($m->created_at ? $m->created_at->format('M d, Y') : 'Recently'),
                    'user' => $m->user ? [
                        'id' => $m->user->id,
                        'name' => $m->user->name,
                        'username' => $m->user->username,
                        'email' => $m->user->email,
                        'avatar_url' => $m->user->profile?->avatar_url ? UserProfile::normalizeStorageUrl($m->user->profile->avatar_url) : $m->user->avatar_url,
                    ] : null,
                ];
            })->values()->all();

            $selectedPageData = [
                'id' => $selectedPage->id,
                'name' => $selectedPage->name,
                'slug' => $selectedPage->slug,
                'username' => $selectedPage->username,
                'category' => $selectedPage->category ?? 'General',
                'bio' => $selectedPage->bio,
                'description' => $selectedPage->description,
                'avatar_url' => $selectedPage->avatar_url,
                'cover_image_url' => $selectedPage->cover_image_url,
                'followers_count' => (int) $selectedPage->followers_count,
                'posts_count' => (int) $selectedPage->posts_count,
                'is_verified' => (bool) $selectedPage->is_verified,
                'status' => $selectedPage->status,
                'visibility' => $selectedPage->visibility,
                'website' => $selectedPage->website,
                'email' => $selectedPage->email,
                'phone' => $selectedPage->phone,
                'address' => $selectedPage->address,
                'city' => $selectedPage->city,
                'country' => $selectedPage->country,
                'cta_type' => $selectedPage->cta_type ?? 'Learn More',
                'cta_url' => $selectedPage->cta_url,
                'settings' => $selectedPage->settings ?? [],
                'is_owner' => $isOwner,
                'user_role' => $memberRole ?? ($isOwner ? 'owner' : null),
                'permissions' => [
                    'can_edit' => $selectedPage->hasPermission($viewer->id, 'page.edit'),
                    'can_delete' => $isOwner,
                    'can_post' => $selectedPage->hasPermission($viewer->id, 'posts.create'),
                    'can_settings' => $selectedPage->hasPermission($viewer->id, 'page.settings'),
                    'can_team' => $selectedPage->hasPermission($viewer->id, 'team.manage'),
                    'can_moderate' => $selectedPage->hasPermission($viewer->id, 'moderation.manage') || $selectedPage->hasPermission($viewer->id, 'comments.moderate'),
                    'can_analytics' => $selectedPage->hasPermission($viewer->id, 'analytics.view') || $selectedPage->hasPermission($viewer->id, 'page.view'),
                ],
                'stats' => [
                    'followers' => (int) $selectedPage->followers_count,
                    'followers_growth' => '+12%',
                    'reach' => $pageReach,
                    'reach_growth' => '+8%',
                    'engagement' => $pageEngagement,
                    'engagement_growth' => '+15%',
                    'views' => $pageViews,
                    'views_growth' => '+10%',
                ],
                'recent_posts' => $pageRecentPosts,
                'top_content' => $topContent,
                'trend_data' => $trendData,
                'team_members' => $teamMembers,
            ];
        }

        // 6. Directory / Discovery Pages Query
        $activeTab = $request->query('tab', 'all');
        $search = $request->query('q') ?? $request->query('search');
        $selectedCategory = $request->query('category');
        $sort = $request->query('sort', 'popular');

        $query = Page::where('status', '!=', Page::STATUS_TRASH)->with('owner');

        if ($activeTab === 'my_pages') {
            $query->whereIn('id', $managedPageIds);
        } elseif ($activeTab === 'followed') {
            if ($viewer) {
                $query->whereHas('followers', fn ($q) => $q->where('user_id', $viewer->id));
            } else {
                $query->whereRaw('1 = 0');
            }
        } else {
            // 'all' tab: public active pages
            $query->where(function ($q) {
                $q->where('visibility', 'public')
                    ->orWhereNull('visibility');
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('bio', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($selectedCategory && $selectedCategory !== 'all') {
            $query->where('category', $selectedCategory);
        }

        if ($sort === 'recent') {
            $query->latest('id');
        } elseif ($sort === 'name') {
            $query->orderBy('name');
        } else {
            $query->orderByDesc('followers_count')->latest('id');
        }

        $allPagesPaginator = $query->paginate(12)->withQueryString();

        $allPages = collect($allPagesPaginator->items())->map(function (Page $p) use ($viewer, $managedPageIds) {
            $isFollowing = $viewer ? $p->isFollowedBy($viewer->id) : false;
            $isOwner = $viewer ? (int) $p->owner_id === (int) $viewer->id : false;
            $role = ($viewer && in_array($p->id, $managedPageIds)) ? ($isOwner ? 'owner' : $p->getMemberRole($viewer->id)) : null;

            return [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'username' => $p->username,
                'category' => $p->category ?? 'General',
                'bio' => $p->bio,
                'avatar_url' => $p->avatar_url,
                'cover_image_url' => $p->cover_image_url,
                'followers_count' => (int) $p->followers_count,
                'posts_count' => (int) $p->posts_count,
                'is_verified' => (bool) $p->is_verified,
                'visibility' => $p->visibility,
                'is_following' => $isFollowing,
                'is_owner' => $isOwner,
                'user_role' => $role,
            ];
        })->values()->all();

        // 7. Categories
        $dbCategories = Page::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->pluck('category')
            ->toArray();

        $defaultCategories = ['Technology', 'Business', 'Education', 'Entertainment', 'Food & Drink', 'Sports', 'Fashion', 'Community', 'Travel & Adventure', 'Photography'];
        $categories = array_values(array_unique(array_filter(array_merge($defaultCategories, $dbCategories))));

        return Inertia::render('Pages/Index', [
            'managedPages' => $managedPagesCollection->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'username' => $p->username,
                'category' => $p->category ?? 'General',
                'avatar_url' => $p->avatar_url,
                'cover_image_url' => $p->cover_image_url,
                'followers_count' => (int) $p->followers_count,
                'posts_count' => (int) $p->posts_count,
                'is_verified' => (bool) $p->is_verified,
                'is_owner' => $viewer ? (int) $p->owner_id === (int) $viewer->id : false,
            ])->values()->all(),
            'followedPages' => $followedPagesCollection->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'username' => $p->username,
                'category' => $p->category ?? 'General',
                'avatar_url' => $p->avatar_url,
                'followers_count' => (int) $p->followers_count,
                'is_verified' => (bool) $p->is_verified,
            ])->values()->all(),
            'selectedPage' => $selectedPageData,
            'summaryStats' => [
                'total_managed_pages' => count($managedPageIds),
                'total_followers' => $totalFollowersManaged,
                'total_posts' => $totalPostsManaged,
                'total_engagement' => $totalEngagement,
                'followers_growth' => ($followersGrowthPct >= 0 ? '+' : '').$followersGrowthPct.'%',
                'posts_growth' => ($postsGrowthPct >= 0 ? '+' : '').$postsGrowthPct.'%',
                'engagement_growth' => ($engagementGrowthPct >= 0 ? '+' : '').$engagementGrowthPct.'%',
            ],
            'pages' => [
                'data' => $allPages,
                'current_page' => $allPagesPaginator->currentPage(),
                'last_page' => $allPagesPaginator->lastPage(),
                'total' => $allPagesPaginator->total(),
                'per_page' => $allPagesPaginator->perPage(),
            ],
            'categories' => $categories,
            'pendingInvitations' => $pendingInvitations,
            'filters' => [
                'tab' => $activeTab,
                'q' => $search ?? '',
                'category' => $selectedCategory ?? 'all',
                'sort' => $sort,
            ],
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Display the SocialHub Create Page wizard interface (Vue 3 / Inertia).
     */
    public function createPage(Request $request): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);

        if (! $viewer) {
            return redirect()->route('login');
        }

        $sidebarData = $this->getSidebarData($viewer);

        $categories = PageCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'page_type_id'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'page_type_id' => $c->page_type_id,
            ]);

        if ($categories->isEmpty()) {
            try {
                app(PageTaxonomySeeder::class)->run();
                $categories = PageCategory::where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['id', 'name', 'slug', 'page_type_id'])
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                        'page_type_id' => $c->page_type_id,
                    ]);
            } catch (\Throwable $e) {
                // Ignore gracefully
            }
        }

        return Inertia::render('Pages/Create', [
            'categories' => $categories,
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Display Enterprise Page Profile / Wall (Vue 3 / Inertia).
     */
    public function pageProfile(Request $request, string $slug): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);

        $page = Page::where(function ($q) use ($slug) {
            $q->where('slug', $slug)
                ->orWhere('username', $slug)
                ->orWhere('id', is_numeric($slug) ? (int) $slug : 0);
        })
            ->where('status', '!=', Page::STATUS_TRASH)
            ->with(['owner.profile', 'members.user.profile'])
            ->first();

        if (! $page) {
            if ($slug === '{slug}' || $slug === '%7Bslug%7D' || str_contains($slug, '{') || str_contains($slug, '}')) {
                $fallback = Page::where('slug', 'socialhub-official')
                    ->orWhere('slug', 'bondhoo-official')
                    ->first() ?? Page::where('status', '!=', Page::STATUS_TRASH)->first();
                if ($fallback) {
                    return redirect()->route('app.pages.show', ['slug' => $fallback->slug]);
                }
            }

            abort(404, 'পেইজটি খুঁজে পাওয়া যায়নি বা এটি মুছে ফেলা হয়েছে।');
        }

        $isOwner = $viewer && ((int) $page->owner_id === (int) $viewer->id);
        $userRole = $viewer ? $page->getMemberRole($viewer->id) : null;
        $canManage = $isOwner || in_array($userRole, [
            Page::ROLE_OWNER,
            Page::ROLE_ADMIN,
            Page::ROLE_MANAGER,
            Page::ROLE_CONTENT_MANAGER,
            Page::ROLE_MODERATOR,
        ], true);

        $isFollowing = false;
        if ($viewer) {
            $isFollowing = PageFollower::where('page_id', $page->id)
                ->where('user_id', $viewer->id)
                ->exists();
        }

        if ($page->visibility === 'private' && ! $canManage && ! $isFollowing) {
            abort(403, 'এই পেইজটি প্রাইভেট এবং শুধুমাত্র অনুমোদিত মেম্বারদের জন্য উন্মুক্ত।');
        }

        $canPost = $canManage || ($viewer && $page->hasPermission($viewer->id, 'posts.create'));

        // Posts Query
        $postsQuery = Post::where('page_id', $page->id)
            ->where(function ($q) use ($canManage) {
                if (! $canManage) {
                    $q->where(function ($sq) {
                        $sq->whereNull('status')->orWhere('status', 'published');
                    });
                }
            })
            ->where(function ($q) use ($canManage, $isFollowing) {
                if (! $canManage) {
                    if ($isFollowing) {
                        $q->whereIn('audience', ['public', 'followers']);
                    } else {
                        $q->where('audience', 'public');
                    }
                }
            })
            ->with([
                'user.profile',
                'media',
                'reactions.user',
                'comments' => fn ($cq) => $cq->with(['user.profile', 'reactions'])->latest()->take(10),
            ])
            ->orderByDesc('is_pinned')
            ->latest('id');

        $postsPaginator = $postsQuery->paginate(15);
        $postsData = collect($postsPaginator->items())->map(function (Post $p) use ($viewer, $page) {
            $arr = $p->toResponseArray($viewer);
            $arr['page'] = [
                'id' => $page->id,
                'name' => $page->name,
                'slug' => $page->slug,
                'avatar_url' => $page->avatar_url,
                'is_verified' => (bool) $page->is_verified,
            ];

            return $arr;
        })->values()->all();

        // Followers
        $followers = PageFollower::where('page_id', $page->id)
            ->with(['user.profile'])
            ->latest('id')
            ->take(40)
            ->get()
            ->map(function ($pf) {
                $u = $pf->user;

                return [
                    'id' => $pf->id,
                    'user_id' => $pf->user_id,
                    'followed_at' => $pf->created_at?->format('M d, Y') ?? 'Recently',
                    'user' => [
                        'id' => $u?->id,
                        'name' => $u?->name ?? 'User',
                        'username' => $u?->username ?? 'user',
                        'avatar_url' => $u?->profile?->avatar_url ? UserProfile::normalizeStorageUrl($u->profile->avatar_url) : $u?->avatar_url,
                    ],
                ];
            })
            ->values()
            ->all();

        // Photos & Videos from Page Posts
        $postIds = Post::where('page_id', $page->id)
            ->where(function ($q) use ($canManage) {
                if (! $canManage) {
                    $q->whereNull('status')->orWhere('status', 'published');
                }
            })
            ->pluck('id');

        $photos = Media::where('mediable_type', Post::class)
            ->whereIn('mediable_id', $postIds)
            ->where('mime_type', 'like', 'image/%')
            ->latest('id')
            ->take(36)
            ->get()
            ->map(function (Media $m) {
                return [
                    'id' => $m->id,
                    'url' => $m->url,
                    'original_url' => $m->getVariantUrl('original') ?: $m->url,
                    'preview_url' => $m->getVariantUrl('medium') ?: $m->url,
                    'post_id' => $m->mediable_id,
                    'name' => $m->name,
                    'created_at' => $m->created_at?->format('M d, Y'),
                ];
            })
            ->values()
            ->all();

        $videos = Media::where('mediable_type', Post::class)
            ->whereIn('mediable_id', $postIds)
            ->where('mime_type', 'like', 'video/%')
            ->latest('id')
            ->take(18)
            ->get()
            ->map(function (Media $m) {
                return [
                    'id' => $m->id,
                    'url' => $m->url,
                    'post_id' => $m->mediable_id,
                    'name' => $m->name,
                    'mime_type' => $m->mime_type,
                    'created_at' => $m->created_at?->format('M d, Y'),
                ];
            })
            ->values()
            ->all();

        // Events
        $events = PageEvent::where('page_id', $page->id)
            ->where('status', 'active')
            ->orderBy('start_time')
            ->take(12)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'description' => $e->description,
                'start_time' => $e->start_time?->format('M d, Y g:i A'),
                'location' => $e->location,
                'cover_url' => $e->cover_url,
                'rsvps_count' => $e->rsvps_count ?? 0,
            ])
            ->values()
            ->all();

        // Products
        $products = PageProduct::where('page_id', $page->id)
            ->where('is_active', true)
            ->latest('id')
            ->take(12)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->name ?? $p->title,
                'price' => $p->price,
                'currency' => $p->currency ?? 'BDT',
                'image_url' => $p->image_url,
                'stock' => $p->stock,
            ])
            ->values()
            ->all();

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
            ->get()
            ->map(fn ($rp) => $rp->toResponseArray($viewer))
            ->values()
            ->all();

        if (empty($relatedPages)) {
            $relatedPages = Page::where('id', '!=', $page->id)
                ->where('status', Page::STATUS_ACTIVE)
                ->latest('followers_count')
                ->take(5)
                ->get()
                ->map(fn ($rp) => $rp->toResponseArray($viewer))
                ->values()
                ->all();
        }

        // Active Stories (Unexpired, created in last 24h)
        $activeStories = Story::where(function ($q) use ($page) {
            $q->where('page_id', $page->id)
                ->orWhere(function ($sq) {
                    $sq->whereNull('page_id')
                        ->where('is_expired', false)
                        ->where('expires_at', '>', now());
                });
        })
            ->where('is_draft', false)
            ->where('is_expired', false)
            ->where('expires_at', '>', now())
            ->with(['user.profile', 'page', 'media'])
            ->latest('id')
            ->take(12)
            ->get()
            ->map(fn (Story $s) => $s->toResponseArray($viewer))
            ->values()
            ->all();

        // Published Reels
        $reels = Reel::where(function ($q) use ($page) {
            $q->where('page_id', $page->id)
                ->orWhere('status', Reel::STATUS_READY);
        })
            ->where('status', Reel::STATUS_READY)
            ->where('is_draft', false)
            ->with(['user.profile', 'page', 'media'])
            ->latest('id')
            ->take(12)
            ->get()
            ->map(fn (Reel $r) => $r->toResponseArray($viewer))
            ->values()
            ->all();

        // Active Live Stream for Page / Owner
        $activeLiveStream = LiveStream::where(function ($q) use ($page) {
            $q->where('page_id', $page->id)
                ->orWhere(function ($sq) use ($page) {
                    $sq->where('user_id', $page->owner_id)->whereNull('page_id');
                });
        })
            ->whereIn('status', [LiveStream::STATUS_LIVE, LiveStream::STATUS_READY, LiveStream::STATUS_PREPARING])
            ->with(['user.profile', 'page'])
            ->latest('id')
            ->first();

        $activeLiveStreamData = null;
        if ($activeLiveStream) {
            $gateway = app(LiveGatewayService::class);
            $isBroadcaster = $viewer && in_array((int) $viewer->id, [(int) $activeLiveStream->user_id, (int) $page->owner_id], true);
            $activeLiveStreamData = [
                'id' => $activeLiveStream->id,
                'channel_id' => $activeLiveStream->channel_id,
                'title' => $activeLiveStream->title,
                'description' => $activeLiveStream->description,
                'status' => $activeLiveStream->status,
                'viewers_count' => $activeLiveStream->viewers_count,
                'playback_url' => $activeLiveStream->playback_url,
                'sfu_room_id' => $activeLiveStream->sfu_room_id,
                'started_at' => $activeLiveStream->started_at?->diffForHumans() ?? 'সদ্য শুরু হয়েছে',
                'can_broadcast' => $isBroadcaster,
                'host_token' => $isBroadcaster ? ($activeLiveStream->sfu_host_token ?: $gateway->generateHostToken($activeLiveStream, $viewer)) : null,
            ];
        }

        // 7-Day Performance Metrics & Sparkline Trend (For Page Insights widget)
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
            'reach_raw' => $reach7d,
            'reach_growth' => ($reachGrowthNum >= 0 ? '+' : '').$reachGrowthNum.'%',
            'engagement' => $eng7d >= 1000 ? round($eng7d / 1000, 1).'K' : (string) $eng7d,
            'engagement_raw' => $eng7d,
            'engagement_growth' => ($engGrowthNum >= 0 ? '+' : '').$engGrowthNum.'%',
            'new_followers' => $newFollowers7d >= 1000 ? round($newFollowers7d / 1000, 1).'K' : (string) $newFollowers7d,
            'new_followers_raw' => $newFollowers7d,
            'followers_growth' => ($followersGrowthNum >= 0 ? '+' : '').$followersGrowthNum.'%',
            'chart_points' => $trendPoints,
        ];

        // Recent Page Activity
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
                'time_ago' => $rf->created_at?->diffForHumans() ?? '2 hours ago',
            ];
        }

        if (count($recentActivity) < 3) {
            $recentComments = Comment::whereIn('commentable_id', $postIds)
                ->where('commentable_type', Post::class)
                ->with('user.profile')
                ->latest('id')
                ->take(3)
                ->get();

            foreach ($recentComments as $rc) {
                $uName = $rc->user?->name ?? 'A follower';
                $recentActivity[] = [
                    'id' => 'comment_'.$rc->id,
                    'type' => 'share',
                    'user_name' => $uName,
                    'avatar_url' => $rc->user?->profile?->avatar_url ? UserProfile::normalizeStorageUrl($rc->user->profile->avatar_url) : $rc->user?->avatar_url,
                    'text' => "{$uName} commented on your post.",
                    'time_ago' => $rc->created_at?->diffForHumans() ?? '4 hours ago',
                ];
                if (count($recentActivity) >= 3) {
                    break;
                }
            }
        }

        // Quick Insights for Manager
        $insights = null;
        if ($canManage) {
            $pagePosts = Post::where('page_id', $page->id)->get();
            $totalEng = (int) ($pagePosts->sum('likes_count') + $pagePosts->sum('comments_count') + $pagePosts->sum('shares_count'));
            $recentEng = Post::where('page_id', $page->id)->where('created_at', '>=', now()->subDays(30))->get()
                ->sum(fn ($p) => $p->likes_count + $p->comments_count + $p->shares_count);
            $reachEstimate = (int) ($page->followers_count * 1.5 + $totalEng * 8);

            $insights = [
                'followers_count' => $page->followers_count,
                'posts_count' => $page->posts_count,
                'total_engagement' => $totalEng,
                'recent_engagement_30d' => (int) $recentEng,
                'reach_estimate' => max($reachEstimate, 100),
            ];
        }

        // Team Members
        $team = PageMember::where('page_id', $page->id)
            ->where('status', 'active')
            ->with('user.profile')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'user_id' => $m->user_id,
                    'role' => $m->role,
                    'joined_at' => $m->created_at?->format('M Y'),
                    'user' => [
                        'id' => $m->user?->id,
                        'name' => $m->user?->name,
                        'username' => $m->user?->username,
                        'avatar_url' => $m->user?->profile?->avatar_url ? UserProfile::normalizeStorageUrl($m->user->profile->avatar_url) : $m->user?->avatar_url,
                    ],
                ];
            })
            ->values()
            ->all();

        $sidebarData = $this->getSidebarData($viewer);

        return Inertia::render('Pages/Show', [
            'page' => $page->toResponseArray($viewer),
            'isFollowing' => $isFollowing,
            'isOwner' => $isOwner,
            'canManage' => $canManage,
            'canPost' => $canPost,
            'userRole' => $userRole ?? ($isOwner ? Page::ROLE_OWNER : null),
            'currentUser' => $viewer ? $this->transformUser($viewer) : null,
            'posts' => [
                'data' => $postsData,
                'current_page' => $postsPaginator->currentPage(),
                'has_more' => $postsPaginator->hasMorePages(),
                'total' => $postsPaginator->total(),
            ],
            'followers' => $followers,
            'photos' => $photos,
            'videos' => $videos,
            'events' => $events,
            'products' => $products,
            'team' => $team,
            'relatedPages' => $relatedPages,
            'insights' => $insights,
            'sevenDayInsights' => $sevenDayInsights,
            'recentActivity' => $recentActivity,
            'stories' => $activeStories,
            'reels' => $reels,
            'activeLiveStream' => $activeLiveStreamData,
            'activeTab' => $request->query('tab', 'posts'),
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Display the SocialHub Create Group multi-step wizard interface (Vue 3 / Inertia).
     */
    public function createGroup(Request $request): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);

        if (! $viewer) {
            return redirect()->route('login');
        }

        $sidebarData = $this->getSidebarData($viewer);

        $categories = [
            ['id' => 'technology', 'name' => 'Technology', 'slug' => 'technology'],
            ['id' => 'education', 'name' => 'Education', 'slug' => 'education'],
            ['id' => 'business', 'name' => 'Business', 'slug' => 'business'],
            ['id' => 'entertainment', 'name' => 'Entertainment', 'slug' => 'entertainment'],
            ['id' => 'sports', 'name' => 'Sports', 'slug' => 'sports'],
            ['id' => 'gaming', 'name' => 'Gaming', 'slug' => 'gaming'],
            ['id' => 'photography', 'name' => 'Photography', 'slug' => 'photography'],
            ['id' => 'travel', 'name' => 'Travel', 'slug' => 'travel'],
            ['id' => 'food', 'name' => 'Food', 'slug' => 'food'],
            ['id' => 'health_fitness', 'name' => 'Health and Fitness', 'slug' => 'health-and-fitness'],
            ['id' => 'local_community', 'name' => 'Local Community', 'slug' => 'local-community'],
            ['id' => 'other', 'name' => 'Other', 'slug' => 'other'],
        ];

        // Eligible friends to invite
        $friendsPaginator = $this->friendshipService->getFriends($viewer, 50);
        $friends = collect($friendsPaginator->items())->map(function (User $f) use ($viewer) {
            $mutual = $this->friendshipService->getMutualCount($viewer, $f);

            return [
                'id' => $f->id,
                'name' => $f->name,
                'username' => $f->username,
                'avatar_url' => $f->profile?->avatar_url ? UserProfile::normalizeStorageUrl($f->profile->avatar_url) : $f->avatar_url,
                'mutual_friends_count' => $mutual,
                'mutual' => $mutual > 0 ? "{$mutual} mutual friends" : 'Friend',
            ];
        })->values()->all();

        return Inertia::render('Groups/Create', [
            'categories' => $categories,
            'friends' => $friends,
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Display Groups discovery and user groups list (Vue 3 / Inertia).
     */
    public function groups(Request $request): Response
    {
        $viewer = $this->resolveViewer($request);

        $filters = [
            'q' => $request->query('q'),
            'category' => $request->query('category'),
            'sort' => $request->query('sort', 'trending'),
        ];

        $discoveryPaginator = $this->groupService->getDiscoveryGroups($viewer, $filters, 15);
        $discoveryGroups = collect($discoveryPaginator->items())->map(fn (Group $g) => $g->toResponseArray($viewer))->values()->all();

        $myGroups = $viewer ? $this->groupService->getUserGroups($viewer)->map(fn (Group $g) => $g->toResponseArray($viewer))->values()->all() : [];

        $invitations = $viewer ? GroupInvitation::where('invitee_id', $viewer->id)
            ->where('status', GroupInvitation::STATUS_PENDING)
            ->with(['group', 'inviter'])
            ->latest()
            ->get()
            ->map(fn ($i) => $i->toResponseArray())
            ->values()
            ->all() : [];

        $sidebarData = $this->getSidebarData($viewer);

        return Inertia::render('Groups/Index', [
            'groups' => $discoveryGroups,
            'myGroups' => $myGroups,
            'invitations' => $invitations,
            'filters' => $filters,
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Display Group profile and discussion page (Vue 3 / Inertia).
     */
    public function groupProfile(Request $request, string $slug): Response
    {
        $viewer = $this->resolveViewer($request);

        $group = Group::where('slug', $slug)
            ->orWhere('id', is_numeric($slug) ? (int) $slug : 0)
            ->orWhere('username', $slug)
            ->with([
                'creator.profile',
                'rules',
                'questions',
                'announcements.author',
                'files.user',
                'polls.options',
                'events.creator',
            ])
            ->firstOrFail();

        if ($group->isHidden() && (! $viewer || (! $group->isMember($viewer->id) && ! $group->isAdmin($viewer->id)))) {
            abort(403, 'This group is hidden.');
        }

        $groupData = $group->toResponseArray($viewer);
        $isMemberOrAdmin = $viewer && ($group->isMember($viewer->id) || $group->isAdmin($viewer->id));

        $members = [];
        if ($group->isPublic() || $isMemberOrAdmin) {
            $members = GroupMember::where('group_id', $group->id)
                ->where('status', GroupMember::STATUS_ACTIVE)
                ->with(['user.profile'])
                ->get()
                ->map(function ($gm) {
                    return [
                        'id' => $gm->id,
                        'user_id' => $gm->user_id,
                        'role' => $gm->role,
                        'joined_at' => $gm->joined_at?->format('M Y') ?? $gm->created_at?->format('M Y'),
                        'user' => [
                            'id' => $gm->user?->id,
                            'name' => $gm->user?->name,
                            'username' => $gm->user?->username,
                            'avatar_url' => $gm->user?->profile?->avatar_url ? UserProfile::normalizeStorageUrl($gm->user->profile->avatar_url) : $gm->user?->avatar_url,
                        ],
                    ];
                })
                ->values()
                ->all();
        } else {
            // For non-members of private groups, only show staff/admins
            $members = GroupMember::where('group_id', $group->id)
                ->where('status', GroupMember::STATUS_ACTIVE)
                ->whereIn('role', [GroupMember::ROLE_ADMIN, GroupMember::ROLE_OWNER])
                ->with(['user.profile'])
                ->get()
                ->map(function ($gm) {
                    return [
                        'id' => $gm->id,
                        'user_id' => $gm->user_id,
                        'role' => $gm->role,
                        'joined_at' => $gm->joined_at?->format('M Y') ?? $gm->created_at?->format('M Y'),
                        'user' => [
                            'id' => $gm->user?->id,
                            'name' => $gm->user?->name,
                            'username' => $gm->user?->username,
                            'avatar_url' => $gm->user?->profile?->avatar_url ? UserProfile::normalizeStorageUrl($gm->user->profile->avatar_url) : $gm->user?->avatar_url,
                        ],
                    ];
                })
                ->values()
                ->all();
        }

        $postsData = [];
        $nextCursor = null;
        if ($group->isPublic() || $isMemberOrAdmin) {
            $postsPaginator = $this->groupService->getGroupFeed($group, $viewer, 15);
            $postsData = $postsPaginator->items();
        }

        $friends = [];
        if ($viewer) {
            $friendsPaginator = $this->friendshipService->getFriends($viewer, 50);
            $friends = collect($friendsPaginator->items())
                ->filter(fn (User $f) => ! $group->isMember($f->id))
                ->map(function (User $f) use ($viewer) {
                    $mutual = $this->friendshipService->getMutualCount($viewer, $f);

                    return [
                        'id' => $f->id,
                        'name' => $f->name,
                        'username' => $f->username,
                        'avatar_url' => $f->profile?->avatar_url ? UserProfile::normalizeStorageUrl($f->profile->avatar_url) : $f->avatar_url,
                        'mutual_friends_count' => $mutual,
                        'mutual' => $mutual > 0 ? "{$mutual} mutual friends" : 'Friend',
                    ];
                })->values()->all();
        }

        $sidebarData = $this->getSidebarData($viewer);

        return Inertia::render('Groups/Show', [
            'group' => $groupData,
            'members' => $members,
            'posts' => [
                'data' => $postsData,
                'next_cursor' => $nextCursor,
            ],
            'friends' => $friends,
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Display the Enterprise Marketplace hub (Vue 3 / Inertia).
     */
    public function marketplace(Request $request): Response
    {
        $viewer = $this->resolveViewer($request);

        $filters = [
            'category' => $request->query('category', 'all'),
            'q' => $request->query('q', ''),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'location' => $request->query('location', ''),
            'condition' => $request->query('condition', ''),
            'sort' => $request->query('sort', 'newest'),
        ];

        $categories = $this->marketplaceService->getTopCategoriesWithCounts();
        $featuredListings = $this->marketplaceService->getFeaturedListings($viewer, 8);
        $latestPaginator = $this->marketplaceService->getLatestListings($viewer, $filters, 16);
        $sidebarData = $this->getSidebarData($viewer);

        return Inertia::render('Marketplace/Index', [
            'categories' => $categories,
            'featuredListings' => $featuredListings,
            'latestListings' => [
                'data' => $latestPaginator->items(),
                'current_page' => $latestPaginator->currentPage(),
                'last_page' => $latestPaginator->lastPage(),
                'per_page' => $latestPaginator->perPage(),
                'total' => $latestPaginator->total(),
            ],
            'filters' => $filters,
            'marketplaceProducts' => $latestPaginator->items(),
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Display single product detail view (Vue 3 / Inertia).
     */
    public function marketplaceShow(Request $request, int $id): Response
    {
        $viewer = $this->resolveViewer($request);

        $product = MarketplaceProduct::with(['seller.profile', 'category'])->findOrFail($id);
        $product->increment('views_count');

        $productData = $product->toResponseArray($viewer);

        $related = MarketplaceProduct::with(['seller.profile', 'category'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', MarketplaceProduct::STATUS_ACTIVE)
            ->latest('id')
            ->take(4)
            ->get()
            ->map(fn (MarketplaceProduct $p) => $p->toResponseArray($viewer))
            ->values()
            ->all();

        $sidebarData = $this->getSidebarData($viewer);

        return Inertia::render('Marketplace/Show', [
            'product' => array_merge($productData, ['related_products' => $related]),
            'marketplaceProduct' => $productData,
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Display the Enterprise Purchase & Checkout Page (Vue 3 / Inertia).
     */
    public function marketplaceCheckout(Request $request, int $id): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);
        if (! $viewer) {
            return redirect()->route('login');
        }

        $product = MarketplaceProduct::with(['seller.profile', 'category'])->findOrFail($id);

        if ($product->status !== MarketplaceProduct::STATUS_ACTIVE) {
            return redirect()->route('marketplace.show', ['id' => $id])
                ->with('error', 'This listing is no longer available for purchase.');
        }

        if ($product->seller_id === $viewer->id) {
            return redirect()->route('marketplace.show', ['id' => $id])
                ->with('error', 'You cannot purchase your own listing.');
        }

        $sidebarData = $this->getSidebarData($viewer);
        $productData = $product->toResponseArray($viewer);

        return Inertia::render('Marketplace/Checkout', [
            'product' => $productData,
            'marketplaceProduct' => $productData,
            'currentUser' => [
                'id' => $viewer->id,
                'name' => $viewer->name,
                'username' => $viewer->username,
                'phone' => $viewer->phone ?? '',
                'address' => $viewer->profile?->location ?? '',
            ],
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Display the Create Product Listing Studio (Vue 3 / Inertia).
     */
    public function marketplaceCreate(Request $request): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);
        if (! $viewer) {
            return redirect()->route('login');
        }

        $categories = $this->marketplaceService->getCategories();
        $sidebarData = $this->getSidebarData($viewer);

        return Inertia::render('Marketplace/Create', [
            'categories' => $categories,
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Display user's own listings management dashboard (Vue 3 / Inertia).
     */
    public function marketplaceMyListings(Request $request): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);
        if (! $viewer) {
            return redirect()->route('login');
        }

        $status = $request->query('status');
        $paginator = $this->marketplaceService->getUserListings($viewer, $status, 16);
        $paginator->getCollection()->transform(function (MarketplaceProduct $p) use ($viewer) {
            return $p->toResponseArray($viewer);
        });

        $sidebarData = $this->getSidebarData($viewer);

        return Inertia::render('Marketplace/MyListings', [
            'listings' => [
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'currentStatus' => $status,
            'marketplaceMyListings' => true,
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Display the complete SocialHub Messenger interface (Vue 3 / Inertia).
     */
    public function messages(Request $request): Response|RedirectResponse
    {
        $viewer = $this->resolveViewer($request);

        if (! $viewer) {
            return redirect()->route('login');
        }

        $sidebarData = $this->getSidebarData($viewer);

        // Retrieve conversations with participants & lastMessage
        $conversationsPaginator = $this->messengerService->getUserConversations($viewer, 40);
        $conversations = collect($conversationsPaginator->items())->map(function ($conv) use ($viewer) {
            if (is_array($conv)) {
                return $conv;
            }
            if ($conv instanceof Conversation) {
                $participant = $conv->participants->firstWhere('user_id', $viewer->id);
                $unreadCount = $participant?->unread_count ?? 0;

                return $conv->toResponseArray($viewer, $unreadCount);
            }

            return (array) $conv;
        })->values()->all();

        // Check if a specific conversation or user was requested
        $activeConversation = null;
        $activeConversationId = $request->query('conversation_id');
        $activeUserId = $request->query('user_id') ?: $request->query('user');

        if ($activeConversationId) {
            $conv = Conversation::with(['participants.user.profile', 'lastMessage'])->find($activeConversationId);
            if ($conv && $conv->hasParticipant($viewer->id)) {
                $activeConversation = $conv;
            }
        } elseif ($activeUserId) {
            $targetUser = User::find($activeUserId);
            if ($targetUser && $targetUser->id !== $viewer->id) {
                $activeConversation = $this->messengerService->getOrCreateDirectConversation($viewer, $targetUser->id);
            }
        }

        // Fallback to first available conversation
        if (! $activeConversation && ! empty($conversations)) {
            $firstConvId = is_array($conversations[0]) ? ($conversations[0]['id'] ?? null) : ($conversations[0]->id ?? null);
            if ($firstConvId) {
                $activeConversation = Conversation::with(['participants.user.profile', 'lastMessage'])->find($firstConvId);
            }
        }

        $messagesData = [];
        $activeConversationData = null;
        $sharedMedia = [];
        $otherUserProfile = null;

        if ($activeConversation) {
            // Mark conversation as read
            try {
                $this->messengerService->markConversationAsRead($activeConversation, $viewer);
            } catch (\Throwable $e) {
                // Ignore gracefully
            }

            // Load messages
            $messagesPaginator = $this->messengerService->getMessages($activeConversation, $viewer, 50);
            $messagesData = collect($messagesPaginator->items())->map(function ($msg) use ($viewer) {
                return $msg instanceof Message ? $msg->toResponseArray($viewer) : $msg;
            })->values()->all();

            $activeConversationData = $activeConversation->toResponseArray($viewer, 0);

            // Load shared media
            try {
                $mediaItems = $this->messengerService->getConversationMedia($activeConversation, $viewer, 'media', 12);
                $sharedMedia = $mediaItems;
            } catch (\Throwable $e) {
                $sharedMedia = [];
            }

            // Load detailed profile for 1-on-1 contact
            if ($activeConversation->isDirect()) {
                $otherUser = $activeConversation->getOtherParticipant($viewer->id);
                if ($otherUser) {
                    $otherUser->loadMissing('profile');
                    $relState = strtolower($this->friendshipService->getRelationshipState($viewer, $otherUser));
                    $otherUserProfile = [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'username' => $otherUser->username,
                        'avatar_url' => $otherUser->profile?->avatar_url ? UserProfile::normalizeStorageUrl($otherUser->profile->avatar_url) : $otherUser->avatar_url,
                        'headline' => $otherUser->profile?->headline ?? 'Member of SocialHub',
                        'occupation' => $otherUser->profile?->occupation ?? 'Works at Tech Solutions',
                        'location' => $otherUser->profile?->location ?? 'Lives in Dhaka, Bangladesh',
                        'city' => $otherUser->profile?->city,
                        'country' => $otherUser->profile?->country,
                        'joined' => $otherUser->created_at ? 'Joined '.$otherUser->created_at->format('F Y') : 'Joined March 2021',
                        'relationship' => $relState,
                        'is_online' => true,
                        'is_blocked' => $this->privacyService->isBlocked($otherUser, $viewer) || $this->privacyService->isBlocked($viewer, $otherUser),
                    ];
                }
            }
        }

        // Friends list for New Message compose modal
        $friendsPaginator = $this->friendshipService->getFriends($viewer, 50);
        $friendsData = collect($friendsPaginator->items())->map(function (User $f) {
            return $this->transformUser($f);
        })->values()->all();

        return Inertia::render('Messenger', [
            'conversations' => $conversations,
            'initialActiveConversation' => $activeConversationData,
            'initialMessages' => $messagesData,
            'initialSharedMedia' => $sharedMedia,
            'otherUserProfile' => $otherUserProfile,
            'friends' => $friendsData,
            'pendingRequests' => $sidebarData['pendingRequests'],
            'contacts' => $sidebarData['contacts'],
            'suggestions' => $sidebarData['suggestions'],
            'upcomingEvents' => $sidebarData['upcomingEvents'],
            'unreadCount' => $sidebarData['unreadCount'],
            'unreadMessagesCount' => $sidebarData['unreadMessagesCount'],
        ]);
    }

    /**
     * Helper to transform a User model into standardized array.
     */
    protected function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'avatar_url' => $user->profile?->avatar_url ? UserProfile::normalizeStorageUrl($user->profile->avatar_url) : $user->avatar_url,
            'cover_image_url' => $user->profile?->cover_url ? UserProfile::normalizeStorageUrl($user->profile->cover_url) : ($user->profile?->cover_image_url ?? $user->cover_image_url),
            'bio' => $user->profile?->bio,
            'is_verified' => (bool) $user->is_verified,
            'friends_count' => count($user->getFriendIds()),
            'followers_count' => $user->followers()->count(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    /**
     * Helper to retrieve common sidebar data for layout.
     */
    protected function getSidebarData(?User $user): array
    {
        $events = Event::where('start_time', '>=', now())
            ->orderBy('start_time', 'asc')
            ->take(3)
            ->get();
        if ($events->isEmpty()) {
            $events = Event::latest('id')->take(3)->get();
        }
        $eventsData = $events->map(function (Event $e) {
            return [
                'id' => $e->id,
                'title' => $e->title,
                'month' => $e->start_time ? strtoupper($e->start_time->format('M')) : 'JUN',
                'day' => $e->start_time ? $e->start_time->format('d') : '14',
                'time_formatted' => $e->start_time ? $e->start_time->format('D, g:i A').($e->end_time ? ' - '.$e->end_time->format('g:i A') : '') : 'Sat, 10:00 AM - 4:00 PM',
                'address' => $e->address ?: 'City Convention Center',
                'cover_image' => $e->cover_image ? UserProfile::normalizeStorageUrl($e->cover_image) : 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=150&q=80',
            ];
        })->values()->all();

        if (! $user) {
            return [
                'pendingRequests' => [],
                'contacts' => [],
                'suggestions' => [],
                'upcomingEvents' => $eventsData,
                'unreadCount' => 0,
                'unreadMessagesCount' => 0,
            ];
        }

        $pendingPaginator = $this->friendshipService->getPendingRequests($user, 5);
        $pendingData = collect($pendingPaginator->items())->map(function ($req) {
            $sender = $req->user ?? $req->sender;

            return [
                'id' => $req->id,
                'user_id' => $req->user_id,
                'sender' => $sender ? $this->transformUser($sender) : null,
                'created_at' => $req->created_at?->toIso8601String(),
            ];
        })->values()->all();

        $friendsPaginator = $this->friendshipService->getFriends($user, 10);
        $contactsData = collect($friendsPaginator->items())->map(function (User $f) {
            $data = $this->transformUser($f);
            $data['online'] = true;

            return $data;
        })->values()->all();

        $suggestionsCollection = $this->friendshipService->getSuggestions($user, 5);
        $suggestionsData = $suggestionsCollection->map(function (User $s) use ($user) {
            $data = $this->transformUser($s);
            $mutual = $this->friendshipService->getMutualCount($user, $s);
            $data['mutual'] = $mutual > 0 ? "{$mutual} mutual friends" : 'Suggested for you';
            $data['added'] = false;

            return $data;
        })->values()->all();

        $unreadCount = $this->notificationService->getUnreadCount($user);
        $unreadMessagesCount = $this->messengerService->getUnreadMessageCount($user);

        return [
            'pendingRequests' => $pendingData,
            'contacts' => $contactsData,
            'suggestions' => $suggestionsData,
            'upcomingEvents' => $eventsData,
            'unreadCount' => $unreadCount,
            'unreadMessagesCount' => $unreadMessagesCount,
        ];
    }
}
