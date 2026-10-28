<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorPayoutRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const METHOD_BKASH = 'bkash';

    public const METHOD_NAGAD = 'nagad';

    public const METHOD_ROCKET = 'rocket';

    public const METHOD_BANK = 'bank';

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'payment_method',
        'account_number',
        'account_name',
        'bank_name',
        'branch_name',
        'routing_number',
        'status',
        'transaction_reference',
        'rejection_reason',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
