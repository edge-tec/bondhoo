<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeletedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $messageId,
        public bool $forEveryone = true,
        public ?int $userId = null
    ) {}

    public function broadcastOn(): array
    {
        if (! $this->forEveryone && $this->userId) {
            return [
                new PrivateChannel("user.{$this->userId}"),
            ];
        }

        return [
            new PrivateChannel("conversation.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return $this->forEveryone ? 'message.deleted' : 'message.deleted_for_me';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'for_everyone' => $this->forEveryone,
            'user_id' => $this->userId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
