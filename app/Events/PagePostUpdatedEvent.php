<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * পেজ পোস্ট সম্পাদনা বা পিন বা ভিজিবিলিটি আপডেট ইভেন্ট
 */
class PagePostUpdatedEvent implements ShouldBroadcastNow
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
     * @param  array<string, mixed>  $postData
     */
    public function __construct(
        public int $pageId,
        public int $postId,
        public string $action,
        public array $postData,
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
            new Channel("page.{$this->pageId}.feed"),
            new Channel("post.{$this->postId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'page.post.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'page_id' => $this->pageId,
            'post_id' => $this->postId,
            'action' => $this->action,
            'post' => $this->postData,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
