<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\StoreLessonRequest;
use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseSection;
use App\Services\Learning\CourseContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningCurriculumApiController extends Controller
{
    public function __construct(
        protected CourseContentService $contentService
    ) {}

    /**
     * GET /api/v1/learning/courses/{id}/curriculum
     */
    public function index(int $id): JsonResponse
    {
        $course = LearningCourse::with(['sections.lessons.attachments', 'sections.lessons.quiz', 'sections.lessons.assignment'])->findOrFail($id);

        return $this->successResponse($course->sections, 'Curriculum retrieved successfully.');
    }

    /**
     * POST /api/v1/learning/courses/{id}/sections
     */
    public function storeSection(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $course = LearningCourse::findOrFail($id);
        $this->authorize('manageCurriculum', $course);

        $section = $this->contentService->addSection($course, $request->all());

        return $this->successResponse($section, 'Section added successfully.', 201);
    }

    /**
     * PUT /api/v1/learning/sections/{id}
     */
    public function updateSection(Request $request, int $id): JsonResponse
    {
        $section = LearningCourseSection::with('course')->findOrFail($id);
        $this->authorize('manageCurriculum', $section->course);

        $updated = $this->contentService->updateSection($section, $request->all());

        return $this->successResponse($updated, 'Section updated successfully.');
    }

    /**
     * DELETE /api/v1/learning/sections/{id}
     */
    public function destroySection(int $id): JsonResponse
    {
        $section = LearningCourseSection::with('course')->findOrFail($id);
        $this->authorize('manageCurriculum', $section->course);

        $this->contentService->deleteSection($section);

        return $this->successResponse(null, 'Section deleted successfully.');
    }

    /**
     * POST /api/v1/learning/sections/{id}/lessons
     */
    public function storeLesson(StoreLessonRequest $request, int $id): JsonResponse
    {
        $section = LearningCourseSection::with('course')->findOrFail($id);
        $this->authorize('manageCurriculum', $section->course);

        $lesson = $this->contentService->addLesson($section, $request->validated());

        return $this->successResponse($lesson, 'Lesson added successfully.', 201);
    }

    /**
     * PUT /api/v1/learning/lessons/{id}
     */
    public function updateLesson(StoreLessonRequest $request, int $id): JsonResponse
    {
        $lesson = LearningCourseLesson::with('course')->findOrFail($id);
        $this->authorize('manageCurriculum', $lesson->course);

        $updated = $this->contentService->updateLesson($lesson, $request->validated());

        return $this->successResponse($updated, 'Lesson updated successfully.');
    }

    /**
     * DELETE /api/v1/learning/lessons/{id}
     */
    public function destroyLesson(int $id): JsonResponse
    {
        $lesson = LearningCourseLesson::with('course')->findOrFail($id);
        $this->authorize('manageCurriculum', $lesson->course);

        $this->contentService->deleteLesson($lesson);

        return $this->successResponse(null, 'Lesson deleted successfully.');
    }
}
