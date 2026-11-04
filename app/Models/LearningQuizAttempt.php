<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningQuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'user_id',
        'attempt_number',
        'score',
        'total_points',
        'earned_points',
        'passed',
        'started_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'score' => 'decimal:2',
            'total_points' => 'integer',
            'earned_points' => 'integer',
            'passed' => 'boolean',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(LearningQuiz::class, 'quiz_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(LearningQuizAnswer::class, 'attempt_id');
    }
}
