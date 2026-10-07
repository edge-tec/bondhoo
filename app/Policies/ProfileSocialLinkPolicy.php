<?php

namespace App\Policies;

use App\Models\ProfileSocialLink;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfileSocialLinkPolicy
{
    use HandlesAuthorization;

    public function view(?User $user, ProfileSocialLink $link): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ProfileSocialLink $link): bool
    {
        if ($user->id === $link->user_id) {
            return true;
        }

        return $user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users');
    }

    public function delete(User $user, ProfileSocialLink $link): bool
    {
        if ($user->id === $link->user_id) {
            return true;
        }

        return $user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users');
    }
}
