<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_react_to_post(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Awesome day!',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/posts/{$post->id}/react", [
                'type' => 'love',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'reacted' => true,
                    'type' => 'love',
                    'total_reactions' => 1,
                    'breakdown' => [
                        'love' => 1,
                    ],
                ],
            ]);

        $this->assertEquals(1, $post->fresh()->likes_count);

        // Verify Redis cache key was updated
        $cacheService = app(CacheServiceInterface::class);
        $cachedVal = $cacheService->get("post:{$post->id}:reactions");
        $this->assertEquals(1, (int) $cachedVal);
    }

    public function test_user_can_toggle_off_reaction(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $post = Post::create(['user_id' => $user->id, 'content' => 'Post']);

        // React with 'haha'
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'haha']);
        $this->assertEquals(1, $post->fresh()->likes_count);

        // Toggle off by reacting with 'haha' again
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'haha']);

        $res->assertStatus(200)
            ->assertJson([
                'data' => [
                    'reacted' => false,
                    'total_reactions' => 0,
                ],
            ]);

        $this->assertEquals(0, $post->fresh()->likes_count);
    }

    public function test_can_retrieve_post_reaction_summary(): void
    {
        $author = User::factory()->create();
        $post = Post::create(['user_id' => $author->id, 'content' => 'Summary test']);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $token1 = $user1->createToken('t1')->plainTextToken;
        $token2 = $user2->createToken('t2')->plainTextToken;

        $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'like']);
        $this->actingAs($user2, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'wow']);

        $response = $this->getJson("/api/v1/posts/{$post->id}/reactions");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 2,
                    'breakdown' => [
                        'like' => 1,
                        'wow' => 1,
                    ],
                ],
            ]);
    }
}
