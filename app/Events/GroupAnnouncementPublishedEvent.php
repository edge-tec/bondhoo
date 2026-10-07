<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * গ্রুপে নতুন অ্যানাউন্সমেন্ট প্রকাশের রিয়েল-টাইম ইভেন্ট
 */
class GroupAnnouncementPublishedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $groupId,
        public array $announcementData
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("group.{$this->groupId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'group.announcement.published';
    }

    public function broadcastWith(): array
    {
        return [
            'group_id' => $this->groupId,
            'announcement' => $this->announcementData,
        ];
    }
}
