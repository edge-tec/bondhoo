<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    /**
     * Generate text completion / chat response.
     *
     * @param  array<string, mixed>  $options
     * @return array{content: string, model: string, prompt_tokens: int, completion_tokens: int, total_tokens: int, latency_ms: int}
     */
    public function complete(string $prompt, array $options = []): array;

    /**
     * Generate a vector embedding for the given text.
     *
     * @return array{embedding: float[], dimension: int, model: string}
     */
    public function embed(string $text): array;

    /**
     * Get the unique provider identifier (openai, gemini, claude, ollama).
     */
    public function getProviderName(): string;

    /**
     * Check if provider credentials / service is reachable.
     */
    public function isAvailable(): bool;
}
