<?php

declare(strict_types=1);

namespace App\Models\Parental;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class RemoteCommand
 *
 * Represents an actionable instruction delivered to a child device (lock, sync, alarm, wipe).
 */
class RemoteCommand extends Model
{
    use HasFactory;

    protected $table = 'remote_commands';

    protected $fillable = [
        'family_id',
        'child_device_id',
        'command_uuid',
        'command_type',
        'payload',
        'command_status',
        'requested_by',
        'issued_at',
        'expires_at',
        'acknowledged_at',
        'completed_at',
        'failure_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Family, RemoteCommand>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * @return BelongsTo<ChildDevice, RemoteCommand>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(ChildDevice::class, 'child_device_id');
    }

    /**
     * @return BelongsTo<User, RemoteCommand>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
