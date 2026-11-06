<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseSection;
use App\Models\LearningEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13Stage05LearningProgressAndTamperingTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $student;

    protected User $nonEnrolledUser;

    protected LearningCourse $course;

    protected LearningCourseLesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['name' => 'Instructor Maxwell']);
        $this->student = User::factory()->create(['name' => 'Student Bob']);
        $this->nonEnrolledUser = User::factory()->create(['name' => 'Intruder Charlie']);

        $this->course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Electromagnetism Masterclass',
            'slug' => 'electromagnetism-masterclass',
            'description' => 'Classical electrodynamics and field equations.',
            'level' => 'intermediate',
            'type' => LearningCourse::TYPE_FREE,
            'price' => 0.00,
            'status' => LearningCourse::STATUS_PUBLISHED,
            'is_published' => true,
        ]);

        $section = LearningCourseSection::create([
            'course_id' => $this->course->id,
            'title' => 'Maxwell Equations',
            'sort_order' => 1,
        ]);

        $this->lesson = LearningCourseLesson::create([
            'course_id' => $this->course->id,
            'section_id' => $section->id,
            'title' => 'Gauss Law for Magnetism',
            'slug' => 'gauss-law-magnetism',
            'type' => LearningCourseLesson::TYPE_VIDEO,
            'video_duration_seconds' => 100, // 100 seconds
            'is_mandatory' => true,
            'is_published' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * 1. Non-enrolled user cannot update progress or view protected lesson
     */
    public function test_non_enrolled_user_cannot_update_progress(): void
    {
        $res = $this->actingAs($this->nonEnrolledUser, 'sanctum')
            ->postJson("/api/v1/learning/lessons/{$this->lesson->id}/progress", [
                'position_seconds' => 50,
            ]);

        $res->assertStatus(403);
    }

    /**
     * 2. Invalid or negative playback position is rejected by request validation
     */
    public function test_negative_position_is_rejected_by_validation(): void
    {
        // Student enrolls first
        LearningEnrollment::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'status' => LearningEnrollment::STATUS_ACTIVE,
        ]);

        $res = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/lessons/{$this->lesson->id}/progress", [
                'position_seconds' => -10,
            ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['position_seconds']);
    }

    /**
     * 3. Video position resume: saves exact position and resumes from that point
     */
    public function test_video_resume_and_auto_completion_threshold(): void
    {
        LearningEnrollment::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'status' => LearningEnrollment::STATUS_ACTIVE,
        ]);

        // 1. Send progress at position = 45 seconds (< 80% threshold)
        $prog1 = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/lessons/{$this->lesson->id}/progress", [
                'position_seconds' => 45,
            ]);
        $prog1->assertOk()
            ->assertJsonPath('data.last_position_seconds', 45)
            ->assertJsonPath('data.is_completed', false);

        // 2. Re-open lesson and verify resume position
        $showLesson = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/lessons/{$this->lesson->id}");
        $showLesson->assertOk()
            ->assertJsonPath('data.progress.last_position_seconds', 45)
            ->assertJsonPath('data.progress.is_completed', false);

        // 3. Send progress at position = 82 seconds (>= 80% threshold -> auto completes)
        $prog2 = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/lessons/{$this->lesson->id}/progress", [
                'position_seconds' => 82,
            ]);
        $prog2->assertOk()
            ->assertJsonPath('data.last_position_seconds', 82)
            ->assertJsonPath('data.is_completed', true);
    }
}
