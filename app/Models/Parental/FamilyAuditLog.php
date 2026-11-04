<?php

declare(strict_types=1);

namespace App\Models\Parental;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class FamilyAuditLog
 *
 * Immutable append-only audit trail for all parental modifications, pairing, and commands.
 */
class FamilyAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'family_audit_logs';

    protected $fillable = [
        'family_id',
        'actor_user_id',
        'actor_role',
        'action',
        'resource_type',
        'resource_id',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Family, FamilyAuditLog>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * @return BelongsTo<User, FamilyAuditLog>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}

