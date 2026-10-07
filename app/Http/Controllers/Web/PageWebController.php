<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageCreationDraft;
use App\Models\PageEvent;
use App\Models\PageFollower;
use App\Models\PageProduct;
use App\Models\PageType;
use App\Models\Post;
use App\Models\User;
use App\Services\PageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        if ($user) {
            $managedPages = Page::where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', fn ($mq) => $mq->where('user_id', $user->id)->where('status', 'active'));
            })
                ->where('status', '!=', Page::STATUS_TRASH)
                ->with('owner')
                ->latest('id')
                ->get();

            $followedPages = Page::whereHas('followers', fn ($q) => $q->where('user_id', $user->id))
                ->where('status', '!=', Page::STATUS_TRASH)
                ->with('owner')
                ->latest('id')
                ->get();
        }

        return view('pages.index', [
            'currentUser' => $user,
            'allPages' => $allPages,
            'managedPages' => $managedPages,
            'followedPages' => $followedPages,
            'searchQuery' => $search,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'sort' => $sort,
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

        return view('pages.manage', [
            'currentUser' => $user,
            'page' => $page,
            'userRole' => $role ?? Page::ROLE_OWNER,
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
