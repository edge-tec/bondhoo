<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyVerification;
use App\Models\JobCategory;
use App\Models\JobPosting;
use App\Models\JobSkill;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminJobPortalApiController extends Controller
{
    /**
     * Ensure only admin or super admin can access.
     */
    protected function checkAdmin(Request $request): void
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user || (! $user->hasRole('ADMIN') && ! $user->hasRole('SUPER_ADMIN'))) {
            abort(403, 'প্রশাসনিক অনুমতি প্রয়োজন।');
        }
    }

    /**
     * GET /api/v1/admin/jobs/pending
     */
    public function pendingJobs(Request $request): JsonResponse
    {
        $this->checkAdmin($request);

        $jobs = JobPosting::with(['user.profile', 'company', 'category'])
            ->whereIn('status', [JobPosting::STATUS_PENDING_REVIEW, JobPosting::STATUS_DRAFT])
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'jobs' => collect($jobs->items())->map(fn (JobPosting $j) => $j->toResponseArray($request->user()))->values()->all(),
            ],
            'meta' => [
                'total' => $jobs->total(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
            ],
            'message' => 'মডারেশন তালিকা লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/admin/jobs/{id}/approve
     */
    public function approveJob(Request $request, int $id): JsonResponse
    {
        $this->checkAdmin($request);
        $job = JobPosting::findOrFail($id);

        $job->update([
            'status' => JobPosting::STATUS_ACTIVE,
            'published_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'job' => $job->fresh()->toResponseArray($request->user()),
            ],
            'message' => 'চাকরির বিজ্ঞপ্তিটি সফলভাবে অনুমোদন দেওয়া হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/admin/jobs/{id}/reject
     */
    public function rejectJob(Request $request, int $id): JsonResponse
    {
        $this->checkAdmin($request);
        $job = JobPosting::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $job->update([
            'status' => JobPosting::STATUS_REJECTED,
        ]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'job' => $job->fresh()->toResponseArray($request->user()),
            ],
            'message' => 'চাকরির বিজ্ঞপ্তি বাতিল করা হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/admin/companies/verifications
     */
    public function companyVerifications(Request $request): JsonResponse
    {
        $this->checkAdmin($request);

        $verifications = CompanyVerification::with(['company', 'user.profile'])
            ->where('status', CompanyVerification::STATUS_PENDING)
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'verifications' => collect($verifications->items())->map(fn (CompanyVerification $v) => $v->toResponseArray())->values()->all(),
            ],
            'meta' => [
                'total' => $verifications->total(),
                'current_page' => $verifications->currentPage(),
                'last_page' => $verifications->lastPage(),
            ],
            'message' => 'কোম্পানি ভেরিফিকেশন আবেদন তালিকা লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/admin/companies/verifications/{id}/approve
     */
    public function approveVerification(Request $request, int $id): JsonResponse
    {
        $this->checkAdmin($request);
        $verification = CompanyVerification::with('company')->findOrFail($id);

        $verification->update([
            'status' => CompanyVerification::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $verification->company->update(['verification_status' => Company::VERIFICATION_VERIFIED]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'কোম্পানি ভেরিফিকেশন সফলভাবে অনুমোদন দেওয়া হয়েছে! 🛡️',
        ]);
    }

    /**
     * POST /api/v1/admin/companies/verifications/{id}/reject
     */
    public function rejectVerification(Request $request, int $id): JsonResponse
    {
        $this->checkAdmin($request);
        $verification = CompanyVerification::with('company')->findOrFail($id);

        $validated = $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        $verification->update([
            'status' => CompanyVerification::STATUS_REJECTED,
            'admin_notes' => $validated['admin_notes'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $verification->company->update(['verification_status' => Company::VERIFICATION_REJECTED]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'কোম্পানি ভেরিফিকেশন বাতিল করা হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/admin/jobs/categories
     */
    public function createCategory(Request $request): JsonResponse
    {
        $this->checkAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'parent_id' => 'nullable|integer|exists:job_categories,id',
            'description' => 'nullable|string|max:500',
        ]);

        $category = JobCategory::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => JobCategory::STATUS_ACTIVE,
        ]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $category->toResponseArray(),
            'message' => 'জব ক্যাটাগরি সফলভাবে তৈরি হয়েছে।',
        ], 201);
    }

    /**
     * POST /api/v1/admin/jobs/skills
     */
    public function createSkill(Request $request): JsonResponse
    {
        $this->checkAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $skill = JobSkill::firstOrCreate(
            ['name' => $validated['name']],
            ['slug' => Str::slug($validated['name'])]
        );

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $skill->toResponseArray(),
            'message' => 'জব স্কিল সফলভাবে যুক্ত হয়েছে।',
        ], 201);
    }
}
