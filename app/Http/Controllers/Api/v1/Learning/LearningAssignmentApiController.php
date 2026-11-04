<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\SubmitAssignmentRequest;
use App\Models\LearningAssignment;
use App\Models\LearningAssignmentSubmission;
use App\Models\LearningCourse;
use App\Services\Learning\AssignmentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningAssignmentApiController extends Controller
{
    public function __construct(
        protected AssignmentService $assignmentService
    ) {}

    /**
     * GET /api/v1/learning/assignments/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $assignment = LearningAssignment::with('course')->findOrFail($id);

        $mySubmission = LearningAssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        return $this->successResponse([
            'assignment' => $assignment,
            'submission' => $mySubmission,
        ], 'Assignment details retrieved successfully.');
    }

    /**
     * POST /api/v1/learning/assignments/{id}/submit
     */
    public function submit(SubmitAssignmentRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $assignment = LearningAssignment::findOrFail($id);

        try {
            $submission = $this->assignmentService->submitAssignment($user, $assignment, $request->validated());

            return $this->successResponse($submission, 'Assignment submitted successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/v1/learning/assignments/submissions/{id}/grade
     */
    public function grade(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'score' => 'required|integer|min:0|max:100',
            'feedback' => 'nullable|string',
        ]);

        $user = $request->user();
        $submission = LearningAssignmentSubmission::with('assignment.course')->findOrFail($id);

        $course = $submission->assignment->course;
        if ($course->instructor_id !== $user->id && ! $user->hasRole(['ADMIN', 'SUPER_ADMIN'])) {
            return $this->errorResponse('Unauthorized to grade this assignment.', 403);
        }

        $graded = $this->assignmentService->gradeSubmission(
            $user,
            $submission,
            (int) $request->input('score'),
            $request->input('feedback')
        );

        return $this->successResponse($graded, 'Submission graded successfully.');
    }

    /**
     * GET /api/v1/learning/courses/{courseId}/assignments
     */
    public function indexCourse(Request $request, int $courseId): JsonResponse
    {
        $course = LearningCourse::findOrFail($courseId);
        $assignments = $this->assignmentService->getCourseAssignments($course);

        return $this->successResponse($assignments, 'Course assignments retrieved.');
    }

    /**
     * GET /api/v1/learning/assignment-submissions/{id}
     */
    public function showSubmission(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $submission = LearningAssignmentSubmission::with(['assignment.course', 'user:id,name,username'])->findOrFail($id);

        if ($submission->user_id !== $user->id && $submission->assignment->course->instructor_id !== $user->id && ! $user->hasRole(['ADMIN', 'SUPER_ADMIN'])) {
            return $this->errorResponse('Unauthorized to view this submission.', 403);
        }

        return $this->successResponse($submission, 'Submission details retrieved.');
    }

    /**
     * POST /api/v1/learning/assignment-submissions/{id}/resubmit
     */
    public function resubmit(SubmitAssignmentRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $submission = LearningAssignmentSubmission::with('assignment')->findOrFail($id);

        if ($submission->user_id !== $user->id) {
            return $this->errorResponse('Unauthorized.', 403);
        }

        try {
            $updated = $this->assignmentService->submitAssignment($user, $submission->assignment, $request->validated());

            return $this->successResponse($updated, 'Assignment resubmitted successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/v1/learning/instructor/courses/{courseId}/assignments
     */
    public function storeInstructor(Request $request, int $courseId): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'lesson_id' => 'nullable|integer',
            'total_points' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|integer|min:1',
            'due_date' => 'nullable|date',
            'allow_resubmissions' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $course = LearningCourse::findOrFail($courseId);

        try {
            $assignment = $this->assignmentService->createAssignment($user, $course, $request->all());

            return $this->successResponse($assignment, 'Assignment created successfully.', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }

    /**
     * PUT /api/v1/learning/instructor/assignments/{id}
     */
    public function updateInstructor(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $assignment = LearningAssignment::with('course')->findOrFail($id);

        try {
            $updated = $this->assignmentService->updateAssignment($user, $assignment, $request->all());

            return $this->successResponse($updated, 'Assignment updated successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }

    /**
     * DELETE /api/v1/learning/instructor/assignments/{id}
     */
    public function destroyInstructor(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $assignment = LearningAssignment::with('course')->findOrFail($id);

        try {
            $this->assignmentService->deleteAssignment($user, $assignment);

            return $this->successResponse(null, 'Assignment deleted successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }

    /**
     * GET /api/v1/learning/instructor/assignments/{id}/submissions
     */
    public function submissionsInstructor(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $assignment = LearningAssignment::with('course')->findOrFail($id);

        try {
            $submissions = $this->assignmentService->getAssignmentSubmissions($user, $assignment);

            return $this->successResponse($submissions, 'Assignment submissions retrieved.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }
}
