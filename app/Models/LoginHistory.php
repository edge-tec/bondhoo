<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device',
        'browser',
        'os',
        'device_type',
        'country',
        'city',
        'status',
        'failure_reason',
        'is_suspicious',
        'session_id',
        'login_at',
        'logout_at',
    ];

    protected function casts(): array
    {
        return [
            'is_suspicious' => 'boolean',
            'login_at' => 'datetime',
            'logout_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this session is currently marked active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'success' && $this->logout_at === null;
    }

    /**
     * Scope query to recent logins.
     */
    public function scopeRecent($query, int $limit = 20)
    {
        return $query->latest('login_at')->latest('id')->limit($limit);
    }
}
