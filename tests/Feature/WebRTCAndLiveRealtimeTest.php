<?php

namespace Tests\Feature;

use App\Events\CallSignalEvent;
use App\Events\LiveStreamCommentBroadcastEvent;
use App\Events\LiveStreamGiftBroadcastEvent;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Contracts\RealtimeServiceInterface;
use App\Services\Streaming\LiveStreamingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WebRTCAndLiveRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_signal_requires_authentication(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = Conversation::create(['type' => Conversation::TYPE_DIRECT]);
        $conversation->participants()->create(['user_id' => $userA->id]);
        $conversation->participants()->create(['user_id' => $userB->id]);

        $response = $this->postJson("/api/v1/conversations/{$conversation->id}/call/signal", [
            'signal_type' => 'offer',
        ]);

        $response->assertStatus(401);
    }

    public function test_call_signal_forbids_non_participant(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $intruder = User::factory()->create();

        $conversation = Conversation::create(['type' => Conversation::TYPE_DIRECT]);
        $conversation->participants()->create(['user_id' => $userA->id]);
        $conversation->participants()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->id}/call/signal", [
                'signal_type' => 'offer',
                'call_type' => 'audio',
            ]);

        $response->assertStatus(403);
    }

    public function test_call_signal_validates_signal_type(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = Conversation::create(['type' => Conversation::TYPE_DIRECT]);
        $conversation->participants()->create(['user_id' => $userA->id]);
        $conversation->participants()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->id}/call/signal", [
                'signal_type' => 'invalid_signal_type',
                'call_type' => 'audio',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['signal_type']);
    }

    public function test_call_signal_broadcasts_call_signal_event(): void
    {
        Event::fake([CallSignalEvent::class]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = Conversation::create(['type' => Conversation::TYPE_DIRECT]);
        $conversation->participants()->create(['user_id' => $userA->id]);
        $conversation->participants()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->id}/call/signal", [
                'signal_type' => 'offer',
                'call_type' => 'video',
                'payload' => [
                    'sdp' => 'v=0\r\no=alice 2890844526 2890844526 IN IP4 127.0.0.1',
                    'type' => 'offer',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'signal_type' => 'offer',
                    'call_type' => 'video',
                    'conversation_id' => $conversation->id,
                ],
            ]);

        Event::assertDispatched(CallSignalEvent::class, function (CallSignalEvent $event) use ($conversation, $userA) {
            return $event->conversationId === $conversation->id
                && $event->senderId === $userA->id
                && $event->signalType === 'offer'
                && $event->callType === 'video'
                && isset($event->payload['sdp']);
        });
    }

    public function test_live_stream_comment_dispatches_broadcast_event(): void
    {
        Event::fake([LiveStreamCommentBroadcastEvent::class]);

        $creator = User::factory()->create();
        $viewer = User::factory()->create();

        $stream = app(LiveStreamingService::class)->createChannel($creator, 'Interactive Live Music');
        app(LiveStreamingService::class)->startStream($stream);

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/live/streams/{$stream->id}/comments", [
                'message' => 'Great performance!',
            ]);

        $response->assertStatus(201);

        Event::assertDispatched(LiveStreamCommentBroadcastEvent::class, function (LiveStreamCommentBroadcastEvent $event) use ($stream, $viewer) {
            return $event->channelId === $stream->channel_id
                && $event->userId === $viewer->id
                && $event->userName === $viewer->name
                && $event->comment === 'Great performance!';
        });
    }

    public function test_live_stream_gift_dispatches_broadcast_event(): void
    {
        Event::fake([LiveStreamGiftBroadcastEvent::class]);

        $creator = User::factory()->create();
        $viewer = User::factory()->create();

        $stream = app(LiveStreamingService::class)->createChannel($creator, 'Charity Live Stream');
        app(LiveStreamingService::class)->startStream($stream);

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/live/streams/{$stream->id}/gifts", [
                'gift_type' => 'rocket',
                'coins' => 100,
            ]);

        $response->assertStatus(201);

        Event::assertDispatched(LiveStreamGiftBroadcastEvent::class, function (LiveStreamGiftBroadcastEvent $event) use ($stream, $viewer) {
            return $event->channelId === $stream->channel_id
                && $event->senderId === $viewer->id
                && $event->senderName === $viewer->name
                && $event->giftType === 'rocket'
                && $event->giftAmount === 100;
        });
    }

    public function test_realtime_service_broadcast_methods(): void
    {
        Event::fake([
            CallSignalEvent::class,
            LiveStreamCommentBroadcastEvent::class,
            LiveStreamGiftBroadcastEvent::class,
        ]);

        $realtimeService = app(RealtimeServiceInterface::class);

        // Test broadcastCallSignal
        $realtimeService->broadcastCallSignal(101, 1, 'John Doe', 'candidate', 'video', ['candidate' => 'xyz']);
        Event::assertDispatched(CallSignalEvent::class);

        // Test broadcastLiveComment
        $realtimeService->broadcastLiveComment('chan_xyz', 1, 'John Doe', null, 'Hello live stream');
        Event::assertDispatched(LiveStreamCommentBroadcastEvent::class);

        // Test broadcastLiveGift
        $realtimeService->broadcastLiveGift('chan_xyz', 1, 'John Doe', null, 'rose', 50);
        Event::assertDispatched(LiveStreamGiftBroadcastEvent::class);
    }
}
