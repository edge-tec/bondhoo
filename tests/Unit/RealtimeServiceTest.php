<?php

namespace Tests\Unit;

use App\Events\NewNotificationEvent;
use App\Events\PostReactionUpdatedEvent;
use App\Events\UserPresenceChangedEvent;
use App\Events\UserTypingEvent;
use App\Services\Contracts\RealtimeServiceInterface;
use App\Services\RealtimeService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * রিয়েল-টাইম সার্ভিস টেস্ট:
 * WebSocket ইভেন্ট ব্রডকাস্টিং এবং এর বিভিন্ন মেথড সঠিকভাবে কাজ করে কিনা তা যাচাই করে।
 */
class RealtimeServiceTest extends TestCase
{
    protected RealtimeServiceInterface $realtimeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->realtimeService = app(RealtimeServiceInterface::class);
    }

    public function test_broadcast_presence_dispatches_user_presence_changed_event(): void
    {
        Event::fake([UserPresenceChangedEvent::class]);

        $this->realtimeService->broadcastPresence(
            userId: 42,
            isOnline: true,
            lastSeen: '2026-09-16T19:00:00Z'
        );

        Event::assertDispatched(UserPresenceChangedEvent::class, function ($event) {
            return $event->userId === 42
                && $event->isOnline === true
                && $event->broadcastAs() === 'user.presence'
                && $event->broadcastOn()[0]->name === 'presence-users';
        });
    }

    public function test_broadcast_typing_dispatches_user_typing_event(): void
    {
        Event::fake([UserTypingEvent::class]);

        $this->realtimeService->broadcastTyping(
            conversationId: 10,
            userId: 5,
            userName: 'Rahim',
            isTyping: true
        );

        Event::assertDispatched(UserTypingEvent::class, function ($event) {
            return $event->conversationId === 10
                && $event->userId === 5
                && $event->userName === 'Rahim'
                && $event->isTyping === true
                && $event->broadcastAs() === 'user.typing'
                && $event->broadcastOn()[0]->name === 'private-conversation.10';
        });
    }

    public function test_broadcast_to_user_dispatches_new_notification_event(): void
    {
        Event::fake([NewNotificationEvent::class]);

        $payload = [
            'id' => 'uuid-123',
            'title' => 'New Follower',
            'message' => 'Karim followed you.',
        ];

        $this->realtimeService->broadcastToUser(7, 'notification.new', $payload);

        Event::assertDispatched(NewNotificationEvent::class, function ($event) use ($payload) {
            return $event->recipientId === 7
                && $event->notificationData === $payload
                && $event->broadcastAs() === 'notification.new'
                && $event->broadcastOn()[0]->name === 'private-user.7';
        });
    }

    public function test_broadcast_post_reaction_dispatches_post_reaction_updated_event(): void
    {
        Event::fake([PostReactionUpdatedEvent::class]);

        $this->realtimeService->broadcastPostReaction(
            postId: 100,
            totalReactions: 15,
            breakdown: ['like' => 10, 'love' => 5]
        );

        Event::assertDispatched(PostReactionUpdatedEvent::class, function ($event) {
            return $event->postId === 100
                && $event->totalReactions === 15
                && $event->breakdown['like'] === 10
                && $event->broadcastAs() === 'post.reaction.updated'
                && $event->broadcastOn()[0]->name === 'post.100';
        });
    }

    public function test_broadcast_handles_exceptions_gracefully_without_crashing(): void
    {
        // Even if an unexpected error occurs during broadcasting, it shouldn't crash the application
        $service = new RealtimeService;

        // Passing empty or mock payload should not throw uncaught exception
        $this->expectNotToPerformAssertions();
        $service->broadcast('invalid-channel', 'test.event', ['key' => 'value']);
    }
}
