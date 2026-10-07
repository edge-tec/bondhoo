<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * এই ইভেন্টটি নতুন কোনো নোটিফিকেশন তৈরি হলে নির্দিষ্ট ইউজারের
 * প্রাইভেট চ্যানেলে সরাসরি লাইভ পুশ করে।
 */
class NewNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $recipientId,
        public array $notificationData
    ) {}

    /**
     * শুধুমাত্র নোটিফিকেশন প্রাপক ইউজারের প্রাইভেট চ্যানেলে যাবে।
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->recipientId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    public function broadcastWith(): array
    {
        return [
            'recipient_id' => $this->recipientId,
            'notification' => $this->notificationData,
        ];
    }
}
