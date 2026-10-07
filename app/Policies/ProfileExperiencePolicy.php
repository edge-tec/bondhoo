<?php

namespace App\Policies;

use App\Models\ProfileExperience;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfileExperiencePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the work experience item.
     */
    public function view(?User $user, ProfileExperience $experience): bool
    {
        if ($user && $user->id === $experience->user_id) {
            return true;
        }

        if ($experience->privacy === 'only_me') {
            return false;
        }

        if ($experience->privacy === 'friends') {
            return $user && in_array($user->id, $experience->user->getFriendIds(), true);
        }

        if ($experience->privacy === 'followers') {
            return $user && (
                in_array($user->id, $experience->user->getFriendIds(), true) ||
                $experience->user->isFollowedBy($user)
            );
        }

        return true;
    }

    /**
     * Determine whether the user can create work experience records.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the work experience record.
     */
    public function update(User $user, ProfileExperience $experience): bool
    {
        if ($user->id === $experience->user_id) {
            return true;
        }

        if ($user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the work experience record.
     */
    public function delete(User $user, ProfileExperience $experience): bool
    {
        if ($user->id === $experience->user_id) {
            return true;
        }

        if ($user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users')) {
            return true;
        }

        return false;
    }
}
