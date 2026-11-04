<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CandidateCertification;
use App\Models\LearningAssignmentSubmission;
use App\Models\LearningCategory;
use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseProgress;
use App\Models\LearningCourseSection;
use App\Models\LearningEnrollment;
use App\Models\LearningLessonAttachment;
use App\Models\LearningLessonProgress;
use App\Models\LearningQuiz;
use App\Models\LearningQuizOption;
use App\Models\LearningQuizQuestion;
use App\Models\Role;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13Stage04EngagementAndAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $otherInstructor;

    protected User $student;

    protected User $otherStudent;

    protected User $admin;

    protected LearningCategory $category;

    protected LearningCourse $freeCourse;

    protected LearningCourse $paidCourse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create([
            'name' => 'Prof. Richard Feynman',
            'email' => 'feynman@connectly.app',
        ]);

        $this->otherInstructor = User::factory()->create([
            'name' => 'Dr. Marie Curie',
            'email' => 'curie@connectly.app',
        ]);

        $this->student = User::factory()->create([
            'name' => 'Albert Einstein',
            'email' => 'einstein@connectly.app',
        ]);

        $this->otherStudent = User::factory()->create([
            'name' => 'Niels Bohr',
            'email' => 'bohr@connectly.app',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'LMS Super Administrator',
            'email' => 'lmsadmin@connectly.app',
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Administrator']);
        $this->admin->roles()->attach($adminRole->id);

        $this->category = LearningCategory::create([
            'name' => 'Physics & Computing',
            'slug' => 'physics-computing',
            'icon' => 'atom',
            'description' => 'Quantum computing, classical mechanics, and electrodynamics.',
        ]);

        // Free Course
        $this->freeCourse = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->category->id,
            'title' => 'Quantum Electrodynamics Basics',
            'slug' => 'qed-basics',
            'description' => 'Light and matter interactions.',
            'level' => 'intermediate',
            'type' => 'free',
            'price' => 0.00,
            'currency' => 'USD',
            'status' => LearningCourse::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        // Paid Course
        $this->paidCourse = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->category->id,
            'title' => 'Advanced Path Integrals Masterclass',
            'slug' => 'advanced-path-integrals',
            'description' => 'Feynman diagrams and path integrals.',
            'level' => 'expert',
            'type' => 'paid',
            'price' => 50.00,
            'currency' => 'USD',
            'status' => LearningCourse::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Test 1: Free Course Enrollment, Details, Progress Initialization & Cancellation
     */
    public function test_free_enrollment_and_progress_initialization(): void
    {
        $section = LearningCourseSection::create([
            'course_id' => $this->freeCourse->id,
            'title' => 'Introduction to Photons',
            'sort_order' => 1,
        ]);

        $lesson = LearningCourseLesson::create([
            'section_id' => $section->id,
            'course_id' => $this->freeCourse->id,
            'title' => 'Lesson 1: Probability Amplitudes',
            'slug' => 'prob-amplitudes',
            'type' => 'video',
            'video_duration_seconds' => 120,
            'is_preview' => false,
            'is_published' => true,
        ]);

        // 1. Student enrolls in free course
        $enrollRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->freeCourse->id}/enroll");

        $enrollRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', LearningEnrollment::STATUS_ACTIVE);
        $this->assertEquals(0.00, (float) $enrollRes->json('data.price_paid'));

        $enrollmentId = $enrollRes->json('data.id');

        // 2. Course progress initialized
        $progress = LearningCourseProgress::where('enrollment_id', $enrollmentId)->first();
        $this->assertNotNull($progress);
        $this->assertEquals(0, $progress->completed_lessons_count);
        $this->assertEquals(1, $progress->total_lessons_count);
        $this->assertEquals(0.00, $progress->percentage);

        // 3. View single enrollment details
        $showRes = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/enrollments/{$enrollmentId}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $enrollmentId)
            ->assertJsonPath('data.course.title', 'Quantum Electrodynamics Basics');

        // 4. Other student cannot view this enrollment (Security IDOR guard)
        $unauthShow = $this->actingAs($this->otherStudent, 'sanctum')
            ->getJson("/api/v1/learning/enrollments/{$enrollmentId}");
        $unauthShow->assertStatus(403);

        // 5. Cancel enrollment
        $cancelRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/enrollments/{$enrollmentId}/cancel");
        $cancelRes->assertOk()
            ->assertJsonPath('data.status', LearningEnrollment::STATUS_CANCELLED);
    }

    /**
     * Test 2: Paid Course Wallet Deduction, Insufficient Balance & Idempotency
     */
    public function test_paid_course_wallet_deduction_and_insufficient_balance(): void
    {
        // 1. Student with no wallet balance fails to enroll in paid course ($50)
        $failRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->paidCourse->id}/enroll");

        $failRes->assertStatus(422)
            ->assertJsonPath('success', false);

        // 2. Fund student wallet with $100
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $this->student->id],
            ['balance' => 0.00, 'currency' => 'USD', 'status' => Wallet::STATUS_ACTIVE]
        );
        $wallet->update(['balance' => 100.00]);

        // 3. Enroll successfully with wallet payment
        $successRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->paidCourse->id}/enroll");

        $successRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', LearningEnrollment::STATUS_ACTIVE);
        $this->assertEquals(50.00, (float) $successRes->json('data.price_paid'));

        // Wallet balance decremented to $50
        $this->assertEquals(50.00, (float) $wallet->fresh()->balance);

        // 4. Duplicate enrollment is safe and idempotent (does not double charge)
        $dupRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->paidCourse->id}/enroll");

        $dupRes->assertOk();
        $this->assertEquals(50.00, (float) $wallet->fresh()->balance);
        $this->assertEquals(1, LearningEnrollment::where('course_id', $this->paidCourse->id)->where('user_id', $this->student->id)->count());
    }

    /**
     * Test 3: Secure Lesson Access, Streaming URL Generation & Attachments
     */
    public function test_secure_lesson_access_and_streaming_and_attachments(): void
    {
        $section = LearningCourseSection::create([
            'course_id' => $this->freeCourse->id,
            'title' => 'Interference of Light',
        ]);

        $previewLesson = LearningCourseLesson::create([
            'section_id' => $section->id,
            'course_id' => $this->freeCourse->id,
            'title' => 'Free Preview: Two-Slit Experiment',
            'slug' => 'two-slit-exp',
            'type' => 'video',
            'video_url' => 'https://stream.connectly.app/two-slit.mp4',
            'video_duration_seconds' => 180,
            'is_preview' => true,
            'is_published' => true,
        ]);

        $protectedLesson = LearningCourseLesson::create([
            'section_id' => $section->id,
            'course_id' => $this->freeCourse->id,
            'title' => 'Protected: Path Integral Formulation',
            'slug' => 'path-integral-form',
            'type' => 'video',
            'video_url' => 'https://stream.connectly.app/path-integral.mp4',
            'video_duration_seconds' => 300,
            'is_preview' => false,
            'is_published' => true,
        ]);

        LearningLessonAttachment::create([
            'lesson_id' => $previewLesson->id,
            'title' => 'Feynman-Diagrams-Handout.pdf',
            'file_url' => 'https://storage.connectly.app/docs/handout.pdf',
            'file_type' => 'pdf',
            'file_size' => 102400,
        ]);

        // 1. Non-enrolled student can access preview lesson
        $previewRes = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/lessons/{$previewLesson->id}");
        $previewRes->assertOk()
            ->assertJsonPath('data.lesson.title', 'Free Preview: Two-Slit Experiment');

        // 2. Non-enrolled student CANNOT access protected lesson (403 Forbidden)
        $forbiddenRes = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/lessons/{$protectedLesson->id}");
        $forbiddenRes->assertStatus(403);

        // 3. Student enrolls in course
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->freeCourse->id}/enroll")
            ->assertOk();

        // 4. Now protected lesson is accessible
        $allowedRes = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/lessons/{$protectedLesson->id}");
        $allowedRes->assertOk()
            ->assertJsonPath('data.lesson.title', 'Protected: Path Integral Formulation');

        // 5. Authorized streaming access
        $streamRes = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/lessons/{$protectedLesson->id}/stream");
        $streamRes->assertOk()
            ->assertJsonPath('data.stream_url', 'https://stream.connectly.app/path-integral.mp4')
            ->assertJsonPath('data.duration_seconds', 300);

        // 6. Lesson attachments retrieval
        $attachRes = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/lessons/{$previewLesson->id}/attachments");
        $attachRes->assertOk()
            ->assertJsonPath('data.0.title', 'Feynman-Diagrams-Handout.pdf');
    }

    /**
     * Test 4: Learning Progress Tracking, Auto-Completion Threshold & Resume Position
     */
    public function test_lesson_progress_tracking_and_auto_completion_threshold(): void
    {
        $section = LearningCourseSection::create([
            'course_id' => $this->freeCourse->id,
            'title' => 'QED Calculations',
        ]);

        $lesson = LearningCourseLesson::create([
            'section_id' => $section->id,
            'course_id' => $this->freeCourse->id,
            'title' => 'Electron-Positron Annihilation',
            'slug' => 'annihilation-calc',
            'type' => 'video',
            'video_duration_seconds' => 100, // 100 seconds
            'is_preview' => false,
            'is_published' => true,
        ]);

        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->freeCourse->id}/enroll")
            ->assertOk();

        // 1. Send progress at position = 30 seconds (< 80% threshold)
        $prog1 = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/lessons/{$lesson->id}/progress", [
                'position_seconds' => 30,
            ]);
        $prog1->assertOk()
            ->assertJsonPath('data.last_position_seconds', 30)
            ->assertJsonPath('data.is_completed', false);

        // 2. Fetch single lesson progress
        $getProg = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/lessons/{$lesson->id}/progress");
        $getProg->assertOk()
            ->assertJsonPath('data.last_position_seconds', 30);

        // 3. Send progress at position = 85 seconds (>= 80% threshold -> auto completes)
        $prog2 = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/lessons/{$lesson->id}/progress", [
                'position_seconds' => 85,
            ]);
        $prog2->assertOk()
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('data.last_position_seconds', 85);

        // 4. Check course progress summary
        $courseProgRes = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/courses/{$this->freeCourse->id}/progress");
        $courseProgRes->assertOk()
            ->assertJsonPath('data.is_enrolled', true)
            ->assertJsonPath('data.progress.completed_lessons_count', 1);
        $this->assertEquals(100.00, (float) $courseProgRes->json('data.progress.percentage'));

        // 5. Explicit complete lesson endpoint
        $completeRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/lessons/{$lesson->id}/complete");
        $completeRes->assertOk()
            ->assertJsonPath('data.is_completed', true);
    }

    /**
     * Test 5: Quiz System, Attempt Concurrency Guard, Interim Answers & Grading
     */
    public function test_quiz_attempt_answering_and_server_side_grading(): void
    {
        $section = LearningCourseSection::create([
            'course_id' => $this->freeCourse->id,
            'title' => 'Quantum Quizzes',
        ]);

        $quizLesson = LearningCourseLesson::create([
            'section_id' => $section->id,
            'course_id' => $this->freeCourse->id,
            'title' => 'Quiz: Wave-Particle Duality',
            'slug' => 'wave-particle-quiz',
            'type' => 'quiz',
            'is_preview' => false,
            'is_published' => true,
        ]);

        $quiz = LearningQuiz::create([
            'course_id' => $this->freeCourse->id,
            'lesson_id' => $quizLesson->id,
            'title' => 'Wave-Particle Duality Mastery Test',
            'instructions' => 'Answer all multiple choice questions.',
            'time_limit_minutes' => 15,
            'passing_score' => 75,
            'max_attempts' => 1, // Only 1 attempt permitted
        ]);

        $q1 = LearningQuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => 'What happens to photons in a double-slit experiment when observed?',
            'type' => 'multiple_choice',
            'points' => 50,
            'sort_order' => 1,
        ]);

        $q1Opt1 = LearningQuizOption::create(['question_id' => $q1->id, 'option_text' => 'Wave function collapses', 'is_correct' => true]);
        $q1Opt2 = LearningQuizOption::create(['question_id' => $q1->id, 'option_text' => 'Photons disappear', 'is_correct' => false]);

        $q2 = LearningQuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => 'Who formulated the path integral formulation of quantum mechanics?',
            'type' => 'multiple_choice',
            'points' => 50,
            'sort_order' => 2,
        ]);

        $q2Opt1 = LearningQuizOption::create(['question_id' => $q2->id, 'option_text' => 'Richard Feynman', 'is_correct' => true]);
        $q2Opt2 = LearningQuizOption::create(['question_id' => $q2->id, 'option_text' => 'Isaac Newton', 'is_correct' => false]);

        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->freeCourse->id}/enroll")
            ->assertOk();

        // 1. Student views quiz (Correct answers must NOT be exposed)
        $quizRes = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/quizzes/{$quiz->id}");
        $quizRes->assertOk()
            ->assertJsonMissing(['is_correct' => true]);

        // 2. Student starts attempt
        $attemptRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/quizzes/{$quiz->id}/attempts");
        $attemptRes->assertStatus(201)
            ->assertJsonPath('data.attempt_number', 1);

        $attemptId = $attemptRes->json('data.id');

        // 3. Max attempts enforcement: starting another attempt is blocked (422)
        $dupAttempt = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/quizzes/{$quiz->id}/attempts");
        $dupAttempt->assertStatus(422);

        // 4. Save interim answers
        $saveRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/quiz-attempts/{$attemptId}/answers", [
                'answers' => [
                    $q1->id => $q1Opt1->id,
                ],
            ]);
        $saveRes->assertOk();

        // 5. Cross-user attempt privacy guard: Other student cannot view or submit attempt
        $unauthAttempt = $this->actingAs($this->otherStudent, 'sanctum')
            ->getJson("/api/v1/learning/quiz-attempts/{$attemptId}");
        $unauthAttempt->assertStatus(403);

        // 6. Submit attempt with 100% correct answers
        $submitRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/quiz-attempts/{$attemptId}/submit", [
                'answers' => [
                    $q1->id => $q1Opt1->id,
                    $q2->id => $q2Opt1->id,
                ],
            ]);
        $submitRes->assertOk()
            ->assertJsonPath('data.passed', true);
        $this->assertEquals(100.00, (float) $submitRes->json('data.score'));

        // 7. Get attempt result
        $resultRes = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/quiz-attempts/{$attemptId}/result");
        $resultRes->assertOk()
            ->assertJsonPath('data.passed', true)
            ->assertJsonPath('data.earned_points', 100);
        $this->assertEquals(100.00, (float) $resultRes->json('data.score'));

        // Lesson progress marked complete
        $lessonProg = LearningLessonProgress::where('lesson_id', $quizLesson->id)->where('user_id', $this->student->id)->first();
        $this->assertTrue($lessonProg->is_completed);
    }

    /**
     * Test 6: Assignment Creation, Student Submission, Late Rules & Instructor Grading
     */
    public function test_assignment_workflow_student_submission_and_instructor_grading(): void
    {
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->freeCourse->id}/enroll")
            ->assertOk();

        // 1. Instructor creates assignment
        $assignRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/instructor/courses/{$this->freeCourse->id}/assignments", [
                'title' => 'Quantum Simulation Lab Assignment',
                'description' => 'Simulate two-particle entanglement in Python or C++.',
                'total_points' => 100,
                'passing_score' => 60,
                'allow_resubmissions' => true,
            ]);
        $assignRes->assertStatus(201);
        $assignmentId = $assignRes->json('data.id');

        // 2. Other instructor CANNOT modify or delete this assignment
        $unauthUpdate = $this->actingAs($this->otherInstructor, 'sanctum')
            ->putJson("/api/v1/learning/instructor/assignments/{$assignmentId}", [
                'title' => 'Malicious Assignment Title',
            ]);
        $unauthUpdate->assertStatus(403);

        // 3. Student views assignment
        $showAssign = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/assignments/{$assignmentId}");
        $showAssign->assertOk()
            ->assertJsonPath('data.assignment.title', 'Quantum Simulation Lab Assignment');

        // 4. Student submits assignment
        $submitRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/assignments/{$assignmentId}/submit", [
                'submission_text' => 'Here is my complete QED simulation notebook solution.',
                'file_url' => 'https://storage.connectly.app/student/qed_solution.ipynb',
            ]);
        $submitRes->assertOk()
            ->assertJsonPath('data.status', LearningAssignmentSubmission::STATUS_SUBMITTED);
        $submissionId = $submitRes->json('data.id');

        // 5. Student views own submission
        $subShow = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/assignment-submissions/{$submissionId}");
        $subShow->assertOk()
            ->assertJsonPath('data.id', $submissionId);

        // Other student CANNOT view this submission (IDOR guard)
        $unauthSub = $this->actingAs($this->otherStudent, 'sanctum')
            ->getJson("/api/v1/learning/assignment-submissions/{$submissionId}");
        $unauthSub->assertStatus(403);

        // 6. Instructor grades submission
        $gradeRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/instructor/submissions/{$submissionId}/grade", [
                'score' => 95,
                'feedback' => 'Exceptional derivation and clean simulation code.',
            ]);
        $gradeRes->assertOk()
            ->assertJsonPath('data.score', 95)
            ->assertJsonPath('data.feedback', 'Exceptional derivation and clean simulation code.')
            ->assertJsonPath('data.status', LearningAssignmentSubmission::STATUS_GRADED);
    }

    /**
     * Test 7: Certificate Eligibility Server-Authoritative Gate, Verification & Revocation
     */
    public function test_certificate_eligibility_enforcement_and_revocation(): void
    {
        $section = LearningCourseSection::create([
            'course_id' => $this->freeCourse->id,
            'title' => 'Final Capstone Module',
        ]);

        $lesson = LearningCourseLesson::create([
            'section_id' => $section->id,
            'course_id' => $this->freeCourse->id,
            'title' => 'Final Capstone Lecture',
            'slug' => 'final-capstone',
            'type' => 'video',
            'video_duration_seconds' => 100,
            'is_preview' => false,
            'is_published' => true,
        ]);

        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->freeCourse->id}/enroll")
            ->assertOk();

        // 1. Ineligible student attempts to claim certificate (Rejected: All lessons not completed)
        $earlyClaim = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->freeCourse->id}/certificate");
        $earlyClaim->assertStatus(422)
            ->assertJsonPath('success', false);

        // 2. Complete lesson
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/lessons/{$lesson->id}/complete")
            ->assertOk();

        // 3. Now student claims certificate successfully
        $certRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->freeCourse->id}/certificate");
        $certRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $certCode = $certRes->json('data.certificate_code');
        $certId = $certRes->json('data.id');

        // 4. Verified CandidateCertification created on student's resume
        $candidateCert = CandidateCertification::where('user_id', $this->student->id)
            ->where('credential_id', $certCode)
            ->first();
        $this->assertNotNull($candidateCert);

        // 5. Public verification endpoint confirms validity
        $pubVerify = $this->getJson("/api/v1/learning/certificates/verify/{$certCode}");
        $pubVerify->assertOk()
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.is_revoked', false)
            ->assertJsonPath('data.student_name', 'Albert Einstein');

        // 6. Instructor revokes certificate for academic integrity violation
        $revokeRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/instructor/certificates/{$certId}/revoke", [
                'reason' => 'Academic dishonesty in assessment.',
            ]);
        $revokeRes->assertOk()
            ->assertJsonPath('data.is_revoked', true);

        // 7. Revocation automatically removes CandidateCertification from student's profile
        $this->assertNull(CandidateCertification::where('credential_id', $certCode)->first());

        // 8. Public verification now shows revoked status
        $revokedVerify = $this->getJson("/api/v1/learning/certificates/verify/{$certCode}");
        $revokedVerify->assertOk()
            ->assertJsonPath('data.is_valid', false)
            ->assertJsonPath('data.is_revoked', true)
            ->assertJsonPath('data.revoked_reason', 'Academic dishonesty in assessment.');
    }
}
