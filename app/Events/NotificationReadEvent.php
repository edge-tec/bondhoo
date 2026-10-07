<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a notification is marked as read.
 * Synchronizes read state across multiple connected devices in real time.
 */
class NotificationReadEvent implements ShouldBroadcastNow
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
        return 'notification.read';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notificationId,
            'unread_count' => $this->unreadCount,
        ];
    }
}
