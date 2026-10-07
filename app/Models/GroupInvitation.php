<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupInvitation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'group_id',
        'inviter_id',
        'invitee_id',
        'token',
        'role',
        'expires_at',
        'max_uses',
        'uses_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    public function isExpired(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return true;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        if ($this->max_uses > 0 && $this->uses_count >= $this->max_uses) {
            return true;
        }

        return false;
    }
}
