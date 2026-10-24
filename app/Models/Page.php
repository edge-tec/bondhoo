<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * এন্টারপ্রাইজ সোশ্যাল পেজ মডেল (Enterprise Social Page Operating System):
 * ইন্ডিভিজুয়াল ক্রিয়েটর, অর্গানাইজেশন এবং ব্র্যান্ড পেজ পরিচালনা করে।
 * মাল্টি-এডমিন আরব্যাক, কাস্টম পারমিশন, টিম ম্যানেজমেন্ট এবং ফুল অডিট ট্রেইল সমন্বিত।
 */
class Page extends Model
{
    use HasFactory, SoftDeletes;

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_CONTENT_MANAGER = 'content_manager';

    public const ROLE_MODERATOR = 'moderator';

    public const ROLE_ANALYST = 'analyst';

    public const ROLE_VIEWER = 'viewer';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_DISABLED = 'disabled';

    public const STATUS_TRASH = 'trash';

    protected $fillable = [
        'name',
        'slug',
        'username',
        'category',
        'sub_category',
        'page_type_id',
        'page_category_id',
        'page_subcategory_id',
        'bio',
        'short_description',
        'description',
        'avatar_url',
        'logo_url',
        'cover_image_url',
        'owner_id',
        'organization_id',
        'website',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'zip_code',
        'business_hours',
        'business_details',
        'custom_fields_data',
        'social_links',
        'gallery_urls',
        'cta_type',
        'cta_url',
        'verification_status',
        'visibility',
        'status',
        'settings',
        'privacy_settings',
        'metadata',
        'followers_count',
        'posts_count',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'business_hours' => 'array',
            'business_details' => 'array',
            'custom_fields_data' => 'array',
            'social_links' => 'array',
            'gallery_urls' => 'array',
            'settings' => 'array',
            'privacy_settings' => 'array',
            'metadata' => 'array',
            'followers_count' => 'integer',
            'posts_count' => 'integer',
            'is_verified' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function pageType(): BelongsTo
    {
        return $this->belongsTo(PageType::class);
    }

    public function categoryRecord(): BelongsTo
    {
        return $this->belongsTo(PageCategory::class, 'page_category_id');
    }

    public function subcategoryRecord(): BelongsTo
    {
        return $this->belongsTo(PageSubcategory::class, 'page_subcategory_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(PageLocation::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(PageVerificationRequest::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(PageMember::class);
    }

    public function teamUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'page_members')
            ->withPivot('role', 'custom_permissions', 'status', 'joined_at')
            ->withTimestamps();
    }

    public function followers(): HasMany
    {
        return $this->hasMany(PageFollower::class);
    }

