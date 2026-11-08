<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\StoreLessonRequest;
use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseSection;
use App\Models\LearningLessonProgress;
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
    public function index(Request $request, int $id): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user('web');
        $course = LearningCourse::findOrFail($id);

        $isPrivilegedViewer = $user && ((int) $user->id === (int) $course->instructor_id || $user->isAdmin());
        $hasFullContentAccess = $isPrivilegedViewer || $course->isEnrolledByUser($user);

        $sectionsQuery = $course->sections()->orderBy('sort_order');
        if (! $isPrivilegedViewer) {
            $sectionsQuery->where('is_published', true);
        }

        $sections = $sectionsQuery->with([
            'lessons' => function ($q) use ($isPrivilegedViewer) {
                if (! $isPrivilegedViewer) {
                    $q->where('is_published', true);
                }
                $q->orderBy('sort_order');
            },
            'lessons.attachments',
            'lessons.quiz:id,lesson_id,course_id,title,time_limit_minutes,passing_score,max_attempts',
            'lessons.assignment:id,lesson_id,course_id,title,max_score,passing_score,allow_resubmissions',
        ])->get();

        $progressMap = [];
        if ($user) {
            $progressMap = LearningLessonProgress::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->get(['lesson_id', 'is_completed', 'last_position_seconds'])
                ->keyBy('lesson_id')
                ->all();
        }

        foreach ($sections as $section) {
            foreach ($section->lessons as $lesson) {
                $prog = $progressMap[$lesson->id] ?? null;
                $lesson->setAttribute('is_completed', (bool) ($prog?->is_completed ?? false));
                $lesson->setAttribute('last_position_seconds', (int) ($prog?->last_position_seconds ?? 0));

                if (! $hasFullContentAccess && ! $lesson->is_preview) {
                    $lesson->setAttribute('video_url', null);
                    $lesson->setAttribute('content', null);
                    $lesson->setRelation('attachments', collect());
                }
            }
        }

        return $this->successResponse($sections, 'Curriculum retrieved successfully.');
    }

    /**
     * POST /api/v1/learning/courses/{id}/sections
     */
    public function storeSection(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_published' => 'nullable|boolean',
        ]);

        $course = LearningCourse::findOrFail($id);
        $this->authorize('manageCurriculum', $course);

        $section = $this->contentService->addSection($course, $validated);

        return $this->successResponse($section, 'Section added successfully.', 201);
    }

    /**
     * PUT /api/v1/learning/sections/{id}
     */
    public function updateSection(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:200',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_published' => 'nullable|boolean',
        ]);

        $section = LearningCourseSection::with('course')->findOrFail($id);
        $this->authorize('manageCurriculum', $section->course);

        $updated = $this->contentService->updateSection($section, $validated);

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
