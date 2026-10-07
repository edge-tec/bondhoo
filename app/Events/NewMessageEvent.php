<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * এই ইভেন্টটি নতুন মেসেজ পাঠানোর সাথে সাথে লাইভ চ্যাট উইন্ডো
 * এবং প্রাপকের প্রাইভেট চ্যানেলে ব্রডকাস্ট করে।
 */
class NewMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public array $messageData,
        public array $recipientIds = []
    ) {}

    /**
     * সংশ্লিষ্ট কনভার্সন চ্যানেল এবং প্রাপকদের পার্সোনাল চ্যানেলে ব্রডকাস্ট করা।
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("conversation.{$this->conversationId}"),
        ];

        // ইনবক্স নোটিফিকেশন আপডেটের জন্য প্রাপকদের চ্যানেলেও পাঠানো
        foreach ($this->recipientIds as $recipientId) {
            $channels[] = new PrivateChannel("user.{$recipientId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.new';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message' => $this->messageData,
        ];
    }
}
