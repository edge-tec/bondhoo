<?php

namespace Tests\Feature;

use App\Events\CallEnded;
use App\Events\CallInitiated;
use App\Events\CallParticipantUpdated;
use App\Events\CallResponded;
use App\Events\CallSignalSent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Friendship;
use App\Models\Media;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EnterpriseMessengerMasterVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test fetching message reactions list with user avatars and emojis.
     */
    public function test_can_get_message_reactions_list(): void
    {
        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        $msg = $messengerService->sendMessage($user1, $conv, [
            'body' => 'Hello Bob! How are you doing today?',
        ]);

        // Add reactions from both users
        MessageReaction::create([
            'message_id' => $msg->id,
            'user_id' => $user2->id,
            'reaction' => '❤️',
        ]);

        MessageReaction::create([
            'message_id' => $msg->id,
            'user_id' => $user1->id,
            'reaction' => '👍',
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->getJson("/api/v1/messages/{$msg->id}/reactions");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $data = $response->json('data');
        $this->assertEquals('👍', $data[0]['reaction']);
        $this->assertEquals('Alice', $data[0]['user']['name']);
        $this->assertEquals('❤️', $data[1]['reaction']);
        $this->assertEquals('Bob', $data[1]['user']['name']);
    }

    /**
     * Test deleting a direct conversation.
     */
    public function test_can_delete_direct_conversation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        $response = $this->actingAs($user1, 'sanctum')
            ->deleteJson("/api/v1/conversations/{$conv->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Assert participant is cleared and archived
        $part = ConversationParticipant::where('conversation_id', $conv->id)
            ->where('user_id', $user1->id)
            ->first();

        $this->assertNotNull($part->cleared_at);
        $this->assertTrue((bool) $part->is_archived);
    }

    /**
     * Test deleting a group conversation (leaves or deletes).
     */
    public function test_can_delete_group_conversation_as_member(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $conv = Conversation::create([
            'type' => 'group',
            'title' => 'Developers Hub',
            'creator_id' => $admin->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($member, 'sanctum')
            ->deleteJson("/api/v1/conversations/{$conv->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('conversation_participants', [
            'conversation_id' => $conv->id,
            'user_id' => $member->id,
        ]);
    }

    /**
     * Test mute durations (1h, 8h, 24h, forever, unmute).
     */
    public function test_mute_conversation_durations(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        // Mute 1 hour
        $r1 = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/mute", ['duration' => '1h']);
        $r1->assertStatus(200)->assertJsonPath('data.is_muted', true);

        // Mute forever
        $rForever = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/mute", ['duration' => 'forever']);
        $rForever->assertStatus(200)->assertJsonPath('data.is_muted', true);

        // Unmute
        $rUnmute = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/mute", ['duration' => 'unmute']);
        $rUnmute->assertStatus(200)->assertJsonPath('data.is_muted', false);
    }

    /**
     * Test pin, archive, unread, and clear chat.
     */
    public function test_pin_archive_unread_and_clear_chat(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        // Toggle Pin
        $rPin = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/pin");
        $rPin->assertStatus(200)->assertJsonPath('data.is_pinned', true);

        // Toggle Archive
        $rArch = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/archive");
        $rArch->assertStatus(200)->assertJsonPath('data.is_archived', true);

        // Mark as Unread
        $rUnread = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/unread");
        $rUnread->assertStatus(200)->assertJsonPath('success', true);

        // Clear Chat
        $rClear = $this->actingAs($user1, 'sanctum')
            ->deleteJson("/api/v1/conversations/{$conv->id}/clear");
        $rClear->assertStatus(200)->assertJsonPath('success', true);
    }

    /**
     * Test group member management.
     */
    public function test_group_member_management(): void
    {
        $admin = User::factory()->create();
        $member1 = User::factory()->create();
        $newMember = User::factory()->create();

        $conv = Conversation::create([
            'type' => 'group',
            'title' => 'Designers Club',
            'creator_id' => $admin->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $member1->id,
            'role' => 'member',
        ]);

        // Add new member
        $rAdd = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/members", [
                'user_ids' => [$newMember->id],
            ]);
        $rAdd->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conv->id,
            'user_id' => $newMember->id,
        ]);

        // Remove member
        $rRemove = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/conversations/{$conv->id}/members/{$newMember->id}");
        $rRemove->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseMissing('conversation_participants', [
            'conversation_id' => $conv->id,
            'user_id' => $newMember->id,
        ]);

        // Member leaves
        $rLeave = $this->actingAs($member1, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/leave");
        $rLeave->assertStatus(200)->assertJsonPath('success', true);
    }

    /**
     * Test WebRTC ICE servers, Call initiation, Signaling, and History.
     */
    public function test_webrtc_calling_infrastructure(): void
    {
        Event::fake([
            CallInitiated::class,
            CallEnded::class,
            CallResponded::class,
            CallSignalSent::class,
            CallParticipantUpdated::class,
        ]);

        $caller = User::factory()->create(['name' => 'John']);
        $receiver = User::factory()->create(['name' => 'Sara']);

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($caller, $receiver->id);

        // 1. Fetch ICE servers
        $iceRes = $this->actingAs($caller, 'sanctum')
            ->getJson('/api/v1/calls/ice-servers');
        $iceRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['ice_servers']]);

        // 2. Initiate Call
        $callRes = $this->actingAs($caller, 'sanctum')
            ->postJson('/api/v1/calls', [
                'conversation_id' => $conv->id,
                'call_type' => 'video',
            ]);
        $callRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $callId = $callRes->json('data.id');

        // 3. Send WebRTC signal (offer)
        $signalRes = $this->actingAs($caller, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/signal", [
                'signal_type' => 'offer',
                'payload' => ['sdp' => 'v=0\r\no=mock 12345 12345 IN IP4 127.0.0.1...'],
                'target_user_id' => $receiver->id,
            ]);
        $signalRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 4. Respond (accept)
        $respondRes = $this->actingAs($receiver, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/respond", [
                'action' => 'accept',
            ]);
        $respondRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 5. Update participant state (mute mic)
        $stateRes = $this->actingAs($caller, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/state", [
                'is_muted' => true,
            ]);
        $stateRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 6. Leave call
        $leaveRes = $this->actingAs($caller, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/leave");
        $leaveRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 7. Get call history
        $historyRes = $this->actingAs($caller, 'sanctum')
            ->getJson('/api/v1/calls/history');
        $historyRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test web route /messages renders HTML with full interface and action buttons.
     */
    public function test_messages_web_view_renders_fully(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        $response = $this->actingAs($user1)
            ->get('/messages?conversation_id='.$conv->id);

        $response->assertStatus(200)
            ->assertSee('চ্যাট আর্কাইভ করুন')
            ->assertSee('অপঠিত হিসেবে চিহ্নিত করুন')
            ->assertSee('কল হিস্ট্রি দেখুন')
            ->assertSee('কনভার্সেশন ডিলিট করুন')
            ->assertSee('muteConversationModal')
            ->assertSee('addMemberModal')
            ->assertSee('reactionsListModal')
            ->assertSee('callHistoryModal')
            ->assertSee('toastNotification')
            ->assertSee('webrtcCallModal')
            ->assertSee('incomingCallModal')
            ->assertSee('detailsDrawerBackdrop')
            ->assertSee('details-drawer-header')
            ->assertSee('btn-mobile-back')
            ->assertSee('chat-composer-wrapper')
            ->assertSee('btn-send-message');
    }

    /**
     * Test message submission idempotency via client_message_id.
     */
    public function test_message_idempotency_via_client_message_id(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        $clientMessageId = 'client-msg-uuid-987654321';

        // First attempt
        $res1 = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Hello idempotency test!',
                'client_message_id' => $clientMessageId,
            ]);

        $res1->assertStatus(201);
        $msgId1 = $res1->json('data.id');

        // Second attempt with exact same client_message_id (e.g. network retry)
        $res2 = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Hello idempotency test!',
                'client_message_id' => $clientMessageId,
            ]);

        $res2->assertStatus(201);
        $msgId2 = $res2->json('data.id');

        $this->assertEquals($msgId1, $msgId2);
        $this->assertEquals(1, Message::where('conversation_id', $conv->id)->count());
    }

    /**
     * Test group creator cannot be removed or demoted by another admin.
     */
    public function test_group_creator_cannot_be_removed_or_demoted(): void
    {
        $creator = User::factory()->create();
        $admin = User::factory()->create();

        $conv = Conversation::create([
            'type' => 'group',
            'title' => 'Executive Committee',
            'creator_id' => $creator->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $creator->id,
            'role' => 'admin',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);

        // Admin attempts to demote creator
        $rDemote = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/conversations/{$conv->id}/members/{$creator->id}", [
                'role' => 'member',
            ]);
        $rDemote->assertStatus(403);

        // Admin attempts to remove creator
        $rRemove = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/conversations/{$conv->id}/members/{$creator->id}");
        $rRemove->assertStatus(403);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conv->id,
            'user_id' => $creator->id,
            'role' => 'admin',
        ]);
    }

    /**
     * Test path traversal attacks on attachment download are blocked.
     */
    public function test_path_traversal_on_attachment_download_is_blocked(): void
    {
        $user = User::factory()->create();

        // Malicious media with relative path traversal
        $maliciousMedia = Media::create([
            'user_id' => $user->id,
            'collection' => 'message',
            'disk' => 'public',
            'original_path' => '../../../../etc/passwd',
            'mime_type' => 'text/plain',
            'size' => 1024,
            'processing_status' => 'ready',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/attachments/{$maliciousMedia->id}/download");

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /**
     * Test blocked users cannot message or call each other in direct conversation.
     */
    public function test_blocked_user_cannot_initiate_call_or_message(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        // User A blocks User B
        Friendship::create([
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);

        // User B attempts to send message to User A
        $rMsg = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Are you there?',
            ]);
        $rMsg->assertStatus(403);

        // User B attempts to initiate call to User A
        $rCall = $this->actingAs($userB, 'sanctum')
            ->postJson('/api/v1/calls', [
                'conversation_id' => $conv->id,
                'call_type' => 'audio',
            ]);
        $rCall->assertStatus(403);
    }

    /**
     * Test non-participant cannot mark message as delivered.
     */
    public function test_non_participant_cannot_mark_message_delivered(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $stranger = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        $msg = $messengerService->sendMessage($user1, $conv, [
            'body' => 'Confidential note',
        ]);

        $response = $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/messages/{$msg->id}/delivered");

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
