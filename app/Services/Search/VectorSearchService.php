<?php

namespace App\Services\Search;

use App\Models\MarketplaceProduct;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\EmbeddingService;
use App\Services\AI\VectorService;

class VectorSearchService
{
    protected EmbeddingService $embeddingService;

    protected VectorService $vectorService;

    public function __construct(EmbeddingService $embeddingService, VectorService $vectorService)
    {
        $this->embeddingService = $embeddingService;
        $this->vectorService = $vectorService;
    }

    /**
     * Index an entity into the vector store.
     */
    public function indexEntity(string $entityType, int $entityId, string $content): void
    {
        $this->embeddingService->storeEntityEmbedding($entityType, $entityId, $content);
    }

    /**
     * Semantic vector search across entities.
     *
     * @return array<int, array<string, mixed>>
     */
    public function semanticSearch(string $queryText, ?string $entityType = null, int $limit = 10): array
    {
        $queryEmbedding = $this->embeddingService->generateEmbedding($queryText);
        $matches = $this->vectorService->findNearest($queryEmbedding['embedding'], $entityType, $limit);

        $results = [];
        foreach ($matches as $match) {
            $entity = $this->resolveEntity($match['entity_type'], $match['entity_id']);
            if ($entity) {
                $results[] = [
                    'entity_type' => $match['entity_type'],
                    'entity_id' => $match['entity_id'],
                    'similarity' => $match['similarity'],
                    'snippet' => $match['snippet'],
                    'data' => $entity,
                ];
            }
        }

        return $results;
    }

    /**
     * Hybrid search combining text keyword match and vector semantic similarity.
     *
     * @return array<int, array<string, mixed>>
     */
    public function hybridSearch(string $queryText, int $limit = 10): array
    {
        $vectorResults = $this->semanticSearch($queryText, null, $limit);

        // Keyword lookup on posts & products
        $posts = Post::where('content', 'like', "%{$queryText}%")->limit($limit)->get();
        $products = MarketplaceProduct::where('title', 'like', "%{$queryText}%")->limit($limit)->get();

        $combined = [];
        foreach ($vectorResults as $res) {
            $key = $res['entity_type'].'_'.$res['entity_id'];
            $res['score'] = $res['similarity'] * 1.5;
            $combined[$key] = $res;
        }

        foreach ($posts as $post) {
            $key = 'post_'.$post->id;
            if (isset($combined[$key])) {
                $combined[$key]['score'] += 1.0;
            } else {
                $combined[$key] = [
                    'entity_type' => 'post',
                    'entity_id' => $post->id,
                    'similarity' => 0.6,
                    'snippet' => mb_substr($post->content, 0, 150),
                    'score' => 1.0,
                    'data' => $post,
                ];
            }
        }

        foreach ($products as $prod) {
            $key = 'product_'.$prod->id;
            if (isset($combined[$key])) {
                $combined[$key]['score'] += 1.0;
            } else {
                $combined[$key] = [
                    'entity_type' => 'product',
                    'entity_id' => $prod->id,
                    'similarity' => 0.6,
                    'snippet' => $prod->title,
                    'score' => 1.0,
                    'data' => $prod,
                ];
            }
        }

        usort($combined, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_values(array_slice($combined, 0, $limit));
    }

    protected function resolveEntity(string $type, int $id): mixed
    {
        return match ($type) {
            'post' => Post::with('user')->find($id),
            'user' => User::find($id),
            'product' => MarketplaceProduct::find($id),
            default => null,
        };
    }
}
