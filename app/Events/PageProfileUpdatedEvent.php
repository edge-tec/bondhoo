<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * পেজ প্রোফাইল/ব্র্যান্ডিং আপডেট ব্রডকাস্ট ইভেন্ট
 */
class PageProfileUpdatedEvent implements ShouldBroadcastNow
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
     * @param  array<string, mixed>  $profileData
     */
    public function __construct(
        public int $pageId,
        public array $profileData,
        public int $actorId,
        ?string $eventId = null
    ) {
        $this->eventId = $eventId ?: (string) Str::uuid();
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("page.{$this->pageId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'page.profile.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'page_id' => $this->pageId,
            'profile' => [
                'name' => $this->profileData['name'] ?? null,
                'slug' => $this->profileData['slug'] ?? null,
                'username' => $this->profileData['username'] ?? null,
                'category' => $this->profileData['category'] ?? null,
                'sub_category' => $this->profileData['sub_category'] ?? null,
                'bio' => $this->profileData['bio'] ?? null,
                'avatar_url' => $this->profileData['avatar_url'] ?? null,
                'cover_image_url' => $this->profileData['cover_image_url'] ?? null,
                'website' => $this->profileData['website'] ?? null,
                'is_verified' => (bool) ($this->profileData['is_verified'] ?? false),
                'followers_count' => (int) ($this->profileData['followers_count'] ?? 0),
                'posts_count' => (int) ($this->profileData['posts_count'] ?? 0),
                'cta_type' => $this->profileData['cta_type'] ?? null,
                'cta_url' => $this->profileData['cta_url'] ?? null,
                'visibility' => $this->profileData['visibility'] ?? 'public',
            ],
            'actor_id' => $this->actorId,
            'version' => $this->profileData['version'] ?? time(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
