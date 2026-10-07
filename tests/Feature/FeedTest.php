<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feed_returns_public_posts(): void
    {
        $author = User::factory()->create();

        // 1 public post, 1 only_me post
        Post::create(['user_id' => $author->id, 'content' => 'Public Post', 'audience' => 'public']);
        Post::create(['user_id' => $author->id, 'content' => 'Private Post', 'audience' => 'only_me']);

        $response = $this->getJson('/api/v1/feed');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $posts = $response->json('data');
        $this->assertCount(1, $posts);
        $this->assertEquals('Public Post', $posts[0]['content']);
    }

    public function test_feed_supports_cursor_pagination(): void
    {
        $author = User::factory()->create();

        for ($i = 1; $i <= 5; $i++) {
            Post::create(['user_id' => $author->id, 'content' => "Post {$i}", 'audience' => 'public']);
        }

        $response = $this->getJson('/api/v1/feed?per_page=3');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
        $this->assertTrue($response->json('meta.has_more'));
        $this->assertNotNull($response->json('meta.next_cursor'));
    }

    public function test_pinned_posts_are_ranked_first(): void
    {
        $author = User::factory()->create();

        $post1 = Post::create(['user_id' => $author->id, 'content' => 'Regular Post', 'is_pinned' => false]);
        $post2 = Post::create(['user_id' => $author->id, 'content' => 'Pinned Post', 'is_pinned' => true]);

        $response = $this->getJson('/api/v1/feed');

        $posts = $response->json('data');
        $this->assertEquals('Pinned Post', $posts[0]['content']);
    }
}
