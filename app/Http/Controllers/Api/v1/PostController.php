<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\CreatePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\GroupMember;
use App\Models\Post;
use App\Models\PostDraft;
use App\Models\PostShare;
use App\Models\User;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\FeedService;
use App\Services\GifService;
use App\Services\LinkPreviewService;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function __construct(
        protected PostService $postService,
        protected FeedService $feedService
    ) {}

    /**
     * Get home/personalized feed with cursor pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $perPage = (int) $request->query('per_page', 15);
        $algorithm = (string) $request->query('algorithm', 'smart');

        $feed = $this->feedService->getFeed($viewer, $algorithm, $perPage);

        return $this->successResponse(
            data: collect($feed->items())->map(fn (Post $p) => $p->toResponseArray($viewer))->values(),
            message: 'Feed retrieved successfully.',
            meta: [
                'per_page' => $feed->perPage(),
                'has_more' => $feed->hasMorePages(),
                'next_cursor' => $feed->nextCursor()?->encode(),
                'prev_cursor' => $feed->previousCursor()?->encode(),
            ]
        );
    }

    /**
     * Create a new post.
     */
    public function store(CreatePostRequest $request): JsonResponse
    {
        $post = $this->postService->createPost(
            user: $request->user(),
            data: $request->validated(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $post->toResponseArray($request->user()),
            message: 'Post created successfully.',
            statusCode: 201
        );
    }

    /**
     * View a single post.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $post = Post::with(['user.profile', 'media', 'reactions'])->findOrFail($id);

        if (! $this->postService->canViewPost($post, $viewer)) {
            return response()->json([
                'success' => false,
                'message' => 'This post is not available.',
            ], 403);
        }

        return $this->successResponse(
            data: $post->toResponseArray($viewer),
            message: 'Post retrieved successfully.'
        );
    }

    /**
     * Update post content/audience.
     */
    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $updated = $this->postService->updatePost($request->user(), $post, $request->validated());

        return $this->successResponse(
            data: $updated->toResponseArray($request->user()),
            message: 'Post updated successfully.'
        );
    }

    /**
     * Delete post.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $this->postService->deletePost($request->user(), $post);

        return $this->successResponse(
            data: null,
            message: 'Post deleted successfully.'
        );
    }

    /**
     * Toggle pinned status.
     */
    public function togglePin(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $isPinned = $this->postService->togglePin($request->user(), $post);

        return $this->successResponse(
            data: ['is_pinned' => $isPinned],
            message: $isPinned ? 'Post pinned.' : 'Post unpinned.'
        );
    }

    /**
     * Toggle comments enabled/disabled.
     */
    public function toggleComments(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $disabled = $this->postService->toggleComments($request->user(), $post);

        return $this->successResponse(
            data: ['comments_disabled' => $disabled],
            message: $disabled ? 'Comments disabled.' : 'Comments enabled.'
        );
    }

    /**
     * Get posts for specific user profile.
     */
    public function userPosts(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();
        $perPage = (int) $request->query('per_page', 15);

        $posts = $this->feedService->getUserPosts($targetUser, $viewer, $perPage);

        return $this->successResponse(
            data: collect($posts->items())->map(fn (Post $p) => $p->toResponseArray($viewer))->values(),
            message: "Posts by @{$username} retrieved.",
            meta: [
                'per_page' => $posts->perPage(),
                'has_more' => $posts->hasMorePages(),
                'next_cursor' => $posts->nextCursor()?->encode(),
            ]
        );
    }

    /**
     * Share a post.
     */
    public function share(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        if (! $this->postService->canViewPost($post, $request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to share this post.',
            ], 403);
        }

        $caption = $request->input('caption');

        $share = DB::transaction(function () use ($request, $post, $caption) {
            $share = PostShare::create([
                'user_id' => $request->user()->id,
                'post_id' => $post->id,
                'caption' => $caption,
            ]);

            $post->increment('shares_count');

            return $share;
        });

        if ($post->user_id !== $request->user()->id) {
            try {
                app(NotificationServiceInterface::class)->send(
                    recipientId: $post->user_id,
                    type: 'social.share',
                    data: [
                        'actor_id' => $request->user()->id,
                        'actor_name' => $request->user()->name ?? $request->user()->username,
                        'target_id' => $post->id,
                        'title' => 'আপনার পোস্ট শেয়ার করা হয়েছে',
                        'message' => ($request->user()->name ?? $request->user()->username).' আপনার পোস্টটি শেয়ার করেছেন।',
                    ],
                    channels: ['database']
                );
            } catch (\Throwable $e) {
                // Silently continue if notification fails
            }
        }

        return $this->successResponse(
            data: [
                'share' => $share,
                'shares_count' => $post->fresh()->shares_count,
            ],
            message: 'Post shared successfully.'
        );
    }

    /**
     * Generate secure link preview with SSRF protection.
     */
    public function linkPreview(Request $request, LinkPreviewService $previewService): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'string', 'url', 'max:1000'],
        ]);

        $metadata = $previewService->fetchMetadata($request->input('url'));

        if (! $metadata) {
            return response()->json([
                'success' => false,
                'message' => 'Could not fetch metadata for the provided URL.',
            ], 422);
        }

        return $this->successResponse(
            data: $metadata,
            message: 'Link preview generated successfully.'
        );
    }

    /**
     * Vote on a post poll option.
     */
    public function votePoll(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'option_id' => ['required', 'integer'],
        ]);

        $post = Post::findOrFail($id);
        $user = $request->user();

        try {
            $updatedPoll = $this->postService->votePoll($user, $post, (int) $request->input('option_id'));

            return $this->successResponse(
                data: $updatedPoll,
                message: 'Vote recorded successfully.'
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Accept or decline a collaboration invite.
     */
    public function respondCollaborator(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string', 'in:accept,decline'],
        ]);

        $post = Post::findOrFail($id);
        $user = $request->user();

        $updatedPost = $this->postService->respondCollaborator($user, $post, $request->input('action'));

        return $this->successResponse(
            data: $updatedPost->toResponseArray($user),
            message: 'Collaborator response updated successfully.'
        );
    }

    /**
     * Get active draft(s) for post composer.
     */
    public function getDrafts(Request $request): JsonResponse
    {
        $user = $request->user();
        $groupId = $request->query('group_id') ? (int) $request->query('group_id') : null;
        $pageId = $request->query('page_id') ? (int) $request->query('page_id') : null;

        $drafts = $this->postService->getDrafts($user, $groupId, $pageId);

        return $this->successResponse(
            data: $drafts->map(fn (PostDraft $d) => $d->toResponseArray())->values(),
            message: 'Drafts retrieved successfully.'
        );
    }

    /**
     * Auto-save or manual save draft.
     */
    public function saveDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:10000'],
            'audience' => ['nullable', 'string', 'in:public,friends,followers,only_me'],
            'type' => ['nullable', 'string', 'in:text,media,link,poll,life_event,question'],
            'location' => ['nullable', 'string', 'max:255'],
            'feeling_activity' => ['nullable', 'string', 'max:255'],
            'poll_data' => ['nullable', 'array'],
            'link_preview' => ['nullable', 'array'],
            'background_style' => ['nullable', 'string', 'max:60'],
            'is_ai_generated' => ['nullable', 'boolean'],
            'content_warning' => ['nullable', 'string', 'max:150'],
            'comments_disabled' => ['nullable', 'boolean'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'collaborator_id' => ['nullable', 'integer', 'exists:users,id'],
            'tagged_user_ids' => ['nullable', 'array'],
            'tagged_user_ids.*' => ['integer', 'exists:users,id'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
            'media_meta' => ['nullable', 'array'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $draft = $this->postService->saveDraft($request->user(), $validated);

        return $this->successResponse(
            data: $draft->toResponseArray(),
            message: 'Draft saved successfully.'
        );
    }

    /**
     * Delete a single draft.
     */
    public function deleteDraft(Request $request, int $id): JsonResponse
    {
        $deleted = $this->postService->deleteDraft($request->user(), $id);

        return $this->successResponse(
            data: ['deleted' => $deleted],
            message: $deleted ? 'Draft deleted successfully.' : 'Draft not found.'
        );
    }

    /**
     * Clear all drafts for composer scope.
     */
    public function clearDrafts(Request $request): JsonResponse
    {
        $groupId = $request->query('group_id') ? (int) $request->query('group_id') : null;
        $pageId = $request->query('page_id') ? (int) $request->query('page_id') : null;

        $cleared = $this->postService->clearDraft($request->user(), $groupId, $pageId);

        return $this->successResponse(
            data: ['cleared' => $cleared],
            message: 'Drafts cleared successfully.'
        );
    }

    /**
     * Search friends and platform users for tagging and mentions.
     */
    public function taggableUsers(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        $limit = min(20, max(1, (int) $request->query('limit', 10)));

        $query = User::where('id', '!=', $user->id)->with('profile');

        if (! empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%");
            });
        }

        $users = $query->limit($limit)->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'username' => $u->username,
            'avatar_url' => $u->profile?->avatar_url,
        ])->values();

        return $this->successResponse(
            data: $users,
            message: 'Taggable users retrieved successfully.'
        );
    }

    /**
     * Get groups user is allowed to post in.
     */
    public function userGroups(Request $request): JsonResponse
    {
        $user = $request->user();

        $groups = GroupMember::where('user_id', $user->id)
            ->where('status', GroupMember::STATUS_ACTIVE)
            ->with('group')
            ->get()
            ->filter(fn ($m) => $m->group !== null)
            ->map(fn ($m) => [
                'id' => $m->group->id,
                'name' => $m->group->name,
                'privacy' => $m->group->privacy,
                'avatar_url' => $m->group->avatar_url,
            ])
            ->values();

        return $this->successResponse(
            data: $groups,
            message: 'User groups retrieved successfully.'
        );
    }

    /**
     * Get animated GIFs by query or category.
     */
    public function getGifs(Request $request, GifService $gifService): JsonResponse
    {
        $query = $request->query('q');
        $category = $request->query('category');
        $limit = min(30, max(1, (int) $request->query('limit', 20)));

        $gifs = $gifService->search($query, $category, $limit);
        $categories = $gifService->getCategories();

        return $this->successResponse(
            data: [
                'categories' => $categories,
                'gifs' => $gifs,
            ],
            message: 'GIFs retrieved successfully.'
        );
    }
}
