<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Call extends Model
{
    use HasFactory;

    public const TYPE_AUDIO = 'audio';

    public const TYPE_VIDEO = 'video';

    public const TYPE_GROUP_AUDIO = 'group_audio';

    public const TYPE_GROUP_VIDEO = 'group_video';

    public const STATUS_INITIATING = 'initiating';

    public const STATUS_RINGING = 'ringing';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_MISSED = 'missed';

    public const STATUS_BUSY = 'busy';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uuid',
        'conversation_id',
        'caller_id',
        'call_type',
        'status',
        'room_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Call $call) {
            if (empty($call->uuid)) {
                $call->uuid = (string) Str::uuid();
            }
            if (empty($call->room_id)) {
                $call->room_id = 'room_'.Str::random(16);
            }
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CallParticipant::class);
    }

    public function isAudio(): bool
    {
        return in_array($this->call_type, [self::TYPE_AUDIO, self::TYPE_GROUP_AUDIO], true);
    }

    public function isVideo(): bool
    {
        return in_array($this->call_type, [self::TYPE_VIDEO, self::TYPE_GROUP_VIDEO], true);
    }

    public function isGroup(): bool
    {
        return in_array($this->call_type, [self::TYPE_GROUP_AUDIO, self::TYPE_GROUP_VIDEO], true);
    }

    public function toResponseArray(?User $viewer = null): array
    {
        $hasAnswered = $this->duration_seconds > 0 || $this->participants->contains(
            fn ($p) => (int) $p->user_id !== (int) $this->caller_id && ($p->joined_at !== null || $p->status === 'accepted')
        );

        $outcome = match ($this->status) {
            self::STATUS_ACTIVE => 'active',
            self::STATUS_RINGING => 'ringing',
            self::STATUS_REJECTED => 'rejected',
            self::STATUS_BUSY => 'busy',
            self::STATUS_MISSED => 'missed',
            self::STATUS_FAILED => 'failed',
            self::STATUS_ENDED => $hasAnswered ? 'completed' : 'missed',
            default => $this->status,
        };

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'conversation_id' => $this->conversation_id,
            'caller' => [
                'id' => $this->caller?->id,
                'name' => $this->caller?->name,
                'username' => $this->caller?->username,
                'avatar_url' => $this->caller?->profile?->avatar_url,
            ],
            'call_type' => $this->call_type,
            'status' => $this->status,
            'outcome' => $outcome,
            'room_id' => $this->room_id,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'duration_seconds' => $this->duration_seconds,
            'is_mine' => $viewer ? (int) $this->caller_id === (int) $viewer->id : false,
            'participants' => $this->participants->map(fn (CallParticipant $p) => $p->toResponseArray())->values()->all(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
