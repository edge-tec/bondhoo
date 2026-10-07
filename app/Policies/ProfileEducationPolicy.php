<?php

namespace App\Policies;

use App\Models\ProfileEducation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfileEducationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the education item.
     */
    public function view(?User $user, ProfileEducation $education): bool
    {
        if ($user && $user->id === $education->user_id) {
            return true;
        }

        if ($education->privacy === 'only_me') {
            return false;
        }

        if ($education->privacy === 'friends') {
            return $user && in_array($user->id, $education->user->getFriendIds(), true);
        }

        if ($education->privacy === 'followers') {
            return $user && (
                in_array($user->id, $education->user->getFriendIds(), true) ||
                $education->user->isFollowedBy($user)
            );
        }

        return true;
    }

    /**
     * Determine whether the user can create education records.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the education record.
     */
    public function update(User $user, ProfileEducation $education): bool
    {
        if ($user->id === $education->user_id) {
            return true;
        }

        if ($user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the education record.
     */
    public function delete(User $user, ProfileEducation $education): bool
    {
        if ($user->id === $education->user_id) {
            return true;
        }

        if ($user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users')) {
            return true;
        }

        return false;
    }
}
