<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\AI\EmbeddingService;
use App\Services\AI\VectorService;
use App\Services\Search\VectorSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VectorSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_vector_service_calculates_exact_cosine_similarity(): void
    {
        $vectorService = new VectorService;

        // Identical vectors -> similarity = 1.0
        $vecA = [1.0, 2.0, 3.0];
        $vecB = [1.0, 2.0, 3.0];
        $this->assertEqualsWithDelta(1.0, $vectorService->cosineSimilarity($vecA, $vecB), 0.0001);

        // Orthogonal vectors -> similarity = 0.0
        $vecOrthA = [1.0, 0.0];
        $vecOrthB = [0.0, 1.0];
        $this->assertEqualsWithDelta(0.0, $vectorService->cosineSimilarity($vecOrthA, $vecOrthB), 0.0001);
    }

    public function test_embedding_service_stores_vector_in_database(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id, 'content' => 'Laravel AI and PHP 8.5 architecture']);

        $embeddingService = app(EmbeddingService::class);
        $record = $embeddingService->storeEntityEmbedding('post', $post->id, $post->content);

        $this->assertDatabaseHas('vector_embeddings', [
            'entity_type' => 'post',
            'entity_id' => $post->id,
            'dimension' => 1536,
        ]);
        $this->assertCount(1536, $record->vector);
    }

    public function test_hybrid_search_combines_vector_and_keyword_results(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'content' => 'ঢাকা মেট্রোরেল এবং ট্রাফিক সমস্যার ডিজিটাল সমাধান',
            'audience' => 'public',
        ]);

        $searchService = app(VectorSearchService::class);
        $searchService->indexEntity('post', $post->id, $post->content);

        $results = $searchService->hybridSearch('মেট্রোরেল', 5);

        $this->assertNotEmpty($results);
        $this->assertSame('post', $results[0]['entity_type']);
        $this->assertSame($post->id, $results[0]['entity_id']);
    }
}
