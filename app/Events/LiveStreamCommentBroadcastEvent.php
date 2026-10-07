<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * লাইভ স্ট্রিমিং কমেন্ট ব্রডকাস্ট ইভেন্ট:
 * লাইভ সম্প্রচারের দর্শকদের কমেন্ট তাৎক্ষণিকভাবে সমস্ত দর্শকের কাছে পৌঁছে দেয়।
 */
class LiveStreamCommentBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $channelId,
        public int $userId,
        public string $userName,
        public ?string $userAvatar,
        public string $comment,
        public ?string $createdAt = null
    ) {
        $this->createdAt = $this->createdAt ?? now()->toIso8601String();
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("live.{$this->channelId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'live.comment';
    }

    public function broadcastWith(): array
    {
        return [
            'channel_id' => $this->channelId,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'user_avatar' => $this->userAvatar,
            'comment' => $this->comment,
            'created_at' => $this->createdAt,
        ];
    }
}
