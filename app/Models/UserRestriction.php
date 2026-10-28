<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRestriction extends Model
{
    use HasFactory;

    public const TYPE_READ_ONLY = 'read_only';

    public const TYPE_POST_BLOCK = 'post_block';

    public const TYPE_COMMENT_BLOCK = 'comment_block';

    public const TYPE_MESSAGE_BLOCK = 'message_block';

    public const TYPE_FULL_BAN = 'full_ban';

    public const TYPE_RATE_LIMITED = 'rate_limited';

    protected $fillable = [
        'user_id',
        'restriction_type',
        'reason',
        'risk_score',
        'expires_at',
        'created_by',
        'is_active',
        'override_reason',
        'overridden_by',
        'overridden_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'risk_score' => 'integer',
            'overridden_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function overrideAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    /**
     * Check if restriction is currently active and not expired.
     */
    public function isCurrentlyEnforced(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Override this restriction with mandatory justification.
     */
    public function override(User $admin, string $justification): void
    {
        $this->update([
            'is_active' => false,
            'override_reason' => $justification,
            'overridden_by' => $admin->id,
            'overridden_at' => now(),
        ]);
    }
}
