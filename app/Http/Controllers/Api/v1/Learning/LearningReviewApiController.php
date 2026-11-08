<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\StoreCourseReviewRequest;
use App\Models\LearningCourse;
use App\Models\LearningCourseReview;
use App\Services\Learning\CourseReviewService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningReviewApiController extends Controller
{
    public function __construct(
        protected CourseReviewService $reviewService
    ) {}

    /**
     * GET /api/v1/learning/courses/{id}/reviews
     */
    public function index(int $id): JsonResponse
    {
        $reviews = LearningCourseReview::with('user')
            ->where('course_id', $id)
            ->where('is_approved', true)
            ->latest()
            ->paginate(15);

        return $this->successResponse($reviews->items(), 'Reviews retrieved successfully.', 200, [
            'current_page' => $reviews->currentPage(),
            'total' => $reviews->total(),
        ]);
    }

    /**
     * POST /api/v1/learning/courses/{id}/reviews
     */
    public function store(StoreCourseReviewRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $course = LearningCourse::findOrFail($id);

        try {
            $review = $this->reviewService->submitReview(
                $user,
                $course,
                (int) $request->input('rating'),
                $request->input('content')
            );

            return $this->successResponse($review, 'Review submitted successfully.', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/v1/learning/reviews/{id}/reply
     */
    public function reply(Request $request, int $id): JsonResponse
    {
        $request->validate(['reply' => 'required|string|max:1000']);
        $user = $request->user();

        $review = LearningCourseReview::with('course')->findOrFail($id);
        if (! $review->course || ((int) $review->course->instructor_id !== (int) $user->id && ! $user->isAdmin())) {
            return $this->errorResponse('Unauthorized to reply to this review.', 403);
        }

        $updated = $this->reviewService->replyToReview($user, $review, $request->input('reply'));

        return $this->successResponse($updated, 'Reply posted successfully.');
    }
}
