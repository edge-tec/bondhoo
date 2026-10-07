<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ReorderWorkExperienceRequest;
use App\Http\Requests\Profile\StoreWorkExperienceRequest;
use App\Http\Requests\Profile\UpdateWorkExperienceRequest;
use App\Models\ProfileExperience;
use App\Models\User;
use App\Services\WorkExperienceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkExperienceV2Controller extends Controller
{
    public function __construct(
        protected WorkExperienceService $workExperienceService
    ) {}

    /**
     * GET /api/v2/profile/work
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $experiences = $this->workExperienceService->getWorkExperiences($user, $user);

        return $this->successResponse(
            data: $experiences,
            message: 'কাজের অভিজ্ঞতার তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/{username}/work
     */
    public function userWork(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();

        $experiences = $this->workExperienceService->getWorkExperiences($targetUser, $viewer);

        return $this->successResponse(
            data: $experiences,
            message: 'ব্যবহারকারীর কাজের অভিজ্ঞতার তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/work
     */
    public function store(StoreWorkExperienceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $experience = $this->workExperienceService->createWorkExperience(
            targetUser: $user,
            data: $request->validated(),
            actor: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $this->workExperienceService->formatExperienceItem($experience),
            message: 'নতুন কাজের অভিজ্ঞতা সফলভাবে যুক্ত করা হয়েছে।',
            statusCode: 201
        );
    }

    /**
     * GET /api/v2/profile/work/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $viewer = $request->user('sanctum');
        /** @var ProfileExperience $experience */
        $experience = ProfileExperience::findOrFail($id);

        Gate::authorize('view', $experience);

        return $this->successResponse(
            data: $this->workExperienceService->formatExperienceItem($experience),
            message: 'কাজের অভিজ্ঞতার বিবরণ সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * PUT /api/v2/profile/work/{id}
     */
    public function update(UpdateWorkExperienceRequest $request, int $id): JsonResponse
    {
        /** @var ProfileExperience $experience */
        $experience = ProfileExperience::findOrFail($id);
        Gate::authorize('update', $experience);

        $updated = $this->workExperienceService->updateWorkExperience(
            experience: $experience,
            data: $request->validated(),
            actor: $request->user(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $this->workExperienceService->formatExperienceItem($updated),
            message: 'কাজের অভিজ্ঞতা সফলভাবে আপডেট করা হয়েছে।'
        );
    }

    /**
     * DELETE /api/v2/profile/work/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var ProfileExperience $experience */
        $experience = ProfileExperience::findOrFail($id);
        Gate::authorize('delete', $experience);

        $this->workExperienceService->deleteWorkExperience(
            experience: $experience,
            actor: $request->user(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: null,
            message: 'কাজের অভিজ্ঞতা সফলভাবে মুছে ফেলা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/work/reorder
     */
    public function reorder(ReorderWorkExperienceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $reordered = $this->workExperienceService->reorderWorkExperiences(
            targetUser: $user,
            orders: $request->validated(),
            actor: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $reordered,
            message: 'কাজের অভিজ্ঞতার ক্রম সফলভাবে সংরক্ষিত হয়েছে।'
        );
    }
}
