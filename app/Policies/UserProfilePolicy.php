<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserProfilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the given target user's profile.
     */
    public function update(User $user, User|UserProfile $target): bool
    {
        $targetUserId = $target instanceof UserProfile ? $target->user_id : $target->id;

        // 1. User can always edit their own profile
        if ($user->id === $targetUserId) {
            return true;
        }

        // 2. Admins with manage.users permission or SUPER_ADMIN role can edit any profile
        if ($user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users')) {
            return true;
        }

        return false;
    }
}
