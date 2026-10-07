<?php

namespace Tests\Feature;

use App\Events\NewNotificationEvent;
use App\Events\PostReactionUpdatedEvent;
use App\Events\UserPresenceChangedEvent;
use App\Events\UserTypingEvent;
use App\Jobs\DispatchNotificationJob;
use App\Models\Post;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * রিয়েল-টাইম ফিচার টেস্ট:
 * প্রেজেন্স হার্টবিট, টাইপিং ইন্ডিকেটর, পোস্ট রিঅ্যাকশন এবং নোটিফিকেশন ব্রডকাস্টিং এন্ড-টু-এন্ড টেস্ট।
 */
class RealtimeBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_heartbeat_updates_redis_and_broadcasts_presence_event(): void
    {
        Event::fake([UserPresenceChangedEvent::class]);

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/presence/heartbeat');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.online', true);

        // Verify event was broadcast
        Event::assertDispatched(UserPresenceChangedEvent::class, function ($event) use ($user) {
            return $event->userId === $user->id && $event->isOnline === true;
        });

        // Verify Redis cache was set
        $cacheService = app(CacheServiceInterface::class);
        $this->assertTrue($cacheService->isUserOnline($user->id));
    }

    public function test_typing_updates_redis_and_broadcasts_typing_event(): void
    {
        Event::fake([UserTypingEvent::class]);

        $user = User::factory()->create(['name' => 'Mizanur Rahman']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/presence/typing', [
                'conversation_id' => 99,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_typing', true);

        Event::assertDispatched(UserTypingEvent::class, function ($event) use ($user) {
            return $event->conversationId === 99
                && $event->userId === $user->id
                && $event->userName === 'Mizanur Rahman'
                && $event->isTyping === true;
        });
    }

    public function test_post_reaction_broadcasts_live_counter_update_event(): void
    {
        Event::fake([PostReactionUpdatedEvent::class]);

        $author = User::factory()->create();
        $reactor = User::factory()->create();
        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Post to react to',
            'audience' => 'public',
        ]);

        $response = $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", [
                'type' => 'love',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.reacted', true)
            ->assertJsonPath('data.type', 'love');

        Event::assertDispatched(PostReactionUpdatedEvent::class, function ($event) use ($post) {
            return $event->postId === $post->id
                && $event->totalReactions === 1
                && isset($event->breakdown['love']);
        });
    }

    public function test_dispatch_notification_job_broadcasts_to_user_private_channel(): void
    {
        Event::fake([NewNotificationEvent::class]);

        $recipient = User::factory()->create();

        $job = new DispatchNotificationJob(
            recipientId: $recipient->id,
            type: 'notification.mention',
            data: [
                'title' => 'Mentioned in a post',
                'message' => 'You were tagged in a discussion.',
            ],
            channels: ['database']
        );

        $job->handle(
            app(CacheServiceInterface::class),
            app(QueueServiceInterface::class),
            app(RealtimeServiceInterface::class)
        );

        Event::assertDispatched(NewNotificationEvent::class, function ($event) use ($recipient) {
            return $event->recipientId === $recipient->id
                && $event->notificationData['type'] === 'notification.mention'
                && $event->broadcastAs() === 'notification.new';
        });
    }

    public function test_user_channel_authorization_callback_logic(): void
    {
        // Testing channel authorization rules configured in routes/channels.php
        $user1 = User::factory()->create(['id' => 10]);
        $user2 = User::factory()->create(['id' => 20]);

        // Get channel callbacks from Broadcast manager
        $channels = Broadcast::getChannels();

        // 1. Test user.{id} channel rule
        $userChannelCallback = $channels->get('user.{id}');
        $this->assertNotNull($userChannelCallback);

        // User 10 accessing user.10 channel should be authorized
        $this->assertTrue($userChannelCallback($user1, 10));

        // User 20 accessing user.10 channel should be denied
        $this->assertFalse($userChannelCallback($user2, 10));

        // 2. Test presence-users channel rule
        $presenceChannelCallback = $channels->get('presence-users');
        $this->assertNotNull($presenceChannelCallback);

        $presenceData = $presenceChannelCallback($user1);
        $this->assertEquals($user1->id, $presenceData['id']);
        $this->assertEquals($user1->username, $presenceData['username']);
    }
}
