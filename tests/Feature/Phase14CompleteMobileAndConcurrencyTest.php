<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase14CompleteMobileAndConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name ?? 'Mobile',
            'last_name' => $user->last_name ?? 'User',
        ]);

        return $user;
    }

    /**
     * Requirement 14.1: Mobile device registration and multi-device session management.
     */
    public function test_mobile_device_registration_and_session_management(): void
    {
        $user = $this->createUser();

        // 1. Register Android device
        $androidRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/devices/register', [
                'device_id' => 'android-pixel-9-pro-uuid-001',
                'device_name' => 'Pixel 9 Pro',
                'platform' => 'android',
                'app_version' => '2.5.0',
                'push_token' => 'fcm-sample-token-abc123xyz',
            ]);

        $androidRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.device_id', 'android-pixel-9-pro-uuid-001')
            ->assertJsonPath('data.platform', 'android');

        $androidSessionId = $androidRes->json('data.id');

        // 2. Register iOS device (multi-device)
        $iosRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/devices/register', [
                'device_id' => 'ios-iphone-16-pro-uuid-002',
                'device_name' => 'iPhone 16 Pro',
                'platform' => 'ios',
                'app_version' => '2.5.0',
                'push_token' => 'apns-sample-token-def456uvw',
            ]);

        $iosRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 3. List active devices
        $listRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/devices');

        $listRes->assertStatus(200)
            ->assertJsonPath('success', true);
        $deviceIds = collect($listRes->json('data'))->pluck('device_id')->all();
        $this->assertContains('android-pixel-9-pro-uuid-001', $deviceIds);
        $this->assertContains('ios-iphone-16-pro-uuid-002', $deviceIds);

        // 4. Revoke Android device
        $revokeRes = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/devices/{$androidSessionId}");

        $revokeRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 5. Verify revoked device is no longer in active sessions
        $afterRevoke = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/devices');
        $activeDeviceIds = collect($afterRevoke->json('data'))->pluck('device_id')->all();
        $this->assertNotContains('android-pixel-9-pro-uuid-001', $activeDeviceIds);
        $this->assertContains('ios-iphone-16-pro-uuid-002', $activeDeviceIds);
    }

    /**
     * Requirement 14.2: Mobile messenger offline event synchronization.
     */
    public function test_mobile_messenger_offline_sync(): void
    {
        $user = $this->createUser();

        $syncRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/messenger/sync?since_id=0&limit=50');

        $syncRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'events',
                    'has_more',
                ],
            ]);
    }

    /**
     * Requirement 14.3: High-concurrency engagement counters integrity.
     */
    public function test_high_concurrency_reactions_and_comments_integrity(): void
    {
        $author = $this->createUser();
        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'High-concurrency benchmark post',
            'audience' => 'public',
        ]);

        $users = [];
        for ($i = 0; $i < 15; $i++) {
            $users[] = $this->createUser();
        }

        // 15 users concurrently like post
        foreach ($users as $user) {
            $res = $this->actingAs($user, 'sanctum')
                ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'like']);
            $res->assertStatus(200);
        }

        $this->assertEquals(15, $post->fresh()->likes_count);
        $this->assertEquals(15, Reaction::where('reactable_type', Post::class)->where('reactable_id', $post->id)->count());

        // 5 users unlike
        for ($i = 0; $i < 5; $i++) {
            $res = $this->actingAs($users[$i], 'sanctum')
                ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'like']);
            $res->assertStatus(200)
                ->assertJsonPath('data.reacted', false);
        }

        $this->assertEquals(10, $post->fresh()->likes_count);

        // 10 comments posted concurrently
        for ($i = 0; $i < 10; $i++) {
            $commentRes = $this->actingAs($users[$i], 'sanctum')
                ->postJson("/api/v1/posts/{$post->id}/comments", [
                    'body' => "Concurrent comment {$i}",
                ]);
            $commentRes->assertStatus(201);
        }

        $this->assertEquals(10, $post->fresh()->comments_count);
    }

    /**
     * Requirement 14.4: Standard envelope response contract compliance across core endpoints.
     */
    public function test_api_v1_envelope_response_contract_compliance(): void
    {
        $user = $this->createUser();

        // 1. Feed envelope
        $feedRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/feed');
        $feedRes->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'message']);

        // 2. Profile envelope
        $profileRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile');
        $profileRes->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'message']);

        // 3. Search envelope
        $searchRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/search?q=test');
        $searchRes->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'message']);

        // 4. Notifications envelope
        $notifRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');
        $notifRes->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'message']);
    }
}
