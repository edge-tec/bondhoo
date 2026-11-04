<?php

declare(strict_types=1);

namespace App\Models\Parental;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class DevicePairingCode
 *
 * Represents a single-use 6-digit cryptographic pairing code with a strict 10-minute TTL.
 */
class DevicePairingCode extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'device_pairing_codes';

    protected $fillable = [
        'family_id',
        'child_member_id',
        'code_hash',
        'expires_at',
        'used_at',
        'failed_attempts',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Family, DevicePairingCode>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * @return BelongsTo<FamilyMember, DevicePairingCode>
     */
    public function childMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'child_member_id');
    }

    /**
     * @return BelongsTo<User, DevicePairingCode>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine if the pairing code is currently valid.
     */
    public function isValid(): bool
    {
        return $this->used_at === null
            && $this->failed_attempts < 5
            && $this->expires_at->isFuture();
    }
}

