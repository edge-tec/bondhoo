<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase91FinalOperatorExecutionAndGoLiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;

    protected User $participant;

    protected User $unauthorizedUser;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'name' => 'Phase 9.1 Lead Operator',
            'username' => 'operator_phase91',
            'email' => 'operator91@jugajug.com',
        ]);

        $this->participant = User::factory()->create([
            'name' => 'Phase 9.1 Participant',
            'username' => 'participant_phase91',
            'email' => 'participant91@jugajug.com',
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'name' => 'Phase 9.1 Intruder',
            'username' => 'intruder_phase91',
            'email' => 'intruder91@jugajug.com',
        ]);

        $messengerService = app(MessengerServiceInterface::class);
        $this->conversation = $messengerService->getOrCreateDirectConversation($this->operator, $this->participant->id);
    }

    /**
     * Test 1: Verify Phase 9.1 release assets and zero-downtime deployment script.
     */
    public function test_phase91_release_assets_and_deployment_script(): void
    {
        $this->assertTrue(file_exists(base_path('deploy.sh')));
        $deployContent = file_get_contents(base_path('deploy.sh'));
        $this->assertStringContainsString('php8.5-fpm', $deployContent);
        $this->assertStringContainsString('ln -nfs', $deployContent);
        $this->assertStringContainsString('/api/v1/health', $deployContent);
    }

    /**
     * Test 2: Verify production MySQL table mapping invariants.
     */
    public function test_mysql_core_tables_and_indexes_exist(): void
    {
        $coreTables = [
            'users',
            'conversations',
            'conversation_participants',
            'messages',
            'message_reactions',
            'pinned_messages',
            'saved_messages',
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
     * Test 3: Messenger real-time message idempotency and eavesdropper protection.
     */
    public function test_messenger_idempotency_and_security_boundary(): void
    {
        $clientMessageId = 'msg_'.Str::uuid()->toString();

        // 1. Authorized operator sends message
        $response = $this->actingAs($this->operator, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Phase 9.1 Launch Ready Message',
            'client_message_id' => $clientMessageId,
        ]);
        $response->assertStatus(201);
        $messageId = $response->json('data.id');

        // 2. Duplicate send with same client_message_id returns existing message
        $duplicate = $this->actingAs($this->operator, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Phase 9.1 Launch Ready Message',
            'client_message_id' => $clientMessageId,
        ]);
        $duplicate->assertStatus(201);
        $this->assertEquals($messageId, $duplicate->json('data.id'));

        // 3. Unauthorized intruder is strictly forbidden
        $forbidden = $this->actingAs($this->unauthorizedUser, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Intrusion attempt',
            'client_message_id' => 'msg_'.Str::uuid()->toString(),
        ]);
        $forbidden->assertStatus(403);
    }

    /**
     * Test 4: WebRTC Calling signaling authentication and participant verification.
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
        $signalRes = $this->actingAs($this->unauthorizedUser, 'sanctum')->postJson("/api/v1/calls/{$callId}/signal", [
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
     * Test 5: Production health check endpoint returns 200 OK with expected schema.
     */
    public function test_production_health_check_endpoint(): void
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }
}
