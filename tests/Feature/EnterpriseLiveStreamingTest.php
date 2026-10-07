<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Streaming\LiveStreamingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseLiveStreamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_live_session_with_privacy_and_feature_flags(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/live/streams', [
            'title' => 'নতুন প্রযুক্তি নিয়ে লাইভ আড্ডা',
            'description' => 'যুগাজুগ প্ল্যাটফর্মের লাইভ সম্প্রচার পরীক্ষা',
            'privacy' => 'public',
            'comments_enabled' => true,
            'reactions_enabled' => true,
            'sharing_enabled' => true,
            'recording_enabled' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'নতুন প্রযুক্তি নিয়ে লাইভ আড্ডা')
            ->assertJsonPath('data.privacy', 'public')
            ->assertJsonPath('data.comments_enabled', true)
            ->assertJsonPath('data.recording_enabled', true);

        $this->assertDatabaseHas('live_streams', [
            'user_id' => $user->id,
            'title' => 'নতুন প্রযুক্তি নিয়ে লাইভ আড্ডা',
            'privacy' => 'public',
            'status' => 'ready',
        ]);
    }

    public function test_stream_lifecycle_start_and_end_with_recording_processing(): void
    {
        $user = User::factory()->create();
        $streaming = app(LiveStreamingService::class);
        $stream = $streaming->createChannel($user, 'গেমিং লাইভ শো', 'সরাসরি গেমপ্লে', [
            'recording_enabled' => true,
        ]);

        $this->assertSame('ready', $stream->status);
        $this->assertSame('none', $stream->recording_status);

        // Start live
        $startRes = $this->actingAs($user, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/start");
        $startRes->assertStatus(200)
            ->assertJsonPath('data.status', 'live');

        $this->assertSame('live', $stream->fresh()->status);
        $this->assertSame('recording', $stream->fresh()->recording_status);
        $this->assertNotNull($stream->fresh()->started_at);

        // End live
        $endRes = $this->actingAs($user, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/end");
        $endRes->assertStatus(200)
            ->assertJsonPath('data.status', 'ended');

        $this->assertSame('ended', $stream->fresh()->status);
        $this->assertSame('processing', $stream->fresh()->recording_status);
        $this->assertNotNull($stream->fresh()->ended_at);
    }

    public function test_viewer_presence_join_heartbeat_and_leave(): void
    {
        $broadcaster = User::factory()->create();
        $viewer = User::factory()->create();
        $streaming = app(LiveStreamingService::class);
        $stream = $streaming->createChannel($broadcaster, 'টেক লাইভ');
        $streaming->startStream($stream);

        $sessionId = 'sess_'.uniqid();

        // 1. Viewer joins
        $joinRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/join", [
            'session_id' => $sessionId,
        ]);

        $joinRes->assertStatus(200)
            ->assertJsonPath('data.viewers_count', 1)
            ->assertJsonPath('data.peak_viewers', 1)
            ->assertJsonPath('data.total_unique_viewers', 1);

        $this->assertDatabaseHas('live_stream_viewers', [
            'live_stream_id' => $stream->id,
            'session_id' => $sessionId,
            'user_id' => $viewer->id,
            'is_active' => true,
        ]);

        // 2. Viewer sends heartbeat
        $heartbeatRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/heartbeat", [
            'session_id' => $sessionId,
        ]);

        $heartbeatRes->assertStatus(200)
            ->assertJsonPath('data.viewers_count', 1);

        // 3. Viewer leaves
        $leaveRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/leave", [
            'session_id' => $sessionId,
        ]);

        $leaveRes->assertStatus(200)
            ->assertJsonPath('data.viewers_count', 0);

        $this->assertDatabaseHas('live_stream_viewers', [
            'session_id' => $sessionId,
            'is_active' => false,
        ]);
    }

    public function test_privacy_enforcement_prevents_unauthorized_viewers(): void
    {
        $broadcaster = User::factory()->create();
        $unrelatedUser = User::factory()->create();

        $streaming = app(LiveStreamingService::class);
        $privateStream = $streaming->createChannel($broadcaster, 'গোপনীয় টিম মিটিং', null, [
            'privacy' => 'only_me',
        ]);
        $streaming->startStream($privateStream);

        // Unrelated user cannot view private stream via API
        $response = $this->actingAs($unrelatedUser, 'sanctum')->getJson("/api/v2/live/streams/{$privateStream->id}");
        $response->assertStatus(403);

        // Broadcaster can view their own private stream
        $ownerResponse = $this->actingAs($broadcaster, 'sanctum')->getJson("/api/v2/live/streams/{$privateStream->id}");
        $ownerResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    public function test_live_chat_comment_creation_and_moderation_deletion(): void
    {
        $broadcaster = User::factory()->create();
        $viewer = User::factory()->create();
        $streaming = app(LiveStreamingService::class);
        $stream = $streaming->createChannel($broadcaster, 'লাইভ মিউজিক');
        $streaming->startStream($stream);

        // Viewer posts comment
        $commentRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/comments", [
            'message' => 'গানটি চমৎকার লেগেছে!',
        ]);

        $commentRes->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.message', 'গানটি চমৎকার লেগেছে!');

        $commentId = $commentRes->json('data.id');

        // Broadcaster deletes viewer's comment
        $delRes = $this->actingAs($broadcaster, 'sanctum')->deleteJson("/api/v2/live/streams/{$stream->id}/comments/{$commentId}");
        $delRes->assertStatus(200);

        $this->assertDatabaseMissing('live_stream_comments', [
            'id' => $commentId,
        ]);
    }

    public function test_live_reactions_and_sharing_counters(): void
    {
        $broadcaster = User::factory()->create();
        $viewer = User::factory()->create();
        $streaming = app(LiveStreamingService::class);
        $stream = $streaming->createChannel($broadcaster, 'লাইভ টকশো');
        $streaming->startStream($stream);

        // Send reaction
        $reactRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/reactions", [
            'reaction_type' => 'love',
        ]);

        $reactRes->assertStatus(201)
            ->assertJsonPath('total_reactions', 1);

        $this->assertDatabaseHas('live_stream_reactions', [
            'live_stream_id' => $stream->id,
            'user_id' => $viewer->id,
            'reaction_type' => 'love',
        ]);

        // Record share
        $shareRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/share", [
            'destination' => 'feed',
        ]);

        $shareRes->assertStatus(200)
            ->assertJsonPath('total_shares', 1);

        $this->assertSame(1, $stream->fresh()->total_shares);
    }

    public function test_broadcaster_can_appoint_and_remove_moderator(): void
    {
        $broadcaster = User::factory()->create();
        $moderatorCandidate = User::factory()->create();
        $streaming = app(LiveStreamingService::class);
        $stream = $streaming->createChannel($broadcaster, 'বিতর্ক প্রতিযোগিতা');

        // Appoint moderator
        $modRes = $this->actingAs($broadcaster, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/moderators", [
            'user_id' => $moderatorCandidate->id,
        ]);

        $modRes->assertStatus(201);
        $this->assertTrue($stream->fresh()->isModerator($moderatorCandidate));

        // Remove moderator
        $removeRes = $this->actingAs($broadcaster, 'sanctum')->deleteJson("/api/v2/live/streams/{$stream->id}/moderators/{$moderatorCandidate->id}");
        $removeRes->assertStatus(200);

        $this->assertFalse($stream->fresh()->isModerator($moderatorCandidate));
    }

    public function test_viewer_can_report_live_stream(): void
    {
        $broadcaster = User::factory()->create();
        $viewer = User::factory()->create();
        $streaming = app(LiveStreamingService::class);
        $stream = $streaming->createChannel($broadcaster, 'লাইভ স্ট্রিমিং');

        $reportRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/report", [
            'reason' => 'harassment',
            'details' => 'কমিউনিটি নির্দেশিকা লঙ্ঘনকারী বক্তব্য রয়েছে।',
        ]);

        $reportRes->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('live_stream_reports', [
            'live_stream_id' => $stream->id,
            'reporter_id' => $viewer->id,
            'reason' => 'harassment',
            'status' => 'pending',
        ]);
    }

    public function test_replay_file_upload_and_ready_state(): void
    {
        Storage::fake('public');

        $broadcaster = User::factory()->create();
        $streaming = app(LiveStreamingService::class);
        $stream = $streaming->createChannel($broadcaster, 'টিউটোরিয়াল লাইভ', null, [
            'recording_enabled' => true,
        ]);
        $streaming->startStream($stream);
        $streaming->endStream($stream);

        $this->assertSame('processing', $stream->fresh()->recording_status);

        $file = UploadedFile::fake()->create('live_recording.webm', 2048, 'video/webm');

        $uploadRes = $this->actingAs($broadcaster, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/replay-upload", [
            'replay_file' => $file,
        ]);

        $uploadRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $fresh = $stream->fresh();
        $this->assertSame('ready', $fresh->recording_status);
        $this->assertNotEmpty($fresh->recording_url);
        $this->assertTrue($fresh->hasReplay());
    }

    public function test_webrtc_signaling_and_ice_servers_endpoint(): void
    {
        $broadcaster = User::factory()->create();
        $viewer = User::factory()->create();
        $streaming = app(LiveStreamingService::class);
        $stream = $streaming->createChannel($broadcaster, 'WebRTC ব্রডকাস্ট');
        $streaming->startStream($stream);

        // Get ICE servers
        $iceRes = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/live/ice-servers');
        $iceRes->assertStatus(200)
            ->assertJsonStructure(['status', 'ice_servers']);

        // Send signaling offer
        $signalRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/live/streams/{$stream->id}/signal", [
            'signal_type' => 'offer',
            'payload' => ['sdp' => 'v=0...', 'type' => 'offer'],
            'target_user_id' => $broadcaster->id,
        ]);

        $signalRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.signal_type', 'offer');
    }

    public function test_admin_can_force_terminate_live_stream(): void
    {
        $admin = User::factory()->create([
            'status' => 'admin',
        ]);
        $broadcaster = User::factory()->create();
        $streaming = app(LiveStreamingService::class);
        $stream = $streaming->createChannel($broadcaster, 'আপত্তিকর লাইভ');
        $streaming->startStream($stream);

        $terminateRes = $this->actingAs($admin)->post("/admin/live/{$stream->id}/terminate", [
            'reason' => 'Community safety violation',
        ]);

        $terminateRes->assertRedirect();
        $this->assertSame('ended', $stream->fresh()->status);
        $this->assertSame(0, $stream->fresh()->viewers_count);
    }
}
