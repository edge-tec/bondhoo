<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class FeedService
{
    public function __construct(
        protected CacheServiceInterface $cacheService
    ) {}

    /**
     * Get feed with cursor pagination, audience filtering, category tab filtering, and ranking algorithm.
     */
    public function getFeed(?User $viewer = null, string $algorithm = 'smart', int $perPage = 15, ?string $tab = null): CursorPaginator
    {
        $query = Post::with([
            'user.profile',
            'media',
            'reactions',
            'page',
        ]);

        // Phase 16.9: Content Moderation & Safety Status Filtering
        $query->where(function ($q) use ($viewer) {
            $q->where('status', 'published');
            if ($viewer) {
                $q->orWhere(function ($sub) use ($viewer) {
                    $sub->where('user_id', $viewer->id)
                        ->whereNotIn('status', ['rejected', 'quarantined']);
                });
            }
        });

        // Audience & Relationship filters
        if (! $viewer) {
            // Unauthenticated viewers only see public posts
            $query->where('audience', 'public');
        } else {
            $privacyService = app(ProfilePrivacyService::class);

            // Mutual block filtering: Hide posts from users viewer blocked OR users who blocked viewer
            $blockedUserIds = $privacyService->getBlockedUserIds($viewer->id);
            $blockedByUserIds = DB::table('blocked_users')
                ->where('identifier', (string) $viewer->id)
                ->where('is_active', 1)
                ->pluck('user_id')
                ->toArray();
            $allBlockedIds = array_values(array_unique(array_merge($blockedUserIds, $blockedByUserIds)));
            if (! empty($allBlockedIds)) {
                $query->whereNotIn('user_id', $allBlockedIds);
            }

            // Hidden posts filtering: exclude posts explicitly hidden by viewer
            try {
                $hiddenPostIds = DB::table('hidden_posts')
                    ->where('user_id', $viewer->id)
                    ->pluck('post_id')
                    ->toArray();
                if (! empty($hiddenPostIds)) {
                    $query->whereNotIn('id', $hiddenPostIds);
                }
            } catch (\Throwable) {
                // Table might not be migrated yet on older environments
            }

            $friendIds = $viewer->getFriendIds();
            $followingIds = $viewer->getFollowingIds();
            $followerOrFriendIds = array_values(array_unique(array_merge($friendIds, $followingIds)));

            // Muted & Snoozed filtering (only applies to personalized 'for_you' feed)
            if ($tab === null || $tab === 'for_you') {
                try {
                    $mutedFriendIds = DB::table('friendships')
                        ->where('user_id', $viewer->id)
                        ->where(function ($q) {
                            $q->where(function ($sq) {
                                $sq->where('is_muted', 1)
                                    ->where(function ($dsq) {
                                        $dsq->whereNull('muted_until')
                                            ->orWhere('muted_until', '>', Carbon::now());
                                    });
                            })->orWhere('snoozed_until', '>', Carbon::now());
                        })
                        ->pluck('friend_id')
                        ->toArray();

                    if (! empty($mutedFriendIds)) {
                        $query->whereNotIn('user_id', $mutedFriendIds);
                    }
                } catch (\Throwable) {
                    // Columns might not exist yet on older environments
                }
            }

            // Tab-based social filtering
            switch ($tab) {
                case 'friends':
                    $query->whereIn('user_id', array_merge([$viewer->id], $friendIds));
                    break;

                case 'following':
                    $query->whereIn('user_id', array_merge([$viewer->id], $followerOrFriendIds));
                    break;

                case 'groups':
                    $joinedGroupIds = DB::table('group_members')
                        ->where('user_id', $viewer->id)
                        ->where('status', 'active')
                        ->whereNull('banned_at')
                        ->pluck('group_id')
                        ->toArray();

                    $query->whereNotNull('group_id')
                        ->whereIn('group_id', $joinedGroupIds);
                    break;

                case 'pages':
                    $followedPageIds = DB::table('page_followers')
                        ->where('user_id', $viewer->id)
                        ->pluck('page_id')
                        ->toArray();
                    $managedPageIds = DB::table('pages')
                        ->where('owner_id', $viewer->id)
                        ->pluck('id')
                        ->toArray();
                    $eligiblePageIds = array_values(array_unique(array_merge($followedPageIds, $managedPageIds)));

                    $query->whereNotNull('page_id')
                        ->whereIn('page_id', $eligiblePageIds);
                    break;

                case 'saved':
                    $savedPostIds = DB::table('saved_items')
                        ->where('user_id', $viewer->id)
                        ->where(function ($q) {
                            $q->where('item_type', 'post')
                                ->orWhere('item_type', Post::class);
                        })
                        ->pluck('item_id')
                        ->toArray();

                    $query->whereIn('id', $savedPostIds);
                    break;

                default: // 'for_you'
                    // Smart personalized feed includes public posts, friends, following, groups, pages
                    break;
            }

            // Audience privacy validation (author always sees own; others must have valid audience access)
            $query->where(function ($q) use ($viewer, $friendIds, $followerOrFriendIds) {
                // Author always sees their own posts
                $q->where('user_id', $viewer->id)
                    // Public posts visible to everyone
                    ->orWhere('audience', 'public');

                // Audience 'friends' visible ONLY if author is in viewer's friends list
                if (! empty($friendIds)) {
                    $q->orWhere(function ($sub) use ($friendIds) {
                        $sub->where('audience', 'friends')
                            ->whereIn('user_id', $friendIds);
                    });
                }

                // Audience 'followers' visible if author is followed by or friends with viewer
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
            $sub->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', Carbon::now());
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
        if ($algorithm === 'chronological' || $tab === 'following' || $tab === 'friends' || $tab === 'saved') {
            return $query->orderByDesc('is_pinned')
                ->orderByDesc('id')
                ->cursorPaginate($perPage);
        }

        // Smart engagement & recency algorithm: Like (2x), Comment (3x), Share (5x)
        return $query->orderByDesc('is_pinned')
            ->orderByRaw('((likes_count * 2) + (comments_count * 3) + (shares_count * 5)) DESC')
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
            'page',
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
                $sub->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', Carbon::now());
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
