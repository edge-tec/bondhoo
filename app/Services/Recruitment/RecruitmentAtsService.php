<?php

declare(strict_types=1);

namespace App\Services\Recruitment;

use App\Models\ApplicationAssignment;
use App\Models\ApplicationNote;
use App\Models\ApplicationStageHistory;
use App\Models\CandidateResume;
use App\Models\Company;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruitmentAtsService
{
    /**
     * Submit an application to a job posting.
     */
    public function applyForJob(User $user, JobPosting $job, array $data): JobApplication
    {
        if ($job->status !== JobPosting::STATUS_ACTIVE || $job->isExpired()) {
            throw ValidationException::withMessages([
                'job' => ['এই চাকরির আবেদনের সময়সীমা শেষ হয়ে গেছে বা বিজ্ঞপ্তিটি বন্ধ রয়েছে।'],
            ]);
        }

        if ($job->user_id === $user->id) {
            throw ValidationException::withMessages([
                'job' => ['নিজের প্রকাশিত বিজ্ঞপ্তিতে আবেদন করা সম্ভব নয়।'],
            ]);
        }

        if ($job->hasApplied($user->id)) {
            throw ValidationException::withMessages([
                'job' => ['You have already applied for this position. / আপনি ইতিমধ্যে এই পদে আবেদন করেছেন।'],
            ]);
        }

        // Validate resume ownership if resume_id provided
        $resume = null;
        if (! empty($data['resume_id'])) {
            $resume = CandidateResume::where('id', $data['resume_id'])
                ->where('user_id', $user->id)
                ->first();

            if (! $resume) {
                throw ValidationException::withMessages([
                    'resume_id' => ['অননুমোদিত বা অস্তিত্বহীন রিজিউমে।'],
                ]);
            }
        }

        return DB::transaction(function () use ($job, $user, $data, $resume) {
            $application = JobApplication::create([
                'job_posting_id' => $job->id,
                'company_id' => $job->company_id,
                'user_id' => $user->id,
                'resume_id' => $resume?->id,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'cover_letter' => $data['cover_letter'] ?? null,
                'answers' => $data['answers'] ?? [],
                'resume_url' => $resume ? $resume->file_path : ($data['resume_url'] ?? null),
                'portfolio_url' => $data['portfolio_url'] ?? null,
                'expected_salary' => isset($data['expected_salary']) ? (int) $data['expected_salary'] : null,
                'status' => JobApplication::STATUS_APPLIED,
                'source' => $data['source'] ?? 'direct',
                'applied_at' => now(),
            ]);

            // Record initial stage in history
            ApplicationStageHistory::create([
                'application_id' => $application->id,
                'stage' => JobApplication::STATUS_APPLIED,
                'previous_stage' => null,
                'actor_user_id' => $user->id,
                'notes' => 'প্রার্থী সফলভাবে আবেদন দাখিল করেছেন।',
                'created_at' => now(),
            ]);

            $job->increment('applications_count');

            return $application->load(['jobPosting.company', 'resume']);
        });
    }

    /**
     * Withdraw candidate application.
     */
    public function withdrawApplication(JobApplication $application, User $user): JobApplication
    {
        if ($application->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'application' => ['এই আবেদনটি প্রত্যাহার করার অনুমতি নেই।'],
            ]);
        }

        if ($application->status === JobApplication::STATUS_WITHDRAWN) {
            return $application;
        }

        return DB::transaction(function () use ($application, $user) {
            $prevStage = $application->status;
            $application->update([
                'status' => JobApplication::STATUS_WITHDRAWN,
                'withdrawn_at' => now(),
            ]);

            ApplicationStageHistory::create([
                'application_id' => $application->id,
                'stage' => JobApplication::STATUS_WITHDRAWN,
                'previous_stage' => $prevStage,
                'actor_user_id' => $user->id,
                'notes' => 'প্রার্থী আবেদন প্রত্যাহার করে নিয়েছেন।',
                'created_at' => now(),
            ]);

            return $application->fresh();
        });
    }

    /**
     * Move application to another recruitment stage.
     */
    public function updateApplicationStage(JobApplication $application, string $newStage, ?string $notes, User $actor): JobApplication
    {
        if ($application->status === JobApplication::STATUS_WITHDRAWN) {
            throw ValidationException::withMessages([
                'stage' => ['Cannot change recruitment stage of a withdrawn application.'],
            ]);
        }

        $allowedStages = [
            JobApplication::STATUS_APPLIED,
            JobApplication::STATUS_UNDER_REVIEW,
            JobApplication::STATUS_SHORTLISTED,
            JobApplication::STATUS_SCREENING,
            JobApplication::STATUS_INTERVIEW,
            JobApplication::STATUS_INTERVIEWED,
            JobApplication::STATUS_ASSESSMENT,
            JobApplication::STATUS_OFFER,
            JobApplication::STATUS_HIRED,
            JobApplication::STATUS_REJECTED,
        ];

        if (! in_array($newStage, $allowedStages, true)) {
            throw ValidationException::withMessages([
                'stage' => ['অকার্যকর রিক্রুটমেন্ট স্টেজ: '.$newStage],
            ]);
        }

        return DB::transaction(function () use ($application, $newStage, $notes, $actor) {
            $lockedApp = JobApplication::where('id', $application->id)->lockForUpdate()->firstOrFail();

            if ($lockedApp->status === JobApplication::STATUS_WITHDRAWN) {
                throw ValidationException::withMessages([
                    'stage' => ['Cannot change recruitment stage of a withdrawn application.'],
                ]);
            }

            $prevStage = $lockedApp->status;
            $lockedApp->update([
                'status' => $newStage,
                'admin_notes' => $notes ?? $lockedApp->admin_notes,
            ]);

            ApplicationStageHistory::create([
                'application_id' => $lockedApp->id,
                'stage' => $newStage,
                'previous_stage' => $prevStage,
                'actor_user_id' => $actor->id,
                'notes' => $notes ?? 'স্টেজ পরিবর্তন করা হয়েছে: '.$newStage,
                'created_at' => now(),
            ]);

            return $lockedApp->fresh();
        });
    }

    /**
     * Add private recruiter internal note.
     */
    public function addApplicationNote(JobApplication $application, User $author, string $note): ApplicationNote
    {
        return ApplicationNote::create([
            'application_id' => $application->id,
            'author_user_id' => $author->id,
            'note' => $note,
            'visibility' => 'internal',
        ]);
    }

    /**
     * Assign candidate to a recruiter or hiring manager.
     */
    public function assignCandidate(JobApplication $application, User $assignedTo, User $assignedBy, ?string $notes = null): ApplicationAssignment
    {
        return ApplicationAssignment::create([
            'application_id' => $application->id,
            'assigned_to_user_id' => $assignedTo->id,
            'assigned_by_user_id' => $assignedBy->id,
            'notes' => $notes,
        ]);
    }

    /**
     * Schedule an interview.
     */
    public function scheduleInterview(JobApplication $application, User $scheduler, array $data): Interview
    {
        return DB::transaction(function () use ($application, $scheduler, $data) {
            // Concurrency protection: lock application row
            $lockedApp = JobApplication::where('id', $application->id)->lockForUpdate()->firstOrFail();

            if ($lockedApp->status === JobApplication::STATUS_WITHDRAWN) {
                throw ValidationException::withMessages([
                    'application' => ['Cannot schedule an interview for a withdrawn application.'],
                ]);
            }

            // Prevent duplicate active interviews on same application
            $hasActiveInterview = Interview::where('application_id', $application->id)
                ->whereIn('status', [Interview::STATUS_SCHEDULED, 'pending', Interview::STATUS_CONFIRMED, Interview::STATUS_RESCHEDULED])
                ->exists();

            if ($hasActiveInterview) {
                throw ValidationException::withMessages([
                    'interview' => ['An active interview is already scheduled for this application.'],
                ]);
            }

            $interview = Interview::create([
                'application_id' => $application->id,
                'company_id' => $application->company_id ?? $application->jobPosting?->company_id,
                'scheduled_by' => $scheduler->id,
                'interview_type' => $data['interview_type'] ?? Interview::TYPE_VIDEO,
                'scheduled_at' => $data['scheduled_at'],
                'duration_minutes' => isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : 45,
                'timezone' => $data['timezone'] ?? 'Asia/Dhaka',
                'meeting_url' => $data['meeting_url'] ?? null,
                'location' => $data['location'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'status' => Interview::STATUS_SCHEDULED,
            ]);

            // Update application stage to interview if not already
            if ($lockedApp->status !== JobApplication::STATUS_INTERVIEW) {
                $this->updateApplicationStage(
                    $lockedApp,
                    JobApplication::STATUS_INTERVIEW,
                    'সাক্ষাৎকার নির্ধারিত হয়েছে: '.$interview->scheduled_at->format('d M Y, h:i A'),
                    $scheduler
                );
            }

            return $interview->load(['application.jobPosting', 'company']);
        });
    }

    /**
     * Reschedule interview.
     */
    public function rescheduleInterview(Interview $interview, array $data, User $actor): Interview
    {
        return DB::transaction(function () use ($interview, $data) {
            $locked = Interview::where('id', $interview->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [Interview::STATUS_SCHEDULED, 'pending', Interview::STATUS_CONFIRMED, Interview::STATUS_RESCHEDULED], true)) {
                throw ValidationException::withMessages([
                    'interview' => ['Cannot reschedule an interview with status: '.$locked->status],
                ]);
            }

            $locked->update([
                'scheduled_at' => $data['scheduled_at'] ?? $locked->scheduled_at,
                'duration_minutes' => isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : $locked->duration_minutes,
                'timezone' => $data['timezone'] ?? $locked->timezone,
                'meeting_url' => array_key_exists('meeting_url', $data) ? $data['meeting_url'] : $locked->meeting_url,
                'location' => array_key_exists('location', $data) ? $data['location'] : $locked->location,
                'instructions' => array_key_exists('instructions', $data) ? $data['instructions'] : $locked->instructions,
                'status' => Interview::STATUS_RESCHEDULED,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Cancel an interview.
     */
    public function cancelInterview(Interview $interview, ?string $reason = null): Interview
    {
        return DB::transaction(function () use ($interview, $reason) {
            $locked = Interview::where('id', $interview->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [Interview::STATUS_SCHEDULED, 'pending', Interview::STATUS_CONFIRMED, Interview::STATUS_RESCHEDULED], true)) {
                throw ValidationException::withMessages([
                    'interview' => ['Cannot cancel an interview with status: '.$locked->status],
                ]);
            }

            $locked->update([
                'status' => Interview::STATUS_CANCELLED,
                'feedback' => $reason ? 'বাতিলকরণের কারণ: '.$reason : $locked->feedback,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Candidate responds to interview invitation.
     */
    public function respondToInterview(Interview $interview, string $status): Interview
    {
        return DB::transaction(function () use ($interview, $status) {
            $locked = Interview::where('id', $interview->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [Interview::STATUS_SCHEDULED, 'pending', Interview::STATUS_RESCHEDULED], true)) {
                throw ValidationException::withMessages([
                    'interview' => ['Cannot respond to interview in status: '.$locked->status],
                ]);
            }

            $newStatus = match ($status) {
                'accept', 'confirmed' => Interview::STATUS_CONFIRMED,
                'decline', 'declined' => Interview::STATUS_DECLINED,
                default => throw ValidationException::withMessages(['status' => ['অবৈধ রেসপন্স স্ট্যাটাস।']]),
            };

            $locked->update(['status' => $newStatus]);

            return $locked->fresh();
        });
    }

    /**
     * Record interviewer feedback.
     */
    public function recordInterviewFeedback(Interview $interview, string $feedback): Interview
    {
        return DB::transaction(function () use ($interview, $feedback) {
            $locked = Interview::where('id', $interview->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [Interview::STATUS_CONFIRMED, Interview::STATUS_SCHEDULED, Interview::STATUS_RESCHEDULED], true)) {
                throw ValidationException::withMessages([
                    'interview' => ['Cannot record feedback for an interview with status: '.$locked->status],
                ]);
            }

            $locked->update([
                'feedback' => $feedback,
                'status' => Interview::STATUS_COMPLETED,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Get Kanban ATS pipeline board for a company.
     */
    public function getPipelineBoard(Company $company, ?int $jobId = null): array
    {
        $stages = [
            'applied' => ['label' => 'Applied', 'color' => '#0284c7'],
            'under_review' => ['label' => 'Under Review', 'color' => '#f59e0b'],
            'shortlisted' => ['label' => 'Shortlisted', 'color' => '#0ea5e9'],
            'screening' => ['label' => 'Screening', 'color' => '#6366f1'],
            'interview' => ['label' => 'Interview', 'color' => '#7c3aed'],
            'assessment' => ['label' => 'Assessment', 'color' => '#9333ea'],
            'offer' => ['label' => 'Offer', 'color' => '#059669'],
            'hired' => ['label' => 'Hired', 'color' => '#16a34a'],
            'rejected' => ['label' => 'Rejected', 'color' => '#dc2626'],
        ];

        $query = JobApplication::with(['user.profile', 'jobPosting', 'resume'])
            ->where('company_id', $company->id)
            ->where('status', '!=', JobApplication::STATUS_WITHDRAWN);

        if ($jobId) {
            $query->where('job_posting_id', $jobId);
        }

        $applications = $query->latest('id')->get();

        $columns = [];
        foreach ($stages as $key => $meta) {
            $stageApps = $applications->where('status', $key);
            $columns[] = [
                'stage_key' => $key,
                'title' => $meta['label'],
                'color' => $meta['color'],
                'count' => $stageApps->count(),
                'candidates' => $stageApps->map(fn (JobApplication $app) => [
                    'id' => $app->id,
                    'job_id' => $app->job_posting_id,
                    'job_title' => $app->jobPosting?->title,
                    'candidate_name' => $app->full_name,
                    'candidate_email' => $app->email,
                    'avatar' => $app->user?->avatar_url ?? $app->user?->profile?->avatar_url,
                    'applied_at' => $app->created_at?->toIso8601String(),
                    'resume_id' => $app->resume_id,
                    'expected_salary' => $app->expected_salary,
                ])->values()->all(),
            ];
        }

        return [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'total_active_candidates' => $applications->count(),
            'columns' => $columns,
        ];
    }

    /**
     * Get real employer dashboard statistics.
     */
    public function getCompanyDashboard(Company $company): array
    {
        $totalJobs = $company->jobs()->count();
        $activeJobs = $company->jobs()->where('status', JobPosting::STATUS_ACTIVE)->count();
        $draftJobs = $company->jobs()->where('status', JobPosting::STATUS_DRAFT)->count();
        $pausedJobs = $company->jobs()->where('status', JobPosting::STATUS_PAUSED)->count();
        $closedJobs = $company->jobs()->where('status', JobPosting::STATUS_CLOSED)->count();

        $totalApplications = JobApplication::where('company_id', $company->id)->count();
        $newApplications = JobApplication::where('company_id', $company->id)
            ->whereIn('status', [JobApplication::STATUS_APPLIED, JobApplication::STATUS_PENDING])
            ->count();
        $shortlisted = JobApplication::where('company_id', $company->id)->where('status', JobApplication::STATUS_SHORTLISTED)->count();
        $interviews = Interview::where('company_id', $company->id)->count();
        $offers = JobApplication::where('company_id', $company->id)->where('status', JobApplication::STATUS_OFFER)->count();
        $hired = JobApplication::where('company_id', $company->id)->where('status', JobApplication::STATUS_HIRED)->count();

        $recentApplications = JobApplication::with(['user.profile', 'jobPosting'])
            ->where('company_id', $company->id)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (JobApplication $a) => $a->toResponseArray())
            ->values()->all();

        $upcomingInterviews = Interview::with(['application.jobPosting'])
            ->where('company_id', $company->id)
            ->where('status', Interview::STATUS_SCHEDULED)
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get()
            ->map(fn (Interview $i) => $i->toResponseArray())
            ->values()->all();

        return [
            'company' => $company->toResponseArray(),
            'metrics' => [
                'total_jobs' => $totalJobs,
                'active_jobs' => $activeJobs,
                'draft_jobs' => $draftJobs,
                'paused_jobs' => $pausedJobs,
                'closed_jobs' => $closedJobs,
                'total_applications' => $totalApplications,
                'new_applications' => $newApplications,
                'shortlisted_candidates' => $shortlisted,
                'interviews_scheduled' => $interviews,
                'offers_sent' => $offers,
                'hired_candidates' => $hired,
            ],
            'recent_applications' => $recentApplications,
            'upcoming_interviews' => $upcomingInterviews,
        ];
    }
}
