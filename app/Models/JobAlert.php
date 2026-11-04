<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'keywords',
        'category_id',
        'locations',
        'employment_types',
        'workplace_types',
        'experience_level',
        'frequency',
        'is_active',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'locations' => 'array',
            'employment_types' => 'array',
            'workplace_types' => 'array',
            'is_active' => 'boolean',
            'last_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'category_id');
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'keywords' => $this->keywords,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'locations' => $this->locations ?? [],
            'employment_types' => $this->employment_types ?? [],
            'workplace_types' => $this->workplace_types ?? [],
            'experience_level' => $this->experience_level,
            'frequency' => $this->frequency,
            'is_active' => $this->is_active,
            'last_notified_at' => $this->last_notified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
