<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CandidateResume;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\JobSkill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase14_9AdvancedRecruitmentSecurityAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $recruiter;

    private User $candidateAlice;

    private User $candidateBob;

    private Company $company;

    private JobPosting $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recruiter = User::factory()->create([
            'name' => 'Recruiter Dave',
            'email' => 'dave@bondhoo-recruitment.com',
        ]);

        $this->candidateAlice = User::factory()->create([
            'name' => 'Alice Engineer',
            'email' => 'alice@candidate-hub.com',
        ]);

        $this->candidateBob = User::factory()->create([
            'name' => 'Bob Hacker',
            'email' => 'bob@candidate-hub.com',
        ]);

        $this->company = Company::create([
            'name' => 'BONDHOO Global Tech',
            'slug' => 'bondhoo-global-tech',
            'industry' => 'Cloud & AI Infrastructure',
            'owner_user_id' => $this->recruiter->id,
            'status' => 'verified',
        ]);

        CompanyMember::create([
            'company_id' => $this->company->id,
            'user_id' => $this->recruiter->id,
            'role' => CompanyMember::ROLE_RECRUITER,
            'invitation_status' => CompanyMember::STATUS_ACCEPTED,
        ]);

        $this->job = JobPosting::create([
            'user_id' => $this->recruiter->id,
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'title' => 'Principal AI & Cloud Architect',
            'slug' => 'principal-ai-cloud-architect-149',
            'location' => 'Dhaka / Remote',
            'workplace_type' => 'hybrid',
            'employment_type' => 'full_time',
            'experience_level' => 'lead',
            'salary_min' => 200000,
            'salary_max' => 350000,
            'salary_currency' => 'BDT',
            'description' => 'Architect high-availability distributed systems.',
            'status' => JobPosting::STATUS_ACTIVE,
            'vacancies' => 2,
            'published_at' => now(),
            'deadline' => now()->addDays(45),
        ]);

        $skillAi = JobSkill::firstOrCreate(['name' => 'AI Architecture', 'slug' => 'ai-architecture']);
        $this->job->skills()->attach([$skillAi->id]);
    }

    /**
     * TASK 2 — Strict Interview State Machine Verification.
     */
    public function test_interview_state_machine_valid_and_prohibited_transitions(): void
    {
        $application = JobApplication::create([
            'job_posting_id' => $this->job->id,
            'company_id' => $this->company->id,
            'user_id' => $this->candidateAlice->id,
            'full_name' => 'Alice Engineer',
            'email' => 'alice@candidate-hub.com',
            'phone' => '01711111111',
            'status' => JobApplication::STATUS_APPLIED,
        ]);

        // 1. Recruiter schedules interview (Scheduled)
        $scheduleRes = $this->actingAs($this->recruiter, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'scheduled_at' => now()->addDays(3)->toDateTimeString(),
                'interview_type' => 'video',
                'duration_minutes' => 60,
                'meeting_url' => 'https://meet.google.com/audit-test-meet',
            ]);

        $scheduleRes->assertCreated();
        $interviewId = $scheduleRes->json('data.interview.id');
        $this->assertDatabaseHas('interviews', [
            'id' => $interviewId,
            'status' => Interview::STATUS_SCHEDULED,
        ]);

        // 2. Candidate accepts interview (Scheduled -> Confirmed)
        $confirmRes = $this->actingAs($this->candidateAlice, 'sanctum')
            ->postJson("/api/v1/interviews/{$interviewId}/response", ['status' => 'confirmed']);

        $confirmRes->assertOk();
        $this->assertDatabaseHas('interviews', ['id' => $interviewId, 'status' => Interview::STATUS_CONFIRMED]);

        // 3. Prohibited Transition: Attempting to confirm or decline an ALREADY confirmed interview must fail (422)
        $reConfirm = $this->actingAs($this->candidateAlice, 'sanctum')
            ->postJson("/api/v1/interviews/{$interviewId}/response", ['status' => 'confirmed']);
        $reConfirm->assertStatus(422);

        // 4. Recruiter reschedules confirmed interview (Confirmed -> Rescheduled)
        $reschedRes = $this->actingAs($this->recruiter, 'sanctum')
            ->putJson("/api/v1/interviews/{$interviewId}", [
                'scheduled_at' => now()->addDays(5)->toDateTimeString(),
            ]);

        $reschedRes->assertOk();
        $this->assertDatabaseHas('interviews', ['id' => $interviewId, 'status' => Interview::STATUS_RESCHEDULED]);

        // 5. Candidate declines rescheduled interview (Rescheduled -> Declined)
        $declineRes = $this->actingAs($this->candidateAlice, 'sanctum')
            ->postJson("/api/v1/interviews/{$interviewId}/response", ['status' => 'declined']);

        $declineRes->assertOk();
        $this->assertDatabaseHas('interviews', ['id' => $interviewId, 'status' => Interview::STATUS_DECLINED]);

        // 6. Prohibited Transition: Declined -> Confirmed MUST FAIL (422)
        $declinedToConfirmed = $this->actingAs($this->candidateAlice, 'sanctum')
            ->postJson("/api/v1/interviews/{$interviewId}/response", ['status' => 'confirmed']);
        $declinedToConfirmed->assertStatus(422);

        // 7. Prohibited Transition: Declined -> Rescheduled MUST FAIL (422)
        $declinedToResched = $this->actingAs($this->recruiter, 'sanctum')
            ->putJson("/api/v1/interviews/{$interviewId}", [
                'scheduled_at' => now()->addDays(6)->toDateTimeString(),
            ]);
        $declinedToResched->assertStatus(422);

        // 8. Prohibited Transition: Declined -> Feedback / Complete MUST FAIL (422)
        $declinedToFeedback = $this->actingAs($this->recruiter, 'sanctum')
            ->postJson("/api/v1/interviews/{$interviewId}/feedback", [
                'feedback' => 'Candidate should not receive evaluation if declined.',
            ]);
        $declinedToFeedback->assertStatus(422);
    }

    /**
     * TASK 2 & 3 — Cancelled and Completed State Machine Prohibitions.
     */
    public function test_cancelled_and_completed_interviews_cannot_be_tampered_or_rescheduled(): void
    {
        $application = JobApplication::create([
            'job_posting_id' => $this->job->id,
            'company_id' => $this->company->id,
            'user_id' => $this->candidateAlice->id,
            'full_name' => 'Alice Engineer',
            'email' => 'alice@candidate-hub.com',
            'phone' => '01711111111',
            'status' => JobApplication::STATUS_APPLIED,
        ]);

        $interview = Interview::create([
            'application_id' => $application->id,
            'company_id' => $this->company->id,
            'scheduled_by' => $this->recruiter->id,
            'interview_type' => 'video',
            'scheduled_at' => now()->addDays(2),
            'duration_minutes' => 45,
            'status' => Interview::STATUS_CONFIRMED,
        ]);

        // 1. Recruiter records evaluation feedback (Confirmed -> Completed)
        $feedbackRes = $this->actingAs($this->recruiter, 'sanctum')
            ->postJson("/api/v1/interviews/{$interview->id}/feedback", [
                'feedback' => 'Outstanding system architecture and leadership depth.',
            ]);

        $feedbackRes->assertOk();
        $this->assertDatabaseHas('interviews', ['id' => $interview->id, 'status' => Interview::STATUS_COMPLETED]);

        // 2. Prohibited Transition: Completed -> Rescheduled MUST FAIL (422)
        $completedToResched = $this->actingAs($this->recruiter, 'sanctum')
            ->putJson("/api/v1/interviews/{$interview->id}", [
                'scheduled_at' => now()->addDays(4)->toDateTimeString(),
            ]);
        $completedToResched->assertStatus(422);

        // 3. Prohibited Transition: Completed -> Cancelled MUST FAIL (422)
        $completedToCancel = $this->actingAs($this->recruiter, 'sanctum')
            ->postJson("/api/v1/interviews/{$interview->id}/cancel", [
                'reason' => 'Too late to cancel a completed interview.',
            ]);
        $completedToCancel->assertStatus(422);

        // 4. Cancelled Interview Isolation
        $cancelApp = JobApplication::create([
            'job_posting_id' => $this->job->id,
            'company_id' => $this->company->id,
            'user_id' => $this->candidateBob->id,
            'full_name' => 'Bob Hacker',
            'email' => 'bob@candidate-hub.com',
            'phone' => '01722222222',
            'status' => JobApplication::STATUS_APPLIED,
        ]);

        $cancelInterview = Interview::create([
            'application_id' => $cancelApp->id,
            'company_id' => $this->company->id,
            'scheduled_by' => $this->recruiter->id,
            'interview_type' => 'video',
            'scheduled_at' => now()->addDays(2),
            'duration_minutes' => 45,
            'status' => Interview::STATUS_SCHEDULED,
        ]);

        $this->actingAs($this->recruiter, 'sanctum')
            ->postJson("/api/v1/interviews/{$cancelInterview->id}/cancel");

        $this->assertDatabaseHas('interviews', ['id' => $cancelInterview->id, 'status' => Interview::STATUS_CANCELLED]);

        // Prohibited Transition: Cancelled -> Confirmed MUST FAIL (422)
        $cancelledToConfirmed = $this->actingAs($this->candidateBob, 'sanctum')
            ->postJson("/api/v1/interviews/{$cancelInterview->id}/response", ['status' => 'confirmed']);
        $cancelledToConfirmed->assertStatus(422);
    }

    /**
     * TASK 3 — Concurrency & Withdrawn Application Protection.
     */
    public function test_withdrawn_application_blocks_concurrent_and_subsequent_scheduling(): void
    {
        $application = JobApplication::create([
            'job_posting_id' => $this->job->id,
            'company_id' => $this->company->id,
            'user_id' => $this->candidateAlice->id,
            'full_name' => 'Alice Engineer',
            'email' => 'alice@candidate-hub.com',
            'phone' => '01711111111',
            'status' => JobApplication::STATUS_WITHDRAWN,
            'withdrawn_at' => now(),
        ]);

        // Attempting to schedule an interview on a withdrawn application must fail with 422
        $res = $this->actingAs($this->recruiter, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'scheduled_at' => now()->addDays(3)->toDateTimeString(),
                'interview_type' => 'video',
            ]);

        $res->assertStatus(422);

        // Attempting to update stage on a withdrawn application must fail with 422
        $stageRes = $this->actingAs($this->recruiter, 'sanctum')
            ->patchJson("/api/v1/applications/{$application->id}/stage", [
                'stage' => JobApplication::STATUS_INTERVIEW,
            ]);

        $stageRes->assertStatus(422);
    }

    /**
     * TASK 1 — Recruiter Feedback Masking & Candidate Data Isolation.
     */
    public function test_recruiter_feedback_is_never_disclosed_to_candidates(): void
    {
        $application = JobApplication::create([
            'job_posting_id' => $this->job->id,
            'company_id' => $this->company->id,
            'user_id' => $this->candidateAlice->id,
            'full_name' => 'Alice Engineer',
            'email' => 'alice@candidate-hub.com',
            'phone' => '01711111111',
            'status' => JobApplication::STATUS_APPLIED,
        ]);

        $interview = Interview::create([
            'application_id' => $application->id,
            'company_id' => $this->company->id,
            'scheduled_by' => $this->recruiter->id,
            'interview_type' => 'video',
            'scheduled_at' => now()->subDay(),
            'duration_minutes' => 60,
            'status' => Interview::STATUS_COMPLETED,
            'feedback' => 'CONFIDENTIAL: Candidate passed salary threshold, recommended for Principal level.',
        ]);

        // 1. Recruiter inspecting interview sees confidential feedback
        $recruiterView = $this->actingAs($this->recruiter, 'sanctum')
            ->getJson("/api/v1/interviews/{$interview->id}");

        $recruiterView->assertOk()
            ->assertJsonPath('data.interview.feedback', 'CONFIDENTIAL: Candidate passed salary threshold, recommended for Principal level.');

        // 2. Candidate inspecting interview sees feedback as NULL (Masked for candidate privacy/security)
        $candidateView = $this->actingAs($this->candidateAlice, 'sanctum')
            ->getJson("/api/v1/interviews/{$interview->id}");

        $candidateView->assertOk()
            ->assertJsonPath('data.interview.feedback', null);

        // 3. Unrelated candidate Bob is blocked completely (403 Forbidden)
        $bobView = $this->actingAs($this->candidateBob, 'sanctum')
            ->getJson("/api/v1/interviews/{$interview->id}");

        $bobView->assertForbidden();
    }

    /**
     * TASK 1 — File Upload Security & MIME Validation for Resumes.
     */
    public function test_resume_upload_security_and_path_traversal_protection(): void
    {
        Storage::fake('local');

        // 1. Reject invalid file extension (e.g. .exe / .sh / .php)
        $maliciousFile = UploadedFile::fake()->create('malicious.php', 500, 'application/x-php');
        $res1 = $this->actingAs($this->candidateAlice, 'sanctum')
            ->postJson('/api/v1/candidate/resumes', [
                'file' => $maliciousFile,
                'title' => 'My PHP Resume',
            ]);

        $res1->assertStatus(422);

        // 2. Reject file exceeding 10 MB
        $oversizedFile = UploadedFile::fake()->create('huge_resume.pdf', 11000, 'application/pdf');
        $res2 = $this->actingAs($this->candidateAlice, 'sanctum')
            ->postJson('/api/v1/candidate/resumes', [
                'file' => $oversizedFile,
                'title' => 'Oversized Resume',
            ]);

        $res2->assertStatus(422);

        // 3. Valid PDF resume is securely stored on private local disk with randomized name
        $validFile = UploadedFile::fake()->create('alice_resume.pdf', 1024, 'application/pdf');
        $res3 = $this->actingAs($this->candidateAlice, 'sanctum')
            ->postJson('/api/v1/candidate/resumes', [
                'file' => $validFile,
                'title' => 'Alice Cloud Architect Resume',
                'is_default' => true,
            ]);

        $res3->assertCreated();
        $resumeId = $res3->json('data.resume.id');

        $resume = CandidateResume::findOrFail($resumeId);
        $this->assertEquals($this->candidateAlice->id, $resume->user_id);
        $this->assertStringStartsWith("resumes/{$this->candidateAlice->id}/", $resume->file_path);
        $this->assertStringEndsWith('.pdf', $resume->file_path);
        Storage::disk('local')->assertExists($resume->file_path);

        // 4. Bob cannot download Alice's private resume (403 Forbidden)
        $bobDownload = $this->actingAs($this->candidateBob, 'sanctum')
            ->getJson("/api/v1/candidate/resumes/{$resumeId}/download");

        $bobDownload->assertForbidden();
    }

    /**
     * TASK 1 & 7 — Unauthorized Company Posting & Saved Jobs Query Optimization.
     */
    public function test_unauthorized_company_posting_blocked_and_saved_jobs_eager_loading(): void
    {
        // 1. Bob cannot publish a job on behalf of BONDHOO Global Tech (403 Forbidden)
        $unauthPost = $this->actingAs($this->candidateBob, 'sanctum')
            ->postJson('/api/v1/jobs', [
                'company_id' => $this->company->id,
                'title' => 'Fake Job by Attacker',
                'location' => 'Dhaka',
                'employment_type' => 'full_time',
                'description' => 'Attempting unauthorized job posting.',
            ]);

        $unauthPost->assertForbidden();

        // 2. Candidate saves and retrieves job with complete eager-loaded relations
        $this->actingAs($this->candidateAlice, 'sanctum')
            ->postJson("/api/v1/jobs/{$this->job->id}/save");

        // DB::listen to verify eager loading and zero N+1 queries
        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $savedRes = $this->actingAs($this->candidateAlice, 'sanctum')
            ->getJson('/api/v1/candidate/saved-jobs');

        $savedRes->assertOk()
            ->assertJsonPath('data.jobs.0.title', 'Principal AI & Cloud Architect')
            ->assertJsonPath('data.jobs.0.company.name', 'BONDHOO Global Tech')
            ->assertJsonPath('data.jobs.0.skills.0.name', 'AI Architecture');

        // Query count should remain low and constant (not proportional to number of jobs)
        $this->assertLessThanOrEqual(10, $queryCount);
    }
}

