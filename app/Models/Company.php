<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    public const VERIFICATION_UNVERIFIED = 'unverified';

    public const VERIFICATION_PENDING = 'pending';

    public const VERIFICATION_VERIFIED = 'verified';

    public const VERIFICATION_REJECTED = 'rejected';

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_UNLISTED = 'unlisted';

    public const VISIBILITY_PRIVATE = 'private';

    protected $fillable = [
        'owner_user_id',
        'name',
        'slug',
        'logo',
        'cover_image',
        'description',
        'industry',
        'company_size',
        'founded_year',
        'website',
        'email',
        'phone',
        'headquarters',
        'locations',
        'verification_status',
        'visibility',
    ];

    protected function casts(): array
    {
        return [
            'locations' => 'array',
            'founded_year' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Company $company): void {
            if (empty($company->slug)) {
                $baseSlug = Str::slug($company->name);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }
                $company->slug = $slug;
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CompanyMember::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(CompanyVerification::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function hasMember(int $userId): bool
    {
        if ($this->owner_user_id === $userId) {
            return true;
        }

        return $this->members()
            ->where('user_id', $userId)
            ->where('invitation_status', CompanyMember::STATUS_ACCEPTED)
            ->exists();
    }

    public function getMemberRole(int $userId): ?string
    {
        if ($this->owner_user_id === $userId) {
            return CompanyMember::ROLE_OWNER;
        }

        $member = $this->members()
            ->where('user_id', $userId)
            ->where('invitation_status', CompanyMember::STATUS_ACCEPTED)
            ->first();

        return $member?->role;
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    public function toResponseArray(?User $viewer = null): array
    {
        $viewerRole = $viewer ? $this->getMemberRole($viewer->id) : null;

        return [
            'id' => $this->id,
            'owner_user_id' => $this->owner_user_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo' => $this->logo,
            'cover_image' => $this->cover_image,
            'description' => $this->description,
            'industry' => $this->industry,
            'company_size' => $this->company_size,
            'founded_year' => $this->founded_year,
            'website' => $this->website,
            'email' => $this->email,
            'phone' => $this->phone,
            'headquarters' => $this->headquarters,
            'locations' => $this->locations ?? [],
            'verification_status' => $this->verification_status,
            'is_verified' => $this->isVerified(),
            'visibility' => $this->visibility,
            'viewer_role' => $viewerRole,
            'active_jobs_count' => $this->jobs()->where('status', JobPosting::STATUS_ACTIVE)->count(),
            'total_jobs_count' => $this->jobs()->count(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
