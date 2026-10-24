<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobApiController extends Controller
{
    /**
     * GET /api/v1/jobs
     *
     * List active jobs with filters and search.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var ?User $viewer */
        $viewer = $request->user();
        $query = JobPosting::with(['user.profile', 'page']);

        // Default to active jobs unless specified otherwise
        $status = $request->query('status', JobPosting::STATUS_ACTIVE);
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($empType = $request->query('employment_type')) {
            if ($empType !== 'all') {
                $query->where('employment_type', $empType);
            }
        }

        if ($locType = $request->query('location_type')) {
            if ($locType !== 'all') {
                $query->where('location_type', $locType);
            }
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));
        $jobs = $query->latest('id')->paginate($perPage);

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
        $job = JobPosting::with(['user.profile', 'page'])->findOrFail($id);

        $job->increment('views_count');

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
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'company_name' => 'required|string|max:150',
            'company_logo' => 'nullable|string|max:255',
            'location' => 'required|string|max:150',
            'location_type' => 'required|string|in:onsite,remote,hybrid',
            'employment_type' => 'required|string|in:full_time,part_time,contract,internship,freelance',
            'experience_level' => 'nullable|string|in:entry,mid,senior,lead,any',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|gte:salary_min',
            'salary_currency' => 'nullable|string|max:10',
            'salary_negotiable' => 'nullable|boolean',
            'description' => 'required|string|max:10000',
            'requirements' => 'nullable|string|max:5000',
            'benefits' => 'nullable|string|max:3000',
            'deadline' => 'nullable|date|after_or_equal:today',
            'page_id' => 'nullable|integer|exists:pages,id',
        ]);

        $job = JobPosting::create([
            'user_id' => $user->id,
            'page_id' => $validated['page_id'] ?? null,
            'title' => $validated['title'],
            'company_name' => $validated['company_name'],
            'company_logo' => $validated['company_logo'] ?? null,
            'location' => $validated['location'],
            'location_type' => $validated['location_type'],
            'employment_type' => $validated['employment_type'],
            'experience_level' => $validated['experience_level'] ?? 'mid',
            'salary_min' => $validated['salary_min'] ?? null,
            'salary_max' => $validated['salary_max'] ?? null,
            'salary_currency' => $validated['salary_currency'] ?? 'BDT',
            'salary_negotiable' => (bool) ($validated['salary_negotiable'] ?? false),
            'description' => $validated['description'],
            'requirements' => $validated['requirements'] ?? null,
            'benefits' => $validated['benefits'] ?? null,
            'deadline' => $validated['deadline'] ?? null,
            'status' => JobPosting::STATUS_ACTIVE,
        ]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'job' => $job->load(['user.profile', 'page'])->toResponseArray($user),
            ],
            'message' => 'চাকরির নিয়োগ বিজ্ঞপ্তি সফলভাবে প্রকাশিত হয়েছে! 💼',
        ], 201);
    }

    /**
     * POST /api/v1/jobs/{id}/apply
     *
     * Submit an application to a job posting.
     */
    public function apply(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $job = JobPosting::findOrFail($id);

        if ($job->status !== JobPosting::STATUS_ACTIVE || $job->isExpired()) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'এই চাকরির আবেদনের সময়সীমা শেষ হয়ে গেছে বা বিজ্ঞপ্তিটি বন্ধ রয়েছে।',
            ], 422);
        }

        // Check if user already applied
        if ($job->hasApplied($user->id)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'আপনি ইতিমধ্যে এই পদে আবেদন করেছেন।',
            ], 422);
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:50',
            'cover_letter' => 'nullable|string|max:5000',
            'resume_url' => 'nullable|string|max:255',
            'portfolio_url' => 'nullable|string|max:255',
            'expected_salary' => 'nullable|integer|min:0',
        ]);

        $application = DB::transaction(function () use ($job, $user, $validated) {
            $app = JobApplication::create([
                'job_posting_id' => $job->id,
                'user_id' => $user->id,
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'cover_letter' => $validated['cover_letter'] ?? null,
                'resume_url' => $validated['resume_url'] ?? null,
                'portfolio_url' => $validated['portfolio_url'] ?? null,
                'expected_salary' => $validated['expected_salary'] ?? null,
                'status' => JobApplication::STATUS_PENDING,
            ]);

            $job->increment('applications_count');

            return $app;
        });

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'application' => $application->toResponseArray(),
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

        $applications = JobApplication::with(['jobPosting.page'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();

        $items = $applications->map(fn (JobApplication $app) => $app->toResponseArray())->values()->all();

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
     * View applicant list for a job (creator or admin only).
     */
    public function applicantList(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $job = JobPosting::findOrFail($id);

        if ($job->user_id !== $user->id && ! $user->hasRole('ADMIN') && ! $user->hasRole('SUPER_ADMIN')) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'এই বিজ্ঞপ্তির আবেদন তালিকা দেখার অনুমতি আপনার নেই।',
            ], 403);
        }

        $applications = JobApplication::with(['user.profile'])
            ->where('job_posting_id', $job->id)
            ->latest('id')
            ->paginate(25);

        $items = collect($applications->items())
            ->map(fn (JobApplication $app) => $app->toResponseArray())
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
     * Update candidate status (shortlisted, interviewed, rejected, hired).
     */
    public function updateApplicationStatus(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $application = JobApplication::with('jobPosting')->findOrFail($id);
        $job = $application->jobPosting;

        if ($job->user_id !== $user->id && ! $user->hasRole('ADMIN') && ! $user->hasRole('SUPER_ADMIN')) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'প্রার্থীর স্ট্যাটাস পরিবর্তন করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:pending,shortlisted,interviewed,rejected,hired',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $application->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $application->admin_notes,
        ]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'application' => $application->fresh()->toResponseArray(),
            ],
            'message' => 'প্রার্থীর আবেদনের স্ট্যাটাস সফলভাবে আপডেট হয়েছে!',
        ]);
    }
}
