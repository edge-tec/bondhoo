<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class MarketplaceOrder extends Model
{
    use HasFactory;

    // Order Lifecycle States
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUND_REQUESTED = 'refund_requested';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_DISPUTED = 'disputed';

    // Payment States
    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PROCESSING = 'processing';

    public const PAYMENT_AUTHORIZED = 'authorized';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_REFUNDED = 'refunded';

    public const PAYMENT_FAILED = 'failed';

    // Escrow / Payment-Hold States
    public const ESCROW_PENDING = 'pending';

    public const ESCROW_HELD = 'held';

    public const ESCROW_RELEASED = 'released';

    public const ESCROW_REFUNDED = 'refunded';

    public const ESCROW_DISPUTED = 'disputed';

    // Delivery Types
    public const DELIVERY_STANDARD = 'standard';

    public const DELIVERY_EXPRESS = 'express';

    public const DELIVERY_PICKUP = 'pickup';

    protected $fillable = [
        'order_number',
        'buyer_id',
        'seller_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'delivery_type',
        'delivery_charge',
        'delivery_fee',
        'discount_amount',
        'amount',
        'total_amount',
        'currency',
        'status',
        'escrow_status',
        'payment_method',
        'payment_status',
        'transaction_id',
        'idempotency_key',
        'shipping_address',
        'shipping_name',
        'shipping_phone',
        'product_snapshot',
        'paid_at',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'disputed_at',
        'dispute_reason',
        'dispute_status',
        'dispute_notes',
        'refunded_at',
        'refund_amount',
        'refund_reason',
        'escrow_released_at',
        'tracking_carrier',
        'tracking_number',
    ];

    public function getTotalAmountAttribute(): float
    {
        return (float) ($this->attributes['amount'] ?? $this->attributes['total_amount'] ?? 0);
    }

    public function setTotalAmountAttribute($value): void
    {
        $this->attributes['amount'] = $value;
    }

    public function getDeliveryFeeAttribute(): float
    {
        return (float) ($this->attributes['delivery_charge'] ?? $this->attributes['delivery_fee'] ?? 0);
    }

    public function setDeliveryFeeAttribute($value): void
    {
        $this->attributes['delivery_charge'] = $value;
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'product_snapshot' => 'array',
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'disputed_at' => 'datetime',
            'refunded_at' => 'datetime',
            'escrow_released_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MarketplaceOrder $order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
            if (empty($order->quantity)) {
                $order->quantity = 1;
            }
            if (empty($order->currency)) {
                $order->currency = 'BDT';
            }
            if (empty($order->delivery_charge) && ! empty($order->delivery_fee)) {
                $order->delivery_charge = $order->delivery_fee;
            }
            if (empty($order->amount) && ! empty($order->total_amount)) {
                $order->amount = $order->total_amount;
            } elseif (empty($order->amount) && ! empty($order->subtotal)) {
                $order->amount = round($order->subtotal + (float) ($order->delivery_charge ?? 0), 2);
            }
            if ($order->unit_price === null && $order->amount !== null) {
                $order->unit_price = $order->amount;
                $order->subtotal = $order->amount * $order->quantity;
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'product_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MarketplacePaymentTransaction::class, 'order_id');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(MarketplacePaymentTransaction::class, 'order_id');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(MarketplaceDispute::class, 'order_id');
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(MarketplaceDispute::class, 'order_id')->latestOfMany();
    }

    public function review(): HasOne
    {
        return $this->hasOne(MarketplaceReview::class, 'order_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MarketplaceReview::class, 'order_id');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID || $this->paid_at !== null;
    }

    public function isDelivered(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_COMPLETED], true);
    }

    public function isEscrowReleased(): bool
    {
        return $this->escrow_status === self::ESCROW_RELEASED;
    }

    public function canBeCancelledBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->status === self::STATUS_CANCELLED || $this->status === self::STATUS_DELIVERED || $this->status === self::STATUS_COMPLETED) {
            return false;
        }

        // Buyer can cancel before shipment
        if ($this->buyer_id === $user->id) {
            return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_CONFIRMED], true)
                && $this->shipped_at === null;
        }

        // Seller or Admin can cancel before delivery
        if ($this->seller_id === $user->id || ($user->is_admin ?? false)) {
            return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_CONFIRMED, self::STATUS_SHIPPED], true);
        }

        return false;
    }

    public function canBeShippedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->seller_id !== $user->id && ! $user->isAdmin()) {
            return false;
        }

        return in_array($this->status, [self::STATUS_PROCESSING, self::STATUS_CONFIRMED], true)
            && $this->shipped_at === null;
    }

    public function canReleaseEscrow(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->buyer_id !== $user->id && ! $user->isAdmin()) {
            return false;
        }

        return $this->escrow_status !== self::ESCROW_RELEASED
            && ! in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_REFUNDED], true);
    }

    public function canBeDisputedBy(?User $user): bool
    {
        if (! $user || $this->buyer_id !== $user->id) {
            return false;
        }

        return in_array($this->status, [self::STATUS_SHIPPED, self::STATUS_DELIVERED], true)
            && $this->escrow_status !== self::ESCROW_RELEASED
            && $this->dispute_status !== 'resolved_refund';
    }

    public function canBeReviewedBy(?User $user): bool
    {
        if (! $user || $this->buyer_id !== $user->id) {
            return false;
        }

        return $this->isDelivered() && $this->review === null;
    }

    public function toResponseArray(?User $viewer = null): array
    {
        $currencySymbol = ($this->currency === 'USD' || $this->currency === '$') ? '$' : '৳';
        $formattedAmount = $currencySymbol.number_format((float) $this->amount, 2);

        $productData = $this->product_snapshot ?: ($this->product ? [
            'id' => $this->product->id,
            'title' => $this->product->title,
            'price' => (float) $this->product->price,
            'primary_image' => is_array($this->product->images) ? ($this->product->images[0] ?? null) : null,
            'condition' => $this->product->condition,
            'location' => $this->product->location,
        ] : null);

        $buyerAvatar = $this->buyer?->profile?->avatar_url
            ? UserProfile::normalizeStorageUrl($this->buyer->profile->avatar_url)
            : ($this->buyer?->avatar_url ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80');

        $sellerAvatar = $this->seller?->profile?->avatar_url
            ? UserProfile::normalizeStorageUrl($this->seller->profile->avatar_url)
            : ($this->seller?->avatar_url ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80');

        return [
            'id' => $this->id,
            'order_number' => $this->order_number ?: ('ORD-'.$this->id),
            'product_id' => $this->product_id,
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) ($this->unit_price ?: $this->amount),
            'subtotal' => (float) ($this->subtotal ?: $this->amount),
            'delivery_type' => $this->delivery_type ?: 'pickup',
            'delivery_charge' => (float) $this->delivery_charge,
            'delivery_fee' => (float) $this->delivery_charge,
            'discount_amount' => (float) $this->discount_amount,
            'amount' => (float) $this->amount,
            'total_amount' => (float) $this->amount,
            'formatted_amount' => $formattedAmount,
            'currency' => $this->currency ?: 'BDT',
            'status' => $this->status,
            'escrow_status' => $this->escrow_status,
            'payment_status' => $this->payment_status ?: ($this->isPaid() ? 'paid' : 'unpaid'),
            'payment_method' => $this->payment_method ?: 'bkash',
            'transaction_id' => $this->transaction_id,
            'shipping_name' => $this->shipping_name ?: ($this->buyer?->name ?? ''),
            'shipping_phone' => $this->shipping_phone ?: '',
            'shipping_address' => $this->shipping_address ?: '',
            'product' => $productData,
            'tracking_carrier' => $this->tracking_carrier,
            'tracking_number' => $this->tracking_number,
            'buyer' => [
                'id' => $this->buyer?->id,
                'name' => $this->buyer?->name ?? 'Buyer',
                'username' => $this->buyer?->username ?? '',
                'avatar_url' => $buyerAvatar,
            ],
            'seller' => [
                'id' => $this->seller?->id,
                'name' => $this->seller?->name ?? 'Seller',
                'username' => $this->seller?->username ?? '',
                'avatar_url' => $sellerAvatar,
            ],
            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'paid_at' => $this->paid_at?->toIso8601String(),
                'confirmed_at' => $this->confirmed_at?->toIso8601String(),
                'shipped_at' => $this->shipped_at?->toIso8601String(),
                'delivered_at' => $this->delivered_at?->toIso8601String(),
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
                'refunded_at' => $this->refunded_at?->toIso8601String(),
                'escrow_released_at' => $this->escrow_released_at?->toIso8601String(),
            ],
            'cancellation' => $this->cancelled_at ? [
                'reason' => $this->cancellation_reason,
                'cancelled_at' => $this->cancelled_at->toIso8601String(),
                'cancelled_by' => $this->cancelled_by,
            ] : null,
            'dispute' => $this->dispute_status ? [
                'status' => $this->dispute_status,
                'reason' => $this->dispute_reason,
                'notes' => $this->dispute_notes,
                'disputed_at' => $this->disputed_at?->toIso8601String(),
            ] : null,
            'refund' => $this->refunded_at ? [
                'amount' => (float) $this->refund_amount,
                'reason' => $this->refund_reason,
                'refunded_at' => $this->refunded_at->toIso8601String(),
            ] : null,
            'permissions' => [
                'can_cancel' => $this->canBeCancelledBy($viewer),
                'can_ship' => $this->canBeShippedBy($viewer),
                'can_release_escrow' => $this->canReleaseEscrow($viewer),
                'can_dispute' => $this->canBeDisputedBy($viewer),
                'can_review' => $this->canBeReviewedBy($viewer),
                'is_buyer' => $viewer ? $this->buyer_id === $viewer->id : false,
                'is_seller' => $viewer ? $this->seller_id === $viewer->id : false,
            ],
        ];
    }
}
