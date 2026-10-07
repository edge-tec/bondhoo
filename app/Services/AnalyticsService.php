<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostAnalytics;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * অ্যানালিটিক্স সার্ভিস:
 * পোস্টের ইমপ্রেশন, ইউনিক রিচ এবং কনটেন্ট ক্রিয়েটরদের জন্য পারফরম্যান্স রিপোর্ট পরিচালনা করে।
 */
class AnalyticsService
{
    public function __construct(
        protected CacheServiceInterface $cacheService
    ) {}

    /**
     * পোস্টের ইমপ্রেশন ও ইউনিক রিচ রেকর্ড করা।
     */
    public function recordImpression(Post $post, ?User $viewer = null): void
    {
        $analytics = PostAnalytics::firstOrCreate(
            ['post_id' => $post->id],
            ['impressions_count' => 0, 'unique_reach' => 0, 'clicks_count' => 0, 'engagement_score' => 0]
        );

        $analytics->increment('impressions_count');

        // ইউনিক রিচ ট্র্যাকিং (রেডিসে ২৪ ঘণ্টার সেট ব্যবহার করে)
        if ($viewer) {
            $reachKey = "post:{$post->id}:reach:{$viewer->id}";
            $alreadyCounted = $this->cacheService->get($reachKey);

            if (! $alreadyCounted) {
                $analytics->increment('unique_reach');
                $this->cacheService->set($reachKey, '1', 86400);
            }
        }
    }

    /**
     * পোস্ট ক্লিকে সংখ্যা বৃদ্ধি।
     */
    public function recordClick(Post $post): void
    {
        $analytics = PostAnalytics::firstOrCreate(
            ['post_id' => $post->id],
            ['impressions_count' => 0, 'unique_reach' => 0, 'clicks_count' => 0, 'engagement_score' => 0]
        );

        $analytics->increment('clicks_count');
    }

    /**
     * পোস্টের বিস্তারিত অ্যানালিটিক্স ও ইনসাইটস রিটার্ন করা (শুধুমাত্র লেখক বা অ্যাডমিন)।
     */
    public function getPostAnalytics(Post $post, User $viewer): array
    {
        if ($viewer->id !== $post->user_id && ! $viewer->hasRole('admin')) {
            throw new AuthorizationException('You are not authorized to view insights for this post.');
        }

        $analytics = PostAnalytics::firstOrCreate(
            ['post_id' => $post->id],
            ['impressions_count' => 0, 'unique_reach' => 0, 'clicks_count' => 0, 'engagement_score' => 0]
        );

        $totalEngagements = $post->likes_count + $post->comments_count + $post->shares_count;
        $engagementRate = $analytics->calculateEngagementRate($totalEngagements);

        return [
            'post_id' => $post->id,
            'impressions' => $analytics->impressions_count,
            'reach' => $analytics->unique_reach,
            'clicks' => $analytics->clicks_count,
            'total_engagements' => $totalEngagements,
            'engagement_rate' => $engagementRate,
            'breakdown' => [
                'likes' => $post->likes_count,
                'comments' => $post->comments_count,
                'shares' => $post->shares_count,
            ],
            'created_at' => $post->created_at?->toIso8601String(),
        ];
    }
}
