<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * এই ইভেন্টটি ইউজার যখন কনভার্সন ওপেন করেন এবং মেসেজগুলো পড়েন,
 * তখন অপর অংশগ্রহণকারীকে ব্লু-টিক বা 'seen' স্ট্যাটাস ব্রডকাস্ট করে।
 */
class MessageReadEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $readerId,
        public ?int $lastReadMessageId,
        public string $readAt
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'reader_id' => $this->readerId,
            'last_read_message_id' => $this->lastReadMessageId,
            'read_at' => $this->readAt,
        ];
    }
}
