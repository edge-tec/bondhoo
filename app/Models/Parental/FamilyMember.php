<?php

declare(strict_types=1);

namespace App\Models\Parental;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class FamilyMember
 *
 * Represents an individual parent, guardian, or child within a Family.
 */
class FamilyMember extends Model
{
    use HasFactory;

    protected $table = 'family_members';

    protected $fillable = [
        'family_id',
        'user_id',
        'name',
        'role',
        'membership_status',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Family, FamilyMember>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * @return BelongsTo<User, FamilyMember>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<ChildDevice>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(ChildDevice::class, 'child_member_id');
    }

    /**
     * @return HasMany<ScreenTimeRecord>
     */
    public function screenTimeRecords(): HasMany
    {
        return $this->hasMany(ScreenTimeRecord::class, 'child_member_id');
    }

    /**
     * @return HasMany<DeviceRestriction>
     */
    public function restrictions(): HasMany
    {
        return $this->hasMany(DeviceRestriction::class, 'child_member_id');
    }

    /**
     * @return HasMany<ConsentRequest>
     */
    public function consentRequests(): HasMany
    {
        return $this->hasMany(ConsentRequest::class, 'child_member_id');
    }
}
