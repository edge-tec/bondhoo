<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseNotificationAndDiscoveryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Test Device Session & Push Token Registration and Rotation.
     */
    public function test_device_token_registration_and_rotation(): void
    {
        $user = User::factory()->create(['name' => 'Device User']);

        // Register new Android device
        $registerRes = $this->actingAs($user)->postJson('/api/v1/devices/register', [
            'device_id' => 'android_device_unique_12345',
            'device_name' => 'Pixel 9 Pro',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'push_token' => 'fcm_token_initial_abc123',
        ]);

        $registerRes->assertStatus(200);
        $this->assertTrue($registerRes->json('success'));
        $sessionId = $registerRes->json('data.id');
        $this->assertNotNull($sessionId);
        $this->assertEquals('Pixel 9 Pro', $registerRes->json('data.device_name'));

        // Rotate push token on existing device
        $rotateRes = $this->actingAs($user)->postJson('/api/v1/devices/register', [
            'device_id' => 'android_device_unique_12345',
            'device_name' => 'Pixel 9 Pro',
            'platform' => 'android',
            'app_version' => '1.0.1',
            'push_token' => 'fcm_token_rotated_xyz789',
        ]);

        $rotateRes->assertStatus(200);
        $this->assertEquals($sessionId, $rotateRes->json('data.id'));

        // List active sessions
        $listRes = $this->actingAs($user)->getJson('/api/v1/devices');
        $listRes->assertStatus(200);
        $this->assertNotEmpty($listRes->json('data'));
    }

    /**
     * 2. Test Device Session Revocation.
     */
    public function test_device_session_revocation(): void
    {
        $user = User::factory()->create(['name' => 'Revoke User']);

        $registerRes = $this->actingAs($user)->postJson('/api/v1/devices/register', [
            'device_id' => 'android_device_to_revoke',
            'device_name' => 'Samsung Galaxy S24',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'push_token' => 'fcm_token_revoke_test',
        ]);
        $sessionId = $registerRes->json('data.id');

        // Revoke session
        $deleteRes = $this->actingAs($user)->deleteJson("/api/v1/devices/{$sessionId}");
        $deleteRes->assertStatus(200);
        $this->assertTrue($deleteRes->json('data.revoked'));

        // Second deletion returns 404
        $repeatDeleteRes = $this->actingAs($user)->deleteJson("/api/v1/devices/{$sessionId}");
        $repeatDeleteRes->assertStatus(404);
    }

    /**
     * 3. Test Friend Suggestions Discovery API.
     */
    public function test_friend_suggestions_discovery(): void
    {
        $user = User::factory()->create(['name' => 'Main User']);
        User::factory()->count(3)->create();

        $suggestionsRes = $this->actingAs($user)->getJson('/api/v1/friends/suggestions');
        $suggestionsRes->assertStatus(200);
        $this->assertTrue($suggestionsRes->json('success'));
        $this->assertIsArray($suggestionsRes->json('data'));
    }

    /**
     * 4. Test Global Search & Discovery with Suggestions and Filters.
     */
    public function test_search_and_discovery_with_suggestions_and_filters(): void
    {
        $user = User::factory()->create(['name' => 'Searcher User']);
        $targetUser = User::factory()->create(['name' => 'Sadia Islam', 'username' => 'sadia_islam_search']);
        $group = Group::factory()->create([
            'name' => 'Android Enthusiasts BD',
            'privacy' => 'public',
        ]);

        // Suggest mode when query is empty
        $suggestRes = $this->actingAs($user)->getJson('/api/v1/search?suggest=true');
        $suggestRes->assertStatus(200);
        $this->assertTrue($suggestRes->json('success'));

        // Direct search query for users
        $queryRes = $this->actingAs($user)->getJson('/api/v1/search?q=Sadia&type=users');
        $queryRes->assertStatus(200);
        $users = $queryRes->json('data.results.users') ?? $queryRes->json('data.users');
        $this->assertNotEmpty($users);
        $this->assertEquals('Sadia Islam', $users[0]['name']);

        // Direct search query for groups
        $groupRes = $this->actingAs($user)->getJson('/api/v1/search?q=Android&type=groups');
        $groupRes->assertStatus(200);
        $groups = $groupRes->json('data.results.groups') ?? $groupRes->json('data.groups');
        $this->assertNotEmpty($groups);
    }

    /**
     * 5. Test Notification Preferences Retrieval and Real-time Update.
     */
    public function test_notification_preferences_get_and_update(): void
    {
        $user = User::factory()->create(['name' => 'Prefs User']);

        // Get initial preferences
        $getRes = $this->actingAs($user)->getJson('/api/v1/notifications/preferences');
        $getRes->assertStatus(200);
        $this->assertTrue($getRes->json('success'));

        // Update preferences
        $updateRes = $this->actingAs($user)->putJson('/api/v1/notifications/preferences', [
            'push_notifications' => true,
            'message_alerts' => true,
            'like_alerts' => true,
            'security_alerts' => true,
        ]);
        $updateRes->assertStatus(200);
        $this->assertTrue($updateRes->json('success'));
        $this->assertTrue((bool) $updateRes->json('data.push_notifications'));
    }
}
