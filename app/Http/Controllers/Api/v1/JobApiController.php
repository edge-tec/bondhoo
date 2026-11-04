<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\CreateJobRequest;
use App\Http\Requests\Recruitment\SubmitJobApplicationRequest;
use App\Models\CompanyMember;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\JobPosting;
use App\Models\JobSkill;
use App\Models\User;
use App\Services\Recruitment\JobPortalService;
use App\Services\Recruitment\RecruitmentAtsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JobApiController extends Controller
{
    public function __construct(
        protected JobPortalService $jobPortalService,
        protected RecruitmentAtsService $atsService
    ) {}

    /**
     * GET /api/v1/jobs
     *
     * List active jobs with filters and search.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var ?User $viewer */
        $viewer = $request->user();

        $filters = [
            'q' => $request->query('q') ?? $request->query('search'),
            'category_id' => $request->query('category_id'),
            'company_id' => $request->query('company_id'),
            'employment_type' => $request->query('employment_type'),
            'workplace_type' => $request->query('workplace_type') ?? $request->query('location_type'),
            'experience_level' => $request->query('experience_level'),
            'location' => $request->query('location'),
            'country' => $request->query('country'),
            'city' => $request->query('city'),
            'salary_min' => $request->query('salary_min'),
            'salary_max' => $request->query('salary_max'),
            'is_remote' => $request->boolean('is_remote'),
            'date_from' => $request->query('date_from'),
            'sort_by' => $request->query('sort_by', 'newest'),
        ];

        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));
        $jobs = $this->jobPortalService->searchJobs($filters, $viewer, $perPage);

        $items = collect($jobs->items())->map(
            fn (JobPosting $job) => $job->toResponseArray($viewer)
        )->values()->all();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'jobs' => $items,
            ],
            'jobs' => $items,
            'meta' => [
                'total' => $jobs->total(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
            ],
            'message' => 'চাকরির বিজ্ঞপ্তিসমূহ সফলভাবে লোড হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/jobs/{id}
     *
     * Show job posting detail and track view count.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var ?User $viewer */
        $viewer = $request->user();
        $job = JobPosting::with(['user.profile', 'page', 'company', 'category', 'skills', 'questions'])->findOrFail($id);

        $this->jobPortalService->recordView(
            $job,
            $viewer,
            $request->ip(),
            $request->query('source', 'direct')
        );

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'job' => $job->toResponseArray($viewer),
            ],
            'message' => 'চাকরির বিস্তারিত তথ্য সফলভাবে লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/jobs
     *
     * Create a new job posting.
     */
    public function store(CreateJobRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $job = $this->jobPortalService->createJob($user, $request->validated());

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'job' => $job->toResponseArray($user),
            ],
            'message' => 'চাকরির নিয়োগ বিজ্ঞপ্তি সফলভাবে প্রকাশিত হয়েছে! 💼',
        ], 201);
    }

    /**
     * PUT /api/v1/jobs/{id}
     *
     * Update an existing job posting.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $job = JobPosting::findOrFail($id);

        if (! $this->canManageJob($user, $job)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'এই চাকরির বিজ্ঞপ্তি এডিট করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:200',
            'category_id' => 'nullable|integer|exists:job_categories,id',
            'location' => 'nullable|string|max:150',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'workplace_type' => 'nullable|string|in:onsite,remote,hybrid',
            'employment_type' => 'nullable|string|in:full_time,part_time,contract,internship,freelance,temporary',
            'experience_level' => 'nullable|string|in:entry,mid,senior,lead,any',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|gte:salary_min',
            'salary_currency' => 'nullable|string|max:10',
            'salary_negotiable' => 'nullable|boolean',
            'salary_visibility' => 'nullable|string|in:visible,hidden,range_only',
            'vacancies' => 'nullable|integer|min:1|max:1000',
            'visibility' => 'nullable|string|in:public,unlisted,internal',
            'description' => 'nullable|string|max:10000',
            'responsibilities' => 'nullable|string|max:5000',
            'requirements' => 'nullable|string|max:5000',
            'qualifications' => 'nullable|string|max:5000',
            'benefits' => 'nullable|string|max:3000',
            'deadline' => 'nullable|date',
            'skills' => 'nullable|array',
        ]);

        $updated = $this->jobPortalService->updateJob($job, $validated);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'job' => $updated->toResponseArray($user),
            ],
            'message' => 'চাকরির বিজ্ঞপ্তি সফলভাবে আপডেট হয়েছে!',
        ]);
    }

    /**
     * POST /api/v1/jobs/{id}/publish
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $job = JobPosting::findOrFail($id);

        if (! $this->canManageJob($user, $job)) {
            return response()->json(['success' => false, 'message' => 'অননুমোদিত অ্যাকশন।'], 403);
        }

        $published = $this->jobPortalService->publishJob($job);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => ['job' => $published->toResponseArray($user)],
            'message' => 'চাকরির বিজ্ঞপ্তি সফলভাবে প্রকাশিত হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/jobs/{id}/pause
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $job = JobPosting::findOrFail($id);

        if (! $this->canManageJob($user, $job)) {
            return response()->json(['success' => false, 'message' => 'অননুমোদিত অ্যাকশন।'], 403);
        }

        $paused = $this->jobPortalService->pauseJob($job);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => ['job' => $paused->toResponseArray($user)],
            'message' => 'চাকরির বিজ্ঞপ্তি সাময়িকভাবে স্থগিত করা হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/jobs/{id}/resume
     */
    public function resume(Request $request, int $id): JsonResponse
    {
        return $this->publish($request, $id);
    }

    /**
     * POST /api/v1/jobs/{id}/close
     */
    public function close(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $job = JobPosting::findOrFail($id);

        if (! $this->canManageJob($user, $job)) {
            return response()->json(['success' => false, 'message' => 'অননুমোদিত অ্যাকশন।'], 403);
        }

        $closed = $this->jobPortalService->closeJob($job);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => ['job' => $closed->toResponseArray($user)],
            'message' => 'চাকরির আবেদন গ্রহণ বন্ধ করা হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/jobs/{id}/duplicate
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $job = JobPosting::findOrFail($id);

        if (! $this->canManageJob($user, $job)) {
            return response()->json(['success' => false, 'message' => 'অননুমোদিত অ্যাকশন।'], 403);
        }

        $copy = $this->jobPortalService->duplicateJob($job, $user);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => ['job' => $copy->toResponseArray($user)],
            'message' => 'চাকরির বিজ্ঞপ্তি সফলভাবে ক্লোন করা হয়েছে।',
        ], 201);
    }

    /**
     * GET /api/v1/jobs/{id}/analytics
     */
    public function analytics(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $job = JobPosting::findOrFail($id);

        if (! $this->canManageJob($user, $job)) {
            return response()->json(['success' => false, 'message' => 'অ্যানালিটিক্স দেখার অনুমতি নেই।'], 403);
        }

        $stats = $this->jobPortalService->getJobAnalytics($job);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $stats,
            'message' => 'চাকরির অ্যানালিটিক্স লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/jobs/{id}/apply
     *
     * Submit an application to a job posting.
     */
    public function apply(SubmitJobApplicationRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $job = JobPosting::findOrFail($id);

        try {
            $application = $this->atsService->applyForJob($user, $job, $request->validated());
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'application' => $application->toResponseArray($user),
            ],
            'message' => 'আপনার চাকরির আবেদন সফলভাবে দাখিল হয়েছে! 🎯',
        ], 201);
    }

    /**
     * GET /api/v1/jobs/my/applications
     *
     * List current user's submitted job applications.
     */
    public function myApplications(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $applications = JobApplication::with(['jobPosting.page', 'jobPosting.company', 'resume', 'stageHistory'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();

        $items = $applications->map(fn (JobApplication $app) => $app->toResponseArray($user))->values()->all();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $items,
            'applications' => $items,
            'message' => 'আমার আবেদনসমূহ সফলভাবে লোড হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/jobs/{id}/applications
     *
     * View applicant list for a job (creator or company recruiter only).
     */
    public function applicantList(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $job = JobPosting::findOrFail($id);

        if (! $this->canManageJob($user, $job)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'এই বিজ্ঞপ্তির আবেদন তালিকা দেখার অনুমতি আপনার নেই।',
            ], 403);
        }

        $query = JobApplication::with(['user.profile', 'resume', 'stageHistory'])
            ->where('job_posting_id', $job->id);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $applications = $query->latest('id')->paginate(25);

        $items = collect($applications->items())
            ->map(fn (JobApplication $app) => $app->toResponseArray($user))
            ->values()->all();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'applications' => $items,
                'job' => $job->toResponseArray($user),
            ],
            'meta' => [
                'total' => $applications->total(),
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
            ],
            'message' => 'আবেদনকারীদের তালিকা লোড হয়েছে।',
        ]);
    }

    /**
     * PUT /api/v1/jobs/applications/{id}/status
     *
     * Update candidate status.
     */
    public function updateApplicationStatus(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $application = JobApplication::with('jobPosting.company')->findOrFail($id);
        $job = $application->jobPosting;

        if (! $job || ! $this->canManageJob($user, $job)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'প্রার্থীর স্ট্যাটাস পরিবর্তন করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:pending,applied,under_review,shortlisted,screening,interview,interviewed,assessment,offer,hired,rejected',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $updated = $this->atsService->updateApplicationStage(
            $application,
            $validated['status'],
            $validated['admin_notes'] ?? null,
            $user
        );

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'application' => $updated->toResponseArray($user),
            ],
            'message' => 'প্রার্থীর আবেদনের স্ট্যাটাস সফলভাবে আপডেট হয়েছে!',
        ]);
    }

    /**
     * GET /api/v1/jobs/categories
     */
    public function categories(): JsonResponse
    {
        $categories = JobCategory::with('children')
            ->whereNull('parent_id')
            ->where('status', JobCategory::STATUS_ACTIVE)
            ->get();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $categories->map(fn (JobCategory $c) => $c->toResponseArray())->values()->all(),
            'message' => 'ক্যাটাগরি তালিকা লোড হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/jobs/skills
     */
    public function skills(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $query = JobSkill::query();
        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }
        $skills = $query->limit(30)->get();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $skills->map(fn (JobSkill $s) => $s->toResponseArray())->values()->all(),
            'message' => 'স্কিল তালিকা লোড হয়েছে।',
        ]);
    }

    private function canManageJob(User $user, JobPosting $job): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($job->user_id === $user->id) {
            return true;
        }

        if ($job->company_id && $job->company) {
            $role = $job->company->getMemberRole($user->id);

            return in_array($role, [
                CompanyMember::ROLE_OWNER,
                CompanyMember::ROLE_ADMIN,
                CompanyMember::ROLE_RECRUITER,
            ], true);
        }

        return false;
    }
}
