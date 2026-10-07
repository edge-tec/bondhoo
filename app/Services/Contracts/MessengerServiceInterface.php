<?php

namespace App\Services\Contracts;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\PinnedMessage;
use App\Models\SavedMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * মেসেঞ্জার সার্ভিস কন্ট্রাক্ট:
 * চ্যাট, কনভার্সন, মেসেজ ডেলিভারি, রিড রিসিট, রিঅ্যাকশন, এডিট, ফরোয়ার্ড ও গ্রুপ ম্যানেজমেন্ট সংজ্ঞায়িত করে।
 */
interface MessengerServiceInterface
{
    /**
     * ইউজারের কনভার্সন তালিকা ফিল্টার ও পেজিনেশন সহ ফিরিয়ে দেয়।
     */
    public function getUserConversations(User $user, int $perPage = 20, array $filters = []): LengthAwarePaginator;

    /**
     * ১-অন-১ ডিরেক্ট চ্যাট খুঁজে বের করে অথবা নতুন কনভার্সন তৈরি করে।
     */
    public function getOrCreateDirectConversation(User $user, int $recipientId): Conversation;

    /**
     * সেভড মেসেজ কনভার্সন খুঁজে বের করে অথবা তৈরি করে।
     */
    public function getOrCreateSavedConversation(User $user): Conversation;

    /**
     * একাধিক সদস্য নিয়ে নতুন গ্রুপ কনভার্সন তৈরি করে।
     */
    public function createGroupConversation(User $creator, string $title, array $participantIds, ?string $description = null, ?string $avatarUrl = null): Conversation;

    /**
     * নির্দিষ্ট কনভার্সনের মেসেজ হিস্ট্রি ফিল্টার ও সার্চ সহ নিয়ে আসে।
     */
    public function getMessages(Conversation $conversation, User $user, int $perPage = 30, array $filters = []): LengthAwarePaginator;

    /**
     * কনভার্সনে নতুন মেসেজ পাঠায় (টেক্সট, মিডিয়া, ভয়েস, লিঙ্ক, সিস্টেম ইত্যাদি) এবং রিয়েল-টাইমে ব্রডকাস্ট করে।
     */
    public function sendMessage(User $sender, Conversation $conversation, array $data): Message;

    /**
     * প্রেরক কর্তৃক নিজের পূর্ববর্তী মেসেজ এডিট করা।
     */
    public function editMessage(User $user, int $messageId, string $newBody, ?int $expectedVersion = null): Message;

    /**
     * মেসেজ ডিলিট করা (নিজের জন্য অথবা সবার জন্য)।
     */
    public function deleteMessage(User $user, int $messageId, bool $forEveryone = false): bool;

    /**
     * মেসেজে রিঅ্যাকশন যোগ, পরিবর্তন বা প্রত্যাহার করা।
     */
    public function reactToMessage(User $user, int $messageId, string $reaction): array;

    /**
     * এক বা একাধিক কনভার্সনে মেসেজ ফরোয়ার্ড করা।
     */
    public function forwardMessage(User $user, int $messageId, array $targetConversationIds): array;

    /**
     * মেসেজ প্রাপকের কাছে পৌঁছেছে বলে মার্ক করে (delivered status)।
     */
    public function markAsDelivered(Message $message, User $recipient): Message;

    /**
     * কনভার্সনের মেসেজগুলো পঠিত (seen/read) হয়েছে বলে মার্ক করে এবং আনরিড কাউন্টার রিসেট করে।
     */
    public function markConversationAsRead(Conversation $conversation, User $user): int;

    /**
     * কনভার্সন আনরিড হিসেবে মার্ক করা।
     */
    public function markConversationAsUnread(User $user, int $conversationId): bool;

    /**
     * কনভার্সন পিন বা আনপিন করা।
     */
    public function togglePinConversation(User $user, int $conversationId): bool;

    /**
     * কনভার্সন আর্কাইভ বা আনআর্কাইভ করা।
     */
    public function toggleArchiveConversation(User $user, int $conversationId): bool;

    /**
     * কনভার্সন মিউট বা আনমিউট করা।
     */
    public function muteConversation(User $user, int $conversationId, ?string $duration = null): bool;

    /**
     * কনভার্সনের চ্যাট হিস্ট্রি নিজের জন্য ক্লিয়ার করা।
     */
    public function clearConversation(User $user, int $conversationId): bool;

    /**
     * গ্রুপ চ্যাট ত্যাগ করা।
     */
    public function leaveGroup(User $user, int $conversationId): bool;

    /**
     * গ্রুপ চ্যাটের তথ্য ও পারমিশন আপডেট করা।
     */
    public function updateGroupInfo(User $user, int $conversationId, array $data): Conversation;

    /**
     * গ্রুপে নতুন সদস্য যুক্ত করা।
     */
    public function addGroupMembers(User $user, int $conversationId, array $newUserIds): array;

    /**
     * গ্রুপ থেকে সদস্য অপসারণ করা।
     */
    public function removeGroupMember(User $user, int $conversationId, int $targetUserId): bool;

    /**
     * গ্রুপ সদস্যের ভূমিকা পরিবর্তন করা (এডমিন / মেম্বার)।
     */
    public function updateMemberRole(User $user, int $conversationId, int $targetUserId, string $role): bool;

    /**
     * মেসেজ রিকোয়েস্ট একসেপ্ট, ডিলিট বা ব্লক হ্যান্ডেল করা।
     */
    public function handleMessageRequest(User $user, int $conversationId, string $action): bool;

    /**
     * কনভার্সনে ড্রাফট মেসেজ সংরক্ষণ করা।
     */
    public function saveDraft(User $user, int $conversationId, ?string $draft): bool;

    /**
     * কনভার্সনে শেয়ার করা মিডিয়া, ফাইল বা লিঙ্ক তালিকা।
     */
    public function getConversationMedia(Conversation $conversation, User $user, string $type = 'media', int $perPage = 30): array;

    /**
     * ইউজারের মোট না পড়া (unread) মেসেজ সংখ্যা ফিরিয়ে দেয়।
     */
    public function getUnreadMessageCount(User $user): int;

    /**
     * কনভার্সনে মেসেজ পিন করা।
     */
    public function pinMessage(User $user, int $conversationId, int $messageId): PinnedMessage;

    /**
     * কনভার্সন থেকে মেসেজ আনপিন করা।
     */
    public function unpinMessage(User $user, int $conversationId, int $messageId): bool;

    /**
     * কনভার্সনের পিন করা মেসেজগুলো পাওয়া।
     */
    public function getPinnedMessages(Conversation $conversation, User $user): array;

    /**
     * মেসেজ সেভ করা (Saved Messages)।
     */
    public function saveMessage(User $user, int $messageId): SavedMessage;

    /**
     * সেভ করা মেসেজ রিমুভ করা।
     */
    public function unsaveMessage(User $user, int $messageId): bool;

    /**
     * ইউজারের সেভ করা মেসেজগুলোর তালিকা।
     */
    public function getSavedMessages(User $user, int $perPage = 20): LengthAwarePaginator;

    /**
     * মেসেজ ও কনভার্সনে গ্লোবাল ও ইন-চ্যাট অনুসন্ধান।
     */
    public function searchMessenger(User $user, string $query, ?int $conversationId = null, string $type = 'all', int $perPage = 20): LengthAwarePaginator;
}
