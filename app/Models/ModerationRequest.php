<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_QUARANTINED = 'quarantined';

    public const STATUS_MANUAL_REVIEW = 'manual_review';

    public const STATUS_ESCALATED = 'escalated';

    public const ACTION_AUTO_APPROVED = 'auto_approved';

    public const ACTION_AUTO_REJECTED = 'auto_rejected';

    public const ACTION_QUEUED_FOR_REVIEW = 'queued_for_review';

    public const ACTION_QUARANTINED = 'quarantined';

    public const ACTION_ADMIN_APPROVED = 'admin_approved';

    public const ACTION_ADMIN_REJECTED = 'admin_rejected';

    protected $fillable = [
        'uuid',
        'user_id',
        'moderatable_type',
        'moderatable_id',
        'content_type',
        'status',
        'model',
        'flagged',
        'categories',
        'category_scores',
        'action_taken',
        'rejection_reason',
        'error_message',
        'raw_response',
    ];

    protected $casts = [
        'flagged' => 'boolean',
        'categories' => 'array',
        'category_scores' => 'array',
        'raw_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function results(): HasMany
    {
        return $this->hasMany(ModerationResult::class);
    }
}
