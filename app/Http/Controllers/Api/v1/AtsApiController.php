<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\ApplicationNote;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\Recruitment\RecruitmentAtsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AtsApiController extends Controller
{
    public function __construct(
        protected RecruitmentAtsService $atsService
    ) {}

    /**
     * GET /api/v1/companies/{company}/recruitment/pipeline
     *
     * Kanban recruitment board by stage.
     */
    public function pipeline(Request $request, int $companyId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = Company::findOrFail($companyId);

        if (! $this->canAccessCompanyAts($user, $company)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'রিক্রুটমেন্ট পাইপলাইন দেখার অনুমতি আপনার নেই।',
            ], 403);
        }

        $jobId = $request->query('job_id') ? (int) $request->query('job_id') : null;
        $board = $this->atsService->getPipelineBoard($company, $jobId);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $board,
            'message' => 'রিক্রুটমেন্ট পাইপলাইন বোর্ড লোড হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/companies/{company}/dashboard
     *
     * Employer dashboard metrics and recent activity.
     */
    public function companyDashboard(Request $request, int $companyId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = Company::findOrFail($companyId);

        if (! $this->canAccessCompanyAts($user, $company)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'কোম্পানি ড্যাশবোর্ড দেখার অনুমতি আপনার নেই।',
            ], 403);
        }

        $dashboard = $this->atsService->getCompanyDashboard($company);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $dashboard,
            'message' => 'কোম্পানি ড্যাশবোর্ড সফলভাবে লোড হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/applications/{id}
     *
     * View application details with IDOR protection.
     */
    public function showApplication(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $application = JobApplication::with([
            'jobPosting.company',
            'user.profile',
            'resume',
            'stageHistory.actor',
            'notes.author',
            'interviews',
        ])->findOrFail($id);

        $isOwner = $application->user_id === $user->id;
        $isCompanyRecruiter = $application->company && $this->canAccessCompanyAts($user, $application->company);
        $isJobCreator = $application->jobPosting && $application->jobPosting->user_id === $user->id;
        $isAdmin = $user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN');

        if (! $isOwner && ! $isCompanyRecruiter && ! $isJobCreator && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'এই আবেদনের বিস্তারিত দেখার অনুমতি নেই।',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'application' => $application->toResponseArray($user),
            ],
            'message' => 'আবেদনের তথ্য লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/applications/{id}/withdraw
     */
    public function withdraw(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $application = JobApplication::findOrFail($id);

        if ($application->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'আবেদনটি প্রত্যাহার করার অনুমতি নেই।',
            ], 403);
        }

        $withdrawn = $this->atsService->withdrawApplication($application, $user);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'application' => $withdrawn->toResponseArray($user),
            ],
            'message' => 'আপনার আবেদন সফলভাবে প্রত্যাহার করা হয়েছে।',
        ]);
    }

    /**
     * PATCH /api/v1/applications/{id}/stage
     */
    public function updateStage(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $application = JobApplication::with('jobPosting.company')->findOrFail($id);

        if (! $this->canManageApplication($user, $application)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'প্রার্থীর স্টেজ পরিবর্তন করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'stage' => 'required|string|in:applied,under_review,shortlisted,screening,interview,assessment,offer,hired,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        $updated = $this->atsService->updateApplicationStage(
            $application,
            $validated['stage'],
            $validated['notes'] ?? null,
            $user
        );

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'application' => $updated->toResponseArray($user),
            ],
            'message' => 'প্রার্থীর স্টেজ সফলভাবে আপডেট হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/applications/{id}/notes
     */
    public function addNote(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $application = JobApplication::with('jobPosting.company')->findOrFail($id);

        if (! $this->canManageApplication($user, $application)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'নোট যোগ করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'note' => 'required|string|max:3000',
        ]);

        $note = $this->atsService->addApplicationNote($application, $user, $validated['note']);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'note' => $note->toResponseArray(),
            ],
            'message' => 'অভ্যন্তরীণ নোট সফলভাবে সংরক্ষণ হয়েছে।',
        ], 201);
    }

    /**
     * GET /api/v1/applications/{id}/notes
     */
    public function listNotes(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $application = JobApplication::with('jobPosting.company')->findOrFail($id);

        if (! $this->canManageApplication($user, $application)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'অভ্যন্তরীণ নোট দেখার অনুমতি নেই।',
            ], 403);
        }

        $notes = ApplicationNote::with('author.profile')
            ->where('application_id', $application->id)
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'notes' => $notes->map(fn (ApplicationNote $n) => $n->toResponseArray())->values()->all(),
            ],
            'message' => 'নোট লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/applications/{id}/assign
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $application = JobApplication::with('jobPosting.company')->findOrFail($id);

        if (! $this->canManageApplication($user, $application)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'প্রার্থী অ্যাসাইন করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'assigned_to_user_id' => 'required|integer|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $assignedUser = User::findOrFail($validated['assigned_to_user_id']);
        $assignment = $this->atsService->assignCandidate(
            $application,
            $assignedUser,
            $user,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $assignment,
            'message' => 'প্রার্থী সফলভাবে অ্যাসাইন করা হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/applications/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $application = JobApplication::with('jobPosting.company')->findOrFail($id);

        if (! $this->canManageApplication($user, $application)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'প্রার্থী বাতিলের অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $updated = $this->atsService->updateApplicationStage(
            $application,
            JobApplication::STATUS_REJECTED,
            $validated['reason'] ?? 'প্রার্থীকে বিবেচনা করা সম্ভব হচ্ছে না।',
            $user
        );

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'application' => $updated->toResponseArray($user),
            ],
            'message' => 'আবেদনটি বাতিল করা হয়েছে।',
        ]);
    }

    private function canAccessCompanyAts(User $user, Company $company): bool
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

    private function canManageApplication(User $user, JobApplication $application): bool
    {
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($application->jobPosting && $application->jobPosting->user_id === $user->id) {
            return true;
        }

        if ($application->company) {
            return $this->canAccessCompanyAts($user, $application->company);
        }

        return false;
    }
}
