<?php

namespace Tests\Feature;

use App\Models\LiveStream;
use App\Models\Media;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Feed Reels and Realtime Live Camera Streaming Integration Test Suite
 */
class FeedReelsAndLiveCameraStreamingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create([
            'name' => 'Bondhoo Streamer',
            'username' => 'bondhoostreamer',
        ]);
    }

    /**
     * 1. Test Feed delivers Inertia response containing reels and active live streams
     */
    public function test_feed_delivers_reels_and_active_live_streams(): void
    {
        // Create an active reel
        $media = Media::create([
            'user_id' => $this->user->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'uploads/reels/sunset.mp4',
            'mime_type' => 'video/mp4',
            'size' => 2048000,
            'processing_status' => 'ready',
        ]);

        $reel = Reel::create([
            'user_id' => $this->user->id,
            'caption' => 'Hatirjheel Sunset Reel #dhaka',
            'audio_title' => 'Bhatiali Melodies',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'privacy' => 'public',
            'views_count' => 1250,
            'likes_count' => 88,
        ]);
        ReelMedia::create([
            'reel_id' => $reel->id,
            'media_id' => $media->id,
            'quality' => 'original',
            'video_path' => 'uploads/reels/sunset.mp4',
            'thumbnail_path' => 'uploads/reels/sunset.jpg',
            'mime_type' => 'video/mp4',
            'size' => 2048000,
        ]);

        // Create an active live stream
        $liveStream = LiveStream::create([
            'channel_id' => 'chan_test_123',
            'user_id' => $this->user->id,
            'title' => 'Community Live Chat',
            'stream_key' => 'live_sk_test_123',
            'ingest_url' => 'rtmp://live.edge-tec.com/live',
            'playback_url' => 'https://live.edge-tec.com/hls/test_123.m3u8',
            'status' => LiveStream::STATUS_LIVE,
            'privacy' => 'public',
            'sfu_room_id' => 'room-test-123',
            'viewers_count' => 5,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/');
        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Feed')
            ->has('posts')
            ->has('stories')
            ->has('reels')
            ->has('activeLiveStreams')
            ->where('reels.0.caption', 'Hatirjheel Sunset Reel #dhaka')
            ->where('activeLiveStreams.0.title', 'Community Live Chat')
        );
    }

    /**
     * 2. Test fully functional Reels upload via /api/v1/media/upload and /api/v2/reels
     */
    public function test_user_can_upload_and_publish_reel(): void
    {
        $videoFile = UploadedFile::fake()->create('dance_vertical.mp4', 5000, 'video/mp4');

        // Step 1: Direct media upload
        $uploadRes = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/media/upload', [
                'file' => $videoFile,
                'collection' => 'reel',
            ]);

        $uploadRes->assertStatus(201);
        $mediaId = $uploadRes->json('data.id') ?? $uploadRes->json('data.media_id');
        $this->assertNotNull($mediaId);

        // Step 2: Publish Reel
        $publishRes = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v2/reels', [
                'media_id' => $mediaId,
                'caption' => 'My first vertical reel on Bondhoo! #viral #bangladesh',
                'audio_title' => 'SynthBD Exclusive',
                'privacy' => 'public',
                'comments_enabled' => true,
                'allow_comments' => true,
                'is_draft' => false,
            ]);

        $publishRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.caption', 'My first vertical reel on Bondhoo! #viral #bangladesh')
            ->assertJsonPath('data.audio_title', 'SynthBD Exclusive');

        $reelId = $publishRes->json('data.id');
        $this->assertDatabaseHas('reels', [
            'id' => $reelId,
            'user_id' => $this->user->id,
            'caption' => 'My first vertical reel on Bondhoo! #viral #bangladesh',
        ]);
    }

    /**
     * 3. Test Realtime Live Streaming full lifecycle (create, start, heartbeat, comment, reaction, end)
     */
    public function test_user_can_broadcast_realtime_live_stream_with_camera(): void
    {
        // 1. Create broadcast channel
        $createRes = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/live/streams', [
                'title' => 'Live Coding Session with Fans',
                'description' => 'Interactive live Q&A using web camera',
                'privacy' => 'public',
                'comments_enabled' => true,
                'reactions_enabled' => true,
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $streamId = $createRes->json('data.id');
        $this->assertNotNull($streamId);

        // 2. Start broadcast
        $startRes = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/live/streams/{$streamId}/start");

        $startRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('live_streams', [
            'id' => $streamId,
            'status' => LiveStream::STATUS_LIVE,
        ]);

        // 3. Heartbeat keeping stream alive
        $hbRes = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/live/streams/{$streamId}/heartbeat");
        $hbRes->assertStatus(200);

        // 4. Live Comment
        $commentRes = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/live/streams/{$streamId}/comments", [
                'comment' => 'Hello everyone watching live!',
            ]);
        $commentRes->assertSuccessful();

        // 5. Live Reaction
        $reactionRes = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/live/streams/{$streamId}/reactions", [
                'type' => 'heart',
                'emoji' => '❤️',
            ]);
        $reactionRes->assertSuccessful();

        // 6. End Broadcast
        $endRes = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/live/streams/{$streamId}/end");

        $endRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('live_streams', [
            'id' => $streamId,
            'status' => LiveStream::STATUS_ENDED,
        ]);
    }
}
