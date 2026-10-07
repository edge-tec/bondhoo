<?php

namespace App\Services;

use App\Events\ProfileUpdatedEvent;
use App\Models\AuditLog;
use App\Models\ProfileSocialLink;
use App\Models\User;
use App\Services\Security\SocialUrlSanitizer;
use App\Services\SocialLink\SocialPlatformRegistry;
use Illuminate\Support\Facades\DB;

class SocialLinkService
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Get social links for target user, filtered by viewer privacy.
     */
    public function getSocialLinks(User $targetUser, ?User $viewer = null): array
    {
        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isAdmin = $privacyService->isAdmin($viewer);
        $isOwner = $viewer && $viewer->id === $targetUser->id;

        if (! $isAdmin && ! $isOwner && ! $privacyService->canViewSocialLinks($targetUser, $viewer)) {
            return [];
        }

        $isFriend = $viewer && in_array($viewer->id, $targetUser->getFriendIds(), true);
        $isLocked = (bool) ($targetUser->settings?->is_profile_locked ?? false);
        $isLockedView = $isLocked && ! $isFriend && ! $isOwner && ! $isAdmin;

        if ($isLockedView) {
            return [];
        }

        $records = $targetUser->socialLinks()
            ->visibleFor($viewer)
            ->orderBy('display_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $records->map(fn (ProfileSocialLink $item) => $this->formatSocialLinkItem($item))->toArray();
    }

    /**
     * Format a social link item.
     */
    public function formatSocialLinkItem(ProfileSocialLink $item): array
    {
        return [
            'id' => $item->id,
            'user_id' => $item->user_id,
            'platform' => $item->platform,
            'platform_name' => $item->platform_name,
            'url' => $item->url,
            'display_order' => (int) $item->display_order,
            'is_visible' => (bool) $item->is_visible,
            'privacy' => $item->privacy ?? 'public',
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Add or upsert a social link for user.
     */
    public function addSocialLink(
        User $targetUser,
        array $data,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileSocialLink {
        return DB::transaction(function () use ($targetUser, $data, $actor, $ip, $userAgent) {
            $platform = SocialPlatformRegistry::normalizePlatform($data['platform']);
            $url = SocialUrlSanitizer::normalize($data['url']) ?: trim($data['url']);
            $privacy = $data['privacy'] ?? 'public';
            $isVisible = isset($data['is_visible']) ? (bool) $data['is_visible'] : true;

            $maxOrder = (int) $targetUser->socialLinks()->max('display_order');
            $displayOrder = isset($data['display_order']) ? (int) $data['display_order'] : $maxOrder + 1;

            $existing = ProfileSocialLink::where('user_id', $targetUser->id)
                ->where('platform', $platform)
                ->first();

            if ($existing) {
                $oldValues = $existing->toArray();
                $existing->update([
                    'url' => $url,
                    'privacy' => $privacy,
                    'is_visible' => $isVisible,
                    'display_order' => $displayOrder,
                ]);
                $link = $existing->fresh();
                $action = 'SOCIAL_LINK_UPDATED';
            } else {
                $oldValues = null;
                $link = $targetUser->socialLinks()->create([
                    'platform' => $platform,
                    'url' => $url,
                    'privacy' => $privacy,
                    'is_visible' => $isVisible,
                    'display_order' => $displayOrder,
                ]);
                $action = 'SOCIAL_LINK_ADDED';
            }

            // Sync user profile JSON column if present
            $this->syncProfileJsonMap($targetUser);

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => $action,
                'entity_type' => ProfileSocialLink::class,
                'entity_id' => $link->id,
                'old_values' => $oldValues,
                'new_values' => $link->toArray(),
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['social_links'],
                actor: $actor
            ));

            return $link;
        });
    }

    /**
     * Update an existing social link.
     */
    public function updateSocialLink(
        ProfileSocialLink $link,
        array $data,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileSocialLink {
        return DB::transaction(function () use ($link, $data, $actor, $ip, $userAgent) {
            $oldValues = $link->toArray();
            $targetUser = $link->user;

            $updates = [];

            if (isset($data['platform'])) {
                $updates['platform'] = SocialPlatformRegistry::normalizePlatform($data['platform']);
            }
            if (isset($data['url'])) {
                $updates['url'] = SocialUrlSanitizer::normalize($data['url']) ?: trim($data['url']);
            }
            if (isset($data['privacy'])) {
                $updates['privacy'] = $data['privacy'];
            }
            if (isset($data['is_visible'])) {
                $updates['is_visible'] = (bool) $data['is_visible'];
            }
            if (isset($data['display_order'])) {
                $updates['display_order'] = (int) $data['display_order'];
            }

            $link->update($updates);
            $fresh = $link->fresh();

            // Sync user profile JSON column
            $this->syncProfileJsonMap($targetUser);

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'SOCIAL_LINK_UPDATED',
                'entity_type' => ProfileSocialLink::class,
                'entity_id' => $link->id,
                'old_values' => $oldValues,
                'new_values' => $fresh->toArray(),
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['social_links'],
                actor: $actor
            ));

            return $fresh;
        });
    }

    /**
     * Delete a social link.
     */
    public function deleteSocialLink(
        ProfileSocialLink $link,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        DB::transaction(function () use ($link, $actor, $ip, $userAgent) {
            $oldValues = $link->toArray();
            $targetUser = $link->user;

            $link->delete();

            // Sync user profile JSON column
            $this->syncProfileJsonMap($targetUser);

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'SOCIAL_LINK_DELETED',
                'entity_type' => ProfileSocialLink::class,
                'entity_id' => $link->id,
                'old_values' => $oldValues,
                'new_values' => null,
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['social_links'],
                actor: $actor
            ));
        });
    }

    /**
     * Reorder user's social links.
     */
    public function reorderSocialLinks(
        User $targetUser,
        array $orders,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        return DB::transaction(function () use ($targetUser, $orders, $actor, $ip, $userAgent) {
            $normalizedOrders = [];

            if (isset($orders['ids']) && is_array($orders['ids'])) {
                foreach ($orders['ids'] as $index => $id) {
                    $normalizedOrders[$id] = $index + 1;
                }
            } elseif (isset($orders['orders']) && is_array($orders['orders'])) {
                foreach ($orders['orders'] as $item) {
                    if (isset($item['id'], $item['display_order'])) {
                        $normalizedOrders[$item['id']] = (int) $item['display_order'];
                    }
                }
            } elseif (isset($orders['items']) && is_array($orders['items'])) {
                foreach ($orders['items'] as $item) {
                    if (isset($item['id'], $item['display_order'])) {
                        $normalizedOrders[$item['id']] = (int) $item['display_order'];
                    }
                }
            }

            foreach ($normalizedOrders as $id => $order) {
                ProfileSocialLink::where('id', $id)
                    ->where('user_id', $targetUser->id)
                    ->update(['display_order' => $order]);
            }

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'SOCIAL_LINK_REORDERED',
                'entity_type' => ProfileSocialLink::class,
                'entity_id' => $targetUser->id,
                'old_values' => null,
                'new_values' => ['reordered' => $normalizedOrders],
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['social_links'],
                actor: $actor
            ));

            return $this->getSocialLinks($targetUser, $actor);
        });
    }

    /**
     * Keep UserProfile `social_links` JSON column in sync.
     */
    protected function syncProfileJsonMap(User $user): void
    {
        if ($user->profile) {
            $map = $user->socialLinks()
                ->where('is_visible', true)
                ->pluck('url', 'platform')
                ->toArray();

            $user->profile->update(['social_links' => $map]);
        }
    }
}
