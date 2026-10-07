<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * গ্রুপ মেম্বার মডেল:
 * গ্রুপে ইউজারের মেম্বারশিপ, রোল, গ্র্যানুলার পারমিশন এবং মডারেশন স্ট্যাটাস সংরক্ষণ করে।
 */
class GroupMember extends Model
{
    use HasFactory;

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MODERATOR = 'moderator';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_MEMBER = 'member';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PENDING = 'pending';

    public const STATUS_MUTED = 'muted';

    public const STATUS_RESTRICTED = 'restricted';

    public const STATUS_BANNED = 'banned';

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'permissions',
        'status',
        'joined_at',
        'invited_by',
        'answers',
        'strikes_count',
        'muted_until',
        'restricted_until',
        'banned_at',
        'ban_reason',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'answers' => 'array',
            'strikes_count' => 'integer',
            'joined_at' => 'datetime',
            'muted_until' => 'datetime',
            'restricted_until' => 'datetime',
            'banned_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && ! $this->isBanned();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isBanned(): bool
    {
        return $this->status === self::STATUS_BANNED || $this->banned_at !== null;
    }

    public function isMuted(): bool
    {
        if ($this->muted_until && $this->muted_until->isFuture()) {
            return true;
        }

        return $this->status === self::STATUS_MUTED;
    }

    public function isRestricted(): bool
    {
        if ($this->restricted_until && $this->restricted_until->isFuture()) {
            return true;
        }

        return $this->status === self::STATUS_RESTRICTED;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER && $this->isActive();
    }

    public function isAdmin(): bool
    {
        return ($this->role === self::ROLE_ADMIN || $this->role === self::ROLE_OWNER) && $this->isActive();
    }

    public function isModerator(): bool
    {
        return ($this->role === self::ROLE_MODERATOR || $this->isAdmin()) && $this->isActive();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $perms = $this->permissions ?? [];

        return in_array($permission, $perms, true);
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'username' => $this->user?->username,
                'avatar_url' => $this->user?->profile?->avatar_url,
            ],
            'role' => $this->role,
            'status' => $this->status,
            'permissions' => $this->permissions ?? [],
            'strikes_count' => $this->strikes_count,
            'is_muted' => $this->isMuted(),
            'muted_until' => $this->muted_until?->toIso8601String(),
            'is_restricted' => $this->isRestricted(),
            'restricted_until' => $this->restricted_until?->toIso8601String(),
            'is_banned' => $this->isBanned(),
            'banned_at' => $this->banned_at?->toIso8601String(),
            'ban_reason' => $this->ban_reason,
            'joined_at' => $this->joined_at?->toIso8601String(),
        ];
    }
}
