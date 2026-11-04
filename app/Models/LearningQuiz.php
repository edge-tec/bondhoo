<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'course_id',
        'title',
        'instructions',
        'time_limit_minutes',
        'passing_score',
        'max_attempts',
        'shuffle_questions',
    ];

    protected function casts(): array
    {
        return [
            'time_limit_minutes' => 'integer',
            'passing_score' => 'integer',
            'max_attempts' => 'integer',
            'shuffle_questions' => 'boolean',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LearningCourseLesson::class, 'lesson_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(LearningQuizQuestion::class, 'quiz_id')->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(LearningQuizAttempt::class, 'quiz_id');
    }
}
