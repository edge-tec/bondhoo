<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * পোস্ট অ্যানালিটিক্স মডেল:
 * পোস্টের ইমপ্রেশন, ইউনিক রিচ, ক্লিক এবং এনগেজমেন্ট রেট ট্র্যাক করে।
 */
class PostAnalytics extends Model
{
    use HasFactory;

    protected $table = 'post_analytics';

    protected $fillable = [
        'post_id',
        'impressions_count',
        'unique_reach',
        'clicks_count',
        'engagement_score',
    ];

    protected function casts(): array
    {
        return [
            'impressions_count' => 'integer',
            'unique_reach' => 'integer',
            'clicks_count' => 'integer',
            'engagement_score' => 'float',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * এনগেজমেন্ট রেট শতাংশে গণনা করা (Engagements / Impressions * 100).
     */
    public function calculateEngagementRate(int $totalEngagements): float
    {
        if ($this->impressions_count <= 0) {
            return 0.0;
        }

        return round(($totalEngagements / $this->impressions_count) * 100, 2);
    }
}
