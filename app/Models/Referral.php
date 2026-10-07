<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'referrer_id',
        'referral_code',
        'reward_claimed',
        'reward_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'reward_claimed' => 'boolean',
            'reward_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Generate a unique referral code.
     */
    public static function generateUniqueCode(string $prefix = 'JUG'): string
    {
        do {
            $code = strtoupper($prefix.'-'.Str::random(8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }
}
