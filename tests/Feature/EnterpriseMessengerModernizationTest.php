<?php

namespace Tests\Feature;

use App\Events\CallEnded;
use App\Events\CallInitiated;
use App\Events\CallResponded;
use App\Events\CallSignalSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EnterpriseMessengerModernizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test full message lifecycle: sending with client_message_id, delivery receipts, and read status.
     */
    public function test_enterprise_message_lifecycle_and_delivery_receipts(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($alice, $bob->id);

        $clientMsgId = 'msg_test_uuid_'.uniqid();

        // 1. Send message with client_message_id
        $response = $this->actingAs($alice)->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Next-gen enterprise message test',
            'client_message_id' => $clientMsgId,
        ]);

        $response->assertStatus(201);
        $messageData = $response->json('data');
        $this->assertEquals('Next-gen enterprise message test', $messageData['body']);
        $messageId = $messageData['id'];

        // 2. Test idempotency: sending again with same client_message_id returns 201 with existing message id
        $retryResponse = $this->actingAs($alice)->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Next-gen enterprise message test duplicate',
            'client_message_id' => $clientMsgId,
        ]);

        $retryResponse->assertStatus(201);
        $this->assertEquals($messageId, $retryResponse->json('data.id'));

        // 3. Bob marks message as delivered
        $deliverResponse = $this->actingAs($bob)->postJson("/api/v1/messages/{$messageId}/delivered");
        $deliverResponse->assertStatus(200);

        $freshMsg = Message::find($messageId);
        $this->assertNotNull($freshMsg->delivered_at);

        // 4. Bob marks conversation as read
        $readResponse = $this->actingAs($bob)->postJson("/api/v1/conversations/{$conv->id}/read");
        $readResponse->assertStatus(200);

        $freshMsg->refresh();
        $this->assertNotNull($freshMsg->read_at);
        $this->assertContains($freshMsg->delivery_status, ['read', 'seen']);
    }

    /**
     * Test message editing, version history, and deletion.
     */
    public function test_enterprise_message_editing_and_deletion(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);

        $msg = $messengerService->sendMessage($sender, $conv, [
            'body' => 'Original text before edit',
        ]);

        // Edit message
        $editResponse = $this->actingAs($sender)->putJson("/api/v1/messages/{$msg->id}", [
            'body' => 'Updated text with corrections',
        ]);

        $editResponse->assertStatus(200);
        $this->assertEquals('Updated text with corrections', $editResponse->json('data.body'));
        $this->assertTrue($editResponse->json('data.is_edited'));

        // Delete for everyone
        $deleteResponse = $this->actingAs($sender)->deleteJson("/api/v1/messages/{$msg->id}", [
            'for_everyone' => true,
        ]);

        $deleteResponse->assertStatus(200);
        $msg->refresh();
        $this->assertNotNull($msg->deleted_at);
        $this->assertTrue((bool) $msg->is_deleted_for_everyone);
    }

    /**
     * Test message reactions and pins.
     */
    public function test_message_reactions_and_pinning(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        $msg = $messengerService->sendMessage($user1, $conv, [
            'body' => 'Important pinned announcement',
        ]);

        // React to message via /messages/{id}/reactions
        $reactResponse = $this->actingAs($user2)->postJson("/api/v1/messages/{$msg->id}/reactions", [
            'reaction' => '🔥',
        ]);

        $this->assertContains($reactResponse->status(), [200, 201]);
        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $msg->id,
            'user_id' => $user2->id,
            'reaction' => '🔥',
        ]);

        // Pin message via /conversations/{id}/messages/{messageId}/pin
        $pinResponse = $this->actingAs($user1)->postJson("/api/v1/conversations/{$conv->id}/messages/{$msg->id}/pin");
        $this->assertContains($pinResponse->status(), [200, 201]);

        // Fetch pinned messages via /conversations/{id}/pins
        $pinnedList = $this->actingAs($user2)->getJson("/api/v1/conversations/{$conv->id}/pins");
        $pinnedList->assertStatus(200);
        $this->assertCount(1, $pinnedList->json('data'));
        $this->assertEquals($msg->id, $pinnedList->json('data.0.id') ?? $pinnedList->json('data.0.message_id'));
    }

    /**
     * Test WebRTC call signaling, active calls query, and ICE servers.
     */
    public function test_enterprise_calling_ice_servers_and_signaling(): void
    {
        Event::fake([
            CallInitiated::class,
            CallResponded::class,
            CallSignalSent::class,
            CallEnded::class,
        ]);

        $caller = User::factory()->create(['name' => 'Caller']);
        $callee = User::factory()->create(['name' => 'Callee']);

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($caller, $callee->id);

        // 1. Fetch ICE servers
        $iceResponse = $this->actingAs($caller)->getJson('/api/v1/calls/ice-servers');
        $iceResponse->assertStatus(200);
        $iceResponse->assertJsonStructure(['data' => ['ice_servers']]);

        // 2. Initiate Call
        $callResponse = $this->actingAs($caller)->postJson('/api/v1/calls', [
            'conversation_id' => $conv->id,
            'call_type' => 'video',
            'type' => 'video',
        ]);

        $callResponse->assertStatus(201);
        $callId = $callResponse->json('data.id');

        // 3. Query active calls
        $activeResponse = $this->actingAs($callee)->getJson('/api/v1/calls/active');
        $activeResponse->assertStatus(200);
        $this->assertNotEmpty($activeResponse->json('data'));

        // 4. Send WebRTC signaling offer
        $signalResponse = $this->actingAs($caller)->postJson("/api/v1/calls/{$callId}/signal", [
            'target_user_id' => $callee->id,
            'signal_type' => 'offer',
            'type' => 'offer',
            'payload' => ['sdp' => 'v=0\r\no=alice...'],
        ]);

        $signalResponse->assertStatus(200);

        // 5. Respond / Accept Call
        $acceptResponse = $this->actingAs($callee)->postJson("/api/v1/calls/{$callId}/respond", [
            'action' => 'accept',
        ]);

        $acceptResponse->assertStatus(200);

        // 6. Leave / End Call
        $endResponse = $this->actingAs($caller)->postJson("/api/v1/calls/{$callId}/leave");
        $endResponse->assertStatus(200);
    }

    /**
     * Test real-time offline sync endpoint.
     */
    public function test_messenger_sync_endpoint(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/messenger/sync');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'events',
                'latest_event_id',
            ],
        ]);
    }
}
