<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SloDefinition extends Model
{
    protected $fillable = [
        'slo_code',
        'name',
        'metric_key',
        'target_value',
        'comparison_operator',
        'unit',
        'window_days',
        'error_budget_minutes',
        'alert_threshold',
        'is_active',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'float',
            'window_days' => 'integer',
            'error_budget_minutes' => 'integer',
            'alert_threshold' => 'float',
            'is_active' => 'boolean',
        ];
    }

    protected $appends = [
        'slo_key',
    ];

    public function getSloKeyAttribute(): string
    {
        return (string) ($this->attributes['slo_key'] ?? $this->slo_code);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
