<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_visibility',
        'avatar_privacy',
        'cover_privacy',
        'bio_privacy',
        'about_privacy',
        'location_privacy',
        'education_privacy',
        'work_privacy',
        'skills_privacy',
        'interests_privacy',
        'languages_privacy',
        'social_links_privacy',
        'first_name_privacy',
        'last_name_privacy',
        'gender_privacy',
        'dob_privacy',
        'dob_display_format',
        'country_privacy',
        'city_privacy',
        'address_privacy',
        'post_default_privacy',
        'friends_list_visibility',
        'who_can_send_friend_requests',
        'who_can_follow_me',
        'mutual_friends_visibility',
        'search_engine_indexing',
        'phone_visibility',
        'email_visibility',
        'birthday_visibility',
        'profile_view_visibility',
        'who_can_message_me',
        'who_can_add_to_groups',
        'show_online_status',
        'show_last_seen',
        'read_receipts_enabled',
    ];

    protected function casts(): array
    {
        return [
            'search_engine_indexing' => 'boolean',
            'show_online_status' => 'boolean',
            'read_receipts_enabled' => 'boolean',
        ];
    }

    public const LEVEL_PUBLIC = 'public';

    public const LEVEL_FOLLOWERS = 'followers';

    public const LEVEL_FRIENDS = 'friends';

    public const LEVEL_ONLY_ME = 'only_me';

    public const DOB_FULL = 'full';

    public const DOB_MONTH_DAY = 'month_day';

    public const DOB_AGE = 'age';

    public const DOB_HIDDEN = 'hidden';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if a field is visible to a viewer based on privacy level.
     */
    public function isFieldVisible(string $privacyLevel, bool $isOwner, bool $isFriend, bool $isFollower): bool
    {
        if ($isOwner) {
            return true;
        }

        $level = strtolower(trim($privacyLevel));

        return match ($level) {
            self::LEVEL_PUBLIC => true,
            self::LEVEL_FOLLOWERS => $isFollower || $isFriend,
            self::LEVEL_FRIENDS => $isFriend,
            self::LEVEL_ONLY_ME => false,
            default => false,
        };
    }
}
