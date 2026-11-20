<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\ScheduledMessage;
use App\Models\User;
use App\Services\Messenger\EnterpriseMessengerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UltraEnterpriseMessengerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;

    protected User $user2;

    protected User $user3;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create(['name' => 'Alice']);
        $this->user2 = User::factory()->create(['name' => 'Bob']);
        $this->user3 = User::factory()->create(['name' => 'Charlie']);

        $this->conversation = Conversation::create([
            'type' => Conversation::TYPE_GROUP,
            'title' => 'Project Alpha',
            'creator_id' => $this->user1->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user1->id,
            'role' => 'admin',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user2->id,
            'role' => 'member',
        ]);
    }

    public function test_can_create_and_vote_on_poll_in_conversation(): void
    {
        $response = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->conversation->id}/polls", [
                'question' => 'What is the release deadline?',
                'options' => ['Friday', 'Next Monday', 'End of Month'],
                'is_multiple_choice' => false,
                'can_change_vote' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.question', 'What is the release deadline?')
            ->assertJsonPath('data.options.0.option_text', 'Friday')
            ->assertJsonPath('data.total_votes_count', 0);

        $pollId = $response->json('data.id');
        $option1Id = $response->json('data.options.0.id');
        $option2Id = $response->json('data.options.1.id');

        // User 2 votes for option 1
        $voteResp = $this->actingAs($this->user2, 'sanctum')
            ->postJson("/api/v1/polls/{$pollId}/vote", [
                'option_ids' => [$option1Id],
            ]);

        $voteResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_votes_count', 1)
            ->assertJsonPath('data.options.0.votes_count', 1)
            ->assertJsonPath('data.options.0.is_voted_by_me', true);

        // User 2 changes vote to option 2
        $changeResp = $this->actingAs($this->user2, 'sanctum')
            ->postJson("/api/v1/polls/{$pollId}/vote", [
                'option_ids' => [$option2Id],
            ]);

        $changeResp->assertStatus(200)
            ->assertJsonPath('data.options.0.votes_count', 0)
            ->assertJsonPath('data.options.1.votes_count', 1);

        // Creator closes poll
        $closeResp = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/polls/{$pollId}/close");

        $closeResp->assertStatus(200)
            ->assertJsonPath('data.is_closed', true);
    }

    public function test_unauthorized_user_cannot_vote_on_poll(): void
    {
        $poll = app(EnterpriseMessengerService::class)->createPoll(
            $this->conversation,
            $this->user1,
            'Should we launch?',
            ['Yes', 'No']
        );

        $optionId = $poll->options()->first()->id;

        // User 3 is not in conversation
        $resp = $this->actingAs($this->user3, 'sanctum')
            ->postJson("/api/v1/polls/{$poll->id}/vote", [
                'option_ids' => [$optionId],
            ]);

        $resp->assertStatus(403);
    }

    public function test_can_set_disappearing_timer_and_purge_expired_messages(): void
    {
        $response = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->conversation->id}/disappearing-timer", [
                'duration_seconds' => 3600, // 1 hour
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.disappearing_duration', 3600);

        $this->conversation->refresh();
        $this->assertEquals(3600, $this->conversation->settings['disappearing_duration']);

        // Send a message, it should have expires_at set
        $msgResp = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
                'body' => 'Secret announcement',
            ]);

        $msgResp->assertStatus(201);
        $msgId = $msgResp->json('data.id');

        $message = Message::findOrFail($msgId);
        $this->assertNotNull($message->expires_at);

        // Set expires_at in past
        $message->update(['expires_at' => now()->subMinutes(5)]);

        // getMessages should filter it out
        $indexResp = $this->actingAs($this->user1, 'sanctum')
            ->getJson("/api/v1/conversations/{$this->conversation->id}/messages");

        $ids = collect($indexResp->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($msgId, $ids);

        // Purge expired command
        $purged = app(EnterpriseMessengerService::class)->purgeExpiredMessages();
        $this->assertGreaterThanOrEqual(1, $purged);
        $this->assertNull(Message::find($msgId));
    }

    public function test_can_schedule_message_and_dispatch(): void
    {
        $scheduledTime = now()->addHours(2);

        $resp = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->conversation->id}/scheduled-messages", [
                'body' => 'Scheduled team reminder',
                'scheduled_at' => $scheduledTime->toIso8601String(),
                'timezone' => 'Asia/Dhaka',
            ]);

        $resp->assertStatus(201)
            ->assertJsonPath('data.body', 'Scheduled team reminder')
            ->assertJsonPath('data.status', 'pending');

        $scheduledId = $resp->json('data.id');

        // Simulate time passing: set scheduled_at in past
        $scheduled = ScheduledMessage::findOrFail($scheduledId);
        $scheduled->update(['scheduled_at' => now()->subMinute()]);

        // Run dispatch command
        $dispatched = app(EnterpriseMessengerService::class)->dispatchDueScheduledMessages();
        $this->assertEquals(1, $dispatched);

        $scheduled->refresh();
        $this->assertEquals('sent', $scheduled->status);
        $this->assertNotNull($scheduled->sent_at);

        // Verify sent message exists in conversation
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversation->id,
            'body' => 'Scheduled team reminder',
        ]);
    }

    public function test_can_share_location_and_stop_live_location(): void
    {
        $resp = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->conversation->id}/location", [
                'latitude' => 23.8103,
                'longitude' => 90.4125,
                'address' => 'Dhaka, Bangladesh',
                'live_duration_seconds' => 3600,
            ]);

        $resp->assertStatus(201)
            ->assertJsonPath('data.type', 'location')
            ->assertJsonPath('data.location.is_live', true)
            ->assertJsonPath('data.location.latitude', 23.8103);

        $msgId = $resp->json('data.id');

        // Stop live location
        $stopResp = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->conversation->id}/location/{$msgId}/stop");

        $stopResp->assertStatus(200)
            ->assertJsonPath('data.location.is_live', false);
    }

    public function test_can_update_and_reset_chat_theme(): void
    {
        $resp = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->conversation->id}/theme", [
                'theme_color' => '#8A2BE2',
                'bubble_color' => '#8A2BE2',
            ]);

        $resp->assertStatus(200)
            ->assertJsonPath('data.theme_color', '#8A2BE2');

        $this->conversation->refresh();
        $this->assertEquals('#8A2BE2', $this->conversation->settings['theme']['theme_color']);

        // Reset theme
        $resetResp = $this->actingAs($this->user1, 'sanctum')
            ->deleteJson("/api/v1/conversations/{$this->conversation->id}/theme");

        $resetResp->assertStatus(200);

        $this->conversation->refresh();
        $this->assertArrayNotHasKey('theme', $this->conversation->settings ?? []);
    }

    public function test_can_transcribe_voice_message_and_translate(): void
    {
        $msg = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->user1->id,
            'type' => 'voice',
            'body' => '🎤 ভয়েস বার্তা',
            'metadata' => [
                'audio_url' => '/storage/audio.mp3',
                'duration' => 12,
            ],
        ]);

        $transResp = $this->actingAs($this->user2, 'sanctum')
            ->postJson("/api/v1/messages/{$msg->id}/transcribe");

        $transResp->assertStatus(200)
            ->assertJsonPath('data.transcription.language', 'bn');

        // Translate text message
        $textMsg = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->user1->id,
            'type' => 'text',
            'body' => 'hello',
        ]);

        $transTextResp = $this->actingAs($this->user2, 'sanctum')
            ->postJson("/api/v1/messages/{$textMsg->id}/translate", [
                'target_language' => 'bn',
            ]);

        $transTextResp->assertStatus(200)
            ->assertJsonPath('data.translated_text', 'হ্যালো');
    }

    public function test_can_create_and_manage_broadcast_channel(): void
    {
        $resp = $this->actingAs($this->user1, 'sanctum')
            ->postJson('/api/v1/channels', [
                'title' => 'Bondhoo Tech Announcements',
                'description' => 'Official tech news and updates',
                'is_public' => true,
            ]);

        $resp->assertStatus(201)
            ->assertJsonPath('data.title', 'Bondhoo Tech Announcements')
            ->assertJsonPath('data.is_channel', true);

        $channelId = $resp->json('data.id');

        // User 2 joins channel
        $joinResp = $this->actingAs($this->user2, 'sanctum')
            ->postJson("/api/v1/channels/{$channelId}/join");

        $joinResp->assertStatus(200)
            ->assertJsonPath('data.role', 'member');

        // User 2 leaves channel
        $leaveResp = $this->actingAs($this->user2, 'sanctum')
            ->deleteJson("/api/v1/channels/{$channelId}/leave");

        $leaveResp->assertStatus(200);
    }
}
