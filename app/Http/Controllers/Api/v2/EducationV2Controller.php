<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ReorderEducationRequest;
use App\Http\Requests\Profile\StoreEducationRequest;
use App\Http\Requests\Profile\UpdateEducationRequest;
use App\Models\ProfileEducation;
use App\Models\User;
use App\Services\EducationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EducationV2Controller extends Controller
{
    public function __construct(
        protected EducationService $educationService
    ) {}

    /**
     * GET /api/v2/profile/education
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $educations = $this->educationService->getEducations($user, $user);

        return $this->successResponse(
            data: $educations,
            message: 'শিক্ষাগত যোগ্যতার তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/{username}/education
     */
    public function userEducations(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();

        $educations = $this->educationService->getEducations($targetUser, $viewer);

        return $this->successResponse(
            data: $educations,
            message: 'ব্যবহারকারীর শিক্ষাগত যোগ্যতার তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/education
     */
    public function store(StoreEducationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $education = $this->educationService->createEducation(
            targetUser: $user,
            data: $request->validated(),
            actor: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $this->educationService->formatEducationItem($education),
            message: 'নতুন শিক্ষাগত যোগ্যতা সফলভাবে যুক্ত করা হয়েছে।',
            statusCode: 201
        );
    }

    /**
     * GET /api/v2/profile/education/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $viewer = $request->user('sanctum');
        /** @var ProfileEducation $education */
        $education = ProfileEducation::findOrFail($id);

        Gate::authorize('view', $education);

        return $this->successResponse(
            data: $this->educationService->formatEducationItem($education),
            message: 'শিক্ষাগত যোগ্যতার বিবরণ সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * PUT /api/v2/profile/education/{id}
     */
    public function update(UpdateEducationRequest $request, int $id): JsonResponse
    {
        /** @var ProfileEducation $education */
        $education = ProfileEducation::findOrFail($id);
        Gate::authorize('update', $education);

        $updated = $this->educationService->updateEducation(
            education: $education,
            data: $request->validated(),
            actor: $request->user(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $this->educationService->formatEducationItem($updated),
            message: 'শিক্ষাগত যোগ্যতা সফলভাবে আপডেট করা হয়েছে।'
        );
    }

    /**
     * DELETE /api/v2/profile/education/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var ProfileEducation $education */
        $education = ProfileEducation::findOrFail($id);
        Gate::authorize('delete', $education);

        $this->educationService->deleteEducation(
            education: $education,
            actor: $request->user(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: null,
            message: 'শিক্ষাগত যোগ্যতা সফলভাবে মুছে ফেলা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/education/reorder
     */
    public function reorder(ReorderEducationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $reordered = $this->educationService->reorderEducations(
            targetUser: $user,
            orders: $request->validated(),
            actor: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $reordered,
            message: 'শিক্ষাগত যোগ্যতার ক্রম সফলভাবে সংরক্ষিত হয়েছে।'
        );
    }
}
