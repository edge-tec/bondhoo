<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LearningCourse;
use App\Models\User;

class LearningCoursePolicy
{
    /**
     * Determine whether the user can view the course (including unpublished drafts).
     */
    public function view(?User $user, LearningCourse $course): bool
    {
        if ($course->status === LearningCourse::STATUS_PUBLISHED) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return (int) $user->id === (int) $course->instructor_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the course.
     */
    public function update(User $user, LearningCourse $course): bool
    {
        return (int) $user->id === (int) $course->instructor_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the course.
     */
    public function delete(User $user, LearningCourse $course): bool
    {
        return (int) $user->id === (int) $course->instructor_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can manage curriculum.
     */
    public function manageCurriculum(User $user, LearningCourse $course): bool
    {
        return (int) $user->id === (int) $course->instructor_id || $user->isAdmin();
    }
}