    public function followerUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'page_followers')
            ->withPivot('created_at');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function blockedUsers(): HasMany
    {
        return $this->hasMany(PageBlockedUser::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PageAuditLog::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(PageConversation::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PageEvent::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(PageProduct::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(PageInvitation::class);
    }

    /**
     * নির্দিষ্ট ইউজার এই পেজটি ফলো করছেন কিনা।
     */
    public function isFollowedBy(int $userId): bool
    {
        return $this->followers()->where('user_id', $userId)->exists();
    }

    /**
     * ইউজার পেজে ব্লক করা আছে কিনা যাচাই
     */
    public function isBlocked(int $userId): bool
    {
        return PageBlockedUser::where('page_id', $this->id)->where('user_id', $userId)->exists();
    }

    /**
     * ইউজারের রোল নির্ণয় (Owner বা PageMember)
     */
    public function getMemberRole(int $userId): ?string
    {
        if ((int) $this->owner_id === $userId) {
            return self::ROLE_OWNER;
        }

        $member = $this->members()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        return $member?->role;
    }

    /**
     * গ্র্যানুলার পারমিশন যাচাই
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        // পেজ ওনার সব পারমিশনের অধিকারী
        if ((int) $this->owner_id === $userId) {
            return true;
        }

        $member = $this->members()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if (! $member) {
            return false;
        }

        // কাস্টম পারমিশন ওভাররাইড চেক (key => bool অথবা list of strings)
        if (! empty($member->custom_permissions) && is_array($member->custom_permissions)) {
            if (array_key_exists($permission, $member->custom_permissions)) {
                return (bool) $member->custom_permissions[$permission];
            }
            if (in_array($permission, $member->custom_permissions, true)) {
                return true;
            }
        }

        // রোল-ভিত্তিক ডিফল্ট পারমিশন ম্যাট্রিক্স
        $rolePermissions = [
            self::ROLE_ADMIN => [
                'page.view', 'page.edit', 'page.settings', 'team.manage',
                'posts.create', 'posts.edit', 'posts.delete', 'posts.publish', 'posts.schedule',
                'media.manage', 'comments.moderate', 'messages.manage', 'analytics.view',
                'events.manage', 'commerce.manage', 'moderation.manage',
            ],
            self::ROLE_MANAGER => [
                'page.view', 'page.edit', 'page.settings',
                'posts.create', 'posts.edit', 'posts.publish', 'posts.schedule',
                'media.manage', 'comments.moderate', 'messages.manage', 'analytics.view',
                'events.manage', 'moderation.manage',
            ],
            self::ROLE_EDITOR => [
                'page.view', 'page.edit', 'posts.create', 'posts.edit', 'posts.publish', 'posts.schedule',
                'media.manage', 'comments.moderate', 'events.manage', 'analytics.view',
            ],
            self::ROLE_CONTENT_MANAGER => [
                'page.view', 'posts.create', 'posts.edit', 'posts.publish', 'posts.schedule',
                'media.manage', 'comments.moderate', 'events.manage', 'analytics.view',
            ],
            self::ROLE_MODERATOR => [
                'page.view', 'comments.moderate', 'messages.manage', 'moderation.manage',
            ],
            self::ROLE_ANALYST => [
                'page.view', 'analytics.view',
            ],
            self::ROLE_VIEWER => [
                'page.view',
            ],
        ];

        $normalizedRole = in_array($member->role, ['administrator', 'admin'], true) ? self::ROLE_ADMIN : $member->role;
        $allowed = $rolePermissions[$normalizedRole] ?? [];

        return in_array($permission, $allowed, true);
    }

    /**
     * পেজের মার্জড ডিফল্ট সেটিংস পাওয়া।
     *
     * @return array<string, mixed>
     */
    public function getMergedSettings(): array
    {
        $defaults = [
            'visibility' => $this->visibility ?? 'public',
            'discoverability' => [
                'search_engine_indexing' => true,
                'show_in_directory' => true,
            ],
            'messaging' => [
                'allow_messages' => true,
                'auto_reply_enabled' => false,
                'auto_reply_message' => 'Thanks for reaching out! We will reply shortly.',
            ],
            'moderation' => [
                'allow_comments' => true,
                'profanity_filter' => true,
                'blocked_keywords' => [],
            ],
            'notifications' => [
                'email_alerts' => true,
                'in_app_follows' => true,
                'in_app_comments' => true,
                'in_app_messages' => true,
            ],
            'publishing' => [
                'allowed_formats' => ['text', 'image', 'video', 'reel', 'story', 'live'],
            ],
            'privacy' => [
                'audience' => 'public',
                'show_contact_info' => true,
            ],
            'tagging' => [
                'allow_mentions' => true,
                'who_can_tag' => 'everyone',
            ],
        ];

        $current = $this->settings ?? [];
        $merged = array_replace_recursive($defaults, $current);

        if (isset($current['publishing']['allowed_formats']) && is_array($current['publishing']['allowed_formats'])) {
            $merged['publishing']['allowed_formats'] = array_values($current['publishing']['allowed_formats']);
        }
        if (isset($current['moderation']['blocked_keywords']) && is_array($current['moderation']['blocked_keywords'])) {
            $merged['moderation']['blocked_keywords'] = array_values($current['moderation']['blocked_keywords']);
        }

        return $merged;
    }

    /**
     * পেজের মার্জড ডিফল্ট প্রাইভেসি সেটিংস পাওয়া।
     *
     * @return array<string, mixed>
     */
    public function getMergedPrivacySettings(): array
    {
        $defaults = [
            'public_email' => true,
            'public_phone' => true,
            'public_address' => true,
            'default_post_audience' => 'public',
        ];

        return array_replace_recursive($defaults, $this->privacy_settings ?? []);
    }

    /**
     * পেজের স্ট্যান্ডার্ড রেসপন্স ফরম্যাট।
     */
    public function toResponseArray(?User $viewer = null): array
    {
        $isFollowing = false;
        $isOwner = false;
        $userRole = null;

        if ($viewer) {
            $isFollowing = $this->isFollowedBy($viewer->id);
            $isOwner = (int) $this->owner_id === (int) $viewer->id;
            $userRole = $this->getMemberRole($viewer->id);
        }

        $settings = $this->getMergedSettings();
        $privacy = $this->getMergedPrivacySettings();
        $canManage = $isOwner || in_array($userRole, [
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_EDITOR,
            self::ROLE_CONTENT_MANAGER,
            self::ROLE_MODERATOR,
        ], true);

        $showContactInfo = (bool) ($settings['privacy']['show_contact_info'] ?? true);
        $publicEmail = (bool) ($privacy['public_email'] ?? true);
        $publicPhone = (bool) ($privacy['public_phone'] ?? true);
        $publicAddress = (bool) ($privacy['public_address'] ?? true);

        $email = ($canManage || ($showContactInfo && $publicEmail)) ? $this->email : null;
        $phone = ($canManage || ($showContactInfo && $publicPhone)) ? $this->phone : null;
        $address = ($canManage || ($showContactInfo && $publicAddress)) ? $this->address : null;
        $city = ($canManage || ($showContactInfo && $publicAddress)) ? $this->city : null;
        $country = ($canManage || ($showContactInfo && $publicAddress)) ? $this->country : null;
        $zipCode = ($canManage || ($showContactInfo && $publicAddress)) ? $this->zip_code : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'username' => $this->username,
            'category' => $this->category,
            'sub_category' => $this->sub_category,
            'bio' => $this->bio,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'avatar_url' => $this->avatar_url,
            'cover_image_url' => $this->cover_image_url,
            'logo_url' => $this->logo_url,
            'owner' => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
                'username' => $this->owner?->username,
            ],
            'website' => $this->website,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'country' => $country,
            'zip_code' => $zipCode,
            'business_hours' => $this->business_hours,
            'social_links' => $this->social_links,
            'cta_type' => $this->cta_type,
            'cta_url' => $this->cta_url,
            'verification_status' => $this->verification_status,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'settings' => $settings,
            'privacy_settings' => $privacy,
            'followers_count' => $this->followers_count,
            'posts_count' => $this->posts_count,
            'is_verified' => $this->is_verified,
            'is_following' => $isFollowing,
            'is_owner' => $isOwner,
            'viewer_role' => $userRole,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
