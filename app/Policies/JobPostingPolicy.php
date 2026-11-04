<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\JobPosting;
use App\Models\User;

class JobPostingPolicy
{
    public function view(?User $user, JobPosting $job): bool
    {
        if ($job->status === JobPosting::STATUS_ACTIVE && $job->visibility === 'public') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($job->user_id === $user->id) {
            return true;
        }

        if ($job->company_id && $job->company) {
            return $job->company->hasMember($user->id);
        }

        return false;
    }

    public function create(User $user, ?Company $company = null): bool
    {
        if (! $company) {
            return true;
        }

        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        $role = $company->getMemberRole($user->id);

        return in_array($role, [CompanyMember::ROLE_OWNER, CompanyMember::ROLE_ADMIN, CompanyMember::ROLE_RECRUITER], true);
    }

    public function update(User $user, JobPosting $job): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($job->user_id === $user->id) {
            return true;
        }

        if ($job->company_id && $job->company) {
            $role = $job->company->getMemberRole($user->id);

            return in_array($role, [CompanyMember::ROLE_OWNER, CompanyMember::ROLE_ADMIN, CompanyMember::ROLE_RECRUITER], true);
        }

        return false;
    }

    public function delete(User $user, JobPosting $job): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($job->user_id === $user->id) {
            return true;
        }

        if ($job->company_id && $job->company) {
            $role = $job->company->getMemberRole($user->id);

            return in_array($role, [CompanyMember::ROLE_OWNER, CompanyMember::ROLE_ADMIN], true);
        }

        return false;
    }

    public function apply(User $user, JobPosting $job): bool
    {
        // Cannot apply to own job
        if ($job->user_id === $user->id) {
            return false;
        }

        // Job must be active and not expired
        if ($job->status !== JobPosting::STATUS_ACTIVE || $job->isExpired()) {
            return false;
        }

        return true;
    }
}
