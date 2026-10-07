<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\NotificationServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CommentService
{
    public function __construct(
        protected CacheServiceInterface $cacheService,
        protected NotificationServiceInterface $notificationService
    ) {}

    public function createComment(User $user, int $postId, string $body, ?int $parentId = null): Comment
    {
        $post = Post::findOrFail($postId);

        if ($post->comments_disabled) {
            throw new AuthorizationException('Comments are disabled for this post.');
        }

        if ($parentId) {
            $parent = Comment::where('id', $parentId)->where('post_id', $postId)->first();
            if (! $parent) {
                throw new InvalidArgumentException('Parent comment does not exist on this post.');
            }
        }

        return DB::transaction(function () use ($user, $post, $body, $parentId) {
            $comment = Comment::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'parent_id' => $parentId,
                'body' => $body,
            ]);

            // Increment post comments count
            $post->increment('comments_count');

            // If nested reply, increment parent replies count
            if ($parentId) {
                Comment::where('id', $parentId)->increment('replies_count');
            }

            // Sync Redis cache key: post:{id}:comments
            $this->cacheService->set("post:{$post->id}:comments", $post->fresh()->comments_count, 3600);

            // Notify post author if commenter is not author
            if ($post->user_id !== $user->id) {
                $this->notificationService->send(
                    recipientId: $post->user_id,
                    type: 'notification.comment',
                    data: [
                        'actor_id' => $user->id,
                        'actor_name' => $user->name ?? $user->username,
                        'post_id' => $post->id,
                        'comment_id' => $comment->id,
                        'title' => 'New comment on your post',
                        'message' => ($user->name ?? $user->username).' commented: '.Str::limit($body, 60),
                    ],
                    channels: ['database', 'email']
                );
            }

            return $comment->load(['user.profile', 'reactions']);
        });
    }

    public function updateComment(User $user, Comment $comment, string $body): Comment
    {
        if ($comment->user_id !== $user->id && ! $user->hasPermission('comments.moderate')) {
            throw new AuthorizationException('Unauthorized to edit this comment.');
        }

        $comment->update(['body' => $body]);

        return $comment->fresh(['user.profile', 'reactions']);
    }

    public function deleteComment(User $user, Comment $comment): void
    {
        if ($comment->user_id !== $user->id && ! $user->hasPermission('comments.moderate')) {
            throw new AuthorizationException('Unauthorized to delete this comment.');
        }

        DB::transaction(function () use ($comment) {
            $post = $comment->post;
            $parentId = $comment->parent_id;

            $comment->delete();

            if ($post && $post->comments_count > 0) {
                $post->decrement('comments_count');
                $this->cacheService->set("post:{$post->id}:comments", $post->fresh()->comments_count, 3600);
            }

            if ($parentId) {
                $parent = Comment::find($parentId);
                if ($parent && $parent->replies_count > 0) {
                    $parent->decrement('replies_count');
                }
            }
        });
    }

    public function getComments(int $postId, ?int $parentId = null, int $perPage = 15): LengthAwarePaginator
    {
        return Comment::with(['user.profile', 'reactions'])
            ->where('post_id', $postId)
            ->where('parent_id', $parentId)
            ->latest()
            ->paginate($perPage);
    }
}
