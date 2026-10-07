<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * এই ইভেন্টটি কোনো পোস্টে রিঅ্যাকশন যোগ, পরিবর্তন বা রিমুভ হলে
 * ওই পোস্টের চ্যানেলে লাইভ কাউন্টার ব্রডকাস্ট করে।
 */
class PostReactionUpdatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $postId,
        public int $totalReactions,
        public array $breakdown = []
    ) {}

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

    public function broadcastWith(): array
    {
        return [
            'post_id' => $this->postId,
            'total_reactions' => $this->totalReactions,
            'breakdown' => $this->breakdown,
        ];
    }
}
