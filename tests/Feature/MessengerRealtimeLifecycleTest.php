<?php

namespace Tests\Feature;

use App\Events\ConversationClearedEvent;
use App\Events\MessageDeletedEvent;
use App\Events\MessageUpdatedEvent;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\PrivacySetting;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MessengerRealtimeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected CacheServiceInterface $cacheService;

    protected MessengerServiceInterface $messengerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(CacheServiceInterface::class);
        $this->messengerService = app(MessengerServiceInterface::class);
    }

    /**
     * Test multi-session heartbeat and offline presence lifecycle.
     */
    public function test_multi_session_heartbeat_and_offline_lifecycle(): void
    {
        $user = User::factory()->create();

        // Session 1 connects
        $resp1 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/presence/heartbeat', ['session_id' => 'device_tab_1']);

        $resp1->assertStatus(200)
            ->assertJsonPath('data.online', true)
            ->assertJsonPath('data.active_sessions', 1);

        $this->assertTrue($this->cacheService->isUserOnline($user->id));
        $this->assertEquals(1, $this->cacheService->getActiveSessionCount($user->id));

        // Session 2 connects (e.g. mobile or second tab)
        $resp2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/presence/heartbeat', ['session_id' => 'device_tab_2']);

        $resp2->assertStatus(200)
            ->assertJsonPath('data.online', true)
            ->assertJsonPath('data.active_sessions', 2);

        $this->assertEquals(2, $this->cacheService->getActiveSessionCount($user->id));

        // Disconnect Session 1: User should remain online because Session 2 is still active
        $respClose1 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/presence/offline', ['session_id' => 'device_tab_1']);

        $respClose1->assertStatus(200)
            ->assertJsonPath('data.online', true)
            ->assertJsonPath('data.active_sessions', 1);

        $this->assertTrue($this->cacheService->isUserOnline($user->id));

        // Disconnect Session 2: User should now transition to offline
        $respClose2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/presence/offline', ['session_id' => 'device_tab_2']);

        $respClose2->assertStatus(200)
            ->assertJsonPath('data.online', false)
            ->assertJsonPath('data.active_sessions', 0);

        $this->assertFalse($this->cacheService->isUserOnline($user->id));
    }

    /**
     * Test friend-only presence visibility and privacy controls.
     */
    public function test_presence_friend_only_visibility_and_privacy_controls(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $stranger = User::factory()->create();

        // Create accepted friendship between User A and User B
        Friendship::create([
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // User A goes online
        $this->cacheService->registerSessionHeartbeat($userA->id, 'session_a', 45);

        // Friend (User B) can inspect User A's presence
        $friendCheck = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/v1/presence/{$userA->id}");

        $friendCheck->assertStatus(200)
            ->assertJsonPath('data.online', true);

        // Stranger cannot view User A's presence (403 Forbidden)
        $strangerCheck = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/presence/{$userA->id}");

        $strangerCheck->assertStatus(403);

        // If User A sets privacy setting to hide online status
        PrivacySetting::updateOrCreate(
            ['user_id' => $userA->id],
            ['show_online_status' => false]
        );

        $hiddenCheck = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/v1/presence/{$userA->id}");

        $hiddenCheck->assertStatus(200)
            ->assertJsonPath('data.online', false);
    }

    /**
     * Test online friends endpoint returns only actual online friends.
     */
    public function test_online_friends_endpoint_returns_only_actual_online_friends(): void
    {
        $userA = User::factory()->create();
        $friendOnline = User::factory()->create(['name' => 'Online Friend']);
        $friendOffline = User::factory()->create(['name' => 'Offline Friend']);

        Friendship::create([
            'user_id' => $userA->id,
            'friend_id' => $friendOnline->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
        Friendship::create([
            'user_id' => $userA->id,
            'friend_id' => $friendOffline->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Register heartbeat only for friendOnline
        $this->cacheService->registerSessionHeartbeat($friendOnline->id, 'sess_fo', 45);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/friends/online');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $friendOnline->id);
    }

    /**
     * Test message edit authorization, version increment, and concurrency conflict.
     */
    public function test_message_edit_authorization_version_increment_and_concurrency_conflict(): void
    {
        Event::fake([MessageUpdatedEvent::class]);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $conv = $this->messengerService->getOrCreateDirectConversation($sender, $recipient->id);
        $message = $this->messengerService->sendMessage($sender, $conv, ['body' => 'Original text']);

        $this->assertEquals(1, $message->version);

        // Sender successfully edits message
        $editResp = $this->actingAs($sender, 'sanctum')
            ->patchJson("/api/v1/messages/{$message->id}", [
                'body' => 'Corrected text',
                'expected_version' => 1,
            ]);

        $editResp->assertStatus(200)
            ->assertJsonPath('data.body', 'Corrected text')
            ->assertJsonPath('data.is_edited', true)
            ->assertJsonPath('data.version', 2);

        $message->refresh();
        $this->assertEquals('Corrected text', $message->body);
        $this->assertEquals(2, $message->version);
        $this->assertTrue($message->is_edited);

        Event::assertDispatched(MessageUpdatedEvent::class, function ($event) use ($message) {
            return $event->messageId === $message->id && $event->version === 2;
        });

        // Concurrency conflict: client sends stale expected_version (1 when version is 2)
        $conflictResp = $this->actingAs($sender, 'sanctum')
            ->patchJson("/api/v1/messages/{$message->id}", [
                'body' => 'Stale overwriting attempt',
                'expected_version' => 1,
            ]);

        $conflictResp->assertStatus(409);

        // Non-sender cannot edit (403 Forbidden)
        $unauthResp = $this->actingAs($recipient, 'sanctum')
            ->patchJson("/api/v1/messages/{$message->id}", [
                'body' => 'Malicious edit',
                'expected_version' => 2,
            ]);

        $unauthResp->assertStatus(403);
    }

    /**
     * Test message delete for everyone soft-deletes/tombstones message and broadcasts event.
     */
    public function test_message_delete_for_everyone_tombstones_message_and_broadcasts(): void
    {
        Event::fake([MessageDeletedEvent::class]);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $conv = $this->messengerService->getOrCreateDirectConversation($sender, $recipient->id);
        $message = $this->messengerService->sendMessage($sender, $conv, ['body' => 'Confidential secret']);

        // Recipient cannot delete for everyone
        $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/messages/{$message->id}/delete-for-everyone")
            ->assertStatus(403);

        // Sender deletes for everyone
        $deleteResp = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/messages/{$message->id}/delete-for-everyone");

        $deleteResp->assertStatus(200)
            ->assertJsonPath('data.is_deleted', true)
            ->assertJsonPath('data.body', null);

        $message->refresh();
        $this->assertNotNull($message->deleted_at);
        $this->assertEquals($sender->id, $message->deleted_by);
        $this->assertNull($message->body);

        // Both sender and recipient see tombstone
        $respRecip = $this->actingAs($recipient, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages");

        $respRecip->assertStatus(200)
            ->assertJsonPath('data.0.is_deleted', true)
            ->assertJsonPath('data.0.body', 'This message was deleted.');

        // Editing a deleted message is blocked
        $this->actingAs($sender, 'sanctum')
            ->patchJson("/api/v1/messages/{$message->id}", ['body' => 'Revive'])
            ->assertStatus(400);

        Event::assertDispatched(MessageDeletedEvent::class, function ($event) use ($conv) {
            return $event->conversationId === $conv->id && $event->forEveryone === true;
        });
    }

    /**
     * Test message delete for me hides only for requesting user.
     */
    public function test_message_delete_for_me_isolates_removal_to_requesting_user(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $conv = $this->messengerService->getOrCreateDirectConversation($sender, $recipient->id);
        $message = $this->messengerService->sendMessage($sender, $conv, ['body' => 'Keep for recipient only']);

        // Sender deletes for me
        $delResp = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/messages/{$message->id}/delete-for-me");

        $delResp->assertStatus(200)
            ->assertJsonPath('data.deleted_for_me', true);

        // Sender's conversation message list no longer contains this message
        $senderMessages = $this->actingAs($sender, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages");

        $senderMessages->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Recipient's conversation message list STILL contains this message with full content
        $recipientMessages = $this->actingAs($recipient, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages");

        $recipientMessages->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', 'Keep for recipient only');
    }

    /**
     * Test clear chat history isolates deletion and preserves other participant copy.
     */
    public function test_clear_chat_history_isolates_deletion_and_preserves_other_participant_copy(): void
    {
        Event::fake([ConversationClearedEvent::class]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conv = $this->messengerService->getOrCreateDirectConversation($userA, $userB->id);
        $this->messengerService->sendMessage($userA, $conv, ['body' => 'Msg 1 from A']);
        $this->messengerService->sendMessage($userB, $conv, ['body' => 'Msg 2 from B']);

        // User A clears chat history
        $clearResp = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/clear");

        $clearResp->assertStatus(200)
            ->assertJsonPath('success', true);

        Event::assertDispatched(ConversationClearedEvent::class, function ($event) use ($conv, $userA) {
            return $event->conversationId === $conv->id && $event->userId === $userA->id;
        });

        // User A now sees 0 messages
        $messagesA = $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages");

        $messagesA->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // User B's copy remains completely intact (2 messages)
        $messagesB = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages");

        $messagesB->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Advance time to simulate user sending a message subsequently
        $this->travel(1)->second();

        // User A sends a new message after clearing
        $newMsg = $this->messengerService->sendMessage($userA, $conv, ['body' => 'New msg after clear']);

        // User A now sees only 1 message (the new one)
        $messagesAAfter = $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages");

        $messagesAAfter->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', 'New msg after clear');

        // User B sees 3 messages in total
        $messagesBAfter = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages");

        $messagesBAfter->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test conversation list resolves last visible message after clear and deletion.
     */
    public function test_conversation_list_resolves_last_visible_message_after_clear_and_deletion(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Establish friendship so conversations land in primary inbox
        Friendship::create([
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $conv = $this->messengerService->getOrCreateDirectConversation($userA, $userB->id);
        $msg1 = $this->messengerService->sendMessage($userA, $conv, ['body' => 'First message']);
        $msg2 = $this->messengerService->sendMessage($userB, $conv, ['body' => 'Second message']);

        // User A deletes msg2 for me
        $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/messages/{$msg2->id}/delete-for-me")
            ->assertStatus(200);

        // User A conversation list should resolve last visible message as msg1
        $listA = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/conversations');

        $listA->assertStatus(200)
            ->assertJsonPath('data.0.last_message.id', $msg1->id)
            ->assertJsonPath('data.0.last_message.body', 'First message');

        // User B conversation list still sees msg2 as the last message
        $listB = $this->actingAs($userB, 'sanctum')
            ->getJson('/api/v1/conversations');

        $listB->assertStatus(200)
            ->assertJsonPath('data.0.last_message.id', $msg2->id)
            ->assertJsonPath('data.0.last_message.body', 'Second message');
    }
}
