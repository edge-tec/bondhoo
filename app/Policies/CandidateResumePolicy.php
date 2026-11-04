<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CandidateResume;
use App\Models\CompanyMember;
use App\Models\JobApplication;
use App\Models\User;

class CandidateResumePolicy
{
    public function view(User $user, CandidateResume $resume): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        // Owner can always view
        if ($resume->user_id === $user->id) {
            return true;
        }

        // Recruiter reviewing an application with this resume
        $hasApplication = JobApplication::where('user_id', $resume->user_id)
            ->where(function ($q) use ($resume) {
                $q->where('resume_id', $resume->id)
                    ->orWhere('resume_url', $resume->file_path);
            })
            ->whereHas('company.members', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->whereIn('role', [
                        CompanyMember::ROLE_OWNER,
                        CompanyMember::ROLE_ADMIN,
                        CompanyMember::ROLE_RECRUITER,
                        CompanyMember::ROLE_HIRING_MANAGER,
                    ])
                    ->where('invitation_status', CompanyMember::STATUS_ACCEPTED);
            })
            ->exists();

        if ($hasApplication) {
            return true;
        }

        // Public visibility check
        return $resume->visibility === CandidateResume::VISIBILITY_PUBLIC;
    }

    public function download(User $user, CandidateResume $resume): bool
    {
        return $this->view($user, $resume);
    }

    public function delete(User $user, CandidateResume $resume): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        return $resume->user_id === $user->id;
    }
}
