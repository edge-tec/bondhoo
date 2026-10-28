<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceControl extends Model
{
    use HasFactory;

    public const EVIDENCE_VERIFIED = 'verified';

    public const EVIDENCE_AUTO_COLLECTED = 'auto_collected';

    public const EVIDENCE_MANUAL_SUPPLIED = 'manual_supplied';

    public const EVIDENCE_EXPIRED = 'expired';

    public const EVIDENCE_MISSING = 'missing';

    public const EVIDENCE_UNAVAILABLE = 'unavailable';

    public const OUTCOME_EFFECTIVE = 'effective';

    public const OUTCOME_NEEDS_IMPROVEMENT = 'needs_improvement';

    public const OUTCOME_INEFFECTIVE = 'ineffective';

    public const OUTCOME_NOT_ASSESSED = 'not_assessed';

    protected $fillable = [
        'control_id',
        'title',
        'framework_mappings',
        'owner_team',
        'scope',
        'evidence_status',
        'assessment_outcome',
        'evidence_refs',
        'evidence_hash',
        'freshness_ttl_hours',
        'last_collected_at',
        'last_assessed_at',
        'remediation_task',
        'remediation_due_at',
        'risk_acceptance_reason',
        'risk_acceptance_expires_at',
        'approved_by',
        'last_reviewed_by',
        'review_history',
    ];

    protected $appends = [
        'last_reviewed_by',
        'framework',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'framework_mappings' => 'array',
            'evidence_refs' => 'array',
            'review_history' => 'array',
            'freshness_ttl_hours' => 'integer',
            'last_collected_at' => 'datetime',
            'last_assessed_at' => 'datetime',
            'remediation_due_at' => 'datetime',
            'risk_acceptance_expires_at' => 'datetime',
        ];
    }

    public function getLastReviewedByAttribute(): ?int
    {
        return $this->approved_by ? (int) $this->approved_by : null;
    }

    public function setLastReviewedByAttribute(?int $value): void
    {
        $this->attributes['approved_by'] = $value;
    }

    public function getFrameworkAttribute(): string
    {
        $mappings = is_array($this->framework_mappings) ? $this->framework_mappings : [];
        $keys = array_keys($mappings);

        return ! empty($keys) ? strtoupper($keys[0]) : 'ISO27001';
    }

    public function getStatusAttribute(): string
    {
        return in_array($this->assessment_outcome, ['effective', 'compliant'], true) ? 'compliant' : 'non_compliant';
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
