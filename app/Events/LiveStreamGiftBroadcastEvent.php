<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * লাইভ স্ট্রিমিং ভার্চুয়াল গিফট ব্রডকাস্ট ইভেন্ট:
 * কোনো দর্শক উপহার পাঠালে তা তাৎক্ষণিকভাবে স্ক্রিন অ্যানিমেশনের জন্য ব্রডকাস্ট করে।
 */
class LiveStreamGiftBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $channelId,
        public int $senderId,
        public string $senderName,
        public ?string $senderAvatar,
        public string $giftType,
        public int $giftAmount,
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
        return 'live.gift';
    }

    public function broadcastWith(): array
    {
        return [
            'channel_id' => $this->channelId,
            'sender_id' => $this->senderId,
            'sender_name' => $this->senderName,
            'sender_avatar' => $this->senderAvatar,
            'gift_type' => $this->giftType,
            'gift_amount' => $this->giftAmount,
            'created_at' => $this->createdAt,
        ];
    }
}
