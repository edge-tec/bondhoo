<?php

namespace App\Services\Feed;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AIFeedRankingEngine
{
    /**
     * Compute individual score for a post given viewer context.
     *
     * @param  array<string, mixed>  $signals
     */
    public function calculateScore(Post $post, ?User $viewer = null, array $signals = []): float
    {
        // Weights
        $wLike = 1.0;
        $wComment = 3.5;
        $wShare = 5.0;
        $wWatch = 0.05; // per second
        $wSave = 4.0;

        // Raw engagement counts
        $likes = (int) ($signals['likes'] ?? $post->reactions()->count());
        $comments = (int) ($signals['comments'] ?? $post->comments()->count());
        $shares = (int) ($signals['shares'] ?? 0);
        $watchSecs = (int) ($signals['watch_duration'] ?? 0);
        $saves = (int) ($signals['saves'] ?? 0);

        $engagementScore = ($likes * $wLike) +
            ($comments * $wComment) +
            ($shares * $wShare) +
            ($watchSecs * $wWatch) +
            ($saves * $wSave);

        // Recency decay: half-life of 12 hours (lambda = ln(2)/12 = 0.05776 per hour)
        $hoursOld = max(0.1, Carbon::now()->diffInHours($post->created_at, true));
        $lambda = 0.0578;
        $recencyMultiplier = exp(-$lambda * $hoursOld);

        // Friendship / Affinity multiplier
        $affinityMultiplier = 1.0;
        if ($viewer) {
            if ($viewer->id === $post->user_id) {
                $affinityMultiplier = 1.2;
            } elseif ($signals['is_friend'] ?? false) {
                $affinityMultiplier = 2.5; // Heavy boost for direct friends
            } elseif ($signals['same_group'] ?? false) {
                $affinityMultiplier = 1.8;
            }
        }

        // Viral & Trending multiplier
        $velocity = ($likes + $comments * 2) / max(1.0, $hoursOld);
        $viralMultiplier = $velocity > 10.0 ? 1.75 : ($velocity > 3.0 ? 1.3 : 1.0);

        // Negative Penalties (Reports, Hides)
        $reports = (int) ($signals['reports'] ?? 0);
        $hides = (int) ($signals['hides'] ?? 0);
        $penalty = ($reports * 25.0) + ($hides * 10.0);

        $finalScore = (($engagementScore + 1.0) * $recencyMultiplier * $affinityMultiplier * $viralMultiplier) - $penalty;

        return max(0.01, round($finalScore, 4));
    }

    /**
     * Rank a collection of posts for a viewer using AI signals.
     *
     * @param  Collection<int, Post>  $posts
     * @return Collection<int, Post>
     */
    public function rankFeed(Collection $posts, ?User $viewer = null, int $limit = 20): Collection
    {
        return $posts->map(function (Post $post) use ($viewer) {
            $post->ai_rank_score = $this->calculateScore($post, $viewer);

            return $post;
        })
            ->sortByDesc('ai_rank_score')
            ->values()
            ->take($limit);
    }

    /**
     * Get personalized cached feed for user.
     *
     * @return Collection<int, Post>
     */
    public function getCachedRankedFeed(?User $user = null, int $limit = 20): Collection
    {
        $cacheKey = 'feed:ai:ranked:'.($user ? $user->id : 'guest');

        return Cache::remember($cacheKey, 60, function () use ($user, $limit) {
            $recentPosts = Post::with(['user', 'media', 'reactions', 'comments'])
                ->where('audience', 'public')
                ->latest('id')
                ->limit(100)
                ->get();

            return $this->rankFeed($recentPosts, $user, $limit);
        });
    }
}
