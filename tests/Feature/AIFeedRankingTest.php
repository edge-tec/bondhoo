<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\Feed\AIFeedRankingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIFeedRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_engine_computes_positive_score_with_likes_and_comments(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id, 'audience' => 'public']);

        $engine = new AIFeedRankingEngine;
        $score = $engine->calculateScore($post, $viewer, [
            'likes' => 15,
            'comments' => 5,
            'shares' => 2,
            'watch_duration' => 60,
        ]);

        $this->assertGreaterThan(1.0, $score);
    }

    public function test_ranking_engine_applies_friendship_affinity_boost(): void
    {
        $author = User::factory()->create();
        $friendViewer = User::factory()->create();
        $strangerViewer = User::factory()->create();

        $post = Post::factory()->create(['user_id' => $author->id, 'audience' => 'public']);
        $engine = new AIFeedRankingEngine;

        $friendScore = $engine->calculateScore($post, $friendViewer, ['is_friend' => true, 'likes' => 10]);
        $strangerScore = $engine->calculateScore($post, $strangerViewer, ['is_friend' => false, 'likes' => 10]);

        $this->assertGreaterThan($strangerScore, $friendScore);
    }

    public function test_ranking_engine_penalizes_reported_posts(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id, 'audience' => 'public']);

        $engine = new AIFeedRankingEngine;
        $normalScore = $engine->calculateScore($post, $viewer, ['likes' => 5, 'reports' => 0]);
        $reportedScore = $engine->calculateScore($post, $viewer, ['likes' => 5, 'reports' => 3]);

        $this->assertGreaterThan($reportedScore, $normalScore);
    }

    public function test_rank_feed_sorts_collection_by_descending_score(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        $postLow = Post::factory()->create(['user_id' => $author->id, 'audience' => 'public', 'created_at' => now()->subDays(5)]);
        $postHigh = Post::factory()->create(['user_id' => $author->id, 'audience' => 'public', 'created_at' => now()]);

        $engine = new AIFeedRankingEngine;
        $ranked = $engine->rankFeed(collect([$postLow, $postHigh]), $viewer);

        $this->assertSame($postHigh->id, $ranked->first()->id);
    }
}
