<?php

namespace Tests\Feature;

use App\Models\PrivacySetting;
use App\Models\User;
use App\Models\UsernameHistory;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase2CompleteProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
    }

    /**
     * Requirement 2.1: View Authenticated and Public Profiles
     */
    public function test_phase2_user_can_view_own_and_public_profiles(): void
    {
        $user = User::factory()->create([
            'username' => 'alex_dev',
            'name' => 'Alex Developer',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'display_name' => 'Alex Developer',
            'headline' => 'Full-Stack Engineer',
            'bio' => 'Building social tech.',
            'location' => 'Dhaka, Bangladesh',
        ]);

        // 1. Authenticated user view own profile
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => 'alex_dev',
                    ],
                ],
            ]);

        // 2. Public view by username
        $publicResponse = $this->getJson('/api/v1/users/alex_dev');
        $publicResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'alex_dev',
                    ],
                ],
            ]);
    }

    /**
     * Requirement 2.2: Update Profile Details (bio, headline, location, name, website, etc.)
     */
    public function test_phase2_user_can_update_profile_details(): void
    {
        $user = User::factory()->create([
            'username' => 'johnny',
            'name' => 'John Original',
        ]);

        $payload = [
            'first_name' => 'John',
            'last_name' => 'Updated',
            'display_name' => 'John Updated',
            'headline' => 'Senior Architect at Edge-Tec',
            'bio' => 'Passionate about distributed systems.',
            'location' => 'Sylhet, Bangladesh',
            'website' => 'https://johndev.test',
            'birth_date' => '1992-06-15',
            'gender' => 'male',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully.',
            ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'display_name' => 'John Updated',
            'headline' => 'Senior Architect at Edge-Tec',
            'bio' => 'Passionate about distributed systems.',
            'location' => 'Sylhet, Bangladesh',
            'website' => 'https://johndev.test',
            'gender' => 'male',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Updated',
        ]);
    }

    /**
     * Requirement 2.3: Username Management & History
     */
    public function test_phase2_user_can_update_username_with_uniqueness_and_history(): void
    {
        $user = User::factory()->create([
            'username' => 'old_handle',
        ]);

        // Update to new valid username
        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/username', [
                'username' => 'new_handle',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('new_handle', $user->fresh()->username);

        // Verify UsernameHistory record
        $this->assertDatabaseHas('username_histories', [
            'user_id' => $user->id,
            'username' => 'old_handle',
        ]);

        // Duplicate username must fail
        $otherUser = User::factory()->create(['username' => 'existing_user']);
        $dupResponse = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/username', [
                'username' => 'existing_user',
            ]);

        $dupResponse->assertStatus(422);
    }

    /**
     * Requirement 2.4: Profile Photo (Avatar) Upload and Delete
     */
    public function test_phase2_user_can_upload_and_delete_avatar(): void
    {
        Queue::fake();

        $user = User::factory()->create(['username' => 'photoman']);
        UserProfile::create([
            'user_id' => $user->id,
            'display_name' => 'Photo Man',
        ]);

        $file = UploadedFile::fake()->image('my_avatar.png', 400, 400);

        // Upload Avatar via v1 endpoint
        $uploadResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar', [
                'file' => $file,
                'caption' => 'New profile look',
                'crop_x' => 0,
                'crop_y' => 0,
                'crop_width' => 300,
                'crop_height' => 300,
            ]);

        $uploadResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertNotNull($user->profile->avatar_url);

        // Delete Avatar via v1 endpoint
        $deleteResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile/avatar');

        $deleteResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Requirement 2.5: Cover Photo Upload, Reposition, and Delete
     */
    public function test_phase2_user_can_upload_reposition_and_delete_cover(): void
    {
        Queue::fake();

        $user = User::factory()->create(['username' => 'coverman']);
        UserProfile::create([
            'user_id' => $user->id,
            'display_name' => 'Cover Man',
        ]);

        $file = UploadedFile::fake()->image('my_cover.jpg', 1200, 500);

        // Upload Cover Photo via v1 endpoint
        $uploadResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/cover', [
                'file' => $file,
                'caption' => 'Scenic Banner',
                'cover_position_y' => 25,
            ]);

        $uploadResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertNotNull($user->profile->cover_url);

        // Reposition Cover Photo via v1 endpoint
        $repositionResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/cover/reposition', [
                'position_y' => 60,
            ]);

        $repositionResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(60, $user->fresh()->profile->cover_position_y);

        // Delete Cover Photo via v1 endpoint
        $deleteResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile/cover');

        $deleteResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Requirement 2.6: Granular Privacy Settings Management
     */
    public function test_phase2_user_can_retrieve_and_update_privacy_settings(): void
    {
        $user = User::factory()->create(['username' => 'private_pete']);
        PrivacySetting::create([
            'user_id' => $user->id,
            'profile_visibility' => 'public',
            'post_default_privacy' => 'public',
        ]);

        // 1. Get privacy settings via v1
        $getResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile/privacy');

        $getResponse->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'profile_visibility',
                    'post_default_privacy',
                    'friends_list_visibility',
                ],
            ]);

        // 2. Update privacy settings via v1
        $updatePayload = [
            'profile_visibility' => 'friends',
            'post_default_privacy' => 'friends',
            'friends_list_visibility' => 'only_me',
            'phone_visibility' => 'only_me',
            'email_visibility' => 'only_me',
            'birthday_visibility' => 'friends',
            'search_engine_indexing' => false,
        ];

        $updateResponse = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/privacy', $updatePayload);

        $updateResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('privacy_settings', [
            'user_id' => $user->id,
            'profile_visibility' => 'friends',
            'post_default_privacy' => 'friends',
            'friends_list_visibility' => 'only_me',
            'search_engine_indexing' => false,
        ]);
    }

    /**
     * Requirement 2.7: Authorization Defense (Unauthorized edits blocked)
     */
    public function test_phase2_unauthenticated_requests_are_rejected(): void
    {
        $this->putJson('/api/v1/profile', ['bio' => 'Hacked bio'])->assertStatus(401);
        $this->putJson('/api/v1/profile/username', ['username' => 'hacked_name'])->assertStatus(401);
        $this->postJson('/api/v1/profile/avatar', [])->assertStatus(401);
        $this->postJson('/api/v1/profile/cover', [])->assertStatus(401);
        $this->putJson('/api/v1/profile/privacy', ['profile_visibility' => 'only_me'])->assertStatus(401);
    }
}
