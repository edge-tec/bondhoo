<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplication extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_SHORTLISTED = 'shortlisted';

    public const STATUS_SCREENING = 'screening';

    public const STATUS_INTERVIEW = 'interview';

    public const STATUS_INTERVIEWED = 'interviewed';

    public const STATUS_ASSESSMENT = 'assessment';

    public const STATUS_OFFER = 'offer';

    public const STATUS_HIRED = 'hired';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $table = 'job_applications';

    protected $fillable = [
        'job_posting_id',
        'company_id',
        'user_id',
        'resume_id',
        'full_name',
        'email',
        'phone',
        'cover_letter',
        'answers',
        'resume_url',
        'portfolio_url',
        'expected_salary',
        'status',
        'source',
        'admin_notes',
        'applied_at',
        'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_salary' => 'integer',
            'answers' => 'array',
            'applied_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(CandidateResume::class, 'resume_id');
    }

    public function stageHistory(): HasMany
    {
        return $this->hasMany(ApplicationStageHistory::class, 'application_id')->latest('id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ApplicationNote::class, 'application_id')->latest('id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ApplicationAssignment::class, 'application_id')->latest('id');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'application_id')->latest('id');
    }

    public function statusBadgeInfo(): array
    {
        return match ($this->status) {
            self::STATUS_SHORTLISTED => ['label' => 'শর্টলিস্টেড (Shortlisted)', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
            self::STATUS_SCREENING => ['label' => 'প্রাথমিক যাচাই (Screening)', 'color' => '#0ea5e9', 'bg' => '#e0f2fe'],
            self::STATUS_INTERVIEW, self::STATUS_INTERVIEWED => ['label' => 'সাক্ষাৎকার (Interview)', 'color' => '#7c3aed', 'bg' => '#f3e8ff'],
            self::STATUS_ASSESSMENT => ['label' => 'মূল্যায়ন (Assessment)', 'color' => '#9333ea', 'bg' => '#fae8ff'],
            self::STATUS_OFFER => ['label' => 'চাকরির প্রস্তাব (Offer)', 'color' => '#059669', 'bg' => '#d1fae5'],
            self::STATUS_HIRED => ['label' => 'নির্বাচিত (Hired)', 'color' => '#16a34a', 'bg' => '#dcfce7'],
            self::STATUS_REJECTED => ['label' => 'বাতিলকৃত (Rejected)', 'color' => '#dc2626', 'bg' => '#fee2e2'],
            self::STATUS_WITHDRAWN => ['label' => 'প্রত্যাহারকৃত (Withdrawn)', 'color' => '#6b7280', 'bg' => '#f3f4f6'],
            self::STATUS_UNDER_REVIEW => ['label' => 'পর্যালোচনাধীন (Under Review)', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
            default => ['label' => 'দাখিলকৃত (Applied)', 'color' => '#d97706', 'bg' => '#fef3c7'],
        };
    }

    public function toResponseArray(?User $viewer = null): array
    {
        $badge = $this->statusBadgeInfo();

        $isCompanyMember = false;
        if ($viewer) {
            if ($viewer->hasRole('ADMIN') || $viewer->hasRole('SUPER_ADMIN')) {
                $isCompanyMember = true;
            } elseif ($this->jobPosting && $this->jobPosting->user_id === $viewer->id) {
                $isCompanyMember = true;
            } elseif ($this->company_id) {
                $isCompanyMember = CompanyMember::where('company_id', $this->company_id)
                    ->where('user_id', $viewer->id)
                    ->where('invitation_status', CompanyMember::STATUS_ACCEPTED)
                    ->exists();
            }
        }

        return [
            'id' => $this->id,
            'job_posting_id' => $this->job_posting_id,
            'company_id' => $this->company_id,
            'user_id' => $this->user_id,
            'resume_id' => $this->resume_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'cover_letter' => $this->cover_letter,
            'answers' => $this->answers ?? [],
            'resume_url' => $this->resume_url,
            'resume_title' => $this->resume?->title,
            'portfolio_url' => $this->portfolio_url,
            'expected_salary' => $this->expected_salary,
            'status' => $this->status,
            'status_label' => $badge['label'],
            'status_color' => $badge['color'],
            'status_bg' => $badge['bg'],
            'status_info' => [
                'label_bn' => $badge['label'],
                'color' => $badge['color'],
                'bg_color' => $badge['bg'],
            ],
            // Private recruiter notes only exposed to company members
            'admin_notes' => $isCompanyMember ? $this->admin_notes : null,
            'notes' => $isCompanyMember ? $this->notes->map(fn (ApplicationNote $n) => $n->toResponseArray())->values()->all() : [],
            'stage_history' => $this->stageHistory->map(fn (ApplicationStageHistory $h) => $h->toResponseArray())->values()->all(),
            'interviews' => $this->interviews->map(fn (Interview $i) => $i->toResponseArray($viewer))->values()->all(),
            'source' => $this->source,
            'created_at' => $this->created_at?->toIso8601String(),
            'applied_at' => $this->applied_at?->toIso8601String() ?? $this->created_at?->toIso8601String(),
            'applied_at_human' => ($this->applied_at ?? $this->created_at)?->diffForHumans(),
            'withdrawn_at' => $this->withdrawn_at?->toIso8601String(),
            'job_title' => $this->jobPosting?->title,
            'company_name' => $this->jobPosting?->company?->name ?? $this->jobPosting?->company_name,
            'job_posting' => $this->jobPosting ? [
                'id' => $this->jobPosting->id,
                'title' => $this->jobPosting->title,
                'company_name' => $this->jobPosting->company?->name ?? $this->jobPosting->company_name,
                'location' => $this->jobPosting->location,
                'employment_type' => $this->jobPosting->employment_type,
            ] : null,
            'applicant' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'username' => $this->user?->username,
                'avatar' => $this->user?->avatar_url ?? $this->user?->profile?->avatar_url,
            ],
        ];
    }
}
