<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_ACTIONED = 'actioned';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITY_CRITICAL = 'critical';

    public const CATEGORY_SPAM = 'spam';

    public const CATEGORY_SCAM = 'scam';

    public const CATEGORY_HARASSMENT = 'harassment';

    public const CATEGORY_VIOLENCE = 'violence';

    public const CATEGORY_HATE_SPEECH = 'hate_speech';

    public const CATEGORY_IMPERSONATION = 'impersonation';

    public const CATEGORY_MALICIOUS_LINK = 'malicious_link';

    public const CATEGORY_GENERAL = 'general';

    public const APPEAL_NONE = 'none';

    public const APPEAL_REQUESTED = 'requested';

    public const APPEAL_UNDER_REVIEW = 'under_review';

    public const APPEAL_UPHELD = 'upheld';

    public const APPEAL_OVERTURNED = 'overturned';

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'details',
        'status',
        'category',
        'duplicate_of_id',
        'is_false_positive',
        'ai_flagged',
        'ai_confidence',
        'ai_reason',
        'ai_evidence',
        'priority',
        'assigned_to',
        'appeal_status',
        'appeal_notes',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'ai_flagged' => 'boolean',
            'ai_confidence' => 'float',
            'ai_evidence' => 'array',
            'is_false_positive' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parentReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'duplicate_of_id');
    }

    public function duplicateReports(): HasMany
    {
        return $this->hasMany(Report::class, 'duplicate_of_id');
    }
}
