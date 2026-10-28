<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomatedResponseAction extends Model
{
    use HasFactory;

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_EXECUTED = 'executed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ROLLED_BACK = 'rolled_back';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'action_uid',
        'idempotency_key',
        'detection_id',
        'security_event_id',
        'action_type',
        'risk_level',
        'target_type',
        'target_id',
        'target_identifier',
        'payload',
        'requires_approval',
        'requires_human_approval',
        'status',
        'requested_by',
        'approved_by',
        'rejected_by',
        'justification',
        'approval_notes',
        'rollback_payload',
        'executed_at',
        'rolled_back_at',
        'retry_count',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'rollback_payload' => 'array',
            'requires_approval' => 'boolean',
            'executed_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AutomatedResponseAction $action) {
            if ($action->target_id !== null && empty($action->attributes['target_identifier'])) {
                $action->attributes['target_identifier'] = (string) $action->target_id;
            } elseif (! empty($action->attributes['target_identifier']) && $action->target_id === null && is_numeric($action->attributes['target_identifier'])) {
                $action->target_id = (int) $action->attributes['target_identifier'];
            }

            if ($action->requires_approval !== null) {
                $action->attributes['requires_human_approval'] = (bool) $action->requires_approval;
            } elseif (isset($action->attributes['requires_human_approval'])) {
                $action->requires_approval = (bool) $action->attributes['requires_human_approval'];
            }
        });
    }

    public function getRequiresHumanApprovalAttribute(): bool
    {
        return (bool) ($this->attributes['requires_human_approval'] ?? $this->requires_approval);
    }

    public function setRequiresHumanApprovalAttribute(bool $value): void
    {
        $this->attributes['requires_human_approval'] = $value;
        $this->attributes['requires_approval'] = $value;
    }

    public function getTargetIdentifierAttribute(): ?string
    {
        return $this->attributes['target_identifier'] ?? ($this->target_id !== null ? (string) $this->target_id : null);
    }

    public function setTargetIdentifierAttribute(?string $value): void
    {
        $this->attributes['target_identifier'] = $value;
        $this->attributes['target_id'] = $value !== null && is_numeric($value) ? (int) $value : null;
    }

    public function getExecutionResultAttribute(): array
    {
        return [
            'reason' => $this->error_message ? (str_contains($this->error_message, 'সুপার অ্যাডমিন') || str_contains($this->error_message, 'CANNOT_DISABLE_LAST_SUPER_ADMIN') ? 'CANNOT_DISABLE_LAST_SUPER_ADMIN' : $this->error_message) : null,
        ];
    }

    public function setExecutionResultAttribute(array $value): void
    {
        if (isset($value['reason'])) {
            $this->attributes['error_message'] = (string) $value['reason'];
        }
    }

    public function detection(): BelongsTo
    {
        return $this->belongsTo(ThreatDetection::class, 'detection_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
