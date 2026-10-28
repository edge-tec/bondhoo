<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZeroTrustAccessLog extends Model
{
    public const DECISION_ALLOW = 'allow';

    public const DECISION_DENY = 'deny';

    public const DECISION_STEP_UP_REQUIRED = 'step_up_required';

    public const DECISION_APPROVAL_REQUIRED = 'approval_required';

    protected $fillable = [
        'evaluation_uid',
        'user_id',
        'policy_code',
        'policy_version',
        'operation',
        'resource_type',
        'resource_id',
        'decision',
        'risk_score',
        'signals',
        'reason_code',
        'reason_message',
        'ip_address',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'policy_version' => 'integer',
            'risk_score' => 'integer',
            'signals' => 'array',
            'evaluated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
