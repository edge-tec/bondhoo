<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallParticipant extends Model
{
    use HasFactory;

    public const ROLE_CALLER = 'caller';

    public const ROLE_CALLEE = 'callee';

    public const ROLE_PARTICIPANT = 'participant';

    public const STATUS_RINGING = 'ringing';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_LEFT = 'left';

    public const STATUS_MISSED = 'missed';

    public const STATUS_BUSY = 'busy';

    protected $fillable = [
        'call_id',
        'user_id',
        'role',
        'status',
        'joined_at',
        'left_at',
        'duration_seconds',
        'is_muted',
        'is_camera_off',
        'is_screen_sharing',
        'device_info',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'duration_seconds' => 'integer',
            'is_muted' => 'boolean',
            'is_camera_off' => 'boolean',
            'is_screen_sharing' => 'boolean',
            'device_info' => 'array',
        ];
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'call_id' => $this->call_id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'username' => $this->user?->username,
                'avatar_url' => $this->user?->profile?->avatar_url,
            ],
            'role' => $this->role,
            'status' => $this->status,
            'joined_at' => $this->joined_at?->toIso8601String(),
            'left_at' => $this->left_at?->toIso8601String(),
            'duration_seconds' => $this->duration_seconds,
            'is_muted' => (bool) $this->is_muted,
            'is_camera_off' => (bool) $this->is_camera_off,
            'is_screen_sharing' => (bool) $this->is_screen_sharing,
        ];
    }
}
