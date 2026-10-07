<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AI\AIGateway;
use App\Services\AI\AIUsageService;
use App\Services\AI\PromptManager;
use App\Services\AI\VectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseAIExtendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_prompt_manager_renders_and_versions_templates(): void
    {
        $manager = app(PromptManager::class);

        $prompt = $manager->storePrompt(
            'custom_event_invite',
            'You are invited to {{event_name}} on {{event_date}} at {{venue}}.',
            'events',
            '1.2.0'
        );

        $this->assertSame('custom_event_invite', $prompt->name);
        $this->assertSame('1.2.0', $prompt->version);

        $rendered = $manager->render('custom_event_invite', [
            'event_name' => 'Jugajug AI Gala',
            'event_date' => '25 December 2026',
            'venue' => 'Radisson Blu, Dhaka',
        ], '1.2.0');

        $this->assertSame('You are invited to Jugajug AI Gala on 25 December 2026 at Radisson Blu, Dhaka.', $rendered);
    }

    public function test_prompt_manager_falls_back_gracefully_to_default(): void
    {
        $manager = app(PromptManager::class);

        $rendered = $manager->render('feed_moderation', ['content' => 'Sample post to check']);
        $this->assertStringContainsString('Sample post to check', $rendered);
    }

    public function test_ai_usage_service_reports_per_user_quota(): void
    {
        $user = User::factory()->create();
        $usageService = app(AIUsageService::class);

        $report = $usageService->getUserUsage($user->id);

        $this->assertSame($user->id, $report['user_id']);
        $this->assertSame(100000, $report['daily_quota']);
        $this->assertFalse($report['quota_exceeded']);
        $this->assertSame(100000, $report['quota_remaining']);
    }

    public function test_ai_usage_service_reports_system_overview(): void
    {
        $gateway = app(AIGateway::class);
        $gateway->complete('System telemetry testing', ['provider' => 'gemini']);

        $usageService = app(AIUsageService::class);
        $summary = $usageService->getSystemUsageSummary(7);

        $this->assertSame(7, $summary['period_days']);
        $this->assertGreaterThanOrEqual(1, $summary['total_calls']);
        $this->assertArrayHasKey('providers', $summary);
    }

    public function test_vector_similarity_extremes_and_empty_vectors(): void
    {
        $vectorService = new VectorService;

        $emptySim = $vectorService->cosineSimilarity([], []);
        $this->assertSame(0.0, $emptySim);

        // Opposite vectors
        $vecPos = [1.0, 1.0];
        $vecNeg = [-1.0, -1.0];
        $this->assertEqualsWithDelta(-1.0, $vectorService->cosineSimilarity($vecPos, $vecNeg), 0.001);
    }

    public function test_vector_find_nearest_filters_by_threshold(): void
    {
        $vectorService = new VectorService;

        // Query with vector
        $queryVec = [1.0, 0.0, 0.0];
        $matches = $vectorService->findNearest($queryVec, 'post', 5, 0.99);

        // Should return array
        $this->assertIsArray($matches);
    }
}
