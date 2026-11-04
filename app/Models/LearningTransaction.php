<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningTransaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'enrollment_id',
        'course_id',
        'user_id',
        'instructor_id',
        'amount',
        'instructor_earning',
        'platform_fee',
        'currency',
        'payment_method',
        'transaction_reference',
        'status',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'instructor_earning' => 'decimal:2',
            'platform_fee' => 'decimal:2',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(LearningEnrollment::class, 'enrollment_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
