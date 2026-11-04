<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LearningCourseLesson;
use App\Models\User;

class LearningCourseLessonPolicy
{
    /**
     * Determine whether the user can access lesson video and resources.
     */
    public function view(User $user, LearningCourseLesson $lesson): bool
    {
        // Free preview lessons are accessible to all authenticated users
        if ($lesson->is_preview) {
            return true;
        }

        $course = $lesson->course;

        // Instructor and Admins always have access
        if ($user->id === $course->instructor_id || $user->hasRole(['ADMIN', 'SUPER_ADMIN'])) {
            return true;
        }

        // Active enrollment gives access
        return $course->isEnrolledByUser($user);
    }
}
