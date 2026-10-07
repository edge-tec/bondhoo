<?php

namespace App\Policies;

use App\Models\ProfileLanguage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfileLanguagePolicy
{
    use HandlesAuthorization;

    public function view(?User $user, ProfileLanguage $language): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ProfileLanguage $language): bool
    {
        if ($user->id === $language->user_id) {
            return true;
        }

        return $user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users');
    }

    public function delete(User $user, ProfileLanguage $language): bool
    {
        if ($user->id === $language->user_id) {
            return true;
        }

        return $user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users');
    }
}
