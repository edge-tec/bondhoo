<?php

declare(strict_types=1);

namespace App\Models\Parental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ChildDevice
 *
 * Represents an enrolled physical child device running the Connectly Child Agent.
 */
class ChildDevice extends Model
{
    use HasFactory;

    protected $table = 'child_devices';

    protected $fillable = [
        'family_id',
        'child_member_id',
        'device_uuid',
        'device_name',
        'platform',
        'android_version',
        'app_version',
        'device_token_hash',
        'device_status',
        'last_seen_at',
        'enrolled_at',
        'revoked_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'enrolled_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Family, ChildDevice>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * @return BelongsTo<FamilyMember, ChildDevice>
     */
    public function childMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'child_member_id');
    }

    /**
     * @return HasMany<ScreenTimeRecord>
     */
    public function screenTimeRecords(): HasMany
    {
        return $this->hasMany(ScreenTimeRecord::class, 'child_device_id');
    }

    /**
     * @return HasMany<AppUsageLog>
     */
    public function appUsageLogs(): HasMany
    {
        return $this->hasMany(AppUsageLog::class, 'child_device_id');
    }

    /**
     * @return HasMany<DeviceRestriction>
     */
    public function restrictions(): HasMany
    {
        return $this->hasMany(DeviceRestriction::class, 'child_device_id');
    }

    /**
     * @return HasMany<RemoteCommand>
     */
    public function remoteCommands(): HasMany
    {
        return $this->hasMany(RemoteCommand::class, 'child_device_id');
    }

    /**
     * @return HasMany<ConsentRequest>
     */
    public function consentRequests(): HasMany
    {
        return $this->hasMany(ConsentRequest::class, 'child_device_id');
    }

    /**
     * @return HasMany<ChildSecurityAlert>
     */
    public function securityAlerts(): HasMany
    {
        return $this->hasMany(ChildSecurityAlert::class, 'child_device_id');
    }

    /**
     * Revoke the device credentials and deactivate enrollment.
     */
    public function revoke(): void
    {
        $this->update([
            'device_status' => 'revoked',
            'revoked_at' => now(),
            'device_token_hash' => null,
        ]);
    }
}

