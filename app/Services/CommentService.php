<?php

namespace App\Services;

use App\Events\PageCommentUpdatedEvent;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\NotificationServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

        if ($post->page_id) {
            $page = Page::find($post->page_id);
            if ($page) {
                if ($page->isBlocked($user->id)) {
                    throw new AuthorizationException('You are blocked from interacting with this page.');
                }

                $pageSettings = $page->getMergedSettings();
                if (isset($pageSettings['moderation']['allow_comments']) && ! $pageSettings['moderation']['allow_comments']) {
                    throw new AuthorizationException('Comments are disabled for this page.');
                }

                $blockedKeywords = $pageSettings['moderation']['blocked_keywords'] ?? [];
                if (! empty($blockedKeywords) && is_array($blockedKeywords)) {
                    foreach ($blockedKeywords as $kw) {
                        $trimmed = trim((string) $kw);
                        if ($trimmed !== '' && stripos($body, $trimmed) !== false) {
                            throw ValidationException::withMessages([
                                'body' => ["Your comment contains prohibited content: '{$trimmed}'."],
                            ]);
                        }
                    }
                }
            }
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

                // Notify parent comment author if different from replier and post author
                $parent = Comment::find($parentId);
                if ($parent && $parent->user_id !== $user->id && $parent->user_id !== $post->user_id) {
                    $this->notificationService->send(
                        recipientId: $parent->user_id,
                        type: 'notification.reply',
                        data: [
                            'actor_id' => $user->id,
                            'actor_name' => $user->name ?? $user->username,
                            'post_id' => $post->id,
                            'comment_id' => $comment->id,
                            'parent_id' => $parentId,
                            'title' => 'New reply to your comment',
                            'message' => ($user->name ?? $user->username).' replied: '.Str::limit($body, 60),
                        ],
                        channels: ['database']
                    );
                }
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

            $freshComment = $comment->load(['user.profile', 'reactions']);

            DB::afterCommit(function () use ($post, $freshComment) {
                try {
                    broadcast(new PageCommentUpdatedEvent(
                        postId: (int) $post->id,
                        pageId: $post->page_id ? (int) $post->page_id : null,
                        commentId: (int) $freshComment->id,
                        action: 'created',
                        commentData: $freshComment->toResponseArray(),
                        commentsCount: (int) $post->fresh()->comments_count
                    ));
                } catch (\Throwable $e) {
                    // Ignore broadcast failure
                }
            });

            return $freshComment;
        });
    }

    public function updateComment(User $user, Comment $comment, string $body): Comment
    {
        if ($comment->user_id !== $user->id && ! $user->hasPermission('comments.moderate')) {
            throw new AuthorizationException('Unauthorized to edit this comment.');
        }

        $comment->update(['body' => $body]);
        $fresh = $comment->fresh(['user.profile', 'reactions']);

        try {
            $post = $comment->post ?? Post::find($comment->post_id);
            broadcast(new PageCommentUpdatedEvent(
                postId: (int) $comment->post_id,
                pageId: $post?->page_id ? (int) $post->page_id : null,
                commentId: (int) $comment->id,
                action: 'updated',
                commentData: $fresh->toResponseArray($user),
                commentsCount: (int) ($post?->comments_count ?? 0)
            ));
        } catch (\Throwable $e) {
            // Ignore broadcast failure
        }

        return $fresh;
    }

    public function deleteComment(User $user, Comment $comment): void
    {
        $isAuthor = (int) $comment->user_id === (int) $user->id;
        $post = $comment->post ?? Post::find($comment->post_id);
        $isPostOwner = $post && (int) $post->user_id === (int) $user->id;

        $isPageManager = false;
        if ($post && $post->page_id) {
            $page = $post->page ?? Page::find($post->page_id);
            if ($page && ((int) $page->owner_id === (int) $user->id || in_array($page->getMemberRole($user->id), [Page::ROLE_OWNER, Page::ROLE_ADMIN, Page::ROLE_MANAGER, Page::ROLE_MODERATOR], true) || $page->hasPermission($user->id, 'comments.moderate') || $page->hasPermission($user->id, 'moderation.manage') || $page->hasPermission($user->id, 'posts.delete'))) {
                $isPageManager = true;
            }
        }

        if (! $isAuthor && ! $isPostOwner && ! $isPageManager && ! $user->hasPermission('comments.moderate')) {
            throw new AuthorizationException('Unauthorized to delete this comment.');
        }

        DB::transaction(function () use ($comment, $post) {
            $parentId = $comment->parent_id;
            $commentId = (int) $comment->id;
            $postId = (int) $comment->post_id;
            $pageId = $post?->page_id ? (int) $post->page_id : null;

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

            DB::afterCommit(function () use ($postId, $pageId, $commentId, $post) {
                try {
                    broadcast(new PageCommentUpdatedEvent(
                        postId: $postId,
                        pageId: $pageId,
                        commentId: $commentId,
                        action: 'deleted',
                        commentData: null,
                        commentsCount: (int) ($post?->fresh()->comments_count ?? 0)
                    ));
                } catch (\Throwable $e) {
                    // Ignore broadcast failure
                }
            });
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
