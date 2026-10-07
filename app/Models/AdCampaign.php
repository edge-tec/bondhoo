<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'objective',
        'total_budget',
        'daily_budget',
        'amount_spent',
        'status',
        'start_date',
        'end_date',
        'targeting_criteria',
        'ad_creatives',
        'impressions_count',
        'clicks_count',
    ];

    protected function casts(): array
    {
        return [
            'total_budget' => 'decimal:2',
            'daily_budget' => 'decimal:2',
            'amount_spent' => 'decimal:2',
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
}
