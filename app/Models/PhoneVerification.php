<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneVerification extends Model
{
    use HasFactory;

    protected $table = 'phone_verifications';

    protected $fillable = [
        'user_id',
        'phone',
        'otp_hash',
        'gateway',
        'attempts',
        'max_attempts',
        'expires_at',
        'verified_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('verified_at')->where('expires_at', '>', now());
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
