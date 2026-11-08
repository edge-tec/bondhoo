<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * গ্রুপ মেম্বার মডেল:
 * গ্রুপে ইউজারের মেম্বারশিপ, রোল, গ্র্যানুলার পারমিশন এবং মডারেশন স্ট্যাটাস সংরক্ষণ করে।
 */
class GroupMember extends Model
{
    use HasFactory;

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MODERATOR = 'moderator';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_CONTENT_MANAGER = 'content_manager';

    public const ROLE_MEMBER = 'member';

    public const ROLE_RESTRICTED = 'restricted';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PENDING = 'pending';

    public const STATUS_MUTED = 'muted';

    public const STATUS_RESTRICTED = 'restricted';

    public const STATUS_BANNED = 'banned';

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'permissions',
        'status',
        'joined_at',
        'invited_by',
        'answers',
        'strikes_count',
        'muted_until',
        'restricted_until',
        'banned_at',
        'ban_reason',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'answers' => 'array',
            'strikes_count' => 'integer',
            'joined_at' => 'datetime',
            'muted_until' => 'datetime',
            'restricted_until' => 'datetime',
            'banned_at' => 'datetime',
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

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, 'approved'], true) && ! $this->isBanned();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isBanned(): bool
    {
        return $this->status === self::STATUS_BANNED || $this->banned_at !== null;
    }

    public function isMuted(): bool
    {
        if ($this->muted_until && $this->muted_until->isFuture()) {
            return true;
        }

        return $this->status === self::STATUS_MUTED;
    }

    public function isRestricted(): bool
    {
        if ($this->restricted_until && $this->restricted_until->isFuture()) {
            return true;
        }

        return $this->status === self::STATUS_RESTRICTED;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER && $this->isActive();
    }

    public function isAdmin(): bool
    {
        return ($this->role === self::ROLE_ADMIN || $this->role === self::ROLE_OWNER) && $this->isActive();
    }

    public function isModerator(): bool
    {
        return ($this->role === self::ROLE_MODERATOR || $this->isAdmin()) && $this->isActive();
    }

    public function isContentManager(): bool
    {
        return ($this->role === self::ROLE_CONTENT_MANAGER || $this->role === self::ROLE_EDITOR || $this->isAdmin()) && $this->isActive();
    }

    public static function getDefaultPermissionsForRole(string $role): array
    {
        return match ($role) {
            self::ROLE_OWNER => [
                'manage_settings', 'transfer_ownership', 'delete_group', 'manage_roles',
                'manage_admins', 'manage_moderators', 'manage_members', 'approve_members',
                'manage_posts', 'manage_comments', 'manage_announcements', 'manage_events',
                'manage_files', 'manage_media', 'manage_reports', 'warn_members', 'suspend_members',
                'view_analytics', 'publish_content', 'comment_and_react', 'invite_members',
            ],
            self::ROLE_ADMIN => [
                'manage_settings', 'manage_moderators', 'manage_members', 'approve_members',
                'manage_posts', 'approve_posts', 'delete_posts', 'pin_posts', 'manage_comments', 'manage_announcements', 'manage_events',
                'manage_files', 'manage_media', 'manage_reports', 'moderate_members', 'warn_members', 'suspend_members',
                'view_analytics', 'publish_content', 'comment_and_react', 'invite_members',
            ],
            self::ROLE_MODERATOR => [
                'approve_members', 'manage_posts', 'approve_posts', 'delete_posts', 'pin_posts', 'manage_comments', 'manage_reports',
                'moderate_members', 'warn_members', 'suspend_members', 'publish_content', 'comment_and_react',
                'invite_members', 'view_analytics',
            ],
            self::ROLE_CONTENT_MANAGER, self::ROLE_EDITOR => [
                'publish_content', 'manage_announcements', 'manage_events', 'manage_files',
                'manage_media', 'schedule_posts', 'comment_and_react', 'invite_members',
            ],
            self::ROLE_MEMBER => [
                'publish_content', 'comment_and_react', 'invite_members', 'rsvp_events',
            ],
            self::ROLE_RESTRICTED => [
                'view_content',
            ],
            default => [],
        };
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner() || $this->isAdmin()) {
            return true;
        }

        if ($this->isRestricted() || $this->isBanned()) {
            return false;
        }

        $defaultPerms = self::getDefaultPermissionsForRole($this->role);
        $customPerms = $this->permissions ?? [];

        if (in_array($permission, $defaultPerms, true) || in_array($permission, $customPerms, true)) {
            return true;
        }

        // Check if role matches a custom role record for this group
        $customRole = GroupCustomRole::where('group_id', $this->group_id)->where('name', $this->role)->first();
        if ($customRole && is_array($customRole->permissions) && in_array($permission, $customRole->permissions, true)) {
            return true;
        }

        return false;
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'username' => $this->user?->username,
                'avatar_url' => $this->user?->profile?->avatar_url,
            ],
            'role' => $this->role,
            'status' => $this->status,
            'permissions' => $this->permissions ?? [],
            'strikes_count' => $this->strikes_count,
            'is_muted' => $this->isMuted(),
            'muted_until' => $this->muted_until?->toIso8601String(),
            'is_restricted' => $this->isRestricted(),
            'restricted_until' => $this->restricted_until?->toIso8601String(),
            'is_banned' => $this->isBanned(),
            'banned_at' => $this->banned_at?->toIso8601String(),
            'ban_reason' => $this->ban_reason,
            'joined_at' => $this->joined_at?->toIso8601String(),
        ];
    }

    public function toDetailedResponseArray(): array
    {
        $base = $this->toResponseArray();

        $postsCount = Post::where('group_id', $this->group_id)->where('user_id', $this->user_id)->count();
        $groupPostIds = Post::where('group_id', $this->group_id)->pluck('id');
        $commentsCount = Comment::whereIn('post_id', $groupPostIds)->where('user_id', $this->user_id)->count();

        $lastPost = Post::where('group_id', $this->group_id)->where('user_id', $this->user_id)->latest('created_at')->first();
        $lastComment = Comment::whereIn('post_id', $groupPostIds)->where('user_id', $this->user_id)->latest('created_at')->first();

        $lastActivity = collect([$lastPost?->created_at, $lastComment?->created_at, $this->joined_at])
            ->filter()
            ->sortDesc()
            ->first();

        $accountStatus = 'active';
        if ($this->isBanned()) {
            $accountStatus = 'banned';
        } elseif ($this->isRestricted()) {
            $accountStatus = 'restricted';
        } elseif ($this->isMuted()) {
            $accountStatus = 'muted';
        } elseif ($this->isPending()) {
            $accountStatus = 'pending';
        }

        return array_merge($base, [
            'name' => $this->user?->name,
            'username' => $this->user?->username,
            'avatar_url' => $this->user?->profile?->avatar_url,
            'posts_published' => $postsCount,
            'posts_count' => $postsCount,
            'comments_count' => $commentsCount,
            'last_activity_at' => $lastActivity?->toIso8601String(),
            'account_status' => $accountStatus,
        ]);
    }
}
