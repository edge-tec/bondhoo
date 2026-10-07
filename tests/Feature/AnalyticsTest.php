<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * অ্যানালিটিক্স ফিচার টেস্ট:
 * ইমপ্রেশন ট্র্যাকিং, রিচ ক্যালকুলেশন এবং পোস্ট পারফরম্যান্স রিপোর্ট টেস্ট।
 */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_post_impression_increments_impressions_and_unique_reach(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Analytics post test',
            'audience' => 'public',
        ]);

        // First impression from viewer
        $response1 = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/impression");

        $response1->assertStatus(200);

        $this->assertDatabaseHas('post_analytics', [
            'post_id' => $post->id,
            'impressions_count' => 1,
            'unique_reach' => 1,
        ]);

        // Second impression from same viewer (impressions increment, reach stays 1)
        $response2 = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/impression");

        $response2->assertStatus(200);

        $this->assertDatabaseHas('post_analytics', [
            'post_id' => $post->id,
            'impressions_count' => 2,
            'unique_reach' => 1,
        ]);
    }

    public function test_author_can_view_post_analytics_with_engagement_rate(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'High engagement post',
            'audience' => 'public',
            'likes_count' => 15,
            'comments_count' => 5,
            'shares_count' => 0,
        ]);

        // Record 100 impressions
        $analytics = $post->analytics()->create([
            'impressions_count' => 100,
            'unique_reach' => 80,
            'clicks_count' => 25,
        ]);

        $response = $this->actingAs($author, 'sanctum')
            ->getJson("/api/v1/posts/{$post->id}/analytics");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.impressions', 100)
            ->assertJsonPath('data.reach', 80)
            ->assertJsonPath('data.total_engagements', 20); // 15 likes + 5 comments

        $this->assertEquals(20.0, (float) $response->json('data.engagement_rate'));
    }

    public function test_non_author_cannot_view_post_analytics(): void
    {
        $author = User::factory()->create();
        $stranger = User::factory()->create();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Author private analytics',
            'audience' => 'public',
        ]);

        $response = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/posts/{$post->id}/analytics");

        $response->assertStatus(403);
    }
}
