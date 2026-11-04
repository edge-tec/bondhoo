<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningCourseSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(LearningCourseLesson::class, 'section_id')->orderBy('sort_order');
    }
}
