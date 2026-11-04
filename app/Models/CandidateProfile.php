<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProfile extends Model
{
    use HasFactory;

    public const AVAILABILITY_LOOKING = 'actively_looking';

    public const AVAILABILITY_OPEN = 'open_to_work';

    public const AVAILABILITY_NOT_LOOKING = 'not_looking';

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_RECRUITERS_ONLY = 'recruiters_only';

    public const VISIBILITY_PRIVATE = 'private';

    protected $fillable = [
        'user_id',
        'professional_title',
        'professional_summary',
        'experience_years',
        'current_company',
        'current_position',
        'expected_salary',
        'salary_currency',
        'preferred_employment_types',
        'preferred_workplace_types',
        'preferred_locations',
        'availability_status',
        'profile_visibility',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'expected_salary' => 'integer',
            'preferred_employment_types' => 'array',
            'preferred_workplace_types' => 'array',
            'preferred_locations' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toResponseArray(?User $viewer = null): array
    {
        $isOwner = $viewer && $viewer->id === $this->user_id;
        $isRecruiter = $viewer && CompanyMember::where('user_id', $viewer->id)
            ->whereIn('role', [CompanyMember::ROLE_OWNER, CompanyMember::ROLE_ADMIN, CompanyMember::ROLE_RECRUITER, CompanyMember::ROLE_HIRING_MANAGER])
            ->where('invitation_status', CompanyMember::STATUS_ACCEPTED)
            ->exists();

        // Privacy gate
        $hideSalaryAndContact = ! $isOwner && ! $isRecruiter && $this->profile_visibility === self::VISIBILITY_RECRUITERS_ONLY;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'professional_title' => $this->professional_title,
            'professional_summary' => $this->professional_summary,
            'experience_years' => $this->experience_years,
            'current_company' => $this->current_company,
            'current_position' => $this->current_position,
            'expected_salary' => $hideSalaryAndContact ? null : $this->expected_salary,
            'salary_currency' => $this->salary_currency,
            'preferred_employment_types' => $this->preferred_employment_types ?? [],
            'preferred_workplace_types' => $this->preferred_workplace_types ?? [],
            'preferred_locations' => $this->preferred_locations ?? [],
            'availability_status' => $this->availability_status,
            'profile_visibility' => $this->profile_visibility,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $hideSalaryAndContact ? null : $this->user?->email,
                'avatar' => $this->user?->avatar_url ?? $this->user?->profile?->avatar_url,
                'headline' => $this->user?->profile?->headline,
                'location' => $this->user?->profile?->location,
            ],
            'experiences' => $this->user?->experiences ? $this->user->experiences->map(fn (ProfileExperience $e) => [
                'id' => $e->id,
                'company_name' => $e->company_name,
                'job_title' => $e->job_title,
                'employment_type' => $e->employment_type,
                'location' => $e->location,
                'start_date' => $e->start_date?->format('Y-m-d'),
                'end_date' => $e->end_date?->format('Y-m-d'),
                'is_current' => $e->is_current,
                'description' => $e->description,
            ])->values()->all() : [],
            'education' => $this->user?->educations ? $this->user->educations->map(fn (ProfileEducation $ed) => [
                'id' => $ed->id,
                'institution_name' => $ed->institution_name,
                'degree' => $ed->degree,
                'field_of_study' => $ed->field_of_study,
                'start_date' => $ed->start_date?->format('Y-m-d'),
                'end_date' => $ed->end_date?->format('Y-m-d'),
                'grade' => $ed->grade,
                'description' => $ed->description,
            ])->values()->all() : [],
            'skills' => $this->user?->skills ? $this->user->skills->map(fn (ProfileSkill $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'level' => $s->level,
            ])->values()->all() : [],
        ];
    }
}
