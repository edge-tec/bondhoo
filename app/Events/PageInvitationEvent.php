<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * পেজ ইনভাইটেশন পাঠানো বা স্ট্যাটাস পরিবর্তন রিয়েল-টাইম ইভেন্ট
 */
class PageInvitationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public int $tries = 3;

    public int $timeout = 10;

    /**
     * @var array<int, int>
     */
    public array $backoff = [2, 5, 10];

    public string $eventId;

    /**
     * @param  array<string, mixed>  $invitationData
     */
    public function __construct(
        public int $invitationId,
        public int $pageId,
        public int $inviteeId,
        public string $status,
        public array $invitationData = [],
        ?string $eventId = null
    ) {
        $this->eventId = $eventId ?: (string) Str::uuid();
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->inviteeId}"),
            new PrivateChannel("page.{$this->pageId}.admin"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'page.invitation.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'invitation_id' => $this->invitationId,
            'page_id' => $this->pageId,
            'invitee_id' => $this->inviteeId,
            'status' => $this->status,
            'invitation' => [
                'id' => $this->invitationId,
                'page_id' => $this->pageId,
                'page_name' => $this->invitationData['page_name'] ?? null,
                'page_slug' => $this->invitationData['page_slug'] ?? null,
                'page_avatar' => $this->invitationData['page_avatar'] ?? null,
                'inviter_id' => $this->invitationData['inviter_id'] ?? null,
                'inviter_name' => $this->invitationData['inviter_name'] ?? null,
                'status' => $this->status,
                'action_url' => $this->invitationData['action_url'] ?? null,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
