<?php

namespace Tests\Feature;

use App\Events\MessageDeliveredEvent;
use App\Events\MessageReadEvent;
use App\Events\NewMessageEvent;
use App\Models\Conversation;
use App\Models\Media;
use App\Models\Message;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Contracts\MessengerServiceInterface;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Phase8CompleteMessagingAndChatTest extends TestCase
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
            'first_name' => $user->first_name ?? 'Chat',
            'last_name' => $user->last_name ?? 'User',
        ]);

        return $user;
    }

    /**
     * Requirement 8.1: Create 1-on-1 direct conversation and exchange text messages with broadcasting.
     */
    public function test_1_on_1_direct_conversation_and_messaging(): void
    {
        Event::fake([NewMessageEvent::class]);

        $alice = $this->createUser(['name' => 'Alice']);
        $bob = $this->createUser(['name' => 'Bob']);

        // Alice creates direct conversation with Bob
        $convResponse = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'recipient_id' => $bob->id,
            ]);

        $convResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'direct')
            ->assertJsonPath('data.other_user.id', $bob->id);

        $convId = $convResponse->json('data.id');

        // Alice sends message to Bob
        $msgResponse = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/messages", [
                'body' => 'Hello Bob! Welcome to Bondhoo chat.',
            ]);

        $msgResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Hello Bob! Welcome to Bondhoo chat.')
            ->assertJsonPath('data.delivery_status', 'sent');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $convId,
            'sender_id' => $alice->id,
            'body' => 'Hello Bob! Welcome to Bondhoo chat.',
        ]);

        Event::assertDispatched(NewMessageEvent::class, function ($event) use ($convId) {
            return $event->conversationId === $convId
                && $event->messageData['body'] === 'Hello Bob! Welcome to Bondhoo chat.'
                && $event->broadcastAs() === 'message.new';
        });

        // Bob fetches messages and verifies Alice's message is present
        $bobListResponse = $this->actingAs($bob, 'sanctum')
            ->getJson("/api/v1/conversations/{$convId}/messages");

        $bobListResponse->assertStatus(200);
        $this->assertCount(1, $bobListResponse->json('data'));
        $this->assertEquals('Hello Bob! Welcome to Bondhoo chat.', $bobListResponse->json('data.0.body'));
    }

    /**
     * Requirement 8.2: Blocked user cannot initiate conversation or send messages.
     */
    public function test_blocked_user_cannot_initiate_conversation(): void
    {
        $alice = $this->createUser(['name' => 'Alice']);
        $spammer = $this->createUser(['name' => 'Spammer']);

        // Alice blocks Spammer
        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/users/{$spammer->id}/block")
            ->assertStatus(200);

        // Spammer tries to create direct chat with Alice -> 403 Forbidden
        $response = $this->actingAs($spammer, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'recipient_id' => $alice->id,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Requirement 8.3: Group Conversations Lifecycle (Create, Add Members, Remove, Leave).
     */
    public function test_group_conversation_lifecycle(): void
    {
        $admin = $this->createUser(['name' => 'Group Admin']);
        $member1 = $this->createUser(['name' => 'Member One']);
        $member2 = $this->createUser(['name' => 'Member Two']);
        $newMember = $this->createUser(['name' => 'New Member']);

        // 1. Create group
        $createRes = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'group',
                'title' => 'Product Engineering Squad',
                'description' => 'Discussion for engineering team',
                'participant_ids' => [$member1->id, $member2->id],
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Product Engineering Squad');

        $convId = $createRes->json('data.id');

        // Verify admin role and member participants
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $convId,
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $convId,
            'user_id' => $member1->id,
            'role' => 'member',
        ]);

        // 2. Admin adds a new member
        $addRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/members", [
                'user_ids' => [$newMember->id],
            ]);
        $addRes->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $convId,
            'user_id' => $newMember->id,
        ]);

        // 3. Admin removes member2
        $removeRes = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/conversations/{$convId}/members/{$member2->id}");
        $removeRes->assertStatus(200);

        $this->assertDatabaseMissing('conversation_participants', [
            'conversation_id' => $convId,
            'user_id' => $member2->id,
        ]);

        // 4. Member1 leaves the group
        $leaveRes = $this->actingAs($member1, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/leave");
        $leaveRes->assertStatus(200);

        $this->assertDatabaseMissing('conversation_participants', [
            'conversation_id' => $convId,
            'user_id' => $member1->id,
        ]);
    }

    /**
     * Requirement 8.4: Send message with media attachments.
     */
    public function test_send_message_with_media_attachment(): void
    {
        $sender = $this->createUser();
        $recipient = $this->createUser();

        $conv = app(MessengerServiceInterface::class)
            ->getOrCreateDirectConversation($sender, $recipient->id);

        $media = Media::create([
            'user_id' => $sender->id,
            'collection' => 'general',
            'disk' => 'public',
            'original_path' => 'messages/preview.png',
            'mime_type' => 'image/png',
            'size' => 20480,
            'processing_status' => 'ready',
        ]);

        $res = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Look at this design screenshot',
                'media_ids' => [$media->id],
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.media.0.id', $media->id);

        $this->assertEquals(Message::class, $media->fresh()->mediable_type);
        $this->assertEquals('message', $media->fresh()->collection);
    }

    /**
     * Requirement 8.5: Edit message with optimistic authorization.
     */
    public function test_edit_message_authorization(): void
    {
        $author = $this->createUser();
        $recipient = $this->createUser();
        $stranger = $this->createUser();

        $conv = app(MessengerServiceInterface::class)
            ->getOrCreateDirectConversation($author, $recipient->id);

        $message = app(MessengerServiceInterface::class)
            ->sendMessage($author, $conv, ['body' => 'Original text message']);

        // Stranger attempts edit -> 400/403
        $strangerRes = $this->actingAs($stranger, 'sanctum')
            ->putJson("/api/v1/messages/{$message->id}", [
                'body' => 'Tampered text',
            ]);
        $this->assertTrue(in_array($strangerRes->status(), [400, 403], true));

        // Author edits message -> 200 Success
        $authorRes = $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/messages/{$message->id}", [
                'body' => 'Corrected text message by author',
            ]);
        $authorRes->assertStatus(200)
            ->assertJsonPath('data.body', 'Corrected text message by author');

        $this->assertEquals('Corrected text message by author', $message->fresh()->body);
    }

    /**
     * Requirement 8.6: Delete for me vs Delete for everyone.
     */
    public function test_delete_for_me_and_delete_for_everyone(): void
    {
        $sender = $this->createUser();
        $recipient = $this->createUser();

        $conv = app(MessengerServiceInterface::class)
            ->getOrCreateDirectConversation($sender, $recipient->id);

        $msg1 = app(MessengerServiceInterface::class)
            ->sendMessage($sender, $conv, ['body' => 'Delete me privately']);
        $msg2 = app(MessengerServiceInterface::class)
            ->sendMessage($sender, $conv, ['body' => 'Delete me for everyone']);

        // 1. Sender deletes msg1 for me
        $delMeRes = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/messages/{$msg1->id}/delete-for-me");
        $delMeRes->assertStatus(200);

        // Recipient can still see msg1
        $recipientList = $this->actingAs($recipient, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages");
        $recipientIds = collect($recipientList->json('data'))->pluck('id')->all();
        $this->assertContains($msg1->id, $recipientIds);

        // Sender no longer sees msg1 in their message list
        $senderList = $this->actingAs($sender, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages");
        $senderIds = collect($senderList->json('data'))->pluck('id')->all();
        $this->assertNotContains($msg1->id, $senderIds);

        // 2. Sender deletes msg2 for everyone
        $delEveryoneRes = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/messages/{$msg2->id}/delete-for-everyone");
        $delEveryoneRes->assertStatus(200);

        $this->assertTrue((bool) $msg2->fresh()->is_deleted);
        $this->assertNull($msg2->fresh()->body);
    }

    /**
     * Requirement 8.7: Message Reactions.
     */
    public function test_message_reaction(): void
    {
        $sender = $this->createUser();
        $recipient = $this->createUser();

        $conv = app(MessengerServiceInterface::class)
            ->getOrCreateDirectConversation($sender, $recipient->id);

        $msg = app(MessengerServiceInterface::class)
            ->sendMessage($sender, $conv, ['body' => 'High-five!']);

        // Recipient reacts with 'heart'
        $reactRes = $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/messages/{$msg->id}/reactions", [
                'reaction' => '❤️',
            ]);
        $reactRes->assertStatus(200)->assertJsonPath('success', true);

        // Get reactions
        $listRes = $this->actingAs($sender, 'sanctum')
            ->getJson("/api/v1/messages/{$msg->id}/reactions");
        $listRes->assertStatus(200);
        $this->assertCount(1, $listRes->json('data'));
        $this->assertEquals('❤️', $listRes->json('data.0.reaction'));
    }

    /**
     * Requirement 8.8: Delivery and Seen tracking (Read Receipts).
     */
    public function test_delivery_and_read_receipts(): void
    {
        Event::fake([MessageDeliveredEvent::class, MessageReadEvent::class]);

        $sender = $this->createUser();
        $recipient = $this->createUser();

        $conv = app(MessengerServiceInterface::class)
            ->getOrCreateDirectConversation($sender, $recipient->id);

        $msg = app(MessengerServiceInterface::class)
            ->sendMessage($sender, $conv, ['body' => 'Tracking test']);

        // 1. Mark as delivered
        $delivRes = $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/messages/{$msg->id}/delivered");
        $delivRes->assertStatus(200)
            ->assertJsonPath('data.delivery_status', 'delivered');

        Event::assertDispatched(MessageDeliveredEvent::class);

        // 2. Mark conversation as read
        $readRes = $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/read");
        $readRes->assertStatus(200);

        $this->assertEquals('seen', $msg->fresh()->delivery_status);
        Event::assertDispatched(MessageReadEvent::class);
    }

    /**
     * Requirement 8.9: Real-time typing indicators with authorization.
     */
    public function test_typing_indicators_with_authorization(): void
    {
        $alice = $this->createUser();
        $bob = $this->createUser();
        $stranger = $this->createUser();

        $conv = app(MessengerServiceInterface::class)
            ->getOrCreateDirectConversation($alice, $bob->id);

        // Stranger tries to send typing in this conversation -> 403 Forbidden
        $strangerRes = $this->actingAs($stranger, 'sanctum')
            ->postJson('/api/v1/presence/typing', [
                'conversation_id' => $conv->id,
                'is_typing' => true,
            ]);
        $strangerRes->assertStatus(403);

        // Alice starts typing
        $aliceRes = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/presence/typing', [
                'conversation_id' => $conv->id,
                'is_typing' => true,
            ]);
        $aliceRes->assertStatus(200)
            ->assertJsonPath('data.is_typing', true);

        // Bob checks typing users
        $getTypingRes = $this->actingAs($bob, 'sanctum')
            ->getJson("/api/v1/presence/typing/{$conv->id}");
        $getTypingRes->assertStatus(200);
        $typingUsers = $getTypingRes->json('data.typing_users');
        $this->assertCount(1, $typingUsers);
        $this->assertEquals($alice->id, $typingUsers[0]['id']);

        // Alice stops typing
        $stopRes = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/presence/typing', [
                'conversation_id' => $conv->id,
                'is_typing' => false,
            ]);
        $stopRes->assertStatus(200)
            ->assertJsonPath('data.is_typing', false);

        // Bob checks typing users again -> empty
        $emptyTypingRes = $this->actingAs($bob, 'sanctum')
            ->getJson("/api/v1/presence/typing/{$conv->id}");
        $this->assertEmpty($emptyTypingRes->json('data.typing_users'));
    }

    /**
     * Requirement 8.10: WebSocket conversation channel membership authorization.
     */
    public function test_conversation_websocket_channel_authorization(): void
    {
        $alice = $this->createUser();
        $bob = $this->createUser();
        $stranger = $this->createUser();

        $conv = app(MessengerServiceInterface::class)
            ->getOrCreateDirectConversation($alice, $bob->id);

        $channels = Broadcast::getChannels();
        $channelCallback = $channels->get('conversation.{conversationId}');
        $this->assertNotNull($channelCallback);

        // Participants are authorized
        $this->assertTrue((bool) $channelCallback($alice, $conv->id));
        $this->assertTrue((bool) $channelCallback($bob, $conv->id));

        // Stranger is rejected
        $this->assertFalse((bool) $channelCallback($stranger, $conv->id));
    }
}
