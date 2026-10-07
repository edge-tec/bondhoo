<?php

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiV2GatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_v2_discovery_returns_modules(): void
    {
        $response = $this->getJson('/api/v2');

        $response->assertStatus(200);
        $response->assertJson([
            'version' => '2.0.0-enterprise',
            'platform' => 'Jugajug (যোগাযোগ)',
        ]);
        $response->assertHeader('X-API-Version', '2.0.0-enterprise');
        $this->assertNotEmpty($response->headers->get('X-Correlation-ID'));
    }

    public function test_idempotency_key_middleware_caches_and_replays(): void
    {
        $key = 'test-idem-'.uniqid();

        $first = $this->withHeaders(['Idempotency-Key' => $key])
            ->postJson('/api/v2/ai/chat', ['message' => 'First message']);
        $first->assertStatus(200);

        $second = $this->withHeaders(['Idempotency-Key' => $key])
            ->postJson('/api/v2/ai/chat', ['message' => 'Different message should be replayed']);
        $second->assertStatus(200);
        $second->assertHeader('X-Cache-Lookup', 'HIT (Idempotent)');
    }

    public function test_ai_caption_endpoint(): void
    {
        $response = $this->postJson('/api/v2/ai/caption', [
            'topic' => 'Sundarbans travel memories',
            'tone' => 'adventurous',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'data' => ['content', 'model']]);
    }

    public function test_ai_translation_endpoint(): void
    {
        $response = $this->postJson('/api/v2/ai/translate', [
            'text' => 'Good morning Dhaka!',
            'target' => 'bn',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'data' => ['content']]);
    }

    public function test_ai_moderation_endpoint(): void
    {
        $response = $this->postJson('/api/v2/ai/moderate', [
            'content' => 'সবাইকে শুভ সকাল ও আন্তরিক শুভেচ্ছা!',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.is_safe'));
    }

    public function test_ai_citizen_services_endpoint(): void
    {
        $response = $this->postJson('/api/v2/ai/citizen-services', [
            'inquiry' => 'অনলাইনে ড্রাইভিং লাইসেন্স নবায়ন করার নিয়ম কী?',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'data' => ['content']]);
    }

    public function test_ranked_feed_endpoint(): void
    {
        $user = User::factory()->create();
        Post::factory()->create(['user_id' => $user->id, 'audience' => 'public', 'content' => 'Ranked post 1']);

        $response = $this->getJson('/api/v2/feed/ranked');

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'count', 'data']);
    }

    public function test_hybrid_search_endpoint(): void
    {
        $response = $this->getJson('/api/v2/search/hybrid?q=Dhaka');

        $response->assertStatus(200);
        $response->assertJsonStructure(['query', 'count', 'results']);
    }

    public function test_marketplace_categories_endpoint(): void
    {
        MarketplaceCategory::create(['name' => 'Fashion', 'slug' => 'fashion']);

        $response = $this->getJson('/api/v2/marketplace/categories');

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'data']);
    }

    public function test_graphql_endpoint_queries_me_and_feed(): void
    {
        $user = User::factory()->create(['name' => 'Tanvir Ahmed']);

        $query = '{ me { id name email } }';
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/graphql', ['query' => $query]);

        $response->assertStatus(200);
        $this->assertSame('Tanvir Ahmed', $response->json('data.me.name'));
    }

    public function test_offline_sync_batch_endpoint(): void
    {
        $user = User::factory()->create();

        $actions = [
            [
                'client_id' => 'cli_1',
                'action' => 'post.react',
                'entity' => 'post',
                'payload' => ['post_id' => 10, 'reaction' => 'like'],
                'client_timestamp' => now()->toIso8601String(),
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/offline/sync', ['actions' => $actions]);

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('data.processed'));
    }
}
