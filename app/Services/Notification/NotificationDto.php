<?php

namespace App\Services\Notification;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;

class NotificationDto implements Arrayable
{
    public function __construct(
        public string $id,
        public string $type,
        public string $category,
        public string $priority,
        public array $actor,
        public string $title,
        public string $message,
        public string $actionUrl,
        public string $icon,
        public ?string $readAt,
        public bool $isRead,
        public string $createdAt,
        public string $timeAgo,
        public array $metadata = [],
        public ?string $groupKey = null,
        public int $groupCount = 1,
    ) {}

    /**
     * Build DTO from raw database notification object or array.
     *
     * @param  object|array  $raw
     */
    public static function fromDatabase(mixed $raw): self
    {
        $id = is_array($raw) ? (string) $raw['id'] : (string) $raw->id;
        $type = is_array($raw) ? (string) ($raw['type'] ?? '') : (string) ($raw->type ?? '');
        $data = is_array($raw) ? ($raw['data'] ?? []) : ($raw->data ?? []);
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }

        $readAt = is_array($raw) ? ($raw['read_at'] ?? null) : ($raw->read_at ?? null);
        $createdAtRaw = is_array($raw) ? ($raw['created_at'] ?? null) : ($raw->created_at ?? null);
        $groupKey = is_array($raw) ? ($raw['group_key'] ?? null) : ($raw->group_key ?? null);
        $categoryCol = is_array($raw) ? ($raw['category'] ?? null) : ($raw->category ?? null);

        $category = $categoryCol ?: NotificationTypeRegistry::resolveCategory($type)->value;
        $icon = NotificationTypeRegistry::resolveIcon($type);
        $priority = $data['priority'] ?? NotificationTypeRegistry::resolvePriority($type);

        // Normalize Actor
        $actorId = $data['actor_id'] ?? $data['sender_id'] ?? $data['user_id'] ?? null;
        $actorName = $data['actor_name'] ?? $data['sender_name'] ?? $data['user_name'] ?? 'ব্যবহারকারী';
        $actorUsername = $data['actor_username'] ?? $data['sender_username'] ?? '';
        $actorAvatar = $data['actor_avatar'] ?? $data['sender_avatar'] ?? $data['avatar_url'] ?? null;
        $actorInitial = mb_substr($actorName, 0, 1);

        $actor = [
            'id' => $actorId ? (int) $actorId : null,
            'name' => (string) $actorName,
            'username' => (string) $actorUsername,
            'avatar_url' => $actorAvatar ?: null,
            'initial' => $actorInitial ?: 'U',
        ];

        // Normalize Title & Message
        $title = (string) ($data['title'] ?? NotificationTypeRegistry::$registry[$type]['default_title'] ?? 'নতুন নোটিফিকেশন');
        $message = (string) ($data['message'] ?? $data['body'] ?? $data['text'] ?? $title);

        // Normalize Deep Link Action URL
        $actionUrl = (string) ($data['action_url'] ?? $data['link'] ?? $data['url'] ?? NotificationTypeRegistry::$registry[$type]['action_url'] ?? '/notifications');

        $carbonCreated = $createdAtRaw ? Carbon::parse($createdAtRaw) : Carbon::now();
        $timeAgo = self::formatBengaliTimeAgo($carbonCreated);

        $groupCount = (int) ($data['group_count'] ?? 1);

        return new self(
            id: $id,
            type: $type,
            category: $category,
            priority: $priority,
            actor: $actor,
            title: $title,
            message: $message,
            actionUrl: $actionUrl,
            icon: $icon,
            readAt: $readAt ? (string) $readAt : null,
            isRead: ! empty($readAt),
            createdAt: $carbonCreated->toIso8601String(),
            timeAgo: $timeAgo,
            metadata: $data['metadata'] ?? $data,
            groupKey: $groupKey,
            groupCount: $groupCount,
        );
    }

    /**
     * Bengali-friendly relative time formatter.
     */
    public static function formatBengaliTimeAgo(Carbon $date): string
    {
        $now = Carbon::now();
        $diffSeconds = $now->diffInSeconds($date);

        if ($diffSeconds < 45) {
            return 'এইমাত্র';
        }

        $diffMinutes = (int) round($diffSeconds / 60);
        if ($diffMinutes < 60) {
            return self::toBengaliNumerals($diffMinutes).' মিনিট আগে';
        }

        $diffHours = (int) round($diffMinutes / 60);
        if ($diffHours < 24) {
            return self::toBengaliNumerals($diffHours).' ঘণ্টা আগে';
        }

        $diffDays = (int) round($diffHours / 24);
        if ($diffDays < 7) {
            return self::toBengaliNumerals($diffDays).' দিন আগে';
        }

        return $date->format('d M, Y');
    }

    public static function toBengaliNumerals(int|string $number): string
    {
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

        return str_replace($en, $bn, (string) $number);
    }

    /**
     * Render SVG icon markup for this notification.
     */
    public function renderSvg(int $size = 20): string
    {
        return NotificationTypeRegistry::renderSvgIcon($this->icon, $size);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'category' => $this->category,
            'priority' => $this->priority,
            'actor' => $this->actor,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'icon' => $this->icon,
            'read_at' => $this->readAt,
            'is_read' => $this->isRead,
            'created_at' => $this->createdAt,
            'time_ago' => $this->timeAgo,
            'group_key' => $this->groupKey,
            'group_count' => $this->groupCount,
            'metadata' => $this->metadata,
        ];
    }
}
