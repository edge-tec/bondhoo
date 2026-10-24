<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;

/**
 * ইউনিভার্সাল সার্চ সার্ভিস:
 * ইউজার, পোস্ট, গ্রুপ এবং পেজজুড়ে সার্বজনীন সার্চ সম্পাদন করে।
 * ব্লকড ইউজার ফিল্টারিং ও সার্চ হিস্ট্রি হ্যান্ডেল করে।
 */
class SearchService
{
    public function __construct(
        protected CacheServiceInterface $cacheService
    ) {}

    /**
     * সার্চ কোয়েরি অনুযায়ী ফলাফল রিটার্ন করে।
     */
    public function search(string $query, string $type = 'all', ?User $viewer = null, int $limit = 10, bool $suggest = false): array
    {
        $term = trim($query);

        if ($viewer && ! empty($term) && ! $suggest) {
            $this->recordSearchHistory($viewer, $term);
        }

        $blockedUserIds = [];
        if ($viewer) {
            $blockedUserIds = app(ProfilePrivacyService::class)->getBlockedUserIds($viewer->id);
        }

        if (empty($term)) {
            if ($suggest) {
                $suggestUsersQuery = User::when($viewer, fn ($q) => $q->where('id', '!=', $viewer->id))
                    ->with('profile');

                if (! empty($blockedUserIds)) {
                    $suggestUsersQuery->whereNotIn('id', $blockedUserIds);
                }

                return [
                    'query' => '',
                    'type' => $type,
                    'is_suggestion' => true,
                    'results' => [
                        'users' => $suggestUsersQuery
                            ->latest('id')
                            ->limit(min($limit, 6))
                            ->get()
                            ->map(fn (User $u) => [
                                'id' => $u->id,
                                'name' => $u->name,
                                'username' => $u->username,
                                'avatar_url' => $u->profile?->avatar_url,
                                'bio' => $u->profile?->bio,
                            ])
                            ->values(),
                        'posts' => [],
                        'groups' => Group::where('privacy', Group::PRIVACY_PUBLIC)
                            ->latest('id')
                            ->limit(min($limit, 4))
                            ->get()
                            ->map(fn (Group $g) => $g->toResponseArray($viewer))
                            ->values(),
                        'pages' => Page::latest('id')
                            ->limit(min($limit, 4))
                            ->get()
                            ->map(fn (Page $p) => $p->toResponseArray($viewer))
                            ->values(),
                    ],
                ];
            }

            return [
                'query' => $term,
                'results' => [
                    'users' => [],
                    'posts' => [],
                    'groups' => [],
                    'pages' => [],
                ],
            ];
        }

        $results = [];

        // ১. ইউজার সার্চ (ব্লকড ইউজার ও সেলফ ফিল্টার সহ)
        if ($type === 'all' || $type === 'users') {
            $userQuery = User::where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%");
            });

            if ($viewer) {
                $userQuery->where('id', '!=', $viewer->id);
                if (! empty($blockedUserIds)) {
                    $userQuery->whereNotIn('id', $blockedUserIds);
                }
            }

            $results['users'] = $userQuery
                ->with('profile')
                ->limit($limit)
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'avatar_url' => $u->profile?->avatar_url,
                    'bio' => $u->profile?->bio,
                ])
                ->values();
        }

        // ২. পোস্ট সার্চ (শুধুমাত্র পাবলিক পোস্ট ও ব্লকড ইউজার ফিল্টার)
        if ($type === 'all' || $type === 'posts') {
            $postQuery = Post::where('audience', 'public')
                ->where('content', 'like', "%{$term}%");

            if (! empty($blockedUserIds)) {
                $postQuery->whereNotIn('user_id', $blockedUserIds);
            }

            // Group privacy check: Only show group posts if group is public or viewer is active member
            $postQuery->where(function ($q) use ($viewer) {
                $q->whereNull('group_id')
                    ->orWhereHas('group', function ($gq) use ($viewer) {
                        $gq->where('privacy', Group::PRIVACY_PUBLIC);
                        if ($viewer) {
                            $gq->orWhereHas('members', fn ($mq) => $mq->where('user_id', $viewer->id)->where('status', 'active'));
                        }
                    });
            });

            $results['posts'] = $postQuery
                ->with(['user.profile', 'media'])
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(fn (Post $p) => $p->toResponseArray($viewer))
                ->values();
        }

        // ৩. গ্রুপ সার্চ (পাবলিক বা অনুমোদিত গ্রুপ)
        if ($type === 'all' || $type === 'groups') {
            $groupQuery = Group::where('status', Group::STATUS_ACTIVE)
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('category', 'like', "%{$term}%");
                });

            if ($viewer) {
                $groupQuery->where(function ($q) use ($viewer) {
                    $q->where('privacy', '!=', Group::PRIVACY_HIDDEN)
                        ->orWhereHas('members', fn ($mq) => $mq->where('user_id', $viewer->id)->where('status', 'active'));
                });
            } else {
                $groupQuery->where('privacy', Group::PRIVACY_PUBLIC);
            }

            $results['groups'] = $groupQuery
                ->limit($limit)
                ->get()
                ->map(fn (Group $g) => $g->toResponseArray($viewer))
                ->values();
        }

        // ৪. পেজ সার্চ
        if ($type === 'all' || $type === 'pages') {
            $results['pages'] = Page::where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%")
                    ->orWhere('bio', 'like', "%{$term}%");
            })
                ->limit($limit)
                ->get()
                ->map(fn (Page $p) => $p->toResponseArray($viewer))
                ->values();
        }

        return [
            'query' => $term,
            'type' => $type,
            'results' => $results,
        ];
    }

    /**
     * সার্চ হিস্ট্রি সংরক্ষণ (Redis ব্যাকড)।
     */
    public function recordSearchHistory(User $user, string $query): void
    {
        $term = trim($query);
        if ($term === '') {
            return;
        }

        $key = "user:{$user->id}:recent_searches";
        $history = $this->cacheService->get($key);
        if (! is_array($history)) {
            $history = [];
        }

        $history = array_values(array_unique(array_merge([$term], $history)));
        $history = array_slice($history, 0, 10);

        $this->cacheService->set($key, $history, 86400 * 30);
    }

    /**
     * ইউজারের সাম্প্রতিক সার্চ হিস্ট্রি রিট্রিভ করা।
     */
    public function getSearchHistory(User $user): array
    {
        $key = "user:{$user->id}:recent_searches";
        $history = $this->cacheService->get($key);

        return is_array($history) ? $history : [];
    }

    /**
     * ইউজারের সার্চ হিস্ট্রি মুছে ফেলা।
     */
    public function clearSearchHistory(User $user): void
    {
        $key = "user:{$user->id}:recent_searches";
        $this->cacheService->forget($key);
    }
}
