<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplacePaymentTransaction extends Model
{
    use HasFactory;

    protected $table = 'marketplace_payment_transactions';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCESSFUL = 'successful';

    public const STATUS_COMPLETED = 'successful';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REVERSED = 'reversed';

    public const STATUS_CANCELLED = 'reversed';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_CHARGE = 'payment';

    public const TYPE_REFUND = 'refund';

    public const TYPE_RELEASE = 'release';

    public const TYPE_FEE = 'fee';

    protected $fillable = [
        'order_id',
        'user_id',
        'transaction_reference',
        'provider',
        'type',
        'amount',
        'currency',
        'status',
        'idempotency_key',
        'gateway_payload',
        'gateway_response',
        'ip_address',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_payload' => 'array',
            'gateway_response' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }
}
