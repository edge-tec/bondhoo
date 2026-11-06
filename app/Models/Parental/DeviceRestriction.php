<?php

declare(strict_types=1);

namespace App\Models\Parental;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class DeviceRestriction
 *
 * Defines parental limits, bedtime curfews, app blacklists, or quota rules.
 */
class DeviceRestriction extends Model
{
    use HasFactory;

    protected $table = 'device_restrictions';

    protected $fillable = [
        'family_id',
        'child_device_id',
        'child_member_id',
        'restriction_type',
        'application_package',
        'configuration_json',
        'enabled',
        'policy_version',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected $casts = [
        'configuration_json' => 'array',
        'enabled' => 'boolean',
        'policy_version' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Family, DeviceRestriction>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * @return BelongsTo<ChildDevice, DeviceRestriction>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(ChildDevice::class, 'child_device_id');
    }

    /**
     * @return BelongsTo<FamilyMember, DeviceRestriction>
     */
    public function childMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'child_member_id');
    }

    /**
     * @return BelongsTo<User, DeviceRestriction>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
