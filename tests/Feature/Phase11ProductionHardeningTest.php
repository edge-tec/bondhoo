<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\AIGateway;
use App\Services\AI\ClaudeProvider;
use App\Services\AI\GeminiProvider;
use App\Services\AI\OllamaProvider;
use App\Services\AI\OpenAIProvider;
use App\Services\Media\VideoTranscodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase11ProductionHardeningTest — Phase 11 Security & Reliability Hardening Verification
 */
class Phase11ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Command Injection Prevention in FFmpeg Video Transcoding
     */
    public function test_ffmpeg_command_generation_escapes_paths_and_sanitizes_watermarks(): void
    {
        $user = User::factory()->create();
        $media = Media::create([
            'user_id' => $user->id,
            'disk' => 'local',
            'original_path' => 'videos/sample; rm -rf /; echo "hacked".mp4',
            'original_name' => 'malicious.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'processing_status' => 'ready',
        ]);

        $transcoder = new VideoTranscodingService;
        $job = $transcoder->generateTranscodeJob($media, [
            'watermark' => 'Jugajug`touch /tmp/pwned`$(whoami);',
        ]);

        $cmd1080 = $job['commands']['1080p'];

        // The malicious path must be safely shell-escaped in single quotes
        $this->assertStringContainsString("'videos/sample; rm -rf /; echo \"hacked\".mp4'", $cmd1080);

        // Dangerous shell injection tokens must be stripped from watermark
        $this->assertStringNotContainsString('`touch', $cmd1080);
        $this->assertStringNotContainsString('$(whoami)', $cmd1080);
        $this->assertStringContainsString("text='Jugajugtouch tmppwnedwhoami'", $cmd1080);
    }

    /**
     * 2. AI Usage Endpoint Authorization & Information Disclosure Prevention
     */
    public function test_ai_usage_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/ai/usage');
        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Unauthorized. Authentication required to view AI usage metrics.',
        ]);
    }

    public function test_ai_usage_endpoint_scoped_to_regular_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v2/ai/usage');
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'scope' => 'user',
        ]);
        $this->assertSame($user->id, $response->json('summary.user_id'));
        $this->assertArrayHasKey('daily_quota', $response->json('summary'));
    }

    public function test_ai_usage_endpoint_allows_admin_system_scope(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN', 'guard_name' => 'sanctum']);
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v2/ai/usage');
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'scope' => 'system',
        ]);
        $this->assertArrayHasKey('total_tokens', $response->json('summary'));
        $this->assertArrayHasKey('total_cost_usd', $response->json('summary'));
    }

    /**
     * 3. ActivityPub Inbound Schema Validation & Malformed Payload Rejection
     */
    public function test_activitypub_inbox_rejects_malformed_payload(): void
    {
        // Missing @context and id
        $malformed = [
            'type' => 'Create',
            'actor' => 'https://remote.social/users/bad',
        ];

        $response = $this->postJson('/api/v2/federation/inbox', $malformed);
        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'rejected',
        ]);
    }

    public function test_activitypub_inbox_rejects_invalid_activity_type(): void
    {
        $invalidType = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://remote.social/activities/hack',
            'type' => 'ExecuteRemoteCommand',
            'actor' => 'https://remote.social/users/bad',
        ];

        $response = $this->postJson('/api/v2/federation/inbox', $invalidType);
        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'rejected',
        ]);
    }

    public function test_activitypub_replay_protection_prevents_duplicate_processing(): void
    {
        $activityId = 'https://mastodon.social/activities/unique-'.uniqid();
        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $activityId,
            'type' => 'Like',
            'actor' => 'https://mastodon.social/users/fan',
            'object' => 'https://jugajug.com/posts/100',
        ];

        $first = $this->postJson('/api/v2/federation/inbox', $payload);
        $first->assertStatus(202);

        // Replay of identical payload
        $replay = $this->postJson('/api/v2/federation/inbox', $payload);
        $replay->assertStatus(202);
        $replay->assertJson(['activity_id' => $activityId]);

        // Ensure only one record exists in DB
        $this->assertDatabaseCount('federated_activities', 1);
    }

    /**
     * 4. Multi-tier AI Cascading Fallback Chain Execution
     */
    public function test_ai_gateway_cascading_fallback_recovers_when_primary_fails(): void
    {
        $failingOpenAI = new class extends OpenAIProvider
        {
            public function __construct()
            {
                parent::__construct('mock-key');
            }

            public function complete(string $prompt, array $options = []): array
            {
                throw new \RuntimeException('OpenAI service outage: 503 Service Unavailable');
            }
        };

        $gemini = app(GeminiProvider::class);
        $claude = app(ClaudeProvider::class);
        $ollama = app(OllamaProvider::class);

        $gateway = new AIGateway($failingOpenAI, $gemini, $claude, $ollama);

        $result = $gateway->complete('Testing fallback cascading', ['provider' => 'openai', 'cache' => false]);

        $this->assertTrue($result['fallback']);
        $this->assertSame('gemini', $result['provider']);
        $this->assertNotEmpty($result['content']);
        $this->assertDatabaseHas('ai_usage_logs', [
            'provider' => 'gemini',
            'status' => 'fallback',
        ]);
    }
}
