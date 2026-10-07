<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ReelComment Model
 *
 * Comments and nested replies on Reels.
 */
class ReelComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reel_id',
        'user_id',
        'parent_id',
        'comment',
        'likes_count',
        'replies_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'likes_count' => 'integer',
            'replies_count' => 'integer',
        ];
    }

    public function reel(): BelongsTo
    {
        return $this->belongsTo(Reel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ReelCommentLike::class);
    }

    /**
     * Format response for reel comments drawer.
     *
     * @return array<string, mixed>
     */
    public function toResponseArray(?User $viewer = null): array
    {
        $hasLiked = false;
        if ($viewer) {
            $hasLiked = $this->likes()->where('user_id', $viewer->id)->exists();
        }

        return [
            'id' => $this->id,
            'reel_id' => $this->reel_id,
            'parent_id' => $this->parent_id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'username' => $this->user?->username,
                'avatar_url' => $this->user?->profile?->avatar_url ?? $this->user?->avatar_url,
            ],
            'comment' => $this->comment,
            'likes_count' => $this->likes_count,
            'has_liked' => $hasLiked,
            'created_at' => $this->created_at?->toIso8601String(),
            'formatted_time' => $this->created_at?->diffForHumans() ?? 'just now',
        ];
    }
}
