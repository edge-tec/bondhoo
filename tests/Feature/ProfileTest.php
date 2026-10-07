<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_public_profile(): void
    {
        $user = User::factory()->create(['username' => 'sarah']);
        UserProfile::create([
            'user_id' => $user->id,
            'display_name' => 'Sarah Connor',
            'bio' => 'Building the future.',
            'location' => 'Los Angeles, CA',
        ]);
        UserSetting::create([
            'user_id' => $user->id,
            'find_by_email' => 'no_one',
            'find_by_phone' => 'no_one',
        ]);

        $response = $this->getJson('/api/v1/users/sarah');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'sarah',
                    ],
                    'profile' => [
                        'display_name' => 'Sarah Connor',
                        'bio' => 'Building the future.',
                        'location' => 'Los Angeles, CA',
                    ],
                    'is_owner' => false,
                ],
            ]);

        // Non-owner should not see email or phone when hidden by privacy settings
        $responseData = $response->json('data.user');
        $this->assertArrayNotHasKey('email', $responseData);
        $this->assertArrayNotHasKey('phone', $responseData);
    }

    public function test_authenticated_user_can_update_their_profile(): void
    {
        $user = User::factory()->create(['username' => 'alex']);
        $token = $user->createToken('test')->plainTextToken;

        $payload = [
            'name' => 'Alexander The Great',
            'bio' => 'Explorer and Strategist.',
            'location' => 'Pella, Greece',
            'gender' => 'male',
            'interests' => ['history', 'strategy', 'leadership'],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully.',
            ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'Explorer and Strategist.',
            'location' => 'Pella, Greece',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Alexander The Great',
        ]);
    }

    public function test_authenticated_user_can_update_their_settings(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $payload = [
            'who_can_see_posts' => 'friends',
            'who_can_send_friend_requests' => 'friends_of_friends',
            'dark_mode' => true,
            'notification_push' => false,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile/settings', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Settings updated successfully.',
            ]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'who_can_see_posts' => 'friends',
            'who_can_send_friend_requests' => 'friends_of_friends',
            'dark_mode' => true,
            'notification_push' => false,
        ]);
    }
}
