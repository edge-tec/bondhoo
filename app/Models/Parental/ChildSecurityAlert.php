<?php

declare(strict_types=1);

namespace App\Models\Parental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ChildSecurityAlert
 *
 * Security and health event emitted when child device agent detects tampering or permission loss.
 */
class ChildSecurityAlert extends Model
{
    use HasFactory;

    protected $table = 'child_security_alerts';

    protected $fillable = [
        'family_id',
        'child_device_id',
        'alert_type',
        'severity',
        'description',
        'alert_status',
        'detected_at',
        'acknowledged_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Family, ChildSecurityAlert>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * @return BelongsTo<ChildDevice, ChildSecurityAlert>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(ChildDevice::class, 'child_device_id');
    }
}
