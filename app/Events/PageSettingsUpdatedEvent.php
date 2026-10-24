<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * রিয়েল-টাইম পেজ সেটিংস পরিবর্তন ইভেন্ট (শুধুমাত্র অনুমোদিত অ্যাডমিন ও ম্যানেজারদের জন্য)
 */
class PageSettingsUpdatedEvent implements ShouldBroadcastNow
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
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        public int $pageId,
        public array $settings,
        public int $actorId,
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
            new PrivateChannel("page.{$this->pageId}.admin"),
            new PrivateChannel("page.{$this->pageId}.settings"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'page.settings.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'page_id' => $this->pageId,
            'settings' => $this->settings,
            'actor_id' => $this->actorId,
            'version' => time(),
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
