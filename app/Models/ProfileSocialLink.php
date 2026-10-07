<?php

namespace App\Models;

use App\Services\Security\SocialUrlSanitizer;
use App\Services\SocialLink\SocialPlatformRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileSocialLink extends Model
{
    use HasFactory;

    protected $table = 'profile_social_links';

    protected $fillable = [
        'user_id',
        'platform',
        'url',
        'display_order',
        'is_visible',
        'privacy',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setPlatformAttribute(string $value): void
    {
        $this->attributes['platform'] = SocialPlatformRegistry::normalizePlatform($value);
    }

    public function setUrlAttribute(string $value): void
    {
        $normalized = SocialUrlSanitizer::normalize($value);
        $this->attributes['url'] = $normalized ?: trim($value);
    }

    public function getPlatformNameAttribute(): string
    {
        return SocialPlatformRegistry::getPlatformName($this->platform);
    }

    /**
     * Scope query to items visible to the viewer based on privacy and visibility.
     */
    public function scopeVisibleFor($query, ?User $viewer)
    {
        return $query->where(function ($q) use ($viewer) {
            if ($viewer) {
                // Owner sees all their own links
                $q->where('user_id', $viewer->id)
                    ->orWhere(function ($publicQuery) use ($viewer) {
                        $publicQuery->where('is_visible', true)
                            ->where(function ($sub) use ($viewer) {
                                $sub->where('privacy', 'public')
                                    ->orWhere(function ($fQuery) use ($viewer) {
                                        $fQuery->where('privacy', 'friends')
                                            ->whereIn('user_id', $viewer->getFriendIds());
                                    })
                                    ->orWhere(function ($folQuery) use ($viewer) {
                                        $folQuery->where('privacy', 'followers')
                                            ->where(function ($f) use ($viewer) {
                                                $f->whereIn('user_id', $viewer->getFriendIds())
                                                    ->orWhereIn('user_id', UserFollower::where('follower_id', $viewer->id)->pluck('user_id'));
                                            });
                                    });
                            });
                    });
            } else {
                $q->where('is_visible', true)
                    ->where('privacy', 'public');
            }
        });
    }
}
