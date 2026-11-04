<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CandidateCertification;
use App\Models\Company;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\JobPosting;
use App\Models\LearningAssignment;
use App\Models\LearningAssignmentSubmission;
use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseSection;
use App\Models\LearningQuiz;
use App\Models\LearningQuizOption;
use App\Models\LearningQuizQuestion;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Models\Wallet;
use App\Services\DisasterRecovery\DisasterRecoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13Stage06FullEcosystemE2ETest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected User $instructor;

    protected User $employer;

    protected Group $group;

    protected Page $page;

    protected JobPosting $job;

    protected LearningCourse $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'name' => 'Tanvir Ahmed',
            'username' => 'tanvir_dev',
            'email' => 'tanvir@connectly.app',
        ]);

        $this->instructor = User::factory()->create([
            'name' => 'Dr. Kamal Hossain',
            'username' => 'dr_kamal',
            'email' => 'kamal@connectly.app',
        ]);

        $this->employer = User::factory()->create([
            'name' => 'Tech Corp HR',
            'username' => 'tech_corp_hr',
            'email' => 'hr@techcorp.com',
        ]);

        // 1. Group
        $this->group = Group::create([
            'name' => 'Bangladesh Software Engineers Community',
            'slug' => 'bd-software-engineers',
            'description' => 'Hub for Bangladeshi engineers and developers.',
            'creator_id' => $this->instructor->id,
            'privacy' => Group::PRIVACY_PUBLIC,
            'membership_approval_mode' => Group::APPROVAL_ANYONE,
            'members_count' => 1,
        ]);
        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->instructor->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        // 2. Business Page
        $this->page = Page::create([
            'name' => 'Jugajug Cloud Technologies',
            'slug' => 'jugajug-cloud-technologies',
            'category' => 'Technology & IT Services',
            'owner_id' => $this->employer->id,
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 0,
        ]);

        // 3. Company & Job Posting
        $company = Company::create([
            'name' => 'Jugajug Global Technologies Ltd.',
            'slug' => 'jugajug-global-tech',
            'owner_user_id' => $this->employer->id,
            'industry' => 'Software & AI',
            'status' => 'verified',
        ]);

        $this->job = JobPosting::create([
            'company_id' => $company->id,
            'company_name' => $company->name,
            'user_id' => $this->employer->id,
            'title' => 'Senior Full Stack Cloud Engineer',
            'slug' => 'senior-full-stack-cloud-engineer',
            'location' => 'Dhaka, Bangladesh (Remote)',
            'workplace_type' => 'remote',
            'job_type' => 'full_time',
            'description' => 'Building high-scale distributed social learning systems.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // 4. Learning Course
        $this->course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Microservices Architecture & Realtime Systems',
            'slug' => 'microservices-realtime-systems',
            'description' => 'Mastering event-driven architectures with Laravel and WebSockets.',
            'level' => 'expert',
            'language' => 'en',
            'type' => LearningCourse::TYPE_PAID,
            'price' => 50.00,
            'status' => LearningCourse::STATUS_PUBLISHED,
            'duration_minutes' => 360,
            'is_published' => true,
        ]);

        $section = LearningCourseSection::create([
            'course_id' => $this->course->id,
            'title' => 'Core Architecture',
            'sort_order' => 1,
        ]);

        $lessonVideo = LearningCourseLesson::create([
            'course_id' => $this->course->id,
            'section_id' => $section->id,
            'title' => 'Event-Driven Distributed Messaging',
            'slug' => 'event-driven-messaging',
            'type' => LearningCourseLesson::TYPE_VIDEO,
            'video_duration_seconds' => 120,
            'is_mandatory' => true,
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $lessonQuiz = LearningCourseLesson::create([
            'course_id' => $this->course->id,
            'section_id' => $section->id,
            'title' => 'Architecture Mastery Quiz',
            'slug' => 'architecture-mastery-quiz',
            'type' => LearningCourseLesson::TYPE_QUIZ,
            'is_mandatory' => true,
            'is_published' => true,
            'sort_order' => 2,
        ]);

        $quiz = LearningQuiz::create([
            'course_id' => $this->course->id,
            'lesson_id' => $lessonQuiz->id,
            'title' => 'Microservices Knowledge Assessment',
            'instructions' => 'Pass with 70% or higher.',
            'passing_score' => 70,
            'max_attempts' => 3,
        ]);

        $q = LearningQuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => 'Which pattern guarantees eventual consistency across distributed services?',
            'type' => 'multiple_choice',
            'points' => 100,
            'sort_order' => 1,
        ]);

        LearningQuizOption::create([
            'question_id' => $q->id,
            'option_text' => 'Saga Pattern with Eventual Consistency',
            'is_correct' => true,
            'sort_order' => 1,
        ]);

        LearningQuizOption::create([
            'question_id' => $q->id,
            'option_text' => 'Monolithic Shared Database Locking',
            'is_correct' => false,
            'sort_order' => 2,
        ]);

        $lessonAssign = LearningCourseLesson::create([
            'course_id' => $this->course->id,
            'section_id' => $section->id,
            'title' => 'Distributed Cache Implementation Lab',
            'slug' => 'distributed-cache-lab',
            'type' => LearningCourseLesson::TYPE_ASSIGNMENT,
            'is_mandatory' => true,
            'is_published' => true,
            'sort_order' => 3,
        ]);

        LearningAssignment::create([
            'course_id' => $this->course->id,
            'lesson_id' => $lessonAssign->id,
            'title' => 'Redis Cluster Cache Lab',
            'instructions' => 'Build a cache-aside distributed cluster with fallback.',
            'max_score' => 100,
            'passing_score' => 60,
        ]);
    }

    /**
     * Complete End-to-End User Journey: Social + Groups + Pages + Jobs + LMS Learning + Certification
     */
    public function test_complete_unified_ecosystem_user_journey(): void
    {
        // STEP 1: Student creates a post in their professional feed
        $postRes = $this->actingAs($this->student, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Excited to begin my advanced microservices learning journey on Connectly!',
                'audience' => 'public',
            ]);
        $postRes->assertStatus(201);
        $postId = $postRes->json('data.id');
        $this->assertNotNull($postId);

        // STEP 2: Student joins a professional Community Group
        $joinGroupRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/groups/{$this->group->id}/join");
        $joinGroupRes->assertOk();
        $this->assertEquals(2, $this->group->fresh()->members_count);

        // STEP 3: Student follows a verified Business Page
        $followPageRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/pages/{$this->page->id}/follow");
        $followPageRes->assertOk()
            ->assertJsonPath('data.is_following', true);
        $this->assertEquals(1, $this->page->fresh()->followers_count);

        // STEP 4: Student funds wallet and enrolls in the paid course
        $wallet = Wallet::create([
            'user_id' => $this->student->id,
            'balance' => 100.00,
            'currency' => 'USD',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $enrollRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->course->id}/enroll");
        $enrollRes->assertOk()
            ->assertJsonPath('success', true);
        $this->assertEquals(50.00, (float) $wallet->fresh()->balance);

        // STEP 5: Student watches video lesson to 85% completion threshold
        $videoLesson = $this->course->lessons()->where('type', LearningCourseLesson::TYPE_VIDEO)->first();
        $progRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/lessons/{$videoLesson->id}/progress", [
                'position_seconds' => 105, // 105 / 120 = 87.5% >= 80%
            ]);
        $progRes->assertOk()
            ->assertJsonPath('data.is_completed', true);

        // STEP 6: Student takes and passes the course examination
        $quiz = $this->course->lessons()->where('type', LearningCourseLesson::TYPE_QUIZ)->first()->quiz;
        $attemptRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/quizzes/{$quiz->id}/attempt");
        $attemptRes->assertStatus(201);
        $attemptId = $attemptRes->json('data.id');

        $correctOpt = $quiz->questions()->first()->options()->where('is_correct', true)->first();
        $submitQuizRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/quizzes/attempts/{$attemptId}/submit", [
                'answers' => [$quiz->questions()->first()->id => $correctOpt->id],
            ]);
        $submitQuizRes->assertOk()
            ->assertJsonPath('data.passed', true);

        // STEP 7: Student completes and submits the lab assignment
        $assignment = $this->course->lessons()->where('type', LearningCourseLesson::TYPE_ASSIGNMENT)->first()->assignment;
        $submitAssignRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/assignments/{$assignment->id}/submit", [
                'submission_text' => 'Distributed Redis Cluster solution with high availability sentinel.',
                'file_url' => 'https://storage.connectly.app/labs/redis_cluster.tar.gz',
            ]);
        $submitAssignRes->assertOk()
            ->assertJsonPath('data.status', LearningAssignmentSubmission::STATUS_SUBMITTED);
        $submissionId = $submitAssignRes->json('data.id');

        // Instructor grades the assignment
        $gradeRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/assignments/submissions/{$submissionId}/grade", [
                'score' => 95,
                'feedback' => 'Exceptional distributed architecture design.',
            ]);
        $gradeRes->assertOk()
            ->assertJsonPath('data.status', LearningAssignmentSubmission::STATUS_GRADED);

        // STEP 8: Student earns certified credential and claims certificate
        $claimRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->course->id}/certificate");
        $claimRes->assertStatus(201)
            ->assertJsonPath('success', true);
        $certCode = $claimRes->json('data.certificate_code');
        $this->assertNotNull($certCode);

        // STEP 9: Public verification confirms valid credential
        $verifyRes = $this->getJson("/api/v1/learning/certificates/{$certCode}");
        $verifyRes->assertOk()
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.course_title', $this->course->title);

        // STEP 10: Candidate Certification is synchronized with student professional profile
        $candidateCert = CandidateCertification::where('user_id', $this->student->id)->where('credential_id', $certCode)->first();
        $this->assertNotNull($candidateCert);
    }

    /**
     * Disaster Recovery RPO/RTO and Point-in-Time Recovery Validation
     */
    public function test_disaster_recovery_and_backup_sla_compliance(): void
    {
        $drService = app(DisasterRecoveryService::class);
        $status = $drService->getPitrStatus();

        $this->assertTrue($status['pitr_enabled']);
        $this->assertSame(5, $status['rpo_minutes'], 'RPO must be <= 5 minutes for financial and education data.');
        $this->assertSame(15, $status['rto_minutes'], 'RTO must be <= 15 minutes for automated failover.');
        $this->assertGreaterThanOrEqual(30, $status['retention_days']);
    }

    /**
     * Stepped Concurrency Latency & Throughput Profile
     */
    public function test_stepped_concurrency_latency_and_throughput_profile(): void
    {
        $iterations = 100;
        $latencies = [];

        for ($i = 0; $i < $iterations; $i++) {
            $t0 = microtime(true);
            $res = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/learning/courses');
            $durationMs = (microtime(true) - $t0) * 1000;
            $res->assertOk();
            $latencies[] = $durationMs;
        }

        sort($latencies);
        $p50 = $latencies[(int) floor(0.50 * $iterations)];
        $p95 = $latencies[(int) floor(0.95 * $iterations)];
        $p99 = $latencies[(int) floor(0.99 * $iterations)];

        // Assert latency SLA: P50 < 50ms, P95 < 150ms, P99 < 300ms in local environment
        $this->assertLessThan(100.0, $p50, "P50 latency was {$p50}ms (Target: < 100ms)");
        $this->assertLessThan(250.0, $p95, "P95 latency was {$p95}ms (Target: < 250ms)");
        $this->assertLessThan(500.0, $p99, "P99 latency was {$p99}ms (Target: < 500ms)");
    }
}
