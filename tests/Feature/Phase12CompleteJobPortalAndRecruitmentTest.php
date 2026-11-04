<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\CompanyVerification;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\JobPosting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase12CompleteJobPortalAndRecruitmentTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $employerUser;

    private User $candidateUser;

    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->adminUser = User::factory()->create([
            'email' => 'admin@connectly.test',
        ]);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN'], ['display_name' => 'Administrator']);
        $this->adminUser->roles()->attach($adminRole);

        $this->employerUser = User::factory()->create([
            'name' => 'Recruiter Rahim',
            'email' => 'recruiter@techcorp.test',
        ]);

        $this->candidateUser = User::factory()->create([
            'name' => 'Candidate Karim',
            'email' => 'candidate@connectly.test',
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'name' => 'Hacker Bob',
            'email' => 'bob@external.test',
        ]);
    }

    /**
     * Test 1: Company Profile Creation, Ownership, and Membership
     */
    public function test_company_creation_and_membership(): void
    {
        Sanctum::actingAs($this->employerUser);

        $response = $this->postJson('/api/v1/companies', [
            'name' => 'TechCorp Bangladesh Ltd',
            'industry' => 'Software & Technology',
            'company_size' => '51-200',
            'headquarters' => 'Dhaka, Bangladesh',
            'website' => 'https://techcorp.test',
            'description' => 'Leading software development enterprise.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company.name', 'TechCorp Bangladesh Ltd')
            ->assertJsonPath('data.company.owner_user_id', $this->employerUser->id);

        $companyId = $response->json('data.company.id');

        // Verify company member is automatically set to Owner
        $this->assertDatabaseHas('company_members', [
            'company_id' => $companyId,
            'user_id' => $this->employerUser->id,
            'role' => CompanyMember::ROLE_OWNER,
            'invitation_status' => CompanyMember::STATUS_ACCEPTED,
        ]);
    }

    /**
     * Test 2: Team Member Invitation and Role Management
     */
    public function test_company_team_invitation_and_role_management(): void
    {
        Sanctum::actingAs($this->employerUser);

        $company = Company::create([
            'owner_user_id' => $this->employerUser->id,
            'name' => 'Apex Systems',
            'slug' => 'apex-systems',
            'verification_status' => Company::VERIFICATION_UNVERIFIED,
            'visibility' => 'public',
        ]);
        CompanyMember::create([
            'company_id' => $company->id,
            'user_id' => $this->employerUser->id,
            'role' => CompanyMember::ROLE_OWNER,
            'invitation_status' => CompanyMember::STATUS_ACCEPTED,
        ]);

        $teammate = User::factory()->create(['email' => 'teammate@apex.test']);

        // Invite teammate as recruiter
        $inviteRes = $this->postJson("/api/v1/companies/{$company->id}/members/invite", [
            'email' => $teammate->email,
            'role' => CompanyMember::ROLE_RECRUITER,
        ]);

        $inviteRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.member.role', CompanyMember::ROLE_RECRUITER);

        $memberId = $inviteRes->json('data.member.id');

        // Update role to admin
        $updateRes = $this->patchJson("/api/v1/companies/{$company->id}/members/{$memberId}", [
            'role' => CompanyMember::ROLE_ADMIN,
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.member.role', CompanyMember::ROLE_ADMIN);
    }

    /**
     * Test 3: Job Creation Wizard with Skills and Custom Questions
     */
    public function test_job_creation_wizard_with_skills_and_questions(): void
    {
        Sanctum::actingAs($this->employerUser);

        $company = Company::create([
            'owner_user_id' => $this->employerUser->id,
            'name' => 'Silicon Valley Tech',
            'slug' => 'silicon-valley-tech',
        ]);
        CompanyMember::create([
            'company_id' => $company->id,
            'user_id' => $this->employerUser->id,
            'role' => CompanyMember::ROLE_OWNER,
            'invitation_status' => CompanyMember::STATUS_ACCEPTED,
        ]);

        $category = JobCategory::create([
            'name' => 'Software Engineering',
            'slug' => 'software-engineering',
        ]);

        $response = $this->postJson('/api/v1/jobs', [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Senior Full Stack Laravel & Kotlin Engineer',
            'location' => 'Dhaka, Bangladesh',
            'workplace_type' => 'hybrid',
            'employment_type' => 'full_time',
            'experience_level' => 'senior',
            'salary_min' => 150000,
            'salary_max' => 220000,
            'salary_currency' => 'BDT',
            'salary_negotiable' => false,
            'vacancies' => 2,
            'description' => 'We are seeking an outstanding Full Stack developer.',
            'responsibilities' => 'Architect robust APIs and Android client features.',
            'requirements' => '5+ years PHP/Laravel & Jetpack Compose experience.',
            'skills' => ['Laravel', 'Kotlin', 'Jetpack Compose', 'PostgreSQL'],
            'questions' => [
                [
                    'question' => 'How many years of commercial Laravel experience do you have?',
                    'type' => 'short_answer',
                    'is_required' => true,
                ],
                [
                    'question' => 'Are you comfortable working on-site in Dhaka 3 days a week?',
                    'type' => 'yes_no',
                    'is_required' => true,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.job.title', 'Senior Full Stack Laravel & Kotlin Engineer')
            ->assertJsonPath('data.job.workplace_type', 'hybrid')
            ->assertJsonPath('data.job.status', JobPosting::STATUS_ACTIVE);

        $jobId = $response->json('data.job.id');

        $this->assertDatabaseHas('job_postings', [
            'id' => $jobId,
            'company_id' => $company->id,
            'vacancies' => 2,
        ]);

        $this->assertDatabaseHas('job_skills', ['name' => 'Laravel']);
        $this->assertDatabaseHas('job_skills', ['name' => 'Kotlin']);
        $this->assertDatabaseHas('application_questions', ['job_posting_id' => $jobId]);
    }

    /**
     * Test 4: Job Publishing, Pausing, Duplication, and View Deduplication
     */
    public function test_job_lifecycle_pause_resume_duplicate_and_view_tracking(): void
    {
        Sanctum::actingAs($this->employerUser);

        $job = JobPosting::create([
            'user_id' => $this->employerUser->id,
            'title' => 'Product Designer (UI/UX)',
            'company_name' => 'Creative Agency',
            'location' => 'Remote',
            'location_type' => 'remote',
            'employment_type' => 'full_time',
            'description' => 'Figma and Material Design expert needed.',
            'status' => JobPosting::STATUS_ACTIVE,
        ]);

        // Pause Job
        $pauseRes = $this->postJson("/api/v1/jobs/{$job->id}/pause");
        $pauseRes->assertStatus(200)->assertJsonPath('data.job.status', JobPosting::STATUS_PAUSED);

        // Resume Job
        $resumeRes = $this->postJson("/api/v1/jobs/{$job->id}/resume");
        $resumeRes->assertStatus(200)->assertJsonPath('data.job.status', JobPosting::STATUS_ACTIVE);

        // Duplicate Job
        $dupRes = $this->postJson("/api/v1/jobs/{$job->id}/duplicate");
        $dupRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.job.status', JobPosting::STATUS_DRAFT);

        // Track View (from Candidate)
        Sanctum::actingAs($this->candidateUser);
        $showRes = $this->getJson("/api/v1/jobs/{$job->id}");
        $showRes->assertStatus(200)->assertJsonPath('data.job.views_count', 1);

        // Immediate repeat view should not artificially inflate
        $showRes2 = $this->getJson("/api/v1/jobs/{$job->id}");
        $showRes2->assertStatus(200)->assertJsonPath('data.job.views_count', 1);
    }

    /**
     * Test 5: Candidate Resume Management with Secure Validation and Download Gate
     */
    public function test_candidate_resume_upload_and_security_download_gate(): void
    {
        Sanctum::actingAs($this->candidateUser);

        $file = UploadedFile::fake()->create('karim_senior_cv.pdf', 500, 'application/pdf');

        $uploadRes = $this->postJson('/api/v1/candidate/resumes', [
            'file' => $file,
            'title' => 'Senior Engineering Resume 2026',
            'is_default' => true,
        ]);

        $uploadRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.resume.title', 'Senior Engineering Resume 2026')
            ->assertJsonPath('data.resume.is_default', true);

        $resumeId = $uploadRes->json('data.resume.id');

        // Owner can download resume
        $downloadRes = $this->get("/api/v1/candidate/resumes/{$resumeId}/download");
        $downloadRes->assertStatus(200);

        // Unauthorized user CANNOT download private resume (IDOR Protection)
        Sanctum::actingAs($this->unauthorizedUser);
        $hackerRes = $this->getJson("/api/v1/candidate/resumes/{$resumeId}/download");
        $hackerRes->assertStatus(403);
    }

    /**
     * Test 6: Job Application Submission, Custom Answers, and Duplicate Prevention
     */
    public function test_job_application_submission_and_duplicate_prevention(): void
    {
        $company = Company::create([
            'owner_user_id' => $this->employerUser->id,
            'name' => 'DataFlow Inc',
            'slug' => 'dataflow-inc',
        ]);

        $job = JobPosting::create([
            'user_id' => $this->employerUser->id,
            'company_id' => $company->id,
            'title' => 'Mobile Lead Engineer',
            'company_name' => 'DataFlow Inc',
            'location' => 'Dhaka',
            'location_type' => 'onsite',
            'employment_type' => 'full_time',
            'description' => 'Android Kotlin Architecture Lead.',
            'status' => JobPosting::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($this->candidateUser);

        // Submit Application
        $applyRes = $this->postJson("/api/v1/jobs/{$job->id}/apply", [
            'full_name' => 'Karim Candidate',
            'email' => 'candidate@connectly.test',
            'phone' => '+8801700000000',
            'expected_salary' => 180000,
            'cover_letter' => 'I have led multiple enterprise Android applications.',
            'answers' => [
                'Commercial Android experience' => '6 years',
                'Ready to join' => 'Immediate',
            ],
        ]);

        $applyRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.application.status', JobApplication::STATUS_APPLIED);

        // Duplicate submission prevention
        $dupApply = $this->postJson("/api/v1/jobs/{$job->id}/apply", [
            'full_name' => 'Karim Candidate',
            'email' => 'candidate@connectly.test',
            'phone' => '+8801700000000',
        ]);

        $dupApply->assertStatus(422);
    }

    /**
     * Test 7: Multi-Stage ATS Recruitment Pipeline and Internal Recruiter Notes Privacy
     */
    public function test_ats_stage_transition_and_internal_notes_privacy(): void
    {
        $company = Company::create([
            'owner_user_id' => $this->employerUser->id,
            'name' => 'InnovateX',
            'slug' => 'innovatex',
        ]);
        CompanyMember::create([
            'company_id' => $company->id,
            'user_id' => $this->employerUser->id,
            'role' => CompanyMember::ROLE_OWNER,
            'invitation_status' => CompanyMember::STATUS_ACCEPTED,
        ]);

        $job = JobPosting::create([
            'user_id' => $this->employerUser->id,
            'company_id' => $company->id,
            'title' => 'Senior Backend Architect',
            'company_name' => 'InnovateX',
            'location' => 'Dhaka',
            'description' => 'High throughput microservices.',
            'status' => JobPosting::STATUS_ACTIVE,
        ]);

        $application = JobApplication::create([
            'job_posting_id' => $job->id,
            'company_id' => $company->id,
            'user_id' => $this->candidateUser->id,
            'full_name' => 'Karim Candidate',
            'email' => 'candidate@connectly.test',
            'phone' => '+8801700000000',
            'status' => JobApplication::STATUS_APPLIED,
        ]);

        // Employer moves candidate to Shortlisted
        Sanctum::actingAs($this->employerUser);
        $stageRes = $this->patchJson("/api/v1/applications/{$application->id}/stage", [
            'stage' => JobApplication::STATUS_SHORTLISTED,
            'notes' => 'Candidate has strong system design background.',
        ]);

        $stageRes->assertStatus(200)
            ->assertJsonPath('data.application.status', JobApplication::STATUS_SHORTLISTED);

        // Employer adds internal confidential note
        $noteRes = $this->postJson("/api/v1/applications/{$application->id}/notes", [
            'note' => 'Confidential: Recommended salary offer BDT 200,000.',
        ]);

        $noteRes->assertStatus(201)
            ->assertJsonPath('success', true);

        // Candidate views application: INTERNAL RECRUITER NOTES MUST BE HIDDEN
        Sanctum::actingAs($this->candidateUser);
        $candidateView = $this->getJson("/api/v1/applications/{$application->id}");
        $candidateView->assertStatus(200)
            ->assertJsonPath('data.application.admin_notes', null)
            ->assertJsonPath('data.application.notes', []);

        // Recruiter views application: Internal notes ARE visible
        Sanctum::actingAs($this->employerUser);
        $recruiterView = $this->getJson("/api/v1/applications/{$application->id}");
        $recruiterView->assertStatus(200)
            ->assertJsonCount(1, 'data.application.notes');
    }

    /**
     * Test 8: Interview Scheduling, Candidate Response, and Interview Feedback
     */
    public function test_interview_scheduling_response_and_feedback(): void
    {
        $company = Company::create([
            'owner_user_id' => $this->employerUser->id,
            'name' => 'CloudScale Ltd',
            'slug' => 'cloudscale-ltd',
        ]);
        CompanyMember::create([
            'company_id' => $company->id,
            'user_id' => $this->employerUser->id,
            'role' => CompanyMember::ROLE_OWNER,
            'invitation_status' => CompanyMember::STATUS_ACCEPTED,
        ]);

        $job = JobPosting::create([
            'user_id' => $this->employerUser->id,
            'company_id' => $company->id,
            'title' => 'DevOps Engineer',
            'company_name' => 'CloudScale Ltd',
            'location' => 'Remote',
            'description' => 'Kubernetes & AWS specialist.',
            'status' => JobPosting::STATUS_ACTIVE,
        ]);

        $application = JobApplication::create([
            'job_posting_id' => $job->id,
            'company_id' => $company->id,
            'user_id' => $this->candidateUser->id,
            'full_name' => 'Karim Candidate',
            'email' => 'candidate@connectly.test',
            'phone' => '+8801700000000',
            'status' => JobApplication::STATUS_SHORTLISTED,
        ]);

        // Employer schedules interview
        Sanctum::actingAs($this->employerUser);
        $scheduleRes = $this->postJson("/api/v1/applications/{$application->id}/interviews", [
            'interview_type' => Interview::TYPE_VIDEO,
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
            'meeting_url' => 'https://meet.connectly.test/interview-room-101',
            'instructions' => 'Please bring your laptop ready for live system design.',
        ]);

        $scheduleRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.interview.status', Interview::STATUS_SCHEDULED);

        $interviewId = $scheduleRes->json('data.interview.id');

        // Candidate responds and accepts interview
        Sanctum::actingAs($this->candidateUser);
        $acceptRes = $this->postJson("/api/v1/interviews/{$interviewId}/response", [
            'status' => 'accept',
        ]);

        $acceptRes->assertStatus(200)
            ->assertJsonPath('data.interview.status', Interview::STATUS_CONFIRMED);

        // Recruiter conducts interview and records feedback
        Sanctum::actingAs($this->employerUser);
        $feedbackRes = $this->postJson("/api/v1/interviews/{$interviewId}/feedback", [
            'feedback' => 'Exceptional technical depth. Recommend direct job offer.',
        ]);

        $feedbackRes->assertStatus(200)
            ->assertJsonPath('data.interview.status', Interview::STATUS_COMPLETED);
    }

    /**
     * Test 9: Saved Jobs, Job Alerts, and Employer Dashboard Real Metrics
     */
    public function test_saved_jobs_job_alerts_and_employer_dashboard_metrics(): void
    {
        $company = Company::create([
            'owner_user_id' => $this->employerUser->id,
            'name' => 'MetricsCorp',
            'slug' => 'metricscorp',
        ]);
        CompanyMember::create([
            'company_id' => $company->id,
            'user_id' => $this->employerUser->id,
            'role' => CompanyMember::ROLE_OWNER,
            'invitation_status' => CompanyMember::STATUS_ACCEPTED,
        ]);

        $job = JobPosting::create([
            'user_id' => $this->employerUser->id,
            'company_id' => $company->id,
            'title' => 'AI Engineer',
            'company_name' => 'MetricsCorp',
            'location' => 'Dhaka',
            'description' => 'PyTorch & LLM fine-tuning.',
            'status' => JobPosting::STATUS_ACTIVE,
        ]);

        // Candidate saves job
        Sanctum::actingAs($this->candidateUser);
        $saveRes = $this->postJson("/api/v1/jobs/{$job->id}/save");
        $saveRes->assertStatus(200)->assertJsonPath('success', true);

        $savedList = $this->getJson('/api/v1/candidate/saved-jobs');
        $savedList->assertStatus(200)->assertJsonCount(1, 'data.jobs');

        // Candidate creates job alert
        $alertRes = $this->postJson('/api/v1/candidate/job-alerts', [
            'title' => 'AI and Machine Learning Jobs in Dhaka',
            'keywords' => 'AI, LLM, PyTorch',
            'locations' => ['Dhaka', 'Remote'],
            'frequency' => 'daily',
        ]);
        $alertRes->assertStatus(201)->assertJsonPath('success', true);

        // Employer dashboard verification with real DB aggregates
        Sanctum::actingAs($this->employerUser);
        $dashRes = $this->getJson("/api/v1/companies/{$company->id}/dashboard");
        $dashRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.metrics.total_jobs', 1)
            ->assertJsonPath('data.metrics.active_jobs', 1);
    }

    /**
     * Test 10: Admin Job Moderation and Company Verification Approval Workflow
     */
    public function test_admin_job_moderation_and_company_verification(): void
    {
        $company = Company::create([
            'owner_user_id' => $this->employerUser->id,
            'name' => 'Unverified Startup Ltd',
            'slug' => 'unverified-startup-ltd',
            'verification_status' => Company::VERIFICATION_PENDING,
        ]);

        $verification = CompanyVerification::create([
            'company_id' => $company->id,
            'user_id' => $this->employerUser->id,
            'documents' => ['incorporation_certificate.pdf', 'trade_license.pdf'],
            'status' => CompanyVerification::STATUS_PENDING,
        ]);

        $pendingJob = JobPosting::create([
            'user_id' => $this->employerUser->id,
            'company_id' => $company->id,
            'title' => 'Pending Review Position',
            'company_name' => 'Unverified Startup Ltd',
            'location' => 'Dhaka',
            'description' => 'Awaiting admin green light.',
            'status' => JobPosting::STATUS_PENDING_REVIEW,
        ]);

        // Non-admin CANNOT access admin queues
        Sanctum::actingAs($this->candidateUser);
        $forbiddenRes = $this->getJson('/api/v1/admin/jobs/pending');
        $forbiddenRes->assertStatus(403);

        // Admin approves company verification
        Sanctum::actingAs($this->adminUser);
        $apprVerRes = $this->postJson("/api/v1/admin/companies/verifications/{$verification->id}/approve");
        $apprVerRes->assertStatus(200)->assertJsonPath('success', true);
        $this->assertEquals(Company::VERIFICATION_VERIFIED, $company->fresh()->verification_status);

        // Admin approves job posting
        $apprJobRes = $this->postJson("/api/v1/admin/jobs/{$pendingJob->id}/approve");
        $apprJobRes->assertStatus(200)->assertJsonPath('data.job.status', JobPosting::STATUS_ACTIVE);
    }
}
