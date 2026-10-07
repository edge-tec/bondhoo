<?php

namespace App\Services;

use App\Events\CallSignalEvent;
use App\Events\ConversationClearedEvent;
use App\Events\ConversationUpdatedEvent;
use App\Events\LiveStreamCommentBroadcastEvent;
use App\Events\LiveStreamGiftBroadcastEvent;
use App\Events\MessageDeletedEvent;
use App\Events\MessageDeliveredEvent;
use App\Events\MessageReactionEvent;
use App\Events\MessageReadEvent;
use App\Events\MessageUpdatedEvent;
use App\Events\NewMessageEvent;
use App\Events\NewNotificationEvent;
use App\Events\PostReactionUpdatedEvent;
use App\Events\UserPresenceChangedEvent;
use App\Events\UserTypingEvent;
use App\Services\Contracts\RealtimeServiceInterface;
use Exception;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Log;

/**
 * রিয়েল-টাইম সার্ভিস:
 * WebSocket ইভেন্ট ব্রডকাস্টিং পরিচালনা করে।
 * যদি WebSocket ড্রাইভার সাময়িকভাবে বন্ধ থাকে, এটি নিরাপদভাবে ত্রুটি হ্যান্ডেল করে।
 */
class RealtimeService implements RealtimeServiceInterface
{
    public function broadcast(string $channel, string $event, array $payload): void
    {
        try {
            broadcast(new class($channel, $event, $payload) implements ShouldBroadcastNow
            {
                use InteractsWithSockets;

                public function __construct(
                    public string $channelName,
                    public string $eventName,
                    public array $data
                ) {}

                public function broadcastOn(): array
                {
                    if (str_starts_with($this->channelName, 'private-')) {
                        return [new PrivateChannel(substr($this->channelName, 8))];
                    }

                    return [new Channel($this->channelName)];
                }

                public function broadcastAs(): string
                {
                    return $this->eventName;
                }

                public function broadcastWith(): array
                {
                    return $this->data;
                }
            });
        } catch (Exception $e) {
            Log::warning("WebSocket broadcast error on [{$channel}]: ".$e->getMessage());
        }
    }

    public function broadcastToUser(int $userId, string $event, array $payload): void
    {
        try {
            broadcast(new NewNotificationEvent($userId, $payload));
        } catch (Exception $e) {
            Log::warning("WebSocket notification broadcast error to user [{$userId}]: ".$e->getMessage());
        }
    }

    public function broadcastToConversation(int $conversationId, string $event, array $payload): void
    {
        $this->broadcast("private-conversation.{$conversationId}", $event, $payload);
    }

    /**
     * ইউজারের অনলাইন/অফলাইন উপস্থিতি পরিবর্তন ব্রডকাস্ট করে।
     */
    public function broadcastPresence(int $userId, bool $isOnline, ?string $lastSeen = null): void
    {
        try {
            broadcast(new UserPresenceChangedEvent($userId, $isOnline, $lastSeen));
        } catch (Exception $e) {
            Log::warning("WebSocket presence broadcast error for user [{$userId}]: ".$e->getMessage());
        }
    }

    /**
     * টাইপিং স্ট্যাটাস ব্রডকাস্ট করে।
     */
    public function broadcastTyping(int $conversationId, int $userId, string $userName, bool $isTyping = true): void
    {
        try {
            broadcast(new UserTypingEvent($conversationId, $userId, $userName, $isTyping));
        } catch (Exception $e) {
            Log::warning("WebSocket typing broadcast error for conversation [{$conversationId}]: ".$e->getMessage());
        }
    }

    /**
     * পোস্ট রিঅ্যাকশন কাউন্টার আপডেট ব্রডকাস্ট করে।
     */
    public function broadcastPostReaction(int $postId, int $totalReactions, array $breakdown = []): void
    {
        try {
            broadcast(new PostReactionUpdatedEvent($postId, $totalReactions, $breakdown));
        } catch (Exception $e) {
            Log::warning("WebSocket reaction broadcast error for post [{$postId}]: ".$e->getMessage());
        }
    }

    /**
     * নতুন মেসেজ ব্রডকাস্ট করে।
     */
    public function broadcastNewMessage(int $conversationId, array $messageData, array $recipientIds = []): void
    {
        try {
            broadcast(new NewMessageEvent($conversationId, $messageData, $recipientIds));
        } catch (Exception $e) {
            Log::warning("WebSocket new message broadcast error for conversation [{$conversationId}]: ".$e->getMessage());
        }
    }

    /**
     * মেসেজ ডেলিভারি স্ট্যাটাস ব্রডকাস্ট করে।
     */
    public function broadcastMessageDelivered(int $conversationId, int $messageId, string $deliveredAt): void
    {
        try {
            broadcast(new MessageDeliveredEvent($conversationId, $messageId, $deliveredAt));
        } catch (Exception $e) {
            Log::warning("WebSocket message delivered broadcast error for conversation [{$conversationId}]: ".$e->getMessage());
        }
    }

