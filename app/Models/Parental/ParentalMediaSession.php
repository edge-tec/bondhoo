<?php

declare(strict_types=1);

namespace App\Models\Parental;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ParentalMediaSession
 *
 * Real-time supervisory audio/media session via LiveKit, requiring explicit child consent.
 */
class ParentalMediaSession extends Model
{
    use HasFactory;

    protected $table = 'parental_media_sessions';

    protected $fillable = [
        'family_id',
        'child_device_id',
        'initiated_by',
        'session_type',
        'livekit_room_id',
        'session_status',
        'child_consent_at',
        'parent_consent_at',
        'started_at',
        'ended_at',
        'termination_reason',
    ];

    protected $casts = [
        'child_consent_at' => 'datetime',
        'parent_consent_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Family, ParentalMediaSession>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * @return BelongsTo<ChildDevice, ParentalMediaSession>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(ChildDevice::class, 'child_device_id');
    }

    /**
     * @return BelongsTo<User, ParentalMediaSession>
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
