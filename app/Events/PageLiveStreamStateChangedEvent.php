<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * পেজ লাইভ স্ট্রিমিং স্ট্যাটাস পরিবর্তন রিয়েল-টাইম ইভেন্ট
 */
class PageLiveStreamStateChangedEvent implements ShouldBroadcastNow
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
     * @param  array<string, mixed>  $streamData
     */
    public function __construct(
        public int $pageId,
        public int $streamId,
        public string $channelId,
        public string $status,
        public array $streamData = [],
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
            new Channel("page.{$this->pageId}.live"),
            new Channel("live.{$this->channelId}"),
            new Channel("page.{$this->pageId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'page.live.state_changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'page_id' => $this->pageId,
            'stream_id' => $this->streamId,
            'channel_id' => $this->channelId,
            'status' => $this->status,
            'stream' => [
                'id' => $this->streamId,
                'channel_id' => $this->channelId,
                'status' => $this->status,
                'title' => $this->streamData['title'] ?? null,
                'viewers_count' => (int) ($this->streamData['viewers_count'] ?? 0),
                'started_at' => $this->streamData['started_at'] ?? null,
                'ended_at' => $this->streamData['ended_at'] ?? null,
                'playback_url' => $this->streamData['playback_url'] ?? null,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
