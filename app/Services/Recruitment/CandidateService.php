<?php

declare(strict_types=1);

namespace App\Services\Recruitment;

use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\JobAlert;
use App\Models\JobPosting;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CandidateService
{
    /**
     * Get or initialize candidate career profile.
     */
    public function getOrCreateProfile(User $user): CandidateProfile
    {
        return CandidateProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'professional_title' => $user->profile?->headline,
                'professional_summary' => $user->profile?->bio,
                'availability_status' => CandidateProfile::AVAILABILITY_OPEN,
                'profile_visibility' => CandidateProfile::VISIBILITY_PUBLIC,
            ]
        );
    }

    /**
     * Update candidate career preferences and profile details.
     */
    public function updateProfile(User $user, array $data): CandidateProfile
    {
        $profile = $this->getOrCreateProfile($user);

        $profile->update(array_filter([
            'professional_title' => array_key_exists('professional_title', $data) ? $data['professional_title'] : $profile->professional_title,
            'professional_summary' => array_key_exists('professional_summary', $data) ? $data['professional_summary'] : $profile->professional_summary,
            'experience_years' => array_key_exists('experience_years', $data) ? (int) $data['experience_years'] : $profile->experience_years,
            'current_company' => array_key_exists('current_company', $data) ? $data['current_company'] : $profile->current_company,
            'current_position' => array_key_exists('current_position', $data) ? $data['current_position'] : $profile->current_position,
            'expected_salary' => array_key_exists('expected_salary', $data) ? (int) $data['expected_salary'] : $profile->expected_salary,
            'salary_currency' => $data['salary_currency'] ?? $profile->salary_currency,
            'preferred_employment_types' => array_key_exists('preferred_employment_types', $data) ? $data['preferred_employment_types'] : $profile->preferred_employment_types,
            'preferred_workplace_types' => array_key_exists('preferred_workplace_types', $data) ? $data['preferred_workplace_types'] : $profile->preferred_workplace_types,
            'preferred_locations' => array_key_exists('preferred_locations', $data) ? $data['preferred_locations'] : $profile->preferred_locations,
            'availability_status' => $data['availability_status'] ?? $profile->availability_status,
            'profile_visibility' => $data['profile_visibility'] ?? $profile->profile_visibility,
        ], fn ($val) => $val !== null));

        return $profile->fresh();
    }

    /**
     * Upload resume securely with randomized storage name and MIME validation.
     */
    public function uploadResume(User $user, UploadedFile $file, string $title, bool $isDefault = false): CandidateResume
    {
        // Enforce maximum file size (10 MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'file' => ['রিজিউমে ফাইলের আকার সর্বোচ্চ ১০ মেগাবাইটের বেশি হতে পারবে না।'],
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'file' => ['শুধুমাত্র PDF, DOC অথবা DOCX ফরম্যাটের ফাইল গ্রহণযোগ্য।'],
            ]);
        }

        $randomName = Str::random(40).'.'.$extension;
        $path = $file->storeAs('resumes/'.$user->id, $randomName, 'local');

        return DB::transaction(function () use ($user, $file, $path, $title, $isDefault) {
            if ($isDefault) {
                CandidateResume::where('user_id', $user->id)->update(['is_default' => false]);
            }

            // If user has no existing resumes, make this one default
            $existingCount = CandidateResume::where('user_id', $user->id)->count();
            if ($existingCount === 0) {
                $isDefault = true;
            }

            return CandidateResume::create([
                'user_id' => $user->id,
                'title' => $title,
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
                'file_size' => $file->getSize(),
                'is_default' => $isDefault,
                'visibility' => CandidateResume::VISIBILITY_RECRUITERS_ONLY,
            ]);
        });
    }

    /**
     * Set a resume as default.
     */
    public function setDefaultResume(User $user, CandidateResume $resume): CandidateResume
    {
        if ($resume->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'resume' => ['অননুমোদিত রিজিউমে অ্যাক্সেস।'],
            ]);
        }

        DB::transaction(function () use ($user, $resume) {
            CandidateResume::where('user_id', $user->id)->update(['is_default' => false]);
            $resume->update(['is_default' => true]);
        });

        return $resume->fresh();
    }

    /**
     * Delete a candidate resume and remove stored file.
     */
    public function deleteResume(CandidateResume $resume): void
    {
        if (Storage::disk('local')->exists($resume->file_path)) {
            Storage::disk('local')->delete($resume->file_path);
        }

        $resume->delete();
    }

    /**
     * Save a job for candidate.
     */
    public function saveJob(User $user, JobPosting $job): SavedJob
    {
        return SavedJob::firstOrCreate([
            'user_id' => $user->id,
            'job_id' => $job->id,
        ]);
    }

    /**
     * Unsave a job for candidate.
     */
    public function unsaveJob(User $user, JobPosting $job): void
    {
        SavedJob::where('user_id', $user->id)->where('job_id', $job->id)->delete();
    }

    /**
     * Get paginated saved jobs for candidate.
     */
    public function getSavedJobs(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return SavedJob::with(['job.company', 'job.user.profile', 'job.skills', 'job.page', 'job.category'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Create a job alert.
     */
    public function createJobAlert(User $user, array $data): JobAlert
    {
        return JobAlert::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'keywords' => $data['keywords'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'locations' => $data['locations'] ?? [],
            'employment_types' => $data['employment_types'] ?? [],
            'workplace_types' => $data['workplace_types'] ?? [],
            'experience_level' => $data['experience_level'] ?? null,
            'frequency' => $data['frequency'] ?? 'daily',
            'is_active' => true,
        ]);
    }

    /**
     * Update job alert.
     */
    public function updateJobAlert(JobAlert $alert, array $data): JobAlert
    {
        $alert->update(array_filter([
            'title' => $data['title'] ?? $alert->title,
            'keywords' => array_key_exists('keywords', $data) ? $data['keywords'] : $alert->keywords,
            'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $alert->category_id,
            'locations' => array_key_exists('locations', $data) ? $data['locations'] : $alert->locations,
            'employment_types' => array_key_exists('employment_types', $data) ? $data['employment_types'] : $alert->employment_types,
            'workplace_types' => array_key_exists('workplace_types', $data) ? $data['workplace_types'] : $alert->workplace_types,
            'experience_level' => array_key_exists('experience_level', $data) ? $data['experience_level'] : $alert->experience_level,
            'frequency' => $data['frequency'] ?? $alert->frequency,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $alert->is_active,
        ], fn ($val) => $val !== null));

        return $alert->fresh();
    }
}
