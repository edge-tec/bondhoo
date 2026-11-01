<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use App\Services\Messenger\SyncEventService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConnectlyPhase6EnterpriseMessengerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    /**
     * 1. One-to-one direct conversation creation, details and message history.
     */
    public function test_one_to_one_direct_chat_and_history(): void
    {
        $user1 = User::factory()->create(['name' => 'Tanvir Hasan']);
        $user2 = User::factory()->create(['name' => 'Sadia Islam']);

        Sanctum::actingAs($user1);

        // 1. Create or retrieve direct conversation
        $convRes = $this->postJson('/api/v1/conversations', [
            'type' => 'direct',
            'recipient_id' => $user2->id,
        ]);

        $convRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $convId = $convRes->json('data.id');
        $this->assertDatabaseHas('conversations', [
            'id' => $convId,
            'type' => 'direct',
        ]);

        // 2. Send message from User 1
        $sendRes = $this->postJson("/api/v1/conversations/{$convId}/messages", [
            'body' => 'Assalamu Alaikum Sadia! How is the project going?',
            'client_message_id' => 'client_msg_101',
            'type' => 'text',
        ]);

        $sendRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Assalamu Alaikum Sadia! How is the project going?')
            ->assertJsonPath('data.is_mine', true);

        $msgId = $sendRes->json('data.id');

        // 3. User 2 logs in and views messages
        Sanctum::actingAs($user2);
        $historyRes = $this->getJson("/api/v1/conversations/{$convId}/messages");
        $historyRes->assertOk()
            ->assertJsonPath('success', true);

        $historyIds = collect($historyRes->json('data'))->pluck('id')->all();
        $this->assertContains($msgId, $historyIds);
    }

    /**
     * 2. Group chat creation, info update, member addition, role management and leave.
     */
    public function test_group_chat_lifecycle_and_member_management(): void
    {
        $creator = User::factory()->create(['name' => 'Fahim Ahmed']);
        $member1 = User::factory()->create(['name' => 'Nusrat Jahan']);
        $member2 = User::factory()->create(['name' => 'Kamal Hossain']);

        Sanctum::actingAs($creator);

        // 1. Create Group Conversation
        $groupRes = $this->postJson('/api/v1/conversations', [
            'type' => 'group',
            'title' => 'Connectly Mobile Architecture Team',
            'description' => 'Discussions regarding Connectly native android app',
            'participant_ids' => [$member1->id],
        ]);

        $groupRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Connectly Mobile Architecture Team');

        $convId = $groupRes->json('data.id');

        // 2. Update Group Info
        $updateRes = $this->putJson("/api/v1/conversations/{$convId}/group-info", [
            'title' => 'Connectly Core Engineering',
            'description' => 'Updated engineering group description',
        ]);
        $updateRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Connectly Core Engineering');

        // 3. Add new member (Member 2)
        $addRes = $this->postJson("/api/v1/conversations/{$convId}/members", [
            'user_ids' => [$member2->id],
        ]);
        $addRes->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $convId,
            'user_id' => $member2->id,
        ]);

        // 4. Update member role (make Member 1 an admin)
        $roleRes = $this->putJson("/api/v1/conversations/{$convId}/members/{$member1->id}/role", [
            'role' => 'admin',
        ]);
        $roleRes->assertOk();

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $convId,
            'user_id' => $member1->id,
            'role' => 'admin',
        ]);

        // 5. Member 2 leaves the group
        Sanctum::actingAs($member2);
        $leaveRes = $this->postJson("/api/v1/conversations/{$convId}/leave");
        $leaveRes->assertOk()
            ->assertJsonPath('data.left', true);

        $this->assertDatabaseMissing('conversation_participants', [
            'conversation_id' => $convId,
            'user_id' => $member2->id,
        ]);
    }

    /**
     * 3. Multimedia, file attachment, and voice messaging.
     */
    public function test_multimedia_and_voice_messaging(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1);
        $conv = Conversation::create(['type' => 'direct']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user1->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user2->id, 'role' => 'member']);

        // 1. Upload Attachment
        $fakeFile = UploadedFile::fake()->create('architecture_diagram.pdf', 1024, 'application/pdf');
        $attachRes = $this->postJson('/api/v1/messages/attachments', [
            'file' => $fakeFile,
        ]);
        $attachRes->assertStatus(201)
            ->assertJsonPath('success', true);
        $mediaId = $attachRes->json('data.id');

        // Send message with media attachment
        $msgRes = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Here is the architecture PDF',
            'media_ids' => [$mediaId],
            'type' => 'file',
            'client_message_id' => 'file_msg_uuid_1',
        ]);
        $msgRes->assertStatus(201);

        // 2. Upload and send Voice Message
        $fakeAudio = UploadedFile::fake()->create('voice_note.m4a', 500, 'audio/mp4');
        $voiceRes = $this->postJson("/api/v1/conversations/{$conv->id}/voice", [
            'audio' => $fakeAudio,
            'duration' => 14,
            'waveform' => [0.2, 0.5, 0.8, 0.4, 0.9, 0.3],
        ]);

        $voiceRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'voice');

        $voiceMsgId = $voiceRes->json('data.id');
        $this->assertDatabaseHas('messages', [
            'id' => $voiceMsgId,
            'conversation_id' => $conv->id,
            'type' => 'voice',
        ]);
    }

    /**
     * 4. Message reply, forwarding, and editing.
     */
    public function test_message_reply_forward_and_edit(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1);
        $conv = Conversation::create(['type' => 'direct']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user1->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user2->id, 'role' => 'member']);

        // 1. Send original message
        $origRes = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Initial roadmap draft',
            'client_message_id' => 'orig_1',
        ]);
        $origId = $origRes->json('data.id');

        // 2. Edit original message
        $editRes = $this->putJson("/api/v1/messages/{$origId}", [
            'body' => 'Updated roadmap draft with Phase 6 Messenger',
        ]);
        $editRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Updated roadmap draft with Phase 6 Messenger')
            ->assertJsonPath('data.is_edited', true);

        // 3. User 2 replies to message
        Sanctum::actingAs($user2);
        $replyRes = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Looks solid! Approved.',
            'reply_to_id' => $origId,
            'client_message_id' => 'reply_1',
        ]);
        $replyRes->assertStatus(201)
            ->assertJsonPath('data.reply_to_message_id', $origId);

        // 4. Forward message to another conversation
        $conv2 = Conversation::create(['type' => 'direct']);
        ConversationParticipant::create(['conversation_id' => $conv2->id, 'user_id' => $user2->id, 'role' => 'member']);

        $forwardRes = $this->postJson("/api/v1/messages/{$origId}/forward", [
            'conversation_ids' => [$conv2->id],
        ]);
        $forwardRes->assertOk()
            ->assertJsonPath('success', true);
    }

    /**
     * 5. Message reactions, saving/bookmarking and unsaving.
     */
    public function test_message_reactions_and_saved_messages(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1);
        $conv = Conversation::create(['type' => 'direct']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user1->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user2->id, 'role' => 'member']);

        $msgRes = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Important credentials and server instructions',
            'client_message_id' => 'save_react_msg',
        ]);
        $msgId = $msgRes->json('data.id');

        // 1. React with 'love'
        Sanctum::actingAs($user2);
        $reactRes = $this->postJson("/api/v1/messages/{$msgId}/reactions", [
            'reaction' => 'love',
        ]);
        $reactRes->assertOk();

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $msgId,
            'user_id' => $user2->id,
            'reaction' => 'love',
        ]);

        // 2. Save Message (Bookmark)
        $saveRes = $this->postJson("/api/v1/messages/{$msgId}/save");
        $saveRes->assertStatus(201);

        // 3. Inspect Saved Messages
        $savedListRes = $this->getJson('/api/v1/saved-messages');
        $savedListRes->assertOk();
        $savedIds = collect($savedListRes->json('data'))->pluck('id')->all();
        $this->assertContains($msgId, $savedIds);

        // 4. Unsave Message
        $unsaveRes = $this->deleteJson("/api/v1/messages/{$msgId}/save");
        $unsaveRes->assertOk();
    }

    /**
     * 6. Multi-device deletion (delete-for-me vs delete-for-everyone).
     */
    public function test_message_deletion_for_me_and_for_everyone(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1);
        $conv = Conversation::create(['type' => 'direct']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user1->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user2->id, 'role' => 'member']);

        // 1. Delete for everyone (Author deletes message)
        $msg1Res = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Accidental typo message',
            'client_message_id' => 'typo_msg',
        ]);
        $msg1Id = $msg1Res->json('data.id');

        $delEveryoneRes = $this->postJson("/api/v1/messages/{$msg1Id}/delete-for-everyone");
        $delEveryoneRes->assertOk();

        $this->assertDatabaseHas('messages', [
            'id' => $msg1Id,
            'is_deleted_for_everyone' => true,
        ]);

        // 2. Delete for me (User 2 hides a message from own view)
        $msg2Res = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Secret note',
            'client_message_id' => 'secret_note',
        ]);
        $msg2Id = $msg2Res->json('data.id');

        Sanctum::actingAs($user2);
        $delMeRes = $this->postJson("/api/v1/messages/{$msg2Id}/delete-for-me");
        $delMeRes->assertOk();

        $this->assertDatabaseHas('message_user_deletions', [
            'message_id' => $msg2Id,
            'user_id' => $user2->id,
        ]);
    }

    /**
     * 7. Read receipts, delivery status, and unread counts.
     */
    public function test_delivery_status_read_receipts_and_unread_counts(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        Sanctum::actingAs($sender);
        $conv = Conversation::create(['type' => 'direct']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $sender->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $recipient->id, 'role' => 'member']);

        $msgRes = $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Ping message for status checks',
            'client_message_id' => 'receipt_test_1',
        ]);
        $msgId = $msgRes->json('data.id');

        // Recipient device receives message -> mark as delivered
        Sanctum::actingAs($recipient);
        $delivRes = $this->postJson("/api/v1/messages/{$msgId}/delivered");
        $delivRes->assertOk()
            ->assertJsonPath('data.delivery_status', 'delivered');

        $this->assertDatabaseHas('messages', [
            'id' => $msgId,
            'delivery_status' => 'delivered',
        ]);

        // Recipient opens conversation -> mark conversation read
        $readRes = $this->postJson("/api/v1/conversations/{$conv->id}/read");
        $readRes->assertOk();

        $this->assertDatabaseHas('messages', [
            'id' => $msgId,
            'delivery_status' => 'seen',
        ]);

        // Total unread count for recipient should be 0
        $unreadRes = $this->getJson('/api/v1/messages/unread-count');
        $unreadRes->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    /**
     * 8. Online presence heartbeat, active friends, and typing indicators.
     */
    public function test_presence_heartbeat_active_friends_and_typing(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // 1. User 1 sends heartbeat
        Sanctum::actingAs($user1);
        $hbRes = $this->postJson('/api/v1/presence/heartbeat');
        $hbRes->assertOk()
            ->assertJsonPath('data.online', true);

        // 2. User 1 sends typing indicator in conversation
        $conv = Conversation::create(['type' => 'direct']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user1->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user2->id, 'role' => 'member']);

        $typeRes = $this->postJson('/api/v1/presence/typing', [
            'conversation_id' => $conv->id,
            'is_typing' => true,
        ]);
        $typeRes->assertOk();

        // 3. User 1 goes offline
        $offRes = $this->postJson('/api/v1/presence/offline');
        $offRes->assertOk()
            ->assertJsonPath('data.online', false);
    }

    /**
     * 9. Offline synchronization engine and message search.
     */
    public function test_offline_sync_and_message_search(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1);
        $conv = Conversation::create(['type' => 'direct']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user1->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user2->id, 'role' => 'member']);

        // Send a unique searchable message
        $this->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Cryptographic quantum encryption meeting at 3 PM',
            'client_message_id' => 'search_test_1',
        ]);

        // Search messages
        $searchRes = $this->getJson('/api/v1/messenger/search?q=quantum');
        $searchRes->assertOk()
            ->assertJsonPath('success', true);

        $results = $searchRes->json('data');
        $this->assertNotEmpty($results);
        $this->assertStringContainsString('quantum', $results[0]['body']);

        // Record a sync event and test sync endpoint
        app(SyncEventService::class)->recordEvent('message.new', [
            'body' => 'Sync payload message',
        ], $user1->id, $conv->id);

        $syncRes = $this->getJson('/api/v1/messenger/sync?since_id=0');
        $syncRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'events',
                    'latest_event_id',
                    'has_more',
                ],
            ]);
    }

    /**
     * 10. Laravel Reverb WebSocket channel authorization with Sanctum Bearer tokens.
     */
    public function test_reverb_websocket_channel_authorization_with_sanctum(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'connectly_test_key',
            'broadcasting.connections.reverb.secret' => 'connectly_test_secret',
            'broadcasting.connections.reverb.app_id' => '12345',
        ]);
        Broadcast::purge('reverb');
        Broadcast::purge();
        require base_path('routes/channels.php');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $outsider = User::factory()->create();

        $conv = Conversation::create(['type' => 'direct']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user1->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $user2->id, 'role' => 'member']);

        Sanctum::actingAs($user1);

        // 1. Authorize User's Private Channel (private-user.{id})
        $userAuthRes = $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '12345.67890',
            'channel_name' => "private-user.{$user1->id}",
        ]);
        $userAuthRes->assertOk()
            ->assertJsonStructure(['auth']);

        // 2. Authorize Conversation Private Channel (private-conversation.{id})
        $convAuthRes = $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '12345.67890',
            'channel_name' => "private-conversation.{$conv->id}",
        ]);
        $convAuthRes->assertOk()
            ->assertJsonStructure(['auth']);

        // 3. Authorize Presence Channel (presence-users)
        $presenceAuthRes = $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '12345.67890',
            'channel_name' => 'presence-users',
        ]);
        $presenceAuthRes->assertOk()
            ->assertJsonStructure(['auth', 'channel_data']);

        // 4. Outsider denied access to conversation private channel
        Sanctum::actingAs($outsider);
        $deniedRes = $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '12345.67890',
            'channel_name' => "private-conversation.{$conv->id}",
        ]);
        $deniedRes->assertStatus(403);
    }
}
