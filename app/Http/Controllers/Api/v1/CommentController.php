<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\CreateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(
        protected CommentService $commentService,
        protected PostService $postService
    ) {}

    /**
     * List comments for a post (or nested replies for a comment).
     */
    public function index(Request $request, int $postId): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $post = Post::findOrFail($postId);

        if (! $this->postService->canViewPost($post, $viewer)) {
            return response()->json([
                'success' => false,
                'message' => 'This post is not available.',
            ], 403);
        }

        $parentId = $request->query('parent_id') ? (int) $request->query('parent_id') : null;
        $perPage = (int) $request->query('per_page', 15);

        $comments = $this->commentService->getComments($postId, $parentId, $perPage);

        return $this->successResponse(
            data: collect($comments->items())->map(fn (Comment $c) => $c->toResponseArray($viewer))->values(),
            message: 'Comments retrieved successfully.',
            meta: [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'total' => $comments->total(),
            ]
        );
    }

    /**
     * Post a comment or nested reply.
     */
    public function store(CreateCommentRequest $request, int $postId): JsonResponse
    {
        $post = Post::findOrFail($postId);

        if (! $this->postService->canViewPost($post, $request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to comment on this post.',
            ], 403);
        }

        $comment = $this->commentService->createComment(
            user: $request->user(),
            postId: $postId,
            body: $request->input('body'),
            parentId: $request->input('parent_id')
        );

        return $this->successResponse(
            data: $comment->toResponseArray($request->user()),
            message: 'Comment added successfully.',
            statusCode: 201
        );
    }

    /**
     * Update comment body.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate(['body' => ['required', 'string', 'min:1', 'max:2000']]);

        $comment = Comment::findOrFail($id);
        $updated = $this->commentService->updateComment($request->user(), $comment, $request->input('body'));

        return $this->successResponse(
            data: $updated->toResponseArray($request->user()),
            message: 'Comment updated successfully.'
        );
    }

    /**
     * Delete comment.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);
        $this->commentService->deleteComment($request->user(), $comment);

        return $this->successResponse(
            data: null,
            message: 'Comment deleted successfully.'
        );
    }
}
