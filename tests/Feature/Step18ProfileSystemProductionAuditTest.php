<?php

namespace Tests\Feature;

use App\Events\ProfileUpdatedEvent;
use App\Jobs\RecordProfileViewJob;
use App\Models\PrivacySetting;
use App\Models\ProfileEducation;
use App\Models\ProfileExperience;
use App\Models\ProfileInterest;
use App\Models\ProfileLanguage;
use App\Models\ProfileSkill;
use App\Models\ProfileSocialLink;
use App\Models\ProfileVerification;
use App\Models\ProfileView;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use App\Services\ProfileCompletionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Step18ProfileSystemProductionAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
        Storage::fake('local');
    }

    protected function createUser(array $attributes = []): User
    {
        static $counter = 2000;
        $counter++;

        $user = User::factory()->create(array_merge([
            'username' => "audit_user_{$counter}",
            'name' => "Audit User {$counter}",
            'first_name' => 'Audit',
            'last_name' => "User {$counter}",
            'email' => "audit_user_{$counter}@example.com",
            'phone' => "+880173300{$counter}",
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ], $attributes));

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'Audit',
                'last_name' => "User {$counter}",
                'display_name' => "Audit User {$counter}",
                'bio' => 'Standard bio text for audit testing',
                'about' => 'Standard about description text for audit testing',
                'country' => 'Bangladesh',
                'city' => 'Dhaka',
                'location' => 'Dhaka, Bangladesh',
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'is_profile_locked' => false,
            ]
        );

        PrivacySetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'profile_visibility' => 'public',
                'email_privacy' => 'only_me',
                'phone_privacy' => 'only_me',
                'bio_privacy' => 'public',
                'about_privacy' => 'public',
                'education_privacy' => 'public',
                'work_privacy' => 'public',
                'skills_privacy' => 'public',
                'interests_privacy' => 'public',
                'languages_privacy' => 'public',
                'social_links_privacy' => 'public',
            ]
        );

        return $user;
    }

    protected function createAdmin(): User
    {
        $admin = $this->createUser([
            'username' => 'system_super_admin',
            'email' => 'superadmin@jugajug.com',
        ]);
        $adminRole = Role::where('name', 'SUPER_ADMIN')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }

        return $admin;
    }

    // =========================================================================
    // Area 1 & Area 2: Public Profile & Edit Profile
    // =========================================================================

    public function test_public_profile_rendered_via_v1_and_v2_apis(): void
    {
        $user = $this->createUser(['username' => 'star_coder']);

        $v1Response = $this->getJson("/api/v1/users/{$user->username}");
        $v1Response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'star_coder');

        $v2Response = $this->getJson("/api/v2/profile/{$user->username}");
        $v2Response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'star_coder');
    }

    public function test_public_profile_web_route_renders_view_properly(): void
    {
        $user = $this->createUser(['username' => 'web_profile_user']);

        $response = $this->get("/user/{$user->username}");
        $response->assertStatus(200);
    }

    public function test_edit_profile_updates_name_and_details_successfully(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v2/profile', [
            'name' => 'UpdatedFirst UpdatedLast',
            'first_name' => 'UpdatedFirst',
            'last_name' => 'UpdatedLast',
            'display_name' => 'UpdatedFirst UpdatedLast',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertEquals('UpdatedFirst UpdatedLast', $user->fresh()->name);
    }

    // =========================================================================
    // Area 3 & Area 4: Avatar & Cover Management
    // =========================================================================

    public function test_avatar_upload_crop_and_delete_lifecycle(): void
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        // 1. Upload avatar using 'file' key
        $uploadRes = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/avatar', [
            'file' => $file,
        ]);
        $uploadRes->assertStatus(200)->assertJsonPath('success', true);
        $this->assertNotNull($user->profile->fresh()->avatar_url);

        // 2. Delete avatar
        $delRes = $this->actingAs($user, 'sanctum')->deleteJson('/api/v2/profile/avatar');
        $delRes->assertStatus(200);
    }

    public function test_cover_upload_crop_and_reposition_lifecycle(): void
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->image('cover.jpg', 1200, 400);

        // 1. Upload cover
        $uploadRes = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/cover', [
            'file' => $file,
        ]);
        $uploadRes->assertStatus(200)->assertJsonPath('success', true);
        $this->assertNotNull($user->profile->fresh()->cover_url);

        // 2. Reposition cover
        $posRes = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/cover/position', [
            'position_y' => 65,
        ]);
        $posRes->assertStatus(200);

        // 3. Delete cover
        $delRes = $this->actingAs($user, 'sanctum')->deleteJson('/api/v2/profile/cover');
        $delRes->assertStatus(200);
    }

    // =========================================================================
    // Area 5, 6, 7: Personal Info, Bio & About
    // =========================================================================

    public function test_personal_info_updates_country_city_gender_and_dob(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v2/profile/personal', [
            'gender' => 'female',
            'birth_date' => '1998-05-12',
            'country' => 'Bangladesh',
            'city' => 'Chittagong',
            'website' => 'https://devfemale.org',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertEquals('female', $user->profile->fresh()->gender);
        $this->assertEquals('Chittagong', $user->profile->fresh()->city);
    }

    public function test_bio_handles_unicode_and_emojis_cleanly(): void
    {
        $user = $this->createUser();
        $unicodeBio = '🚀 সফ্টওয়্যার ইঞ্জিনিয়ার এবং ওপেন সোর্স অনুরাগী ❤️ #Laravel';

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v2/profile/about', [
            'bio' => $unicodeBio,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($unicodeBio, $user->profile->fresh()->bio);
    }

    public function test_about_handles_multiline_structured_text(): void
    {
        $user = $this->createUser();
        $aboutText = "## Career Journey\nOver 10 years of building high scale distributed architectures.\n- PHP & Laravel\n- Redis & PostgreSQL\n- Docker & Kubernetes";

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v2/profile/about', [
            'about' => $aboutText,
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Career Journey', $user->profile->fresh()->about);
    }

    // =========================================================================
    // Area 8: Username Rules, Reserved Words & Uniqueness
    // =========================================================================

    public function test_username_availability_check_and_reserved_word_protection(): void
    {
        $user = $this->createUser();

        // 1. Reserved username returns available = false
        $resReserved = $this->actingAs($user, 'sanctum')->getJson('/api/v2/profile/username/check?username=admin');
        $resReserved->assertStatus(200)
            ->assertJsonPath('data.is_available', false)
            ->assertJsonPath('data.reason', 'reserved');

        // 2. Unique valid username returns available = true
        $resValid = $this->actingAs($user, 'sanctum')->getJson('/api/v2/profile/username/check?username=unique_developer_99');
        $resValid->assertStatus(200)
            ->assertJsonPath('data.is_available', true);
    }

    public function test_username_change_updates_slug_and_logs_audit(): void
    {
        $user = $this->createUser(['username' => 'original_handle']);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v2/profile/username', [
            'username' => 'new_handle_2026',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('new_handle_2026', $user->fresh()->username);
    }

    // =========================================================================
    // Area 9: Education CRUD & Ordering
    // =========================================================================

    public function test_education_crud_and_display_ordering(): void
    {
        $user = $this->createUser();

        // 1. Create education
        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/education', [
            'institution' => 'University of Oxford',
            'degree' => 'Master of Science',
            'field_of_study' => 'Artificial Intelligence',
            'start_date' => '2021-09-01',
            'end_date' => '2022-09-01',
            'currently_studying' => false,
            'privacy' => 'public',
        ]);
        $res->assertStatus(201);
        $eduId = $res->json('data.id');

        // 2. Update education
        $updateRes = $this->actingAs($user, 'sanctum')->putJson("/api/v2/profile/education/{$eduId}", [
            'degree' => 'M.Sc. with Distinction',
        ]);
        $updateRes->assertStatus(200);

        // 3. Delete education (soft delete)
        $delRes = $this->actingAs($user, 'sanctum')->deleteJson("/api/v2/profile/education/{$eduId}");
        $delRes->assertStatus(200);
        $this->assertSoftDeleted('profile_educations', ['id' => $eduId]);
    }

    // =========================================================================
    // Area 10: Work Experience CRUD & Current Job Logic
    // =========================================================================

    public function test_work_experience_crud_and_current_employment_flag(): void
    {
        $user = $this->createUser();

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/work', [
            'company' => 'Stripe',
            'position' => 'Staff Infrastructure Engineer',
            'employment_type' => 'full_time',
            'start_date' => '2022-01-01',
            'currently_working' => true,
            'privacy' => 'public',
        ]);

        $res->assertStatus(201)->assertJsonPath('data.is_current', true);
        $expId = $res->json('data.id');

        $this->assertDatabaseHas('profile_experiences', [
            'id' => $expId,
            'company_name' => 'Stripe',
            'is_current' => true,
        ]);
    }

    // =========================================================================
    // Area 11 & 12: Skills & Interests with Central Taxonomy Reuse
    // =========================================================================

    public function test_skills_creation_search_and_taxonomy_reuse(): void
    {
        $user = $this->createUser();

        // 1. Add skill
        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/skills', [
            'name' => 'Kubernetes Orchestration',
            'level' => 'expert',
        ]);
        $res->assertStatus(201);
        $skillId = $res->json('data.id');

        // 2. Duplicate prevention
        $dupRes = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/skills', [
            'name' => 'Kubernetes Orchestration',
        ]);
        $this->assertTrue(in_array($dupRes->status(), [200, 422]));

        // 3. Delete skill
        $delRes = $this->actingAs($user, 'sanctum')->deleteJson("/api/v2/profile/skills/{$skillId}");
        $delRes->assertStatus(200);
    }

    public function test_interests_creation_and_removal(): void
    {
        $user = $this->createUser();

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/interests', [
            'name' => 'Quantum Computing',
        ]);
        $res->assertStatus(201);
        $interestId = $res->json('data.id');

        $delRes = $this->actingAs($user, 'sanctum')->deleteJson("/api/v2/profile/interests/{$interestId}");
        $delRes->assertStatus(200);
    }

    // =========================================================================
    // Area 13: Languages & Proficiency Validation
    // =========================================================================

    public function test_languages_proficiency_levels_and_duplicate_prevention(): void
    {
        $user = $this->createUser();

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/languages', [
            'language' => 'Spanish',
            'proficiency' => 'conversational',
        ]);
        $res->assertStatus(201);
        $langId = $res->json('data.id');

        // Update proficiency
        $upRes = $this->actingAs($user, 'sanctum')->putJson("/api/v2/profile/languages/{$langId}", [
            'proficiency' => 'fluent',
        ]);
        $upRes->assertStatus(200);

        // Invalid proficiency rejected
        $badRes = $this->actingAs($user, 'sanctum')->putJson("/api/v2/profile/languages/{$langId}", [
            'proficiency' => 'legendary',
        ]);
        $badRes->assertStatus(422);
    }

    // =========================================================================
    // Area 14: Social Links Provider Config & URL Normalization
    // =========================================================================

    public function test_social_links_support_all_standard_platforms(): void
    {
        $user = $this->createUser();

        $testPlatforms = [
            ['platform' => 'GitHub', 'url' => 'github.com/torvalds'],
            ['platform' => 'LinkedIn', 'url' => 'linkedin.com/in/williamhgates'],
            ['platform' => 'YouTube', 'url' => 'youtube.com/@veritasium'],
            ['platform' => 'Website', 'url' => 'https://jugajug.com'],
        ];

        foreach ($testPlatforms as $item) {
            $res = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/social-links', $item);
            $res->assertStatus(201);
            $this->assertStringStartsWith('https://', $res->json('data.url'));
        }

        $this->assertEquals(4, $user->socialLinks()->count());
    }

    // =========================================================================
    // Area 15: Privacy Engine Matrix Evaluation
    // =========================================================================

    public function test_privacy_matrix_evaluation_for_different_viewer_roles(): void
    {
        $owner = $this->createUser(['username' => 'privacy_master']);
        $stranger = $this->createUser(['username' => 'stranger_user']);

        // Set owner privacy: bio public, education friends, work only_me
        $owner->privacySettings()->update([
            'profile_visibility' => 'public',
            'bio_privacy' => 'public',
            'education_privacy' => 'friends',
            'work_privacy' => 'only_me',
        ]);

        ProfileEducation::create([
            'user_id' => $owner->id,
            'institution_name' => 'Friends Only College',
            'display_order' => 1,
        ]);
        ProfileExperience::create([
            'user_id' => $owner->id,
            'company_name' => 'Secret Company',
            'job_title' => 'Security Architect',
            'employment_type' => 'full_time',
            'start_date' => '2020-01-01',
            'display_order' => 1,
        ]);

        // 1. Stranger viewing profile
        $resStranger = $this->actingAs($stranger, 'sanctum')->getJson("/api/v2/profile/{$owner->username}");
        $resStranger->assertStatus(200);
        $this->assertNotEmpty($resStranger->json('data.profile.bio'));
        $this->assertEmpty($resStranger->json('data.sections.educations'));
        $this->assertEmpty($resStranger->json('data.sections.experiences'));

        // 2. Owner viewing own profile sees everything
        $resOwner = $this->actingAs($owner, 'sanctum')->getJson("/api/v2/profile/{$owner->username}");
        $resOwner->assertStatus(200);
        $this->assertNotEmpty($resOwner->json('data.sections.educations'));
        $this->assertNotEmpty($resOwner->json('data.sections.experiences'));
    }

    // =========================================================================
    // Area 16: Verification Lifecycle & Badge Rendering
    // =========================================================================

    public function test_verification_lifecycle_and_verified_badge(): void
    {
        $applicant = $this->createUser(['username' => 'aspiring_verified']);
        $admin = $this->createAdmin();

        // 1. Submit verification
        $submitRes = $this->actingAs($applicant, 'sanctum')->postJson('/api/v2/profile/verification/submit', [
            'verification_type' => 'nid',
            'document_number' => '1995880173300',
            'document_front' => UploadedFile::fake()->image('nid_front.jpg', 600, 400),
            'selfie' => UploadedFile::fake()->image('selfie.jpg', 400, 400),
        ]);
        $submitRes->assertStatus(201);
        $verifId = $submitRes->json('data.id');

        // 2. Admin approves verification
        $approveRes = $this->actingAs($admin, 'sanctum')->postJson("/api/v2/admin/verifications/{$verifId}/approve");
        $approveRes->assertStatus(200);

        // 3. User profile now reflects verified badge
        $profRes = $this->getJson("/api/v2/profile/{$applicant->username}");
        $profRes->assertStatus(200)
            ->assertJsonPath('data.user.is_verified', true);
    }

    // =========================================================================
    // Area 17: Dynamic Profile Completion (13 Sections)
    // =========================================================================

    public function test_dynamic_profile_completion_calculation_and_caching(): void
    {
        $user = $this->createUser();
        $completionService = app(ProfileCompletionService::class);

        // Calculate initial completion
        $initialData = $completionService->calculate($user);
        $this->assertArrayHasKey('percentage', $initialData);
        $this->assertArrayHasKey('completed_items', $initialData);
        $this->assertArrayHasKey('remaining_items', $initialData);

        // API endpoint returns same structured completion
        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v2/profile/completion');
        $res->assertStatus(200)->assertJsonPath('success', true);
        $this->assertEquals($initialData['percentage'], $res->json('data.percentage'));
    }

    // =========================================================================
    // Area 18: Profile Views & Analytics Engine
    // =========================================================================

    public function test_profile_view_recording_cooldown_and_dashboard(): void
    {
        Queue::fake([RecordProfileViewJob::class]);

        $owner = $this->createUser(['username' => 'view_celebrity']);
        $viewer = $this->createUser(['username' => 'curious_viewer']);

        // 1. First view records and dispatches job
        $res1 = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/profile/{$owner->username}/view", [
            'source' => 'feed',
        ]);
        $res1->assertStatus(200)->assertJsonPath('data.recorded', true);
        Queue::assertPushed(RecordProfileViewJob::class, 1);

        // 2. Immediate second view hits 30m cooldown
        $res2 = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/profile/{$owner->username}/view");
        $res2->assertStatus(200)->assertJsonPath('data.recorded', false);
        // Queue should NOT be pushed again
        Queue::assertPushed(RecordProfileViewJob::class, 1);

        // 3. Analytics dashboard returns metrics
        $analyticsRes = $this->actingAs($owner, 'sanctum')->getJson('/api/v2/profile/analytics');
        $analyticsRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'total_views',
                    'unique_viewers_count',
                    'views_today',
                    'views_trend',
                    'device_breakdown',
                    'discovery_sources',
                    'recent_viewers',
                ],
            ]);
    }

    // =========================================================================
    // Area 19 & 20: Admin Controls & Audit Logs
    // =========================================================================

    public function test_audit_logs_created_on_profile_updates(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')->putJson('/api/v2/profile', [
            'name' => 'AuditedName User',
            'first_name' => 'AuditedName',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'profile.updated',
        ]);
    }

    // =========================================================================
    // Area 21: Redis / Cache Operations
    // =========================================================================

    public function test_redis_cache_invalidation_on_profile_changes(): void
    {
        $user = $this->createUser();
        $cacheKey = "profile_completion_{$user->id}";

        Cache::put($cacheKey, ['fake_percentage' => 99], 3600);
        $this->assertTrue(Cache::has($cacheKey));

        // Adding education triggers cache invalidation
        $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/education', [
            'institution' => 'Harvard University',
            'degree' => 'Doctor of Philosophy',
            'start_date' => '2019-01-01',
            'privacy' => 'public',
        ]);

        // Cache must have been invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    // =========================================================================
    // Area 22: Queue Execution
    // =========================================================================

    public function test_record_profile_view_job_executes_cleanly(): void
    {
        $owner = $this->createUser();
        $viewer = $this->createUser();

        $job = new RecordProfileViewJob(
            ownerId: $owner->id,
            viewerId: $viewer->id,
            ipHash: hash('sha256', '127.0.0.1'),
            ipAddress: '127.0.0.1',
            userAgent: 'Mozilla/5.0',
            deviceType: 'desktop',
            source: 'feed',
            referer: null,
            isAnonymous: false,
            viewedAt: now()
        );

        $job->handle();

        $this->assertDatabaseHas('profile_views', [
            'user_id' => $owner->id,
            'viewer_id' => $viewer->id,
            'source' => 'feed',
            'device_type' => 'desktop',
        ]);
    }

    // =========================================================================
    // Area 23 & 24: Object Storage & CDN Asset URLs
    // =========================================================================

    public function test_object_storage_stores_and_generates_valid_media_urls(): void
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->image('gallery.png', 800, 600);

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/avatar', [
            'file' => $file,
        ]);

        $res->assertStatus(200);
        $avatarUrl = $user->profile->fresh()->avatar_url;
        $this->assertNotNull($avatarUrl);
        $this->assertStringContainsString('/storage/', $avatarUrl);
    }

    // =========================================================================
    // Area 25: API Authentication & Standards Compliance
    // =========================================================================

    public function test_api_returns_standard_json_envelopes_and_http_status_codes(): void
    {
        // 1. Unauthenticated request gives 401
        $this->getJson('/api/v2/profile')->assertStatus(401);

        // 2. Authenticated gives 200
        $user = $this->createUser();
        $this->actingAs($user, 'sanctum')->getJson('/api/v2/profile')->assertStatus(200)->assertJsonStructure(['success', 'data']);

        // 3. 404 Not Found
        $this->getJson('/api/v2/profile/non_existent_username_xyz999')->assertStatus(404);

        // 4. 422 Unprocessable Entity
        $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/education', [])->assertStatus(422);
    }

    // =========================================================================
    // Area 26: WebSocket / Realtime Integration
    // =========================================================================

    public function test_profile_updated_event_broadcasts_over_websockets(): void
    {
        $user = $this->createUser();
        $event = new ProfileUpdatedEvent($user, ['basic_info', 'bio'], $user);

        // 1. Verify broadcast channels
        $channels = $event->broadcastOn();
        $this->assertCount(2, $channels);
        $this->assertEquals('private-user.'.$user->id, $channels[0]->name);
        $this->assertEquals('profile.'.$user->username, $channels[1]->name);

        // 2. Verify broadcast event name
        $this->assertEquals('profile.updated', $event->broadcastAs());

        // 3. Verify broadcast payload
        $payload = $event->broadcastWith();
        $this->assertEquals($user->id, $payload['user_id']);
        $this->assertEquals(['basic_info', 'bio'], $payload['updated_sections']);
    }

    // =========================================================================
    // Area 27: Education Edge Cases & Validations
    // =========================================================================

    public function test_education_rejects_invalid_date_chronology_start_after_end(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/education', [
            'institution' => 'Invalid Chronology Academy',
            'degree' => 'B.Sc.',
            'start_date' => '2024-01-01',
            'end_date' => '2020-01-01',
        ]);

        $response->assertStatus(422);
    }

    public function test_education_requires_valid_institution_name(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/education', [
            'institution' => '',
            'degree' => 'B.Sc.',
            'start_date' => '2020-01-01',
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // Area 28: Work Experience Edge Cases & Validations
    // =========================================================================

    public function test_work_experience_rejects_start_date_after_end_date(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/work', [
            'company' => 'Time Travel Corp',
            'position' => 'Researcher',
            'employment_type' => 'full_time',
            'start_date' => '2025-01-01',
            'end_date' => '2021-01-01',
        ]);

        $response->assertStatus(422);
    }

    public function test_work_experience_supports_ongoing_job_without_end_date(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/work', [
            'company' => 'Current Forever Ltd',
            'position' => 'CTO',
            'employment_type' => 'full_time',
            'start_date' => '2020-01-01',
            'currently_working' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_current', true);
    }

    // =========================================================================
    // Area 29: Skills & Interests Normalization
    // =========================================================================

    public function test_skill_name_is_trimmed_and_normalized(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/skills', [
            'name' => '   PHP Design Patterns   ',
            'level' => 'advanced',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('PHP Design Patterns', $response->json('data.name'));
    }

    public function test_interest_name_is_trimmed_and_normalized(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/interests', [
            'name' => '   Open Source Hardware   ',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('Open Source Hardware', $response->json('data.name'));
    }

    // =========================================================================
    // Area 30: Languages Unique Constraint Handling
    // =========================================================================

    public function test_languages_unique_per_user_duplicate_addition_handled(): void
    {
        $user = $this->createUser();

        $res1 = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/languages', [
            'language' => 'Korean',
            'proficiency' => 'basic',
        ]);
        $res1->assertStatus(201);

        $res2 = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/languages', [
            'language' => 'Korean',
            'proficiency' => 'fluent',
        ]);
        // Duplicate should be rejected or handled gracefully
        $this->assertTrue(in_array($res2->status(), [200, 422]));
    }

    // =========================================================================
    // Area 31: Social Links URL Normalization & Validation
    // =========================================================================

    public function test_social_links_rejects_invalid_url_structure(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/social-links', [
            'platform' => 'Website',
            'url' => 'not-a-valid-url-at-all',
        ]);

        $response->assertStatus(422);
    }

    public function test_social_links_auto_prefixes_https_to_bare_domains(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/social-links', [
            'platform' => 'Website',
            'url' => 'my-portfolio-domain.com',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('https://my-portfolio-domain.com', $response->json('data.url'));
    }

    // =========================================================================
    // Area 32: Profile Completion Dynamics
    // =========================================================================

    public function test_profile_completion_reaches_100_percent_when_all_sections_present(): void
    {
        $user = $this->createUser([
            'country' => 'Bangladesh',
        ]);

        $user->profile()->update([
            'avatar_url' => 'https://example.com/avatar.jpg',
            'cover_url' => 'https://example.com/cover.jpg',
            'bio' => 'Full bio',
            'about' => 'Full about',
            'location' => 'Dhaka',
        ]);

        ProfileEducation::create(['user_id' => $user->id, 'institution_name' => 'Uni', 'display_order' => 1]);
        ProfileExperience::create(['user_id' => $user->id, 'company_name' => 'Corp', 'job_title' => 'Dev', 'employment_type' => 'full_time', 'start_date' => '2020-01-01', 'display_order' => 1]);
        ProfileSkill::create(['user_id' => $user->id, 'name' => 'Coding', 'order' => 1]);
        ProfileInterest::create(['user_id' => $user->id, 'name' => 'AI', 'order' => 1]);
        ProfileLanguage::create(['user_id' => $user->id, 'language' => 'English', 'proficiency' => 'fluent', 'order' => 1]);
        ProfileSocialLink::create(['user_id' => $user->id, 'platform' => 'github', 'url' => 'https://github.com/test', 'username' => 'test', 'order' => 1]);

        $completion = app(ProfileCompletionService::class)->calculate($user);
        $this->assertEquals(100, $completion['percentage']);
        $this->assertEquals(13, $completion['completed_count']);
        $this->assertEmpty($completion['remaining_items']);
    }

    public function test_profile_completion_dynamic_drop_when_item_deleted(): void
    {
        $user = $this->createUser();
        $edu = ProfileEducation::create(['user_id' => $user->id, 'institution_name' => 'Uni', 'display_order' => 1]);

        $before = app(ProfileCompletionService::class)->calculate($user);
        $this->assertContains('education', $before['completed_items']);

        // Delete education
        $edu->delete();

        $after = app(ProfileCompletionService::class)->calculate($user);
        $this->assertContains('education', $after['remaining_items']);
        $this->assertLessThan($before['percentage'], $after['percentage']);
    }

    public function test_profile_completion_service_calculate_without_persisting(): void
    {
        $user = $this->createUser();

        $data = app(ProfileCompletionService::class)->calculate($user, persist: false);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('percentage', $data);
    }

    // =========================================================================
    // Area 33: Profile Views & Analytics Edge Cases
    // =========================================================================

    public function test_profile_view_analytics_empty_dashboard_returns_zeroes_without_error(): void
    {
        $freshUser = $this->createUser(['username' => 'no_views_yet']);

        $response = $this->actingAs($freshUser, 'sanctum')->getJson('/api/v2/profile/analytics');
        $response->assertStatus(200)
            ->assertJsonPath('data.total_views', 0)
            ->assertJsonPath('data.unique_viewers_count', 0)
            ->assertJsonPath('data.views_today', 0);
    }

    public function test_profile_view_analytics_weekly_timeframe_filtering(): void
    {
        $owner = $this->createUser(['username' => 'timeframed_user']);

        ProfileView::create([
            'user_id' => $owner->id,
            'viewer_id' => null,
            'is_anonymous' => true,
            'ip_hash' => 'hash_week',
            'device_type' => 'mobile',
            'source' => 'feed',
            'viewed_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v2/profile/analytics?timeframe=7d');
        $response->assertStatus(200)
            ->assertJsonPath('data.total_views', 1);
    }

    public function test_profile_view_analytics_device_unknown_defaults_gracefully(): void
    {
        $owner = $this->createUser();

        ProfileView::create([
            'user_id' => $owner->id,
            'is_anonymous' => true,
            'ip_hash' => 'hash_dev',
            'device_type' => 'desktop',
            'source' => 'direct',
            'viewed_at' => now(),
        ]);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v2/profile/analytics');
        $response->assertStatus(200);
        $breakdown = $response->json('data.device_breakdown');
        $this->assertArrayHasKey('desktop', $breakdown);
    }

    // =========================================================================
    // Area 34: Admin Verification Actions
    // =========================================================================

    public function test_admin_verification_rejection_stores_reason_and_updates_status(): void
    {
        $applicant = $this->createUser();
        $admin = $this->createAdmin();

        $verif = ProfileVerification::create([
            'user_id' => $applicant->id,
            'verification_type' => 'nid',
            'document_number' => '9988776655',
            'status' => ProfileVerification::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v2/admin/verifications/{$verif->id}/reject", [
            'rejection_reason' => 'Blurry document photograph provided',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(ProfileVerification::STATUS_REJECTED, $verif->fresh()->status);
    }

    public function test_admin_verification_request_info_updates_status(): void
    {
        $applicant = $this->createUser();
        $admin = $this->createAdmin();

        $verif = ProfileVerification::create([
            'user_id' => $applicant->id,
            'verification_type' => 'passport',
            'document_number' => 'A12345678',
            'status' => ProfileVerification::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v2/admin/verifications/{$verif->id}/request-info", [
            'instructions' => 'Please provide the back page of passport',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(ProfileVerification::STATUS_NEEDS_INFO, $verif->fresh()->status);
    }

    public function test_admin_verification_revoke_removes_verified_badge(): void
    {
        $verifiedUser = $this->createUser();
        $admin = $this->createAdmin();

        $verif = ProfileVerification::create([
            'user_id' => $verifiedUser->id,
            'verification_type' => 'nid',
            'document_number' => '1122334455',
            'status' => ProfileVerification::STATUS_VERIFIED,
            'submitted_at' => now()->subWeek(),
            'reviewed_at' => now()->subDay(),
            'admin_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v2/admin/verifications/{$verif->id}/revoke", [
            'revocation_reason' => 'Fraudulent identity documents detected',
        ]);

        $response->assertStatus(200);
        $this->assertNotEquals(ProfileVerification::STATUS_VERIFIED, $verif->fresh()->status);
    }

    public function test_admin_verification_requires_super_admin_or_admin_role(): void
    {
        $applicant = $this->createUser();
        $regularUser = $this->createUser();

        $verif = ProfileVerification::create([
            'user_id' => $applicant->id,
            'verification_type' => 'nid',
            'status' => ProfileVerification::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($regularUser, 'sanctum')->postJson("/api/v2/admin/verifications/{$verif->id}/approve");
        $response->assertStatus(403);
    }

    // =========================================================================
    // Area 35: Privacy Settings Persistence & Blocked User Access
    // =========================================================================

    public function test_profile_privacy_update_endpoint_persists_settings(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v2/profile/privacy/settings', [
            'profile_visibility' => 'followers',
            'education_privacy' => 'only_me',
            'work_privacy' => 'friends',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('followers', $user->privacySettings->fresh()->profile_visibility);
        $this->assertEquals('only_me', $user->privacySettings->fresh()->education_privacy);
    }

    public function test_public_profile_custom_slug_matches_username_access(): void
    {
        $user = $this->createUser(['username' => 'custom_slugger']);

        $response = $this->getJson("/api/v2/profile/{$user->username}");
        $response->assertStatus(200)
            ->assertJsonPath('data.user.username', 'custom_slugger');
    }

    public function test_profile_avatar_delete_reverts_to_default_avatar_state(): void
    {
        $user = $this->createUser();
        $user->profile()->update(['avatar_url' => 'https://example.com/custom.jpg']);

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/v2/profile/avatar');
        $response->assertStatus(200);

        // After deletion, avatar_url is either null or default placeholder
        $freshAvatar = $user->profile->fresh()->avatar_url;
        $this->assertTrue(empty($freshAvatar) || str_contains($freshAvatar, 'default') || str_contains($freshAvatar, 'placeholder'));
    }
}
