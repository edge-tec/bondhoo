<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigDriftBaseline extends Model
{
    use HasFactory;

    public const STATUS_COMPLIANT = 'compliant';

    public const STATUS_DRIFT_DETECTED = 'drift_detected';

    public const STATUS_EXCEPTION_GRANTED = 'exception_granted';

    protected $fillable = [
        'baseline_uid',
        'version',
        'name',
        'approved_baseline',
        'last_drift_report',
        'drift_count',
        'status',
        'exceptions',
        'approved_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'drift_count' => 'integer',
            'approved_baseline' => 'array',
            'last_drift_report' => 'array',
            'exceptions' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    protected $appends = [
        'baseline_key',
    ];

    public function getBaselineKeyAttribute(): string
    {
        return (string) ($this->attributes['baseline_key'] ?? $this->baseline_uid);
    }

    public function setBaselineKeyAttribute(string $value): void
    {
        $this->attributes['baseline_uid'] = $value;
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
