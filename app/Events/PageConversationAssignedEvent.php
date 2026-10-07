<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * পেজ ইনবক্স এজেন্ট অ্যাসাইনমেন্ট ব্রডকাস্ট ইভেন্ট
 */
class PageConversationAssignedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $pageId,
        public int $conversationId,
        public int $assignedAgentId,
        public string $assignedAgentName
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("page.{$this->pageId}.inbox"),
            new PrivateChannel("user.{$this->assignedAgentId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'page.conversation.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'page_id' => $this->pageId,
            'conversation_id' => $this->conversationId,
            'assigned_agent' => [
                'id' => $this->assignedAgentId,
                'name' => $this->assignedAgentName,
            ],
        ];
    }
}
