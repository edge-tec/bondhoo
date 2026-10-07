<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMemberBadge extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const BADGE_HELPFUL_CONTRIBUTOR = 'helpful_contributor';

    public const BADGE_DISCUSSION_STARTER = 'discussion_starter';

    public const BADGE_EVENT_ORGANIZER = 'event_organizer';

    public const BADGE_GUIDE_CONTRIBUTOR = 'guide_contributor';

    public const BADGE_RESOURCE_CONTRIBUTOR = 'resource_contributor';

    public const BADGE_COMMUNITY_MENTOR = 'community_mentor';

    // Type aliases
    public const TYPE_HELPFUL_CONTRIBUTOR = self::BADGE_HELPFUL_CONTRIBUTOR;

    public const TYPE_DISCUSSION_STARTER = self::BADGE_DISCUSSION_STARTER;

    public const TYPE_EVENT_ORGANIZER = self::BADGE_EVENT_ORGANIZER;

    public const TYPE_GUIDE_CONTRIBUTOR = self::BADGE_GUIDE_CONTRIBUTOR;

    public const TYPE_RESOURCE_CONTRIBUTOR = self::BADGE_RESOURCE_CONTRIBUTOR;

    public const TYPE_COMMUNITY_MENTOR = self::BADGE_COMMUNITY_MENTOR;

    public const TYPES = [
        self::BADGE_HELPFUL_CONTRIBUTOR,
        self::BADGE_DISCUSSION_STARTER,
        self::BADGE_EVENT_ORGANIZER,
        self::BADGE_GUIDE_CONTRIBUTOR,
        self::BADGE_RESOURCE_CONTRIBUTOR,
        self::BADGE_COMMUNITY_MENTOR,
    ];

    protected $fillable = [
        'group_id',
        'user_id',
        'badge_type',
        'assigned_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function getTitleAttribute(): string
    {
        return match ($this->badge_type) {
            self::BADGE_HELPFUL_CONTRIBUTOR => 'Helpful Contributor',
            self::BADGE_DISCUSSION_STARTER => 'Discussion Starter',
            self::BADGE_EVENT_ORGANIZER => 'Event Organizer',
            self::BADGE_GUIDE_CONTRIBUTOR => 'Guide Contributor',
            self::BADGE_RESOURCE_CONTRIBUTOR => 'Resource Contributor',
            self::BADGE_COMMUNITY_MENTOR => 'Community Mentor',
            default => 'Contributor',
        };
    }
}
