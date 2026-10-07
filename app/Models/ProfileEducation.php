<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileEducation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'profile_educations';

    protected $fillable = [
        'user_id',
        'institution_name',
        'degree',
        'field_of_study',
        'start_date',
        'end_date',
        'is_current',
        'grade',
        'description',
        'location',
        'display_order',
        'privacy',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getInstitutionAttribute(): ?string
    {
        return $this->institution_name;
    }

    public function setInstitutionAttribute(?string $value): void
    {
        $this->attributes['institution_name'] = $value;
    }

    public function getCurrentlyStudyingAttribute(): bool
    {
        return (bool) $this->is_current;
    }

    public function setCurrentlyStudyingAttribute(mixed $value): void
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
