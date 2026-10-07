<?php

namespace Tests\Feature;

use App\Events\ProfileUpdatedEvent;
use App\Models\Friendship;
use App\Models\ProfileEducation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Step10EducationProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Test user can add multiple education records with all requested fields.
     */
    public function test_user_can_add_multiple_education_records_with_all_fields(): void
    {
        Event::fake([ProfileUpdatedEvent::class]);

        $user = User::factory()->create(['username' => 'academic_dev']);

        // 1. First record: Completed Bachelor's
        $payload1 = [
            'institution' => 'University of Dhaka',
            'degree' => 'Bachelor of Science',
            'field_of_study' => 'Computer Science & Engineering',
            'start_date' => '2016-01-10',
            'end_date' => '2020-01-15',
            'currently_studying' => false,
            'description' => 'Graduated with Distinction. Focused on Distributed Systems.',
            'location' => 'Dhaka, Bangladesh',
            'privacy' => 'public',
        ];

        $response1 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/education', $payload1);

        $response1->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.institution', 'University of Dhaka')
            ->assertJsonPath('data.degree', 'Bachelor of Science')
            ->assertJsonPath('data.field_of_study', 'Computer Science & Engineering')
            ->assertJsonPath('data.location', 'Dhaka, Bangladesh')
            ->assertJsonPath('data.is_current', false);

        // 2. Second record: Ongoing Master's
        $payload2 = [
            'institution_name' => 'BUET',
            'degree' => 'Master of Science',
            'field_of_study' => 'Software Engineering',
            'start_date' => '2021-03-01',
            'is_current' => true,
            'description' => 'Researching Social Network Cryptography.',
            'location' => 'Dhaka, Bangladesh',
            'privacy' => 'friends',
        ];

        $response2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/education', $payload2);

        $response2->assertStatus(201)
            ->assertJsonPath('data.institution_name', 'BUET')
            ->assertJsonPath('data.is_current', true)
            ->assertJsonPath('data.currently_studying', true)
            ->assertJsonPath('data.end_date', null);

        $this->assertEquals(2, ProfileEducation::where('user_id', $user->id)->count());

        Event::assertDispatched(ProfileUpdatedEvent::class, function ($event) use ($user) {
            return $event->user->id === $user->id && in_array('educations', $event->updatedSections, true);
        });
    }

    /**
     * Test user can edit their own education record.
     */
    public function test_user_can_edit_own_education_record(): void
    {
        $user = User::factory()->create();
        $education = ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'Old College',
            'degree' => 'B.Sc.',
            'field_of_study' => 'Physics',
            'start_date' => '2015-01-01',
            'end_date' => '2019-01-01',
            'is_current' => false,
            'location' => 'Old Location',
            'privacy' => 'public',
        ]);

        $updatePayload = [
            'institution_name' => 'Imperial College London',
            'degree' => 'M.Sc. Advanced Computing',
            'location' => 'London, United Kingdom',
            'privacy' => 'followers',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v2/profile/education/{$education->id}", $updatePayload);

        $response->assertOk()
            ->assertJsonPath('data.institution', 'Imperial College London')
            ->assertJsonPath('data.degree', 'M.Sc. Advanced Computing')
            ->assertJsonPath('data.location', 'London, United Kingdom')
            ->assertJsonPath('data.privacy', 'followers');

        $this->assertDatabaseHas('profile_educations', [
            'id' => $education->id,
            'institution_name' => 'Imperial College London',
            'location' => 'London, United Kingdom',
        ]);
    }

    /**
     * Test user can delete their own education record.
     */
    public function test_user_can_delete_own_education_record(): void
    {
        $user = User::factory()->create();
        $education = ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'To Be Deleted University',
            'start_date' => '2018-01-01',
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v2/profile/education/{$education->id}");

        $response->assertOk();

        $this->assertSoftDeleted('profile_educations', [
            'id' => $education->id,
        ]);
    }

    /**
     * Test a user cannot modify or delete another user's education record (403 Forbidden).
     */
    public function test_user_cannot_modify_or_delete_another_users_education(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $education = ProfileEducation::create([
            'user_id' => $owner->id,
            'institution_name' => 'Harvard University',
            'degree' => 'Master of Public Policy',
            'start_date' => '2018-01-01',
            'privacy' => 'public',
        ]);

        // 1. Attempt Update
        $this->actingAs($attacker, 'sanctum')
            ->putJson("/api/v2/profile/education/{$education->id}", [
                'institution_name' => 'Hacked University',
            ])
            ->assertStatus(403);

        $this->assertDatabaseHas('profile_educations', [
            'id' => $education->id,
            'institution_name' => 'Harvard University',
        ]);

        // 2. Attempt Delete
        $this->actingAs($attacker, 'sanctum')
            ->deleteJson("/api/v2/profile/education/{$education->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('profile_educations', [
            'id' => $education->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test reordering of education items.
     */
    public function test_user_can_reorder_education_records(): void
    {
        $user = User::factory()->create();

        $edu1 = ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'School A',
            'display_order' => 1,
            'privacy' => 'public',
        ]);

        $edu2 = ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'School B',
            'display_order' => 2,
            'privacy' => 'public',
        ]);

        $edu3 = ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'School C',
            'display_order' => 3,
            'privacy' => 'public',
        ]);

        // Reorder so that School C is 1st, School A is 2nd, School B is 3rd
        $reorderPayload = [
            'items' => [
                ['id' => $edu3->id, 'display_order' => 1],
                ['id' => $edu1->id, 'display_order' => 2],
                ['id' => $edu2->id, 'display_order' => 3],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/education/reorder', $reorderPayload);

        $response->assertOk();

        $this->assertEquals(1, $edu3->fresh()->display_order);
        $this->assertEquals(2, $edu1->fresh()->display_order);
        $this->assertEquals(3, $edu2->fresh()->display_order);

        // Fetching educations should return in new display_order sequence
        $listResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile/education');

        $listResponse->assertOk()
            ->assertJsonPath('data.0.id', $edu3->id)
            ->assertJsonPath('data.1.id', $edu1->id)
            ->assertJsonPath('data.2.id', $edu2->id);
    }

    /**
     * Test granular privacy per education item (public, friends, followers, only_me).
     */
    public function test_privacy_per_education_item_enforcement(): void
    {
        $owner = User::factory()->create(['username' => 'privacy_owner']);
        $friend = User::factory()->create();
        $stranger = User::factory()->create();

        // Establish friendship
        Friendship::create([
            'user_id' => $owner->id,
            'friend_id' => $friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Create 3 items with different privacy settings
        $publicEdu = ProfileEducation::create([
            'user_id' => $owner->id,
            'institution_name' => 'Public High School',
            'privacy' => 'public',
        ]);

        $friendsEdu = ProfileEducation::create([
            'user_id' => $owner->id,
            'institution_name' => 'Friends Only College',
            'privacy' => 'friends',
        ]);

        $onlyMeEdu = ProfileEducation::create([
            'user_id' => $owner->id,
            'institution_name' => 'Top Secret Academy',
            'privacy' => 'only_me',
        ]);

        // 1. Owner sees all 3
        $ownerRes = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v2/profile/{$owner->username}/education");
        $ownerRes->assertOk();
        $this->assertCount(3, $ownerRes->json('data'));

        // 2. Friend sees Public and Friends Only (2 items), but NOT only_me
        $friendRes = $this->actingAs($friend, 'sanctum')
            ->getJson("/api/v2/profile/{$owner->username}/education");
        $friendRes->assertOk();
        $friendData = $friendRes->json('data');
        $this->assertCount(2, $friendData);
        $institutions = array_column($friendData, 'institution');
        $this->assertContains('Public High School', $institutions);
        $this->assertContains('Friends Only College', $institutions);
        $this->assertNotContains('Top Secret Academy', $institutions);

        // 3. Stranger / Public sees ONLY Public (1 item)
        $strangerRes = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v2/profile/{$owner->username}/education");
        $strangerRes->assertOk();
        $strangerData = $strangerRes->json('data');
        $this->assertCount(1, $strangerData);
        $this->assertEquals('Public High School', $strangerData[0]['institution']);
    }

    /**
     * Test validation rules (required institution, start_date <= end_date).
     */
    public function test_validation_rules_for_education(): void
    {
        $user = User::factory()->create();

        // 1. Missing institution
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/education', [
                'degree' => 'B.A.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['institution_name']);

        // 2. End date before start date when not currently studying
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/education', [
                'institution' => 'Invalid Date Univ',
                'start_date' => '2022-01-01',
                'end_date' => '2020-01-01',
                'currently_studying' => false,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);

        // 3. Start date in future
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/education', [
                'institution' => 'Future Univ',
                'start_date' => '2099-01-01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    /**
     * Test audit logging records education events.
     */
    public function test_audit_logging_records_education_events(): void
    {
        $user = User::factory()->create();

        // 1. Create
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/education', [
                'institution' => 'Audit Test University',
                'degree' => 'B.Sc.',
            ]);

        $eduId = $response->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'EDUCATION_CREATED',
            'entity_type' => ProfileEducation::class,
            'entity_id' => $eduId,
        ]);

        // 2. Update
        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v2/profile/education/{$eduId}", [
                'degree' => 'M.Sc.',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'EDUCATION_UPDATED',
            'entity_type' => ProfileEducation::class,
            'entity_id' => $eduId,
        ]);

        // 3. Delete
        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v2/profile/education/{$eduId}");

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'EDUCATION_DELETED',
            'entity_type' => ProfileEducation::class,
            'entity_id' => $eduId,
        ]);
    }

    /**
     * Test cache is invalidated when education records change.
     */
    public function test_cache_invalidation_after_education_changes(): void
    {
        $user = User::factory()->create(['username' => 'cache_student']);
        $username = strtolower($user->username);

        Cache::put("profile:public:{$username}", ['data' => 'cached'], 3600);

        $this->assertTrue(Cache::has("profile:public:{$username}"));

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/education', [
                'institution' => 'Stanford University',
            ])
            ->assertStatus(201);

        $this->assertFalse(Cache::has("profile:public:{$username}"));
    }

    /**
     * Test XSS payloads in institution and description are sanitized.
     */
    public function test_xss_payload_in_education_fields_is_sanitized(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/education', [
                'institution' => 'MIT <script>alert(1)</script>',
                'description' => '<p>Good courses</p><script>evil()</script><img src=x onerror=alert(2)>',
            ]);

        $response->assertStatus(201);

        $edu = ProfileEducation::where('user_id', $user->id)->first();

        $this->assertStringNotContainsString('<script>', $edu->institution_name);
        $this->assertStringNotContainsString('alert', $edu->institution_name);
        $this->assertStringNotContainsString('<script>', $edu->description);
        $this->assertStringNotContainsString('onerror', $edu->description);
    }
}
