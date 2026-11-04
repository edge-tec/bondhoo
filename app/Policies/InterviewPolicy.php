<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CompanyMember;
use App\Models\Interview;
use App\Models\User;

class InterviewPolicy
{
    public function view(User $user, Interview $interview): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($interview->application && $interview->application->user_id === $user->id) {
            return true;
        }

        if ($interview->company && $interview->company->hasMember($user->id)) {
            return true;
        }

        return false;
    }

    public function manage(User $user, Interview $interview): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($interview->company) {
            $role = $interview->company->getMemberRole($user->id);

            return in_array($role, [
                CompanyMember::ROLE_OWNER,
                CompanyMember::ROLE_ADMIN,
                CompanyMember::ROLE_RECRUITER,
            ], true);
        }

        return false;
    }

    public function respond(User $user, Interview $interview): bool
    {
        return $interview->application && $interview->application->user_id === $user->id;
    }
}
