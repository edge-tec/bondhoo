<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Company $company): bool
    {
        if ($company->visibility === Company::VISIBILITY_PUBLIC) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        return $company->hasMember($user->id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Company $company): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        $role = $company->getMemberRole($user->id);

        return in_array($role, [CompanyMember::ROLE_OWNER, CompanyMember::ROLE_ADMIN], true);
    }

    public function delete(User $user, Company $company): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        return $company->owner_user_id === $user->id;
    }

    public function manageMembers(User $user, Company $company): bool
    {
        return $this->update($user, $company);
    }

    public function manageJobs(User $user, Company $company): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        $role = $company->getMemberRole($user->id);

        return in_array($role, [CompanyMember::ROLE_OWNER, CompanyMember::ROLE_ADMIN, CompanyMember::ROLE_RECRUITER], true);
    }

    public function viewApplications(User $user, Company $company): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        $role = $company->getMemberRole($user->id);

        return in_array($role, [
            CompanyMember::ROLE_OWNER,
            CompanyMember::ROLE_ADMIN,
            CompanyMember::ROLE_RECRUITER,
            CompanyMember::ROLE_HIRING_MANAGER,
        ], true);
    }

    public function manageApplications(User $user, Company $company): bool
    {
        return $this->viewApplications($user, $company);
    }
}
