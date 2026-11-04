<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\LearningCourse;
use App\Models\LearningCourseReview;
use App\Models\LearningEnrollment;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class CourseReviewService
{
    /**
     * Submit a review for a course.
     */
    public function submitReview(
        User $user,
        LearningCourse $course,
        int $rating,
        ?string $content = null
    ): LearningCourseReview {
        $isEnrolled = LearningEnrollment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('status', LearningEnrollment::STATUS_ACTIVE)
            ->exists();

        if (! $isEnrolled) {
            throw new Exception('Only actively enrolled students may review this course.');
        }

        return DB::transaction(function () use ($user, $course, $rating, $content) {
            $review = LearningCourseReview::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'user_id' => $user->id,
                ],
                [
                    'rating' => min(5, max(1, $rating)),
                    'content' => $content,
                    'is_approved' => true,
                ]
            );

            // Recalculate course average rating
            $avgRating = LearningCourseReview::where('course_id', $course->id)
                ->where('is_approved', true)
                ->avg('rating');

            $count = LearningCourseReview::where('course_id', $course->id)
                ->where('is_approved', true)
                ->count();

            $course->update([
                'rating_avg' => round((float) $avgRating, 2),
                'rating_count' => $count,
            ]);

            return $review->load('user');
        });
    }

    /**
     * Instructor replies to a student review.
     */
    public function replyToReview(
        User $instructor,
        LearningCourseReview $review,
        string $reply
    ): LearningCourseReview {
        $review->update([
            'instructor_reply' => $reply,
            'replied_at' => now(),
        ]);

        return $review;
    }
}
