<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'buyer_id',
        'seller_id',
        'rating',
        'comment',
    ];

    protected $appends = [
        'is_verified_purchase',
    ];

    public function getIsVerifiedPurchaseAttribute(): bool
    {
        return $this->order_id !== null;
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'product_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'order_id');
    }
}
