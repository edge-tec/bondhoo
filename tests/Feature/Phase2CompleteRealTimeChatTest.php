<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\MessageDeliveredEvent;
use App\Events\MessageReadEvent;
use App\Events\NewMessageEvent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use App\Services\Contracts\MessengerServiceInterface;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Phase2CompleteRealTimeChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(string $username, string $name): User
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

        return $user;
    }

    /**
     * 1. Test 1-on-1 Direct Messaging Lifecycle with Real-Time Broadcasting
     */
    public function test_one_to_one_direct_messaging_lifecycle(): void
    {
        Event::fake([NewMessageEvent::class]);

        $alice = $this->createUser('alice_p2', 'Alice Johnson');
        $bob = $this->createUser('bob_p2', 'Bob Smith');

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
                'body' => 'Hello Bob! This is Alice on Connectly Real-Time Chat.',
            ]);

        $msgResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Hello Bob! This is Alice on Connectly Real-Time Chat.')
            ->assertJsonPath('data.delivery_status', 'sent');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $convId,
            'sender_id' => $alice->id,
            'body' => 'Hello Bob! This is Alice on Connectly Real-Time Chat.',
        ]);

        Event::assertDispatched(NewMessageEvent::class, function ($event) use ($convId) {
            return $event->conversationId === $convId
                && $event->messageData['body'] === 'Hello Bob! This is Alice on Connectly Real-Time Chat.';
        });

        // Bob fetches conversation messages
        $bobListResponse = $this->actingAs($bob, 'sanctum')
            ->getJson("/api/v1/conversations/{$convId}/messages");

        $bobListResponse->assertStatus(200);
        $this->assertCount(1, $bobListResponse->json('data'));
        $this->assertEquals('Hello Bob! This is Alice on Connectly Real-Time Chat.', $bobListResponse->json('data.0.body'));
    }

    /**
     * 2. Test Group Conversation Creation and Multi-User Messaging
     */
    public function test_group_conversation_creation_and_messaging(): void
    {
        $admin = $this->createUser('admin_p2', 'Group Admin');
        $member1 = $this->createUser('member1_p2', 'Member One');
        $member2 = $this->createUser('member2_p2', 'Member Two');

        // Create group conversation
        $createRes = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'group',
                'title' => 'Connectly Core Architecture Team',
                'participant_ids' => [$member1->id, $member2->id],
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Connectly Core Architecture Team');

        $convId = $createRes->json('data.id');

        // Member1 sends a message to the group
        $msgRes = $this->actingAs($member1, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/messages", [
                'body' => 'Welcome to the team chat!',
            ]);

        $msgRes->assertStatus(201);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $convId,
            'sender_id' => $member1->id,
            'body' => 'Welcome to the team chat!',
        ]);
    }

    /**
     * 3. Test Message Reactions, Replying, and Editing
     */
    public function test_message_reactions_replying_and_editing(): void
    {
        $alice = $this->createUser('alice_act', 'Alice Act');
        $bob = $this->createUser('bob_act', 'Bob Act');

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($alice, $bob->id);
        $msg = $messengerService->sendMessage($alice, $conv, ['body' => 'Initial Discussion Point']);

        // 1. Bob reacts with heart emoji
        $reactRes = $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/messages/{$msg->id}/reactions", [
                'reaction' => '❤️',
            ]);
        $reactRes->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $msg->id,
            'user_id' => $bob->id,
            'reaction' => '❤️',
        ]);

        // 2. Alice edits her message
        $editRes = $this->actingAs($alice, 'sanctum')
            ->putJson("/api/v1/messages/{$msg->id}", [
                'body' => 'Updated Discussion Point (Refined)',
            ]);
        $editRes->assertStatus(200);

        $msg->refresh();
        $this->assertSame('Updated Discussion Point (Refined)', $msg->body);
        $this->assertTrue((bool) $msg->is_edited);

        // 3. Bob replies to Alice's message
        $replyRes = $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Acknowledged and confirmed!',
                'reply_to_id' => $msg->id,
            ]);
        $replyRes->assertStatus(201);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id,
            'sender_id' => $bob->id,
            'reply_to_message_id' => $msg->id,
        ]);
    }

    /**
     * 4. Test Delivery Status, Seen Indicators, and Read Receipts
     */
    public function test_delivery_status_and_read_receipts(): void
    {
        Event::fake([MessageDeliveredEvent::class, MessageReadEvent::class]);

        $sender = $this->createUser('sender_d', 'Sender D');
        $recipient = $this->createUser('recipient_d', 'Recipient D');

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);
        $msg = $messengerService->sendMessage($sender, $conv, ['body' => 'Tracking Delivery & Read']);

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
     * 5. Test Real-time Typing Indicators and Authorization
     */
    public function test_typing_indicators_and_presence(): void
    {
        $alice = $this->createUser('alice_typ', 'Alice Typ');
        $bob = $this->createUser('bob_typ', 'Bob Typ');

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($alice, $bob->id);

        // Alice starts typing
        $typingRes = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/presence/typing', [
                'conversation_id' => $conv->id,
                'is_typing' => true,
            ]);
        $typingRes->assertStatus(200)
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
    }

    /**
     * 6. Test WebSocket Private Channel Authorization
     */
    public function test_conversation_websocket_channel_authorization(): void
    {
        $alice = $this->createUser('alice_ws', 'Alice WS');
        $bob = $this->createUser('bob_ws', 'Bob WS');
        $stranger = $this->createUser('stranger_ws', 'Stranger WS');

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($alice, $bob->id);

        $channels = Broadcast::getChannels();
        $channelCallback = $channels->get('conversation.{conversationId}');
        $this->assertNotNull($channelCallback);

        // Valid participants are authorized
        $this->assertTrue((bool) $channelCallback($alice, $conv->id));
        $this->assertTrue((bool) $channelCallback($bob, $conv->id));

        // Stranger is rejected
        $this->assertFalse((bool) $channelCallback($stranger, $conv->id));
    }
}
