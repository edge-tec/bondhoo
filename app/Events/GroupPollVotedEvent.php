<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * গ্রুপ পোলে ভোটের রিয়েল-টাইম আপডেট ইভেন্ট
 */
class GroupPollVotedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $groupId,
        public int $pollId,
        public array $pollData
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("group.{$this->groupId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'group.poll.voted';
    }

    public function broadcastWith(): array
    {
        return [
            'group_id' => $this->groupId,
            'poll_id' => $this->pollId,
            'poll' => $this->pollData,
        ];
    }
}
