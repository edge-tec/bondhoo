<?php

namespace Tests\Feature;

use App\Events\MessageDeliveredEvent;
use App\Events\MessageReadEvent;
use App\Events\NewMessageEvent;
use App\Models\Conversation;
use App\Models\Media;
use App\Models\Message;
use App\Models\User;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * মেসেঞ্জার ফিচার টেস্ট:
 * ইনবক্স লিস্ট, ডিরেক্ট ও গ্রুপ চ্যাট, মেসেজ সেন্ড, মিডিয়া অ্যাটাচমেন্ট,
 * ডেলিভারি স্ট্যাটাস ও রিড রিসিট এন্ড-টু-এন্ড টেস্ট।
 */
class MessengerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_conversations(): void
    {
        $user = User::factory()->create();
        $other1 = User::factory()->create();
        $other2 = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $messengerService->getOrCreateDirectConversation($user, $other1->id);
        $messengerService->getOrCreateDirectConversation($user, $other2->id);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/conversations');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_create_direct_conversation_via_api(): void
    {
        $user = User::factory()->create();
        $recipient = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'recipient_id' => $recipient->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'direct')
            ->assertJsonPath('data.other_user.id', $recipient->id);

        $this->assertDatabaseHas('conversations', [
            'type' => 'direct',
        ]);
    }

    public function test_user_can_create_group_conversation_via_api(): void
    {
        $user = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'group',
                'title' => 'Design Sprint 2026',
                'participant_ids' => [$member1->id, $member2->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'group')
            ->assertJsonPath('data.title', 'Design Sprint 2026');

        $this->assertDatabaseHas('conversations', [
            'type' => 'group',
            'title' => 'Design Sprint 2026',
        ]);
    }

    public function test_non_participant_cannot_access_or_send_messages_in_conversation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $stranger = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        // Stranger tries to view conversation details -> 403
        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}")
            ->assertStatus(403);

        // Stranger tries to view messages -> 403
        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages")
            ->assertStatus(403);

        // Stranger tries to send message -> 403
        $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Intruder message',
            ])
            ->assertStatus(403);
    }

    public function test_user_can_send_text_message_and_broadcast_new_message_event(): void
    {
        Event::fake([NewMessageEvent::class]);

        $sender = User::factory()->create(['name' => 'Alice']);
        $recipient = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);

        $response = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Hey Bob, how are you?',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Hey Bob, how are you?')
            ->assertJsonPath('data.delivery_status', 'sent');

        // Check database
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id,
            'sender_id' => $sender->id,
            'body' => 'Hey Bob, how are you?',
            'delivery_status' => 'sent',
        ]);

        // Assert broadcast
        Event::assertDispatched(NewMessageEvent::class, function ($event) use ($conv) {
            return $event->conversationId === $conv->id
                && $event->messageData['body'] === 'Hey Bob, how are you?'
                && $event->broadcastAs() === 'message.new';
        });
    }

    public function test_user_can_send_message_with_media_attachment(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);

        // Create media record
        $media = Media::create([
            'user_id' => $sender->id,
            'collection' => 'general',
            'disk' => 'public',
            'original_path' => 'messages/sample.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 10240,
            'processing_status' => 'ready',
        ]);

        $response = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Check this photo out',
                'media_ids' => [$media->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.media.0.id', $media->id);

        // Check media is linked to message
        $this->assertEquals(Message::class, $media->fresh()->mediable_type);
        $this->assertEquals('message', $media->fresh()->collection);
    }

    public function test_mark_message_as_delivered_updates_status_and_broadcasts(): void
    {
        Event::fake([MessageDeliveredEvent::class]);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);
        $message = $messengerService->sendMessage($sender, $conv, ['body' => 'Are you there?']);

        $response = $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/messages/{$message->id}/delivered");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.delivery_status', 'delivered');

        $this->assertEquals('delivered', $message->fresh()->delivery_status);

        Event::assertDispatched(MessageDeliveredEvent::class, function ($event) use ($conv, $message) {
            return $event->conversationId === $conv->id
                && $event->messageId === $message->id;
        });
    }

    public function test_mark_conversation_as_read_updates_seen_and_broadcasts(): void
    {
        Event::fake([MessageReadEvent::class]);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);
        $message = $messengerService->sendMessage($sender, $conv, ['body' => 'Important update']);

        $response = $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/read");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.messages_marked_read', 1);

        $this->assertEquals('seen', $message->fresh()->delivery_status);

        Event::assertDispatched(MessageReadEvent::class, function ($event) use ($conv, $recipient) {
            return $event->conversationId === $conv->id
                && $event->readerId === $recipient->id;
        });
    }

    public function test_unread_count_endpoint(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $user->id);

        $messengerService->sendMessage($sender, $conv, ['body' => 'Msg 1']);
        $messengerService->sendMessage($sender, $conv, ['body' => 'Msg 2']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/messages/unread-count');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 2);
    }

    public function test_conversation_channel_authorization_checks_membership(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $stranger = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        $channels = Broadcast::getChannels();
        $conversationCallback = $channels->get('conversation.{conversationId}');
        $this->assertNotNull($conversationCallback);

        // Member of conversation should be authorized
        $this->assertTrue($conversationCallback($user1, $conv->id));
        $this->assertTrue($conversationCallback($user2, $conv->id));

        // Non-member stranger should be denied
        $this->assertFalse($conversationCallback($stranger, $conv->id));
    }
}
