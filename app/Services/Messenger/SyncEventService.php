<?php

namespace App\Services\Messenger;

use App\Models\ConversationParticipant;
use App\Models\MessengerSyncEvent;
use App\Models\User;
use App\Services\Contracts\RealtimeServiceInterface;

class SyncEventService
{
    public function __construct(
        protected RealtimeServiceInterface $realtimeService
    ) {}

    /**
     * Record an event for offline synchronization and multi-device replay.
     */
    public function recordEvent(string $eventType, array $payload, ?int $userId = null, ?int $conversationId = null): MessengerSyncEvent
    {
        $event = MessengerSyncEvent::create([
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'event_type' => $eventType,
            'payload' => $payload,
            'created_at' => now(),
        ]);

        return $event;
    }

    /**
     * Fetch missed events for a user since a given event ID sequence.
     *
     * @return array{events: array, latest_event_id: int, has_more: bool}
     */
    public function getEventsSince(User $user, int $sinceId = 0, int $limit = 100): array
    {
        $limit = min(max($limit, 1), 200);

        // Fetch events targeted directly to this user OR events in conversations user belongs to OR friends presence updates
        $conversationIds = ConversationParticipant::where('user_id', $user->id)
            ->pluck('conversation_id')
            ->toArray();

        $friendIds = $user->getFriendIds();

        $query = MessengerSyncEvent::where('id', '>', $sinceId)
            ->where(function ($q) use ($user, $conversationIds, $friendIds) {
                $q->where('user_id', $user->id);
                if (! empty($conversationIds)) {
                    $q->orWhereIn('conversation_id', $conversationIds);
                }
                if (! empty($friendIds)) {
                    $q->orWhere(function ($fq) use ($friendIds) {
                        $fq->whereIn('user_id', $friendIds)
                            ->whereIn('event_type', ['presence.online', 'presence.offline', 'user_online', 'user_offline', 'user.presence']);
                    });
                }
            })
            ->orderBy('id', 'asc')
            ->limit($limit + 1);

        $results = $query->get();
        $hasMore = $results->count() > $limit;
        $events = $results->take($limit);

        $latestEventId = $events->isNotEmpty() ? $events->last()->id : $sinceId;

        return [
            'events' => $events->map(fn (MessengerSyncEvent $e) => [
                'id' => $e->id,
                'event_uuid' => $e->event_uuid,
                'event_type' => $e->event_type,
                'conversation_id' => $e->conversation_id,
                'user_id' => $e->user_id,
                'payload' => $e->payload,
                'timestamp' => $e->created_at->toIso8601String(),
            ])->values()->all(),
            'latest_event_id' => $latestEventId,
            'has_more' => $hasMore,
        ];
    }
}
