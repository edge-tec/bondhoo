<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * এই ইভেন্টটি কোনো পোস্টে রিঅ্যাকশন যোগ, পরিবর্তন বা রিমুভ হলে
 * ওই পোস্টের চ্যানেলে লাইভ কাউন্টার ব্রডকাস্ট করে।
 */
class PostReactionUpdatedEvent implements ShouldBroadcastNow
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
     * @param  array<string, int>  $breakdown
     */
    public function __construct(
        public int $postId,
        public int $totalReactions,
        public array $breakdown = [],
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
        return 'post.reaction.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'post_id' => $this->postId,
            'total_reactions' => $this->totalReactions,
            'breakdown' => $this->breakdown,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
