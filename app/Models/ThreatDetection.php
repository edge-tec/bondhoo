<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreatDetection extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_INVESTIGATING = 'investigating';

    public const STATUS_SUPPRESSED = 'suppressed';

    public const STATUS_FALSE_POSITIVE = 'false_positive';

    public const STATUS_MITIGATED = 'mitigated';

    protected $fillable = [
        'detection_uid',
        'correlation_id',
        'dedup_key',
        'rule_code',
        'rule_version',
        'data_source',
        'severity',
        'target_user_id',
        'ip_address',
        'observed_count',
        'occurrence_count',
        'threshold_count',
        'window_minutes',
        'confidence_level',
        'evidence_refs',
        'limitations',
        'status',
        'security_event_id',
        'evaluated_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (ThreatDetection $detection) {
            if ($detection->observed_count !== null && empty($detection->occurrence_count)) {
                $detection->occurrence_count = $detection->observed_count;
            } elseif ($detection->occurrence_count !== null && empty($detection->observed_count)) {
                $detection->observed_count = $detection->occurrence_count;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'rule_version' => 'integer',
            'observed_count' => 'integer',
            'occurrence_count' => 'integer',
            'threshold_count' => 'integer',
            'window_minutes' => 'integer',
            'evidence_refs' => 'array',
            'evaluated_at' => 'datetime',
        ];
    }

    public function getOccurrenceCountAttribute(): int
    {
        return (int) ($this->attributes['occurrence_count'] ?? $this->attributes['observed_count'] ?? 1);
    }

    public function setOccurrenceCountAttribute(int $value): void
    {
        $this->attributes['occurrence_count'] = $value;
        $this->attributes['observed_count'] = $value;
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function securityEvent(): BelongsTo
    {
        return $this->belongsTo(SecurityEvent::class, 'security_event_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ThreatDetectionRule::class, 'rule_code', 'rule_code');
    }
}
