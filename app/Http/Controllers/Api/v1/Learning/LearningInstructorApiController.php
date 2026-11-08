<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Learning;

use App\Http\Controllers\Controller;
use App\Models\LearningInstructor;
use App\Services\Learning\InstructorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningInstructorApiController extends Controller
{
    public function __construct(
        protected InstructorService $instructorService
    ) {}

    /**
     * POST /api/v1/learning/instructors/apply
     */
    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'headline' => 'required|string|max:255',
            'biography' => 'nullable|string',
            'expertise' => 'nullable|array',
            'website_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'github_url' => 'nullable|url|max:255',
        ]);

        $user = $request->user();
        $instructor = $this->instructorService->applyForInstructor($user, $request->all());

        return $this->successResponse($instructor, 'Instructor profile registered successfully.', 201);
    }

    /**
     * GET /api/v1/learning/instructors/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $this->instructorService->getInstructorDashboard($user);

        return $this->successResponse($data, 'Instructor dashboard metrics retrieved successfully.');
    }

    /**
     * GET /api/v1/learning/instructors/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $instructor = LearningInstructor::with('user')->where('user_id', $user->id)->first();

        if (! $instructor) {
            return $this->errorResponse('Instructor profile not found.', 404);
        }

        return $this->successResponse($instructor, 'Instructor profile retrieved successfully.');
    }

    /**
     * GET /api/v1/learning/instructors/{id}
     */
    public function show(int $id): JsonResponse
    {
        $instructor = $this->instructorService->getInstructorProfile($id);

        if (! $instructor) {
            return $this->errorResponse('Instructor not found.', 404);
        }

        return $this->successResponse($instructor, 'Instructor details retrieved successfully.');
    }

    /**
     * POST /api/v1/learning/admin/instructors/{id}/verify
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            return $this->errorResponse('Unauthorized admin access.', 403);
        }

        $instructor = LearningInstructor::findOrFail($id);
        $verified = $this->instructorService->verifyInstructor($instructor, true, $user);

        return $this->successResponse($verified, 'Instructor verified successfully.');
    }
}
