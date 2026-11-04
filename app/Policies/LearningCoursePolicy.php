<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LearningCourse;
use App\Models\User;

class LearningCoursePolicy
{
    /**
     * Determine whether the user can update the course.
     */
    public function update(User $user, LearningCourse $course): bool
    {
        return $user->id === $course->instructor_id || $user->hasRole(['ADMIN', 'SUPER_ADMIN']);
    }

    /**
     * Determine whether the user can delete the course.
     */
    public function delete(User $user, LearningCourse $course): bool
    {
        return $user->id === $course->instructor_id || $user->hasRole(['ADMIN', 'SUPER_ADMIN']);
    }

    /**
     * Determine whether the user can manage curriculum.
     */
    public function manageCurriculum(User $user, LearningCourse $course): bool
    {
        return $user->id === $course->instructor_id || $user->hasRole(['ADMIN', 'SUPER_ADMIN']);
    }
}
