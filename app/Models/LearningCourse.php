<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningCourse extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_ARCHIVED = 'archived';

    public const TYPE_FREE = 'free';

    public const TYPE_PAID = 'paid';

    public const TYPE_PRIVATE = 'private';

    public const TYPE_ORGANIZATION = 'organization';

    protected $fillable = [
        'instructor_id',
        'category_id',
        'title',
        'slug',
        'subtitle',
        'description',
        'level',
        'language',
        'thumbnail_url',
        'promotional_video_url',
        'type',
        'price',
        'discounted_price',
        'currency',
        'status',
        'learning_objectives',
        'prerequisites',
        'target_audience',
        'duration_minutes',
        'total_sections',
        'total_lessons',
        'enrollments_count',
        'rating_avg',
        'rating_count',
        'is_featured',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'instructor_id' => 'integer',
            'category_id' => 'integer',
            'learning_objectives' => 'array',
            'prerequisites' => 'array',
            'target_audience' => 'array',
            'price' => 'decimal:2',
            'discounted_price' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
            'duration_minutes' => 'integer',
            'total_sections' => 'integer',
            'total_lessons' => 'integer',
            'enrollments_count' => 'integer',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class, 'category_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(LearningCategory::class, 'learning_course_categories', 'course_id', 'category_id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(LearningCourseSkill::class, 'course_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(LearningCourseSection::class, 'course_id')->orderBy('sort_order');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(LearningCourseLesson::class, 'course_id')->orderBy('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(LearningEnrollment::class, 'course_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(LearningCourseReview::class, 'course_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(LearningCertificate::class, 'course_id');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(LearningDiscussion::class, 'course_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(LearningCourseAnnouncement::class, 'course_id');
    }

    public function isEnrolledByUser(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->enrollments()
            ->where('user_id', $user->id)
            ->whereIn('status', [LearningEnrollment::STATUS_ACTIVE, LearningEnrollment::STATUS_COMPLETED])
            ->exists();
    }
}
