<?php

declare(strict_types=1);

namespace App\Models\Parental;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ConsentRequest
 *
 * Child-initiated permission escalation request (e.g., extra screen-time, unblock app).
 */
class ConsentRequest extends Model
{
    use HasFactory;

    protected $table = 'consent_requests';

    protected $fillable = [
        'family_id',
        'child_member_id',
        'child_device_id',
        'request_type',
        'request_reason',
        'requested_value',
        'request_status',
        'parent_response',
        'responded_by',
        'responded_at',
        'expires_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Family, ConsentRequest>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * @return BelongsTo<FamilyMember, ConsentRequest>
     */
    public function childMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'child_member_id');
    }

    /**
     * @return BelongsTo<ChildDevice, ConsentRequest>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(ChildDevice::class, 'child_device_id');
    }

    /**
     * @return BelongsTo<User, ConsentRequest>
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
