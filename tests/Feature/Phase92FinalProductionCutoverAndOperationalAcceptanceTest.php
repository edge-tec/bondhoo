<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase92FinalProductionCutoverAndOperationalAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;

    protected User $participant;

    protected User $intruder;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'name' => 'Phase 9.2 Lead Operator',
            'username' => 'operator_phase92',
            'email' => 'operator92@jugajug.com',
        ]);

        $this->participant = User::factory()->create([
            'name' => 'Phase 9.2 Participant',
            'username' => 'participant_phase92',
            'email' => 'participant92@jugajug.com',
        ]);

        $this->intruder = User::factory()->create([
            'name' => 'Phase 9.2 Intruder',
            'username' => 'intruder_phase92',
            'email' => 'intruder92@jugajug.com',
        ]);

        $messengerService = app(MessengerServiceInterface::class);
        $this->conversation = $messengerService->getOrCreateDirectConversation($this->operator, $this->participant->id);
    }

    /**
     * Test 1: Verify Phase 9.2 release manifest and documentation deliverables.
     */
    public function test_phase92_release_manifest_and_documentation_presence(): void
    {
        $this->assertTrue(file_exists(base_path('deploy.sh')));
        $this->assertTrue(file_exists(base_path('docs/phase9-2/PHASE91_EVIDENCE_RECONCILIATION.md')));
        $this->assertTrue(file_exists(base_path('docs/phase9-2/LIVE_INFRASTRUCTURE_OPERATIONAL_ACCEPTANCE.md')));
        $this->assertTrue(file_exists(base_path('docs/phase9-2/FINAL_PRODUCTION_BACKUP_AND_RECOVERY_ACCEPTANCE.md')));
        $this->assertTrue(file_exists(base_path('docs/phase9-2/FINAL_GO_LIVE_APPROVAL.md')));
    }

    /**
     * Test 2: Verify production MySQL core schema tables.
     */
    public function test_mysql_core_tables_integrity(): void
    {
        $coreTables = [
            'users',
            'conversations',
            'conversation_participants',
            'messages',
            'message_reactions',
            'calls',
            'call_participants',
            'reels',
            'reel_reactions',
            'reel_comments',
            'posts',
            'comments',
            'backups',
            'backup_restore_drills',
            'audit_logs',
            'admin_alerts',
        ];

        foreach ($coreTables as $table) {
            $this->assertTrue(
                DB::getSchemaBuilder()->hasTable($table),
                "Database schema must contain core table: {$table}"
            );
        }
    }

    /**
     * Test 3: Storage operational round-trip (Put, Get, Verify, Delete).
     */
    public function test_storage_operational_roundtrip(): void
    {
        $testPayload = 'PHASE_9_2_STORAGE_TEST_'.Str::random(16);
        $filename = 'test_phase92_roundtrip.txt';

        // 1. Put
        $putSuccess = Storage::disk('local')->put($filename, $testPayload);
        $this->assertTrue($putSuccess);

        // 2. Get & Verify
        $retrieved = Storage::disk('local')->get($filename);
        $this->assertEquals($testPayload, $retrieved);

        // 3. Delete & Confirm
        Storage::disk('local')->delete($filename);
        $this->assertFalse(Storage::disk('local')->exists($filename));
    }

    /**
     * Test 4: Messenger client message ID idempotency and intruder protection.
     */
    public function test_messenger_idempotency_and_intruder_protection(): void
    {
        $clientMessageId = 'msg_'.Str::uuid()->toString();

        // 1. Authorized operator sends message
        $response = $this->actingAs($this->operator, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Phase 9.2 Launch Ready Message',
            'client_message_id' => $clientMessageId,
        ]);
        $response->assertStatus(201);
        $messageId = $response->json('data.id');

        // 2. Duplicate send with same client_message_id returns existing message
        $duplicate = $this->actingAs($this->operator, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Phase 9.2 Launch Ready Message',
            'client_message_id' => $clientMessageId,
        ]);
        $duplicate->assertStatus(201);
        $this->assertEquals($messageId, $duplicate->json('data.id'));

        // 3. Unauthorized intruder is strictly forbidden
        $forbidden = $this->actingAs($this->intruder, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Intrusion probe',
            'client_message_id' => 'msg_'.Str::uuid()->toString(),
        ]);
        $forbidden->assertStatus(403);
    }

    /**
     * Test 5: WebRTC calling participant isolation.
     */
    public function test_webrtc_calling_participant_isolation(): void
    {
        // 1. Initiate call between operator and participant
        $callRes = $this->actingAs($this->operator, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'receiver_id' => $this->participant->id,
            'call_type' => 'video',
        ]);
        $callRes->assertStatus(201);
        $callId = $callRes->json('data.id');

        // 2. Unauthorized user cannot signal into call
        $signalRes = $this->actingAs($this->intruder, 'sanctum')->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'offer',
            'payload' => ['sdp' => 'v=0...'],
        ]);
        $signalRes->assertStatus(403);

        // 3. Legitimate participant can signal into call
        $validSignal = $this->actingAs($this->participant, 'sanctum')->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'candidate',
            'payload' => ['candidate' => 'test-candidate'],
        ]);
        $validSignal->assertStatus(200);
    }

    /**
     * Test 6: Production health check endpoint observability.
     */
    public function test_production_health_check_endpoint(): void
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'status',
                'services' => [
                    'database',
                    'storage',
                    'queue',
                ],
            ],
        ]);
    }
}
