<?php

namespace App\Services;

use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\PrivacySetting;
use App\Models\User;

class ProfilePrivacyService
{
    /**
     * Check if a viewer is blocked by the target user or vice versa.
     */
    public function isBlocked(User|int $targetUser, User|int|null $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        $targetUserId = $targetUser instanceof User ? $targetUser->id : (int) $targetUser;
        $viewerId = $viewer instanceof User ? $viewer->id : (int) $viewer;

        if ($viewerId === $targetUserId) {
            return false;
        }

        // 1. Check friendship table for blocked status
        $hasFriendshipBlock = Friendship::where(function ($query) use ($targetUserId, $viewerId) {
            $query->where(function ($q) use ($targetUserId, $viewerId) {
                $q->where('user_id', $targetUserId)->where('friend_id', $viewerId);
            })->orWhere(function ($q) use ($targetUserId, $viewerId) {
                $q->where('user_id', $viewerId)->where('friend_id', $targetUserId);
            });
        })->where('status', Friendship::STATUS_BLOCKED)->exists();

        if ($hasFriendshipBlock) {
            return true;
        }

        // 2. Check active records in blocked_users table
        $hasActiveBlock = BlockedUser::active()->where(function ($q) use ($targetUserId, $viewerId) {
            $q->where(function ($sub) use ($targetUserId, $viewerId) {
                $sub->where('user_id', $targetUserId)
                    ->where('identifier', (string) $viewerId);
            })->orWhere(function ($sub) use ($targetUserId, $viewerId) {
                $sub->where('user_id', $viewerId)
                    ->where('identifier', (string) $targetUserId);
            });
        })->exists();

        return $hasActiveBlock;
    }

    /**
     * Get all user IDs involved in a block relationship with the given user.
     *
     * @return array<int>
     */
    public function getBlockedUserIds(int $userId): array
    {
        $friendshipBlocked1 = Friendship::where('user_id', $userId)
            ->where('status', Friendship::STATUS_BLOCKED)
            ->pluck('friend_id')
            ->toArray();

        $friendshipBlocked2 = Friendship::where('friend_id', $userId)
            ->where('status', Friendship::STATUS_BLOCKED)
            ->pluck('user_id')
            ->toArray();

        $tableBlocked1 = BlockedUser::active()
            ->where('user_id', $userId)
            ->whereNotNull('identifier')
            ->pluck('identifier')
            ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
            ->filter()
            ->toArray();

        $tableBlocked2 = BlockedUser::active()
            ->where('identifier', (string) $userId)
            ->pluck('user_id')
            ->toArray();

        return array_values(array_unique(array_merge(
            $friendshipBlocked1,
            $friendshipBlocked2,
            $tableBlocked1,
            $tableBlocked2
        )));
    }

    /**
     * Check if the viewer has administrative privilege to bypass privacy filters (RBAC).
     */
    public function isAdmin(?User $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        return $viewer->hasRole('SUPER_ADMIN')
            || $viewer->hasRole('ADMIN')
            || $viewer->hasPermission('manage.users');
    }

    /**
     * Check if viewer and target user are accepted friends.
     */
    public function isFriend(User $targetUser, ?User $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        if ($viewer->id === $targetUser->id) {
            return true;
        }

        return in_array($viewer->id, $targetUser->getFriendIds(), true);
    }

    /**
     * Check if viewer is following the target user.
     */
    public function isFollower(User $targetUser, ?User $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        if ($viewer->id === $targetUser->id) {
            return true;
        }

        return $targetUser->isFollowedBy($viewer);
    }

    /**
     * Determine if a viewer can access the target user's general profile.
     */
    public function canViewProfile(User $targetUser, ?User $viewer): bool
    {
        // 1. Block check: blocked users can never access the profile
        if ($this->isBlocked($targetUser, $viewer)) {
            return false;
        }

        // 2. Owner can always access
        if ($viewer && $viewer->id === $targetUser->id) {
            return true;
        }

        // 3. Admin bypass
        if ($this->isAdmin($viewer)) {
            return true;
        }

        // 4. Check profile_visibility from PrivacySetting
        $privacySetting = $targetUser->privacySettings;
        $profileVisibility = strtolower(trim($privacySetting?->profile_visibility ?? PrivacySetting::LEVEL_PUBLIC));

        return match ($profileVisibility) {
            PrivacySetting::LEVEL_PUBLIC => true,
            PrivacySetting::LEVEL_FOLLOWERS => $this->isFollower($targetUser, $viewer) || $this->isFriend($targetUser, $viewer),
            PrivacySetting::LEVEL_FRIENDS => $this->isFriend($targetUser, $viewer),
            PrivacySetting::LEVEL_ONLY_ME => false,
            default => true,
        };
    }

