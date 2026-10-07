<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * অ্যাডভান্সড ফিড অ্যালগরিদম টেস্ট:
 * এনগেজমেন্ট স্কোরিং, পিন্ড পোস্ট এবং ক্রোনোলজিক্যাল ভার্সাস স্মার্ট ফিড টেস্ট।
 */
class AdvancedFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_smart_feed_prioritizes_high_engagement_posts(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();

        // 1. Older post with high engagement (50 likes, 10 comments)
        $viralPost = Post::create([
            'user_id' => $author->id,
            'content' => 'Viral post with lots of engagement!',
            'audience' => 'public',
            'likes_count' => 50,
            'comments_count' => 10,
            'shares_count' => 5,
        ]);

        // 2. Newer post with 0 engagement
        $quietPost = Post::create([
            'user_id' => $author->id,
            'content' => 'Just a quiet thought with 0 likes.',
            'audience' => 'public',
            'likes_count' => 0,
            'comments_count' => 0,
            'shares_count' => 0,
        ]);

        // Request with smart algorithm
        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/feed?algorithm=smart');

        $response->assertStatus(200);

        $items = $response->json('data');
        $this->assertCount(2, $items);
        // Viral post should rank higher than quiet post
        $this->assertEquals($viralPost->id, $items[0]['id']);
        $this->assertEquals($quietPost->id, $items[1]['id']);
    }

    public function test_chronological_feed_strictly_sorts_by_recency(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();

        // Older post with high engagement
        $olderPost = Post::create([
            'user_id' => $author->id,
            'content' => 'Older post with 100 likes',
            'audience' => 'public',
            'likes_count' => 100,
        ]);

        // Newer post with 0 likes
        $newerPost = Post::create([
            'user_id' => $author->id,
            'content' => 'Brand new post',
            'audience' => 'public',
            'likes_count' => 0,
        ]);

        // Request with chronological algorithm
        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/feed?algorithm=chronological');

        $response->assertStatus(200);

        $items = $response->json('data');
        $this->assertCount(2, $items);
        // Newer post should come first in chronological mode
        $this->assertEquals($newerPost->id, $items[0]['id']);
        $this->assertEquals($olderPost->id, $items[1]['id']);
    }

    public function test_pinned_post_always_stays_on_top_in_both_algorithms(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();

        $regularPost = Post::create([
            'user_id' => $author->id,
            'content' => 'Regular post',
            'audience' => 'public',
            'likes_count' => 200,
            'is_pinned' => false,
        ]);

        $pinnedPost = Post::create([
            'user_id' => $author->id,
            'content' => 'Community Guidelines (Pinned)',
            'audience' => 'public',
            'likes_count' => 1,
            'is_pinned' => true,
        ]);

        // Smart feed
        $smartResponse = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/feed?algorithm=smart');
        $this->assertEquals($pinnedPost->id, $smartResponse->json('data.0.id'));

        // Chronological feed
        $chronoResponse = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/feed?algorithm=chronological');
        $this->assertEquals($pinnedPost->id, $chronoResponse->json('data.0.id'));
    }
}
