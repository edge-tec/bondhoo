<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseSection;
use App\Models\LearningEnrollment;
use App\Models\LearningQuiz;
use App\Models\LearningQuizOption;
use App\Models\LearningQuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13Stage05QuizAndAssessmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $student;

    protected User $otherStudent;

    protected LearningCourse $course;

    protected LearningQuiz $quiz;

    protected LearningQuizQuestion $q1;

    protected LearningQuizOption $q1Opt1;

    protected LearningQuizOption $q1Opt2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['name' => 'Dr. Richard Feynman']);
        $this->student = User::factory()->create(['name' => 'Student Paul']);
        $this->otherStudent = User::factory()->create(['name' => 'Intruder Dave']);

        $this->course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Quantum Electrodynamics',
            'slug' => 'quantum-electrodynamics',
            'description' => 'Light and matter interaction.',
            'level' => 'expert',
            'type' => LearningCourse::TYPE_FREE,
            'price' => 0.00,
            'status' => LearningCourse::STATUS_PUBLISHED,
            'is_published' => true,
        ]);

        $section = LearningCourseSection::create([
            'course_id' => $this->course->id,
            'title' => 'Photons and Electrons',
            'sort_order' => 1,
        ]);

        $lesson = LearningCourseLesson::create([
            'course_id' => $this->course->id,
            'section_id' => $section->id,
            'title' => 'QED Evaluation Quiz',
            'slug' => 'qed-eval-quiz',
            'type' => LearningCourseLesson::TYPE_QUIZ,
            'is_mandatory' => true,
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->quiz = LearningQuiz::create([
            'course_id' => $this->course->id,
            'lesson_id' => $lesson->id,
            'title' => 'QED Mastery Assessment',
            'instructions' => 'Choose the correct answer.',
            'passing_score' => 70,
            'max_attempts' => 2,
        ]);

        $this->q1 = LearningQuizQuestion::create([
            'quiz_id' => $this->quiz->id,
            'question' => 'What is the coupling constant of QED approximately equal to?',
            'type' => 'multiple_choice',
            'points' => 10,
            'sort_order' => 1,
        ]);

        $this->q1Opt1 = LearningQuizOption::create([
            'question_id' => $this->q1->id,
            'option_text' => '1/137 (Fine structure constant)',
            'is_correct' => true,
            'sort_order' => 1,
        ]);

        $this->q1Opt2 = LearningQuizOption::create([
            'question_id' => $this->q1->id,
            'option_text' => '3.14159',
            'is_correct' => false,
            'sort_order' => 2,
        ]);

        // Student enrolls
        LearningEnrollment::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'status' => LearningEnrollment::STATUS_ACTIVE,
        ]);
    }

    /**
     * 1. Quiz questions and options NEVER expose is_correct in student API response
     */
    public function test_quiz_student_api_never_exposes_answer_key_or_is_correct(): void
    {
        $res = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/quizzes/{$this->quiz->id}");

        $res->assertOk();

        // Raw json string must NOT contain 'is_correct'
        $jsonString = $res->getContent();
        $this->assertStringNotContainsString('is_correct', $jsonString);

        // Questions must have options, each option must only have id and option_text
        $options = $res->json('data.questions.0.options');
        $this->assertCount(2, $options);
        $this->assertArrayNotHasKey('is_correct', $options[0]);
        $this->assertArrayNotHasKey('is_correct', $options[1]);
    }

    /**
     * 2. Cross-user attempt isolation: another student cannot view, save, or submit attempt
     */
    public function test_cross_user_attempt_isolation(): void
    {
        // Student Paul starts attempt
        $startRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/quizzes/{$this->quiz->id}/attempt");
        $startRes->assertStatus(201);
        $attemptId = $startRes->json('data.id');

        // Intruder Dave tries to view attempt
        $unauthView = $this->actingAs($this->otherStudent, 'sanctum')
            ->getJson("/api/v1/learning/quiz-attempts/{$attemptId}");
        $unauthView->assertStatus(403);

        // Intruder Dave tries to submit attempt
        $unauthSubmit = $this->actingAs($this->otherStudent, 'sanctum')
            ->postJson("/api/v1/learning/quizzes/attempts/{$attemptId}/submit", [
                'answers' => [$this->q1->id => $this->q1Opt1->id],
            ]);
        $unauthSubmit->assertStatus(403);
    }

    /**
     * 3. Attempt concurrency & limit enforcement
     */
    public function test_max_attempts_limit_enforcement(): void
    {
        // Attempt 1
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/quizzes/{$this->quiz->id}/attempt")
            ->assertStatus(201);

        // Attempt 2
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/quizzes/{$this->quiz->id}/attempt")
            ->assertStatus(201);

        // Attempt 3 (Max is 2) -> rejected with 422
        $attempt3 = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/quizzes/{$this->quiz->id}/attempt");

        $attempt3->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
