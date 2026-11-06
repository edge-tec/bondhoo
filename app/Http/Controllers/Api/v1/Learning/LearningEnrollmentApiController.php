<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Learning;

use App\Http\Controllers\Controller;
use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningEnrollment;
use App\Services\Learning\EnrollmentService;
use App\Services\Learning\LearningProgressService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningEnrollmentApiController extends Controller
{
    public function __construct(
        protected EnrollmentService $enrollmentService,
        protected LearningProgressService $progressService
    ) {}

    /**
     * POST /api/v1/learning/courses/{id}/enroll
     */
    public function enroll(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $course = LearningCourse::findOrFail($id);
        $couponCode = $request->input('coupon_code');

        try {
            $enrollment = $this->enrollmentService->enrollUser($user, $course, $couponCode);

            return $this->successResponse($enrollment->load(['course.instructor', 'courseProgress']), 'Enrolled successfully in course.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * GET /api/v1/learning/my-learning
     */
    public function myLearning(Request $request): JsonResponse
    {
        $user = $request->user();
        $enrollments = LearningEnrollment::with(['course.instructor', 'courseProgress'])
            ->where('user_id', $user->id)
            ->where('status', LearningEnrollment::STATUS_ACTIVE)
            ->latest('last_accessed_at')
            ->get();

        return $this->successResponse($enrollments, 'My learning list retrieved successfully.');
    }

    /**
     * GET /api/v1/learning/lessons/{id}
     *
     * Protected lesson content (requires enrollment or preview access).
     */
    public function showLesson(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $lesson = LearningCourseLesson::with(['attachments', 'quiz', 'assignment', 'course'])->findOrFail($id);

        $this->authorize('view', $lesson);

        $progress = $lesson->userProgress($user);

        return $this->successResponse([
            'lesson' => $lesson,
            'progress' => $progress,
        ], 'Lesson loaded successfully.');
    }

    /**
     * POST /api/v1/learning/lessons/{id}/progress
     */
    public function updateProgress(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'position_seconds' => 'required|integer|min:0',
            'mark_completed' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $lesson = LearningCourseLesson::with('course')->findOrFail($id);

        $this->authorize('view', $lesson);

        $progress = $this->progressService->recordLessonProgress(
            $user,
            $lesson,
            (int) $request->input('position_seconds'),
            $request->boolean('mark_completed')
        );

        return $this->successResponse($progress, 'Lesson progress synced successfully.');
    }

    /**
     * GET /api/v1/learning/enrollments/{id}
     */
    public function showEnrollment(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $enrollment = LearningEnrollment::findOrFail($id);

        try {
            $details = $this->enrollmentService->getEnrollmentDetails($user, $enrollment);

            return $this->successResponse($details, 'Enrollment details retrieved.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }

    /**
     * GET /api/v1/learning/enrollments/{id}/progress
     */
    public function enrollmentProgress(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $enrollment = LearningEnrollment::with('courseProgress')->findOrFail($id);

        if ($enrollment->user_id !== $user->id && ! $user->hasRole(['ADMIN', 'SUPER_ADMIN'])) {
            return $this->errorResponse('Unauthorized to view this enrollment progress.', 403);
        }

        return $this->successResponse($enrollment->courseProgress, 'Enrollment progress retrieved.');
    }

    /**
     * POST /api/v1/learning/enrollments/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $enrollment = LearningEnrollment::findOrFail($id);

        try {
            $cancelled = $this->enrollmentService->cancelEnrollment($user, $enrollment);

            return $this->successResponse($cancelled, 'Enrollment cancelled successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }

    /**
     * GET /api/v1/learning/courses/{id}/progress
     */
    public function courseProgress(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $course = LearningCourse::findOrFail($id);

        $progress = $this->progressService->getCourseProgress($user, $course);

        return $this->successResponse($progress, 'Course progress retrieved.');
    }

    /**
     * GET /api/v1/learning/lessons/{id}/progress
     */
    public function lessonProgress(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $lesson = LearningCourseLesson::findOrFail($id);

        $this->authorize('view', $lesson);

        $progress = $this->progressService->getLessonProgress($user, $lesson);

        return $this->successResponse($progress, 'Lesson progress retrieved.');
    }

    /**
     * POST /api/v1/learning/lessons/{id}/complete
     */
    public function completeLesson(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $lesson = LearningCourseLesson::with('course')->findOrFail($id);

        $this->authorize('view', $lesson);

        $progress = $this->progressService->markLessonComplete($user, $lesson);

        return $this->successResponse($progress, 'Lesson marked as completed.');
    }

    /**
     * GET /api/v1/learning/lessons/{id}/stream
     */
    public function stream(Request $request, int $id): JsonResponse
    {
        $lesson = LearningCourseLesson::with('course')->findOrFail($id);
        $this->authorize('view', $lesson);

        return $this->successResponse([
            'lesson_id' => $lesson->id,
            'title' => $lesson->title,
            'stream_url' => $lesson->video_url,
            'duration_seconds' => $lesson->video_duration_seconds,
            'is_preview' => $lesson->is_preview,
            'expires_at' => now()->addHours(6)->toIso8601String(),
        ], 'Stream access authorized.');
    }

    /**
     * GET /api/v1/learning/lessons/{id}/attachments
     */
    public function attachments(Request $request, int $id): JsonResponse
    {
        $lesson = LearningCourseLesson::with('course')->findOrFail($id);
        $this->authorize('view', $lesson);

        return $this->successResponse($lesson->attachments, 'Lesson attachments retrieved.');
    }
}
