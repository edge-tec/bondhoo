<?php

namespace App\Models;

use App\Services\ProfilePrivacyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveStream extends Model
{
    use HasFactory;

    public const STATUS_CREATING = 'creating';

    public const STATUS_STARTING = 'starting';

    public const STATUS_READY = 'ready';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_LIVE = 'live';

    public const STATUS_ENDING = 'ending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_ENDED = 'ended';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PROCESSING_FAILED = 'processing_failed';

    public const PRIVACY_PUBLIC = 'public';

    public const PRIVACY_FRIENDS = 'friends';

    public const PRIVACY_ONLY_ME = 'only_me';

    public const RECORDING_STATUS_NONE = 'none';

    public const RECORDING_STATUS_RECORDING = 'recording';

    public const RECORDING_STATUS_PROCESSING = 'processing';

    public const RECORDING_STATUS_READY = 'ready';

    public const RECORDING_STATUS_FAILED = 'failed';

    protected $fillable = [
        'channel_id',
        'user_id',
        'title',
        'description',
        'privacy',
        'stream_key',
        'ingest_url',
        'playback_url',
        'sfu_room_id',
        'sfu_provider',
        'sfu_host_token',
        'status',
        'viewers_count',
        'peak_viewers',
        'total_unique_viewers',
        'total_reactions',
        'total_shares',
        'comments_enabled',
        'reactions_enabled',
        'sharing_enabled',
        'recording_enabled',
        'recording_status',
        'recording_url',
        'recording_file_path',
        'thumbnail',
        'started_at',
        'ended_at',
        'duration',
        'generated_post_id',
        'last_heartbeat_at',
        'health_stats',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'viewers_count' => 'integer',
            'peak_viewers' => 'integer',
            'total_unique_viewers' => 'integer',
            'total_reactions' => 'integer',
            'total_shares' => 'integer',
            'duration' => 'integer',
            'comments_enabled' => 'boolean',
            'reactions_enabled' => 'boolean',
            'sharing_enabled' => 'boolean',
            'recording_enabled' => 'boolean',
            'health_stats' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'generated_post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LiveStreamComment::class);
    }

    public function gifts(): HasMany
    {
        return $this->hasMany(LiveStreamGift::class);
    }

    public function viewers(): HasMany
    {
        return $this->hasMany(LiveStreamViewer::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(LiveStreamReaction::class);
    }

    public function moderators(): HasMany
    {
        return $this->hasMany(LiveStreamModerator::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(LiveStreamReport::class);
    }

    public function isStarting(): bool
    {
        return $this->status === self::STATUS_STARTING;
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function isEnding(): bool
    {
        return $this->status === self::STATUS_ENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isEnded(): bool
    {
        return in_array($this->status, [self::STATUS_ENDED, self::STATUS_READY], true);
    }

    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_PROCESSING_FAILED], true);
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        $allowed = [
            self::STATUS_CREATING => [self::STATUS_STARTING, self::STATUS_READY, self::STATUS_FAILED],
            self::STATUS_READY => [self::STATUS_STARTING, self::STATUS_LIVE, self::STATUS_ENDING, self::STATUS_ENDED],
            self::STATUS_PREPARING => [self::STATUS_STARTING, self::STATUS_LIVE, self::STATUS_FAILED],
            self::STATUS_STARTING => [self::STATUS_LIVE, self::STATUS_FAILED, self::STATUS_ENDING],
            self::STATUS_LIVE => [self::STATUS_ENDING, self::STATUS_ENDED, self::STATUS_FAILED],
            self::STATUS_ENDING => [self::STATUS_PROCESSING, self::STATUS_ENDED, self::STATUS_FAILED],
            self::STATUS_PROCESSING => [self::STATUS_READY, self::STATUS_ENDED, self::STATUS_PROCESSING_FAILED],
            self::STATUS_ENDED => [self::STATUS_PROCESSING, self::STATUS_READY],
            self::STATUS_FAILED => [],
            self::STATUS_PROCESSING_FAILED => [],
        ];

        return in_array($targetStatus, $allowed[$this->status] ?? [], true);
    }

    public function hasReplay(): bool
    {
        return $this->recording_status === self::RECORDING_STATUS_READY && ! empty($this->recording_url);
    }

    public function isModerator(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->moderators()->where('user_id', $user->id)->exists();
    }

    public function canView(?User $user): bool
    {
        if ($user && $this->user && $user->id !== $this->user_id) {
            if (app(ProfilePrivacyService::class)->isBlocked($this->user, $user)) {
                return false;
            }
        }

        if ($this->privacy === self::PRIVACY_PUBLIC) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        if ($this->privacy === self::PRIVACY_ONLY_ME) {
            return false;
        }

        if ($this->privacy === self::PRIVACY_FRIENDS) {
            return $this->user->friends()->where('friend_id', $user->id)->exists()
                || $user->friends()->where('friend_id', $this->user_id)->exists();
        }

        return false;
    }
}
