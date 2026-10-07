<?php

namespace App\Services\Story;

use App\Models\Story;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StoryAIEngine
{
    /**
     * Rank stories for viewer feed based on close friends affinity and freshness.
     *
     * @param  Collection<int, Story>  $stories
     * @return Collection<int, Story>
     */
    public function rankStories(Collection $stories, ?User $viewer = null): Collection
    {
        return $stories->map(function (Story $story) use ($viewer) {
            $hoursLeft = max(0.1, Carbon::now()->diffInHours($story->expires_at, false));
            $freshnessScore = min(24.0, $hoursLeft) / 24.0; // 1.0 (brand new) down to 0.0

            $affinityScore = 1.0;
            if ($viewer) {
                if ($story->user_id === $viewer->id) {
                    $affinityScore = 3.0;
                } elseif (Cache::has("affinity:{$viewer->id}:{$story->user_id}")) {
                    $affinityScore = (float) Cache::get("affinity:{$viewer->id}:{$story->user_id}");
                }
            }

            $views = (int) ($story->views_count ?? 0);
            $popularityBonus = min(2.0, log10($views + 1) * 0.5);

            $story->ai_rank = round(($affinityScore * 2.0) + ($freshnessScore * 1.5) + $popularityBonus, 4);

            return $story;
        })
            ->sortByDesc('ai_rank')
            ->values();
    }

    /**
     * Attach interactive sticker to story (poll, quiz, countdown, location, music).
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    public function attachInteractiveElement(Story $story, string $type, array $data): array
    {
        $cacheKey = "story:{$story->id}:interactive";
        $existing = Cache::get($cacheKey, []);
        $existing[] = [
            'type' => $type,
            'data' => $data,
            'created_at' => now()->toIso8601String(),
        ];

        Cache::put($cacheKey, $existing, 86400);

        return $existing;
    }

    /**
     * Get interactive elements for a story.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getInteractiveElements(Story $story): array
    {
        return Cache::get("story:{$story->id}:interactive", []);
    }

    /**
     * Archive expired stories (> 24 hours old).
     */
    public function archiveExpiredStories(): int
    {
        return Story::where('expires_at', '<', now())
            ->whereNull('archived_at')
            ->update([
                'archived_at' => now(),
            ]);
    }
}
