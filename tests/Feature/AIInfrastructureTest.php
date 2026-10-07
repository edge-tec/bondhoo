<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AI\AIGateway;
use App\Services\AI\ClaudeProvider;
use App\Services\AI\GeminiProvider;
use App\Services\AI\OllamaProvider;
use App\Services\AI\OpenAIProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_openai_provider_generates_completion_and_embedding(): void
    {
        $provider = new OpenAIProvider('demo-key');
        $this->assertTrue($provider->isAvailable());
        $this->assertSame('openai', $provider->getProviderName());

        $res = $provider->complete('Testing OpenAI prompt');
        $this->assertNotEmpty($res['content']);
        $this->assertGreaterThan(0, $res['total_tokens']);

        $embed = $provider->embed('Test text');
        $this->assertSame(1536, $embed['dimension']);
        $this->assertCount(1536, $embed['embedding']);
    }

    public function test_gemini_provider_generates_completion(): void
    {
        $provider = new GeminiProvider('demo-key');
        $this->assertTrue($provider->isAvailable());
        $this->assertSame('gemini', $provider->getProviderName());

        $res = $provider->complete('Testing Gemini prompt');
        $this->assertNotEmpty($res['content']);
        $this->assertSame('gemini-2.5-flash', $res['model']);
    }

    public function test_claude_provider_generates_completion(): void
    {
        $provider = new ClaudeProvider('demo-key');
        $this->assertTrue($provider->isAvailable());
        $this->assertSame('claude', $provider->getProviderName());

        $res = $provider->complete('Testing Claude prompt');
        $this->assertNotEmpty($res['content']);
    }

    public function test_ollama_provider_provides_local_sovereign_completion(): void
    {
        $provider = new OllamaProvider('http://127.0.0.1:11434');
        $this->assertTrue($provider->isAvailable());
        $this->assertSame('ollama', $provider->getProviderName());

        $res = $provider->complete('Testing Ollama local prompt');
        $this->assertNotEmpty($res['content']);
    }

    public function test_ai_gateway_routes_and_logs_audit_usage(): void
    {
        $user = User::factory()->create();
        $gateway = app(AIGateway::class);

        $res = $gateway->complete('Hello AI Gateway', ['provider' => 'gemini', 'operation' => 'chat'], $user->id);

        $this->assertNotEmpty($res['content']);
        $this->assertSame('gemini', $res['provider']);

        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $user->id,
            'provider' => 'gemini',
            'operation' => 'chat',
        ]);
    }

    public function test_ai_gateway_calculates_correct_costs(): void
    {
        $gateway = app(AIGateway::class);
        $cost = $gateway->calculateCost('openai', 'gpt-4o', 1000, 2000);

        $this->assertGreaterThan(0.0, $cost);
        $this->assertIsFloat($cost);
    }

    public function test_ai_gateway_handles_caching_properly(): void
    {
        $gateway = app(AIGateway::class);

        $first = $gateway->complete('Cached prompt testing', ['cache' => true]);
        $second = $gateway->complete('Cached prompt testing', ['cache' => true]);

        $this->assertFalse($first['cached']);
        $this->assertTrue($second['cached']);
    }
}
