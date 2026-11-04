<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseProgress;
use App\Models\LearningEnrollment;
use App\Models\LearningLessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LearningProgressService
{
    public function __construct(
        protected CertificateService $certificateService
    ) {}

    /**
     * Record progress on a lesson (playback position & completion).
     */
    public function recordLessonProgress(
        User $user,
        LearningCourseLesson $lesson,
        int $positionSeconds,
        ?bool $markCompleted = null
    ): LearningLessonProgress {
        return DB::transaction(function () use ($user, $lesson, $positionSeconds, $markCompleted) {
            $enrollment = LearningEnrollment::where('course_id', $lesson->course_id)
                ->where('user_id', $user->id)
                ->whereIn('status', [LearningEnrollment::STATUS_ACTIVE, LearningEnrollment::STATUS_COMPLETED])
                ->firstOrFail();

            $lessonProgress = LearningLessonProgress::firstOrNew([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ]);

            $isNewlyCompleted = false;

            if (! $lessonProgress->exists) {
                $lessonProgress->enrollment_id = $enrollment->id;
                $lessonProgress->course_id = $lesson->course_id;
                $lessonProgress->watch_time_seconds = $positionSeconds;
                $lessonProgress->last_position_seconds = $positionSeconds;
                $lessonProgress->is_completed = false;
            } else {
                $lessonProgress->watch_time_seconds = max($lessonProgress->watch_time_seconds, $positionSeconds);
                $lessonProgress->last_position_seconds = $positionSeconds;
            }

            // Auto-complete if watch time is >= 80% of video duration or explicit markCompleted
            $autoComplete = false;
            if ($lesson->video_duration_seconds > 0) {
                $autoComplete = ($positionSeconds / $lesson->video_duration_seconds) >= 0.80;
            } elseif ($lesson->type === LearningCourseLesson::TYPE_ARTICLE) {
                $autoComplete = true;
            }

            if (($markCompleted === true || $autoComplete) && ! $lessonProgress->is_completed) {
                $lessonProgress->is_completed = true;
                $lessonProgress->completed_at = now();
                $isNewlyCompleted = true;
            }

            $lessonProgress->save();
            $lessonProgress->refresh();

            // Recalculate course progress
            $this->recalculateCourseProgress($enrollment, $user, $lesson->course);

            return $lessonProgress;
        });
    }

    /**
     * Recalculate overall course progress percentage and issue certificate if 100%.
     */
    public function recalculateCourseProgress(
        LearningEnrollment $enrollment,
        User $user,
        LearningCourse $course
    ): LearningCourseProgress {
        $totalLessons = (int) $course->lessons()->where('is_published', true)->count();
        if ($totalLessons === 0) {
            $totalLessons = 1;
        }

        $completedLessons = LearningLessonProgress::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->count();

        $percentage = min(100.00, round(($completedLessons / $totalLessons) * 100, 2));
        $isCompleted = ($percentage >= 100.00);

        $courseProgress = LearningCourseProgress::updateOrCreate(
            ['enrollment_id' => $enrollment->id],
            [
                'course_id' => $course->id,
                'user_id' => $user->id,
                'completed_lessons_count' => $completedLessons,
                'total_lessons_count' => $totalLessons,
                'percentage' => $percentage,
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? ($enrollment->completed_at ?? now()) : null,
            ]
        );

        if ($isCompleted && ! $enrollment->completed_at) {
            $enrollment->update([
                'status' => LearningEnrollment::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            // Auto issue completion certificate
            $this->certificateService->issueCertificate($user, $course);
        }

        return $courseProgress;
    }

    /**
     * Get student progress summary for a course.
     */
    public function getCourseProgress(User $user, LearningCourse $course): array
    {
        $enrollment = LearningEnrollment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->whereIn('status', [LearningEnrollment::STATUS_ACTIVE, LearningEnrollment::STATUS_COMPLETED])
            ->first();

        $progress = $enrollment ? LearningCourseProgress::where('enrollment_id', $enrollment->id)->first() : null;

        $lessonProgresses = LearningLessonProgress::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('lesson_id');

        return [
            'course_id' => $course->id,
            'is_enrolled' => (bool) $enrollment,
            'progress' => $progress,
            'completed_lesson_ids' => $lessonProgresses->where('is_completed', true)->pluck('lesson_id')->values(),
            'last_accessed_lesson_id' => $lessonProgresses->sortByDesc('updated_at')->first()?->lesson_id,
        ];
    }

    /**
     * Get progress on a single lesson.
     */
    public function getLessonProgress(User $user, LearningCourseLesson $lesson): ?LearningLessonProgress
    {
        return LearningLessonProgress::where('lesson_id', $lesson->id)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Explicitly mark a lesson complete if criteria satisfied.
     */
    public function markLessonComplete(User $user, LearningCourseLesson $lesson): LearningLessonProgress
    {
        return $this->recordLessonProgress(
            $user,
            $lesson,
            $lesson->video_duration_seconds > 0 ? $lesson->video_duration_seconds : 1,
            true
        );
    }
}
