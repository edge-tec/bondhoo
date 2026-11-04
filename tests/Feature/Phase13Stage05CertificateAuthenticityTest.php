<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CandidateCertification;
use App\Models\LearningCertificate;
use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseSection;
use App\Models\LearningEnrollment;
use App\Models\LearningLessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13Stage05CertificateAuthenticityTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $student;

    protected User $otherInstructor;

    protected LearningCourse $course;

    protected LearningCourseLesson $lesson1;

    protected LearningCourseLesson $lesson2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['name' => 'Prof. Claude Shannon']);
        $this->otherInstructor = User::factory()->create(['name' => 'Prof. Malicious']);
        $this->student = User::factory()->create(['name' => 'Student Claude']);

        $this->course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Information Theory & Signal Processing',
            'slug' => 'information-theory-signal-processing',
            'description' => 'Entropy, channel capacity, and noiseless coding.',
            'level' => 'expert',
            'type' => LearningCourse::TYPE_FREE,
            'price' => 0.00,
            'status' => LearningCourse::STATUS_PUBLISHED,
            'is_published' => true,
        ]);

        $section = LearningCourseSection::create([
            'course_id' => $this->course->id,
            'title' => 'Entropy & Coding',
            'sort_order' => 1,
        ]);

        $this->lesson1 = LearningCourseLesson::create([
            'course_id' => $this->course->id,
            'section_id' => $section->id,
            'title' => 'Shannon Entropy Theorem',
            'slug' => 'shannon-entropy-theorem',
            'type' => LearningCourseLesson::TYPE_VIDEO,
            'video_duration_seconds' => 300,
            'is_mandatory' => true,
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->lesson2 = LearningCourseLesson::create([
            'course_id' => $this->course->id,
            'section_id' => $section->id,
            'title' => 'Huffman & Arithmetic Coding',
            'slug' => 'huffman-arithmetic-coding',
            'type' => LearningCourseLesson::TYPE_VIDEO,
            'video_duration_seconds' => 300,
            'is_mandatory' => true,
            'is_published' => true,
            'sort_order' => 2,
        ]);

        LearningEnrollment::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'status' => LearningEnrollment::STATUS_ACTIVE,
        ]);
    }

    /**
     * 1. Ineligible claim rejected when lessons are incomplete
     */
    public function test_ineligible_certificate_claim_is_rejected(): void
    {
        // Only 1 of 2 lessons completed
        LearningLessonProgress::create([
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson1->id,
            'user_id' => $this->student->id,
            'is_completed' => true,
        ]);

        $claimRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->course->id}/certificate");

        $claimRes->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * 2. Legitimate certificate issued on 100% completion, synced with resume, and verified publicly
     */
    public function test_certificate_issuance_profile_sync_and_public_verification(): void
    {
        // Complete both lessons
        LearningLessonProgress::create([
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson1->id,
            'user_id' => $this->student->id,
            'is_completed' => true,
        ]);

        LearningLessonProgress::create([
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson2->id,
            'user_id' => $this->student->id,
            'is_completed' => true,
        ]);

        // Claim certificate
        $claimRes = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->course->id}/certificate");

        $claimRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $certCode = $claimRes->json('data.certificate_code');
        $this->assertNotNull($certCode);

        // Resume candidate certification synced
        $resumeCert = CandidateCertification::where('user_id', $this->student->id)->where('credential_id', $certCode)->first();
        $this->assertNotNull($resumeCert);

        // Public verification endpoint
        $verifyRes = $this->getJson("/api/v1/learning/certificates/{$certCode}");
        $verifyRes->assertOk()
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.is_revoked', false)
            ->assertJsonPath('data.course_title', $this->course->title);
    }

    /**
     * 3. Certificate Revocation & Synchronization
     */
    public function test_certificate_revocation_and_resume_desynchronization(): void
    {
        // Issue certificate directly
        $cert = LearningCertificate::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'certificate_code' => 'CERT-2026-TEST-ABCD',
            'verification_token' => 'test-token-12345',
            'student_name' => $this->student->name,
            'course_title' => $this->course->title,
            'instructor_name' => $this->instructor->name,
            'issued_at' => now(),
            'is_revoked' => false,
        ]);

        CandidateCertification::create([
            'user_id' => $this->student->id,
            'credential_id' => 'CERT-2026-TEST-ABCD',
            'title' => 'Information Theory',
            'issuing_organization' => 'Connectly LMS',
        ]);

        // Unrelated instructor CANNOT revoke
        $unauthRevoke = $this->actingAs($this->otherInstructor, 'sanctum')
            ->postJson("/api/v1/learning/instructor/certificates/{$cert->id}/revoke", [
                'reason' => 'Unauthorized malice attempt',
            ]);
        $unauthRevoke->assertStatus(403);

        // Authorized instructor revokes certificate
        $authRevoke = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/instructor/certificates/{$cert->id}/revoke", [
                'reason' => 'Academic misconduct discovered post-graduation.',
            ]);
        $authRevoke->assertOk()
            ->assertJsonPath('data.is_revoked', true);

        // Public verification now marks invalid and revoked
        $verifyRes = $this->getJson('/api/v1/learning/certificates/CERT-2026-TEST-ABCD');
        $verifyRes->assertOk()
            ->assertJsonPath('data.is_valid', false)
            ->assertJsonPath('data.is_revoked', true)
            ->assertJsonPath('data.revoked_reason', 'Academic misconduct discovered post-graduation.');

        // Automatically purged from resume certifications
        $this->assertEquals(0, CandidateCertification::where('credential_id', 'CERT-2026-TEST-ABCD')->count());
    }
}

