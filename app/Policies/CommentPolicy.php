<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create comments.
     */
    public function create(User $user): bool
    {
        return ! in_array($user->status, ['suspended', 'banned', 'deactivated'], true);
    }

    /**
     * Determine whether the user can update the comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        return (int) $user->id === (int) $comment->user_id;
    }

    /**
     * Determine whether the user can delete the comment.
     * Allowed for: Comment author, Post owner, or Admin with comments.moderate.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // 1. Comment author
        if ((int) $user->id === (int) $comment->user_id) {
            return true;
        }

        // 2. Post owner can delete any comment on their post
        if ($comment->post && (int) $user->id === (int) $comment->post->user_id) {
            return true;
        }

        // 3. Admin / Moderator with comments.moderate permission
        return $user->hasPermission('comments.moderate') || $user->hasRole('SUPER_ADMIN');
    }
}
