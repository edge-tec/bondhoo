<?php

namespace App\Services\Contracts;

interface RealtimeServiceInterface
{
    /**
     * Broadcast an event to a WebSocket channel.
     */
    public function broadcast(string $channel, string $event, array $payload): void;

    /**
     * Broadcast a private event to an authenticated user's channel.
     */
    public function broadcastToUser(int $userId, string $event, array $payload): void;

    /**
     * Broadcast to a private conversation channel.
     */
    public function broadcastToConversation(int $conversationId, string $event, array $payload): void;

    /**
     * ইউজারের অনলাইন/অফলাইন উপস্থিতি পরিবর্তন ব্রডকাস্ট করে।
     */
    public function broadcastPresence(int $userId, bool $isOnline, ?string $lastSeen = null): void;

    /**
     * টাইপিং স্ট্যাটাস ব্রডকাস্ট করে।
     */
    public function broadcastTyping(int $conversationId, int $userId, string $userName, bool $isTyping = true): void;

    /**
     * পোস্ট রিঅ্যাকশন কাউন্টার ব্রডকাস্ট করে।
     */
    public function broadcastPostReaction(int $postId, int $totalReactions, array $breakdown = []): void;

    /**
     * নতুন মেসেজ ব্রডকাস্ট করে।
     */
    public function broadcastNewMessage(int $conversationId, array $messageData, array $recipientIds = []): void;

    /**
     * মেসেজ ডেলিভারি স্ট্যাটাস ব্রডকাস্ট করে।
     */
    public function broadcastMessageDelivered(int $conversationId, int $messageId, string $deliveredAt): void;

    /**
     * মেসেজ রিড রিসিট (seen) ব্রডকাস্ট করে।
     */
    public function broadcastMessageRead(int $conversationId, int $readerId, ?int $lastReadMessageId, string $readAt): void;

    /**
     * WebRTC অডিও/ভিডিও কল সিগন্যালিং ব্রডকাস্ট করে।
     */
    public function broadcastCallSignal(int $conversationId, int $senderId, string $senderName, string $signalType, string $callType, array $payload = []): void;

    /**
     * লাইভ স্ট্রিমিং কমেন্ট ব্রডকাস্ট করে।
     */
    public function broadcastLiveComment(string $channelId, int $userId, string $userName, ?string $userAvatar, string $comment): void;

    /**
     * লাইভ স্ট্রিমিং গিফট ব্রডকাস্ট করে।
     */
    public function broadcastLiveGift(string $channelId, int $senderId, string $senderName, ?string $senderAvatar, string $giftType, int $giftAmount): void;

    /**
     * মেসেজ রিঅ্যাকশন ব্রডকাস্ট করে।
     */
    public function broadcastMessageReaction(int $conversationId, int $messageId, int $userId, string $userName, string $reaction, string $action = 'added', array $reactionsSummary = []): void;

    /**
     * এডিটেড মেসেজ ব্রডকাস্ট করে।
     */
    public function broadcastMessageUpdated(int $conversationId, int $messageId, string $body, string $editedAt, bool $isEdited = true, int $version = 1, ?int $senderId = null): void;

    /**
     * ডিলিটেড মেসেজ ব্রডকাস্ট করে।
     */
    public function broadcastMessageDeleted(int $conversationId, int $messageId, bool $forEveryone = true, ?int $userId = null): void;

    /**
     * চ্যাট ক্লিয়ার ব্রডকাস্ট করে।
     */
    public function broadcastConversationCleared(int $conversationId, int $userId, string $clearedAt): void;

    /**
     * কনভার্সন আপডেট ব্রডকাস্ট করে।
     */
    public function broadcastConversationUpdated(int $conversationId, string $action, array $payload = []): void;
}
