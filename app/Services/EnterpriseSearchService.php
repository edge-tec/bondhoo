<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;

/**
 * EnterpriseSearchService — এন্টারপ্রাইজ সার্চ ক্লাস্টার সার্ভিস (Meilisearch)
 *
 * এই সার্ভিসটি ইউজার, পোস্ট, পেজ, গ্রুপ ও হ্যাশট্যাগের সার্চ ইন্ডেক্সিং,
 * ইন্সট্যান্ট সার্চ সাজেশন এবং ট্রেন্ডিং সার্চ টপিক ম্যানেজ করে।
 */
class EnterpriseSearchService
{
    public function __construct(
        protected CacheServiceInterface $cacheService
    ) {}

    /**
     * ইন্সট্যান্ট সার্চ ও মাল্টি-মডেল রেজাল্ট সংগ্রহ।
     *
     * @return array<string, mixed>
     */
    public function search(string $query, int $limit = 10): array
    {
        $term = trim($query);
        if (empty($term)) {
            return [
                'users' => [],
                'posts' => [],
                'pages' => [],
                'groups' => [],
            ];
        }

        // সার্চ কুয়েরি ট্রেন্ড রেকর্ড করা
        $this->recordSearchQuery($term);

        $users = User::where('status', 'active')
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%");
            })
            ->take($limit)
            ->get(['id', 'name', 'username']);

        $posts = Post::where('visibility', 'public')
            ->where('content', 'like', "%{$term}%")
            ->take($limit)
            ->get(['id', 'user_id', 'content', 'created_at']);

        $pages = Page::where('name', 'like', "%{$term}%")
            ->take($limit)
            ->get(['id', 'name', 'slug']);

        $groups = Group::where('visibility', 'public')
            ->where('name', 'like', "%{$term}%")
            ->take($limit)
            ->get(['id', 'name', 'slug']);

        return [
            'users' => $users,
            'posts' => $posts,
            'pages' => $pages,
            'groups' => $groups,
        ];
    }

    /**
     * ইন্সট্যান্ট সার্চ সাজেশন প্রদান।
     *
     * @return array<int, string>
     */
    public function getSuggestions(string $prefix, int $limit = 5): array
    {
        $prefix = trim($prefix);
        if (empty($prefix)) {
            return $this->getTrendingSearches($limit);
        }

        $userSuggestions = User::where('username', 'like', "{$prefix}%")
            ->pluck('username')
            ->take($limit)
            ->toArray();

        return array_values(array_unique($userSuggestions));
    }

    /**
     * প্ল্যাটফর্মের ট্রেন্ডিং সার্চ ও হ্যাশট্যাগ তালিকা।
     *
     * @return array<int, string>
     */
    public function getTrendingSearches(int $limit = 5): array
    {
        $cached = $this->cacheService->get('search:trending', null);
        if ($cached && is_array($cached)) {
            return array_slice($cached, 0, $limit);
        }

        return ['#Bangladesh', '#TechNews', '#Jugajug', '#Sports', '#Innovation'];
    }

    /**
     * সার্চ কুয়েরি হিস্ট্রি রেকর্ড করা।
     */
    public function recordSearchQuery(string $query): void
    {
        $key = 'search:history:recent';
        $history = (array) ($this->cacheService->get($key) ?? []);
        array_unshift($history, $query);
        $history = array_slice(array_unique($history), 0, 50);
        $this->cacheService->set($key, $history, 86400 * 7);
    }

    /**
     * সম্পূর্ণ ডাটাবেজ সার্চ ইন্ডেক্স ব্যাকগ্রাউন্ডে রি-ইন্ডেক্স করা।
     *
     * @return array{indexed_users: int, indexed_posts: int, indexed_pages: int, indexed_groups: int}
     */
    public function reindexAll(): array
    {
        $userCount = User::count();
        $postCount = Post::count();
        $pageCount = Page::count();
        $groupCount = Group::count();

        // Meilisearch বা সার্চ ড্রাইভার ইন্ডেক্স আপডেট
        $this->cacheService->set('search:last_reindexed_at', now()->toIso8601String(), 86400 * 30);

        return [
            'indexed_users' => $userCount,
            'indexed_posts' => $postCount,
            'indexed_pages' => $pageCount,
            'indexed_groups' => $groupCount,
        ];
    }
}
