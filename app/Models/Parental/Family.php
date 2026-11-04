<?php

declare(strict_types=1);

namespace App\Models\Parental;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Family
 *
 * Represents an isolated parental control family unit.
 */
class Family extends Model
{
    use HasFactory;

    protected $table = 'families';

    protected $fillable = [
        'name',
        'owner_user_id',
        'timezone',
        'status',
    ];

    /**
     * @return BelongsTo<User, Family>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return HasMany<FamilyMember>
     */
    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class, 'family_id');
    }

    /**
     * @return HasMany<ChildDevice>
     */
    public function childDevices(): HasMany
    {
        return $this->hasMany(ChildDevice::class, 'family_id');
    }

    /**
     * @return HasMany<DeviceRestriction>
     */
    public function restrictions(): HasMany
    {
        return $this->hasMany(DeviceRestriction::class, 'family_id');
    }

    /**
     * @return HasMany<RemoteCommand>
     */
    public function remoteCommands(): HasMany
    {
        return $this->hasMany(RemoteCommand::class, 'family_id');
    }

    /**
     * @return HasMany<ConsentRequest>
     */
    public function consentRequests(): HasMany
    {
        return $this->hasMany(ConsentRequest::class, 'family_id');
    }

    /**
     * @return HasMany<ChildSecurityAlert>
     */
    public function securityAlerts(): HasMany
    {
        return $this->hasMany(ChildSecurityAlert::class, 'family_id');
    }

    /**
     * @return HasMany<FamilyAuditLog>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(FamilyAuditLog::class, 'family_id');
    }

    /**
     * Determine if a user has parent/guardian privileges in this family.
     */
    public function hasGuardianAccess(int $userId): bool
    {
        if ($this->owner_user_id === $userId) {
            return true;
        }

        return $this->members()
            ->where('user_id', $userId)
            ->whereIn('role', ['parent', 'guardian'])
            ->where('membership_status', 'active')
            ->exists();
    }
}

