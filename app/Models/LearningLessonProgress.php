<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningLessonProgress extends Model
{
    use HasFactory;

    protected $table = 'learning_lesson_progress';

    protected $fillable = [
        'enrollment_id',
        'course_id',
        'lesson_id',
        'user_id',
        'is_completed',
        'watch_time_seconds',
        'last_position_seconds',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'watch_time_seconds' => 'integer',
            'last_position_seconds' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(LearningEnrollment::class, 'enrollment_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LearningCourseLesson::class, 'lesson_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
