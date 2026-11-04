<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'course_id',
        'title',
        'instructions',
        'max_score',
        'passing_score',
        'due_days_after_enrollment',
        'allow_resubmissions',
    ];

    protected function casts(): array
    {
        return [
            'max_score' => 'integer',
            'passing_score' => 'integer',
            'due_days_after_enrollment' => 'integer',
            'allow_resubmissions' => 'boolean',
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

    public function submissions(): HasMany
    {
        return $this->hasMany(LearningAssignmentSubmission::class, 'assignment_id');
    }
}
