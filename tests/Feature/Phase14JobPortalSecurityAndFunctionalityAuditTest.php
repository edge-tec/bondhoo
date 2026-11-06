<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase14JobPortalSecurityAndFunctionalityAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $employer;

    private User $candidate;

    private User $attacker;

    private Company $company;

    private JobPosting $activeJob;

    private JobPosting $draftJob;

    private JobPosting $expiredJob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employer = User::factory()->create([
            'name' => 'Tech Employer',
            'email' => 'employer@techcorp.test',
        ]);

        $this->candidate = User::factory()->create([
            'name' => 'Candidate User',
            'email' => 'candidate@bondhoo.test',
        ]);

        $this->attacker = User::factory()->create([
            'name' => 'Malicious Actor',
            'email' => 'attacker@external.test',
        ]);

        $this->company = Company::create([
            'owner_user_id' => $this->employer->id,
            'name' => 'TechCorp Bangladesh',
            'slug' => 'techcorp-bd',
            'industry' => 'Software',
            'company_size' => '50-100',
            'headquarters' => 'Dhaka',
            'verification_status' => 'verified',
            'visibility' => 'public',
        ]);

        CompanyMember::create([
            'company_id' => $this->company->id,
            'user_id' => $this->employer->id,
            'role' => CompanyMember::ROLE_OWNER,
            'invitation_status' => CompanyMember::STATUS_ACCEPTED,
        ]);

        $this->activeJob = JobPosting::create([
            'user_id' => $this->employer->id,
            'company_id' => $this->company->id,
            'title' => 'Senior Mobile Engineer',
            'slug' => 'senior-mobile-engineer',
            'company_name' => $this->company->name,
            'location' => 'Dhaka, Bangladesh',
            'location_type' => 'hybrid',
            'workplace_type' => 'hybrid',
            'employment_type' => 'full_time',
            'experience_level' => 'senior',
            'salary_min' => 100000,
            'salary_max' => 150000,
            'status' => JobPosting::STATUS_ACTIVE,
            'visibility' => 'public',
            'description' => 'Mobile architect role building Jetpack Compose app.',
            'deadline' => now()->addDays(30)->toDateString(),
        ]);

        $this->draftJob = JobPosting::create([
            'user_id' => $this->employer->id,
            'company_id' => $this->company->id,
            'title' => 'Confidential Executive Director',
            'slug' => 'confidential-exec',
            'company_name' => $this->company->name,
            'location' => 'Dhaka',
            'status' => 'draft',
            'visibility' => 'public',
            'description' => 'Unpublished confidential draft.',
            'deadline' => now()->addDays(10)->toDateString(),
        ]);

        $this->expiredJob = JobPosting::create([
            'user_id' => $this->employer->id,
            'company_id' => $this->company->id,
            'title' => 'Past Marketing Associate',
            'slug' => 'past-marketing',
            'company_name' => $this->company->name,
            'location' => 'Dhaka',
            'status' => JobPosting::STATUS_ACTIVE,
            'visibility' => 'public',
            'description' => 'Expired campaign opening.',
            'deadline' => now()->subDays(5)->toDateString(),
        ]);
    }

    /**
     * Test 1: Guest can view public active jobs without 401 Unauthorized
     */
    public function test_guest_can_discover_active_jobs_publicly(): void
    {
        $response = $this->getJson('/api/v1/jobs');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['jobs'],
                'meta' => ['total', 'current_page', 'last_page'],
            ]);

        $jobTitles = collect($response->json('data.jobs'))->pluck('title');
        $this->assertTrue($jobTitles->contains('Senior Mobile Engineer'));
        // Expired and draft jobs should NOT appear in public active search
        $this->assertFalse($jobTitles->contains('Confidential Executive Director'));
        $this->assertFalse($jobTitles->contains('Past Marketing Associate'));
    }

    /**
     * Test 2: Unauthenticated guest gets 404 when querying draft job detail
     */
    public function test_draft_job_is_protected_from_public_inspection(): void
    {
        $response = $this->getJson("/api/v1/jobs/{$this->draftJob->id}");

        $response->assertStatus(404);
    }

    /**
     * Test 3: Unauthorized candidate gets 404 when accessing draft job
     */
    public function test_unauthorized_user_cannot_view_draft_job(): void
    {
        Sanctum::actingAs($this->candidate);

        $response = $this->getJson("/api/v1/jobs/{$this->draftJob->id}");

        $response->assertStatus(404);
    }

    /**
     * Test 4: Employer who owns the draft job CAN inspect it
     */
    public function test_authorized_employer_can_view_their_own_draft_job(): void
    {
        Sanctum::actingAs($this->employer);

        $response = $this->getJson("/api/v1/jobs/{$this->draftJob->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.job.id', $this->draftJob->id)
            ->assertJsonPath('data.job.status', 'draft');
    }

    /**
     * Test 5: Guest is blocked with 401 when attempting to create job
     */
    public function test_guest_cannot_create_job(): void
    {
        $response = $this->postJson('/api/v1/jobs', [
            'title' => 'Unauthorized Job',
            'location' => 'Dhaka',
            'employment_type' => 'full_time',
            'description' => 'Test description',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test 6: Malicious user cannot impersonate another company when posting job
     */
    public function test_user_cannot_create_job_under_unauthorized_company(): void
    {
        Sanctum::actingAs($this->attacker);

        $response = $this->postJson('/api/v1/jobs', [
            'company_id' => $this->company->id,
            'title' => 'Spoofed Position',
            'location' => 'Dhaka',
            'employment_type' => 'full_time',
            'description' => 'Attempting to post as TechCorp.',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 7: Authorized company owner can successfully create a job
     */
    public function test_authorized_owner_can_create_job(): void
    {
        Sanctum::actingAs($this->employer);

        $response = $this->postJson('/api/v1/jobs', [
            'company_id' => $this->company->id,
            'title' => 'Senior DevOps Specialist',
            'location' => 'Dhaka, Bangladesh',
            'workplace_type' => 'remote',
            'employment_type' => 'full_time',
            'salary_min' => 120000,
            'salary_max' => 180000,
            'vacancies' => 2,
            'description' => 'Kubernetes and Cloudflare infra specialist.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.job.title', 'Senior DevOps Specialist')
            ->assertJsonPath('data.job.company_name', 'TechCorp Bangladesh');

        $this->assertDatabaseHas('job_postings', [
            'title' => 'Senior DevOps Specialist',
            'company_id' => $this->company->id,
            'user_id' => $this->employer->id,
        ]);
    }

    /**
     * Test 8: Attacker cannot update someone else's job
     */
    public function test_attacker_cannot_update_job(): void
    {
        Sanctum::actingAs($this->attacker);

        $response = $this->putJson("/api/v1/jobs/{$this->activeJob->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 9: Attacker cannot delete someone else's job
     */
    public function test_attacker_cannot_delete_job(): void
    {
        Sanctum::actingAs($this->attacker);

        $response = $this->deleteJson("/api/v1/jobs/{$this->activeJob->id}");

        $response->assertStatus(403);
    }

    /**
     * Test 10: Authorized owner can soft delete job
     */
    public function test_authorized_owner_can_delete_job(): void
    {
        Sanctum::actingAs($this->employer);

        $response = $this->deleteJson("/api/v1/jobs/{$this->activeJob->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('job_postings', [
            'id' => $this->activeJob->id,
        ]);
    }

    /**
     * Test 11: Candidate cannot apply to their own job
     */
    public function test_creator_cannot_apply_to_own_job(): void
    {
        Sanctum::actingAs($this->employer);

        $response = $this->postJson("/api/v1/jobs/{$this->activeJob->id}/apply", [
            'full_name' => 'Self Apply',
            'email' => 'employer@techcorp.test',
            'phone' => '+8801700000000',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test 12: Candidate cannot apply to expired job
     */
    public function test_candidate_cannot_apply_to_expired_job(): void
    {
        Sanctum::actingAs($this->candidate);

        $response = $this->postJson("/api/v1/jobs/{$this->expiredJob->id}/apply", [
            'full_name' => 'Late Candidate',
            'email' => 'candidate@bondhoo.test',
            'phone' => '+8801700000000',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test 13: Candidate cannot apply to draft job
     */
    public function test_candidate_cannot_apply_to_draft_job(): void
    {
        Sanctum::actingAs($this->candidate);

        $response = $this->postJson("/api/v1/jobs/{$this->draftJob->id}/apply", [
            'full_name' => 'Draft Candidate',
            'email' => 'candidate@bondhoo.test',
            'phone' => '+8801700000000',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test 14: Candidate can apply to active job once, and duplicate application is rejected
     */
    public function test_candidate_apply_and_duplicate_prevention(): void
    {
        Sanctum::actingAs($this->candidate);

        // First application
        $first = $this->postJson("/api/v1/jobs/{$this->activeJob->id}/apply", [
            'full_name' => 'Candidate User',
            'email' => 'candidate@bondhoo.test',
            'phone' => '+8801711111111',
            'expected_salary' => 120000,
            'cover_letter' => 'Excited to join the team.',
        ]);

        $first->assertStatus(201)
            ->assertJsonPath('success', true);

        // Duplicate application attempt
        $second = $this->postJson("/api/v1/jobs/{$this->activeJob->id}/apply", [
            'full_name' => 'Candidate User',
            'email' => 'candidate@bondhoo.test',
            'phone' => '+8801711111111',
        ]);

        $second->assertStatus(422);
    }

    /**
     * Test 15: Attacker cannot view applicant list
     */
    public function test_attacker_cannot_view_job_applicants(): void
    {
        Sanctum::actingAs($this->attacker);

        $response = $this->getJson("/api/v1/jobs/{$this->activeJob->id}/applications");

        $response->assertStatus(403);
    }

    /**
     * Test 16: Employer can view applicant list
     */
    public function test_employer_can_view_job_applicants(): void
    {
        // First candidate applies
        JobApplication::create([
            'job_posting_id' => $this->activeJob->id,
            'company_id' => $this->company->id,
            'user_id' => $this->candidate->id,
            'full_name' => 'Candidate User',
            'email' => 'candidate@bondhoo.test',
            'phone' => '+8801700000000',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->employer);

        $response = $this->getJson("/api/v1/jobs/{$this->activeJob->id}/applications");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'applications',
                    'job',
                ],
            ]);
    }

    /**
     * Test 17: Attacker cannot access company ATS recruitment pipeline
     */
    public function test_attacker_cannot_access_company_recruitment_pipeline(): void
    {
        Sanctum::actingAs($this->attacker);

        $response = $this->getJson("/api/v1/companies/{$this->company->id}/recruitment/pipeline");

        $response->assertStatus(403);
    }

    /**
     * Test 18: Attacker cannot access employer dashboard metrics
     */
    public function test_attacker_cannot_access_employer_dashboard(): void
    {
        Sanctum::actingAs($this->attacker);

        $response = $this->getJson("/api/v1/companies/{$this->company->id}/dashboard");

        $response->assertStatus(403);
    }
}