    /**
     * মেসেজ রিড রিসিট (seen) ব্রডকাস্ট করে।
     */
    public function broadcastMessageRead(int $conversationId, int $readerId, ?int $lastReadMessageId, string $readAt): void
    {
        try {
            broadcast(new MessageReadEvent($conversationId, $readerId, $lastReadMessageId, $readAt));
        } catch (Exception $e) {
            Log::warning("WebSocket message read broadcast error for conversation [{$conversationId}]: ".$e->getMessage());
        }
    }

    /**
     * WebRTC অডিও/ভিডিও কল সিগন্যালিং ব্রডকাস্ট করে।
     */
    public function broadcastCallSignal(int $conversationId, int $senderId, string $senderName, string $signalType, string $callType, array $payload = []): void
    {
        try {
            broadcast(new CallSignalEvent($conversationId, $senderId, $senderName, $signalType, $callType, $payload));
        } catch (Exception $e) {
            Log::warning("WebSocket call signal broadcast error for conversation [{$conversationId}]: ".$e->getMessage());
        }
    }

    /**
     * লাইভ স্ট্রিমিং কমেন্ট ব্রডকাস্ট করে।
     */
    public function broadcastLiveComment(string $channelId, int $userId, string $userName, ?string $userAvatar, string $comment): void
    {
        try {
            broadcast(new LiveStreamCommentBroadcastEvent($channelId, $userId, $userName, $userAvatar, $comment));
        } catch (Exception $e) {
            Log::warning("WebSocket live comment broadcast error on channel [{$channelId}]: ".$e->getMessage());
        }
    }

    /**
     * লাইভ স্ট্রিমিং গিফট ব্রডকাস্ট করে।
     */
    public function broadcastLiveGift(string $channelId, int $senderId, string $senderName, ?string $senderAvatar, string $giftType, int $giftAmount): void
    {
        try {
            broadcast(new LiveStreamGiftBroadcastEvent($channelId, $senderId, $senderName, $senderAvatar, $giftType, $giftAmount));
        } catch (Exception $e) {
            Log::warning("WebSocket live gift broadcast error on channel [{$channelId}]: ".$e->getMessage());
        }
    }

    /**
     * মেসেজ রিঅ্যাকশন ব্রডকাস্ট করে।
     */
    public function broadcastMessageReaction(int $conversationId, int $messageId, int $userId, string $userName, string $reaction, string $action = 'added', array $reactionsSummary = []): void
    {
        try {
            broadcast(new MessageReactionEvent($conversationId, $messageId, $userId, $userName, $reaction, $action, $reactionsSummary));
        } catch (Exception $e) {
            Log::warning("WebSocket message reaction broadcast error for conversation [{$conversationId}]: ".$e->getMessage());
        }
    }

    /**
     * এডিটেড মেসেজ ব্রডকাস্ট করে।
     */
    public function broadcastMessageUpdated(int $conversationId, int $messageId, string $body, string $editedAt, bool $isEdited = true, int $version = 1, ?int $senderId = null): void
    {
        try {
            broadcast(new MessageUpdatedEvent($conversationId, $messageId, $body, $editedAt, $isEdited, $version, $senderId));
        } catch (Exception $e) {
            Log::warning("WebSocket message update broadcast error for conversation [{$conversationId}]: ".$e->getMessage());
        }
    }

    /**
     * ডিলিটেড মেসেজ ব্রডকাস্ট করে।
     */
    public function broadcastMessageDeleted(int $conversationId, int $messageId, bool $forEveryone = true, ?int $userId = null): void
    {
        try {
            broadcast(new MessageDeletedEvent($conversationId, $messageId, $forEveryone, $userId));
        } catch (Exception $e) {
            Log::warning("WebSocket message delete broadcast error for conversation [{$conversationId}]: ".$e->getMessage());
        }
    }

    /**
     * চ্যাট ক্লিয়ার ব্রডকাস্ট করে।
     */
    public function broadcastConversationCleared(int $conversationId, int $userId, string $clearedAt): void
    {
        try {
            broadcast(new ConversationClearedEvent($conversationId, $userId, $clearedAt));
        } catch (Exception $e) {
            Log::warning("WebSocket conversation cleared broadcast error for user [{$userId}]: ".$e->getMessage());
        }
    }

    /**
     * কনভার্সন আপডেট ব্রডকাস্ট করে।
     */
    public function broadcastConversationUpdated(int $conversationId, string $action, array $payload = []): void
    {
        try {
            broadcast(new ConversationUpdatedEvent($conversationId, $action, $payload));
        } catch (Exception $e) {
            Log::warning("WebSocket conversation update broadcast error for conversation [{$conversationId}]: ".$e->getMessage());
        }
    }
}
