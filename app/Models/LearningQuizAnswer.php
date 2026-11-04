<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningQuizAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_option_ids',
        'text_answer',
        'is_correct',
        'points_awarded',
    ];

    protected function casts(): array
    {
        return [
            'selected_option_ids' => 'array',
            'is_correct' => 'boolean',
            'points_awarded' => 'integer',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(LearningQuizAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(LearningQuizQuestion::class, 'question_id');
    }
}
