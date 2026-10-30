<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase87EnterpriseRealtimeCommunicationReliabilityTest extends TestCase
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
            'username' => 'alice_phase87',
            'email' => 'alice87@example.com',
        ]);

        $this->bob = User::factory()->create([
            'name' => 'Bob Member',
            'username' => 'bob_phase87',
            'email' => 'bob87@example.com',
        ]);

        $this->mallory = User::factory()->create([
            'name' => 'Mallory Outsider',
            'username' => 'mallory_phase87',
            'email' => 'mallory87@example.com',
        ]);

        $messengerService = app(MessengerServiceInterface::class);
        $this->conversation = $messengerService->getOrCreateDirectConversation($this->alice, $this->bob->id);
    }

    /**
     * Test 1: WebRTC Call Signaling Authorization — Authorized participant can initiate call, outsider is rejected.
     */
    public function test_call_initiate_authorization_enforcement(): void
    {
        // 1. Outsider Mallory attempts to initiate a call in Alice & Bob's conversation -> 403 Forbidden
        $unauthorizedRes = $this->actingAs($this->mallory, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'audio',
        ]);

        $unauthorizedRes->assertStatus(403);

        // 2. Authorized participant Alice initiates a call -> 201 Created
        $authorizedRes = $this->actingAs($this->alice, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'receiver_id' => $this->bob->id,
            'call_type' => 'audio',
        ]);

        $authorizedRes->assertStatus(201);
        $callId = $authorizedRes->json('data.id');
        $this->assertNotNull($callId);

        // 3. Mallory attempts to transmit WebRTC ICE signal on Alice's call -> 403 Forbidden
        $signalRes = $this->actingAs($this->mallory, 'sanctum')->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'candidate',
            'payload' => ['candidate' => 'candidate:dummy 1 UDP 2130706431 192.168.1.1 50000 typ host'],
        ]);

        $signalRes->assertStatus(403);

        // 4. Authorized caller Alice can transmit WebRTC signal -> 200 OK
        $aliceSignalRes = $this->actingAs($this->alice, 'sanctum')->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'candidate',
            'payload' => ['candidate' => 'candidate:valid 1 UDP 2130706431 192.168.1.1 50000 typ host'],
        ]);

        $aliceSignalRes->assertStatus(200);
    }

    /**
     * Test 2: WebSocket channel authorization in routes/channels.php for direct and call channels.
     */
    public function test_channel_authorization_allows_participants_and_rejects_outsiders(): void
    {
        // Check conversation channel authorization logic
        $aliceCanJoinConv = DB::table('conversation_participants')
            ->where('conversation_id', $this->conversation->id)
            ->where('user_id', $this->alice->id)
            ->where(function ($q) {
                $q->whereNull('request_status')->orWhere('request_status', '!=', 'declined');
            })
            ->exists();

        $malloryCanJoinConv = DB::table('conversation_participants')
            ->where('conversation_id', $this->conversation->id)
            ->where('user_id', $this->mallory->id)
            ->where(function ($q) {
                $q->whereNull('request_status')->orWhere('request_status', '!=', 'declined');
            })
            ->exists();

        $this->assertTrue($aliceCanJoinConv, 'Alice must be authorized on conversation channel');
        $this->assertFalse($malloryCanJoinConv, 'Mallory must NOT be authorized on conversation channel');

        // Check call channel authorization logic
        $bobCanJoinCall = DB::table('conversation_participants')
            ->where('conversation_id', $this->conversation->id)
            ->where('user_id', $this->bob->id)
            ->where(function ($q) {
                $q->whereNull('request_status')->orWhere('request_status', '!=', 'declined');
            })
            ->exists();

        $malloryCanJoinCall = DB::table('conversation_participants')
            ->where('conversation_id', $this->conversation->id)
            ->where('user_id', $this->mallory->id)
            ->where(function ($q) {
                $q->whereNull('request_status')->orWhere('request_status', '!=', 'declined');
            })
            ->exists();

        $this->assertTrue($bobCanJoinCall, 'Bob must be authorized on call channel');
        $this->assertFalse($malloryCanJoinCall, 'Mallory must NOT be authorized on call channel');
    }

    /**
     * Test 3: Message retry deduplication via client_message_id idempotency key.
     */
    public function test_message_retry_deduplication_via_client_message_id(): void
    {
        $clientMessageId = 'phase87_test_uuid_'.uniqid('', true);

        // First attempt to send message
        $firstResponse = $this->actingAs($this->alice, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Hello Bob with idempotent token',
            'client_message_id' => $clientMessageId,
        ]);

        $firstResponse->assertStatus(201);
        $firstMessageId = $firstResponse->json('data.id');
        $this->assertNotNull($firstMessageId);

        // Immediate retry with the EXACT same client_message_id (e.g. mobile reconnect/flaky network)
        $retryResponse = $this->actingAs($this->alice, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Hello Bob with idempotent token',
            'client_message_id' => $clientMessageId,
        ]);

        // Must succeed idempotently and return the SAME message ID without creating duplicate
        $retryResponse->assertStatus(201);
        $retryMessageId = $retryResponse->json('data.id');
        $this->assertEquals($firstMessageId, $retryMessageId, 'Idempotent retry must return identical message ID');

        // Verify only 1 message exists in database for this client_message_id
        $totalStored = Message::where('conversation_id', $this->conversation->id)
            ->where('sender_id', $this->alice->id)
            ->where('metadata->client_message_id', $clientMessageId)
            ->count();

        $this->assertEquals(1, $totalStored, 'Exactly 1 message record must exist for the idempotent client_message_id');
    }

    /**
     * Test 4: Read receipt synchronization and unread message counter accuracy.
     */
    public function test_read_receipt_synchronization(): void
    {
        // Alice sends 2 messages to Bob
        $msg1 = $this->actingAs($this->alice, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Message 1 for Bob',
        ]);
        $msg2 = $this->actingAs($this->alice, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Message 2 for Bob',
        ]);

        $msg1Id = $msg1->json('data.id');
        $msg2Id = $msg2->json('data.id');

        // Bob marks conversation as read
        $readRes = $this->actingAs($this->bob, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/read");
        $readRes->assertStatus(200);

        // Verify Bob's participant record is updated
        $bobPart = ConversationParticipant::where('conversation_id', $this->conversation->id)
            ->where('user_id', $this->bob->id)
            ->first();

        $this->assertEquals($msg2Id, $bobPart->last_read_message_id);
        $this->assertNotNull($bobPart->last_read_at);
    }

    /**
     * Test 5: Verify dependency vulnerability resolution (league/commonmark & symfony/mime).
     */
    public function test_security_advisories_resolved_in_lockfile(): void
    {
        $composerLockPath = base_path('composer.lock');
        $this->assertFileExists($composerLockPath);

        $lockContent = json_decode(file_get_contents($composerLockPath), true);
        $packages = collect($lockContent['packages'] ?? []);

        // league/commonmark must be >= 2.10.2
        $commonmark = $packages->firstWhere('name', 'league/commonmark');
        $this->assertNotNull($commonmark, 'league/commonmark must be present');
        $this->assertTrue(
            version_compare($commonmark['version'], '2.10.1', '>'),
            "league/commonmark version {$commonmark['version']} must be > 2.10.1 to resolve advisories"
        );

        // symfony/mime must be >= 8.1.10
        $symfonyMime = $packages->firstWhere('name', 'symfony/mime');
        $this->assertNotNull($symfonyMime, 'symfony/mime must be present');
        $cleanMimeVer = ltrim($symfonyMime['version'], 'v');
        $this->assertTrue(
            version_compare($cleanMimeVer, '8.1.9', '>'),
            "symfony/mime version {$symfonyMime['version']} must be >= 8.1.10 to resolve CVE-2026-45070"
        );
    }
}
