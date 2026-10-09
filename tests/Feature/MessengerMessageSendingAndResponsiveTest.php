<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessengerMessageSendingAndResponsiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $recipient;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Farhan Ahmed']);
        UserProfile::create(['user_id' => $this->user->id]);

        $this->recipient = User::factory()->create(['name' => 'Nusrat Jahan']);
        UserProfile::create(['user_id' => $this->recipient->id]);

        $messengerService = app(MessengerServiceInterface::class);
        $this->conversation = $messengerService->getOrCreateDirectConversation($this->user, $this->recipient->id);
    }

    /**
     * Test sending message via API endpoint persists to database and returns 201.
     */
    public function test_authenticated_user_can_send_message_via_api_and_persists_in_database(): void
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
                'body' => 'স্বাগতম বন্ধু! মেসেঞ্জার এখন সম্পূর্ণ সক্রিয়।',
                'client_message_id' => 'msg_test_api_1',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Message sent successfully.',
            ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->user->id,
            'body' => 'স্বাগতম বন্ধু! মেসেঞ্জার এখন সম্পূর্ণ সক্রিয়।',
        ]);
    }

    /**
     * Test sending message via Web session fallback route persists to database and returns 201.
     */
    public function test_authenticated_user_can_send_message_via_web_fallback_route(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/messages/{$this->conversation->id}/send", [
                'body' => 'Web fallback route sends message seamlessly.',
                'client_message_id' => 'msg_test_web_fallback_1',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Message sent successfully.',
            ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->user->id,
            'body' => 'Web fallback route sends message seamlessly.',
        ]);
    }

    /**
     * Test duplicate prevention (idempotency) when retrying with the same client_message_id.
     */
    public function test_idempotency_prevents_duplicate_messages_when_retrying_with_same_client_message_id(): void
    {
        $clientMsgId = 'msg_idempotent_unique_'.time();

        // First attempt
        $res1 = $this->actingAs($this->user)
            ->postJson("/messages/{$this->conversation->id}/send", [
                'body' => 'Idempotent message test',
                'client_message_id' => $clientMsgId,
            ]);
        $res1->assertStatus(201);

        // Second attempt with exact same client_message_id (e.g. user retried after network hiccup)
        $res2 = $this->actingAs($this->user)
            ->postJson("/messages/{$this->conversation->id}/send", [
                'body' => 'Idempotent message test',
                'client_message_id' => $clientMsgId,
            ]);
        $res2->assertStatus(201);

        // Verify only 1 record exists with this body and client_message_id
        $count = Message::where('conversation_id', $this->conversation->id)
            ->where('body', 'Idempotent message test')
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * Test empty or whitespace-only messages are rejected with 422.
     */
    public function test_empty_or_whitespace_only_messages_are_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/messages/{$this->conversation->id}/send", [
                'body' => '     ',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $this->conversation->id,
            'body' => '     ',
        ]);
    }

    /**
     * Test unauthorized user cannot send message to a conversation they are not participant in.
     */
    public function test_user_cannot_send_message_to_conversation_they_are_not_part_of(): void
    {
        $stranger = User::factory()->create(['name' => 'Stranger Danger']);
        UserProfile::create(['user_id' => $stranger->id]);

        $response = $this->actingAs($stranger)
            ->postJson("/messages/{$this->conversation->id}/send", [
                'body' => 'Trying to intrude into private chat',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test view provides userToken and responsive header layout structure.
     */
    public function test_messenger_view_provides_user_token_and_responsive_header_classes(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('messages.show', ['id' => $this->conversation->id]));

        $response->assertStatus(200);
        $response->assertViewHas('userToken');

        $userToken = $response->viewData('userToken');
        $this->assertIsString($userToken);
        $this->assertNotEmpty($userToken);

        // Verify header elements and responsive classes exist in HTML
        $content = $response->getContent();
        $this->assertStringContainsString('chat-header-info', $content);
        $this->assertStringContainsString('chat-header-name', $content);
        $this->assertStringContainsString('chat-header-status', $content);
        $this->assertStringContainsString('btn-header-profile', $content);
        $this->assertStringContainsString('btn-header-search', $content);
        $this->assertStringContainsString('btn-header-multicall', $content);
        $this->assertStringContainsString('btn-header-audiocall', $content);
        $this->assertStringContainsString('btn-header-videocall', $content);
        $this->assertStringContainsString('btn-header-details', $content);
        $this->assertStringContainsString('btn-send-message', $content);
        $this->assertStringContainsString('placeholder="বার্তা লিখুন..."', $content);
    }
}
