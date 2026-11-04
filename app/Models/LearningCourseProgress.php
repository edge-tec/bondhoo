<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningCourseProgress extends Model
{
    use HasFactory;

    protected $table = 'learning_course_progress';

    protected $fillable = [
        'enrollment_id',
        'course_id',
        'user_id',
        'completed_lessons_count',
        'total_lessons_count',
        'percentage',
        'is_completed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_lessons_count' => 'integer',
            'total_lessons_count' => 'integer',
            'percentage' => 'decimal:2',
            'is_completed' => 'boolean',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
