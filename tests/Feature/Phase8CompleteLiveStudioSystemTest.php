<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LiveStream;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase8CompleteLiveStudioSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(string $username, string $name, string $role = 'USER'): User
    {
        $user = User::factory()->create([
            'username' => $username,
            'name' => $name,
            'email' => "{$username}@example.com",
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'avatar_url' => "https://ui-avatars.com/api/?name={$username}",
                'bio' => "Bio of {$name}",
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'dark_mode' => true,
            ]
        );

        $roleModel = Role::where('name', $role)->first();
        if ($roleModel) {
            $user->roles()->syncWithoutDetaching([$roleModel->id]);
        }

        return $user;
    }

    /**
     * 1. Test Channel Creation with Metadata, Stream Key, and WebRTC Room
     */
    public function test_live_stream_creation_with_webrtc_provisioning(): void
    {
        $broadcaster = $this->createUser('broadcaster_pro', 'Broadcaster Pro');

        $res = $this->actingAs($broadcaster, 'sanctum')->postJson('/api/v1/live/streams', [
            'title' => 'Live Coding Session: Jetpack Compose & Vue 3',
            'description' => 'Real-time architectural discussion and live coding stream.',
            'privacy' => 'public',
            'comments_enabled' => true,
            'reactions_enabled' => true,
            'recording_enabled' => true,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'Live Coding Session: Jetpack Compose & Vue 3')
            ->assertJsonPath('data.status', 'ready');

        $streamId = $res->json('data.id');
        $this->assertDatabaseHas('live_streams', [
            'id' => $streamId,
            'user_id' => $broadcaster->id,
            'title' => 'Live Coding Session: Jetpack Compose & Vue 3',
            'status' => 'ready',
        ]);

        $stream = LiveStream::find($streamId);
        $this->assertNotEmpty($stream->stream_key);
        $this->assertNotEmpty($stream->sfu_room_id);
    }

    /**
     * 2. Test Stream Start and End Lifecycle
     */
    public function test_live_stream_start_and_end_lifecycle(): void
    {
        $broadcaster = $this->createUser('streamer_live', 'Streamer Live');

        $createRes = $this->actingAs($broadcaster, 'sanctum')->postJson('/api/v1/live/streams', [
            'title' => 'Daily Standup Broadcast',
            'privacy' => 'public',
        ]);
        $streamId = $createRes->json('data.id');

        // 1. Broadcaster starts stream
        $startRes = $this->actingAs($broadcaster, 'sanctum')->postJson("/api/v1/live/streams/{$streamId}/start");
        $startRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $stream = LiveStream::find($streamId);
        $this->assertEquals(LiveStream::STATUS_LIVE, $stream->status);
        $this->assertNotNull($stream->started_at);

        // 2. Broadcaster ends stream
        $endRes = $this->actingAs($broadcaster, 'sanctum')->postJson("/api/v1/live/streams/{$streamId}/end");
        $endRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $stream->refresh();
        $this->assertEquals(LiveStream::STATUS_ENDED, $stream->status);
        $this->assertNotNull($stream->ended_at);
    }

    /**
     * 3. Test Viewer Join, Heartbeat, and Viewer Counter Increment
     */
    public function test_viewer_presence_and_heartbeat(): void
    {
        $broadcaster = $this->createUser('host_user', 'Host User');
        $viewer = $this->createUser('fan_viewer', 'Fan Viewer');

        $createRes = $this->actingAs($broadcaster, 'sanctum')->postJson('/api/v1/live/streams', [
            'title' => 'Interactive AMA Session',
            'privacy' => 'public',
        ]);
        $streamId = $createRes->json('data.id');
        $this->actingAs($broadcaster, 'sanctum')->postJson("/api/v1/live/streams/{$streamId}/start");

        // Viewer joins stream
        $joinRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v1/live/streams/{$streamId}/join", [
            'session_id' => 'viewer-session-12345',
        ]);
        $joinRes->assertStatus(200);

        // Viewer sends heartbeat
        $heartbeatRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v1/live/streams/{$streamId}/heartbeat", [
            'session_id' => 'viewer-session-12345',
        ]);
        $heartbeatRes->assertStatus(200);

        $stream = LiveStream::find($streamId);
        $this->assertGreaterThanOrEqual(1, $stream->viewers_count);
    }

    /**
     * 4. Test Live Real-Time Chat Comments and Floating Reactions
     */
    public function test_live_chat_comments_and_reactions(): void
    {
        $broadcaster = $this->createUser('gaming_streamer', 'Gaming Streamer');
        $viewer = $this->createUser('chat_viewer', 'Chat Viewer');

        $createRes = $this->actingAs($broadcaster, 'sanctum')->postJson('/api/v1/live/streams', [
            'title' => 'Championship Finals Live',
            'privacy' => 'public',
        ]);
        $streamId = $createRes->json('data.id');
        $this->actingAs($broadcaster, 'sanctum')->postJson("/api/v1/live/streams/{$streamId}/start");

        // 1. Viewer posts comment in live stream chat
        $commentRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v1/live/streams/{$streamId}/comments", [
            'message' => 'Insane clutch in round 3! 🔥',
        ]);
        $commentRes->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('live_stream_comments', [
            'live_stream_id' => $streamId,
            'user_id' => $viewer->id,
            'message' => 'Insane clutch in round 3! 🔥',
        ]);

        // 2. Viewer sends floating emoji reaction
        $reactionRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v1/live/streams/{$streamId}/reactions", [
            'reaction_type' => 'love',
        ]);
        $reactionRes->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $stream = LiveStream::find($streamId);
        $this->assertGreaterThanOrEqual(1, $stream->total_reactions);
    }

    /**
     * 5. Test Live WebRTC STUN/TURN ICE Servers Endpoint
     */
    public function test_live_ice_servers_retrieval(): void
    {
        $user = $this->createUser('webrtc_user', 'WebRTC User');

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/live/ice-servers');
        $res->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $iceServers = $res->json('ice_servers');
        $this->assertNotEmpty($iceServers);
    }

    /**
     * 6. Test Stream Security & Authorization Gate
     */
    public function test_stream_security_and_authorization(): void
    {
        $broadcaster = $this->createUser('secure_host', 'Secure Host');
        $intruder = $this->createUser('unauthorized_user', 'Unauthorized User');

        $createRes = $this->actingAs($broadcaster, 'sanctum')->postJson('/api/v1/live/streams', [
            'title' => 'Security Demo Stream',
            'privacy' => 'public',
        ]);
        $streamId = $createRes->json('data.id');

        // Intruder cannot start another user's stream -> 404/403
        $intruderStart = $this->actingAs($intruder, 'sanctum')->postJson("/api/v1/live/streams/{$streamId}/start");
        $intruderStart->assertStatus(404);

        // Intruder cannot end another user's stream -> 404/403
        $intruderEnd = $this->actingAs($intruder, 'sanctum')->postJson("/api/v1/live/streams/{$streamId}/end");
        $intruderEnd->assertStatus(404);
    }

    /**
     * 7. Test Web Route Resolution for Live Studio & Viewer
     */
    public function test_live_studio_web_routes_and_navigation(): void
    {
        $creator = $this->createUser('studio_creator', 'Studio Creator');

        // 1. Live Studio creator page
        $studioRes = $this->actingAs($creator, 'web')->get('/live/studio');
        $studioRes->assertStatus(200);

        // 2. Live create shortcut
        $createRes = $this->actingAs($creator, 'web')->get('/live/create');
        $createRes->assertStatus(200);

        // 3. Live stream viewer page
        $stream = LiveStream::where('user_id', $creator->id)->first();
        if ($stream) {
            $showRes = $this->actingAs($creator, 'web')->get("/live/{$stream->id}");
            $showRes->assertStatus(200);
        }
    }
}
