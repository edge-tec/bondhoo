<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    use HasFactory;

    public const STATUS_NONE = 'none';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
        'requested_at',
        'accepted_at',
        'declined_at',
        'interacted_at',
        'is_favorite',
        'is_close_friend',
        'is_restricted',
        'is_muted',
        'muted_until',
        'snoozed_until',
    ];

    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'is_close_friend' => 'boolean',
            'is_restricted' => 'boolean',
            'is_muted' => 'boolean',
            'requested_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'interacted_at' => 'datetime',
            'muted_until' => 'datetime',
            'snoozed_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function friend(): BelongsTo
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeBetween(Builder $query, int $userA, int $userB): Builder
    {
        return $query->where(function ($q) use ($userA, $userB) {
            $q->where('user_id', $userA)->where('friend_id', $userB);
        })->orWhere(function ($q) use ($userA, $userB) {
            $q->where('user_id', $userB)->where('friend_id', $userA);
        });
    }

    public function scopeFavorites(Builder $query): Builder
    {
        return $query->where('is_favorite', true);
    }

    public function scopeCloseFriends(Builder $query): Builder
    {
        return $query->where('is_close_friend', true);
    }
}
