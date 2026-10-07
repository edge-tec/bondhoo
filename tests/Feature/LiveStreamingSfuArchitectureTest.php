<?php

namespace Tests\Feature;

use App\Models\BlockedUser;
use App\Models\LiveStream;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\Streaming\LiveGatewayService;
use App\Services\Streaming\LiveStreamingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LiveStreamingSfuArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_create_session_initializes_sfu_room_and_host_token(): void
    {
        $user = User::factory()->create(['name' => 'Host User']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/live/sessions', [
            'title' => 'WebRTC SFU Live Show',
            'description' => 'Architecture verification session',
            'privacy' => 'public',
            'recording_enabled' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => ['id', 'channel_id', 'title', 'status'],
                'sfu_room_id',
                'sfu_endpoint',
                'host_token',
                'ice_servers',
            ]);

        $streamId = $response->json('data.id');
        $sfuRoomId = $response->json('sfu_room_id');
        $hostToken = $response->json('host_token');

        $this->assertNotEmpty($sfuRoomId);
        $this->assertNotEmpty($hostToken);
        $this->assertDatabaseHas('live_streams', [
            'id' => $streamId,
            'user_id' => $user->id,
            'sfu_room_id' => $sfuRoomId,
            'status' => 'ready',
        ]);

        // Cryptographically verify host token
        $gateway = app(LiveGatewayService::class);
        $verified = $gateway->verifyToken($hostToken);

        $this->assertNotNull($verified);
        $this->assertSame((string) $user->id, $verified['sub']);
        $this->assertSame('host', $verified['role']);
        $this->assertTrue($verified['permissions']['can_publish']);
        $this->assertTrue($verified['permissions']['can_subscribe']);
        $this->assertTrue($verified['permissions']['can_record']);
    }

    public function test_confirm_sfu_track_publication_transitions_to_live(): void
    {
        $user = User::factory()->create();
        $streaming = app(LiveStreamingService::class);
        $stream = $streaming->createChannel($user, 'Stream Confirmation Test');

        $hostToken = $stream->sfu_host_token;

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/sfu/publish", [
            'host_token' => $hostToken,
            'track_meta' => [
                'fps' => 30,
                'resolution' => '720p',
                'codec' => 'H.264',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $freshStream = $stream->fresh();
        $this->assertSame('live', $freshStream->status);
        $this->assertNotNull($freshStream->started_at);
        $this->assertNotNull($freshStream->last_heartbeat_at);
    }

    public function test_viewer_token_issuance_with_privacy_and_blocking_authorization(): void
    {
        $broadcaster = User::factory()->create();
        $viewer = User::factory()->create();
        $blockedUser = User::factory()->create();

        // Establish block relationship via BlockedUser
        BlockedUser::create([
            'user_id' => $broadcaster->id,
            'identifier' => (string) $blockedUser->id,
            'reason' => 'user_blocked',
            'is_active' => true,
        ]);

        $streaming = app(LiveStreamingService::class);
        $stream = $streaming->createChannel($broadcaster, 'Public Stream for Authorization Test');
        $streaming->startStream($stream);

        // 1. Legitimate viewer receives signed token
        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/live/streams/{$stream->id}/viewer-token");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['token', 'room_id', 'sfu_endpoint', 'ice_servers']]);

        $viewerToken = $response->json('data.token');
        $gateway = app(LiveGatewayService::class);
        $verified = $gateway->verifyToken($viewerToken);

        $this->assertNotNull($verified);
        $this->assertSame((string) $viewer->id, $verified['sub']);
        $this->assertSame('viewer', $verified['role']);
        $this->assertFalse($verified['permissions']['can_publish']);
        $this->assertTrue($verified['permissions']['can_subscribe']);

        // 2. Blocked viewer is rejected with 403 Forbidden
        $blockedResponse = $this->actingAs($blockedUser, 'sanctum')
            ->postJson("/api/v2/live/streams/{$stream->id}/viewer-token");

        $blockedResponse->assertStatus(403);
    }

    public function test_authoritative_server_side_end_live_closes_sfu_room_and_finalizes_recording(): void
    {
        $user = User::factory()->create();
        $streaming = app(LiveStreamingService::class);

        $stream = $streaming->createChannel($user, 'Architecture End Live Test', 'Testing post-live auto conversion');
        $streaming->startStream($stream);

        // Simulate 45 seconds of live broadcast with server-side SFU recording file
        $recordingPath = "replays/{$stream->channel_id}.webm";
        Storage::disk('public')->put($recordingPath, 'server-side-recorded-video-stream');
        $stream->update([
            'started_at' => now()->subSeconds(45),
            'recording_file_path' => $recordingPath,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v2/live/sessions/{$stream->id}/end");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $freshStream = $stream->fresh();
        $this->assertSame('ended', $freshStream->status);
        $this->assertSame('ready', $freshStream->recording_status);
        $this->assertGreaterThanOrEqual(44, $freshStream->duration);
        $this->assertNotNull($freshStream->generated_post_id);

        // Assert permanent Post was created
        $post = Post::find($freshStream->generated_post_id);
        $this->assertNotNull($post);
        $this->assertSame($user->id, $post->user_id);
        $this->assertSame('video', $post->type);
        $this->assertSame('published', $post->status);
        $this->assertStringContainsString('Architecture End Live Test', $post->content);

        // Assert permanent Media was created
        $media = Media::where('mediable_id', $post->id)->first();
        $this->assertNotNull($media);
        $this->assertSame('videos', $media->collection);
        $this->assertSame('completed', $media->processing_status);
    }

    public function test_single_active_live_stream_per_user_enforcement(): void
    {
        $user = User::factory()->create();
        $streaming = app(LiveStreamingService::class);

        $streamA = $streaming->createChannel($user, 'First Stream');
        $streaming->startStream($streamA);
        $this->assertSame('live', $streamA->fresh()->status);

        // Creating a second stream automatically terminates the first stream
        $streamB = $streaming->createChannel($user, 'Second Stream');
        $this->assertSame('ended', $streamA->fresh()->status);
        $this->assertSame('ready', $streamB->fresh()->status);
    }

    public function test_abnormal_disconnect_prune_command_terminates_dead_stream(): void
    {
        $user = User::factory()->create();
        $streaming = app(LiveStreamingService::class);

        $stream = $streaming->createChannel($user, 'Abandoned Stream');
        $streaming->startStream($stream);

        // Set last heartbeat to 90 seconds ago (exceeding 75 second threshold)
        $recordingPath = "replays/{$stream->channel_id}.webm";
        Storage::disk('public')->put($recordingPath, 'abandoned-server-stream');
        $stream->update([
            'last_heartbeat_at' => now()->subSeconds(90),
            'recording_file_path' => $recordingPath,
        ]);

        $this->artisan('live:prune-stale')
            ->expectsOutputToContain('Successfully pruned 1 stale live stream(s)')
            ->assertExitCode(0);

        $this->assertSame('ended', $stream->fresh()->status);
        $this->assertSame('ready', $stream->fresh()->recording_status);
    }

    public function test_live_stream_model_state_machine_transitions(): void
    {
        $stream = new LiveStream(['status' => LiveStream::STATUS_CREATING]);
        $this->assertTrue($stream->canTransitionTo(LiveStream::STATUS_STARTING));
        $this->assertTrue($stream->canTransitionTo(LiveStream::STATUS_FAILED));
        $this->assertFalse($stream->canTransitionTo(LiveStream::STATUS_ENDED));

        $stream->status = LiveStream::STATUS_LIVE;
        $this->assertTrue($stream->canTransitionTo(LiveStream::STATUS_ENDING));
        $this->assertTrue($stream->canTransitionTo(LiveStream::STATUS_ENDED));
        $this->assertTrue($stream->canTransitionTo(LiveStream::STATUS_FAILED));
        $this->assertFalse($stream->canTransitionTo(LiveStream::STATUS_CREATING));
    }
}
