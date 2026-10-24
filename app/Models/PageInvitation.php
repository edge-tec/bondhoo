<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageInvitation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Default expiration lifetime for page follow invitations in days.
     */
    public const DEFAULT_EXPIRY_DAYS = 14;

    /**
     * Maximum number of invitations an inviter can send within 24 hours.
     */
    public const DAILY_RATE_LIMIT = 30;

    protected $fillable = [
        'page_id',
        'inviter_id',
        'invitee_id',
        'status',
        'expires_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->status === self::STATUS_PENDING && $this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        return false;
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'page_id' => $this->page_id,
            'page' => $this->relationLoaded('page') && $this->page ? [
                'id' => $this->page->id,
                'name' => $this->page->name,
                'slug' => $this->page->slug,
                'avatar_url' => $this->page->avatar_url,
                'category' => $this->page->category,
                'followers_count' => $this->page->followers_count,
            ] : null,
            'inviter_id' => $this->inviter_id,
            'inviter' => $this->relationLoaded('inviter') && $this->inviter ? [
                'id' => $this->inviter->id,
                'name' => $this->inviter->name,
                'username' => $this->inviter->username,
                'avatar_url' => $this->inviter->avatar_url,
            ] : null,
            'invitee_id' => $this->invitee_id,
            'status' => $this->isExpired() ? self::STATUS_EXPIRED : $this->status,
            'expires_at' => $this->expires_at?->toISOString(),
            'responded_at' => $this->responded_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
