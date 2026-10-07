<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * এন্টারপ্রাইজ সোশ্যাল গ্রুপ / কমিউনিটি মডেল V2:
 * ফেসবুকের চেয়ে উন্নত, আধুনিক এবং হাইপার-স্কেলেবল কমিউনিটি আর্কিটেকচার।
 * মাল্টি-অ্যাডমিন আরব্যাক, কাস্টম পারমিশন, মডারেশন ইঞ্জিন, রুলস, স্ক্রিনিং, পোল, ইভেন্ট ও নলেজবেস সমন্বিত।
 */
class Group extends Model
{
    use HasFactory, SoftDeletes;

    public const PRIVACY_PUBLIC = 'public';

    public const PRIVACY_PRIVATE = 'private';

    public const PRIVACY_HIDDEN = 'hidden';

    public const PRIVACY_INVITE_ONLY = 'invite_only';

    // Community Types V2
    public const COMMUNITY_PUBLIC = 'public';

    public const COMMUNITY_CONTROLLED = 'controlled';

    public const COMMUNITY_PRIVATE = 'private';

    public const COMMUNITY_HIDDEN = 'hidden';

    public const COMMUNITY_INVITE_ONLY = 'invite_only';

    public const COMMUNITY_ORGANIZATION = 'organization';

    public const COMMUNITY_PROJECT = 'project';

    public const COMMUNITY_INTEREST = 'interest';

    // Type Aliases
    public const TYPE_PUBLIC = self::COMMUNITY_PUBLIC;

    public const TYPE_CONTROLLED = self::COMMUNITY_CONTROLLED;

    public const TYPE_PRIVATE = self::COMMUNITY_PRIVATE;

    public const TYPE_HIDDEN = self::COMMUNITY_HIDDEN;

    public const TYPE_INVITE_ONLY = self::COMMUNITY_INVITE_ONLY;

    public const TYPE_ORGANIZATION = self::COMMUNITY_ORGANIZATION;

    public const TYPE_PROJECT = self::COMMUNITY_PROJECT;

    public const TYPE_INTEREST = self::COMMUNITY_INTEREST;

    public const COMMUNITY_TYPES = [
        self::COMMUNITY_PUBLIC,
        self::COMMUNITY_CONTROLLED,
        self::COMMUNITY_PRIVATE,
        self::COMMUNITY_HIDDEN,
        self::COMMUNITY_INVITE_ONLY,
        self::COMMUNITY_ORGANIZATION,
        self::COMMUNITY_PROJECT,
        self::COMMUNITY_INTEREST,
    ];

    public const APPROVAL_ANYONE = 'anyone';

    public const APPROVAL_REQUIRED = 'approval_required';

    public const APPROVAL_ADMIN_ONLY = 'admin_only';

    public const POST_APPROVAL_AUTO = 'auto';

    public const POST_APPROVAL_ADMIN = 'admin_approval';

    public const POST_APPROVAL_NEW_MEMBERS = 'new_members_approval';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_ARCHIVED = 'archived';

    public const VERIFICATION_UNVERIFIED = 'unverified';

    public const VERIFICATION_PENDING = 'pending';

    public const VERIFICATION_VERIFIED = 'verified';

