<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminElevation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'elevated_role',
        'permissions',
        'reason',
        'granted_by',
        'expires_at',
        'revoked_at',
        'revoked_by',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Check if elevation is currently active and not expired or revoked.
     */
    public function isCurrentlyActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        return $this->expires_at->isFuture();
    }
}
