<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_LOCKED = 'locked';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'balance',
        'pending_balance',
        'coins_balance',
        'currency',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'coins_balance' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function hasSufficientCoins(int $coins): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->coins_balance >= $coins;
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->status === self::STATUS_ACTIVE && (float) $this->balance >= $amount;
    }
}
