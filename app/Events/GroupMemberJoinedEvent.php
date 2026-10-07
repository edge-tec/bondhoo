<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * গ্রুপে নতুন সদস্য যোগদানের রিয়েল-টাইম ইভেন্ট
 */
class GroupMemberJoinedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $groupId,
        public array $memberData,
        public int $membersCount
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("group.{$this->groupId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'group.member.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'group_id' => $this->groupId,
            'member' => $this->memberData,
            'members_count' => $this->membersCount,
        ];
    }
}
