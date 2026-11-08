<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\LearningAssignment;
use App\Models\LearningAssignmentSubmission;
use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningEnrollment;
use App\Models\LearningLessonProgress;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    /**
     * Submit an assignment response.
     */
    public function submitAssignment(
        User $user,
        LearningAssignment $assignment,
        array $data
    ): LearningAssignmentSubmission {
        $existing = LearningAssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && ! $assignment->allow_resubmissions && $existing->status === LearningAssignmentSubmission::STATUS_GRADED) {
            throw new Exception('Resubmissions are not permitted for this assignment.');
        }

        return LearningAssignmentSubmission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
            ],
            [
                'submission_text' => $data['submission_text'] ?? null,
                'file_url' => $data['file_url'] ?? null,
                'status' => LearningAssignmentSubmission::STATUS_SUBMITTED,
                'score' => null,
                'feedback' => null,
            ]
        );
    }

    /**
     * Instructor grades a student submission.
     */
    public function gradeSubmission(
        User $instructor,
        LearningAssignmentSubmission $submission,
        int $score,
        ?string $feedback = null
    ): LearningAssignmentSubmission {
        return DB::transaction(function () use ($instructor, $submission, $score, $feedback) {
            $lockedSubmission = LearningAssignmentSubmission::with('assignment.course')
                ->whereKey($submission->id)
                ->lockForUpdate()
                ->firstOrFail();

            $assignment = $lockedSubmission->assignment;
            $passed = $score >= $assignment->passing_score;

            $lockedSubmission->update([
                'score' => $score,
                'feedback' => $feedback,
                'status' => LearningAssignmentSubmission::STATUS_GRADED,
                'graded_by' => $instructor->id,
                'graded_at' => now(),
            ]);

            // If passed, mark assignment lesson as completed and recalculate course progress
            if ($passed && $assignment->lesson_id) {
                $enrollment = LearningEnrollment::where('course_id', $assignment->course_id)
                    ->where('user_id', $lockedSubmission->user_id)
                    ->whereIn('status', [LearningEnrollment::STATUS_ACTIVE, LearningEnrollment::STATUS_COMPLETED])
                    ->lockForUpdate()
                    ->first();

                LearningLessonProgress::updateOrCreate(
                    [
                        'user_id' => $lockedSubmission->user_id,
                        'lesson_id' => $assignment->lesson_id,
                    ],
                    [
                        'enrollment_id' => $enrollment?->id,
                        'course_id' => $assignment->course_id,
                        'is_completed' => true,
                        'completed_at' => now(),
                    ]
                );

                if ($enrollment && $assignment->course) {
                    $student = User::find($lockedSubmission->user_id);
                    if ($student) {
                        app(LearningProgressService::class)->recalculateCourseProgress($enrollment, $student, $assignment->course);
                    }
                }
            }

            return $lockedSubmission->fresh();
        });
    }

    /**
     * Instructor creates an assignment for a course.
     */
    public function createAssignment(User $instructor, LearningCourse $course, array $data): LearningAssignment
    {
        if ((int) $course->instructor_id !== (int) $instructor->id && ! $instructor->isAdmin()) {
            throw new Exception('Unauthorized to create assignments for this course.');
        }

        $lessonId = isset($data['lesson_id']) ? (int) $data['lesson_id'] : null;
        if ($lessonId) {
            $lessonBelongsToCourse = LearningCourseLesson::where('id', $lessonId)
                ->where('course_id', $course->id)
                ->exists();
            if (! $lessonBelongsToCourse) {
                throw new Exception('Selected lesson does not belong to this course.');
            }
        } else {
            $section = $course->sections()->first();
            if (! $section) {
                $section = $course->sections()->create([
                    'title' => 'Assignments & Projects',
                    'sort_order' => 999,
                ]);
            }
            $lesson = LearningCourseLesson::create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'title' => $data['title'] ?? 'Course Assignment',
                'slug' => 'assignment-'.uniqid(),
                'type' => LearningCourseLesson::TYPE_ASSIGNMENT,
                'is_mandatory' => true,
                'is_published' => true,
                'sort_order' => 999,
            ]);
            $lessonId = $lesson->id;
        }

        return LearningAssignment::create([
            'course_id' => $course->id,
            'lesson_id' => $lessonId,
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? ($data['description'] ?? 'Complete the assignment according to provided instructions.'),
            'max_score' => (int) ($data['max_score'] ?? ($data['total_points'] ?? 100)),
            'passing_score' => (int) ($data['passing_score'] ?? 60),
            'due_days_after_enrollment' => isset($data['due_days_after_enrollment']) ? (int) $data['due_days_after_enrollment'] : null,
            'allow_resubmissions' => (bool) ($data['allow_resubmissions'] ?? true),
        ]);
    }

    /**
     * Instructor updates an assignment (strictly whitelisted fields; course_id immutable).
     */
    public function updateAssignment(User $instructor, LearningAssignment $assignment, array $data): LearningAssignment
    {
        $course = $assignment->course;
        if (! $course || ((int) $course->instructor_id !== (int) $instructor->id && ! $instructor->isAdmin())) {
            throw new Exception('Unauthorized to update this assignment.');
        }

        $safeData = array_intersect_key($data, array_flip([
            'title',
            'instructions',
            'max_score',
            'passing_score',
            'due_days_after_enrollment',
            'allow_resubmissions',
        ]));

        if (isset($data['description']) && ! isset($safeData['instructions'])) {
            $safeData['instructions'] = $data['description'];
        }
        if (isset($data['total_points']) && ! isset($safeData['max_score'])) {
            $safeData['max_score'] = (int) $data['total_points'];
        }

        $assignment->update($safeData);

        return $assignment->fresh();
    }

    /**
     * Instructor deletes an assignment.
     */
    public function deleteAssignment(User $instructor, LearningAssignment $assignment): void
    {
        $course = $assignment->course;
        if (! $course || ((int) $course->instructor_id !== (int) $instructor->id && ! $instructor->isAdmin())) {
            throw new Exception('Unauthorized to delete this assignment.');
        }

        $assignment->delete();
    }

    /**
     * List all assignments for a course.
     */
    public function getCourseAssignments(LearningCourse $course): Collection
    {
        return LearningAssignment::where('course_id', $course->id)
            ->with('lesson:id,title')
            ->get();
    }

    /**
     * Instructor views all submissions for an assignment.
     */
    public function getAssignmentSubmissions(User $instructor, LearningAssignment $assignment): Collection
    {
        $course = $assignment->course;
        if (! $course || ((int) $course->instructor_id !== (int) $instructor->id && ! $instructor->isAdmin())) {
            throw new Exception('Unauthorized to view submissions for this assignment.');
        }

        return LearningAssignmentSubmission::where('assignment_id', $assignment->id)
            ->with('user:id,name,username')
            ->latest('updated_at')
            ->get();
    }
}
