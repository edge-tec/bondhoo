<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpLog extends Model
{
    use HasFactory;

    protected $table = 'otp_logs';

    protected $fillable = [
        'user_id',
        'identifier',
        'otp_code',
        'type',
        'ip_address',
        'user_agent',
        'expires_at',
        'verified_at',
        'is_used',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'is_used' => 'boolean',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
