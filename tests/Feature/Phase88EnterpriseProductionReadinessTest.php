<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\BackupService;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase88EnterpriseProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected User $alice;

    protected User $bob;

    protected User $mallory;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = User::factory()->create([
            'name' => 'Alice Admin',
            'username' => 'alice_p88',
            'email' => 'alice88@example.com',
        ]);

        $this->bob = User::factory()->create([
            'name' => 'Bob Member',
            'username' => 'bob_p88',
            'email' => 'bob88@example.com',
        ]);

        $this->mallory = User::factory()->create([
            'name' => 'Mallory Attacker',
            'username' => 'mallory_p88',
            'email' => 'mallory88@example.com',
        ]);

        $messengerService = app(MessengerServiceInterface::class);
        $this->conversation = $messengerService->getOrCreateDirectConversation($this->alice, $this->bob->id);
    }

    /**
     * Test 1: Message identifier isolation — Attacker cannot hijack or read another user's message using client_message_id.
     */
    public function test_client_message_id_cannot_be_manipulated_across_users(): void
    {
        $sharedClientMsgId = 'msg_'.Str::uuid()->toString();

        // 1. Alice sends an authorized message with client_message_id
        $aliceRes = $this->actingAs($this->alice, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Alice secret communication',
            'client_message_id' => $sharedClientMsgId,
        ]);

        $aliceRes->assertStatus(201);
        $aliceMsgId = $aliceRes->json('data.id');

        // 2. Mallory (outsider to conversation) attempts to submit with Alice's client_message_id -> 403 Forbidden
        $malloryOutsiderRes = $this->actingAs($this->mallory, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Mallory exploit probe',
            'client_message_id' => $sharedClientMsgId,
        ]);
        $malloryOutsiderRes->assertStatus(403);

        // 3. Mallory creates her own conversation with Bob and tries to reuse Alice's client_message_id
        $messengerService = app(MessengerServiceInterface::class);
        $malloryBobConv = $messengerService->getOrCreateDirectConversation($this->mallory, $this->bob->id);

        $malloryRes = $this->actingAs($this->mallory, 'sanctum')->postJson("/api/v1/conversations/{$malloryBobConv->id}/messages", [
            'body' => 'Mallory separate text',
            'client_message_id' => $sharedClientMsgId,
        ]);

        $malloryRes->assertStatus(201);
        $malloryMsgId = $malloryRes->json('data.id');

        // Mallory must get her own message, NEVER Alice's message ID or content
        $this->assertNotEquals($aliceMsgId, $malloryMsgId, 'Mallory must not receive Alice message ID');
        $this->assertEquals('Mallory separate text', $malloryRes->json('data.body'));
    }

    /**
     * Test 2: Idempotent message retry produces exact same message ID and 0 duplicate rows.
     */
    public function test_message_retry_deduplication_integrity(): void
    {
        $secureUuid = 'msg_'.Str::uuid()->toString();

        // Initial submission
        $res1 = $this->actingAs($this->alice, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Network retry verification message',
            'client_message_id' => $secureUuid,
        ]);
        $res1->assertStatus(201);
        $originalId = $res1->json('data.id');

        // Network retransmission (e.g. timeout retry from mobile client)
        $res2 = $this->actingAs($this->alice, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Network retry verification message',
            'client_message_id' => $secureUuid,
        ]);
        $res2->assertStatus(201);
        $retryId = $res2->json('data.id');

        $this->assertEquals($originalId, $retryId, 'Retransmitted message must return original message ID');

        $count = Message::where('conversation_id', $this->conversation->id)
            ->where('sender_id', $this->alice->id)
            ->where('metadata->client_message_id', $secureUuid)
            ->count();

        $this->assertEquals(1, $count, 'Exactly 1 message row must exist in the database');
    }

    /**
     * Test 3: Disaster Recovery Key Rotation Drill (Gate D) execution.
     */
    public function test_disaster_recovery_key_rotation_and_recovery_drill(): void
    {
        $backupService = app(BackupService::class);
        $drill = $backupService->runKeyRotationAndRecoveryDrill();

        $this->assertTrue($drill['passed'], 'Key rotation and recovery drill must pass');
        $this->assertEquals('AUTOMATED_AND_DRILL_VERIFIED', $drill['gate_d_status']);
        $this->assertTrue($drill['checks']['v1_encryption_and_recovery_ok']);
        $this->assertTrue($drill['checks']['wrong_key_rejected_by_hmac']);
        $this->assertTrue($drill['checks']['historical_v1_recoverable_after_rotation']);
    }

    /**
     * Test 4: Eager loading and message query performance.
     */
    public function test_messages_index_eager_loading_and_authorization(): void
    {
        Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->alice->id,
            'body' => 'Sample test message',
            'type' => 'text',
        ]);

        $response = $this->actingAs($this->alice, 'sanctum')
            ->getJson("/api/v1/conversations/{$this->conversation->id}/messages");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'conversation_id',
                    'sender' => ['id', 'name'],
                    'body',
                    'type',
                    'is_mine',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'total'],
        ]);
    }
}
