<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * স্টোরি মডেল:
 * ২৪ ঘণ্টার ক্ষণস্থায়ী স্টোরি কন্টেন্ট, মিডিয়া, রিঅ্যাকশন এবং ভিউয়ার সংখ্যা রিপ্রেজেন্ট করে।
 */
class Story extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';

    public const TYPE_MEDIA = 'media';

    public const STATUS_READY = 'ready';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'content',
        'background_color',
        'font_family',
        'music_title',
        'music_track_id',
        'music_start_offset',
        'music_volume',
        'emoji',
        'interactive_sticker',
        'location',
        'privacy',
        'allow_replies',
        'published_at',
        'expires_at',
        'is_expired',
        'is_archived',
        'is_draft',
        'views_count',
        'reactions_count',
        'replies_count',
        'shares_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_expired' => 'boolean',
            'is_archived' => 'boolean',
            'is_draft' => 'boolean',
            'allow_replies' => 'boolean',
            'music_start_offset' => 'float',
            'music_volume' => 'integer',
            'interactive_sticker' => 'array',
            'views_count' => 'integer',
            'reactions_count' => 'integer',
            'replies_count' => 'integer',
            'shares_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function musicTrack(): BelongsTo
    {
        return $this->belongsTo(MusicTrack::class, 'music_track_id');
    }

    /**
     * মরফিক মিডিয়া সম্পর্ক (backward compatibility)
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /**
     * মাল্টি-আইটেম স্টোরি মিডিয়া রিলেশন
     */
    public function storyMedia(): HasMany
    {
        return $this->hasMany(StoryMedia::class)->orderBy('order');
    }

    public function views(): HasMany
    {
        return $this->hasMany(StoryView::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(StoryReaction::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(StoryReply::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Story $story) {
            if (! $story->published_at && $story->status === self::STATUS_READY && ! $story->is_draft) {
                $story->published_at = now();
            }
            if (! $story->expires_at && $story->published_at) {
                $story->expires_at = $story->published_at->copy()->addHours(24);
            }
        });
    }

    /**
     * শুধুমাত্র সক্রিয় (২৪ ঘণ্টার মধ্যে থাকা এবং আন-আর্কাইভড) স্টোরি ফিল্টার স্কোপ।
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_expired', false)
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->where('status', self::STATUS_READY)
            ->where('expires_at', '>', now());
    }

    /**
     * আর্কাইভড স্টোরি স্কোপ
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    public function isExpired(): bool
    {
        return (bool) $this->is_expired || ($this->expires_at !== null && $this->expires_at->lessThanOrEqualTo(now()));
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_READY && ! $this->is_draft && ! $this->is_archived && ! $this->isExpired();
    }

    /**
     * স্টোরি অবজেক্টের স্ট্যান্ডার্ড রেসপন্স ফরম্যাট।
     *
     * @return array<string, mixed>
     */
    public function toResponseArray(?User $viewer = null): array
    {
        $hasViewed = false;
        $myReaction = null;

        if ($viewer) {
            $hasViewed = $this->views()->where('user_id', $viewer->id)->exists();
            $myReaction = $this->reactions()->where('user_id', $viewer->id)->value('type');
        }

        // Collect media items (supporting both morphMany and story_media sequence)
        $mediaList = [];
        if ($this->relationLoaded('storyMedia') && $this->storyMedia->isNotEmpty()) {
            foreach ($this->storyMedia as $item) {
                if ($item->media) {
                    $mediaData = $item->media->toResponseArray();
                    $mediaData['duration'] = $item->duration;
                    $mediaData['order'] = $item->order;
                    $mediaList[] = $mediaData;
                }
            }
        } elseif ($this->media->isNotEmpty()) {
            $mediaList = $this->media->map(fn ($m) => $m->toResponseArray())->toArray();
        }

        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'username' => $this->user?->username,
                'avatar_url' => $this->user?->profile?->avatar_url ?? $this->user?->avatar_url,
                'is_verified' => (bool) ($this->user?->profile?->is_verified ?? false),
            ],
            'type' => $this->type,
            'status' => $this->status ?? self::STATUS_READY,
            'content' => $this->content,
            'background_color' => $this->background_color,
            'font_family' => $this->font_family,
            'music_title' => $this->music_title ?: ($this->musicTrack?->title ?? null),
            'music_track' => $this->musicTrack?->toResponseArray(),
            'music_start_offset' => $this->music_start_offset,
            'music_volume' => $this->music_volume,
            'emoji' => $this->emoji,
            'interactive_sticker' => $this->interactive_sticker,
            'location' => $this->location,
            'privacy' => $this->privacy,
            'allow_replies' => $this->allow_replies,
            'published_at' => $this->published_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_expired' => $this->isExpired(),
            'time_remaining_seconds' => $this->expires_at ? max(0, (int) now()->diffInSeconds($this->expires_at, false)) : 0,
            'views_count' => $this->views_count,
            'reactions_count' => $this->reactions_count,
            'replies_count' => $this->replies_count,
            'shares_count' => $this->shares_count ?? 0,
            'has_viewed' => $hasViewed,
            'my_reaction' => $myReaction,
            'media' => $mediaList,
            'created_at' => $this->created_at?->toIso8601String(),
            'formatted_time' => $this->created_at?->diffForHumans() ?? 'এইমাত্র',
        ];
    }
}
