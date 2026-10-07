<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface NotificationServiceInterface
{
    /**
     * Send notification across active channels (In-app, WebSocket, Email/Push via queue).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $channels
     */
    public function send(int $recipientId, string $type, array $data, array $channels = ['database']): void;

    /**
     * Retrieve paginated notifications for user with optional filter & category.
     */
    public function getNotifications(User $user, int $perPage = 15, ?string $filter = null, ?string $category = null): LengthAwarePaginator;

    /**
     * Get accurate unread count with cache acceleration.
     */
    public function getUnreadCount(User $user): int;

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(User $user, string $notificationId): bool;

    /**
     * Mark all notifications as read for user.
     */
    public function markAllAsRead(User $user): int;

    /**
     * Delete a single notification.
     */
    public function deleteNotification(User $user, string $notificationId): bool;

    /**
     * Delete all notifications for user.
     */
    public function deleteAllNotifications(User $user, bool $onlyRead = false): int;

    /**
     * Get user notification preferences.
     *
     * @return array<string, mixed>
     */
    public function getPreferences(User $user): array;

    /**
     * Update user notification preferences.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function updatePreferences(User $user, array $settings): array;
}
