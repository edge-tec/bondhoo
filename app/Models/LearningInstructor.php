<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningInstructor extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'headline',
        'biography',
        'expertise',
        'website_url',
        'linkedin_url',
        'github_url',
        'status',
        'is_verified',
        'commission_rate',
        'total_students',
        'total_courses',
        'rating_avg',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'expertise' => 'array',
            'is_verified' => 'boolean',
            'commission_rate' => 'decimal:2',
            'total_students' => 'integer',
            'total_courses' => 'integer',
            'rating_avg' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(LearningCourse::class, 'instructor_id', 'user_id');
    }
}
