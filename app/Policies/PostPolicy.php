<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the post.
     */
    public function view(?User $user, Post $post): bool
    {
        return app(PostService::class)->canViewPost($post, $user);
    }

    /**
     * Determine whether the user can create posts.
     */
    public function create(User $user): bool
    {
        return ! in_array($user->status, ['suspended', 'banned', 'deactivated'], true);
    }

    /**
     * Determine whether the user can update the post.
     */
    public function update(User $user, Post $post): bool
    {
        // 1. Author can update own post
        if ((int) $user->id === (int) $post->user_id) {
            return true;
        }

        // 2. Admins with moderation permissions
        return $user->hasPermission('posts.moderate') || $user->hasRole('SUPER_ADMIN');
    }

    /**
     * Determine whether the user can delete the post.
     */
    public function delete(User $user, Post $post): bool
    {
        // 1. Author can delete own post
        if ((int) $user->id === (int) $post->user_id) {
            return true;
        }

        // 2. Admins with delete permission
        return $user->hasPermission('posts.delete') || $user->hasRole('SUPER_ADMIN');
    }
}
