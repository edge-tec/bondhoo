<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminTrustedDevice extends Model
{
    use HasFactory;

    protected $table = 'admin_trusted_devices';

    protected $fillable = [
        'admin_id',
        'device_fingerprint',
        'device_name',
        'browser',
        'os',
        'ip_address',
        'trusted_until',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'trusted_until' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function isValid(): bool
    {
        return $this->trusted_until === null || $this->trusted_until->isFuture();
    }
}
