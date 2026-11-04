<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningCourseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'category_id',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class, 'category_id');
    }
}