    public const VERIFICATION_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'slug',
        'username',
        'description',
        'category',
        'subcategory',
        'tags',
        'group_type',
        'community_type',
        'language',
        'location',
        'country',
        'privacy',
        'cover_image_url',
        'avatar_url',
        'creator_id',
        'conversation_id',
        'members_count',
        'active_members_count',
        'posts_count',
        'verification_status',
        'official_status',
        'is_verified',
        'status',
        'health_score',
        'health_metrics',
        'membership_approval_mode',
        'post_approval_mode',
        'features',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'members_count' => 'integer',
            'active_members_count' => 'integer',
            'posts_count' => 'integer',
            'is_verified' => 'boolean',
            'health_score' => 'float',
            'health_metrics' => 'array',
            'tags' => 'array',
            'features' => 'array',
            'settings' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class)->where('status', GroupMember::STATUS_ACTIVE);
    }

    public function pendingMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class)->where('status', GroupMember::STATUS_PENDING);
    }

    public function bannedMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class)->where('status', GroupMember::STATUS_BANNED);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withPivot(['role', 'permissions', 'status', 'joined_at', 'strikes_count', 'muted_until', 'restricted_until'])
            ->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(GroupDiscussion::class)->latest();
    }

    public function memberBadges(): HasMany
    {
        return $this->hasMany(GroupMemberBadge::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(GroupSubscription::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(GroupBookmark::class);
    }

    public function customRoles(): HasMany
    {
        return $this->hasMany(GroupCustomRole::class)->orderBy('sort_order');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(GroupRule::class)->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(GroupQuestion::class)->orderBy('sort_order');
    }

    public function polls(): HasMany
    {
        return $this->hasMany(GroupPoll::class)->latest();
    }

    public function events(): HasMany
    {
        return $this->hasMany(GroupEvent::class)->orderBy('start_time');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(GroupAnnouncement::class)->latest();
    }

    public function featuredItems(): HasMany
    {
        return $this->hasMany(GroupFeaturedItem::class)->orderBy('sort_order');
    }

    public function guides(): HasMany
    {
        return $this->hasMany(GroupGuide::class)->orderBy('sort_order');
    }

    public function files(): HasMany
    {
        return $this->hasMany(GroupFile::class)->latest();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(GroupInvitation::class);
    }

    public function moderationActions(): HasMany
    {
        return $this->hasMany(GroupModerationAction::class)->latest();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(GroupReport::class)->latest();
    }

    public function strikes(): HasMany
    {
        return $this->hasMany(GroupMemberStrike::class)->latest();
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(GroupNotificationPreference::class);
    }

    public function analyticsSnapshots(): HasMany
    {
        return $this->hasMany(GroupAnalyticsSnapshot::class)->latest('date');
    }

    public function isPublic(): bool
    {
        return $this->privacy === self::PRIVACY_PUBLIC || $this->community_type === self::COMMUNITY_PUBLIC;
    }

    public function isPrivate(): bool
    {
        return $this->privacy === self::PRIVACY_PRIVATE || $this->community_type === self::COMMUNITY_PRIVATE;
    }

    public function isHidden(): bool
    {
        return $this->privacy === self::PRIVACY_HIDDEN || $this->community_type === self::COMMUNITY_HIDDEN;
    }

    public function isInviteOnly(): bool
    {
        return $this->privacy === self::PRIVACY_INVITE_ONLY || $this->community_type === self::COMMUNITY_INVITE_ONLY;
    }

    /**
     * নির্দিষ্ট ইউজার এই গ্রুপের সক্রিয় সদস্য কিনা যাচাই করা।
     */
    public function hasMember(int $userId): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->where('status', GroupMember::STATUS_ACTIVE)
            ->exists();
    }

    public function isMember(int $userId): bool
    {
        return $this->hasMember($userId);
    }

    /**
     * ইউজারের মেম্বারশিপ রেকর্ড রিটার্ন করে।
     */
    public function getMembership(int $userId): ?GroupMember
    {
        return $this->members()->where('user_id', $userId)->first();
    }

    /**
     * নির্দিষ্ট ইউজার এই গ্রুপের ওনার কিনা।
     */
    public function isOwner(int $userId): bool
    {
        if ($this->creator_id === $userId) {
            return true;
        }

        $member = $this->getMembership($userId);

        return $member && $member->role === GroupMember::ROLE_OWNER && $member->isActive();
    }

    /**
     * নির্দিষ্ট ইউজার এই গ্রুপের অ্যাডমিন কিনা।
     */
    public function isAdmin(int $userId): bool
    {
        if ($this->isOwner($userId)) {
            return true;
        }

        $member = $this->getMembership($userId);

        return $member && $member->role === GroupMember::ROLE_ADMIN && $member->isActive();
    }

    /**
     * নির্দিষ্ট ইউজার এই গ্রুপের মডারেটর কিনা।
     */
    public function isModerator(int $userId): bool
    {
        if ($this->isAdmin($userId)) {
            return true;
        }

        $member = $this->getMembership($userId);

        return $member && $member->role === GroupMember::ROLE_MODERATOR && $member->isActive();
    }

    /**
     * নির্দিষ্ট ইউজারের গ্র্যানুলার পারমিশন আছে কিনা যাচাই করা।
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        if ($this->isOwner($userId) || $this->isAdmin($userId)) {
            return true;
        }

        $member = $this->getMembership($userId);
        if (! $member || ! $member->isActive()) {
            return false;
        }

        return $member->hasPermission($permission);
    }

    /**
     * ইউজার গ্রুপ দেখতে পারবে কিনা।
     */
    public function canView(?User $user): bool
    {
        if ($this->isPublic()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        // Hidden groups only visible to active members
        if ($this->isHidden()) {
            return $this->hasMember($user->id);
        }

        return true;
    }

    /**
     * ইউজার পোস্ট করতে পারবে কিনা।
     */
    public function canPost(int $userId): bool
    {
        $member = $this->getMembership($userId);
        if (! $member || ! $member->isActive()) {
            return false;
        }

        if ($member->isMuted() || $member->isRestricted()) {
            return false;
        }

        $setting = $this->post_approval_mode;
        if ($setting === 'admins_only' && ! $this->isAdmin($userId)) {
            return false;
        }

        return true;
    }

    /**
     * কমিউনিটি হেলথ স্কোর ক্যালকুলেশন।
     */
    public function calculateHealthScore(): array
    {
        $total = max(1, $this->members_count);
        $active = $this->active_members_count;
        $activeRatio = round(($active / $total) * 100, 1);

        $unresolvedReports = $this->reports()->where('status', 'pending')->count();
        $reportPenalty = min(30, $unresolvedReports * 5);

        $recentPosts = $this->posts()->where('created_at', '>=', now()->subDays(7))->count();
        $activityBonus = min(20, $recentPosts * 2);

        $rawScore = max(0, min(100, (0.6 * $activeRatio) + $activityBonus - $reportPenalty + 30));
        $score = round($rawScore, 1);

        $metrics = [
            'active_ratio' => $activeRatio,
            'recent_posts_7d' => $recentPosts,
            'weekly_posts' => $recentPosts,
            'unresolved_reports' => $unresolvedReports,
            'pending_reports' => $unresolvedReports,
            'moderation_health' => $unresolvedReports === 0 ? 'excellent' : ($unresolvedReports < 5 ? 'good' : 'needs_attention'),
        ];

        $this->update([
            'health_score' => $score,
            'health_metrics' => $metrics,
        ]);

        return [
            'health_score' => $score,
            'health_metrics' => $metrics,
            'score' => $score,
            'metrics' => $metrics,
        ];
    }

    /**
     * গ্রুপের স্ট্যান্ডার্ড রেসপন্স ফরম্যাট।
     */
    public function toResponseArray(?User $viewer = null): array
    {
        $viewerMembership = null;
        if ($viewer) {
            $member = $this->getMembership($viewer->id);
            if ($member) {
                $viewerMembership = [
                    'role' => $member->role,
                    'status' => $member->status,
                    'permissions' => $member->permissions ?? [],
                    'is_muted' => $member->isMuted(),
                    'is_restricted' => $member->isRestricted(),
                    'joined_at' => $member->joined_at?->toIso8601String(),
                ];
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'username' => $this->username,
            'description' => $this->description,
            'category' => $this->category,
            'subcategory' => $this->subcategory,
            'tags' => $this->tags ?? [],
            'group_type' => $this->group_type,
            'community_type' => $this->community_type ?? 'interest',
            'language' => $this->language,
            'location' => $this->location,
            'country' => $this->country,
            'privacy' => $this->privacy,
            'membership_approval_mode' => $this->membership_approval_mode,
            'post_approval_mode' => $this->post_approval_mode,
            'cover_image_url' => $this->cover_image_url,
            'avatar_url' => $this->avatar_url,
            'verification_status' => $this->verification_status,
            'official_status' => $this->official_status ?? 'standard',
            'is_verified' => $this->is_verified,
            'status' => $this->status,
            'health_score' => $this->health_score ?? 100.00,
            'health_metrics' => $this->health_metrics ?? [
                'active_ratio' => 100,
                'moderation_health' => 'excellent',
            ],
            'creator' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'username' => $this->creator?->username,
                'avatar_url' => $this->creator?->profile?->avatar_url,
            ],
            'members_count' => $this->members_count,
            'active_members_count' => $this->active_members_count,
            'posts_count' => $this->posts_count,
            'features' => $this->features ?? [
                'posts' => true,
                'discussions' => true,
                'polls' => true,
                'events' => true,
                'files' => true,
                'guides' => true,
                'chat' => true,
                'announcements' => true,
            ],
            'rules_count' => $this->rules()->count(),
            'viewer_membership' => $viewerMembership,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
