<?php

namespace App\Policies;

use App\Models\ProfileInterest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfileInterestPolicy
{
    use HandlesAuthorization;

    public function view(?User $user, ProfileInterest $interest): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ProfileInterest $interest): bool
    {
        if ($user->id === $interest->user_id) {
            return true;
        }

        return $user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users');
    }

    public function delete(User $user, ProfileInterest $interest): bool
    {
        if ($user->id === $interest->user_id) {
            return true;
        }

        return $user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users');
    }
}
