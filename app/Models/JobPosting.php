<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_DRAFT = 'draft';

    public const LOCATION_ONSITE = 'onsite';

    public const LOCATION_REMOTE = 'remote';

    public const LOCATION_HYBRID = 'hybrid';

    public const TYPE_FULL_TIME = 'full_time';

    public const TYPE_PART_TIME = 'part_time';

    public const TYPE_CONTRACT = 'contract';

    public const TYPE_INTERNSHIP = 'internship';

    public const TYPE_FREELANCE = 'freelance';

    protected $table = 'job_postings';

    protected $fillable = [
        'user_id',
        'page_id',
        'title',
        'company_name',
        'company_logo',
        'location',
        'location_type',
        'employment_type',
        'experience_level',
        'salary_min',
        'salary_max',
        'salary_currency',
        'salary_negotiable',
        'description',
        'requirements',
        'benefits',
        'deadline',
        'status',
        'applications_count',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'salary_negotiable' => 'boolean',
            'deadline' => 'date',
            'applications_count' => 'integer',
            'views_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function isExpired(): bool
    {
        return $this->deadline && $this->deadline->isPast();
    }

    public function hasApplied(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return $this->applications()->where('user_id', $userId)->exists();
    }

    public function salaryFormatted(): string
    {
        if ($this->salary_negotiable && ! $this->salary_min && ! $this->salary_max) {
            return 'আলোচনা সাপেক্ষে (Negotiable)';
        }

        if ($this->salary_min && $this->salary_max) {
            return number_format($this->salary_min).' - '.number_format($this->salary_max).' '.$this->salary_currency;
        }

        if ($this->salary_min) {
            return 'থেকে '.number_format($this->salary_min).' '.$this->salary_currency;
        }

        if ($this->salary_max) {
            return 'সর্বোচ্চ '.number_format($this->salary_max).' '.$this->salary_currency;
        }

        return 'আলোচনা সাপেক্ষে';
    }

    public function toResponseArray(?User $viewer = null): array
    {
        $hasApplied = $viewer ? $this->hasApplied($viewer->id) : false;
        $userApplication = null;

        if ($viewer && $hasApplied) {
            $userApplication = $this->applications()
                ->where('user_id', $viewer->id)
                ->first();
        }

        $typeLabels = [
            self::TYPE_FULL_TIME => 'ফুল-টাইম (Full-time)',
            self::TYPE_PART_TIME => 'পার্ট-টাইম (Part-time)',
            self::TYPE_CONTRACT => 'চুক্তিভিত্তিক (Contract)',
            self::TYPE_INTERNSHIP => 'ইন্টার্নশিপ (Internship)',
            self::TYPE_FREELANCE => 'ফ্রিল্যান্স (Freelance)',
        ];

        $locationLabels = [
            self::LOCATION_ONSITE => '📍 অনসাইট (অফিস)',
            self::LOCATION_REMOTE => '🌐 রিমোট (বাসা থেকে)',
            self::LOCATION_HYBRID => '🔀 হাইব্রিড',
        ];

        return [
            'id' => $this->id,
            'title' => $this->title,
            'company_name' => $this->company_name,
            'company_logo' => $this->company_logo,
            'location' => $this->location,
            'location_type' => $this->location_type,
            'location_type_label' => $locationLabels[$this->location_type] ?? $this->location_type,
            'employment_type' => $this->employment_type,
            'employment_type_label' => $typeLabels[$this->employment_type] ?? $this->employment_type,
            'experience_level' => $this->experience_level,
            'salary_formatted' => $this->salaryFormatted(),
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'salary_currency' => $this->salary_currency,
            'salary_negotiable' => $this->salary_negotiable,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'benefits' => $this->benefits,
            'deadline' => $this->deadline?->format('Y-m-d'),
            'deadline_formatted' => $this->deadline?->locale('bn')->translatedFormat('d F, Y'),
            'is_expired' => $this->isExpired(),
            'status' => $this->status,
            'applications_count' => $this->applications_count,
            'views_count' => $this->views_count,
            'has_applied' => $hasApplied,
            'my_application_status' => $userApplication?->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'creator' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'username' => $this->user?->username,
                'avatar' => $this->user?->avatar_url ?? $this->user?->profile?->avatar_url,
            ],
            'page' => $this->page ? [
                'id' => $this->page->id,
                'name' => $this->page->name,
                'slug' => $this->page->slug,
                'avatar' => $this->page->avatar_url,
                'is_verified' => $this->page->is_verified,
            ] : null,
        ];
    }
}
