<?php

namespace App\Services;

use App\Events\NotificationDeletedEvent;
use App\Events\NotificationReadAllEvent;
use App\Events\NotificationReadEvent;
use App\Jobs\DispatchNotificationJob;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Enterprise Notification Service:
 * Manages database persistence, caching, multi-device real-time state synchronization,
 * extensible type handling, and notification preference settings.
 */
class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        protected CacheServiceInterface $cacheService,
        protected QueueServiceInterface $queueService
    ) {}

    /**
     * Send an asynchronous notification via the high-priority queue.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $channels
     */
    public function send(int $recipientId, string $type, array $data, array $channels = ['database']): void
    {
        $this->queueService->dispatchHigh(new DispatchNotificationJob(
            recipientId: $recipientId,
            type: $type,
            data: $data,
            channels: $channels
        ));
    }

    /**
     * Retrieve paginated notifications with filters (all, unread, read) and categories.
     */
    public function getNotifications(User $user, int $perPage = 15, ?string $filter = null, ?string $category = null): LengthAwarePaginator
    {
        $query = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id);

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($category && $category !== 'all') {
            $query->where(function ($q) use ($category) {
                $q->where('category', $category)
                    ->orWhere('type', 'like', "%{$category}%");
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Get accurate unread count (cached in Redis, verified from DB).
     */
    public function getUnreadCount(User $user): int
    {
        $cacheKey = "notifications:user:{$user->id}";
        $cachedCount = $this->cacheService->get($cacheKey);

        if ($cachedCount !== null) {
            return (int) $cachedCount;
        }

        $count = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $this->cacheService->set($cacheKey, $count, 3600);

        return $count;
    }

    /**
     * Mark a single notification as read and sync in real-time.
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $updated = DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        if ($updated) {
            $cacheKey = "notifications:user:{$user->id}";
            $current = $this->cacheService->get($cacheKey);
            if ($current && (int) $current > 0) {
                $this->cacheService->decrement($cacheKey, 1);
            }
            $newCount = $this->getUnreadCount($user);

            try {
                broadcast(new NotificationReadEvent($user->id, $notificationId, $newCount));
            } catch (Exception $e) {
                Log::warning("Realtime broadcast error for notification.read: {$e->getMessage()}");
            }
        }

        return (bool) $updated;
    }

    /**
     * Mark all notifications as read for the user and sync across devices.
     */
    public function markAllAsRead(User $user): int
    {
        $updated = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        $this->cacheService->set("notifications:user:{$user->id}", 0, 3600);

        try {
            broadcast(new NotificationReadAllEvent($user->id));
        } catch (Exception $e) {
            Log::warning("Realtime broadcast error for notification.read_all: {$e->getMessage()}");
        }

        return $updated;
    }

    /**
     * Delete a single notification with IDOR protection and realtime sync.
     */
    public function deleteNotification(User $user, string $notificationId): bool
    {
        $notif = DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->first();

        if (! $notif) {
            return false;
        }

        $wasUnread = is_null($notif->read_at);

        DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->delete();

        $cacheKey = "notifications:user:{$user->id}";
        if ($wasUnread) {
            $current = $this->cacheService->get($cacheKey);
            if ($current && (int) $current > 0) {
                $this->cacheService->decrement($cacheKey, 1);
            }
        }

        $newCount = $this->getUnreadCount($user);

        try {
            broadcast(new NotificationDeletedEvent($user->id, $notificationId, $newCount));
        } catch (Exception $e) {
            Log::warning("Realtime broadcast error for notification.deleted: {$e->getMessage()}");
        }

        return true;
    }

    /**
     * Delete all notifications for the user (or only read ones).
     */
    public function deleteAllNotifications(User $user, bool $onlyRead = false): int
    {
        $query = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id);

        if ($onlyRead) {
            $query->whereNotNull('read_at');
        }

        $count = $query->delete();

        if (! $onlyRead) {
            $this->cacheService->set("notifications:user:{$user->id}", 0, 3600);
            try {
                broadcast(new NotificationReadAllEvent($user->id));
            } catch (Exception $e) {
                Log::warning("Realtime broadcast error for notification.read_all: {$e->getMessage()}");
            }
        }

        return $count;
    }

    /**
     * Retrieve user notification preferences.
     *
     * @return array<string, mixed>
     */
    public function getPreferences(User $user): array
    {
        $settings = NotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'email_notifications' => true,
                'sms_notifications' => false,
                'push_notifications' => true,
                'friend_request_alerts' => true,
                'comment_alerts' => true,
                'mention_alerts' => true,
                'security_alerts' => true,
            ]
        );

        return $settings->toArray();
    }

    /**
     * Update user notification preferences.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function updatePreferences(User $user, array $settings): array
    {
        $model = NotificationSetting::firstOrCreate(['user_id' => $user->id]);
        $allowed = [
            'email_notifications',
            'sms_notifications',
            'push_notifications',
            'friend_request_alerts',
            'comment_alerts',
            'mention_alerts',
            'security_alerts',
        ];
        $filtered = array_intersect_key($settings, array_flip($allowed));
        $model->update($filtered);

        return $model->fresh()->toArray();
    }
}
