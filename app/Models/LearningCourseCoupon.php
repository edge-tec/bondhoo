<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningCourseCoupon extends Model
{
    use HasFactory;

    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'course_id',
        'code',
        'discount_type',
        'discount_amount',
        'max_uses',
        'times_used',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'max_uses' => 'integer',
            'times_used' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses > 0 && $this->times_used >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
