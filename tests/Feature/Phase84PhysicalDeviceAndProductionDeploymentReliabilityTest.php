<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Phase84PhysicalDeviceAndProductionDeploymentReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Prolonged Stress & Reliability Execution (250 Reel transitions & 500 concurrent messages)
     */
    public function test_prolonged_stress_and_reliability_runner(): void
    {
        $stressScript = base_path('tests/Javascript/phase84-prolonged-stress-runner.mjs');
        $this->assertFileExists($stressScript);

        $output = shell_exec("node {$stressScript} 2>&1");
        $this->assertNotEmpty($output, 'Prolonged stress runner should produce JSON output');

        $result = json_decode($output, true);
        $this->assertIsArray($result, 'Output must be valid JSON: '.$output);
        $this->assertSame('SUCCESS', $result['status']);
        $this->assertSame('8.4', $result['phase']);

        $metrics = $result['metrics'];
        $this->assertGreaterThanOrEqual(200, $metrics['transitionsCompleted']);
        $this->assertGreaterThanOrEqual(500, $metrics['messagesProcessed']);
        $this->assertSame(0, $metrics['concurrentPlayViolations']);
        $this->assertSame(0, $metrics['unhandledPromiseRejections']);
        $this->assertGreaterThanOrEqual(1, $metrics['failedPreloadsIsolated']);
        $this->assertGreaterThanOrEqual(1, $metrics['bufferingStallRecoveries']);
        $this->assertGreaterThanOrEqual(1, $metrics['visibilityResumesWithoutReset']);
        $this->assertGreaterThanOrEqual(20, $metrics['duplicateMessagesSuppressed']);
        $this->assertLessThan(5.0, $metrics['memoryDeltaMB'], 'Memory growth across extended run must be bounded under 5MB');
    }

    /**
     * 2. Production Health Check, Liveness and Readiness Probes
     */
    public function test_production_health_and_probes_endpoints_without_secret_leakage(): void
    {
        // 1. /api/v1/health
        $healthRes = $this->getJson('/api/v1/health');
        $healthRes->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'status',
                    'services' => [
                        'database' => ['status', 'driver', 'latency_ms'],
                        'redis_cache' => ['status'],
                        'queue' => ['status', 'default_connection'],
                        'storage' => ['status', 'disk'],
                    ],
                ],
            ]);

        $rawBody = $healthRes->getContent();
        $this->assertStringNotContainsString('password', strtolower($rawBody));
        $this->assertStringNotContainsString('app_key', strtolower($rawBody));

        // 2. /api/v2/health/live
        $liveRes = $this->getJson('/api/v2/health/live');
        $liveRes->assertStatus(200);

        // 3. /api/v2/health/ready
        $readyRes = $this->getJson('/api/v2/health/ready');
        $this->assertContains($readyRes->status(), [200, 503]);
    }

    /**
     * 3. Deployment Script & Rollback Architecture Verification
     */
    public function test_zero_downtime_deployment_and_rollback_script_integrity(): void
    {
        $deployScript = base_path('deploy.sh');
        $this->assertFileExists($deployScript);

        $content = file_get_contents($deployScript);

        // Zero downtime release directory and symlinking
        $this->assertStringContainsString('RELEASE_DIR=', $content);
        $this->assertStringContainsString('ln -nfs', $content);
        $this->assertStringContainsString('${APP_DIR}/current', $content);

        // Optimization caches
        $this->assertStringContainsString('php artisan config:cache', $content);
        $this->assertStringContainsString('php artisan route:cache', $content);
        $this->assertStringContainsString('php artisan view:cache', $content);

        // Migration safety
        $this->assertStringContainsString('php artisan migrate --force', $content);

        // Health verification and automated rollback
        $this->assertStringContainsString('/api/v1/health', $content);
        $this->assertStringContainsString('PREVIOUS_RELEASE=', $content);
        $this->assertStringContainsString('Triggering automatic rollback', $content);
    }

    /**
     * 4. Disaster Recovery & Encryption Key Rotation Drill
     */
    public function test_disaster_recovery_key_rotation_and_recovery_drill(): void
    {
        $exitCode = Artisan::call('admin:backup-verify', ['--key-drill' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode, 'Key rotation drill must exit with 0: '.$output);
        $this->assertStringContainsString('Status: PASSED', $output);
        $this->assertStringContainsString('[OK] 1_create_and_verify_v1_backup', $output);
        $this->assertStringContainsString('[OK] 2_reject_wrong_decryption_key', $output);
        $this->assertStringContainsString('[OK] 2b_reject_tampered_ciphertext', $output);
        $this->assertStringContainsString('[OK] 4_key_rotation_v2_new_backup', $output);
        $this->assertStringContainsString('[OK] 5_historical_v1_backup_decryptable_after_rotation', $output);
    }

    /**
     * 5. Security & Privacy: Messenger Conversation Isolation
     */
    public function test_messenger_conversation_strict_isolation_and_unauthorized_access_prevention(): void
    {
        $alice = User::factory()->create([
            'username' => 'phase84_alice',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $bob = User::factory()->create([
            'username' => 'phase84_bob',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $eve = User::factory()->create([
            'username' => 'phase84_eve',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Private conversation between Alice and Bob
        $conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
            'creator_id' => $alice->id,
            'last_message_at' => now(),
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $alice->id,
            'role' => 'member',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $bob->id,
            'role' => 'member',
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $alice->id,
            'body' => 'Confidential enterprise communication between Alice and Bob.',
            'type' => 'text',
        ]);

        // Eve (outsider) attempts to read Alice & Bob's conversation messages
        $eveReadAttempt = $this->actingAs($eve, 'sanctum')->getJson("/api/v1/conversations/{$conversation->id}/messages");
        $this->assertContains($eveReadAttempt->status(), [403, 404], 'Eve must be rejected with 403 Forbidden or 404 Not Found');

        // Eve attempts to inject a message into Alice & Bob's conversation
        $eveSendAttempt = $this->actingAs($eve, 'sanctum')->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'body' => 'Malicious injection from Eve',
        ]);
        $this->assertContains($eveSendAttempt->status(), [403, 404], 'Eve cannot inject messages into conversations she does not belong to');

        // Alice (legitimate participant) CAN read messages
        $aliceRead = $this->actingAs($alice, 'sanctum')->getJson("/api/v1/conversations/{$conversation->id}/messages");
        $aliceRead->assertStatus(200);
        $this->assertStringContainsString('Confidential enterprise communication', $aliceRead->getContent());
    }
}
