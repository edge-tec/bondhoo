<?php

declare(strict_types=1);

namespace App\Models\Parental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ScreenTimeRecord
 *
 * Stores aggregated daily foreground screen-time per package per child device.
 */
class ScreenTimeRecord extends Model
{
    use HasFactory;

    protected $table = 'screen_time_records';

    protected $fillable = [
        'child_device_id',
        'child_member_id',
        'application_package',
        'usage_date',
        'foreground_duration_seconds',
        'collection_started_at',
        'collection_ended_at',
        'source',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'foreground_duration_seconds' => 'integer',
        'collection_started_at' => 'datetime',
        'collection_ended_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<ChildDevice, ScreenTimeRecord>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(ChildDevice::class, 'child_device_id');
    }

    /**
     * @return BelongsTo<FamilyMember, ScreenTimeRecord>
     */
    public function childMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'child_member_id');
    }
}

