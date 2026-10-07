<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BlockedUser;
use App\Models\FriendList;
use App\Models\FriendListMember;
use App\Models\Friendship;
use App\Models\User;
use App\Models\UserFollower;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FriendshipService
{
    public function __construct(
        protected RealtimeServiceInterface $realtimeService,
        protected NotificationServiceInterface $notificationService,
        protected ProfilePrivacyService $privacyService,
        protected CacheServiceInterface $cacheService
    ) {}

    /**
     * Determine relationship state between viewer and target.
     * Supported states: SELF, NONE, REQUEST_SENT, REQUEST_RECEIVED, FRIENDS, REQUEST_REJECTED, REQUEST_CANCELLED, BLOCKED, BLOCKED_BY_USER.
     */
    public function getRelationshipState(User $viewer, User $target): string
    {
        if ($viewer->id === $target->id) {
            return 'SELF';
        }

        // 1. Check if viewer blocked target
        $blockedByViewer = Friendship::where('user_id', $viewer->id)
            ->where('friend_id', $target->id)
            ->where('status', Friendship::STATUS_BLOCKED)
            ->exists() || BlockedUser::active()
            ->where('user_id', $viewer->id)
            ->where('identifier', (string) $target->id)
            ->exists();

        if ($blockedByViewer) {
            return 'BLOCKED';
        }

        // 2. Check if target blocked viewer
        $blockedByTarget = Friendship::where('user_id', $target->id)
            ->where('friend_id', $viewer->id)
            ->where('status', Friendship::STATUS_BLOCKED)
            ->exists() || BlockedUser::active()
            ->where('user_id', $target->id)
            ->where('identifier', (string) $viewer->id)
            ->exists();

        if ($blockedByTarget) {
            return 'BLOCKED_BY_USER';
        }

        // 3. Check friendship record
        $friendship = Friendship::where(function ($q) use ($viewer, $target) {
            $q->where('user_id', $viewer->id)->where('friend_id', $target->id);
        })->orWhere(function ($q) use ($viewer, $target) {
            $q->where('user_id', $target->id)->where('friend_id', $viewer->id);
        })->first();

        if (! $friendship) {
            return 'NONE';
        }

        return match ($friendship->status) {
            Friendship::STATUS_ACCEPTED => 'FRIENDS',
            Friendship::STATUS_PENDING => ($friendship->user_id === $viewer->id) ? 'REQUEST_SENT' : 'REQUEST_RECEIVED',
            Friendship::STATUS_DECLINED => 'REQUEST_REJECTED',
            Friendship::STATUS_CANCELLED => 'REQUEST_CANCELLED',
            Friendship::STATUS_BLOCKED => ($friendship->user_id === $viewer->id) ? 'BLOCKED' : 'BLOCKED_BY_USER',
            default => 'NONE',
        };
    }

    /**
     * Send a friend request with concurrency protection, duplicate validation, and privacy checks.
     */
    public function sendRequest(User $user, int $friendId): Friendship
    {
        if ($user->id === $friendId) {
            throw new InvalidArgumentException('You cannot send a friend request to yourself.');
        }

        $friend = User::findOrFail($friendId);

        // Check blocking
        if ($this->privacyService->isBlocked($friend, $user)) {
            throw new InvalidArgumentException('Action not allowed.');
        }

        // Check recipient privacy setting
        $privacyWhoCanSend = 'everyone';
        if ($friend->privacySettings && ! empty($friend->privacySettings->who_can_send_friend_requests)) {
            $privacyWhoCanSend = strtolower(trim($friend->privacySettings->who_can_send_friend_requests));
        } elseif ($friend->settings && ! empty($friend->settings->who_can_send_friend_requests)) {
            $privacyWhoCanSend = strtolower(trim($friend->settings->who_can_send_friend_requests));
        } else {
            // Direct query fallback for robust testing and caching consistency
            $dbPrivacy = DB::table('privacy_settings')->where('user_id', $friend->id)->value('who_can_send_friend_requests');
            if ($dbPrivacy) {
                $privacyWhoCanSend = strtolower(trim($dbPrivacy));
            } else {
                $dbSetting = DB::table('user_settings')->where('user_id', $friend->id)->value('who_can_send_friend_requests');
                if ($dbSetting) {
                    $privacyWhoCanSend = strtolower(trim($dbSetting));
                }
            }
        }

        if ($privacyWhoCanSend === 'nobody') {
            throw new InvalidArgumentException('This user is not accepting friend requests.');
        }

        if ($privacyWhoCanSend === 'friends_of_friends') {
            $mutualCount = $this->getMutualCount($user, $friend);
            if ($mutualCount === 0) {
                throw new InvalidArgumentException('This user only accepts friend requests from friends of mutual friends.');
            }
        }

        return DB::transaction(function () use ($user, $friend, $friendId) {
            // Find existing relationship with row-level lock
            $existing = Friendship::where(function ($q) use ($user, $friendId) {
                $q->where('user_id', $user->id)->where('friend_id', $friendId);
            })->orWhere(function ($q) use ($user, $friendId) {
                $q->where('user_id', $friendId)->where('friend_id', $user->id);
            })->lockForUpdate()->first();

            if ($existing) {
                if ($existing->status === Friendship::STATUS_ACCEPTED) {
                    throw new InvalidArgumentException('You are already friends with this user.');
                }

                if ($existing->status === Friendship::STATUS_BLOCKED) {
                    throw new InvalidArgumentException('Action not allowed.');
                }

                // If cross-request already sent by the other user, automatically accept it
                if ($existing->status === Friendship::STATUS_PENDING) {
                    if ($existing->user_id === $friendId && $existing->friend_id === $user->id) {
                        $existing->update([
                            'status' => Friendship::STATUS_ACCEPTED,
                            'accepted_at' => now(),
                            'interacted_at' => now(),
                        ]);

                        $this->dispatchAcceptedEvents($existing, $user, $friend);

                        return $existing;
                    }

                    throw new InvalidArgumentException('A friend request is already pending.');
                }

                // Reset declined or cancelled request
                $existing->update([
                    'user_id' => $user->id,
                    'friend_id' => $friendId,
                    'status' => Friendship::STATUS_PENDING,
                    'requested_at' => now(),
                    'declined_at' => null,
                ]);

                $friendship = $existing;
            } else {
                $friendship = Friendship::create([
                    'user_id' => $user->id,
                    'friend_id' => $friendId,
                    'status' => Friendship::STATUS_PENDING,
                    'requested_at' => now(),
                ]);
            }

            // Real-time Event broadcasting
            $senderPayload = [
                'type' => 'friend.request.sent',
                'friendship_id' => $friendship->id,
                'target_id' => $friend->id,
                'target_name' => $friend->name,
                'target_username' => $friend->username,
                'timestamp' => now()->toIso8601String(),
            ];

            $recipientPayload = [
                'type' => 'friend.request.received',
                'friendship_id' => $friendship->id,
                'sender' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar_url' => $user->profile?->avatar_url,
                    'is_verified' => (bool) $user->is_verified,
                    'mutual_friends_count' => $this->getMutualCount($user, $friend),
                ],
                'requested_at' => now()->toIso8601String(),
            ];

            $this->realtimeService->broadcast("private-user.{$user->id}", 'friend.request.sent', $senderPayload);
            $this->realtimeService->broadcast("private-user.{$friend->id}", 'friend.request.received', $recipientPayload);

            // Realtime counter update for recipient
            $this->broadcastCounterUpdate($friend);

            // In-app notification
            $this->notificationService->send($friend->id, 'friend.request', [
                'title' => 'New Friend Request',
                'message' => "{$user->name} sent you a friend request.",
                'sender_id' => $user->id,
                'sender_name' => $user->name,
                'sender_avatar' => $user->profile?->avatar_url,
                'action_url' => '/friends',
            ], ['database', 'broadcast']);

            // Audit log
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'friend.request.sent',
                'entity_type' => Friendship::class,
                'entity_id' => $friendship->id,
                'new_values' => ['friend_id' => $friendId, 'status' => Friendship::STATUS_PENDING],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $friendship;
        });
    }

    /**
     * Accept a pending friend request received by this user.
     */
    public function acceptRequest(User $user, int $senderId): Friendship
    {
        return DB::transaction(function () use ($user, $senderId) {
            $friendship = Friendship::where('user_id', $senderId)
                ->where('friend_id', $user->id)
                ->where('status', Friendship::STATUS_PENDING)
                ->lockForUpdate()
                ->firstOrFail();

            $friendship->update([
                'status' => Friendship::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'interacted_at' => now(),
            ]);

            $sender = User::findOrFail($senderId);
            $this->dispatchAcceptedEvents($friendship, $user, $sender);

            return $friendship;
        });
    }

    /**
     * Decline a pending friend request received by this user.
     */
    public function declineRequest(User $user, int $senderId): Friendship
    {
        return DB::transaction(function () use ($user, $senderId) {
            $friendship = Friendship::where('user_id', $senderId)
                ->where('friend_id', $user->id)
                ->where('status', Friendship::STATUS_PENDING)
                ->lockForUpdate()
                ->firstOrFail();

            $friendship->update([
                'status' => Friendship::STATUS_DECLINED,
                'declined_at' => now(),
                'interacted_at' => now(),
            ]);

            // Broadcast events
            $this->realtimeService->broadcast("private-user.{$senderId}", 'friend.request.rejected', [
                'target_id' => $user->id,
                'message' => 'Friend request declined.',
            ]);

            $this->broadcastCounterUpdate($user);
            $this->broadcastCounterUpdate(User::find($senderId));

            // Audit log
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'friend.request.rejected',
                'entity_type' => Friendship::class,
                'entity_id' => $friendship->id,
                'new_values' => ['sender_id' => $senderId, 'status' => Friendship::STATUS_DECLINED],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $friendship;
        });
    }

    /**
     * Cancel an outgoing pending friend request.
     */
    public function cancelRequest(User $user, int $targetId): bool
    {
        return DB::transaction(function () use ($user, $targetId) {
            $friendship = Friendship::where('user_id', $user->id)
                ->where('friend_id', $targetId)
                ->where('status', Friendship::STATUS_PENDING)
                ->lockForUpdate()
                ->first();

            if (! $friendship) {
                return false;
            }

            $friendship->delete();

            // Broadcast cancellation event
            $this->realtimeService->broadcast("private-user.{$targetId}", 'friend.request.cancelled', [
                'sender_id' => $user->id,
                'message' => 'Friend request was cancelled.',
            ]);

            $this->realtimeService->broadcast("private-user.{$user->id}", 'friend.request.cancelled', [
                'target_id' => $targetId,
            ]);

            $targetUser = User::find($targetId);
            if ($targetUser) {
                $this->broadcastCounterUpdate($targetUser);
            }
            $this->broadcastCounterUpdate($user);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'friend.request.cancelled',
                'entity_type' => Friendship::class,
                'entity_id' => $friendship->id,
                'new_values' => ['target_id' => $targetId],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return true;
        });
    }

    /**
     * Unfriend a user or remove pending relationship.
     */
    public function unfriend(User $user, int $friendId): bool
    {
        return DB::transaction(function () use ($user, $friendId) {
            $friendship = Friendship::where(function ($q) use ($user, $friendId) {
                $q->where('user_id', $user->id)->where('friend_id', $friendId);
            })->orWhere(function ($q) use ($user, $friendId) {
                $q->where('user_id', $friendId)->where('friend_id', $user->id);
            })->lockForUpdate()->first();

            if (! $friendship) {
                return false;
            }

            // Remove friend from custom lists
            $userListIds = FriendList::where('user_id', $user->id)->pluck('id');
            FriendListMember::whereIn('friend_list_id', $userListIds)->where('friend_id', $friendId)->delete();

            $targetListIds = FriendList::where('user_id', $friendId)->pluck('id');
            FriendListMember::whereIn('friend_list_id', $targetListIds)->where('friend_id', $user->id)->delete();

            $friendship->delete();

            // Real-time broadcast
            $this->realtimeService->broadcast("private-user.{$friendId}", 'friend.removed', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->realtimeService->broadcast("private-user.{$user->id}", 'friend.removed', [
                'friend_id' => $friendId,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->broadcastCounterUpdate($user);
            $friendUser = User::find($friendId);
            if ($friendUser) {
                $this->broadcastCounterUpdate($friendUser);
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'friend.removed',
                'entity_type' => Friendship::class,
                'entity_id' => $friendship->id,
                'new_values' => ['friend_id' => $friendId],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return true;
        });
    }

    /**
     * Follow a user.
     */
    public function followUser(User $user, int $targetId): UserFollower
    {
        if ($user->id === $targetId) {
            throw new InvalidArgumentException('You cannot follow yourself.');
        }

        $target = User::findOrFail($targetId);

        if ($this->privacyService->isBlocked($target, $user)) {
            throw new InvalidArgumentException('Action not allowed.');
        }

        $targetPrivacy = $target->privacySettings;
        $whoCanFollow = strtolower(trim($targetPrivacy?->who_can_follow_me ?? 'everyone'));

        if ($whoCanFollow === 'nobody') {
            throw new InvalidArgumentException('This user does not allow followers.');
        }

        if ($whoCanFollow === 'friends') {
            if (! in_array($targetId, $user->getFriendIds(), true)) {
                throw new InvalidArgumentException('Only friends can follow this user.');
            }
        }

        $follow = UserFollower::firstOrCreate([
            'user_id' => $targetId,
            'follower_id' => $user->id,
        ]);

        $this->realtimeService->broadcast("private-user.{$targetId}", 'friend.followed', [
            'follower_id' => $user->id,
            'follower_name' => $user->name,
            'follower_avatar' => $user->profile?->avatar_url,
            'timestamp' => now()->toIso8601String(),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.followed',
            'entity_type' => UserFollower::class,
            'entity_id' => $follow->id,
            'new_values' => ['target_id' => $targetId],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $follow;
    }

    /**
     * Unfollow a user.
     */
    public function unfollowUser(User $user, int $targetId): bool
    {
        $deleted = (bool) UserFollower::where('user_id', $targetId)
            ->where('follower_id', $user->id)
            ->delete();

        if ($deleted) {
            $this->realtimeService->broadcast("private-user.{$targetId}", 'friend.unfollowed', [
                'follower_id' => $user->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user.unfollowed',
                'entity_type' => UserFollower::class,
                'entity_id' => 0,
                'new_values' => ['target_id' => $targetId],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return $deleted;
    }

    /**
     * Block a user.
     */
    public function blockUser(User $user, int $targetId): Friendship
    {
        if ($user->id === $targetId) {
            throw new InvalidArgumentException('You cannot block yourself.');
        }

        User::findOrFail($targetId);

        return DB::transaction(function () use ($user, $targetId) {
            // Remove follow relations in both directions
            UserFollower::where(function ($q) use ($user, $targetId) {
                $q->where('user_id', $user->id)->where('follower_id', $targetId);
            })->orWhere(function ($q) use ($user, $targetId) {
                $q->where('user_id', $targetId)->where('follower_id', $user->id);
            })->delete();

            // Remove from custom friend lists
            $userListIds = FriendList::where('user_id', $user->id)->pluck('id');
            FriendListMember::whereIn('friend_list_id', $userListIds)->where('friend_id', $targetId)->delete();

            // Sync with BlockedUser model
            BlockedUser::firstOrCreate([
                'user_id' => $user->id,
                'type' => 'user',
                'identifier' => (string) $targetId,
            ], [
                'reason' => 'User blocked from friend system',
                'status' => 'active',
            ]);

            // Update or create friendship as blocked
            $friendship = Friendship::where(function ($q) use ($user, $targetId) {
                $q->where('user_id', $user->id)->where('friend_id', $targetId);
            })->orWhere(function ($q) use ($user, $targetId) {
                $q->where('user_id', $targetId)->where('friend_id', $user->id);
            })->lockForUpdate()->first();

            if ($friendship) {
                $friendship->update([
                    'user_id' => $user->id,
                    'friend_id' => $targetId,
                    'status' => Friendship::STATUS_BLOCKED,
                ]);
            } else {
                $friendship = Friendship::create([
                    'user_id' => $user->id,
                    'friend_id' => $targetId,
                    'status' => Friendship::STATUS_BLOCKED,
                ]);
            }

            // Real-time notification to target and user
            $this->realtimeService->broadcast("private-user.{$user->id}", 'friend.blocked', [
                'blocked_id' => $targetId,
            ]);

            $this->realtimeService->broadcast("private-user.{$targetId}", 'friend.blocked', [
                'by_user_id' => $user->id,
            ]);

            $this->broadcastCounterUpdate($user);
            $targetUser = User::find($targetId);
            if ($targetUser) {
                $this->broadcastCounterUpdate($targetUser);
            }

            $profileService = app(ProfileService::class);
            $profileService->invalidateProfileCache($user);
            if ($targetUser) {
                $profileService->invalidateProfileCache($targetUser);
            }
            $profileService->invalidateRelationshipCache($user->id, $targetId);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user.blocked',
                'entity_type' => Friendship::class,
                'entity_id' => $friendship->id,
                'new_values' => ['target_id' => $targetId],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $friendship;
        });
    }

    /**
     * Unblock a user.
     */
    public function unblockUser(User $user, int $targetId): bool
    {
        return DB::transaction(function () use ($user, $targetId) {
            $deleted = (bool) Friendship::where('user_id', $user->id)
                ->where('friend_id', $targetId)
                ->where('status', Friendship::STATUS_BLOCKED)
                ->delete();

            BlockedUser::where('user_id', $user->id)
                ->where('identifier', (string) $targetId)
                ->delete();

            $this->realtimeService->broadcast("private-user.{$user->id}", 'friend.unblocked', [
                'unblocked_id' => $targetId,
            ]);

            $this->broadcastCounterUpdate($user);
            $targetUser = User::find($targetId);
            if ($targetUser) {
                $this->broadcastCounterUpdate($targetUser);
            }

            $profileService = app(ProfileService::class);
            $profileService->invalidateProfileCache($user);
            if ($targetUser) {
                $profileService->invalidateProfileCache($targetUser);
            }
            $profileService->invalidateRelationshipCache($user->id, $targetId);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user.unblocked',
                'entity_type' => User::class,
                'entity_id' => $targetId,
                'new_values' => ['unblocked_id' => $targetId],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $deleted;
        });
    }

    /**
     * Bulk accept pending requests.
     */
    public function bulkAccept(User $user, array $senderIds): array
    {
        $results = ['success' => [], 'failed' => []];

        foreach ($senderIds as $senderId) {
            try {
                $this->acceptRequest($user, (int) $senderId);
                $results['success'][] = (int) $senderId;
            } catch (Exception $e) {
                $results['failed'][] = ['id' => (int) $senderId, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Bulk decline pending requests.
     */
    public function bulkDecline(User $user, array $senderIds): array
    {
        $results = ['success' => [], 'failed' => []];

        foreach ($senderIds as $senderId) {
            try {
                $this->declineRequest($user, (int) $senderId);
                $results['success'][] = (int) $senderId;
            } catch (Exception $e) {
                $results['failed'][] = ['id' => (int) $senderId, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Bulk cancel or delete requests.
     */
    public function bulkDelete(User $user, array $senderIds): array
    {
        $results = ['success' => [], 'failed' => []];

        foreach ($senderIds as $senderId) {
            try {
                $this->unfriend($user, (int) $senderId);
                $results['success'][] = (int) $senderId;
            } catch (Exception $e) {
                $results['failed'][] = ['id' => (int) $senderId, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Bulk block users.
     */
    public function bulkBlock(User $user, array $targetIds): array
    {
        $results = ['success' => [], 'failed' => []];

        foreach ($targetIds as $targetId) {
            try {
                $this->blockUser($user, (int) $targetId);
                $results['success'][] = (int) $targetId;
            } catch (Exception $e) {
                $results['failed'][] = ['id' => (int) $targetId, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Get paginated friends list with rich filtering, search, and sorting.
     */
    public function getFriends(
        User $user,
        int|array $filtersOrPerPage = 20,
        string $sort = 'recently_added',
        ?string $search = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        $filters = [];
        if (is_array($filtersOrPerPage)) {
            $filters = $filtersOrPerPage;
        } else {
            $perPage = $filtersOrPerPage;
        }

        if (isset($filters['search']) && ! $search) {
            $search = $filters['search'];
        }
        if (isset($filters['sort']) && $sort === 'recently_added') {
            $sort = $filters['sort'];
        }

        $friendIds = $user->getFriendIds();

        $query = User::whereIn('id', $friendIds)->with(['profile', 'privacySettings']);

        // Search query
        if ($search && trim($search) !== '') {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($p) use ($search) {
                        $p->where('city', 'like', "%{$search}%")
                            ->orWhere('country', 'like', "%{$search}%")
                            ->orWhere('headline', 'like', "%{$search}%");
                    });
            });
        }

        // Filters
        $filterType = $filters['filter'] ?? 'all';

        switch ($filterType) {
            case 'online':
                $onlineIds = array_filter($friendIds, fn ($id) => $this->cacheService->isUserOnline($id));
                $query->whereIn('id', count($onlineIds) > 0 ? $onlineIds : [0]);
                break;
            case 'verified':
                $query->where('is_verified', true);
                break;
            case 'close_friends':
                $closeIds = Friendship::where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)->orWhere('friend_id', $user->id);
                })->where('status', Friendship::STATUS_ACCEPTED)
                    ->where('is_close_friend', true)
                    ->get()
                    ->map(fn ($f) => $f->user_id === $user->id ? $f->friend_id : $f->user_id)
                    ->toArray();
                $query->whereIn('id', count($closeIds) > 0 ? $closeIds : [0]);
                break;
            case 'favorites':
                $favIds = Friendship::where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)->orWhere('friend_id', $user->id);
                })->where('status', Friendship::STATUS_ACCEPTED)
                    ->where('is_favorite', true)
                    ->get()
                    ->map(fn ($f) => $f->user_id === $user->id ? $f->friend_id : $f->user_id)
                    ->toArray();
                $query->whereIn('id', count($favIds) > 0 ? $favIds : [0]);
                break;
            case 'same_city':
                $city = $user->profile?->city;
                if ($city) {
                    $query->whereHas('profile', fn ($p) => $p->where('city', $city));
                }
                break;
            case 'same_country':
                $country = $user->profile?->country ?? $user->country;
                if ($country) {
                    $query->whereHas('profile', fn ($p) => $p->where('country', $country));
                }
                break;
            case 'following':
                $query->whereIn('id', $user->getFollowingIds());
                break;
            case 'followers':
                $query->whereIn('id', $user->getFollowerIds());
                break;
        }

        // Sorting
        switch ($sort) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'recently_active':
                $query->orderByDesc('updated_at');
                break;
            case 'recently_added':
            default:
                $query->orderByDesc('id');
                break;
        }

        $paginator = $query->paginate($perPage);

        // Decorate models with mutual counts, presence, and relationship state
        $paginator->getCollection()->transform(function ($friend) use ($user) {
            $friend->mutual_count = $this->getMutualCount($user, $friend);
            $friend->is_online = $this->cacheService->isUserOnline($friend->id);
            $friend->last_seen = $this->cacheService->getUserLastSeen($friend->id);
            $friend->is_following = $user->isFollowing($friend->id);

            // Fetch flags from friendship
            $fs = Friendship::where(function ($q) use ($user, $friend) {
                $q->where('user_id', $user->id)->where('friend_id', $friend->id);
            })->orWhere(function ($q) use ($user, $friend) {
                $q->where('user_id', $friend->id)->where('friend_id', $user->id);
            })->first();

            $friend->is_favorite = (bool) ($fs?->is_favorite ?? false);
            $friend->is_close_friend = (bool) ($fs?->is_close_friend ?? false);
            $friend->is_restricted = (bool) ($fs?->is_restricted ?? false);

            return $friend;
        });

        return $paginator;
    }

    /**
     * Get pending received friend requests.
     */
    public function getPendingRequests(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $paginator = Friendship::where('friend_id', $user->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->with(['user.profile'])
            ->orderByDesc('requested_at')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function ($request) use ($user) {
            if ($request->user) {
                $request->user->mutual_count = $this->getMutualCount($user, $request->user);
                $request->user->is_online = $this->cacheService->isUserOnline($request->user->id);
                $request->user->last_seen = $this->cacheService->getUserLastSeen($request->user->id);
            }

            return $request;
        });

        return $paginator;
    }

    /**
     * Get sent friend requests awaiting acceptance.
     */
    public function getSentRequests(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $paginator = Friendship::where('user_id', $user->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->with(['friend.profile'])
            ->orderByDesc('requested_at')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function ($request) use ($user) {
            if ($request->friend) {
                $request->friend->mutual_count = $this->getMutualCount($user, $request->friend);
                $request->friend->is_online = $this->cacheService->isUserOnline($request->friend->id);
                $request->friend->last_seen = $this->cacheService->getUserLastSeen($request->friend->id);
            }

            return $request;
        });

        return $paginator;
    }

    /**
     * Calculate mutual friend IDs between two users.
     */
    public function getMutualFriendIds(int $userId1, int $userId2): array
    {
        if ($userId1 === $userId2) {
            return [];
        }

        $user1 = User::find($userId1);
        $user2 = User::find($userId2);

        if (! $user1 || ! $user2) {
            return [];
        }

        return array_values(array_intersect($user1->getFriendIds(), $user2->getFriendIds()));
    }

    /**
     * Get count of mutual friends between two users.
     */
    public function getMutualCount(User $user, User $target): int
    {
        return count($this->getMutualFriendIds($user->id, $target->id));
    }

    /**
     * Get paginated mutual friends list with search and privacy checking.
     */
    public function getMutualFriends(User $user, User $target, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $mutualIds = $this->getMutualFriendIds($user->id, $target->id);

        $query = User::whereIn('id', count($mutualIds) > 0 ? $mutualIds : [0])
            ->with(['profile']);

        if ($search && trim($search) !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($mutual) {
            $mutual->is_online = $this->cacheService->isUserOnline($mutual->id);
            $mutual->last_seen = $this->cacheService->getUserLastSeen($mutual->id);

            return $mutual;
        });

        return $paginator;
    }

    /**
     * Enterprise Recommendation Engine: Calculates smart friend suggestions based on:
     * - Mutual friends weight (10 pts/each)
     * - Shared groups weight (4 pts/each)
     * - Shared followed pages weight (3 pts/each)
     * - Same city / location weight (5 pts)
     * - Follower / Following signal (2 pts)
     */
    public function getSuggestions(User $user, int $limit = 20): Collection
    {
        $friendIds = $user->getFriendIds();
        $blockedIds = BlockedUser::where('user_id', $user->id)->pluck('identifier')->toArray();

        // Exclude self, existing friends, pending requests, and blocked users
        $pendingSent = Friendship::where('user_id', $user->id)->pluck('friend_id')->toArray();
        $pendingReceived = Friendship::where('friend_id', $user->id)->pluck('user_id')->toArray();

        $excludedIds = array_unique(array_merge(
            [$user->id],
            $friendIds,
            $blockedIds,
            $pendingSent,
            $pendingReceived
        ));

        // Fetch user context for signal scoring
        $userCity = $user->profile?->city;
        $userCountry = $user->profile?->country ?? $user->country;

        $userGroupIds = DB::table('group_members')->where('user_id', $user->id)->pluck('group_id')->toArray();
        $userPageIds = DB::table('page_followers')->where('user_id', $user->id)->pluck('page_id')->toArray();

        // Candidates: fetch active users outside the excluded pool
        $candidates = User::whereNotIn('id', $excludedIds)
            ->where('status', 'active')
            ->with(['profile', 'privacySettings'])
            ->limit(100)
            ->get();

        $scored = $candidates->map(function ($candidate) use ($user, $userCity, $userCountry, $userGroupIds, $userPageIds) {
            $score = 0;
            $reasons = [];

            // 1. Mutual friends
            $mutualCount = $this->getMutualCount($user, $candidate);
            if ($mutualCount > 0) {
                $score += ($mutualCount * 10);
                $reasons[] = "{$mutualCount} mutual friend".($mutualCount > 1 ? 's' : '');
            }

            // 2. Shared groups
            if (! empty($userGroupIds)) {
                $sharedGroups = DB::table('group_members')
                    ->where('user_id', $candidate->id)
                    ->whereIn('group_id', $userGroupIds)
                    ->count();

                if ($sharedGroups > 0) {
                    $score += ($sharedGroups * 4);
                    $reasons[] = "{$sharedGroups} shared group".($sharedGroups > 1 ? 's' : '');
                }
            }

            // 3. Shared pages
            if (! empty($userPageIds)) {
                $sharedPages = DB::table('page_followers')
                    ->where('user_id', $candidate->id)
                    ->whereIn('page_id', $userPageIds)
                    ->count();

                if ($sharedPages > 0) {
                    $score += ($sharedPages * 3);
                    $reasons[] = "Follows {$sharedPages} same page".($sharedPages > 1 ? 's' : '');
                }
            }

            // 4. Location match (respecting candidate location privacy)
            $candCity = $candidate->profile?->city;
            $cityPrivacy = strtolower(trim($candidate->privacySettings?->city_privacy ?? 'public'));
            $isCityAllowed = ! in_array($cityPrivacy, ['only_me', 'private'], true);

            if ($isCityAllowed && $userCity && $candCity && strcasecmp($userCity, $candCity) === 0) {
                $score += 5;
                $reasons[] = "Lives in {$candCity}";
            } elseif ($userCountry && strcasecmp($userCountry, $candidate->country ?? '') === 0) {
                $score += 2;
                $reasons[] = "From {$userCountry}";
            }

            // 5. Following signal
            if ($user->isFollowing($candidate->id)) {
                $score += 3;
                $reasons[] = 'You follow this person';
            }

            if (empty($reasons)) {
                $reasons[] = 'Suggested for you';
            }

            $candidate->suggestion_score = $score;
            $candidate->suggestion_reasons = $reasons;
            $candidate->mutual_count = $mutualCount;
            $candidate->is_online = $this->cacheService->isUserOnline($candidate->id);
            $candidate->last_seen = $this->cacheService->getUserLastSeen($candidate->id);

            return $candidate;
        });

        return $scored->sortByDesc('suggestion_score')->take($limit)->values();
    }

    /**
     * Get birthdays of accepted friends categorized by: today, upcoming (next 30 days), and recent (past 14 days).
     */
    public function getBirthdays(User $user): array
    {
        $friendIds = $user->getFriendIds();
        $friends = User::whereIn('id', $friendIds)->with(['profile'])->get();

        $today = Carbon::today();
        $birthdaysToday = [];
        $upcomingBirthdays = [];
        $recentBirthdays = [];

        foreach ($friends as $friend) {
            $dob = $friend->birth_date ?? $friend->profile?->birth_date;
            if (! $dob) {
                continue;
            }

            $bday = Carbon::parse($dob);
            $isToday = ((int) $bday->month === (int) $today->month && (int) $bday->day === (int) $today->day);
            $thisYearBday = Carbon::create($today->year, $bday->month, $bday->day)->startOfDay();

            $diffInDays = (int) $today->diffInDays($thisYearBday, false);

            $friendData = [
                'id' => $friend->id,
                'name' => $friend->name,
                'username' => $friend->username,
                'avatar_url' => $friend->profile?->avatar_url,
                'birth_date' => $bday->format('F d'),
                'is_today' => $isToday,
                'days_away' => $diffInDays,
            ];

            if ($isToday) {
                $birthdaysToday[] = $friendData;
            } elseif ($diffInDays > 0 && $diffInDays <= 30) {
                $upcomingBirthdays[] = $friendData;
            } elseif ($diffInDays < 0 && $diffInDays >= -14) {
                $recentBirthdays[] = $friendData;
            }
        }

        usort($upcomingBirthdays, fn ($a, $b) => $a['days_away'] <=> $b['days_away']);
        usort($recentBirthdays, fn ($a, $b) => $b['days_away'] <=> $a['days_away']);

        return [
            'today' => $birthdaysToday,
            'upcoming' => $upcomingBirthdays,
            'recent' => $recentBirthdays,
            'counts' => [
                'today' => count($birthdaysToday),
                'upcoming' => count($upcomingBirthdays),
                'recent' => count($recentBirthdays),
            ],
        ];
    }

    /**
     * Create a custom friend list.
     */
    public function createList(User $user, array $data): FriendList
    {
        $name = trim($data['name']);
        $slug = Str::slug($name);

        $originalSlug = $slug;
        $counter = 1;
        while (FriendList::where('user_id', $user->id)->where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $list = FriendList::create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => $slug,
            'type' => $data['type'] ?? 'custom',
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);

        $this->realtimeService->broadcast("private-user.{$user->id}", 'friend.list.created', [
            'list' => $list,
        ]);

        return $list;
    }

    /**
     * Update a custom friend list.
     */
    public function updateList(User $user, int $listId, array $data): FriendList
    {
        $list = FriendList::where('user_id', $user->id)->where('id', $listId)->firstOrFail();

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = trim($data['name']);
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        $list->update($updateData);

        $this->realtimeService->broadcast("private-user.{$user->id}", 'friend.list.updated', [
            'list' => $list,
        ]);

        return $list;
    }

    /**
     * Delete a custom friend list.
     */
    public function deleteList(User $user, int $listId): bool
    {
        $list = FriendList::where('user_id', $user->id)->where('id', $listId)->firstOrFail();

        if ($list->is_system) {
            throw new InvalidArgumentException('System friend lists cannot be deleted.');
        }

        FriendListMember::where('friend_list_id', $list->id)->delete();
        $list->delete();

        $this->realtimeService->broadcast("private-user.{$user->id}", 'friend.list.deleted', [
            'list_id' => $listId,
        ]);

        return true;
    }

    /**
     * Add a friend to a custom list.
     */
    public function addMemberToList(User $user, int $listId, int $friendId): bool
    {
        $list = FriendList::where('user_id', $user->id)->where('id', $listId)->firstOrFail();

        // Ensure target is an accepted friend
        if (! in_array($friendId, $user->getFriendIds(), true)) {
            throw new InvalidArgumentException('Target user is not in your friends list.');
        }

        FriendListMember::firstOrCreate([
            'friend_list_id' => $list->id,
            'friend_id' => $friendId,
        ]);

        return true;
    }

    /**
     * Remove a friend from a custom list.
     */
    public function removeMemberFromList(User $user, int $listId, int $friendId): bool
    {
        $list = FriendList::where('user_id', $user->id)->where('id', $listId)->firstOrFail();

        return (bool) FriendListMember::where('friend_list_id', $list->id)
            ->where('friend_id', $friendId)
            ->delete();
    }

    /**
     * Get all custom and system lists for user.
     */
    public function getLists(User $user): Collection
    {
        return FriendList::where('user_id', $user->id)
            ->withCount('members')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get paginated members of a custom list.
     */
    public function getListMembers(User $user, int $listId, int $perPage = 20): LengthAwarePaginator
    {
        $list = FriendList::where('user_id', $user->id)->where('id', $listId)->firstOrFail();

        $memberFriendIds = FriendListMember::where('friend_list_id', $list->id)->pluck('friend_id');

        $paginator = User::whereIn('id', $memberFriendIds)
            ->with(['profile'])
            ->paginate($perPage);

        $paginator->getCollection()->transform(function ($member) use ($user) {
            $member->is_online = $this->cacheService->isUserOnline($member->id);
            $member->last_seen = $this->cacheService->getUserLastSeen($member->id);
            $member->mutual_count = $this->getMutualCount($user, $member);

            return $member;
        });

        return $paginator;
    }

    /**
     * Toggle Favorite friend status.
     */
    public function toggleFavorite(User $user, int $friendId): bool
    {
        $friendship = Friendship::where(function ($q) use ($user, $friendId) {
            $q->where('user_id', $user->id)->where('friend_id', $friendId);
        })->orWhere(function ($q) use ($user, $friendId) {
            $q->where('user_id', $friendId)->where('friend_id', $user->id);
        })->where('status', Friendship::STATUS_ACCEPTED)->firstOrFail();

        $friendship->update(['is_favorite' => ! $friendship->is_favorite]);

        // Sync with user's system Favorites list
        $favList = FriendList::firstOrCreate([
            'user_id' => $user->id,
            'type' => FriendList::TYPE_FAVORITES,
        ], [
            'name' => 'Favorites',
            'slug' => 'favorites',
            'is_system' => true,
        ]);

        if ($friendship->is_favorite) {
            FriendListMember::firstOrCreate([
                'friend_list_id' => $favList->id,
                'friend_id' => $friendId,
            ]);
        } else {
            FriendListMember::where('friend_list_id', $favList->id)->where('friend_id', $friendId)->delete();
        }

        return (bool) $friendship->is_favorite;
    }

    /**
     * Toggle Close friend status.
     */
    public function toggleCloseFriend(User $user, int $friendId): bool
    {
        $friendship = Friendship::where(function ($q) use ($user, $friendId) {
            $q->where('user_id', $user->id)->where('friend_id', $friendId);
        })->orWhere(function ($q) use ($user, $friendId) {
            $q->where('user_id', $friendId)->where('friend_id', $user->id);
        })->where('status', Friendship::STATUS_ACCEPTED)->firstOrFail();

        $friendship->update(['is_close_friend' => ! $friendship->is_close_friend]);

        // Sync with user's system Close Friends list
        $closeList = FriendList::firstOrCreate([
            'user_id' => $user->id,
            'type' => FriendList::TYPE_CLOSE_FRIENDS,
        ], [
            'name' => 'Close Friends',
            'slug' => 'close-friends',
            'is_system' => true,
        ]);

        if ($friendship->is_close_friend) {
            FriendListMember::firstOrCreate([
                'friend_list_id' => $closeList->id,
                'friend_id' => $friendId,
            ]);
        } else {
            FriendListMember::where('friend_list_id', $closeList->id)->where('friend_id', $friendId)->delete();
        }

        return (bool) $friendship->is_close_friend;
    }

    /**
     * Toggle Restricted friend status.
     */
    public function toggleRestricted(User $user, int $friendId): bool
    {
        $friendship = Friendship::where(function ($q) use ($user, $friendId) {
            $q->where('user_id', $user->id)->where('friend_id', $friendId);
        })->orWhere(function ($q) use ($user, $friendId) {
            $q->where('user_id', $friendId)->where('friend_id', $user->id);
        })->where('status', Friendship::STATUS_ACCEPTED)->firstOrFail();

        $friendship->update(['is_restricted' => ! $friendship->is_restricted]);

        return (bool) $friendship->is_restricted;
    }

    /**
     * Real-time counters summary.
     */
    public function getCounters(User $user): array
    {
        $totalFriends = count($user->getFriendIds());

        $pendingRequests = Friendship::where('friend_id', $user->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->count();

        $sentRequests = Friendship::where('user_id', $user->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->count();

        $birthdays = $this->getBirthdays($user);

        return [
            'total_friends' => $totalFriends,
            'pending_requests' => $pendingRequests,
            'sent_requests' => $sentRequests,
            'birthdays_today' => $birthdays['counts']['today'] ?? 0,
            'unread_notifications' => $this->notificationService->getUnreadCount($user),
        ];
    }

    /**
     * Broadcast live counter updates to user WebSocket channel.
     */
    public function broadcastCounterUpdate(User $user): void
    {
        $counters = $this->getCounters($user);
        $this->realtimeService->broadcast("private-user.{$user->id}", 'friend.counters.updated', $counters);
    }

    /**
     * Internal helper to dispatch accepted friendship events, notifications, and logs.
     */
    protected function dispatchAcceptedEvents(Friendship $friendship, User $user, User $otherUser): void
    {
        // Real-time broadcast to both parties
        $this->realtimeService->broadcast("private-user.{$otherUser->id}", 'friend.request.accepted', [
            'friend' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->profile?->avatar_url,
                'is_verified' => (bool) $user->is_verified,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->realtimeService->broadcast("private-user.{$user->id}", 'friend.request.accepted', [
            'friend' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'username' => $otherUser->username,
                'avatar_url' => $otherUser->profile?->avatar_url,
                'is_verified' => (bool) $otherUser->is_verified,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->broadcastCounterUpdate($user);
        $this->broadcastCounterUpdate($otherUser);

        // Notification to request sender
        $this->notificationService->send($otherUser->id, 'friend.accepted', [
            'title' => 'Friend Request Accepted',
            'message' => "{$user->name} accepted your friend request.",
            'sender_id' => $user->id,
            'sender_name' => $user->name,
            'sender_avatar' => $user->profile?->avatar_url,
            'action_url' => "/profile/{$user->username}",
        ], ['database', 'broadcast']);

        // Audit log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'friend.request.accepted',
            'entity_type' => Friendship::class,
            'entity_id' => $friendship->id,
            'new_values' => ['friend_id' => $otherUser->id, 'status' => Friendship::STATUS_ACCEPTED],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
