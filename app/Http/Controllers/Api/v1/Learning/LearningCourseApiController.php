<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\StoreCourseRequest;
use App\Models\LearningCategory;
use App\Models\LearningCourse;
use App\Models\LearningEnrollment;
use App\Services\Learning\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LearningCourseApiController extends Controller
{
    public function __construct(
        protected CourseService $courseService
    ) {}

    /**
     * GET /api/v1/learning/categories
     */
    public function categories(): JsonResponse
    {
        $categories = LearningCategory::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse($categories, 'Categories retrieved successfully.');
    }

    /**
     * GET /api/v1/learning/courses
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'q',
            'category_id',
            'category_slug',
            'level',
            'type',
            'skill',
            'min_price',
            'max_price',
            'min_rating',
            'sort_by',
        ]);

        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $courses = $this->courseService->getMarketplaceCourses($filters, $perPage);

        $user = $request->user('sanctum') ?? $request->user('web');
        $enrolledCourseIds = [];
        if ($user && count($courses->items()) > 0) {
            $courseIds = array_map(fn ($c) => $c->id, $courses->items());
            $enrolledCourseIds = LearningEnrollment::where('user_id', $user->id)
                ->whereIn('course_id', $courseIds)
                ->whereIn('status', [LearningEnrollment::STATUS_ACTIVE, LearningEnrollment::STATUS_COMPLETED])
                ->pluck('course_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        foreach ($courses->items() as $course) {
            $course->setAttribute('is_enrolled', in_array((int) $course->id, $enrolledCourseIds, true));
        }

        return $this->successResponse(
            $courses->items(),
            'Courses retrieved successfully.',
            200,
            [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ]
        );
    }

    /**
     * GET /api/v1/learning/courses/{slug}
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user('web');
        $course = $this->courseService->getCourseDetails($slug, $user);

        if (! $course) {
            return $this->errorResponse('Course not found.', 404);
        }

        $isEnrolled = $course->isEnrolledByUser($user);
        $course->setAttribute('is_enrolled', $isEnrolled);

        return $this->successResponse([
            'course' => $course,
            'is_enrolled' => $isEnrolled,
        ], 'Course details retrieved successfully.');
    }

    /**
     * POST /api/v1/learning/courses
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $user = $request->user();
        $course = $this->courseService->createCourse($user, $request->validated());

        return $this->successResponse($course, 'Course created successfully as draft.', 201);
    }

    /**
     * PUT /api/v1/learning/courses/{id}
     */
    public function update(StoreCourseRequest $request, int $id): JsonResponse
    {
        $course = LearningCourse::findOrFail($id);
        $this->authorize('update', $course);

        $updated = $this->courseService->updateCourse($course, $request->validated());

        return $this->successResponse($updated, 'Course updated successfully.');
    }

    /**
     * POST /api/v1/learning/courses/{id}/publish
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $course = LearningCourse::findOrFail($id);
        $this->authorize('update', $course);

        $published = $this->courseService->publishCourse($course, true);

        return $this->successResponse($published, 'Course published successfully to marketplace.');
    }

    /**
     * GET /api/v1/learning/courses/featured
     */
    public function featured(): JsonResponse
    {
        $courses = $this->courseService->getFeaturedCourses();

        return $this->successResponse($courses, 'Featured courses retrieved successfully.');
    }

    /**
     * GET /api/v1/learning/courses/popular
     */
    public function popular(): JsonResponse
    {
        $courses = $this->courseService->getPopularCourses();

        return $this->successResponse($courses, 'Popular courses retrieved successfully.');
    }

    /**
     * GET /api/v1/learning/categories/{category}
     */
    public function showCategory(string|int $category): JsonResponse
    {
        $query = LearningCategory::where('is_active', true)->with([
            'children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            },
            'courses' => function ($q) {
                $q->where('status', LearningCourse::STATUS_PUBLISHED)->limit(10);
            },
        ]);

        $cat = is_numeric($category)
            ? $query->find($category)
            : $query->where('slug', $category)->first();

        if (! $cat) {
            return $this->errorResponse('Category not found.', 404);
        }

        return $this->successResponse($cat, 'Category details retrieved successfully.');
    }

    /**
     * POST /api/v1/learning/admin/categories
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            return $this->errorResponse('Unauthorized admin access.', 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:120|unique:learning_categories,slug',
            'icon' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:learning_categories,id',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = LearningCategory::create($validated);

        return $this->successResponse($category, 'Category created successfully.', 201);
    }

    /**
     * PUT /api/v1/learning/admin/categories/{category}
     */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            return $this->errorResponse('Unauthorized admin access.', 403);
        }

        $category = LearningCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'slug' => 'sometimes|required|string|max:120|unique:learning_categories,slug,'.$id,
            'icon' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:learning_categories,id',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update($validated);

        return $this->successResponse($category, 'Category updated successfully.');
    }

    /**
     * DELETE /api/v1/learning/admin/categories/{category}
     */
    public function destroyCategory(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            return $this->errorResponse('Unauthorized admin access.', 403);
        }

        $category = LearningCategory::findOrFail($id);

        if ($category->courses()->exists()) {
            return $this->errorResponse('Cannot delete category with associated courses.', 422);
        }

        $category->delete();

        return $this->successResponse(null, 'Category deleted successfully.');
    }

    /**
     * GET /api/v1/learning/instructor/courses
     */
    public function instructorCourses(Request $request): JsonResponse
    {
        $user = $request->user();
        $courses = $this->courseService->getInstructorCourses($user);

        return $this->successResponse(
            $courses->items(),
            'Instructor courses retrieved successfully.',
            200,
            [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ]
        );
    }

    /**
     * GET /api/v1/learning/instructor/courses/{id}
     */
    public function instructorCourseShow(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $course = LearningCourse::with(['sections.lessons.attachments', 'category', 'skills'])->findOrFail($id);

        if ((int) $course->instructor_id !== (int) $user->id && ! $user->isAdmin()) {
            return $this->errorResponse('Unauthorized access to course.', 403);
        }

        return $this->successResponse($course, 'Course details retrieved successfully.');
    }

    /**
     * DELETE /api/v1/learning/courses/{id} & DELETE /api/v1/learning/instructor/courses/{id}
     */
    public function destroyCourse(Request $request, int $id): JsonResponse
    {
        $course = LearningCourse::findOrFail($id);
        $this->authorize('delete', $course);

        $this->courseService->deleteCourse($course);

        return $this->successResponse(null, 'Course deleted successfully.');
    }
}
