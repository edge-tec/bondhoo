<?php

namespace App\Policies;

use App\Models\ProfileSkill;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfileSkillPolicy
{
    use HandlesAuthorization;

    public function view(?User $user, ProfileSkill $skill): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ProfileSkill $skill): bool
    {
        if ($user->id === $skill->user_id) {
            return true;
        }

        return $user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users');
    }

    public function delete(User $user, ProfileSkill $skill): bool
    {
        if ($user->id === $skill->user_id) {
            return true;
        }

        return $user->hasRole('SUPER_ADMIN') || $user->hasPermission('manage.users');
    }
}
