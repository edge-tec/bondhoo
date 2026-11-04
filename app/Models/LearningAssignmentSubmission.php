<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningAssignmentSubmission extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_GRADED = 'graded';

    public const STATUS_RESUBMISSION_REQUESTED = 'resubmission_requested';

    protected $fillable = [
        'assignment_id',
        'user_id',
        'submission_text',
        'file_url',
        'score',
        'feedback',
        'status',
        'graded_by',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'graded_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(LearningAssignment::class, 'assignment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
