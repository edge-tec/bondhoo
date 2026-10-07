<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Streaming\LiveStreamingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveStreamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_live_channel(): void
    {
        $user = User::factory()->create();
        $streaming = app(LiveStreamingService::class);

        $stream = $streaming->createChannel($user, 'টেক লাইভ ব্রডকাস্ট');

        $this->assertDatabaseHas('live_streams', [
            'id' => $stream->id,
            'user_id' => $user->id,
            'title' => 'টেক লাইভ ব্রডকাস্ট',
            'status' => 'ready',
        ]);
        $this->assertNotEmpty($stream->stream_key);
        $this->assertNotEmpty($stream->ingest_url);
        $this->assertNotEmpty($stream->playback_url);
    }

    public function test_stream_lifecycle_start_and_end(): void
    {
        $user = User::factory()->create();
        $streaming = app(LiveStreamingService::class);

        $stream = $streaming->createChannel($user, 'লাইভ সেশন');
        $this->assertSame('ready', $stream->status);

        $streaming->startStream($stream);
        $this->assertSame('live', $stream->fresh()->status);
        $this->assertNotNull($stream->fresh()->started_at);

        $streaming->endStream($stream);
        $this->assertSame('ended', $stream->fresh()->status);
        $this->assertNotNull($stream->fresh()->ended_at);
    }

    public function test_live_chat_and_virtual_gifts(): void
    {
        $streamer = User::factory()->create();
        $viewer = User::factory()->create();
        $streaming = app(LiveStreamingService::class);

        $stream = $streaming->createChannel($streamer, 'লাইভ মিউজিক শো');
        $streaming->startStream($stream);

        // Chat comment
        $comment = $streaming->sendComment($stream, $viewer, 'চমৎকার পারফরম্যান্স!');
        $this->assertDatabaseHas('live_stream_comments', [
            'live_stream_id' => $stream->id,
            'user_id' => $viewer->id,
            'message' => 'চমৎকার পারফরম্যান্স!',
        ]);

        // Virtual gift
        $gift = $streaming->sendGift($stream, $viewer, 'diamond', 50);
        $this->assertDatabaseHas('live_stream_gifts', [
            'live_stream_id' => $stream->id,
            'user_id' => $viewer->id,
            'gift_type' => 'diamond',
            'coin_amount' => 50,
        ]);
    }

    public function test_adaptive_bitrate_endpoints_structure(): void
    {
        $user = User::factory()->create();
        $streaming = app(LiveStreamingService::class);

        $stream = $streaming->createChannel($user, '4K Live');
        $bitrates = $streaming->getAdaptiveBitrates($stream);

        $this->assertArrayHasKey('1080p', $bitrates);
        $this->assertArrayHasKey('720p', $bitrates);
        $this->assertArrayHasKey('480p', $bitrates);
        $this->assertArrayHasKey('360p', $bitrates);
        $this->assertArrayHasKey('240p', $bitrates);
    }
}
