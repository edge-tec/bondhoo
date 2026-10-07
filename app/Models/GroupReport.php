<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupReport extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWING = 'reviewing';

    public const STATUS_ACTION_TAKEN = 'action_taken';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'group_id',
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason_category',
        'description',
        'evidence',
        'status',
        'reviewed_by',
        'decision_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'reporter' => [
                'id' => $this->reporter?->id,
                'name' => $this->reporter?->name,
                'username' => $this->reporter?->username,
            ],
            'reportable_type' => $this->reportable_type,
            'reportable_id' => $this->reportable_id,
            'reason_category' => $this->reason_category,
            'description' => $this->description,
            'evidence' => $this->evidence,
            'status' => $this->status,
            'reviewer' => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null,
            'decision_note' => $this->decision_note,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
