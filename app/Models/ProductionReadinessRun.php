<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductionReadinessRun — প্রোডাকশন প্রস্তুতি ও কনফিগারেশন অডিটের ঐতিহাসিক রেকর্ড
 */
class ProductionReadinessRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'run_uid',
        'overall_status',
        'readiness_score',
        'pass_count',
        'warning_count',
        'fail_count',
        'not_applicable_count',
        'critical_blockers_count',
        'environment',
        'checks',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'readiness_score' => 'integer',
            'pass_count' => 'integer',
            'warning_count' => 'integer',
            'fail_count' => 'integer',
            'not_applicable_count' => 'integer',
            'critical_blockers_count' => 'integer',
            'checks' => 'array',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
