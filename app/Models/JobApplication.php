<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplication extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SHORTLISTED = 'shortlisted';

    public const STATUS_INTERVIEWED = 'interviewed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_HIRED = 'hired';

    protected $table = 'job_applications';

    protected $fillable = [
        'job_posting_id',
        'user_id',
        'full_name',
        'email',
        'phone',
        'cover_letter',
        'resume_url',
        'portfolio_url',
        'expected_salary',
        'status',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_salary' => 'integer',
        ];
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusBadgeInfo(): array
    {
        return match ($this->status) {
            self::STATUS_SHORTLISTED => ['label' => 'শর্টলিস্টেড', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
            self::STATUS_INTERVIEWED => ['label' => 'সাক্ষাৎকার আহ্বান', 'color' => '#7c3aed', 'bg' => '#f3e8ff'],
            self::STATUS_HIRED => ['label' => 'নির্বাচিত (Hired)', 'color' => '#16a34a', 'bg' => '#dcfce7'],
            self::STATUS_REJECTED => ['label' => 'বাতিলকৃত', 'color' => '#dc2626', 'bg' => '#fee2e2'],
            default => ['label' => 'পর্যালোচনাধীন (Pending)', 'color' => '#d97706', 'bg' => '#fef3c7'],
        };
    }

    public function toResponseArray(): array
    {
        $badge = $this->statusBadgeInfo();

        return [
            'id' => $this->id,
            'job_posting_id' => $this->job_posting_id,
            'user_id' => $this->user_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'cover_letter' => $this->cover_letter,
            'resume_url' => $this->resume_url,
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
            'admin_notes' => $this->admin_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'applied_at' => $this->created_at?->toIso8601String(),
            'applied_at_human' => $this->created_at?->diffForHumans(),
            'job_title' => $this->jobPosting?->title,
            'company_name' => $this->jobPosting?->company_name,
            'job_posting' => $this->jobPosting ? [
                'id' => $this->jobPosting->id,
                'title' => $this->jobPosting->title,
                'company_name' => $this->jobPosting->company_name,
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
