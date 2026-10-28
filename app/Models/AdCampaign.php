<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdCampaign extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DRAFT = 'draft';

    public const OBJECTIVE_REACH = 'reach';

    public const OBJECTIVE_ENGAGEMENT = 'engagement';

    public const OBJECTIVE_TRAFFIC = 'traffic';

    public const OBJECTIVE_CONVERSIONS = 'conversions';

    public const BILLING_PAID = 'paid';

    public const BILLING_PENDING = 'pending';

    public const BILLING_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'post_id',
        'name',
        'objective',
        'total_budget',
        'daily_budget',
        'amount_spent',
        'cpm_rate',
        'cpc_rate',
        'status',
        'billing_status',
        'start_date',
        'end_date',
        'targeting_criteria',
        'ad_creatives',
        'cta_text',
        'cta_link',
        'impressions_count',
        'clicks_count',
    ];

    protected function casts(): array
    {
        return [
            'total_budget' => 'decimal:2',
            'daily_budget' => 'decimal:2',
            'amount_spent' => 'decimal:2',
            'cpm_rate' => 'decimal:2',
            'cpc_rate' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'targeting_criteria' => 'array',
            'ad_creatives' => 'array',
            'impressions_count' => 'integer',
            'clicks_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Check if campaign is active and within schedule.
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        return $this->hasBudgetRemaining();
    }

    /**
     * Check if the campaign still has unspent budget.
     */
    public function hasBudgetRemaining(): bool
    {
        return (float) $this->amount_spent < (float) $this->total_budget;
    }

    /**
     * Calculate Click-Through-Rate (CTR) percentage.
     */
    public function calculateCtr(): float
    {
        if ($this->impressions_count <= 0) {
            return 0.0;
        }

        return round(($this->clicks_count / $this->impressions_count) * 100, 2);
    }
}
