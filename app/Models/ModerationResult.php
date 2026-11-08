<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationResult extends Model
{
    use HasFactory;

    public const DECISION_APPROVE = 'approve';

    public const DECISION_REJECT = 'reject';

    public const DECISION_MANUAL_REVIEW = 'manual_review';

    public const DECISION_QUARANTINE = 'quarantine';

    protected $fillable = [
        'moderation_request_id',
        'target_type',
        'target_id',
        'flagged',
        'categories',
        'category_scores',
        'policy_decision',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
    ];

    protected $casts = [
        'flagged' => 'boolean',
        'categories' => 'array',
        'category_scores' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ModerationRequest::class, 'moderation_request_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
