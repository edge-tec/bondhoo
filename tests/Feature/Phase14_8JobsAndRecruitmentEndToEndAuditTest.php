<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\JobSkill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase14_8JobsAndRecruitmentEndToEndAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $employer;

    private User $candidate;

    private User $otherCandidate;

    private Company $company;

    private JobPosting $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employer = User::factory()->create(['name' => 'Tech Recruiter', 'email' => 'recruiter@bondhoo.com']);
        $this->candidate = User::factory()->create(['name' => 'Alice Dev', 'email' => 'alice@bondhoo.com']);
        $this->otherCandidate = User::factory()->create(['name' => 'Bob Hacker', 'email' => 'bob@bondhoo.com']);

        $this->company = Company::create([
            'name' => 'BONDHOO Global Technologies',
            'slug' => 'bondhoo-global-technologies',
            'industry' => 'Information Technology',
            'owner_user_id' => $this->employer->id,
            'status' => 'verified',
        ]);

        CompanyMember::create([
            'company_id' => $this->company->id,
            'user_id' => $this->employer->id,
            'role' => 'admin',
            'invitation_status' => CompanyMember::STATUS_ACCEPTED,
        ]);

        $this->job = JobPosting::create([
            'user_id' => $this->employer->id,
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'title' => 'Senior Cloud Architect',
            'slug' => 'senior-cloud-architect-audit',
            'location' => 'Dhaka, Bangladesh',
            'workplace_type' => 'hybrid',
            'employment_type' => 'full_time',
            'experience_level' => 'senior',
            'salary_min' => 120000,
            'salary_max' => 180000,
            'salary_currency' => 'BDT',
            'description' => 'Architect modern scalable microservices and cloud infrastructures.',
            'status' => JobPosting::STATUS_ACTIVE,
            'vacancies' => 2,
            'published_at' => now(),
            'deadline' => now()->addDays(30),
        ]);

        $skillKubernetes = JobSkill::firstOrCreate(['name' => 'Kubernetes', 'slug' => 'kubernetes']);
        $skillLaravel = JobSkill::firstOrCreate(['name' => 'Laravel', 'slug' => 'laravel']);
        $this->job->skills()->attach([$skillKubernetes->id, $skillLaravel->id]);
    }

    public function test_guest_can_browse_published_jobs_with_filters(): void
    {
        $response = $this->getJson('/api/v1/jobs?workplace_type=hybrid&employment_type=full_time&search=Cloud');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonFragment(['title' => 'Senior Cloud Architect']);
    }

    public function test_guest_cannot_apply_for_job(): void
    {
        $response = $this->postJson("/api/v1/jobs/{$this->job->id}/apply", [
            'full_name' => 'Guest User',
            'email' => 'guest@test.com',
            'phone' => '01700000000',
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_candidate_can_apply_and_prevent_duplicate_application(): void
    {
        $payload = [
            'full_name' => 'Alice Dev',
            'email' => 'alice@bondhoo.com',
            'phone' => '01711111111',
            'expected_salary' => 150000,
            'cover_letter' => 'I am eager to contribute to BONDHOO Cloud Architect position.',
        ];

        // 1. Initial application succeeds
        $firstResponse = $this->actingAs($this->candidate, 'sanctum')
            ->postJson("/api/v1/jobs/{$this->job->id}/apply", $payload);

        $firstResponse->assertCreated()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('job_applications', [
            'job_posting_id' => $this->job->id,
            'user_id' => $this->candidate->id,
            'status' => JobApplication::STATUS_APPLIED,
        ]);

        // 2. Duplicate submission is prevented with 422
        $dupResponse = $this->actingAs($this->candidate, 'sanctum')
            ->postJson("/api/v1/jobs/{$this->job->id}/apply", $payload);

        $dupResponse->assertStatus(422)
            ->assertJsonValidationErrors(['job']);
    }

    public function test_candidate_can_withdraw_application_and_idor_protection(): void
    {
        $application = JobApplication::create([
            'job_posting_id' => $this->job->id,
            'user_id' => $this->candidate->id,
            'full_name' => 'Alice Dev',
            'email' => 'alice@bondhoo.com',
            'phone' => '01711111111',
            'status' => JobApplication::STATUS_APPLIED,
        ]);

        // Another candidate cannot withdraw Alice's application (403 Forbidden)
        $hackerResponse = $this->actingAs($this->otherCandidate, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/withdraw");

        $hackerResponse->assertForbidden();

        // Alice can withdraw her own application
        $withdrawResponse = $this->actingAs($this->candidate, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/withdraw");

        $withdrawResponse->assertOk();
        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'status' => JobApplication::STATUS_WITHDRAWN,
        ]);
    }

    public function test_withdrawn_application_cannot_be_transitioned_or_scheduled(): void
    {
        $application = JobApplication::create([
            'job_posting_id' => $this->job->id,
            'company_id' => $this->company->id,
            'user_id' => $this->candidate->id,
            'full_name' => 'Alice Dev',
            'email' => 'alice@bondhoo.com',
            'phone' => '01711111111',
            'status' => JobApplication::STATUS_WITHDRAWN,
            'withdrawn_at' => now(),
        ]);

        // 1. Stage transition blocked (422)
        $stageResponse = $this->actingAs($this->employer, 'sanctum')
            ->patchJson("/api/v1/applications/{$application->id}/stage", [
                'stage' => JobApplication::STATUS_SHORTLISTED,
            ]);

        $stageResponse->assertStatus(422);

        // 2. Interview scheduling blocked (422)
        $schedResponse = $this->actingAs($this->employer, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'scheduled_at' => now()->addDays(2)->toDateTimeString(),
                'interview_type' => 'video',
                'duration_minutes' => 45,
            ]);

        $schedResponse->assertStatus(422);
    }

    public function test_interview_state_machine_and_concurrency_lock(): void
    {
        $application = JobApplication::create([
            'job_posting_id' => $this->job->id,
            'company_id' => $this->company->id,
            'user_id' => $this->candidate->id,
            'full_name' => 'Alice Dev',
            'email' => 'alice@bondhoo.com',
            'phone' => '01711111111',
            'status' => JobApplication::STATUS_APPLIED,
        ]);

        // 1. Schedule Interview
        $schedResponse = $this->actingAs($this->employer, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'scheduled_at' => now()->addDays(3)->toDateTimeString(),
                'interview_type' => 'video',
                'duration_minutes' => 60,
                'meeting_url' => 'https://meet.google.com/xyz-abc-def',
                'instructions' => 'Bring system architecture diagrams.',
            ]);

        $schedResponse->assertCreated();
        $interviewId = $schedResponse->json('data.interview.id');

        // Application stage should now be interview
        $this->assertEquals(JobApplication::STATUS_INTERVIEW, $application->fresh()->status);

        // 2. Concurrency guard: Attempting to schedule duplicate active interview on same application fails
        $dupSched = $this->actingAs($this->employer, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/interviews", [
                'scheduled_at' => now()->addDays(4)->toDateTimeString(),
                'interview_type' => 'phone',
            ]);

        $dupSched->assertStatus(422);

        // 3. IDOR protection: unauthorized candidate cannot respond
        $unauthResp = $this->actingAs($this->otherCandidate, 'sanctum')
            ->postJson("/api/v1/interviews/{$interviewId}/response", ['status' => 'accept']);

        $unauthResp->assertForbidden();

        // 4. Candidate accepts/confirms interview
        $acceptResp = $this->actingAs($this->candidate, 'sanctum')
            ->postJson("/api/v1/interviews/{$interviewId}/response", ['status' => 'accept']);

        $acceptResp->assertOk();
        $this->assertDatabaseHas('interviews', [
            'id' => $interviewId,
            'status' => Interview::STATUS_CONFIRMED,
        ]);

        // 5. State Machine: Cannot respond again to an already confirmed interview
        $reRespond = $this->actingAs($this->candidate, 'sanctum')
            ->postJson("/api/v1/interviews/{$interviewId}/response", ['status' => 'decline']);

        $reRespond->assertStatus(422);

        // 6. Recruiter reschedules confirmed interview
        $reschedResp = $this->actingAs($this->employer, 'sanctum')
            ->putJson("/api/v1/interviews/{$interviewId}", [
                'scheduled_at' => now()->addDays(5)->toDateTimeString(),
            ]);

        $reschedResp->assertOk();
        $this->assertDatabaseHas('interviews', [
            'id' => $interviewId,
            'status' => Interview::STATUS_RESCHEDULED,
        ]);

        // 7. Candidate declines rescheduled interview
        $declineResp = $this->actingAs($this->candidate, 'sanctum')
            ->postJson("/api/v1/interviews/{$interviewId}/response", ['status' => 'decline']);

        $declineResp->assertOk();
        $this->assertDatabaseHas('interviews', [
            'id' => $interviewId,
            'status' => Interview::STATUS_DECLINED,
        ]);

        // 8. Cannot reschedule an already declined interview
        $invalidResched = $this->actingAs($this->employer, 'sanctum')
            ->putJson("/api/v1/interviews/{$interviewId}", [
                'scheduled_at' => now()->addDays(6)->toDateTimeString(),
            ]);

        $invalidResched->assertStatus(422);
    }

    public function test_job_creation_validation_rules(): void
    {
        // 1. salary_max < salary_min fails
        $res1 = $this->actingAs($this->employer, 'sanctum')
            ->postJson('/api/v1/jobs', [
                'title' => 'Invalid Salary Job',
                'location' => 'Dhaka',
                'employment_type' => 'full_time',
                'description' => 'Test invalid salary range',
                'salary_min' => 100000,
                'salary_max' => 50000,
            ]);

        $res1->assertStatus(422)
            ->assertJsonValidationErrors(['salary_max']);

        // 2. vacancies < 1 fails
        $res2 = $this->actingAs($this->employer, 'sanctum')
            ->postJson('/api/v1/jobs', [
                'title' => 'Invalid Vacancy Job',
                'location' => 'Dhaka',
                'employment_type' => 'full_time',
                'description' => 'Test invalid vacancies',
                'vacancies' => 0,
            ]);

        $res2->assertStatus(422)
            ->assertJsonValidationErrors(['vacancies']);

        // 3. Past deadline fails
        $res3 = $this->actingAs($this->employer, 'sanctum')
            ->postJson('/api/v1/jobs', [
                'title' => 'Expired Deadline Job',
                'location' => 'Dhaka',
                'employment_type' => 'full_time',
                'description' => 'Test expired deadline',
                'deadline' => now()->subDay()->toDateString(),
            ]);

        $res3->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);

        // 4. Valid job creation persists and attaches skills
        $res4 = $this->actingAs($this->employer, 'sanctum')
            ->postJson('/api/v1/jobs', [
                'company_id' => $this->company->id,
                'title' => 'Lead DevOps Engineer',
                'location' => 'Dhaka, Bangladesh',
                'workplace_type' => 'remote',
                'employment_type' => 'full_time',
                'experience_level' => 'lead',
                'description' => 'Architect CI/CD and production Kubernetes clusters.',
                'salary_min' => 150000,
                'salary_max' => 220000,
                'vacancies' => 1,
                'deadline' => now()->addDays(20)->toDateString(),
                'skills' => ['Docker', 'Terraform', 'Kubernetes'],
            ]);

        $res4->assertCreated()
            ->assertJsonPath('data.job.title', 'Lead DevOps Engineer');

        $this->assertDatabaseHas('job_postings', [
            'title' => 'Lead DevOps Engineer',
            'company_id' => $this->company->id,
            'workplace_type' => 'remote',
        ]);
    }

    public function test_saved_jobs_functionality_and_uniqueness(): void
    {
        // 1. Save job
        $saveRes = $this->actingAs($this->candidate, 'sanctum')
            ->postJson("/api/v1/jobs/{$this->job->id}/save");

        $saveRes->assertOk();
        $this->assertDatabaseHas('saved_jobs', [
            'user_id' => $this->candidate->id,
            'job_id' => $this->job->id,
        ]);

        // 2. Fetch saved jobs
        $fetchRes = $this->actingAs($this->candidate, 'sanctum')
            ->getJson('/api/v1/candidate/saved-jobs');

        $fetchRes->assertOk()
            ->assertJsonFragment(['title' => 'Senior Cloud Architect']);

        // 3. Unsave job
        $unsaveRes = $this->actingAs($this->candidate, 'sanctum')
            ->deleteJson("/api/v1/jobs/{$this->job->id}/save");

        $unsaveRes->assertOk();
        $this->assertDatabaseMissing('saved_jobs', [
            'user_id' => $this->candidate->id,
            'job_id' => $this->job->id,
        ]);
    }
}
