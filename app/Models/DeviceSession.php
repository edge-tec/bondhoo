<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'platform',
        'app_version',
        'ip_address',
        'user_agent',
        'push_token',
        'last_active_at',
        'is_revoked',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'is_revoked' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_revoked', false);
    }
}
