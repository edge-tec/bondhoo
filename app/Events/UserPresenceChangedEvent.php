<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * এই ইভেন্টটি কোনো ইউজারের অনলাইন বা অফলাইন স্ট্যাটাস পরিবর্তন হলে
 * তাৎক্ষণিকভাবে (ShouldBroadcastNow) WebSocket-এর মাধ্যমে ব্রডকাস্ট করে।
 */
class UserPresenceChangedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public bool $isOnline,
        public ?string $lastSeen = null
    ) {}

    /**
     * যে চ্যানেলে ইভেন্টটি ব্রডকাস্ট হবে।
     */
    public function broadcastOn(): array
    {
        // সব ইউজারদের জন্য সাধারণ উপস্থিতি চ্যানেল
        return [
            new Channel('presence-users'),
        ];
    }

    /**
     * ব্রডকাস্ট ইভেন্টের নাম।
     */
    public function broadcastAs(): string
    {
        return 'user.presence';
    }

    /**
     * ক্লায়েন্টে পাঠানো ডেটা।
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'is_online' => $this->isOnline,
            'last_seen' => $this->lastSeen ?? now()->toIso8601String(),
        ];
    }
}
