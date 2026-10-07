<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'identifier',
        'purpose',
        'code_hash',
        'expires_at',
        'resend_available_at',
        'retry_count',
        'max_retries',
        'is_used',
        'used_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'used_at' => 'datetime',
            'retry_count' => 'integer',
            'max_retries' => 'integer',
            'is_used' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function canRetry(): bool
    {
        return ! $this->is_used && ! $this->isExpired() && $this->retry_count < $this->max_retries;
    }
}
