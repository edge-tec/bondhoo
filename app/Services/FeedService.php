<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;

class FeedService
{
    public function __construct(
        protected CacheServiceInterface $cacheService
    ) {}

    /**
     * Get feed with cursor pagination, audience filtering, and selectable ranking algorithm ('smart' or 'chronological').
     */
    public function getFeed(?User $viewer = null, string $algorithm = 'smart', int $perPage = 15): CursorPaginator
    {
        $query = Post::with([
            'user.profile',
            'media',
            'reactions',
        ]);

        // Audience filters:
        if (! $viewer) {
            // Unauthenticated viewers only see public posts
            $query->where('audience', 'public');
        } else {
            $friendIds = $viewer->getFriendIds();
            $followingIds = $viewer->getFollowingIds();

            $query->where(function ($q) use ($viewer, $friendIds, $followingIds) {
                // Author always sees their own posts
                $q->where('user_id', $viewer->id)
                    // Public posts visible to everyone
                    ->orWhere('audience', 'public');

                // Posts with audience 'friends' visible ONLY if author is in viewer's friends list
                if (! empty($friendIds)) {
                    $q->orWhere(function ($sub) use ($friendIds) {
                        $sub->where('audience', 'friends')
                            ->whereIn('user_id', $friendIds);
                    });
                }

                // Posts with audience 'followers' visible ONLY if author is followed by viewer
                if (! empty($followingIds)) {
                    $q->orWhere(function ($sub) use ($followingIds) {
                        $sub->where('audience', 'followers')
                            ->whereIn('user_id', $followingIds);
                    });
                }
            });
        }

        // Status & scheduling filters: hide pending scheduled or drafts
        $query->where(function ($sub) {
            $sub->whereNull('status')->orWhere('status', 'published');
        })->where(function ($sub) {
            $sub->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
        });

        // Feed ranking algorithm
        if ($algorithm === 'smart') {
            // স্মার্ট এনগেজমেন্ট অ্যালগরিদম: লাইক (২ গুণ), কমেন্ট (৩ গুণ), শেয়ার (৫ গুণ)
            return $query->orderByDesc('is_pinned')
                ->orderByRaw('((likes_count * 2) + (comments_count * 3) + (shares_count * 5)) DESC')
                ->orderByDesc('id')
                ->cursorPaginate($perPage);
        }

        // সাধারণ সময়ক্রমিক (Reverse-Chronological)
        return $query->orderByDesc('is_pinned')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }

    /**
     * Get user specific profile feed.
     */
    public function getUserPosts(User $targetUser, ?User $viewer = null, int $perPage = 15): CursorPaginator
    {
        $query = Post::with([
            'user.profile',
            'media',
            'reactions',
        ])->where('user_id', $targetUser->id);

        $isOwner = $viewer && $viewer->id === $targetUser->id;

        if (! $isOwner) {
            $query->where('audience', 'public')
                ->where(function ($sub) {
                    $sub->whereNull('status')->orWhere('status', 'published');
                })->where(function ($sub) {
                    $sub->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
                });
        }

        return $query->orderByDesc('is_pinned')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }
}
