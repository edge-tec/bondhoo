<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CandidateCertification;
use App\Models\LearningAssignment;
use App\Models\LearningCategory;
use App\Models\LearningCourse;
use App\Models\LearningCourseCoupon;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseSection;
use App\Models\LearningEnrollment;
use App\Models\LearningInstructor;
use App\Models\LearningQuiz;
use App\Models\LearningQuizOption;
use App\Models\LearningQuizQuestion;
use App\Models\Role;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13CompleteLmsSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $student;

    protected User $admin;

    protected LearningCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create([
            'name' => 'Prof. Alan Turing',
            'email' => 'turing@connectly.app',
        ]);

        $this->student = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@connectly.app',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'LMS Super Admin',
            'email' => 'admin@connectly.app',
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Administrator']);
        $this->admin->roles()->attach($adminRole->id);

        $this->category = LearningCategory::create([
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'icon' => 'code',
            'description' => 'Algorithms, architecture, and programming.',
        ]);
    }

    public function test_categories_endpoint_returns_active_categories(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/learning/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.name', 'Computer Science');
    }

    public function test_instructor_application_and_admin_verification(): void
    {
        // 1. Instructor applies
        $response = $this->actingAs($this->instructor, 'sanctum')
            ->postJson('/api/v1/learning/instructors/apply', [
                'headline' => 'AI & Distributed Systems Specialist',
                'biography' => 'Author of multiple computing foundation books.',
                'expertise' => ['Algorithms', 'Python', 'Machine Learning'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.headline', 'AI & Distributed Systems Specialist');

        $instructorRecord = LearningInstructor::where('user_id', $this->instructor->id)->first();
        $this->assertNotNull($instructorRecord);

        // 2. Admin verifies instructor
        $verifyRes = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/learning/admin/instructors/{$instructorRecord->id}/verify");

        $verifyRes->assertStatus(200)
            ->assertJsonPath('data.is_verified', true);
    }

    public function test_course_creation_curriculum_and_publishing_workflow(): void
    {
        // 1. Create course as draft
        $createRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson('/api/v1/learning/courses', [
                'title' => 'Mastering Modern Algorithms',
                'subtitle' => 'From Graph Theory to Dynamic Programming',
                'category_id' => $this->category->id,
                'description' => 'A comprehensive deep dive into competitive programming and algorithm design.',
                'level' => 'intermediate',
                'type' => 'free',
                'skills' => ['Algorithms', 'Data Structures', 'C++'],
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft');

        $courseId = $createRes->json('data.id');
        $slug = $createRes->json('data.slug');

        // 2. Add Section
        $sectionRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$courseId}/sections", [
                'title' => 'Section 1: Foundations of Asymptotic Analysis',
                'description' => 'Big-O, Big-Theta, and Big-Omega notation.',
            ]);

        $sectionRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $sectionId = $sectionRes->json('data.id');

        // 3. Add Lessons (One Preview Video, One Protected Video)
        $previewLessonRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/sections/{$sectionId}/lessons", [
                'title' => 'Lecture 1: Course Welcome & Overview',
                'type' => 'video',
                'video_url' => 'https://stream.connectly.app/welcome.mp4',
                'video_duration_seconds' => 300,
                'is_preview' => true,
            ]);
        $previewLessonRes->assertStatus(201);
        $previewLessonId = $previewLessonRes->json('data.id');

        $protectedLessonRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/sections/{$sectionId}/lessons", [
                'title' => 'Lecture 2: Recurrence Relations & Master Theorem',
                'type' => 'video',
                'video_url' => 'https://stream.connectly.app/recurrence.mp4',
                'video_duration_seconds' => 600,
                'is_preview' => false,
            ]);
        $protectedLessonRes->assertStatus(201);
        $protectedLessonId = $protectedLessonRes->json('data.id');

        // 4. Publish Course
        $publishRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$courseId}/publish");

        $publishRes->assertStatus(200)
            ->assertJsonPath('data.status', 'published');

        // 5. Public Course Details
        $showRes = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/courses/{$slug}");

        $showRes->assertStatus(200)
            ->assertJsonPath('data.course.title', 'Mastering Modern Algorithms')
            ->assertJsonPath('data.is_enrolled', false);

        // 6. Preview Lesson Access vs. Protected Lesson Access
        $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/lessons/{$previewLessonId}")
            ->assertStatus(200);

        $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/lessons/{$protectedLessonId}")
            ->assertStatus(403);
    }

    public function test_free_enrollment_progress_tracking_and_certificate_issuance(): void
    {
        $course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->category->id,
            'title' => 'Database Internals & Storage Engines',
            'slug' => 'database-internals',
            'description' => 'B-Trees, LSM Trees, and Write-Ahead Logging.',
            'type' => 'free',
            'price' => 0.00,
            'status' => LearningCourse::STATUS_PUBLISHED,
        ]);

        $section = LearningCourseSection::create([
            'course_id' => $course->id,
            'title' => 'Module 1: Storage',
        ]);

        $lesson = LearningCourseLesson::create([
            'section_id' => $section->id,
            'course_id' => $course->id,
            'title' => 'LSM Tree compaction strategies',
            'slug' => 'lsm-tree-compaction',
            'type' => 'video',
            'video_duration_seconds' => 100,
            'is_preview' => false,
            'is_published' => true,
        ]);

        // 1. Enroll in free course
        $enrollRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$course->id}/enroll");

        $enrollRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        // Duplicate enrollment is safe and idempotent
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$course->id}/enroll")
            ->assertStatus(200);

        $this->assertEquals(1, LearningEnrollment::where('user_id', $this->student->id)->count());

        // 2. Access protected lesson now succeeds
        $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/lessons/{$lesson->id}")
            ->assertStatus(200);

        // 3. Update lesson progress to 90 seconds (90% >= 80% threshold -> auto-complete)
        $progressRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/lessons/{$lesson->id}/progress", [
                'position_seconds' => 90,
            ]);

        $progressRes->assertStatus(200)
            ->assertJsonPath('data.is_completed', true);

        // 4. Check Course Progress and Verified Certificate issuance
        $certRes = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/learning/my-certificates');

        $certRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $cert = $certRes->json('data.0');
        $this->assertNotNull($cert);
        $code = $cert['certificate_code'];

        // 5. Public verification endpoint
        $verifyRes = $this->getJson("/api/v1/learning/certificates/{$code}");
        $verifyRes->assertStatus(200)
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.student_name', $this->student->name)
            ->assertJsonPath('data.course_title', $course->title);

        // 6. Verify sync to CandidateCertification (Phase 12 ATS integration)
        $candidateCert = CandidateCertification::where('credential_id', $code)->first();
        $this->assertNotNull($candidateCert);
        $this->assertEquals('Connectly LMS Academy', $candidateCert->issuing_organization);
    }

    public function test_paid_course_enrollment_with_wallet(): void
    {
        $course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->category->id,
            'title' => 'High Performance Distributed Systems',
            'slug' => 'distributed-systems',
            'description' => 'Consensus protocols, Raft, and Paxos.',
            'type' => 'paid',
            'price' => 50.00,
            'status' => LearningCourse::STATUS_PUBLISHED,
        ]);

        // Student with insufficient funds fails
        $wallet = Wallet::create([
            'user_id' => $this->student->id,
            'balance' => 20.00,
            'currency' => 'USD',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$course->id}/enroll")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // Deposit funds into student's wallet
        $wallet->update(['balance' => 100.00]);

        $enrollRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$course->id}/enroll");

        $enrollRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.price_paid', '50.00');

        $this->assertEquals(50.00, (float) $wallet->fresh()->balance);
    }

    public function test_quiz_attempt_answering_and_server_side_grading(): void
    {
        $course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Compiler Design',
            'slug' => 'compiler-design',
            'description' => 'Lexical analysis, parsing, and IR optimization.',
            'status' => LearningCourse::STATUS_PUBLISHED,
        ]);

        $section = LearningCourseSection::create([
            'course_id' => $course->id,
            'title' => 'Section 1',
        ]);

        $lesson = LearningCourseLesson::create([
            'section_id' => $section->id,
            'course_id' => $course->id,
            'title' => 'Quiz 1: Syntax Analysis',
            'slug' => 'quiz-1',
            'type' => 'quiz',
        ]);

        $quiz = LearningQuiz::create([
            'lesson_id' => $lesson->id,
            'course_id' => $course->id,
            'title' => 'Syntax Analysis Assessment',
            'passing_score' => 70,
        ]);

        $question1 = LearningQuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => 'Which parsing algorithm is top-down?',
            'type' => 'multiple_choice',
            'points' => 10,
        ]);

        $correctOpt = LearningQuizOption::create([
            'question_id' => $question1->id,
            'option_text' => 'LL(1) Recursive Descent',
            'is_correct' => true,
        ]);

        $wrongOpt = LearningQuizOption::create([
            'question_id' => $question1->id,
            'option_text' => 'LR(1) Shift-Reduce',
            'is_correct' => false,
        ]);

        // 1. Fetch sanitized quiz questions (does NOT leak is_correct)
        $quizRes = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/learning/quizzes/{$quiz->id}");

        $quizRes->assertStatus(200)
            ->assertJsonMissing(['is_correct']);

        // 2. Start attempt
        $attemptRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/quizzes/{$quiz->id}/attempt");

        $attemptRes->assertStatus(201);
        $attemptId = $attemptRes->json('data.id');

        // 3. Submit correct answer
        $submitRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/quizzes/attempts/{$attemptId}/submit", [
                'answers' => [
                    $question1->id => $correctOpt->id,
                ],
            ]);

        $submitRes->assertStatus(200)
            ->assertJsonPath('data.passed', true)
            ->assertJsonPath('data.score', '100.00');
    }

    public function test_course_reviews_restricted_to_enrolled_students(): void
    {
        $course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Clean Architecture with Kotlin & Laravel',
            'slug' => 'clean-architecture',
            'description' => 'Best practices for decoupled production systems.',
            'status' => LearningCourse::STATUS_PUBLISHED,
        ]);

        // Unenrolled student review fails
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$course->id}/reviews", [
                'rating' => 5,
                'content' => 'Exceptional clarity and depth.',
            ])
            ->assertStatus(422);

        // Enroll student
        LearningEnrollment::create([
            'course_id' => $course->id,
            'user_id' => $this->student->id,
            'status' => LearningEnrollment::STATUS_ACTIVE,
        ]);

        // Now review succeeds
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$course->id}/reviews", [
                'rating' => 5,
                'content' => 'Exceptional clarity and depth.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.rating', 5);

        $this->assertEquals(5.00, (float) $course->fresh()->rating_avg);
    }

    public function test_assignment_submission_and_instructor_grading(): void
    {
        $course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Operating Systems Engineering',
            'slug' => 'os-engineering',
            'description' => 'Kernel threads, page tables, and virtual memory.',
            'status' => LearningCourse::STATUS_PUBLISHED,
        ]);

        $section = LearningCourseSection::create([
            'course_id' => $course->id,
            'title' => 'Memory Management',
        ]);

        $lesson = LearningCourseLesson::create([
            'section_id' => $section->id,
            'course_id' => $course->id,
            'title' => 'Assignment 1: Virtual Memory Pager',
            'slug' => 'assignment-1',
            'type' => 'assignment',
        ]);

        $assignment = LearningAssignment::create([
            'lesson_id' => $lesson->id,
            'course_id' => $course->id,
            'title' => 'Implement Clock Page Replacement',
            'instructions' => 'Write a C/C++ page replacement policy simulator.',
            'max_score' => 100,
            'passing_score' => 60,
        ]);

        // 1. Student submits assignment
        $submitRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/assignments/{$assignment->id}/submit", [
                'submission_text' => 'https://github.com/adalovelace/clock-pager-simulator',
                'file_url' => 'https://storage.connectly.app/assignments/solution.zip',
            ]);

        $submitRes->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');

        $submissionId = $submitRes->json('data.id');

        // 2. Instructor grades submission
        $gradeRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/assignments/submissions/{$submissionId}/grade", [
                'score' => 95,
                'feedback' => 'Flawless clock pointer implementation and comprehensive unit tests.',
            ]);

        $gradeRes->assertStatus(200)
            ->assertJsonPath('data.status', 'graded')
            ->assertJsonPath('data.score', 95);
    }

    public function test_coupon_discount_applied_on_paid_enrollment(): void
    {
        $course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->category->id,
            'title' => 'Advanced Cryptography & Zero Knowledge Proofs',
            'slug' => 'advanced-zkp',
            'description' => 'SNARKs, STARKs, and elliptic curve pairings.',
            'type' => 'paid',
            'price' => 100.00,
            'status' => LearningCourse::STATUS_PUBLISHED,
        ]);

        $coupon = LearningCourseCoupon::create([
            'code' => 'HALFPRICE',
            'discount_type' => 'percentage',
            'discount_amount' => 50.00,
            'is_active' => true,
        ]);

        $wallet = Wallet::create([
            'user_id' => $this->student->id,
            'balance' => 60.00,
            'currency' => 'USD',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        // Student pays $50 instead of $100
        $enrollRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$course->id}/enroll", [
                'coupon_code' => 'HALFPRICE',
            ]);

        $enrollRes->assertStatus(200)
            ->assertJsonPath('data.price_paid', '50.00');

        $this->assertEquals(10.00, (float) $wallet->fresh()->balance);
        $this->assertEquals(1, $coupon->fresh()->times_used);
    }

    public function test_instructor_dashboard_analytics(): void
    {
        LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Quantum Computing Basics',
            'slug' => 'quantum-computing',
            'description' => 'Qubits and superposition.',
            'enrollments_count' => 142,
            'rating_avg' => 4.85,
            'status' => LearningCourse::STATUS_PUBLISHED,
        ]);

        $dashboardRes = $this->actingAs($this->instructor, 'sanctum')
            ->getJson('/api/v1/learning/instructors/dashboard');

        $dashboardRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_courses', 1)
            ->assertJsonPath('data.total_students', 142)
            ->assertJsonPath('data.rating_avg', 4.85);
    }
}
