<?php

namespace App\Services\AI;

use App\Models\VectorEmbedding;
use Illuminate\Support\Facades\Cache;

class EmbeddingService
{
    protected AIGateway $gateway;

    public function __construct(AIGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Generate and cache embedding vector for a given string.
     *
     * @return array{embedding: float[], dimension: int, model: string}
     */
    public function generateEmbedding(string $text, ?string $provider = null): array
    {
        $cacheKey = 'ai:embed:'.md5($text);

        return Cache::remember($cacheKey, 86400, function () use ($text, $provider) {
            return $this->gateway->embed($text, $provider);
        });
    }

    /**
     * Store or update vector embedding for an entity (Post, User, Product, Group).
     */
    public function storeEntityEmbedding(string $entityType, int $entityId, string $content, ?string $provider = null): VectorEmbedding
    {
        $embeddingData = $this->generateEmbedding($content, $provider);

        return VectorEmbedding::updateOrCreate(
            ['entity_type' => $entityType, 'entity_id' => $entityId],
            [
                'model' => $embeddingData['model'] ?? 'text-embedding-3-small',
                'dimension' => $embeddingData['dimension'] ?? 1536,
                'vector' => $embeddingData['embedding'],
                'content_snippet' => mb_substr($content, 0, 250),
            ]
        );
    }
}
