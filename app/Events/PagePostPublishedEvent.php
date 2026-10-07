<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * পেজ পোস্ট প্রকাশের রিয়েল-টাইম ইভেন্ট
 */
class PagePostPublishedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $pageId,
        public array $postData
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("page.{$this->pageId}.feed"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'page.post.published';
    }

    public function broadcastWith(): array
    {
        return [
            'page_id' => $this->pageId,
            'post' => $this->postData,
        ];
    }
}
