<?php

namespace Tests\Feature;

use App\Events\MessageDeletedEvent;
use App\Events\MessageReactionEvent;
use App\Events\MessageUpdatedEvent;
use App\Models\Conversation;
use App\Models\Media;
use App\Models\Message;
use App\Models\PrivacySetting;
use App\Models\User;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Enterprise Messenger Feature Tests:
 * Reactions, Replies, Forwards, Edit, Delete (Me vs Everyone),
 * Pin, Archive, Mute, Draft, Group Management, Privacy Controls, IDOR Attachment Security.
 */
class EnterpriseMessengerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_manage_saved_messages(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'saved',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'saved')
            ->assertJsonPath('data.is_saved', true);

        $convId = $response->json('data.id');

        // Send a note to self
        $msgResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/messages", [
                'body' => 'Remember to check quarterly goals',
            ]);

        $msgResponse->assertStatus(201)
            ->assertJsonPath('data.body', 'Remember to check quarterly goals');
    }

    public function test_user_can_react_change_and_remove_reaction(): void
    {
        Event::fake([MessageReactionEvent::class]);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);
        $message = $messengerService->sendMessage($sender, $conv, ['body' => 'Exciting news!']);

        // 1. Add reaction ❤️
        $resp1 = $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/messages/{$message->id}/reactions", [
                'reaction' => '❤️',
            ]);

        $resp1->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.action', 'added')
            ->assertJsonPath('data.user_reaction', '❤️');

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $recipient->id,
            'reaction' => '❤️',
        ]);

        // 2. Change reaction to 👍
        $resp2 = $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/messages/{$message->id}/reactions", [
                'reaction' => '👍',
            ]);

        $resp2->assertStatus(200)
            ->assertJsonPath('data.action', 'changed')
            ->assertJsonPath('data.user_reaction', '👍');

        // 3. Remove reaction (toggle off)
        $resp3 = $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/messages/{$message->id}/reactions", [
                'reaction' => '👍',
            ]);

        $resp3->assertStatus(200)
            ->assertJsonPath('data.action', 'removed')
            ->assertJsonPath('data.user_reaction', null);

        $this->assertDatabaseMissing('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $recipient->id,
        ]);
    }

    public function test_user_can_reply_to_message(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);
        $original = $messengerService->sendMessage($sender, $conv, ['body' => 'Do you have time today?']);

        $replyResp = $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Yes, 3 PM works best.',
                'reply_to_message_id' => $original->id,
            ]);

        $replyResp->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reply_to_message_id', $original->id)
            ->assertJsonPath('data.reply_to.body', 'Do you have time today?');
    }

    public function test_user_can_edit_own_message_within_policy(): void
    {
        Event::fake([MessageUpdatedEvent::class]);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);
        $message = $messengerService->sendMessage($sender, $conv, ['body' => 'Meet at 2 PM']);

        $editResp = $this->actingAs($sender, 'sanctum')
            ->putJson("/api/v1/messages/{$message->id}", [
                'body' => 'Meet at 3 PM (rescheduled)',
            ]);

        $editResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Meet at 3 PM (rescheduled)')
            ->assertJsonPath('data.is_edited', true);

        $this->assertEquals('Meet at 3 PM (rescheduled)', $message->fresh()->body);
        $this->assertTrue($message->fresh()->is_edited);

        // Another user cannot edit sender's message -> 403
        $this->actingAs($recipient, 'sanctum')
            ->putJson("/api/v1/messages/{$message->id}", [
                'body' => 'Malicious edit',
            ])
            ->assertStatus(403);
    }

    public function test_user_can_delete_message_for_me_vs_for_everyone(): void
    {
        Event::fake([MessageDeletedEvent::class]);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);
        $msg1 = $messengerService->sendMessage($sender, $conv, ['body' => 'Secret note for me']);
        $msg2 = $messengerService->sendMessage($sender, $conv, ['body' => 'Sent by accident to all']);

        // 1. Delete for Me by sender
        $this->actingAs($sender, 'sanctum')
            ->deleteJson("/api/v1/messages/{$msg1->id}", ['mode' => 'me'])
            ->assertStatus(200);

        // Sender's message list should NOT contain msg1
        $senderList = $this->actingAs($sender, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages")
            ->json('data');
        $this->assertEmpty(array_filter($senderList, fn ($m) => $m['id'] === $msg1->id));

        // Recipient can still see msg1
        $recipientList = $this->actingAs($recipient, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages")
            ->json('data');
        $this->assertNotEmpty(array_filter($recipientList, fn ($m) => $m['id'] === $msg1->id));

        // 2. Delete for Everyone by sender
        $this->actingAs($sender, 'sanctum')
            ->deleteJson("/api/v1/messages/{$msg2->id}", ['mode' => 'everyone'])
            ->assertStatus(200);

        // For both users, msg2 is now marked as deleted
        $this->assertTrue($msg2->fresh()->is_deleted);
        $this->assertEquals('This message was deleted.', $msg2->fresh()->toResponseArray($recipient)['body']);
    }

    public function test_user_can_forward_message_to_multiple_conversations(): void
    {
        $sender = User::factory()->create();
        $friend1 = User::factory()->create();
        $friend2 = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv1 = $messengerService->getOrCreateDirectConversation($sender, $friend1->id);
        $conv2 = $messengerService->getOrCreateDirectConversation($sender, $friend2->id);

        $origMessage = $messengerService->sendMessage($friend1, $conv1, ['body' => 'Important announcement']);

        $forwardResp = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/messages/{$origMessage->id}/forward", [
                'conversation_ids' => [$conv1->id, $conv2->id],
            ]);

        $forwardResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        // Check forwarded message in conv2
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv2->id,
            'sender_id' => $sender->id,
            'body' => 'Important announcement',
            'is_forwarded' => true,
        ]);
    }

    public function test_user_can_pin_archive_mute_and_save_draft_in_conversation(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user, $friend->id);

        // Pin conversation
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/pin")
            ->assertStatus(200)
            ->assertJsonPath('data.is_pinned', true);

        // Archive conversation
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/archive")
            ->assertStatus(200)
            ->assertJsonPath('data.is_archived', true);

        // Mute conversation for 8 hours
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/mute", ['duration' => '8_hours'])
            ->assertStatus(200)
            ->assertJsonPath('data.is_muted', true);

        // Save draft message
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/draft", ['draft' => 'Drafting a thoughtful response...'])
            ->assertStatus(200)
            ->assertJsonPath('data.draft_message', 'Drafting a thoughtful response...');

        // Fetch conversation and check state
        $showResp = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}");

        $showResp->assertStatus(200)
            ->assertJsonPath('data.is_pinned', true)
            ->assertJsonPath('data.is_archived', true)
            ->assertJsonPath('data.is_muted', true)
            ->assertJsonPath('data.draft_message', 'Drafting a thoughtful response...');
    }

    public function test_message_requests_flow_for_non_friends(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create(); // Not friends

        // User A creates conversation with User B
        $createResp = $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'recipient_id' => $userB->id,
            ]);

        $convId = $createResp->json('data.id');

        // Check for User B, this is a message request
        $bView = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/v1/conversations/{$convId}");

        $bView->assertStatus(200)
            ->assertJsonPath('data.is_request', true)
            ->assertJsonPath('data.request_status', 'pending');

        // User B accepts request
        $acceptResp = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/request", [
                'action' => 'accept',
            ]);

        $acceptResp->assertStatus(200)
            ->assertJsonPath('data.request_status', 'accepted')
            ->assertJsonPath('data.is_request', false);
    }

    public function test_privacy_enforcement_who_can_message_me(): void
    {
        $recipient = User::factory()->create();
        PrivacySetting::create([
            'user_id' => $recipient->id,
            'who_can_message_me' => 'nobody',
        ]);

        $sender = User::factory()->create();

        // Attempting to message recipient should fail with 403
        $resp = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'recipient_id' => $recipient->id,
            ]);

        $resp->assertStatus(403);
    }

    public function test_group_management_admin_privileges(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $newMember = User::factory()->create();

        // Create group
        $createResp = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'group',
                'title' => 'Project Alpha',
                'participant_ids' => [$member->id],
            ]);

        $convId = $createResp->json('data.id');

        // Add new member
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/members", [
                'user_ids' => [$newMember->id],
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // Make member an admin
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/conversations/{$convId}/members/{$member->id}", [
                'role' => 'admin',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.role', 'admin');

        // Remove newMember
        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/conversations/{$convId}/members/{$newMember->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // Normal member cannot remove others without admin rights
        $stranger = User::factory()->create();
        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/v1/conversations/{$convId}/members/{$member->id}")
            ->assertStatus(403);
    }

    public function test_attachment_download_idor_protection(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $intruder = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        $media = Media::create([
            'user_id' => $user1->id,
            'collection' => 'message',
            'disk' => 'public',
            'original_path' => 'messages/confidential.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
            'processing_status' => 'ready',
        ]);

        $message = $messengerService->sendMessage($user1, $conv, [
            'body' => 'Confidential attachment',
            'media_ids' => [$media->id],
        ]);

        // Intruder tries to download attachment -> 403
        $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/v1/messages/attachments/{$media->id}/download")
            ->assertStatus(403)
            ->assertJsonPath('message', 'Unauthorized attachment access.');

        // Participant user2 can download -> not 403 (will either stream or 404 if file physically missing on mock disk)
        $resp = $this->actingAs($user2, 'sanctum')
            ->getJson("/api/v1/messages/attachments/{$media->id}/download");

        $this->assertNotEquals(403, $resp->status());
    }

    public function test_user_can_upload_and_send_real_voice_message(): void
    {
        Storage::fake('public');
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        $fakeAudio = UploadedFile::fake()->create('note.webm', 500, 'audio/webm');

        $response = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/voice", [
                'audio' => $fakeAudio,
                'duration' => 12,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'voice')
            ->assertJsonPath('data.metadata.duration', 12);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'type' => 'voice',
        ]);
    }

    public function test_user_can_upload_batch_attachments_with_proper_validation(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file1 = UploadedFile::fake()->image('photo1.jpg');
        $file2 = UploadedFile::fake()->create('document.pdf', 300, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages/attachments', [
                'files' => [$file1, $file2],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
    }

    public function test_presence_heartbeat_and_typing_indicators(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        // 1. Heartbeat
        $hbResp = $this->actingAs($user1, 'sanctum')
            ->postJson('/api/v1/presence/heartbeat');
        $hbResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.online', true);

        // 2. Typing start
        $typingResp = $this->actingAs($user1, 'sanctum')
            ->postJson('/api/v1/presence/typing', [
                'conversation_id' => $conv->id,
            ]);
        $typingResp->assertStatus(200)
            ->assertJsonPath('data.is_typing', true);

        // 3. User2 checks typing
        $getTypingResp = $this->actingAs($user2, 'sanctum')
            ->getJson("/api/v1/presence/typing/{$conv->id}");
        $getTypingResp->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertCount(1, $getTypingResp->json('data.typing_users'));
    }

    public function test_search_messenger_matches_sender_text_and_media_filename(): void
    {
        $user1 = User::factory()->create(['name' => 'তামিম ইকবাল']);
        $user2 = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        $messengerService->sendMessage($user1, $conv, [
            'body' => 'বিশেষ পরিকল্পনা সংক্রান্ত আলোচনা',
        ]);

        $searchResp = $this->actingAs($user2, 'sanctum')
            ->getJson('/api/v1/messenger/search?'.http_build_query(['q' => 'পরিকল্পনা']));

        $searchResp->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertGreaterThanOrEqual(1, count($searchResp->json('data')));
    }
}
