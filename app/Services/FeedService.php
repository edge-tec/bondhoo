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
            $privacyService = app(ProfilePrivacyService::class);
            $blockedUserIds = $privacyService->getBlockedUserIds($viewer->id);
            if (! empty($blockedUserIds)) {
                $query->whereNotIn('user_id', $blockedUserIds);
            }

            $friendIds = $viewer->getFriendIds();
            $followingIds = $viewer->getFollowingIds();
            $followerOrFriendIds = array_values(array_unique(array_merge($friendIds, $followingIds)));

            $query->where(function ($q) use ($viewer, $friendIds, $followerOrFriendIds) {
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

                // Posts with audience 'followers' visible if author is followed by or friends with viewer
                if (! empty($followerOrFriendIds)) {
                    $q->orWhere(function ($sub) use ($followerOrFriendIds) {
                        $sub->where('audience', 'followers')
                            ->whereIn('user_id', $followerOrFriendIds);
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

        // Private group protection: Never leak non-public group posts to non-members
        $query->where(function ($q) use ($viewer) {
            $q->whereNull('group_id')
                ->orWhereHas('group', function ($gq) use ($viewer) {
                    $gq->where('privacy', 'public');
                    if ($viewer) {
                        $gq->orWhereHas('members', fn ($mq) => $mq->where('user_id', $viewer->id)->where('status', 'active'));
                    }
                });
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
        $privacyService = app(ProfilePrivacyService::class);

        if ($viewer && $privacyService->isBlocked($targetUser, $viewer)) {
            return Post::whereRaw('1 = 0')->cursorPaginate($perPage);
        }

        $query = Post::with([
            'user.profile',
            'media',
            'reactions',
        ])->where('user_id', $targetUser->id);

        $isOwner = $viewer && $viewer->id === $targetUser->id;

        if (! $isOwner) {
            $isFriend = $viewer && $privacyService->isFriend($targetUser, $viewer);
            $isFollower = $viewer && ($privacyService->isFollower($targetUser, $viewer) || $isFriend);

            $query->where(function ($q) use ($isFriend, $isFollower) {
                $q->where('audience', 'public');
                if ($isFriend) {
                    $q->orWhere('audience', 'friends');
                }
                if ($isFollower) {
                    $q->orWhere('audience', 'followers');
                }
            })->where(function ($sub) {
                $sub->whereNull('status')->orWhere('status', 'published');
            })->where(function ($sub) {
                $sub->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            });

            // Hide private group posts from non-members
            $query->where(function ($q) use ($viewer) {
                $q->whereNull('group_id')
                    ->orWhereHas('group', function ($gq) use ($viewer) {
                        $gq->where('privacy', 'public');
                        if ($viewer) {
                            $gq->orWhereHas('members', fn ($mq) => $mq->where('user_id', $viewer->id)->where('status', 'active'));
                        }
                    });
            });
        }

        return $query->orderByDesc('is_pinned')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }
}
