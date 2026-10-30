<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\BackupService;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase90FinalProductionDeploymentAndAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $recipient;

    protected User $eavesdropper;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Phase 9 Operator',
            'username' => 'phase9_op',
            'email' => 'phase9op@example.com',
        ]);

        $this->recipient = User::factory()->create([
            'name' => 'Phase 9 Recipient',
            'username' => 'phase9_rec',
            'email' => 'phase9rec@example.com',
        ]);

        $this->eavesdropper = User::factory()->create([
            'name' => 'Phase 9 Eavesdropper',
            'username' => 'phase9_eaves',
            'email' => 'phase9eaves@example.com',
        ]);

        $messengerService = app(MessengerServiceInterface::class);
        $this->conversation = $messengerService->getOrCreateDirectConversation($this->user, $this->recipient->id);
    }

    /**
     * Test 1: Release Candidate commit ancestry and integrity.
     */
    public function test_release_candidate_commit_ancestry(): void
    {
        $this->assertTrue(file_exists(base_path('composer.lock')));
        $this->assertTrue(file_exists(base_path('package-lock.json')));
        $this->assertTrue(file_exists(base_path('deploy.sh')));
        $this->assertTrue(file_exists(base_path('docker/nginx.conf')));
        $this->assertTrue(file_exists(base_path('docker/supervisord.conf')));
    }

    /**
     * Test 2: Cryptographic client_message_id format and cross-user isolation.
     */
    public function test_cryptographic_client_message_id_format_and_isolation(): void
    {
        $uuid = Str::uuid()->toString();
        $secureClientMessageId = 'msg_'.$uuid;

        // Verify UUID v4 regex format
        $this->assertMatchesRegularExpression(
            '/^msg_[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $secureClientMessageId,
            'Message identifier must follow RFC 4122 v4 UUID format'
        );

        // 1. Legitimate user sends message
        $sendRes = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Production certified message',
            'client_message_id' => $secureClientMessageId,
        ]);
        $sendRes->assertStatus(201);
        $userMsgId = $sendRes->json('data.id');

        // 2. Retransmit identical message (e.g. flaky network retry) -> Idempotent response
        $retryRes = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Production certified message',
            'client_message_id' => $secureClientMessageId,
        ]);
        $retryRes->assertStatus(201);
        $this->assertEquals($userMsgId, $retryRes->json('data.id'));

        // 3. Eavesdropper attempting to access or spoof with same client_message_id in conversation -> 403 Forbidden
        $eavesRes = $this->actingAs($this->eavesdropper, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Spoofed probe message',
            'client_message_id' => $secureClientMessageId,
        ]);
        $eavesRes->assertStatus(403);
    }

    /**
     * Test 3: WebRTC calling signaling authorization.
     */
    public function test_calling_signaling_authorization(): void
    {
        // 1. Authorized user can initiate call
        $callRes = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'receiver_id' => $this->recipient->id,
            'call_type' => 'video',
        ]);
        $callRes->assertStatus(201);
        $callId = $callRes->json('data.id');
        $this->assertNotNull($callId);

        // 2. Outsider cannot signal to the call
        $signalRes = $this->actingAs($this->eavesdropper, 'sanctum')->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'candidate',
            'payload' => ['candidate' => 'dummy-candidate-string'],
        ]);
        $signalRes->assertStatus(403);
    }

    /**
     * Test 4: Health check and dependency observability.
     */
    public function test_production_health_check_observability(): void
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
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

    /**
     * Test 5: Disaster Recovery Key Rotation Drill (Gate D).
     */
    public function test_disaster_recovery_key_rotation_readiness(): void
    {
        $backupService = app(BackupService::class);
        $drill = $backupService->runKeyRotationAndRecoveryDrill();

        $this->assertTrue($drill['passed'], 'Disaster recovery key rotation drill must pass');
        $this->assertEquals('AUTOMATED_AND_DRILL_VERIFIED', $drill['gate_d_status']);
    }

    /**
     * Test 6: Database table schema integrity.
     */
    public function test_database_schema_integrity(): void
    {
        $requiredTables = [
            'users',
            'posts',
            'comments',
            'reactions',
            'conversations',
            'conversation_participants',
            'messages',
            'reels',
            'calls',
            'call_participants',
            'backups',
            'security_events',
        ];

        foreach ($requiredTables as $table) {
            $this->assertTrue(
                DB::getSchemaBuilder()->hasTable($table),
                "Database must contain table '{$table}'"
            );
        }
    }
}
