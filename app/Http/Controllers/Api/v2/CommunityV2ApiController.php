<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupBookmark;
use App\Models\GroupCustomRole;
use App\Models\GroupDiscussion;
use App\Models\GroupMemberBadge;
use App\Services\GroupService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Jugajug Enterprise Community & Groups V2 API Controller
 * Provides client-independent, mobile-ready REST endpoints for the V2 Community Operating System.
 */
class CommunityV2ApiController extends Controller
{
    public function __construct(
        protected GroupService $groupService
    ) {}

    /**
     * List communities with V2 advanced filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Group::query()->where('status', Group::STATUS_ACTIVE);

        if ($request->filled('community_type')) {
            $query->where('community_type', $request->query('community_type'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Privacy filter for unauthenticated / non-members
        $user = $request->user();
        if (! $user) {
            $query->where('community_type', Group::TYPE_PUBLIC);
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('community_type', '!=', Group::TYPE_HIDDEN)
                    ->orWhereHas('members', function ($mq) use ($user) {
                        $mq->where('user_id', $user->id);
                    });
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 50);
        $groups = $query->latest('health_score')->latest('id')->paginate($perPage);

        return response()->json([
            'data' => $groups->items(),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        ]);
    }

    /**
     * Create a new community with V2 identity, classification, and access settings.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:groups,username'],
            'description' => ['nullable', 'string', 'max:5000'],
            'community_type' => ['nullable', 'string', 'in:'.implode(',', Group::COMMUNITY_TYPES)],
            'privacy' => ['nullable', 'string', 'in:public,private,hidden'],
            'category' => ['nullable', 'string', 'max:100'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'language' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', 'string', 'max:150'],
            'post_permission' => ['nullable', 'string', 'in:all,admin_only'],
            'requires_post_approval' => ['nullable', 'boolean'],
            'requires_member_approval' => ['nullable', 'boolean'],
        ]);

        try {
            $group = $this->groupService->createGroup($request->user(), $validated);

            return response()->json([
                'data' => $group->loadCount(['members', 'posts']),
                'meta' => [
                    'message' => 'Community created successfully.',
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'COMMUNITY_CREATION_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    /**
     * Community Discovery Engine with explainable signals.
     */
    public function discover(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Group::query()
            ->where('status', Group::STATUS_ACTIVE)
            ->whereIn('community_type', [Group::TYPE_PUBLIC, Group::TYPE_CONTROLLED, Group::TYPE_PROJECT, Group::TYPE_INTEREST, Group::TYPE_ORGANIZATION]);

        if ($user) {
            $query->whereDoesntHave('members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $items = $query->orderByDesc('health_score')
            ->orderByDesc('members_count')
            ->take(12)
            ->get();

        $ranked = $items->map(function (Group $g) {
            $data = $g->toArray();
            $data['discovery_reason'] = $g->health_score > 70
                ? 'High community activity and responsive moderation'
                : 'Trending community in Jugajug ecosystem';

            return $data;
        });

        return response()->json([
            'data' => $ranked,
            'meta' => [
                'total' => $ranked->count(),
                'algorithm' => 'jugajug-explainable-health-v2',
            ],
        ]);
    }

    /**
     * View Community Details including Health Metrics and Rules.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        $group = is_numeric($id)
            ? Group::with(['rules', 'coverMedia', 'avatarMedia'])->findOrFail($id)
            : Group::with(['rules', 'coverMedia', 'avatarMedia'])->where('username', $id)->orWhere('slug', $id)->firstOrFail();

        $user = $request->user();
        if ($group->isPrivate() && (! $user || ! $group->hasMember($user->id))) {
            return response()->json([
                'error' => [
                    'code' => 'COMMUNITY_PRIVATE',
                    'message' => 'This community is private. Membership is required to view content.',
                ],
            ], 403);
        }

        $membership = $user ? $group->getMembership($user->id) : null;

        return response()->json([
            'data' => $group,
            'meta' => [
                'is_member' => (bool) $membership?->isActive(),
                'membership_status' => $membership?->status,
                'role' => $membership?->role,
                'health_score' => $group->health_score,
                'health_metrics' => $group->health_metrics,
            ],
        ]);
    }

    /**
     * Get or calculate Community Health.
     */
    public function health(Request $request, int $id): JsonResponse
    {
        $group = Group::findOrFail($id);
        $user = $request->user();

        if ($group->isPrivate() && (! $user || ! $group->hasMember($user->id))) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Unauthorized to view community health.',
                ],
            ], 403);
        }

        $health = $group->calculateHealthScore();

        return response()->json([
            'data' => $health,
        ]);
    }

    /**
     * Get Community Discussions & Questions.
     */
    public function discussions(Request $request, int $id): JsonResponse
    {
        $group = Group::findOrFail($id);

        try {
            $discussions = $this->groupService->getDiscussions(
                $group,
                $request->user(),
                min((int) $request->input('per_page', 15), 50)
            );

            return response()->json([
                'data' => $discussions->items(),
                'meta' => [
                    'current_page' => $discussions->currentPage(),
                    'last_page' => $discussions->lastPage(),
                    'total' => $discussions->total(),
                ],
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => $e->getMessage(),
                ],
            ], 403);
        }
    }

    /**
     * Create a new Discussion / Question.
     */
    public function storeDiscussion(Request $request, int $id): JsonResponse
    {
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'type' => ['nullable', 'string', 'in:discussion,question,announcement'],
            'tags' => ['nullable', 'array'],
        ]);

        try {
            $discussion = $this->groupService->createDiscussion($group, $request->user(), $validated);

            return response()->json([
                'data' => $discussion->load('author.profile'),
                'meta' => [
                    'message' => 'Discussion published successfully.',
                ],
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => $e->getMessage(),
                ],
            ], 403);
        }
    }

    /**
     * Post a reply or solution to a discussion.
     */
    public function replyDiscussion(Request $request, int $id, int $discussionId): JsonResponse
    {
        $group = Group::findOrFail($id);
        $discussion = GroupDiscussion::where('group_id', $group->id)->findOrFail($discussionId);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'parent_id' => ['nullable', 'integer', 'exists:group_discussion_replies,id'],
        ]);

        try {
            $reply = $this->groupService->replyToDiscussion(
                $group,
                $request->user(),
                $discussionId,
                $validated
            );

            return response()->json([
                'data' => $reply->load('author.profile'),
                'meta' => [
                    'message' => 'Reply posted successfully.',
                ],
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => $e->getMessage(),
                ],
            ], 403);
        }
    }

    /**
     * Mark a reply as Accepted Answer / Solution.
     */
    public function markDiscussionSolved(Request $request, int $id, int $discussionId, int $replyId): JsonResponse
    {
        $group = Group::findOrFail($id);
        $discussion = GroupDiscussion::where('group_id', $group->id)->findOrFail($discussionId);

        try {
            $updated = $this->groupService->markDiscussionSolved($group, $request->user(), $discussionId, $replyId);

            return response()->json([
                'data' => $updated,
                'meta' => [
                    'message' => 'Accepted answer marked successfully.',
                ],
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => $e->getMessage(),
                ],
            ], 403);
        }
    }

    /**
     * List badges awarded in this community.
     */
    public function badges(Request $request, int $id): JsonResponse
    {
        $group = Group::findOrFail($id);
        $badges = GroupMemberBadge::where('group_id', $group->id)
            ->with(['user.profile', 'granter.profile'])
            ->latest('id')
            ->paginate(30);

        return response()->json([
            'data' => $badges->items(),
            'meta' => [
                'total' => $badges->total(),
                'available_types' => GroupMemberBadge::TYPES,
            ],
        ]);
    }

    /**
     * Assign a Contributor Badge to a member.
     */
    public function assignBadge(Request $request, int $id, int $userId): JsonResponse
    {
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'badge_type' => ['required', 'string', 'in:'.implode(',', GroupMemberBadge::TYPES)],
        ]);

        try {
            $badge = $this->groupService->assignBadge(
                $group,
                $request->user(),
                $userId,
                $validated['badge_type']
            );

            return response()->json([
                'data' => $badge->load('user.profile'),
                'meta' => [
                    'message' => 'Badge assigned successfully.',
                ],
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => $e->getMessage(),
                ],
            ], 403);
        }
    }

    /**
     * Toggle granular subscription on a community resource.
     */
    public function toggleSubscription(Request $request, int $id, string $itemType, int $itemId): JsonResponse
    {
        $group = Group::findOrFail($id);
        $subscribed = $this->groupService->toggleSubscription($group, $request->user(), $itemType, $itemId);

        return response()->json([
            'data' => [
                'subscribed' => $subscribed,
                'item_type' => $itemType,
                'item_id' => $itemId,
            ],
            'meta' => [
                'message' => $subscribed ? 'Subscribed to notifications.' : 'Unsubscribed from notifications.',
            ],
        ]);
    }

    /**
     * Toggle bookmark on a community resource.
     */
    public function toggleBookmark(Request $request, int $id, string $itemType, int $itemId): JsonResponse
    {
        $group = Group::findOrFail($id);
        $bookmarked = $this->groupService->toggleBookmark($group, $request->user(), $itemType, $itemId);

        return response()->json([
            'data' => [
                'bookmarked' => $bookmarked,
                'item_type' => $itemType,
                'item_id' => $itemId,
            ],
            'meta' => [
                'message' => $bookmarked ? 'Item saved to bookmarks.' : 'Item removed from bookmarks.',
            ],
        ]);
    }

    /**
     * List user's bookmarks in this community.
     */
    public function bookmarks(Request $request, int $id): JsonResponse
    {
        $group = Group::findOrFail($id);
        $bookmarks = GroupBookmark::where('group_id', $group->id)
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'data' => $bookmarks->items(),
            'meta' => [
                'total' => $bookmarks->total(),
            ],
        ]);
    }

    /**
     * List custom roles in this community.
     */
    public function customRoles(Request $request, int $id): JsonResponse
    {
        $group = Group::findOrFail($id);
        $roles = GroupCustomRole::where('group_id', $group->id)->get();

        return response()->json([
            'data' => $roles,
        ]);
    }

    /**
     * Create a custom role.
     */
    public function storeCustomRole(Request $request, int $id): JsonResponse
    {
        $group = Group::findOrFail($id);

        if (! $group->isAdmin($request->user()->id)) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Only administrators can create custom roles.',
                ],
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['required', 'array'],
        ]);

        $role = GroupCustomRole::create([
            'group_id' => $group->id,
            'name' => $validated['name'],
            'display_name' => $validated['display_name'] ?? $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions'],
        ]);

        return response()->json([
            'data' => $role,
            'meta' => [
                'message' => 'Custom role created successfully.',
            ],
        ], 201);
    }
}
