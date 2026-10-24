<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use App\Models\User;

class MemoriesService
{
    /**
     * Get user memories (posts from this day in past years).
     *
     * @return array{today_memories: array<int, array<string, mixed>>, past_week_memories: array<int, array<string, mixed>>, total_count: int}
     */
    public function getMemoriesForUser(User $user): array
    {
        $todayMonth = (int) now()->format('m');
        $todayDay = (int) now()->format('d');
        $currentYear = (int) now()->format('Y');

        // 1. Posts on exact same month & day in past years
        $exactPosts = Post::where('user_id', $user->id)
            ->whereYear('created_at', '<', $currentYear)
            ->whereMonth('created_at', $todayMonth)
            ->whereDay('created_at', $todayDay)
            ->with(['user.profile', 'media'])
            ->latest('created_at')
            ->get();

        $todayMemories = $exactPosts->map(function (Post $post) use ($currentYear): array {
            $postYear = (int) $post->created_at->format('Y');
            $yearsAgo = max(1, $currentYear - $postYear);

            return [
                'id' => $post->id,
                'content' => $post->content,
                'created_at' => $post->created_at->toIso8601String(),
                'created_at_formatted' => $post->created_at->locale('bn')->translatedFormat('d F, Y • h:i A'),
                'years_ago' => $yearsAgo,
                'years_ago_label' => "{$yearsAgo} বছর আগের আজকের দিনে",
                'likes_count' => $post->likes_count ?? $post->reactions_count ?? 0,
                'comments_count' => $post->comments_count ?? 0,
                'author' => [
                    'id' => $post->user?->id,
                    'name' => $post->user?->name,
                    'username' => $post->user?->username,
                    'avatar' => $post->user?->avatar_url ?? $post->user?->profile?->avatar_url,
                ],
                'media' => $post->media?->map(fn ($m) => [
                    'id' => $m->id,
                    'url' => $m->file_url ?? $m->url,
                    'type' => $m->file_type ?? $m->type,
                ])->values()->all() ?? [],
            ];
        })->values()->all();

        // 2. Fallback: Recent memories around this week in past years if exact today has fewer than 2
        $pastWeekMemories = [];
        if (count($todayMemories) < 2) {
            $weekPosts = Post::where('user_id', $user->id)
                ->whereYear('created_at', '<', $currentYear)
                ->whereNotIn('id', $exactPosts->pluck('id'))
                ->with(['user.profile', 'media'])
                ->latest('created_at')
                ->take(6)
                ->get();

            $pastWeekMemories = $weekPosts->map(function (Post $post) use ($currentYear): array {
                $postYear = (int) $post->created_at->format('Y');
                $yearsAgo = max(1, $currentYear - $postYear);

                return [
                    'id' => $post->id,
                    'content' => $post->content,
                    'created_at' => $post->created_at->toIso8601String(),
                    'created_at_formatted' => $post->created_at->locale('bn')->translatedFormat('d F, Y • h:i A'),
                    'years_ago' => $yearsAgo,
                    'years_ago_label' => "{$yearsAgo} বছর আগের স্মৃতি",
                    'likes_count' => $post->likes_count ?? $post->reactions_count ?? 0,
                    'comments_count' => $post->comments_count ?? 0,
                    'author' => [
                        'id' => $post->user?->id,
                        'name' => $post->user?->name,
                        'username' => $post->user?->username,
                        'avatar' => $post->user?->avatar_url ?? $post->user?->profile?->avatar_url,
                    ],
                    'media' => $post->media?->map(fn ($m) => [
                        'id' => $m->id,
                        'url' => $m->file_url ?? $m->url,
                        'type' => $m->file_type ?? $m->type,
                    ])->values()->all() ?? [],
                ];
            })->values()->all();
        }

        return [
            'today_memories' => $todayMemories,
            'past_week_memories' => $pastWeekMemories,
            'total_count' => count($todayMemories) + count($pastWeekMemories),
        ];
    }
}
