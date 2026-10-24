<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * পেজ ফলোয়ার সংখ্যা পরিবর্তন রিয়েল-টাইম ইভেন্ট
 */
class PageFollowerUpdatedEvent implements ShouldBroadcastNow
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

    public function __construct(
        public int $pageId,
        public int $followersCount,
        public string $action = 'followed',
        public ?int $actorId = null,
        ?string $eventId = null
    ) {
        $this->eventId = $eventId ?: (string) Str::uuid();
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("page.{$this->pageId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'page.follower.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'page_id' => $this->pageId,
            'followers_count' => $this->followersCount,
            'action' => $this->action,
            'actor_id' => $this->actorId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
