<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\User;

class LearningCourseLessonPolicy
{
    /**
     * Determine whether the user can access lesson video and resources.
     */
    public function view(User $user, LearningCourseLesson $lesson): bool
    {
        $course = $lesson->course;
        if (! $course) {
            return false;
        }

        // Instructor and Admins always have access
        if ((int) $user->id === (int) $course->instructor_id || $user->isAdmin()) {
            return true;
        }

        if (! $lesson->is_published || $course->status !== LearningCourse::STATUS_PUBLISHED) {
            return false;
        }

        // Free preview lessons on published courses are accessible to authenticated users
        if ($lesson->is_preview) {
            return true;
        }

        // Active or completed enrollment gives access
        return $course->isEnrolledByUser($user);
    }

    /**
     * Determine whether the user can record or complete lesson progress.
     */
    public function trackProgress(User $user, LearningCourseLesson $lesson): bool
    {
        $course = $lesson->course;
        if (! $course || ! $lesson->is_published) {
            return false;
        }

        return $course->isEnrolledByUser($user);
    }
}
