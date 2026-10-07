<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\Notification\NotificationDto;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Enterprise Notification Controller:
 * Endpoints for retrieving notifications, real-time unread counter, read state updates,
 * deletion, and channel preferences across Web, Mobile, and Desktop clients.
 */
class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * List paginated notifications with filters (all, unread, read) and categories.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $filter = $request->query('filter', 'all');
        $category = $request->query('category', 'all');

        $notifications = $this->notificationService->getNotifications(
            user: $user,
            perPage: $perPage,
            filter: $filter,
            category: $category
        );

        $unreadCount = $this->notificationService->getUnreadCount($user);

        // Normalize every record using enterprise NotificationDto
        $items = collect($notifications->items())->map(function ($notification) {
            return NotificationDto::fromDatabase($notification)->toArray();
        })->values();

        return $this->successResponse(
            data: $items,
            message: 'Notifications retrieved successfully.',
            meta: [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $unreadCount,
                'filter' => $filter,
                'category' => $category,
            ]
        );
    }

    /**
     * Return accurate unread notification count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return $this->successResponse(
            data: ['unread_count' => $unreadCount],
            message: 'Unread notification count retrieved.'
        );
    }

    /**
     * Mark a specific notification as read with IDOR protection.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $success = $this->notificationService->markAsRead($user, $id);

        if (! $success) {
            return $this->errorResponse('Notification not found or already read.', 404);
        }

        $unreadCount = $this->notificationService->getUnreadCount($user);

        return $this->successResponse(
            data: [
                'id' => $id,
                'read' => true,
                'unread_count' => $unreadCount,
            ],
            message: 'Notification marked as read.'
        );
    }

    /**
     * Mark all notifications as read for the user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->markAllAsRead($user);

        return $this->successResponse(
            data: [
                'updated_count' => $count,
                'unread_count' => 0,
            ],
            message: 'All notifications marked as read.'
        );
    }

    /**
     * Delete a single notification with IDOR authorization check.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $success = $this->notificationService->deleteNotification($user, $id);

        if (! $success) {
            return $this->errorResponse('Notification not found or access denied.', 404);
        }

        $unreadCount = $this->notificationService->getUnreadCount($user);

        return $this->successResponse(
            data: [
                'id' => $id,
                'deleted' => true,
                'unread_count' => $unreadCount,
            ],
            message: 'Notification deleted successfully.'
        );
    }

    /**
     * Delete all notifications (or optionally only read ones).
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $onlyRead = filter_var($request->query('only_read', false), FILTER_VALIDATE_BOOLEAN);

        $count = $this->notificationService->deleteAllNotifications($user, $onlyRead);
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return $this->successResponse(
            data: [
                'deleted_count' => $count,
                'unread_count' => $unreadCount,
            ],
            message: $onlyRead ? 'Read notifications cleared.' : 'All notifications cleared.'
        );
    }

    /**
     * Get user notification preferences.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = $this->notificationService->getPreferences($user);

        return $this->successResponse(
            data: $preferences,
            message: 'Notification preferences retrieved.'
        );
    }

    /**
     * Update user notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'email_notifications' => ['sometimes', 'boolean'],
            'sms_notifications' => ['sometimes', 'boolean'],
            'push_notifications' => ['sometimes', 'boolean'],
            'friend_request_alerts' => ['sometimes', 'boolean'],
            'comment_alerts' => ['sometimes', 'boolean'],
            'mention_alerts' => ['sometimes', 'boolean'],
            'security_alerts' => ['sometimes', 'boolean'],
        ]);

        $updated = $this->notificationService->updatePreferences($user, $validated);

        return $this->successResponse(
            data: $updated,
            message: 'Notification preferences updated successfully.'
        );
    }
}
