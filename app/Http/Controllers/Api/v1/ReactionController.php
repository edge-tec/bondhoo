<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reaction\ReactionRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Services\PostService;
use App\Services\ReactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function __construct(
        protected ReactionService $reactionService,
        protected PostService $postService
    ) {}

    /**
     * React to a post (Like, Love, Care, Haha, Wow, Sad, Angry).
     */
    public function reactPost(ReactionRequest $request, int $postId): JsonResponse
    {
        $post = Post::findOrFail($postId);

        if (! $this->postService->canViewPost($post, $request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to react to this post.',
            ], 403);
        }

        $result = $this->reactionService->react(
            user: $request->user(),
            target: $post,
            type: $request->input('type')
        );

        return $this->successResponse(
            data: $result,
            message: $result['reacted'] ? "Reacted with {$result['type']}." : 'Reaction removed.'
        );
    }

    /**
     * React to a comment.
     */
    public function reactComment(ReactionRequest $request, int $commentId): JsonResponse
    {
        $comment = Comment::findOrFail($commentId);

        if ($comment->post && ! $this->postService->canViewPost($comment->post, $request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to react to this comment.',
            ], 403);
        }

        $result = $this->reactionService->react(
            user: $request->user(),
            target: $comment,
            type: $request->input('type')
        );

        return $this->successResponse(
            data: $result,
            message: $result['reacted'] ? "Reacted with {$result['type']}." : 'Reaction removed.'
        );
    }

    /**
     * Get summary breakdown of reactions for a post and the users who reacted.
     */
    public function postReactions(Request $request, int $postId): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $post = Post::findOrFail($postId);

        if (! $this->postService->canViewPost($post, $viewer)) {
            return response()->json([
                'success' => false,
                'message' => 'This post is not available.',
            ], 403);
        }

        $summary = $this->reactionService->getSummary($post);

        $reactions = Reaction::with('user.profile')
            ->where('reactable_type', Post::class)
            ->where('reactable_id', $post->id)
            ->latest()
            ->take(50)
            ->get();

        $summary['users'] = $reactions->map(function ($r) {
            return [
                'user_id' => $r->user_id,
                'name' => $r->user?->name ?? 'User',
                'username' => $r->user?->username ?? '',
                'avatar_url' => $r->user?->profile?->avatar_url,
                'type' => $r->type,
            ];
        })->values();

        return $this->successResponse(
            data: $summary,
            message: 'Post reaction summary retrieved.'
        );
    }
}
