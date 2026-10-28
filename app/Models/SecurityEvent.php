<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SecurityEvent — সিকিউরিটি অপারেশন্স সেন্টার (SOC) ইভেন্ট ও ইনসিডেন্ট লাইফসাইকেল মডেল
 */
class SecurityEvent extends Model
{
    use HasFactory;

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    public const STATUS_OPEN = 'open';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_INVESTIGATING = 'investigating';

    public const STATUS_ESCALATED = 'escalated';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_REOPENED = 'reopened';

    protected $fillable = [
        'user_id',
        'event_type',
        'severity',
        'ip_address',
        'country_code',
        'user_agent',
        'details',
        'evidence_refs',
        'timeline',
        'resolved',
        'status',
        'assigned_to',
        'internal_notes',
        'resolved_by',
        'resolved_at',
        'acknowledged_at',
        'acknowledged_by',
        'escalated_at',
        'reopened_at',
        'reopened_by',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'evidence_refs' => 'array',
            'timeline' => 'array',
            'resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'escalated_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    public function appendTimelineEvent(string $action, ?int $actorId, ?string $note = null, array $extra = []): void
    {
        $entries = is_array($this->timeline) ? $this->timeline : [];
        $entries[] = array_filter([
            'action' => $action,
            'actor_id' => $actorId,
            'note' => $note,
            'meta' => ! empty($extra) ? $extra : null,
            'timestamp' => now()->toIso8601String(),
        ], fn ($v) => $v !== null);

        $this->timeline = $entries;
    }

    public function markResolved(int $adminId, ?string $notes = null): static
    {
        $this->resolved = true;
        $this->status = self::STATUS_RESOLVED;
        $this->resolved_by = $adminId;
        $this->resolved_at = now();
        if ($notes) {
            $this->internal_notes = $notes;
        }
        $this->appendTimelineEvent('resolved', $adminId, $notes);
        $this->save();

        return $this;
    }
}
