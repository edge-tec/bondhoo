<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LearningCourseLesson extends Model
{
    use HasFactory;

    public const TYPE_VIDEO = 'video';

    public const TYPE_ARTICLE = 'article';

    public const TYPE_QUIZ = 'quiz';

    public const TYPE_ASSIGNMENT = 'assignment';

    public const TYPE_RESOURCE = 'resource';

    protected $fillable = [
        'section_id',
        'course_id',
        'title',
        'slug',
        'type',
        'content',
        'video_url',
        'video_duration_seconds',
        'sort_order',
        'is_preview',
        'is_mandatory',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'video_duration_seconds' => 'integer',
            'sort_order' => 'integer',
            'is_preview' => 'boolean',
            'is_mandatory' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(LearningCourseSection::class, 'section_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LearningLessonAttachment::class, 'lesson_id')->orderBy('sort_order');
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(LearningQuiz::class, 'lesson_id');
    }

    public function assignment(): HasOne
    {
        return $this->hasOne(LearningAssignment::class, 'lesson_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LearningLessonProgress::class, 'lesson_id');
    }

    public function userProgress(?User $user): ?LearningLessonProgress
    {
        if (! $user) {
            return null;
        }

        return $this->progress()->where('user_id', $user->id)->first();
    }
}
