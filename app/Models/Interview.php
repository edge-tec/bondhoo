<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    use HasFactory;

    public const TYPE_VIDEO = 'video';

    public const TYPE_PHONE = 'phone';

    public const TYPE_IN_PERSON = 'in_person';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_RESCHEDULED = 'rescheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    protected $fillable = [
        'application_id',
        'company_id',
        'scheduled_by',
        'interview_type',
        'scheduled_at',
        'duration_minutes',
        'timezone',
        'meeting_url',
        'location',
        'instructions',
        'status',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function toResponseArray(?User $viewer = null): array
    {
        $isCompanyMember = false;
        if ($viewer) {
            if ($viewer->hasRole('ADMIN') || $viewer->hasRole('SUPER_ADMIN')) {
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
            'application_id' => $this->application_id,
            'company_id' => $this->company_id,
            'company_name' => $this->company?->name,
            'interview_type' => $this->interview_type,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'scheduled_at_formatted' => $this->scheduled_at?->format('d M Y, h:i A'),
            'duration_minutes' => $this->duration_minutes,
            'timezone' => $this->timezone,
            'meeting_url' => $this->meeting_url,
            'location' => $this->location,
            'instructions' => $this->instructions,
            'status' => $this->status,
            'feedback' => $isCompanyMember ? $this->feedback : null,
            'job_title' => $this->application?->jobPosting?->title,
            'candidate_name' => $this->application?->full_name,
            'scheduled_by' => [
                'id' => $this->scheduler?->id,
                'name' => $this->scheduler?->name,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
