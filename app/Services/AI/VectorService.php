<?php

namespace App\Services\AI;

use App\Models\VectorEmbedding;

class VectorService
{
    /**
     * Compute cosine similarity between two numeric vectors.
     * Range: -1.0 (opposite) to +1.0 (identical).
     *
     * @param  float[]  $vecA
     * @param  float[]  $vecB
     */
    public function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $len = min(count($vecA), count($vecB));
        if ($len === 0) {
            return 0.0;
        }

        for ($i = 0; $i < $len; $i++) {
            $dotProduct += ($vecA[$i] * $vecB[$i]);
            $normA += ($vecA[$i] * $vecA[$i]);
            $normB += ($vecB[$i] * $vecB[$i]);
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return round($dotProduct / (sqrt($normA) * sqrt($normB)), 6);
    }

    /**
     * Search nearest entities by vector similarity.
     *
     * @param  float[]  $queryVector
     * @return array<int, array{entity_type: string, entity_id: int, similarity: float, snippet: ?string}>
     */
    public function findNearest(array $queryVector, ?string $entityType = null, int $limit = 10, float $minThreshold = 0.5): array
    {
        $query = VectorEmbedding::query();
        if ($entityType) {
            $query->where('entity_type', $entityType);
        }

        $records = $query->limit(200)->get();
        $results = [];

        foreach ($records as $record) {
            $similarity = $this->cosineSimilarity($queryVector, $record->vector ?? []);
            if ($similarity >= $minThreshold) {
                $results[] = [
                    'entity_type' => $record->entity_type,
                    'entity_id' => (int) $record->entity_id,
                    'similarity' => $similarity,
                    'snippet' => $record->content_snippet,
                ];
            }
        }

        usort($results, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($results, 0, $limit);
    }
}
