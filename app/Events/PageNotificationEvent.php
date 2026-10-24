<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * পেজ নোটিফিকেশন রিয়েল-টাইম ব্রডকাস্ট ইভেন্ট
 */
class PageNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public int $tries = 3;

    public int $timeout = 10;

    /**
     * @var array<int, int>
     */
    public array $backoff = [2, 5, 10];

    public string $eventId;

    /**
     * @param  array<string, mixed>  $notificationData
     */
    public function __construct(
        public int $recipientUserId,
        public int $pageId,
        public array $notificationData,
        public bool $broadcastToAdminChannel = false,
        ?string $eventId = null
    ) {
        $this->eventId = $eventId ?: (string) Str::uuid();
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("user.{$this->recipientUserId}"),
        ];

        if ($this->broadcastToAdminChannel) {
            $channels[] = new PrivateChannel("page.{$this->pageId}.admin");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'page.notification.received';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'page_id' => $this->pageId,
            'recipient_id' => $this->recipientUserId,
            'notification' => $this->notificationData,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
