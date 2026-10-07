<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * এই ইভেন্টটি ইনবক্সে বা চ্যাটে টাইপিং স্ট্যাটাস পরিবর্তন হলে
 * তাৎক্ষণিকভাবে প্রাইভেট কনভার্সন চ্যানেলে ব্রডকাস্ট করে।
 */
class UserTypingEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $userId,
        public string $userName,
        public bool $isTyping = true
    ) {}

    /**
     * শুধুমাত্র সংশ্লিষ্ট কনভার্সনের প্রাইভেট চ্যানেলে ব্রডকাস্ট হবে।
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'is_typing' => $this->isTyping,
        ];
    }
}
