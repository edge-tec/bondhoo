<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseMessengerRealtimeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Test 1-on-1 Conversation Creation and Retrieval.
     */
    public function test_direct_conversation_creation_and_listing(): void
    {
        $user1 = User::factory()->create(['name' => 'Mizanur Rahman']);
        $user2 = User::factory()->create(['name' => 'Sadia Islam']);

        $res = $this->actingAs($user1)->postJson('/api/v1/conversations', [
            'type' => 'direct',
            'recipient_id' => $user2->id,
        ]);

        $res->assertStatus(201);
        $convId = $res->json('data.id');
        $this->assertNotNull($convId);

        // List conversations for user1
        $listRes = $this->actingAs($user1)->getJson('/api/v1/conversations');
        $listRes->assertStatus(200);
        $this->assertNotEmpty($listRes->json('data'));
    }

    /**
     * 2. Test Message Sending with Client Message ID and Idempotency.
     */
    public function test_send_message_with_client_id_and_idempotency(): void
    {
        $user1 = User::factory()->create(['name' => 'Mizanur Rahman']);
        $user2 = User::factory()->create(['name' => 'Rahim Ahmed']);

        // Create direct conversation
        $convRes = $this->actingAs($user1)->postJson('/api/v1/conversations', [
            'type' => 'direct',
            'recipient_id' => $user2->id,
        ]);
        $convId = $convRes->json('data.id');

        $clientMessageId = 'client_uuid_abc_123';

        // Send message
        $sendRes = $this->actingAs($user1)->postJson("/api/v1/conversations/{$convId}/messages", [
            'body' => 'See you soon! 👋',
            'type' => 'text',
            'client_message_id' => $clientMessageId,
        ]);

        $sendRes->assertStatus(201);
        $messageId = $sendRes->json('data.id');
        $this->assertNotNull($messageId);

        // Resend identical message with same client_message_id
        $dupRes = $this->actingAs($user1)->postJson("/api/v1/conversations/{$convId}/messages", [
            'body' => 'See you soon! 👋',
            'type' => 'text',
            'client_message_id' => $clientMessageId,
        ]);

        // Backend returns the existing message without creating duplicate
        $dupRes->assertSuccessful();
        $this->assertEquals($messageId, $dupRes->json('data.id'));
    }

    /**
     * 3. Test Message Delivery Status (Delivered and Read Receipts).
     */
    public function test_message_delivery_and_read_receipts(): void
    {
        $sender = User::factory()->create(['name' => 'Sender']);
        $recipient = User::factory()->create(['name' => 'Recipient']);

        $convRes = $this->actingAs($sender)->postJson('/api/v1/conversations', [
            'type' => 'direct',
            'recipient_id' => $recipient->id,
        ]);
        $convId = $convRes->json('data.id');

        // Sender writes message
        $sendRes = $this->actingAs($sender)->postJson("/api/v1/conversations/{$convId}/messages", [
            'body' => 'Hello there, can we connect?',
            'type' => 'text',
        ]);
        $sendRes->assertStatus(201);
        $messageId = $sendRes->json('data.id');

        // Recipient marks message as delivered
        $deliveredRes = $this->actingAs($recipient)->postJson("/api/v1/messages/{$messageId}/delivered");
        $deliveredRes->assertStatus(200);
        $this->assertEquals('delivered', $deliveredRes->json('data.delivery_status'));

        // Recipient marks conversation as read
        $readRes = $this->actingAs($recipient)->postJson("/api/v1/conversations/{$convId}/read");
        $readRes->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $readRes->json('data.messages_marked_read'));
    }

    /**
     * 4. Test Emoji Reaction on Message.
     */
    public function test_message_emoji_reaction(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $convRes = $this->actingAs($user1)->postJson('/api/v1/conversations', [
            'type' => 'direct',
            'recipient_id' => $user2->id,
        ]);
        $convId = $convRes->json('data.id');

        $msgRes = $this->actingAs($user1)->postJson("/api/v1/conversations/{$convId}/messages", [
            'body' => 'Great news today!',
            'type' => 'text',
        ]);
        $messageId = $msgRes->json('data.id');

        // User2 reacts with ❤️
        $reactRes = $this->actingAs($user2)->postJson("/api/v1/messages/{$messageId}/reactions", [
            'reaction' => '❤️',
        ]);
        $reactRes->assertStatus(200);
    }

    /**
     * 5. Test Message Deletion (Delete For Me).
     */
    public function test_message_delete_for_me(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $convRes = $this->actingAs($user1)->postJson('/api/v1/conversations', [
            'type' => 'direct',
            'recipient_id' => $user2->id,
        ]);
        $convId = $convRes->json('data.id');

        $msgRes = $this->actingAs($user1)->postJson("/api/v1/conversations/{$convId}/messages", [
            'body' => 'A private message to remove locally.',
            'type' => 'text',
        ]);
        $messageId = $msgRes->json('data.id');

        // Delete for me
        $delRes = $this->actingAs($user1)->postJson("/api/v1/messages/{$messageId}/delete-for-me");
        $delRes->assertStatus(200);
    }

    /**
     * 6. Test Unread Message Count.
     */
    public function test_unread_message_count_flow(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $convRes = $this->actingAs($sender)->postJson('/api/v1/conversations', [
            'type' => 'direct',
            'recipient_id' => $recipient->id,
        ]);
        $convId = $convRes->json('data.id');

        $this->actingAs($sender)->postJson("/api/v1/conversations/{$convId}/messages", [
            'body' => 'Unread message 1',
            'type' => 'text',
        ]);

        $unreadRes = $this->actingAs($recipient)->getJson('/api/v1/messages/unread-count');
        $unreadRes->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $unreadRes->json('data.unread_count'));
    }
}
