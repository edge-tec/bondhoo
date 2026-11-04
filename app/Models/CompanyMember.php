<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyMember extends Model
{
    use HasFactory;

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_RECRUITER = 'recruiter';

    public const ROLE_HIRING_MANAGER = 'hiring_manager';

    public const ROLE_VIEWER = 'viewer';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'company_id',
        'user_id',
        'role',
        'permissions',
        'invitation_status',
        'invited_by',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'joined_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function canManageJobs(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_RECRUITER], true);
    }

    public function canManageApplicants(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_RECRUITER, self::ROLE_HIRING_MANAGER], true);
    }

    public function canManageTeam(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN], true);
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'user_id' => $this->user_id,
            'role' => $this->role,
            'permissions' => $this->permissions ?? [],
            'invitation_status' => $this->invitation_status,
            'joined_at' => $this->joined_at?->toIso8601String(),
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'avatar' => $this->user?->avatar_url ?? $this->user?->profile?->avatar_url,
            ],
        ];
    }
}
