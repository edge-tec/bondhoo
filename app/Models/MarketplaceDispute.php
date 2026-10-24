<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceDispute extends Model
{
    use HasFactory;

    protected $table = 'marketplace_disputes';

    public const STATUS_OPEN = 'open';

    public const STATUS_OPENED = 'open';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_RESOLVED_REFUND = 'resolved_refund';

    public const STATUS_REFUNDED = 'resolved_refund';

    public const STATUS_RESOLVED_REJECT = 'resolved_reject';

    public const STATUS_REJECTED = 'resolved_reject';

    public const STATUS_RESOLVED_RELEASED = 'resolved_released';

    public const STATUS_RELEASED = 'resolved_released';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'order_id',
        'raised_by',
        'status',
        'reason',
        'details',
        'evidence_files',
        'admin_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence_files' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'order_id');
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_UNDER_REVIEW], true);
    }
}
