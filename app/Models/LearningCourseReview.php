<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningCourseReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'rating',
        'content',
        'instructor_reply',
        'replied_at',
        'helpful_votes',
        'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'helpful_votes' => 'integer',
            'is_approved' => 'boolean',
            'replied_at' => 'datetime',
        ];
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
