<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningAssignment;
use App\Models\LearningAssignmentSubmission;
use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseSection;
use App\Models\LearningEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13Stage05AssignmentAndStorageSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $otherInstructor;

    protected User $studentA;

    protected User $studentB;

    protected LearningCourse $course;

    protected LearningAssignment $assignment;

    protected LearningAssignmentSubmission $submissionA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['name' => 'Prof. Donald Knuth']);
        $this->otherInstructor = User::factory()->create(['name' => 'Prof. Other']);
        $this->studentA = User::factory()->create(['name' => 'Student Alice']);
        $this->studentB = User::factory()->create(['name' => 'Student Bob']);

        $this->course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'The Art of Computer Programming',
            'slug' => 'art-of-computer-programming',
            'description' => 'Fundamental algorithms and asymptotic analysis.',
            'level' => 'expert',
            'type' => LearningCourse::TYPE_FREE,
            'price' => 0.00,
            'status' => LearningCourse::STATUS_PUBLISHED,
            'is_published' => true,
        ]);

        $section = LearningCourseSection::create([
            'course_id' => $this->course->id,
            'title' => 'Analysis of Algorithms',
            'sort_order' => 1,
        ]);

        $lesson = LearningCourseLesson::create([
            'course_id' => $this->course->id,
            'section_id' => $section->id,
            'title' => 'Algorithm Lab Assignment',
            'slug' => 'algorithm-lab-assignment',
            'type' => LearningCourseLesson::TYPE_ASSIGNMENT,
            'is_mandatory' => true,
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->assignment = LearningAssignment::create([
            'course_id' => $this->course->id,
            'lesson_id' => $lesson->id,
            'title' => 'B-Tree Index Implementation in Rust',
            'instructions' => 'Implement concurrent B-tree with locking.',
            'max_score' => 100,
            'passing_score' => 60,
        ]);

        LearningEnrollment::create([
            'course_id' => $this->course->id,
            'user_id' => $this->studentA->id,
            'status' => LearningEnrollment::STATUS_ACTIVE,
        ]);

        LearningEnrollment::create([
            'course_id' => $this->course->id,
            'user_id' => $this->studentB->id,
            'status' => LearningEnrollment::STATUS_ACTIVE,
        ]);

        // Student A submits assignment
        $this->submissionA = LearningAssignmentSubmission::create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->studentA->id,
            'submission_text' => 'Here is my complete B-Tree Rust crate repository link.',
            'file_url' => 'https://storage.connectly.app/assignments/alice_btree.tar.gz',
            'status' => LearningAssignmentSubmission::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    /**
     * 1. Student B CANNOT view Student A's submission
     */
    public function test_student_cannot_view_another_students_submission(): void
    {
        $unauthView = $this->actingAs($this->studentB, 'sanctum')
            ->getJson("/api/v1/learning/assignment-submissions/{$this->submissionA->id}");

        $unauthView->assertStatus(403);
    }

    /**
     * 2. Student A CAN view their own submission
     */
    public function test_student_can_view_own_submission(): void
    {
        $authView = $this->actingAs($this->studentA, 'sanctum')
            ->getJson("/api/v1/learning/assignment-submissions/{$this->submissionA->id}");

        $authView->assertOk()
            ->assertJsonPath('data.submission_text', 'Here is my complete B-Tree Rust crate repository link.');
    }

    /**
     * 3. Student CANNOT grade their own or others' submissions
     */
    public function test_student_cannot_grade_submission(): void
    {
        $unauthGrade = $this->actingAs($this->studentA, 'sanctum')
            ->postJson("/api/v1/learning/assignments/submissions/{$this->submissionA->id}/grade", [
                'score' => 100,
                'feedback' => 'Self-awarded 100 points.',
            ]);

        $unauthGrade->assertStatus(403);
    }

    /**
     * 4. Unrelated instructor CANNOT grade the assignment
     */
    public function test_unrelated_instructor_cannot_grade_submission(): void
    {
        $unauthGrade = $this->actingAs($this->otherInstructor, 'sanctum')
            ->postJson("/api/v1/learning/assignments/submissions/{$this->submissionA->id}/grade", [
                'score' => 90,
                'feedback' => 'Nice work from other instructor.',
            ]);

        $unauthGrade->assertStatus(403);
    }

    /**
     * 5. Assigned course instructor CAN grade the submission
     */
    public function test_course_instructor_can_grade_submission(): void
    {
        $gradeRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/assignments/submissions/{$this->submissionA->id}/grade", [
                'score' => 95,
                'feedback' => 'Outstanding implementation and memory safety.',
            ]);

        $gradeRes->assertOk()
            ->assertJsonPath('data.score', 95)
            ->assertJsonPath('data.status', LearningAssignmentSubmission::STATUS_GRADED);

        $this->assertEquals(95, $this->submissionA->fresh()->score);
    }
}
