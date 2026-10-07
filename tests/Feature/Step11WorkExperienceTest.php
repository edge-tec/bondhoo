<?php

namespace Tests\Feature;

use App\Events\ProfileUpdatedEvent;
use App\Models\Friendship;
use App\Models\ProfileExperience;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Step11WorkExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Test user can add multiple work experience records with requested fields.
     */
    public function test_user_can_add_multiple_work_experience_records_with_all_fields(): void
    {
        Event::fake([ProfileUpdatedEvent::class]);

        $user = User::factory()->create(['username' => 'software_lead']);

        // 1. First record: Past role
        $payload1 = [
            'company' => 'Google',
            'position' => 'Senior Software Engineer',
            'employment_type' => 'Full Time',
            'location' => 'Mountain View, CA',
            'start_date' => '2019-06-01',
            'end_date' => '2022-12-31',
            'currently_working' => false,
            'description' => 'Architected scalable microservices and distributed database systems.',
            'privacy' => 'public',
        ];

        $response1 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/work', $payload1);

        $response1->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company', 'Google')
            ->assertJsonPath('data.company_name', 'Google')
            ->assertJsonPath('data.position', 'Senior Software Engineer')
            ->assertJsonPath('data.job_title', 'Senior Software Engineer')
            ->assertJsonPath('data.employment_type', 'full_time')
            ->assertJsonPath('data.location', 'Mountain View, CA')
            ->assertJsonPath('data.is_current', false);

        // 2. Second record: Current role
        $payload2 = [
            'company_name' => 'GitHub',
            'job_title' => 'Principal Systems Architect',
            'employment_type' => 'full_time',
            'location' => 'San Francisco, CA',
            'start_date' => '2023-01-15',
            'currently_working' => true,
            'description' => 'Leading platform reliability and global infrastructure.',
            'privacy' => 'friends',
        ];

        $response2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/work', $payload2);

        $response2->assertStatus(201)
            ->assertJsonPath('data.company', 'GitHub')
            ->assertJsonPath('data.is_current', true)
            ->assertJsonPath('data.currently_working', true)
            ->assertJsonPath('data.end_date', null);

        $this->assertEquals(2, ProfileExperience::where('user_id', $user->id)->count());

        Event::assertDispatched(ProfileUpdatedEvent::class, function ($event) use ($user) {
            return $event->user->id === $user->id && in_array('experiences', $event->updatedSections, true);
        });
    }

    /**
     * Test user can view their own work experiences list.
     */
    public function test_user_can_view_own_work_experiences(): void
    {
        $user = User::factory()->create();

        ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => 'Microsoft',
            'job_title' => 'Software Engineer',
            'employment_type' => 'full_time',
            'start_date' => '2018-05-01',
            'end_date' => '2021-04-30',
            'is_current' => false,
            'privacy' => 'only_me',
            'display_order' => 1,
        ]);

        ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => 'Amazon',
            'job_title' => 'Senior Solutions Architect',
            'employment_type' => 'full_time',
            'start_date' => '2021-05-01',
            'is_current' => true,
            'privacy' => 'public',
            'display_order' => 2,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile/work');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.company', 'Microsoft')
            ->assertJsonPath('data.1.company', 'Amazon');
    }

    /**
     * Test user can edit their own work experience record.
     */
    public function test_user_can_edit_own_work_experience_record(): void
    {
        $user = User::factory()->create();
        $experience = ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => 'Meta',
            'job_title' => 'Frontend Engineer',
            'employment_type' => 'full_time',
            'start_date' => '2020-01-01',
            'end_date' => '2022-01-01',
            'is_current' => false,
            'location' => 'Menlo Park, CA',
            'privacy' => 'public',
        ]);

        $updatePayload = [
            'position' => 'Staff Frontend Engineer',
            'employment_type' => 'Contract',
            'currently_working' => true,
            'location' => 'Remote, USA',
            'privacy' => 'followers',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v2/profile/work/{$experience->id}", $updatePayload);

        $response->assertOk()
            ->assertJsonPath('data.position', 'Staff Frontend Engineer')
            ->assertJsonPath('data.job_title', 'Staff Frontend Engineer')
            ->assertJsonPath('data.employment_type', 'contract')
            ->assertJsonPath('data.is_current', true)
            ->assertJsonPath('data.end_date', null)
            ->assertJsonPath('data.privacy', 'followers');

        $this->assertDatabaseHas('profile_experiences', [
            'id' => $experience->id,
            'job_title' => 'Staff Frontend Engineer',
            'employment_type' => 'contract',
            'is_current' => 1,
            'privacy' => 'followers',
        ]);
    }

    /**
     * Test user can delete their own work experience record.
     */
    public function test_user_can_delete_own_work_experience_record(): void
    {
        $user = User::factory()->create();
        $experience = ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => 'Apple',
            'job_title' => 'iOS Developer',
            'start_date' => '2019-01-01',
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v2/profile/work/{$experience->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('profile_experiences', [
            'id' => $experience->id,
        ]);
    }

    /**
     * Test cross-user authorization: user cannot modify another user's work experience.
     */
    public function test_user_cannot_modify_another_users_work_experience(): void
    {
        $owner = User::factory()->create(['username' => 'career_owner']);
        $attacker = User::factory()->create(['username' => 'career_attacker']);

        $experience = ProfileExperience::create([
            'user_id' => $owner->id,
            'company_name' => 'Netflix',
            'job_title' => 'Cloud Engineer',
            'start_date' => '2021-01-01',
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->putJson("/api/v2/profile/work/{$experience->id}", [
                'job_title' => 'Hacked Position',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('profile_experiences', [
            'id' => $experience->id,
            'job_title' => 'Hacked Position',
        ]);
    }

    /**
     * Test cross-user authorization: user cannot delete another user's work experience.
     */
    public function test_user_cannot_delete_another_users_work_experience(): void
    {
        $owner = User::factory()->create(['username' => 'career_owner_2']);
        $attacker = User::factory()->create(['username' => 'career_attacker_2']);

        $experience = ProfileExperience::create([
            'user_id' => $owner->id,
            'company_name' => 'Oracle',
            'job_title' => 'Database Administrator',
            'start_date' => '2020-01-01',
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->deleteJson("/api/v2/profile/work/{$experience->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('profile_experiences', [
            'id' => $experience->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test all requested employment types are supported and normalized.
     */
    public function test_all_employment_types_are_accepted_and_normalized(): void
    {
        $user = User::factory()->create();

        $typesMap = [
            'Full Time' => 'full_time',
            'Part Time' => 'part_time',
            'Self Employed' => 'self_employed',
            'Freelance' => 'freelance',
            'Contract' => 'contract',
            'Internship' => 'internship',
            'part-time' => 'part_time',
            'self-employed' => 'self_employed',
        ];

        foreach ($typesMap as $input => $expected) {
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/v2/profile/work', [
                    'company' => 'IBM',
                    'position' => 'Developer',
                    'employment_type' => $input,
                    'start_date' => '2022-01-01',
                ]);

            $response->assertStatus(201)
                ->assertJsonPath('data.employment_type', $expected);
        }

        // Invalid type should be rejected with 422
        $invalidResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/work', [
                'company' => 'IBM',
                'position' => 'Developer',
                'employment_type' => 'Casual Unofficial',
            ]);

        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors(['employment_type']);
    }

    /**
     * Test work experience privacy levels (public, friends, followers, only_me).
     */
    public function test_work_experience_privacy_levels_are_strictly_enforced(): void
    {
        $targetUser = User::factory()->create(['username' => 'privacy_profile']);
        $friend = User::factory()->create(['username' => 'verified_friend']);
        $follower = User::factory()->create(['username' => 'subscribed_follower']);
        $stranger = User::factory()->create(['username' => 'random_stranger']);

        // Set friendship
        Friendship::create([
            'user_id' => $targetUser->id,
            'friend_id' => $friend->id,
            'status' => 'accepted',
        ]);

        // Set follower
        $targetUser->followers()->attach($follower->id);

        // 1. Public item
        ProfileExperience::create([
            'user_id' => $targetUser->id,
            'company_name' => 'Intel',
            'job_title' => 'Hardware Specialist',
            'privacy' => 'public',
            'display_order' => 1,
        ]);

        // 2. Followers item
        ProfileExperience::create([
            'user_id' => $targetUser->id,
            'company_name' => 'Cisco',
            'job_title' => 'Network Engineer',
            'privacy' => 'followers',
            'display_order' => 2,
        ]);

        // 3. Friends item
        ProfileExperience::create([
            'user_id' => $targetUser->id,
            'company_name' => 'Adobe',
            'job_title' => 'UI Designer',
            'privacy' => 'friends',
            'display_order' => 3,
        ]);

        // 4. Only Me item
        ProfileExperience::create([
            'user_id' => $targetUser->id,
            'company_name' => 'Tesla',
            'job_title' => 'Autopilot Engineer',
            'privacy' => 'only_me',
            'display_order' => 4,
        ]);

        // Case A: Anonymous public view
        $publicRes = $this->getJson("/api/v2/profile/{$targetUser->username}/work");
        $publicRes->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals('Intel', $publicRes->json('data.0.company'));

        // Case B: Stranger view
        $strangerRes = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v2/profile/{$targetUser->username}/work");
        $strangerRes->assertOk()->assertJsonCount(1, 'data');

        // Case C: Follower view (should see public + followers = 2)
        $followerRes = $this->actingAs($follower, 'sanctum')
            ->getJson("/api/v2/profile/{$targetUser->username}/work");
        $followerRes->assertOk()->assertJsonCount(2, 'data');

        // Case D: Friend view (should see public + followers + friends = 3)
        $friendRes = $this->actingAs($friend, 'sanctum')
            ->getJson("/api/v2/profile/{$targetUser->username}/work");
        $friendRes->assertOk()->assertJsonCount(3, 'data');

        // Case E: Owner view (should see all 4)
        $ownerRes = $this->actingAs($targetUser, 'sanctum')
            ->getJson('/api/v2/profile/work');
        $ownerRes->assertOk()->assertJsonCount(4, 'data');
    }

    /**
     * Test work experience reordering works.
     */
    public function test_work_experience_reordering_works(): void
    {
        $user = User::factory()->create();

        $exp1 = ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => 'Company One',
            'job_title' => 'Role One',
            'display_order' => 1,
        ]);
        $exp2 = ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => 'Company Two',
            'job_title' => 'Role Two',
            'display_order' => 2,
        ]);
        $exp3 = ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => 'Company Three',
            'job_title' => 'Role Three',
            'display_order' => 3,
        ]);

        $payload = [
            'ids' => [$exp3->id, $exp1->id, $exp2->id],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/work/reorder', $payload);

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $exp3->id)
            ->assertJsonPath('data.1.id', $exp1->id)
            ->assertJsonPath('data.2.id', $exp2->id);

        $this->assertEquals(1, $exp3->fresh()->display_order);
        $this->assertEquals(2, $exp1->fresh()->display_order);
        $this->assertEquals(3, $exp2->fresh()->display_order);
    }

    /**
     * Test validation rules for dates and required fields.
     */
    public function test_validation_rules_for_dates_and_company_position(): void
    {
        $user = User::factory()->create();

        // 1. Missing company and position
        $res1 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/work', []);
        $res1->assertStatus(422)
            ->assertJsonValidationErrors(['company_name', 'job_title']);

        // 2. Future start_date
        $res2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/work', [
                'company' => 'Salesforce',
                'position' => 'Developer',
                'start_date' => now()->addMonths(2)->format('Y-m-d'),
            ]);
        $res2->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);

        // 3. end_date before start_date when not currently working
        $res3 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/work', [
                'company' => 'Salesforce',
                'position' => 'Developer',
                'start_date' => '2022-01-01',
                'end_date' => '2021-01-01',
                'currently_working' => false,
            ]);
        $res3->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /**
     * Test audit logging records all work experience actions.
     */
    public function test_audit_logging_records_work_experience_events(): void
    {
        $user = User::factory()->create();

        // Create
        $createRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/work', [
                'company' => 'Spotify',
                'position' => 'Backend Engineer',
                'start_date' => '2021-01-01',
            ]);
        $createRes->assertStatus(201);
        $workId = $createRes->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'WORK_EXPERIENCE_CREATED',
            'entity_type' => ProfileExperience::class,
            'entity_id' => $workId,
        ]);

        // Update
        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v2/profile/work/{$workId}", [
                'position' => 'Lead Backend Engineer',
            ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'WORK_EXPERIENCE_UPDATED',
            'entity_type' => ProfileExperience::class,
            'entity_id' => $workId,
        ]);

        // Reorder
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/work/reorder', [
                'ids' => [$workId],
            ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'WORK_EXPERIENCE_REORDERED',
            'entity_type' => ProfileExperience::class,
        ]);

        // Delete
        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v2/profile/work/{$workId}")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'WORK_EXPERIENCE_DELETED',
            'entity_type' => ProfileExperience::class,
            'entity_id' => $workId,
        ]);
    }
}
