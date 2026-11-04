<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class JobPosting extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const LOCATION_ONSITE = 'onsite';

    public const LOCATION_REMOTE = 'remote';

    public const LOCATION_HYBRID = 'hybrid';

    public const WORKPLACE_ONSITE = 'onsite';

    public const WORKPLACE_REMOTE = 'remote';

    public const WORKPLACE_HYBRID = 'hybrid';

    public const TYPE_FULL_TIME = 'full_time';

    public const TYPE_PART_TIME = 'part_time';

    public const TYPE_CONTRACT = 'contract';

    public const TYPE_INTERNSHIP = 'internship';

    public const TYPE_FREELANCE = 'freelance';

    public const TYPE_TEMPORARY = 'temporary';

    protected $table = 'job_postings';

    protected $fillable = [
        'user_id',
        'page_id',
        'company_id',
        'category_id',
        'title',
        'slug',
        'company_name',
        'company_logo',
        'location',
        'country',
        'city',
        'location_type',
        'workplace_type',
        'employment_type',
        'experience_level',
        'salary_min',
        'salary_max',
        'salary_currency',
        'salary_negotiable',
        'salary_visibility',
        'vacancies',
        'visibility',
        'application_method',
        'external_application_url',
        'description',
        'responsibilities',
        'requirements',
        'qualifications',
        'benefits',
        'deadline',
        'published_at',
        'closed_at',
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
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
            'applications_count' => 'integer',
            'views_count' => 'integer',
            'vacancies' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (JobPosting $job): void {
            if (empty($job->slug)) {
                $baseSlug = Str::slug($job->title);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }
                $job->slug = $slug;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'category_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(JobSkill::class, 'job_job_skill', 'job_posting_id', 'job_skill_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ApplicationQuestion::class, 'job_posting_id')->orderBy('display_order');
    }

    public function savedJobs(): HasMany
    {
        return $this->hasMany(SavedJob::class, 'job_id');
    }

    public function jobViews(): HasMany
    {
        return $this->hasMany(JobView::class, 'job_id');
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
        $isSaved = $viewer ? $this->savedJobs()->where('user_id', $viewer->id)->exists() : false;
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
            self::TYPE_TEMPORARY => 'অস্থায়ী (Temporary)',
        ];

        $locationLabels = [
            self::LOCATION_ONSITE => '📍 অনসাইট (অফিস)',
            self::LOCATION_REMOTE => '🌐 রিমোট (বাসা থেকে)',
            self::LOCATION_HYBRID => '🔀 হাইব্রিড',
        ];

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'company_name' => $this->company?->name ?? $this->company_name,
            'company_logo' => $this->company?->logo ?? $this->company_logo,
            'location' => $this->location,
            'country' => $this->country,
            'city' => $this->city,
            'location_type' => $this->location_type,
            'location_type_label' => $locationLabels[$this->location_type] ?? $this->location_type,
            'workplace_type' => $this->workplace_type ?? $this->location_type,
            'employment_type' => $this->employment_type,
            'employment_type_label' => $typeLabels[$this->employment_type] ?? $this->employment_type,
            'experience_level' => $this->experience_level,
            'salary_formatted' => $this->salaryFormatted(),
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'salary_currency' => $this->salary_currency,
            'salary_negotiable' => $this->salary_negotiable,
            'salary_visibility' => $this->salary_visibility ?? 'visible',
            'vacancies' => $this->vacancies ?? 1,
            'visibility' => $this->visibility ?? 'public',
            'application_method' => $this->application_method ?? 'in_app',
            'external_application_url' => $this->external_application_url,
            'description' => $this->description,
            'responsibilities' => $this->responsibilities,
            'requirements' => $this->requirements,
            'qualifications' => $this->qualifications,
            'benefits' => $this->benefits,
            'deadline' => $this->deadline?->format('Y-m-d'),
            'deadline_formatted' => $this->deadline?->locale('bn')->translatedFormat('d F, Y'),
            'published_at' => $this->published_at?->toIso8601String(),
            'is_expired' => $this->isExpired(),
            'status' => $this->status,
            'applications_count' => $this->applications_count,
            'views_count' => $this->views_count,
            'has_applied' => $hasApplied,
            'is_saved' => $isSaved,
            'my_application_status' => $userApplication?->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'creator' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'username' => $this->user?->username,
                'avatar' => $this->user?->avatar_url ?? $this->user?->profile?->avatar_url,
            ],
            'company' => $this->company ? [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'slug' => $this->company->slug,
                'logo' => $this->company->logo,
                'industry' => $this->company->industry,
                'is_verified' => $this->company->isVerified(),
            ] : null,
            'page' => $this->page ? [
                'id' => $this->page->id,
                'name' => $this->page->name,
                'slug' => $this->page->slug,
                'avatar' => $this->page->avatar_url,
                'is_verified' => $this->page->is_verified,
            ] : null,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'skills' => $this->skills->map(fn (JobSkill $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'slug' => $s->slug,
            ])->values()->all(),
            'questions' => $this->questions->map(fn (ApplicationQuestion $q) => [
                'id' => $q->id,
                'question' => $q->question,
                'type' => $q->type,
                'options' => $q->options,
                'is_required' => $q->is_required,
                'display_order' => $q->display_order,
            ])->values()->all(),
        ];
    }
}
