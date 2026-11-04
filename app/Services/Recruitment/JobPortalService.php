<?php

declare(strict_types=1);

namespace App\Services\Recruitment;

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\JobSkill;
use App\Models\JobView;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobPortalService
{
    /**
     * Search and filter active jobs.
     */
    public function searchJobs(array $filters, ?User $viewer = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = JobPosting::with(['user.profile', 'company', 'category', 'skills', 'page'])
            ->where('status', JobPosting::STATUS_ACTIVE)
            ->where('visibility', 'public');

        if (! empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $query->where(function ($sub) use ($q): void {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('company_name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('location', 'like', "%{$q}%")
                    ->orWhereHas('skills', fn ($sq) => $sq->where('name', 'like', "%{$q}%"));
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['employment_type']) && $filters['employment_type'] !== 'all') {
            $query->where('employment_type', $filters['employment_type']);
        }

        if (! empty($filters['workplace_type']) && $filters['workplace_type'] !== 'all') {
            $query->where(function ($w) use ($filters): void {
                $w->where('workplace_type', $filters['workplace_type'])
                    ->orWhere('location_type', $filters['workplace_type']);
            });
        }

        if (! empty($filters['experience_level']) && $filters['experience_level'] !== 'all') {
            $query->where('experience_level', $filters['experience_level']);
        }

        if (! empty($filters['location'])) {
            $loc = trim((string) $filters['location']);
            $query->where(function ($lq) use ($loc): void {
                $lq->where('location', 'like', "%{$loc}%")
                    ->orWhere('city', 'like', "%{$loc}%")
                    ->orWhere('country', 'like', "%{$loc}%");
            });
        }

        if (! empty($filters['country'])) {
            $query->where('country', 'like', '%'.trim((string) $filters['country']).'%');
        }

        if (! empty($filters['city'])) {
            $query->where('city', 'like', '%'.trim((string) $filters['city']).'%');
        }

        if (! empty($filters['salary_min'])) {
            $query->where('salary_max', '>=', (int) $filters['salary_min']);
        }

        if (! empty($filters['salary_max'])) {
            $query->where('salary_min', '<=', (int) $filters['salary_max']);
        }

        if (! empty($filters['is_remote'])) {
            $query->where(function ($rq): void {
                $rq->where('workplace_type', 'remote')
                    ->orWhere('location_type', 'remote');
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'newest';
        match ($sortBy) {
            'oldest' => $query->oldest('id'),
            'salary_high' => $query->orderByDesc('salary_max')->latest('id'),
            'salary_low' => $query->orderBy('salary_min')->latest('id'),
            'most_viewed' => $query->orderByDesc('views_count')->latest('id'),
            'popular' => $query->orderByDesc('applications_count')->latest('id'),
            default => $query->latest('id'),
        };

        $perPage = min(50, max(1, $perPage));

        return $query->paginate($perPage);
    }

    /**
     * Create a job posting (with wizard fields, skills, questions).
     */
    public function createJob(User $creator, array $data): JobPosting
    {
        return DB::transaction(function () use ($creator, $data) {
            $company = null;
            if (! empty($data['company_id'])) {
                $company = Company::findOrFail((int) $data['company_id']);
            }

            $job = JobPosting::create([
                'user_id' => $creator->id,
                'company_id' => $company?->id,
                'page_id' => $data['page_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'company_name' => $company?->name ?? ($data['company_name'] ?? 'Connectly Employer'),
                'company_logo' => $company?->logo ?? ($data['company_logo'] ?? null),
                'location' => $data['location'],
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
                'location_type' => $data['workplace_type'] ?? ($data['location_type'] ?? 'onsite'),
                'workplace_type' => $data['workplace_type'] ?? ($data['location_type'] ?? 'onsite'),
                'employment_type' => $data['employment_type'] ?? 'full_time',
                'experience_level' => $data['experience_level'] ?? 'mid',
                'salary_min' => isset($data['salary_min']) ? (int) $data['salary_min'] : null,
                'salary_max' => isset($data['salary_max']) ? (int) $data['salary_max'] : null,
                'salary_currency' => $data['salary_currency'] ?? 'BDT',
                'salary_negotiable' => (bool) ($data['salary_negotiable'] ?? false),
                'salary_visibility' => $data['salary_visibility'] ?? 'visible',
                'vacancies' => isset($data['vacancies']) ? (int) $data['vacancies'] : 1,
                'visibility' => $data['visibility'] ?? 'public',
                'application_method' => $data['application_method'] ?? 'in_app',
                'external_application_url' => $data['external_application_url'] ?? null,
                'description' => $data['description'],
                'responsibilities' => $data['responsibilities'] ?? null,
                'requirements' => $data['requirements'] ?? null,
                'qualifications' => $data['qualifications'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'deadline' => $data['deadline'] ?? null,
                'status' => ($data['is_draft'] ?? false) ? JobPosting::STATUS_DRAFT : JobPosting::STATUS_ACTIVE,
                'published_at' => ($data['is_draft'] ?? false) ? null : now(),
            ]);

            // Sync skills
            if (! empty($data['skills']) && is_array($data['skills'])) {
                $skillIds = [];
                foreach ($data['skills'] as $skillName) {
                    $skillName = trim((string) $skillName);
                    if ($skillName !== '') {
                        $skill = JobSkill::firstOrCreate(
                            ['name' => $skillName],
                            ['slug' => Str::slug($skillName)]
                        );
                        $skillIds[] = $skill->id;
                    }
                }
                $job->skills()->sync($skillIds);
            }

            // Custom application questions
            if (! empty($data['questions']) && is_array($data['questions'])) {
                foreach ($data['questions'] as $order => $qData) {
                    if (! empty($qData['question'])) {
                        $job->questions()->create([
                            'question' => $qData['question'],
                            'type' => $qData['type'] ?? 'short_answer',
                            'options' => $qData['options'] ?? null,
                            'is_required' => (bool) ($qData['is_required'] ?? false),
                            'display_order' => $order,
                        ]);
                    }
                }
            }

            return $job->load(['company', 'category', 'skills', 'questions']);
        });
    }

    /**
     * Update an existing job posting.
     */
    public function updateJob(JobPosting $job, array $data): JobPosting
    {
        return DB::transaction(function () use ($job, $data) {
            $job->update(array_filter([
                'title' => $data['title'] ?? $job->title,
                'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $job->category_id,
                'location' => $data['location'] ?? $job->location,
                'country' => array_key_exists('country', $data) ? $data['country'] : $job->country,
                'city' => array_key_exists('city', $data) ? $data['city'] : $job->city,
                'workplace_type' => $data['workplace_type'] ?? $job->workplace_type,
                'location_type' => $data['workplace_type'] ?? $job->location_type,
                'employment_type' => $data['employment_type'] ?? $job->employment_type,
                'experience_level' => $data['experience_level'] ?? $job->experience_level,
                'salary_min' => array_key_exists('salary_min', $data) ? (int) $data['salary_min'] : $job->salary_min,
                'salary_max' => array_key_exists('salary_max', $data) ? (int) $data['salary_max'] : $job->salary_max,
                'salary_currency' => $data['salary_currency'] ?? $job->salary_currency,
                'salary_negotiable' => array_key_exists('salary_negotiable', $data) ? (bool) $data['salary_negotiable'] : $job->salary_negotiable,
                'salary_visibility' => $data['salary_visibility'] ?? $job->salary_visibility,
                'vacancies' => array_key_exists('vacancies', $data) ? (int) $data['vacancies'] : $job->vacancies,
                'visibility' => $data['visibility'] ?? $job->visibility,
                'description' => $data['description'] ?? $job->description,
                'responsibilities' => array_key_exists('responsibilities', $data) ? $data['responsibilities'] : $job->responsibilities,
                'requirements' => array_key_exists('requirements', $data) ? $data['requirements'] : $job->requirements,
                'qualifications' => array_key_exists('qualifications', $data) ? $data['qualifications'] : $job->qualifications,
                'benefits' => array_key_exists('benefits', $data) ? $data['benefits'] : $job->benefits,
                'deadline' => array_key_exists('deadline', $data) ? $data['deadline'] : $job->deadline,
            ], fn ($val) => $val !== null));

            if (isset($data['skills']) && is_array($data['skills'])) {
                $skillIds = [];
                foreach ($data['skills'] as $skillName) {
                    $skillName = trim((string) $skillName);
                    if ($skillName !== '') {
                        $skill = JobSkill::firstOrCreate(
                            ['name' => $skillName],
                            ['slug' => Str::slug($skillName)]
                        );
                        $skillIds[] = $skill->id;
                    }
                }
                $job->skills()->sync($skillIds);
            }

            return $job->fresh()->load(['company', 'category', 'skills', 'questions']);
        });
    }

    /**
     * Publish a draft or paused job.
     */
    public function publishJob(JobPosting $job): JobPosting
    {
        $job->update([
            'status' => JobPosting::STATUS_ACTIVE,
            'published_at' => $job->published_at ?? now(),
            'closed_at' => null,
        ]);

        return $job->fresh();
    }

    /**
     * Pause an active job.
     */
    public function pauseJob(JobPosting $job): JobPosting
    {
        $job->update(['status' => JobPosting::STATUS_PAUSED]);

        return $job->fresh();
    }

    /**
     * Close a job posting.
     */
    public function closeJob(JobPosting $job): JobPosting
    {
        $job->update([
            'status' => JobPosting::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        return $job->fresh();
    }

    /**
     * Duplicate a job posting as a draft.
     */
    public function duplicateJob(JobPosting $job, User $user): JobPosting
    {
        return DB::transaction(function () use ($job, $user) {
            $replica = $job->replicate(['slug', 'applications_count', 'views_count', 'published_at', 'closed_at']);
            $replica->user_id = $user->id;
            $replica->title = $job->title.' (কপি)';
            $replica->status = JobPosting::STATUS_DRAFT;
            $replica->save();

            // Duplicate skills
            $replica->skills()->sync($job->skills->pluck('id')->toArray());

            // Duplicate questions
            foreach ($job->questions as $question) {
                $replica->questions()->create([
                    'question' => $question->question,
                    'type' => $question->type,
                    'options' => $question->options,
                    'is_required' => $question->is_required,
                    'display_order' => $question->display_order,
                ]);
            }

            return $replica->load(['company', 'category', 'skills', 'questions']);
        });
    }

    /**
     * Track a job view preventing artificial refresh inflation.
     */
    public function recordView(JobPosting $job, ?User $viewer, ?string $ip, string $source = 'direct'): void
    {
        // Don't track creator views
        if ($viewer && $viewer->id === $job->user_id) {
            return;
        }

        // Check if viewed in last 30 minutes
        $recentView = JobView::where('job_id', $job->id)
            ->where(function ($q) use ($viewer, $ip) {
                if ($viewer) {
                    $q->where('viewer_user_id', $viewer->id);
                } elseif ($ip) {
                    $q->where('ip_address', $ip);
                }
            })
            ->where('viewed_at', '>=', now()->subMinutes(30))
            ->exists();

        if (! $recentView) {
            JobView::create([
                'job_id' => $job->id,
                'viewer_user_id' => $viewer?->id,
                'ip_address' => $ip,
                'source' => $source,
                'viewed_at' => now(),
            ]);

            $job->increment('views_count');
        }
    }

    /**
     * Get analytics for a specific job posting.
     */
    public function getJobAnalytics(JobPosting $job): array
    {
        $totalViews = $job->views_count;
        $uniqueViews = JobView::where('job_id', $job->id)
            ->whereNotNull('viewer_user_id')
            ->distinct('viewer_user_id')
            ->count('viewer_user_id');

        $totalApplications = $job->applications()->count();
        $conversionRate = $totalViews > 0 ? round(($totalApplications / $totalViews) * 100, 2) : 0.0;

        $shortlistedCount = $job->applications()->where('status', 'shortlisted')->count();
        $interviewCount = $job->applications()->whereIn('status', ['interview', 'interviewed'])->count();
        $offerCount = $job->applications()->where('status', 'offer')->count();
        $hiredCount = $job->applications()->where('status', 'hired')->count();

        return [
            'job_id' => $job->id,
            'job_title' => $job->title,
            'total_views' => $totalViews,
            'unique_views' => $uniqueViews,
            'total_applications' => $totalApplications,
            'conversion_rate' => $conversionRate,
            'funnel' => [
                'applied' => $totalApplications,
                'shortlisted' => $shortlistedCount,
                'interview' => $interviewCount,
                'offer' => $offerCount,
                'hired' => $hiredCount,
            ],
        ];
    }
}
