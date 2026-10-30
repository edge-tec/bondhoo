<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase8EnterpriseMessengerUpgradeTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveUser(string $username, string $name): User
    {
        return User::factory()->create([
            'username' => $username,
            'name' => $name,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    public function test_messenger_inertia_page_loads_complete_phase8_enterprise_props(): void
    {
        $alice = $this->createActiveUser('phase8_alice', 'Alice Rahman');
        $bob = $this->createActiveUser('phase8_bob', 'Bob Karim');

        $conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
            'creator_id' => $alice->id,
            'last_message_at' => now(),
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $alice->id,
            'role' => 'member',
            'request_status' => 'accepted',
        ]);
        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $bob->id,
            'role' => 'member',
            'request_status' => 'accepted',
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $bob->id,
            'body' => 'Check out https://jugajug.com for enterprise updates',
            'type' => 'text',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($alice)
            ->get('/messages');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Messenger')
                ->has('conversations')
                ->has('initialActiveConversation')
                ->has('initialMessages')
                ->has('initialSharedMedia')
                ->has('initialSharedFiles')
                ->has('initialSharedLinks')
                ->has('initialPinnedMessages')
                ->has('initialSavedMessages')
                ->has('initialCallHistory')
                ->has('soundPreferences')
                ->has('apiToken')
                ->has('friends')
            );
    }

    public function test_direct_saved_and_group_conversation_creation_and_member_management(): void
    {
        $admin = $this->createActiveUser('phase8_admin', 'Group Admin');
        $member1 = $this->createActiveUser('phase8_mem1', 'Member One');
        $member2 = $this->createActiveUser('phase8_mem2', 'Member Two');
        $member3 = $this->createActiveUser('phase8_mem3', 'Member Three');

        // 1. Saved Messages (self-chat)
        $savedRes = $this->actingAs($admin)->postJson('/api/v1/conversations', [
            'type' => 'saved',
        ]);
        $savedRes->assertStatus(201)
            ->assertJsonPath('data.type', 'saved');

        // 2. Direct 1-to-1 conversation
        $directRes = $this->actingAs($admin)->postJson('/api/v1/conversations', [
            'recipient_id' => $member1->id,
        ]);
        $directRes->assertStatus(201)
            ->assertJsonPath('data.type', 'direct');

        // 3. Group conversation creation with title, description & members list
        $groupRes = $this->actingAs($admin)->postJson('/api/v1/conversations', [
            'type' => 'group',
            'title' => 'Phase 8 Engineering Team',
            'description' => 'Next-Gen Messenger Architecture',
            'participant_ids' => [$member1->id, $member2->id],
        ]);
        $groupRes->assertStatus(201)
            ->assertJsonPath('data.type', 'group')
            ->assertJsonPath('data.title', 'Phase 8 Engineering Team')
            ->assertJsonPath('data.creator_id', $admin->id);

        $groupId = (int) $groupRes->json('data.id');
        $members = $groupRes->json('data.members');
        $this->assertIsArray($members);
        $this->assertCount(3, $members);

        // 4. Update Group Info
        $this->actingAs($admin)->putJson("/api/v1/conversations/{$groupId}/group-info", [
            'title' => 'Phase 8 Core Squad',
            'description' => 'Updated description',
        ])->assertOk();

        // 5. Add new member
        $this->actingAs($admin)->postJson("/api/v1/conversations/{$groupId}/members", [
            'user_ids' => [$member3->id],
        ])->assertOk();

        // 6. Promote member1 to admin
        $this->actingAs($admin)->putJson("/api/v1/conversations/{$groupId}/members/{$member1->id}/role", [
            'role' => 'admin',
        ])->assertOk();

        // 7. Remove member3
        $this->actingAs($admin)->deleteJson("/api/v1/conversations/{$groupId}/members/{$member3->id}")
            ->assertOk();

        // 8. Member2 leaves group
        $this->actingAs($member2)->postJson("/api/v1/conversations/{$groupId}/leave")
            ->assertOk();
    }

    public function test_advanced_message_interactions_reply_edit_forward_pin_star_reactions_and_bulk_delete(): void
    {
        $alice = $this->createActiveUser('phase8_msg_alice', 'Alice');
        $bob = $this->createActiveUser('phase8_msg_bob', 'Bob');
        $charlie = $this->createActiveUser('phase8_msg_charlie', 'Charlie');

        $conv1 = Conversation::create(['type' => Conversation::TYPE_DIRECT, 'creator_id' => $alice->id]);
        ConversationParticipant::create(['conversation_id' => $conv1->id, 'user_id' => $alice->id, 'role' => 'member', 'request_status' => 'accepted']);
        ConversationParticipant::create(['conversation_id' => $conv1->id, 'user_id' => $bob->id, 'role' => 'member', 'request_status' => 'accepted']);

        $conv2 = Conversation::create(['type' => Conversation::TYPE_DIRECT, 'creator_id' => $alice->id]);
        ConversationParticipant::create(['conversation_id' => $conv2->id, 'user_id' => $alice->id, 'role' => 'member', 'request_status' => 'accepted']);
        ConversationParticipant::create(['conversation_id' => $conv2->id, 'user_id' => $charlie->id, 'role' => 'member', 'request_status' => 'accepted']);

        // Send initial message
        $sendRes = $this->actingAs($alice)->postJson("/api/v1/conversations/{$conv1->id}/messages", [
            'body' => 'Hello Bob, welcome to Phase 8 Messenger!',
            'type' => 'text',
        ]);
        $sendRes->assertStatus(201);
        $msg1Id = (int) $sendRes->json('data.id');

        // Reply to message
        $replyRes = $this->actingAs($bob)->postJson("/api/v1/conversations/{$conv1->id}/messages", [
            'body' => 'Thanks Alice! Looks great.',
            'type' => 'text',
            'reply_to_id' => $msg1Id,
        ]);
        $replyRes->assertStatus(201)
            ->assertJsonPath('data.reply_preview.id', $msg1Id);
        $msg2Id = (int) $replyRes->json('data.id');

        // Edit message
        $this->actingAs($alice)->patchJson("/api/v1/messages/{$msg1Id}", [
            'body' => 'Hello Bob, welcome to Phase 8 Enterprise Messenger (edited)!',
        ])->assertOk()
            ->assertJsonPath('data.is_edited', true);

        // React to message & list reactions
        $this->actingAs($bob)->postJson("/api/v1/messages/{$msg1Id}/reactions", [
            'reaction' => '❤️',
        ])->assertOk();
        $this->actingAs($alice)->getJson("/api/v1/messages/{$msg1Id}/reactions")
            ->assertOk();

        // Pin & unpin message
        $this->actingAs($alice)->postJson("/api/v1/conversations/{$conv1->id}/messages/{$msg1Id}/pin")
            ->assertStatus(201);
        $this->actingAs($alice)->getJson("/api/v1/conversations/{$conv1->id}/pins")
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->actingAs($alice)->deleteJson("/api/v1/conversations/{$conv1->id}/messages/{$msg1Id}/pin")
            ->assertOk();

        // Star / Save message
        $this->actingAs($alice)->postJson("/api/v1/messages/{$msg1Id}/save")
            ->assertStatus(201);
        $this->actingAs($alice)->deleteJson("/api/v1/messages/{$msg1Id}/save")
            ->assertOk();

        // Multi-conversation forward
        $this->actingAs($alice)->postJson("/api/v1/messages/{$msg1Id}/forward", [
            'target_conversation_ids' => [$conv2->id],
        ])->assertSuccessful();

        // Report message
        $this->actingAs($alice)->postJson("/api/v1/messages/{$msg2Id}/report", [
            'reason' => 'spam',
            'details' => 'Automated test report',
        ])->assertSuccessful();

        // Multi-select bulk delete
        $this->actingAs($alice)->postJson('/api/v1/messages/bulk-delete', [
            'message_ids' => [$msg1Id, $msg2Id],
        ])->assertOk()
            ->assertJsonPath('data.deleted_count', 2);
    }

    public function test_conversation_controls_pin_archive_mute_unread_draft_shared_explorer_and_search(): void
    {
        $alice = $this->createActiveUser('phase8_ctrl_alice', 'Alice');
        $bob = $this->createActiveUser('phase8_ctrl_bob', 'Bob');

        $conv = Conversation::create(['type' => Conversation::TYPE_DIRECT, 'creator_id' => $alice->id]);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $alice->id, 'role' => 'member', 'request_status' => 'accepted']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $bob->id, 'role' => 'member', 'request_status' => 'accepted']);

        Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $bob->id,
            'body' => 'Shared link https://example.com/spec and keyword AlphaOmega',
            'type' => 'text',
            'sent_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $bob->id,
            'body' => 'Voice note',
            'type' => 'voice',
            'metadata' => ['duration' => 12, 'audio_url' => '/storage/voice/test.webm'],
            'sent_at' => now(),
        ]);

        // Pin, Mute with duration, Mark unread, Save draft, Archive
        $this->actingAs($alice)->postJson("/api/v1/conversations/{$conv->id}/pin")->assertOk();
        $this->actingAs($alice)->postJson("/api/v1/conversations/{$conv->id}/mute", ['duration' => '8h'])->assertOk();
        $this->actingAs($alice)->postJson("/api/v1/conversations/{$conv->id}/unread")->assertOk();
        $this->actingAs($alice)->postJson("/api/v1/conversations/{$conv->id}/draft", ['draft' => 'Work in progress...'])->assertOk();
        $this->actingAs($alice)->postJson("/api/v1/conversations/{$conv->id}/archive")->assertOk();

        // Shared Content Explorer tabs: media, files, links, voice
        $this->actingAs($alice)->getJson("/api/v1/conversations/{$conv->id}/media?type=media")->assertOk();
        $this->actingAs($alice)->getJson("/api/v1/conversations/{$conv->id}/media?type=files")->assertOk();
        $this->actingAs($alice)->getJson("/api/v1/conversations/{$conv->id}/media?type=links")
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->actingAs($alice)->getJson("/api/v1/conversations/{$conv->id}/media?type=voice")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Global search
        $this->actingAs($alice)->getJson('/api/v1/messenger/search?q=AlphaOmega')
            ->assertOk();
    }

    public function test_audio_and_video_call_lifecycle_ringing_detection_and_history(): void
    {
        $caller = $this->createActiveUser('phase8_caller', 'Caller User');
        $callee = $this->createActiveUser('phase8_callee', 'Callee User');

        $conv = Conversation::create(['type' => Conversation::TYPE_DIRECT, 'creator_id' => $caller->id]);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $caller->id, 'role' => 'member', 'request_status' => 'accepted']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $callee->id, 'role' => 'member', 'request_status' => 'accepted']);

        // Caller initiates video call
        $initRes = $this->actingAs($caller)->postJson('/api/v1/calls', [
            'conversation_id' => $conv->id,
            'call_type' => 'video',
        ]);
        $initRes->assertStatus(201)
            ->assertJsonPath('data.status', 'ringing')
            ->assertJsonPath('data.call_type', 'video');

        $callId = (int) $initRes->json('data.id');

        // Callee polls active incoming call and sees it ringing
        $activeRes = $this->actingAs($callee)->getJson('/api/v1/calls/active');
        $activeRes->assertOk()
            ->assertJsonPath('data.id', $callId)
            ->assertJsonPath('data.status', 'ringing');

        // Callee accepts the call
        $this->actingAs($callee)->postJson("/api/v1/calls/{$callId}/respond", [
            'action' => 'accept',
        ])->assertOk();

        // Caller ends the call
        $this->actingAs($caller)->postJson("/api/v1/calls/{$callId}/leave")
            ->assertOk();

        // Call appears in history
        $this->actingAs($caller)->getJson('/api/v1/calls/history')
            ->assertOk();
    }

    public function test_messenger_vue_contains_all_phase8_enterprise_ui_components_and_responsive_hooks(): void
    {
        $content = file_get_contents(resource_path('js/Pages/Messenger.vue'));

        // Preserved hooks
        $this->assertStringContainsString('jugajug:messages-unread-updated', $content);
        $this->assertStringContainsString('h-[calc(100dvh-8.5rem)]', $content);

        // Phase 8 UI components & capabilities
        $this->assertStringContainsString('quickContacts', $content);
        $this->assertStringContainsString('openCreateGroup', $content);
        $this->assertStringContainsString('openSavedMessages', $content);
        $this->assertStringContainsString('toggleSelectionMode', $content);
        $this->assertStringContainsString('bulkDeleteSelected', $content);
        $this->assertStringContainsString('loadOlderMessages', $content);
        $this->assertStringContainsString('dateLabelFor', $content);
        $this->assertStringContainsString('detailsTab', $content);
        $this->assertStringContainsString('pickerTab', $content);
        $this->assertStringContainsString('/api/v1/calls', $content);
        $this->assertStringContainsString('respondIncomingCall', $content);
    }
}
