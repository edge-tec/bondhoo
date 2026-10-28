<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    public const TYPE_COIN_PURCHASE = 'coin_purchase';

    public const TYPE_GIFT_SENT = 'gift_sent';

    public const TYPE_GIFT_RECEIVED = 'gift_received';

    public const TYPE_SUBSCRIPTION_EARNING = 'subscription_earning';

    public const TYPE_AD_REV_SHARE = 'ad_rev_share';

    public const TYPE_PAYOUT_HOLD = 'payout_hold';

    public const TYPE_PAYOUT_SETTLED = 'payout_settled';

    public const TYPE_PAYOUT_REFUND = 'payout_refund';

    protected $fillable = [
        'wallet_id',
        'user_id',
        'type',
        'amount',
        'coins',
        'currency',
        'reference_id',
        'description',
        'balance_after',
        'coins_after',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'coins' => 'integer',
            'balance_after' => 'decimal:2',
            'coins_after' => 'integer',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
