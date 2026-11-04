<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningLessonAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'title',
        'file_url',
        'file_type',
        'file_size',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LearningCourseLesson::class, 'lesson_id');
    }
}
