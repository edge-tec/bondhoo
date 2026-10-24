<?php

namespace Tests\Feature;

use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Phase2BusinessAndProfessionalHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super Admin', 'description' => 'Full access']);
        Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Admin', 'description' => 'Staff administrator']);
        Role::firstOrCreate(['name' => 'USER'], ['label' => 'Regular User', 'description' => 'Platform user']);
        Role::firstOrCreate(['name' => 'VERIFIED_USER'], ['label' => 'Verified User', 'description' => 'Identity verified citizen']);
    }

    protected function createUser(array $attributes = []): User
    {
        static $counter = 500;
        $counter++;

        $user = User::factory()->create(array_merge([
            'username' => "phase2_user_{$counter}",
            'name' => "Phase2 User {$counter}",
            'email' => "phase2_user_{$counter}@example.com",
            'phone' => "+880171100{$counter}",
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ], $attributes));

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'Phase 2 test bio',
                'about' => 'Phase 2 test about',
                'is_locked' => false,
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'profile_visibility' => 'public',
            ]
        );

        return $user;
    }

    public function test_pages_directory_lists_pages_with_category_and_search_filters(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Page::create([
            'name' => 'Tech Innovations BD',
            'slug' => 'tech-innovations-bd',
            'category' => 'Technology',
            'bio' => 'Cutting edge AI and Laravel software hub',
            'owner_id' => $user->id,
            'followers_count' => 150,
            'posts_count' => 10,
        ]);

        Page::create([
            'name' => 'Dhaka Foodies Club',
            'slug' => 'dhaka-foodies-club',
            'category' => 'Entertainment',
            'bio' => 'Best food reviews in Dhaka',
            'owner_id' => $user->id,
            'followers_count' => 300,
            'posts_count' => 25,
        ]);

        // 1. List all
        $responseAll = $this->getJson('/api/v1/pages');
        $responseAll->assertOk()
            ->assertJsonPath('success', true);
        $this->assertCount(2, $responseAll->json('data'));

        // 2. Filter by Category
        $responseTech = $this->getJson('/api/v1/pages?category=Technology');
        $responseTech->assertOk();
        $this->assertCount(1, $responseTech->json('data'));
        $this->assertSame('Tech Innovations BD', $responseTech->json('data.0.name'));

        // 3. Filter by Search Query
        $responseSearch = $this->getJson('/api/v1/pages?search=Foodies');
        $responseSearch->assertOk();
        $this->assertCount(1, $responseSearch->json('data'));
        $this->assertSame('Dhaka Foodies Club', $responseSearch->json('data.0.name'));
    }

    public function test_user_can_create_new_page_and_toggle_follow(): void
    {
        $creator = $this->createUser();
        $follower = $this->createUser();

        $this->actingAs($creator);

        // 1. Create Page
        $createRes = $this->postJson('/api/v1/pages', [
            'name' => 'Jugajug Developer Community',
            'category' => 'Technology',
            'bio' => 'Official community of developers',
            'website' => 'https://jugajug.com',
        ]);

        $createRes->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Jugajug Developer Community')
            ->assertJsonPath('data.category', 'Technology');

        $pageId = (int) $createRes->json('data.id');

        // 2. Switch to follower user and follow
        $this->actingAs($follower);

        $followRes = $this->postJson("/api/v1/pages/{$pageId}/follow");
        $followRes->assertOk()
            ->assertJsonPath('data.is_following', true)
            ->assertJsonPath('data.followers_count', 1);

        // 3. Unfollow
        $unfollowRes = $this->postJson("/api/v1/pages/{$pageId}/follow");
        $unfollowRes->assertOk()
            ->assertJsonPath('data.is_following', false)
            ->assertJsonPath('data.followers_count', 0);
    }

    public function test_user_can_create_job_posting_and_search_with_filters(): void
    {
        $recruiter = $this->createUser();
        $candidate = $this->createUser();

        $this->actingAs($recruiter);

        // 1. Create Job Posting
        $deadline = Carbon::now()->addDays(30)->toDateString();
        $createJobRes = $this->postJson('/api/v1/jobs', [
            'title' => 'Senior Laravel Engineer',
            'company_name' => 'Jugajug Enterprise Ltd',
            'location' => 'Gulshan, Dhaka',
            'location_type' => 'hybrid',
            'employment_type' => 'full_time',
            'experience_level' => 'senior',
            'salary_min' => 80000,
            'salary_max' => 140000,
            'salary_currency' => 'BDT',
            'salary_negotiable' => false,
            'deadline' => $deadline,
            'description' => 'Architect and scale high-concurrency microservices.',
            'requirements' => '5+ years experience in PHP 8.x, Laravel, MySQL, Redis.',
        ]);

        $createJobRes->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.job.title', 'Senior Laravel Engineer')
            ->assertJsonPath('data.job.salary_formatted', '80,000 - 140,000 BDT');

        $jobId = (int) $createJobRes->json('data.job.id');

        // Create second job for filter testing
        $this->postJson('/api/v1/jobs', [
            'title' => 'Junior UI/UX Designer',
            'company_name' => 'Jugajug Enterprise Ltd',
            'location' => 'Remote, Bangladesh',
            'location_type' => 'remote',
            'employment_type' => 'contract',
            'deadline' => $deadline,
            'description' => 'Figma design and design tokens.',
        ]);

        // 2. Candidate searches and filters jobs
        $this->actingAs($candidate);

        // Search by keyword
        $searchRes = $this->getJson('/api/v1/jobs?search=Laravel');
        $searchRes->assertOk();
        $this->assertCount(1, $searchRes->json('data.jobs'));
        $this->assertSame('Senior Laravel Engineer', $searchRes->json('data.jobs.0.title'));

        // Filter by employment type
        $typeRes = $this->getJson('/api/v1/jobs?employment_type=contract');
        $typeRes->assertOk();
        $this->assertCount(1, $typeRes->json('data.jobs'));
        $this->assertSame('Junior UI/UX Designer', $typeRes->json('data.jobs.0.title'));

        // View single job detail (increments views_count)
        $detailRes = $this->getJson("/api/v1/jobs/{$jobId}");
        $detailRes->assertOk()
            ->assertJsonPath('data.job.views_count', 1)
            ->assertJsonPath('data.job.has_applied', false);
    }

    public function test_candidate_can_apply_for_job_and_duplicate_prevention_works(): void
    {
        $recruiter = $this->createUser();
        $candidate = $this->createUser();

        $this->actingAs($recruiter);

        $job = JobPosting::create([
            'user_id' => $recruiter->id,
            'title' => 'Full-Stack Developer',
            'company_name' => 'Edge Technologies',
            'location' => 'Banani, Dhaka',
            'location_type' => 'onsite',
            'employment_type' => 'full_time',
            'deadline' => Carbon::now()->addDays(20)->toDateString(),
            'description' => 'Develop modern web solutions.',
            'status' => 'active',
        ]);

        $this->actingAs($candidate);

        // 1. Submit application
        $applyRes = $this->postJson("/api/v1/jobs/{$job->id}/apply", [
            'full_name' => 'Rahim Chowdhury',
            'phone' => '01711223344',
            'email' => 'rahim@example.com',
            'resume_url' => 'https://drive.google.com/resume-rahim.pdf',
            'portfolio_url' => 'https://github.com/rahim-dev',
            'expected_salary' => 95000,
            'cover_letter' => 'I have 4 years of hands-on experience in full-stack web applications.',
        ]);

        $applyRes->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.application.full_name', 'Rahim Chowdhury')
            ->assertJsonPath('data.application.status', 'pending');

        // Check applications_count updated
        $this->assertSame(1, $job->fresh()->applications_count);

        // 2. Duplicate application attempt should fail
        $duplicateRes = $this->postJson("/api/v1/jobs/{$job->id}/apply", [
            'full_name' => 'Rahim Chowdhury',
            'phone' => '01711223344',
            'email' => 'rahim@example.com',
            'resume_url' => 'https://drive.google.com/resume-rahim.pdf',
        ]);

        $duplicateRes->assertStatus(422)
            ->assertJsonPath('success', false);

        // 3. Candidate verifies their application in "My Applications"
        $myAppsRes = $this->getJson('/api/v1/jobs/my/applications');
        $myAppsRes->assertOk()
            ->assertJsonPath('success', true);
        $this->assertCount(1, $myAppsRes->json('data'));
        $this->assertSame('Full-Stack Developer', $myAppsRes->json('data.0.job_posting.title'));
    }

    public function test_recruiter_can_review_candidates_and_update_status(): void
    {
        $recruiter = $this->createUser();
        $candidate = $this->createUser();
        $unauthorizedUser = $this->createUser();

        $job = JobPosting::create([
            'user_id' => $recruiter->id,
            'title' => 'DevOps Cloud Architect',
            'company_name' => 'CloudNet BD',
            'location' => 'Dhaka',
            'location_type' => 'remote',
            'employment_type' => 'full_time',
            'deadline' => Carbon::now()->addDays(15)->toDateString(),
            'description' => 'Manage Kubernetes clusters and CI/CD pipelines.',
            'status' => 'active',
        ]);

        $application = JobApplication::create([
            'job_posting_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => 'Karim Hasan',
            'email' => 'karim@example.com',
            'phone' => '01811998877',
            'resume_url' => 'https://cloudnet.com/resumes/karim.pdf',
            'expected_salary' => 120000,
            'status' => 'pending',
        ]);

        // 1. Unauthorized user cannot see recruiter's applicant list
        $this->actingAs($unauthorizedUser);
        $this->getJson("/api/v1/jobs/{$job->id}/applications")
            ->assertForbidden();

        // 2. Recruiter reviews applications
        $this->actingAs($recruiter);
        $appListRes = $this->getJson("/api/v1/jobs/{$job->id}/applications");
        $appListRes->assertOk();
        $this->assertCount(1, $appListRes->json('data.applications'));

        // 3. Recruiter shortlists candidate
        $updateStatusRes = $this->putJson("/api/v1/jobs/applications/{$application->id}/status", [
            'status' => 'shortlisted',
            'admin_notes' => 'Strong Kubernetes and Terraform background. Schedule technical round.',
        ]);

        $updateStatusRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.application.status', 'shortlisted');

        $this->assertSame('shortlisted', $application->fresh()->status);
        $this->assertSame('Strong Kubernetes and Terraform background. Schedule technical round.', $application->fresh()->admin_notes);
    }

    public function test_web_route_jobs_redirects_to_dashboard_with_jobs_tab(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/jobs');
        $response->assertRedirect('/dashboard?tab=jobs');
    }
}
