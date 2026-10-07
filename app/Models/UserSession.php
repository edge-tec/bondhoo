<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token_id',
        'device_name',
        'device_fingerprint',
        'browser',
        'os',
        'ip_address',
        'location',
        'is_current',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope query to current session only.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope query to active sessions within given days.
     */
    public function scopeActive($query, int $days = 30)
    {
        return $query->where('last_active_at', '>=', now()->subDays($days));
    }
}
