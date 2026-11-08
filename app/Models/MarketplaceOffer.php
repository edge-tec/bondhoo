<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOffer extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_COUNTERED = 'countered';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'product_id',
        'buyer_id',
        'seller_id',
        'offer_amount',
        'original_price',
        'counter_amount',
        'currency',
        'status',
        'message',
        'counter_message',
        'expires_at',
        'accepted_at',
        'rejected_at',
        'countered_at',
        'cancelled_at',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'buyer_id' => 'integer',
            'seller_id' => 'integer',
            'offer_amount' => 'decimal:2',
            'original_price' => 'decimal:2',
            'counter_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'countered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'product_id')->withTrashed();
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_COUNTERED])
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function toResponseArray(?User $viewer = null): array
    {
        $product = $this->product;
        $currencySymbol = ($this->currency === 'USD' || $this->currency === '$') ? '$' : '৳';

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_title' => $product?->title ?? 'Product Listing',
            'product_primary_image' => $product?->primary_image_url ?? $product?->primary_image,
            'product_price' => (float) ($product?->price ?? 0),
            'buyer_id' => $this->buyer_id,
            'buyer_name' => $this->buyer?->name ?? 'Buyer',
            'buyer_avatar' => $this->buyer?->profile?->avatar_url ? UserProfile::normalizeStorageUrl($this->buyer->profile->avatar_url) : null,
            'seller_id' => $this->seller_id,
            'seller_name' => $this->seller?->name ?? 'Seller',
            'offer_amount' => (float) $this->offer_amount,
            'original_price' => (float) $this->original_price,
            'counter_amount' => $this->counter_amount !== null ? (float) $this->counter_amount : null,
            'currency' => $this->currency ?: 'BDT',
            'currency_symbol' => $currencySymbol,
            'status' => $this->status,
            'message' => $this->message,
            'counter_message' => $this->counter_message,
            'is_buyer' => $viewer ? (int) $viewer->id === (int) $this->buyer_id : false,
            'is_seller' => $viewer ? (int) $viewer->id === (int) $this->seller_id : false,
            'is_expired' => $this->isExpired(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