    /**
     * Evaluate if a specific field/section is visible based on privacy level.
     */
    public function isFieldVisible(string $privacyLevel, User $targetUser, ?User $viewer): bool
    {
        // 1. If blocked, nothing is visible
        if ($this->isBlocked($targetUser, $viewer)) {
            return false;
        }

        // 2. Owner can view everything
        if ($viewer && $viewer->id === $targetUser->id) {
            return true;
        }

        // 3. Admin bypass via RBAC
        if ($this->isAdmin($viewer)) {
            return true;
        }

        // 4. If target user locked their profile, only friends and owner can see details
        $settings = $targetUser->settings;
        $isProfileLocked = (bool) ($settings?->is_profile_locked ?? false);
        $isFriend = $this->isFriend($targetUser, $viewer);

        if ($isProfileLocked && ! $isFriend) {
            return false;
        }

        $level = strtolower(trim($privacyLevel));
        $isFollower = $this->isFollower($targetUser, $viewer);

        return match ($level) {
            PrivacySetting::LEVEL_PUBLIC, 'everyone' => true,
            PrivacySetting::LEVEL_FOLLOWERS => $isFollower || $isFriend,
            PrivacySetting::LEVEL_FRIENDS => $isFriend,
            PrivacySetting::LEVEL_ONLY_ME => false,
            default => false,
        };
    }

    /**
     * Check if viewer can see Avatar.
     */
    public function canViewAvatar(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->avatar_privacy ?? PrivacySetting::LEVEL_PUBLIC;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see Cover photo.
     */
    public function canViewCover(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->cover_privacy ?? PrivacySetting::LEVEL_PUBLIC;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see Bio.
     */
    public function canViewBio(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->bio_privacy ?? PrivacySetting::LEVEL_PUBLIC;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see About.
     */
    public function canViewAbout(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->about_privacy ?? PrivacySetting::LEVEL_PUBLIC;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see Email.
     */
    public function canViewEmail(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->email_privacy
            ?? $targetUser->privacySettings?->email_visibility
            ?? PrivacySetting::LEVEL_ONLY_ME;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see Phone.
     */
    public function canViewPhone(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->phone_privacy
            ?? $targetUser->privacySettings?->phone_visibility
            ?? PrivacySetting::LEVEL_ONLY_ME;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see Date of Birth.
     */
    public function canViewDob(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->dob_privacy
            ?? $targetUser->privacySettings?->birthday_visibility
            ?? PrivacySetting::LEVEL_FRIENDS;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see Location.
     */
    public function canViewLocation(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->location_privacy ?? PrivacySetting::LEVEL_PUBLIC;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see Education section.
     */
    public function canViewEducation(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->education_privacy ?? PrivacySetting::LEVEL_PUBLIC;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see Work section.
     */
    public function canViewWork(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->work_privacy ?? PrivacySetting::LEVEL_PUBLIC;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see Skills section.
     */
    public function canViewSkills(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->skills_privacy ?? PrivacySetting::LEVEL_PUBLIC;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see Interests section.
     */
    public function canViewInterests(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->interests_privacy ?? PrivacySetting::LEVEL_PUBLIC;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see Languages section.
     */
    public function canViewLanguages(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->languages_privacy ?? PrivacySetting::LEVEL_PUBLIC;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }

    /**
     * Check if viewer can see Social Links section.
     */
    public function canViewSocialLinks(User $targetUser, ?User $viewer): bool
    {
        $privacy = $targetUser->privacySettings?->social_links_privacy ?? PrivacySetting::LEVEL_PUBLIC;

        return $this->isFieldVisible($privacy, $targetUser, $viewer);
    }
}
