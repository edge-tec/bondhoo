<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * পেজ রিল প্রকাশের রিয়েল-টাইম ইভেন্ট
 */
class PageReelPublishedEvent implements ShouldBroadcastNow
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
     * @param  array<string, mixed>  $reelData
     */
    public function __construct(
        public int $pageId,
        public array $reelData,
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
            new Channel('reels'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'page.reel.published';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'page_id' => $this->pageId,
            'reel' => $this->reelData,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
