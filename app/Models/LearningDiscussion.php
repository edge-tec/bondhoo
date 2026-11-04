<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningDiscussion extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'lesson_id',
        'user_id',
        'title',
        'content',
        'replies_count',
        'is_pinned',
        'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'replies_count' => 'integer',
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
        ];
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

    public function replies(): HasMany
    {
        return $this->hasMany(LearningDiscussionReply::class, 'discussion_id');
    }
}
