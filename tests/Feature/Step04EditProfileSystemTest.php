<?php

namespace Tests\Feature;

use App\Events\ProfileUpdatedEvent;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Step04EditProfileSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_all_ten_sections_of_own_profile(): void
    {
        Event::fake([ProfileUpdatedEvent::class]);

        $user = User::factory()->create([
            'name' => 'Original Name',
            'username' => 'originaluser',
            'status' => 'active',
        ]);

        $payload = [
            // Section 1: Basic Information
            'name' => 'Updated Name',
            'display_name' => 'Super User',
            'slug' => 'updated-custom-slug',
            'gender' => 'male',
            'birth_date' => '1995-05-15',
            'relationship_status' => 'single',

            // Section 2: About
            'bio' => 'Passionate full stack software engineer building high-scale social platforms.',
            'about' => 'Extensive background in distributed systems, PHP, Laravel, Redis, and high concurrency architectures.',

            // Section 3: Contact Information
            'website' => 'https://jugajug.social',

            // Section 4: Location
            'city' => 'Dhaka',
            'hometown' => 'Chittagong',

            // Section 5: Education
            'educations' => [
                [
                    'institution_name' => 'University of Dhaka',
                    'degree' => 'B.Sc. in Computer Science & Engineering',
                    'field_of_study' => 'Computer Science',
                    'start_date' => '2014-01-01',
                    'end_date' => '2018-12-31',
                    'is_current' => false,
                    'grade' => '3.95/4.00',
                    'privacy' => 'public',
                ],
            ],

            // Section 6: Work Experience
            'experiences' => [
                [
                    'company_name' => 'Jugajug Technologies',
                    'job_title' => 'Principal Software Architect',
                    'employment_type' => 'Full-time',
                    'location' => 'Dhaka, Bangladesh',
                    'is_remote' => true,
                    'start_date' => '2020-01-01',
                    'is_current' => true,
                    'description' => 'Architecting scalable backend microservices and real-time feeds.',
                    'privacy' => 'public',
                ],
            ],

            // Section 7: Skills
            'skills' => [
                ['name' => 'Laravel', 'level' => 'expert'],
                ['name' => 'Redis', 'level' => 'expert'],
                ['name' => 'Vue.js', 'level' => 'intermediate'],
            ],

            // Section 8: Interests
            'interests' => [
                ['name' => 'Artificial Intelligence', 'category' => 'Technology'],
                ['name' => 'Cricket', 'category' => 'Sports'],
            ],

            // Section 9: Languages
            'languages' => [
                ['language' => 'Bengali', 'proficiency' => 'native'],
                ['language' => 'English', 'proficiency' => 'fluent'],
            ],

            // Section 10: Social Links
            'social_links' => [
                ['platform' => 'github', 'url' => 'https://github.com/jugajug-dev', 'is_visible' => true],
                ['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/jugajug-dev', 'is_visible' => true],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'প্রোফাইল সফলভাবে আপডেট করা হয়েছে।',
            ])
            ->assertJsonPath('data.user.username', 'originaluser')
            ->assertJsonPath('data.user.name', 'Updated Name')
            ->assertJsonPath('data.sections.skills.0.name', 'Laravel')
            ->assertJsonPath('data.sections.languages.0.language', 'Bengali');

        // Verify Core User & Profile database updates
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'display_name' => 'Super User',
            'slug' => 'updated-custom-slug',
            'gender' => 'male',
            'birth_date' => '1995-05-15 00:00:00',
            'relationship_status' => 'single',
            'bio' => 'Passionate full stack software engineer building high-scale social platforms.',
            'website' => 'https://jugajug.social',
            'location' => 'Dhaka',
            'hometown' => 'Chittagong',
        ]);

        // Verify Relational Tables
        $this->assertDatabaseHas('profile_educations', [
            'user_id' => $user->id,
            'institution_name' => 'University of Dhaka',
            'degree' => 'B.Sc. in Computer Science & Engineering',
        ]);

        $this->assertDatabaseHas('profile_experiences', [
            'user_id' => $user->id,
            'company_name' => 'Jugajug Technologies',
            'job_title' => 'Principal Software Architect',
        ]);

        $this->assertDatabaseHas('profile_skills', [
            'user_id' => $user->id,
            'name' => 'Laravel',
            'level' => 'expert',
        ]);

        $this->assertDatabaseHas('profile_interests', [
            'user_id' => $user->id,
            'name' => 'Artificial Intelligence',
            'category' => 'Technology',
        ]);

        $this->assertDatabaseHas('profile_languages', [
            'user_id' => $user->id,
            'language' => 'Bengali',
            'proficiency' => 'native',
        ]);

        $this->assertDatabaseHas('profile_social_links', [
            'user_id' => $user->id,
            'platform' => 'github',
            'url' => 'https://github.com/jugajug-dev',
        ]);

        // Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'profile.updated',
            'entity_type' => UserProfile::class,
        ]);

        // Verify Event Dispatched
        Event::assertDispatched(ProfileUpdatedEvent::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function test_user_cannot_update_another_users_profile(): void
    {
        $alice = User::factory()->create(['username' => 'alice']);
        $bob = User::factory()->create(['username' => 'bob']);

        $response = $this->actingAs($alice, 'sanctum')
            ->putJson("/api/v2/profile/{$bob->id}", [
                'name' => 'Hacked Name',
                'bio' => 'Hacked Bio',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('users', [
            'id' => $bob->id,
            'name' => 'Hacked Name',
        ]);
    }

    public function test_admin_with_manage_users_permission_can_update_any_profile(): void
    {
        Event::fake([ProfileUpdatedEvent::class]);

        $admin = User::factory()->create(['username' => 'superadmin']);
        $superAdminRole = Role::firstOrCreate(['name' => 'SUPER_ADMIN', 'guard_name' => 'sanctum']);
        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $targetUser = User::factory()->create(['username' => 'targetuser', 'name' => 'Old Target Name']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v2/profile/{$targetUser->id}", [
                'name' => 'Admin Updated Name',
                'bio' => 'Updated by Administrator',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'প্রোফাইল সফলভাবে আপডেট করা হয়েছে।',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Admin Updated Name',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $targetUser->id,
            'bio' => 'Updated by Administrator',
        ]);

        // Audit log records admin as the actor
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'profile.updated',
        ]);
    }

    public function test_profile_update_validates_inputs_strictly(): void
    {
        $user = User::factory()->create(['username' => 'validateme']);
        $otherUser = User::factory()->create(['username' => 'takenuser']);
        $otherUser->profile()->create([
            'slug' => 'taken-slug',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile', [
                'website' => 'not-a-valid-url',
                'gender' => 'invalid_gender_value',
                'relationship_status' => 'invalid_status',
                'birth_date' => '2099-01-01', // Future date
                'slug' => 'taken-slug', // Duplicate slug
                'social_links' => [
                    ['platform' => 'github', 'url' => 'invalid-link'],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'website',
                'gender',
                'relationship_status',
                'birth_date',
                'slug',
                'social_links.0.url',
            ]);
    }

    public function test_profile_update_clears_redis_cache(): void
    {
        $user = User::factory()->create(['username' => 'cacheduser']);
        $user->profile()->create(['bio' => 'Initial bio']);

        $cacheKey = "user_profile:{$user->username}";
        Cache::put($cacheKey, ['dummy' => 'cached_data'], 3600);
        $this->assertTrue(Cache::has($cacheKey));

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile', [
                'bio' => 'Fresh newly updated bio',
            ])
            ->assertStatus(200);

        // Cache must have been invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }
}
