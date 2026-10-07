<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when all notifications are marked as read.
 * Resets unread badges and state across all open sessions/devices.
 */
class NotificationReadAllEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $recipientId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->recipientId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.read_all';
    }

    public function broadcastWith(): array
    {
        return [
            'unread_count' => 0,
        ];
    }
}
