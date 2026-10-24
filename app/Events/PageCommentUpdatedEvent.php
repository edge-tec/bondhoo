<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * পোস্ট কমেন্ট সংযোজন, সম্পাদন বা ডিলিট রিয়েল-টাইম ইভেন্ট
 */
class PageCommentUpdatedEvent implements ShouldBroadcastNow
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
     * @param  array<string, mixed>|null  $commentData
     */
    public function __construct(
        public int $postId,
        public ?int $pageId,
        public int $commentId,
        public string $action,
        public ?array $commentData = null,
        public int $commentsCount = 0,
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
            new Channel("post.{$this->postId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'post.comment.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'post_id' => $this->postId,
            'page_id' => $this->pageId,
            'comment_id' => $this->commentId,
            'action' => $this->action,
            'comment' => $this->commentData,
            'comments_count' => $this->commentsCount,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
