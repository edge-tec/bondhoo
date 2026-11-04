<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CompanyMember;
use App\Models\JobApplication;
use App\Models\User;

class JobApplicationPolicy
{
    public function view(User $user, JobApplication $application): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        // Applicant can view their own application
        if ($application->user_id === $user->id) {
            return true;
        }

        // Company member / Job creator can view
        if ($application->jobPosting && $application->jobPosting->user_id === $user->id) {
            return true;
        }

        if ($application->company_id && $application->company) {
            return $application->company->hasMember($user->id);
        }

        return false;
    }

    public function withdraw(User $user, JobApplication $application): bool
    {
        return $application->user_id === $user->id && $application->status !== JobApplication::STATUS_WITHDRAWN;
    }

    public function updateStage(User $user, JobApplication $application): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($application->jobPosting && $application->jobPosting->user_id === $user->id) {
            return true;
        }

        if ($application->company_id && $application->company) {
            $role = $application->company->getMemberRole($user->id);

            return in_array($role, [
                CompanyMember::ROLE_OWNER,
                CompanyMember::ROLE_ADMIN,
                CompanyMember::ROLE_RECRUITER,
                CompanyMember::ROLE_HIRING_MANAGER,
            ], true);
        }

        return false;
    }

    public function addNote(User $user, JobApplication $application): bool
    {
        return $this->updateStage($user, $application);
    }
}
