<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a notification is deleted.
 * Removes the item from UI across all open tabs/devices.
 */
class NotificationDeletedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $recipientId,
        public string $notificationId,
        public int $unreadCount
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->recipientId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notificationId,
            'unread_count' => $this->unreadCount,
        ];
    }
}
