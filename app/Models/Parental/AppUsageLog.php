<?php

declare(strict_types=1);

namespace App\Models\Parental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AppUsageLog
 *
 * Granular telemetry session log emitted by the Android Child Agent.
 */
class AppUsageLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'app_usage_logs';

    protected $fillable = [
        'child_device_id',
        'application_package',
        'usage_type',
        'duration_seconds',
        'event_started_at',
        'event_ended_at',
        'collected_at',
        'created_at',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'event_started_at' => 'datetime',
        'event_ended_at' => 'datetime',
        'collected_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<ChildDevice, AppUsageLog>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(ChildDevice::class, 'child_device_id');
    }
}
