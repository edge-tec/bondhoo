<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMemberBadge;
use App\Models\Post;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class GroupWebController extends Controller
{
    public function __construct(
        protected GroupService $groupService
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
     * Display groups discovery hub, categories, trending groups, and user's joined groups.
     */
    public function index(Request $request): View
    {
        $user = $this->resolveUser($request);

        $filters = [
            'q' => $request->query('q'),
            'category' => $request->query('category'),
            'sort' => $request->query('sort', 'trending'),
        ];

        $allGroups = $this->groupService->getDiscoveryGroups($user, $filters, 12);

        $myGroups = collect();
        if ($user) {
            $myGroups = $this->groupService->getUserGroups($user);
        }

        $categories = [
            'All' => 'সকল ক্যাটাগরি',
            'Technology' => 'প্রযুক্তি ও গ্যাজেট',
            'Business' => 'ব্যবসা ও ক্যারিয়ার',
            'Education' => 'শিক্ষা ও দক্ষতা',
            'Entertainment' => 'বিনোদন ও সিনেমা',
            'Sports' => 'খেলাধুলা ও ফিটনেস',
            'Gaming' => 'গেমিং ও ই-স্পোর্টস',
            'Lifestyle' => 'লাইফস্টাইল ও ভ্রমণ',
            'News' => 'সংবাদ ও সমসাময়িক',
            'Community' => 'সামাজিক ও আঞ্চলিক',
        ];

        return view('groups.index', [
            'currentUser' => $user,
            'allGroups' => $allGroups,
            'myGroups' => $myGroups,
            'searchQuery' => $filters['q'] ?? '',
            'activeCategory' => $filters['category'] ?? 'All',
            'activeSort' => $filters['sort'] ?? 'trending',
            'categories' => $categories,
        ]);
    }

    /**
     * Display a specific group's page with dynamic, permission-aware tabs.
     */
    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $user = $this->resolveUser($request);

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
            ->first();

        if (! $group) {
            abort(404, 'গ্রুপটি খুঁজে পাওয়া যায়নি।');
        }

        if (! $group->canView($user)) {
            abort(403, 'এই গ্রুপটি হিডেন এবং শুধুমাত্র অনুমোদিত সদস্যদের জন্য দৃশ্যমান।');
        }

        $membership = null;
        if ($user) {
            $membership = $group->getMembership($user->id);
        }

        $isMember = $membership && $membership->isActive();
        $isAdmin = $user && $group->isAdmin($user->id);
        $isModerator = $user && $group->isModerator($user->id);
        $canPost = $user && $group->canPost($user->id);

        $activeTab = $request->query('tab', 'posts');

        // লোড পোস্ট বা অন্যান্য ট্যাব ডেটা
        $posts = null;
        $moderationPosts = null;
        $analytics = null;
        $discussions = null;

        $canViewPosts = $group->isPublic() || $isMember;

        if ($canViewPosts && in_array($activeTab, ['posts', 'home'])) {
            $posts = Post::where('group_id', $group->id)
                ->where('status', 'published')
                ->with(['user.profile', 'media', 'reactions'])
                ->latest('id')
                ->paginate(15);
        }

        if ($canViewPosts && $activeTab === 'discussions') {
            $discussions = $this->groupService->getDiscussions($group, $user, 15);
        }

        if ($isModerator && $activeTab === 'moderation') {
            $moderationPosts = $this->groupService->getModerationQueue($group, $user, 15);
        }

        if ($isAdmin && $activeTab === 'analytics') {
            $analytics = $this->groupService->getAnalytics($group, $user, 30);
        }

        $activeMembers = $group->members()
            ->where('status', GroupMember::STATUS_ACTIVE)
            ->with('user.profile')
            ->latest('id')
            ->limit(12)
            ->get();

        $badges = GroupMemberBadge::where('group_id', $group->id)
            ->with('user.profile')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('groups.show', [
            'currentUser' => $user,
            'group' => $group,
            'membership' => $membership,
            'isMember' => $isMember,
            'isAdmin' => $isAdmin,
            'isModerator' => $isModerator,
            'canPost' => $canPost,
            'canViewPosts' => $canViewPosts,
            'posts' => $posts,
            'discussions' => $discussions,
            'badges' => $badges,
            'moderationPosts' => $moderationPosts,
            'analytics' => $analytics,
            'activeMembers' => $activeMembers,
            'activeTab' => $activeTab,
        ]);
    }
}
