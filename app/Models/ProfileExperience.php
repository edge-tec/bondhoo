<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileExperience extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'profile_experiences';

    protected $fillable = [
        'user_id',
        'company_name',
        'job_title',
        'employment_type',
        'location',
        'is_remote',
        'start_date',
        'end_date',
        'is_current',
        'description',
        'display_order',
        'privacy',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
            'is_remote' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCompanyAttribute(): ?string
    {
        return $this->company_name;
    }

    public function setCompanyAttribute(?string $value): void
    {
        $this->attributes['company_name'] = $value;
    }

    public function getPositionAttribute(): ?string
    {
        return $this->job_title;
    }

    public function setPositionAttribute(?string $value): void
    {
        $this->attributes['job_title'] = $value;
    }

    public function getCurrentlyWorkingAttribute(): bool
    {
        return (bool) $this->is_current;
    }

    public function setCurrentlyWorkingAttribute(mixed $value): void
    {
        $this->attributes['is_current'] = (bool) $value;
    }

    /**
     * Scope query to items visible to the viewer based on privacy level.
     */
    public function scopeVisibleFor($query, ?User $viewer)
    {
        return $query->where(function ($q) use ($viewer) {
            if ($viewer) {
                // Owner sees all their items
                $q->where('user_id', $viewer->id)
                    ->orWhere('privacy', 'public')
                    ->orWhere(function ($sub) use ($viewer) {
                        $sub->where('privacy', 'friends')
                            ->whereIn('user_id', $viewer->getFriendIds());
                    })
                    ->orWhere(function ($sub) use ($viewer) {
                        $sub->where('privacy', 'followers')
                            ->where(function ($f) use ($viewer) {
                                $f->whereIn('user_id', $viewer->getFriendIds())
                                    ->orWhereIn('user_id', UserFollower::where('follower_id', $viewer->id)->pluck('user_id'));
                            });
                    });
            } else {
                $q->where('privacy', 'public');
            }
        });
    }
}
