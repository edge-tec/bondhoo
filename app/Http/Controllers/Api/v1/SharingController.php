<?php

namespace App\Http\Controllers\Api\v1;

use App\Contracts\NotificationServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Page;
use App\Models\PageMember;
use App\Models\Post;
use App\Models\PostShare;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SharingController extends Controller
{
    public function __construct(
        protected PostService $postService
    ) {}

    /**
     * Get groups where the current user is an active member eligible to share posts.
     */
    public function eligibleGroups(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $request->query('q');

        $memberships = GroupMember::where('user_id', $user->id)
            ->where('status', GroupMember::STATUS_ACTIVE)
            ->whereNull('banned_at')
            ->where(function ($q) {
                $q->whereNull('restricted_until')
                    ->orWhere('restricted_until', '<', now());
            })
            ->whereIn('role', [
                GroupMember::ROLE_OWNER,
                GroupMember::ROLE_ADMIN,
                GroupMember::ROLE_MODERATOR,
                GroupMember::ROLE_EDITOR,
                GroupMember::ROLE_CONTENT_MANAGER,
                GroupMember::ROLE_MEMBER,
            ])
            ->pluck('role', 'group_id');

        if ($memberships->isEmpty()) {
            return $this->successResponse(
                data: [],
                message: 'No eligible groups found.'
            );
        }

        $groupsQuery = Group::whereIn('id', $memberships->keys());

        if (! empty($query)) {
            $groupsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('slug', 'like', "%{$query}%");
            });
        }

        $groups = $groupsQuery->orderBy('name', 'asc')->get();

        $data = $groups->map(function (Group $group) use ($memberships) {
            $role = $memberships[$group->id] ?? 'member';

            return [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'avatar_url' => $group->cover_image ?? $group->avatar_url ?? null,
                'cover_url' => $group->cover_image ?? null,
                'privacy' => $group->privacy ?? 'public',
                'members_count' => (int) ($group->members_count ?? 0),
                'user_role' => $role,
                'can_post' => true,
            ];
        });

        return $this->successResponse(
            data: $data,
            message: 'Eligible groups retrieved successfully.'
        );
    }

    /**
     * Get pages owned or managed by the user with authorized publishing roles.
     */
    public function eligiblePages(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $request->query('q');

        // 1. Pages owned by user (owner_id or user_id)
        $ownedPageIds = Page::where(function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })
            ->pluck('id')
            ->toArray();

        // 2. Pages where user is manager/editor/admin/moderator
        $memberPageRoles = PageMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereIn('role', [
                Page::ROLE_OWNER,
                Page::ROLE_ADMIN,
                Page::ROLE_MANAGER,
                Page::ROLE_EDITOR,
                Page::ROLE_CONTENT_MANAGER,
                Page::ROLE_MODERATOR,
            ])
            ->pluck('role', 'page_id')
            ->toArray();

        $allPageIds = array_unique(array_merge($ownedPageIds, array_keys($memberPageRoles)));

        if (empty($allPageIds)) {
            return $this->successResponse(
                data: [],
                message: 'No eligible pages found.'
            );
        }

        $pagesQuery = Page::whereIn('id', $allPageIds);

        if (! empty($query)) {
            $pagesQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('slug', 'like', "%{$query}%");
            });
        }

        $pages = $pagesQuery->orderBy('name', 'asc')->get();

        $data = $pages->map(function (Page $page) use ($user, $memberPageRoles) {
            $isOwner = ((int) ($page->owner_id ?? 0) === (int) $user->id);
            $role = $isOwner
                ? 'owner'
                : ($memberPageRoles[$page->id] ?? 'admin');

            return [
                'id' => $page->id,
                'name' => $page->name,
                'slug' => $page->slug ?? (string) $page->id,
                'avatar_url' => $page->avatar_url ?? $page->logo_url ?? null,
                'category' => $page->category ?? 'General',
                'user_role' => $role,
                'can_post' => true,
            ];
        });

        return $this->successResponse(
            data: $data,
            message: 'Eligible pages retrieved successfully.'
        );
    }

    /**
     * Share post to user's timeline.
     */
    public function shareToTimeline(Request $request, int $postId): JsonResponse
    {
        $post = Post::findOrFail($postId);

        if (! $this->postService->canViewPost($post, $request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to share this post.',
            ], 403);
        }

        $request->validate([
            'caption' => ['nullable', 'string', 'max:5000'],
        ]);

        $caption = $request->input('caption');

        $share = DB::transaction(function () use ($request, $post, $caption) {
            $share = PostShare::create([
                'user_id' => $request->user()->id,
                'post_id' => $post->id,
                'caption' => $caption,
                'target_type' => 'timeline',
                'target_id' => null,
            ]);

            $post->increment('shares_count');

            return $share;
        });

        $this->notifyPostAuthor($post, $request->user());

        return $this->successResponse(
            data: [
                'share' => $share,
                'shares_count' => (int) $post->fresh()->shares_count,
            ],
            message: 'Post shared to timeline successfully.'
        );
    }

    /**
     * Share post to one or multiple groups.
     */
    public function shareToGroups(Request $request, int $postId): JsonResponse
    {
        $post = Post::findOrFail($postId);

        if (! $this->postService->canViewPost($post, $request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to share this post.',
            ], 403);
        }

        $request->validate([
            'caption' => ['nullable', 'string', 'max:5000'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => ['integer'],
        ]);

        $user = $request->user();
        $caption = $request->input('caption');
        $requestedGroupIds = array_values(array_unique($request->input('group_ids')));

        // Validate active membership and posting capability for each group
        $validGroupIds = GroupMember::where('user_id', $user->id)
            ->whereIn('group_id', $requestedGroupIds)
            ->where('status', GroupMember::STATUS_ACTIVE)
            ->whereNull('banned_at')
            ->where(function ($q) {
                $q->whereNull('restricted_until')
                    ->orWhere('restricted_until', '<', now());
            })
            ->pluck('group_id')
            ->toArray();

        if (empty($validGroupIds)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not an active member in any of the selected groups.',
            ], 422);
        }

        $shares = DB::transaction(function () use ($user, $post, $caption, $validGroupIds) {
            $created = [];
            foreach ($validGroupIds as $groupId) {
                $created[] = PostShare::create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'caption' => $caption,
                    'target_type' => 'group',
                    'target_id' => $groupId,
                ]);
            }

            $post->increment('shares_count', count($created));

            return $created;
        });

        $this->notifyPostAuthor($post, $user);

        return $this->successResponse(
            data: [
                'shares_count' => (int) $post->fresh()->shares_count,
                'shared_count' => count($shares),
                'group_ids' => $validGroupIds,
            ],
            message: count($shares) === 1
                ? 'Post shared to group successfully.'
                : 'Post shared to '.count($shares).' groups successfully.'
        );
    }

    /**
     * Share post to pages owned or managed by user.
     */
    public function shareToPages(Request $request, int $postId): JsonResponse
    {
        $post = Post::findOrFail($postId);

        if (! $this->postService->canViewPost($post, $request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to share this post.',
            ], 403);
        }

        $request->validate([
            'caption' => ['nullable', 'string', 'max:5000'],
            'page_ids' => ['nullable', 'array'],
            'page_ids.*' => ['integer'],
            'page_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $caption = $request->input('caption');

        $pageIds = $request->input('page_ids', []);
        if ($request->has('page_id') && ! empty($request->input('page_id'))) {
            $pageIds[] = (int) $request->input('page_id');
        }
        $pageIds = array_values(array_unique(array_filter($pageIds)));

        if (empty($pageIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Please select at least one page to share to.',
            ], 422);
        }

        // Validate user authorization on each page
        $ownedPageIds = Page::where('owner_id', $user->id)
            ->whereIn('id', $pageIds)
            ->pluck('id')
            ->toArray();

        $managedPageIds = PageMember::where('user_id', $user->id)
            ->whereIn('page_id', $pageIds)
            ->where('status', 'active')
            ->whereIn('role', [
                Page::ROLE_OWNER,
                Page::ROLE_ADMIN,
                Page::ROLE_MANAGER,
                Page::ROLE_EDITOR,
                Page::ROLE_CONTENT_MANAGER,
                Page::ROLE_MODERATOR,
            ])
            ->pluck('page_id')
            ->toArray();

        $authorizedPageIds = array_values(array_unique(array_merge($ownedPageIds, $managedPageIds)));

        if (empty($authorizedPageIds)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to publish to any of the selected pages.',
            ], 403);
        }

        $shares = DB::transaction(function () use ($user, $post, $caption, $authorizedPageIds) {
            $created = [];
            foreach ($authorizedPageIds as $pageId) {
                $created[] = PostShare::create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'caption' => $caption,
                    'target_type' => 'page',
                    'target_id' => $pageId,
                ]);
            }

            $post->increment('shares_count', count($created));

            return $created;
        });

        $this->notifyPostAuthor($post, $user);

        return $this->successResponse(
            data: [
                'shares_count' => (int) $post->fresh()->shares_count,
                'shared_count' => count($shares),
                'page_ids' => $authorizedPageIds,
            ],
            message: count($shares) === 1
                ? 'Post shared to page successfully.'
                : 'Post shared to '.count($shares).' pages successfully.'
        );
    }

    /**
     * Notify original post author about the share.
     */
    protected function notifyPostAuthor(Post $post, $actor): void
    {
        if ($post->user_id === $actor->id) {
            return;
        }

        try {
            app(NotificationServiceInterface::class)->send(
                recipientId: $post->user_id,
                type: 'social.share',
                data: [
                    'actor_id' => $actor->id,
                    'actor_name' => $actor->name ?? $actor->username,
                    'target_id' => $post->id,
                    'title' => 'Your post was shared',
                    'message' => ($actor->name ?? $actor->username).' shared your post.',
                ],
                channels: ['database']
            );
        } catch (\Throwable $e) {
            // Silently continue if notification delivery fails
        }
    }
}
