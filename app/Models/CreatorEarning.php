<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_month',
        'stars_received',
        'stars_amount',
        'subscription_amount',
        'ad_rev_share',
        'gross_total',
        'platform_fee',
        'net_payout',
        'payout_status',
    ];

    protected function casts(): array
    {
        return [
            'stars_received' => 'integer',
            'stars_amount' => 'decimal:2',
            'subscription_amount' => 'decimal:2',
            'ad_rev_share' => 'decimal:2',
            'gross_total' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'net_payout' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
