<?php

namespace App\Services\AI;

use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIGateway
{
    /** @var array<string, AIProviderInterface> */
    protected array $providers = [];

    protected string $defaultProvider;

    public function __construct(
        OpenAIProvider $openai,
        GeminiProvider $gemini,
        ClaudeProvider $claude,
        OllamaProvider $ollama
    ) {
        $this->providers = [
            'openai' => $openai,
            'gemini' => $gemini,
            'claude' => $claude,
            'ollama' => $ollama,
        ];

        $this->defaultProvider = config('ai.default_provider', env('AI_DEFAULT_PROVIDER', 'openai'));
    }

    public function getProvider(string $name): ?AIProviderInterface
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * Complete prompt with model routing, cache, and usage auditing.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function complete(string $prompt, array $options = [], ?int $userId = null): array
    {
        $providerName = $options['provider'] ?? $this->defaultProvider;
        $operation = $options['operation'] ?? 'chat';
        $useCache = $options['cache'] ?? true;
        $cacheTtl = $options['cache_ttl'] ?? 3600;

        $cacheKey = 'ai:complete:'.md5($providerName.':'.$prompt.':'.json_encode($options));
        if ($useCache && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $cached['cached'] = true;

            return $cached;
        }

        $primary = $providerName;
        // Build cascading fallback chain: primary -> remaining cloud providers -> local Ollama
        $fallbackChain = [$primary];
        foreach (['openai', 'gemini', 'claude', 'ollama'] as $candidate) {
            if ($candidate !== $primary && isset($this->providers[$candidate])) {
                $fallbackChain[] = $candidate;
            }
        }

        $lastException = null;

        foreach ($fallbackChain as $currentProvider) {
            $provider = $this->providers[$currentProvider];

            try {
                $result = $provider->complete($prompt, $options);
                $result['provider'] = $provider->getProviderName();
                $result['cost_usd'] = $this->calculateCost($result['provider'], $result['model'], $result['prompt_tokens'], $result['completion_tokens']);
                $result['cached'] = false;
                $result['fallback'] = ($currentProvider !== $primary);

                $status = ($currentProvider === $primary) ? 'success' : 'fallback';
                $this->logUsage($userId, $result['provider'], $result['model'], $operation, $result['prompt_tokens'], $result['completion_tokens'], $result['cost_usd'], $result['latency_ms'], $status);

                if ($useCache) {
                    Cache::put($cacheKey, $result, $cacheTtl);
                }

                return $result;
            } catch (\Throwable $e) {
                Log::warning("AI Provider [{$currentProvider}] failed: ".$e->getMessage().'. Attempting next fallback.', [
                    'primary' => $primary,
                    'failed_provider' => $currentProvider,
                ]);
                $lastException = $e;
            }
        }

        $this->logUsage($userId, $primary, 'unknown', $operation, 0, 0, 0.0, 0, 'error', $lastException?->getMessage());

        throw $lastException ?? new \RuntimeException('All AI providers in fallback chain failed.');
    }

    /**
     * Generate text embedding with provider abstraction.
     *
     * @return array{embedding: float[], dimension: int, model: string, provider: string}
     */
    public function embed(string $text, ?string $providerName = null): array
    {
        $providerName = $providerName ?? $this->defaultProvider;
        $provider = $this->providers[$providerName] ?? $this->providers['openai'];

        $result = $provider->embed($text);
        $result['provider'] = $provider->getProviderName();

        return $result;
    }

    /**
     * Calculate cost based on tokens.
     */
    public function calculateCost(string $provider, string $model, int $promptTokens, int $completionTokens): float
    {
        // Cost per 1M tokens in USD
        $rates = [
            'openai' => ['prompt' => 2.50 / 1000000, 'completion' => 10.00 / 1000000],
            'gemini' => ['prompt' => 0.075 / 1000000, 'completion' => 0.30 / 1000000],
            'claude' => ['prompt' => 3.00 / 1000000, 'completion' => 15.00 / 1000000],
            'ollama' => ['prompt' => 0.0, 'completion' => 0.0],
        ];

        $rate = $rates[$provider] ?? ['prompt' => 1.0 / 1000000, 'completion' => 2.0 / 1000000];

        $cost = ($promptTokens * $rate['prompt']) + ($completionTokens * $rate['completion']);

        return round($cost, 6);
    }

    /**
     * Record AI usage metrics.
     */
    protected function logUsage(?int $userId, string $provider, string $model, string $operation, int $pTokens, int $cTokens, float $cost, int $latency, string $status, ?string $errorMessage = null): void
    {
        try {
            AiUsageLog::create([
                'user_id' => $userId,
                'provider' => $provider,
                'model' => $model,
                'operation' => $operation,
                'prompt_tokens' => $pTokens,
                'completion_tokens' => $cTokens,
                'total_tokens' => $pTokens + $cTokens,
                'cost_usd' => $cost,
                'latency_ms' => $latency,
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Could not write AI usage log: '.$e->getMessage());
        }
    }
}
