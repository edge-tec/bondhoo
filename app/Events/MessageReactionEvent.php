<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReactionEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $messageId,
        public int $userId,
        public string $userName,
        public string $reaction,
        public string $action = 'added', // 'added', 'removed'
        public array $reactionsSummary = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.reaction';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'reaction' => $this->reaction,
            'action' => $this->action,
            'reactions_summary' => $this->reactionsSummary,
        ];
    }
}
