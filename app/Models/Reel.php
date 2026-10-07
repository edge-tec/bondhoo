<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * রিলস মডেল:
 * ফেসবুক/ইনস্টাগ্রাম স্টাইলের শর্ট-ফর্ম ভার্টিক্যাল (9:16) ভিডিও কন্টেন্ট।
 */
class Reel extends Model
{
    use HasFactory;

    public const STATUS_READY = 'ready';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'caption',
        'audio_title',
        'audio_artist',
        'cover_image_path',
        'music_track_id',
        'audio_volume',
        'music_volume',
        'music_start_offset',
        'trim_start',
        'trim_end',
        'rotation_deg',
        'crop_aspect',
        'duration',
        'width',
        'height',
        'aspect_ratio',
        'views_count',
        'likes_count',
        'comments_count',
        'shares_count',
        'saves_count',
        'privacy',
        'status',
        'published_at',
        'expires_at',
        'is_expired',
        'is_draft',
        'location',
        'allow_comments',
        'allow_duet',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration' => 'float',
            'audio_volume' => 'integer',
            'music_volume' => 'integer',
            'music_start_offset' => 'float',
            'trim_start' => 'float',
            'trim_end' => 'float',
            'rotation_deg' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'views_count' => 'integer',
            'likes_count' => 'integer',
            'comments_count' => 'integer',
            'shares_count' => 'integer',
            'saves_count' => 'integer',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_expired' => 'boolean',
            'is_draft' => 'boolean',
            'allow_comments' => 'boolean',
            'allow_duet' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ReelMedia::class);
    }

    public function musicTrack(): BelongsTo
    {
        return $this->belongsTo(MusicTrack::class, 'music_track_id');
    }

    public function musicUsage(): HasOne
    {
        return $this->hasOne(MusicUsage::class, 'usable_id')->where('usable_type', self::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(ReelView::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ReelReaction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReelComment::class)->latest();
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ReelShare::class);
    }

    public function saves(): HasMany
    {
        return $this->hasMany(ReelSave::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Reel $reel) {
            if ($reel->status === self::STATUS_READY && ! $reel->is_draft && ! $reel->published_at) {
                $reel->published_at = now();
                $reel->expires_at = $reel->published_at->copy()->addHours(24);
                $reel->is_expired = false;
            }
        });
    }

    /**
     * শুধুমাত্র সক্রিয় (২৪ ঘণ্টার মধ্যে থাকা এবং আন-এক্সপায়ার্ড) রিল ফিল্টার স্কোপ
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_READY)
            ->where('is_draft', false)
            ->where('is_expired', false)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * শুধুমাত্র প্রসেসড এবং সক্রিয় রিল ফিল্টার স্কোপ
     */
    public function scopeReady(Builder $query): Builder
    {
        return $this->scopeActive($query);
    }

    public function scopePublicFeed(Builder $query): Builder
    {
        return $query->active()
            ->where('privacy', 'public')
            ->latest('id');
    }

    public function isExpired(): bool
    {
        return (bool) $this->is_expired || ($this->expires_at !== null && $this->expires_at->lessThanOrEqualTo(now()));
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_READY && ! $this->is_draft && ! $this->isExpired();
    }

    /**
     * রিল অবজেক্টের এপিআই রেসপন্স ফরম্যাট
     *
     * @return array<string, mixed>
     */
    public function toResponseArray(?User $viewer = null): array
    {
        $hasLiked = false;
        $myReaction = null;
        $hasSaved = false;

        if ($viewer) {
            $reaction = $this->reactions()->where('user_id', $viewer->id)->first();
            if ($reaction) {
                $hasLiked = true;
                $myReaction = $reaction->type;
            }
            $hasSaved = $this->saves()->where('user_id', $viewer->id)->exists();
        }

        // Renditions mapping
        $renditions = $this->media->map(fn ($m) => [
            'id' => $m->id,
            'quality' => $m->quality,
            'video_url' => $m->video_url,
            'thumbnail_url' => $m->thumbnail_url,
            'mime_type' => $m->mime_type,
            'size' => $m->size,
            'bitrate' => $m->bitrate,
        ])->values()->toArray();

        $primary = $this->media->firstWhere('quality', 'original') ?? $this->media->first();

        $coverUrl = null;
        if ($this->cover_image_path) {
            $coverUrl = str_starts_with($this->cover_image_path, 'http')
                ? $this->cover_image_path
                : asset('storage/'.$this->cover_image_path);
        } else {
            $coverUrl = $primary?->thumbnail_url;
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
            'caption' => $this->caption,
            'audio_title' => $this->audio_title ?: ($this->musicTrack?->title ?? 'Original Audio'),
            'audio_artist' => $this->audio_artist ?: ($this->musicTrack?->artist ?? $this->user?->name),
            'music_track' => $this->musicTrack?->toResponseArray(),
            'audio_volume' => $this->audio_volume,
            'music_volume' => $this->music_volume,
            'music_start_offset' => $this->music_start_offset,
            'trim_start' => $this->trim_start,
            'trim_end' => $this->trim_end,
            'rotation_deg' => $this->rotation_deg,
            'crop_aspect' => $this->crop_aspect,
            'duration' => $this->duration,
            'width' => $this->width,
            'height' => $this->height,
            'aspect_ratio' => $this->aspect_ratio,
            'views_count' => $this->views_count,
            'likes_count' => $this->likes_count,
            'comments_count' => $this->comments_count,
            'shares_count' => $this->shares_count,
            'saves_count' => $this->saves_count,
            'privacy' => $this->privacy,
            'status' => $this->status,
            'is_draft' => $this->is_draft,
            'location' => $this->location,
            'allow_comments' => $this->allow_comments,
            'allow_duet' => $this->allow_duet,
            'published_at' => $this->published_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_expired' => $this->isExpired(),
            'time_remaining_seconds' => $this->expires_at ? max(0, (int) now()->diffInSeconds($this->expires_at, false)) : 0,
            'video_url' => $primary?->video_url,
            'thumbnail_url' => $coverUrl,
            'cover_url' => $coverUrl,
            'has_liked' => $hasLiked,
            'my_reaction' => $myReaction,
            'has_saved' => $hasSaved,
            'renditions' => $renditions,
            'created_at' => $this->created_at?->toIso8601String(),
            'formatted_time' => $this->created_at?->diffForHumans() ?? 'এইমাত্র',
        ];
    }
}
