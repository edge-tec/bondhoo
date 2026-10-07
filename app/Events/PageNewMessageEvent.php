<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * পেজ ইনবক্সের রিয়েল-টাইম মেসেজ ব্রডকাস্ট ইভেন্ট:
 * মেসেজ আসার সাথে সাথে পেজ ইনবক্স এবং সংশ্লিষ্ট ব্যবহারকারীর প্রাইভেট চ্যানেলে ইভেন্ট পাঠায়।
 */
class PageNewMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $pageId,
        public int $conversationId,
        public array $messageData,
        public ?int $recipientUserId = null
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("page.{$this->pageId}.inbox"),
            new PrivateChannel("page.{$this->pageId}.conversation.{$this->conversationId}"),
        ];

        if ($this->recipientUserId) {
            $channels[] = new PrivateChannel("user.{$this->recipientUserId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'page.message.new';
    }

    public function broadcastWith(): array
    {
        return [
            'page_id' => $this->pageId,
            'conversation_id' => $this->conversationId,
            'message' => $this->messageData,
        ];
    }
}
