<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Friendship;
use App\Models\Media;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\MessageUserDeletion;
use App\Models\PinnedMessage;
use App\Models\SavedMessage;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\MessengerServiceInterface;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use App\Services\Messenger\SyncEventService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * এন্টারপ্রাইজ মেসেঞ্জার সার্ভিস:
 * কনভার্সন, মেসেজ, রিঅ্যাকশন, এডিট, ডিলিট, গ্রুপ ও রিয়েল-টাইম ট্রান্সপোর্ট হ্যান্ডেল করে।
 */
class MessengerService implements MessengerServiceInterface
{
    public function __construct(
        protected CacheServiceInterface $cacheService,
        protected RealtimeServiceInterface $realtimeService,
        protected NotificationServiceInterface $notificationService
    ) {}

    /**
     * ইউজারের কনভার্সন তালিকা পেজিনেশন ও ফিল্টার সহ রিটার্ন করে।
     */
    public function getUserConversations(User $user, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $filter = $filters['filter'] ?? 'all';
        $search = trim((string) ($filters['search'] ?? ''));

        $query = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with([
            'users.profile',
            'lastMessage.sender.profile',
            'participants',
        ]);

        // ১. ট্যাব ফিল্টার লজিক
        if ($filter === 'pinned') {
            $query->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('is_pinned', true);
            });
        } elseif ($filter === 'archived') {
            $query->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('is_archived', true);
            });
        } elseif ($filter === 'unread') {
            $query->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('is_archived', false)
                    ->whereColumn('last_read_message_id', '<', 'conversations.last_message_id');
            });
        } elseif ($filter === 'groups') {
            $query->where('type', Conversation::TYPE_GROUP)
                ->whereHas('participants', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('is_archived', false);
                });
        } elseif ($filter === 'requests') {
            $query->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('is_request', true)
                    ->where('request_status', 'pending');
            });
        } else {
            // ডিফল্ট: সাধারণ ইনবক্স (আর্কাইভ ও পেন্ডিং রিকোয়েস্ট ছাড়া)
            $query->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('is_archived', false)
                    ->where(function ($sq) {
                        $sq->where('is_request', false)
                            ->orWhere('request_status', 'accepted');
                    });
            });
        }

        // ২. সার্চ ফিল্টার (গ্রুপের নাম অথবা অপর ইউজারের নাম/ইউজারনেম)
        if ($search !== '') {
            $query->where(function ($q) use ($search, $user) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('users', function ($uq) use ($search, $user) {
                        $uq->where('users.id', '!=', $user->id)
                            ->where(function ($sub) use ($search) {
                                $sub->where('name', 'like', "%{$search}%")
                                    ->orWhere('username', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // ৩. সর্টিং: প্রথমে পিন করা চ্যাট, এরপর সর্বশেষ মেসেজের সময়
        $paginator = $query
            ->orderByRaw('(SELECT is_pinned FROM conversation_participants WHERE conversation_participants.conversation_id = conversations.id AND conversation_participants.user_id = ?) DESC', [$user->id])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        // প্রতিটি কনভার্সনের জন্য আনরিড কাউন্ট ও ফরম্যাটিং
        $paginator->getCollection()->transform(function (Conversation $conversation) use ($user) {
            $unreadKey = "conversation:{$conversation->id}:unread:{$user->id}";
            $unreadCount = (int) ($this->cacheService->get($unreadKey) ?? 0);

            // ক্যাশে না থাকলে DB থেকে আনরিড যাচাই
            if ($unreadCount === 0) {
                $participant = $conversation->participants->firstWhere('user_id', $user->id);
                if ($participant && $conversation->last_message_id && $participant->last_read_message_id < $conversation->last_message_id) {
                    $unreadCount = Message::where('conversation_id', $conversation->id)
                        ->where('sender_id', '!=', $user->id)
                        ->where('id', '>', (int) $participant->last_read_message_id)
                        ->where('delivery_status', '!=', Message::STATUS_SEEN)
                        ->count();
                }
            }

            return $conversation->toResponseArray($user, $unreadCount);
        });

        return $paginator;
    }

    /**
     * সেভড মেসেজ (Saved Messages) কনভার্সন খুঁজে বের করে অথবা নতুন তৈরি করে।
     */
    public function getOrCreateSavedConversation(User $user): Conversation
    {
        $savedConv = Conversation::where('type', Conversation::TYPE_SAVED)
            ->where('creator_id', $user->id)
            ->first();

        if ($savedConv) {
            $savedConv->load(['users.profile', 'participants', 'lastMessage']);

            return $savedConv;
        }

        return DB::transaction(function () use ($user) {
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_SAVED,
                'title' => 'Saved Messages',
                'creator_id' => $user->id,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'is_request' => false,
                'request_status' => 'accepted',
            ]);

            $conversation->load(['users.profile', 'participants']);

            return $conversation;
        });
    }

    /**
     * ১-অন-১ ডিরেক্ট চ্যাট খুঁজে বের করে অথবা নতুন তৈরি করে।
     */
    public function getOrCreateDirectConversation(User $user, int $recipientId): Conversation
    {
        if ($user->id === $recipientId) {
            throw new InvalidArgumentException('Cannot start a direct conversation with yourself. Use saved messages instead.');
        }

        $recipient = User::findOrFail($recipientId);

        // ২. ব্লক চেক (উভয় পক্ষের কেউ কাউকে ব্লক করেছে কিনা)
        $isBlocked = app(ProfilePrivacyService::class)->isBlocked($recipient, $user);

        if ($isBlocked) {
            throw new AuthorizationException('You cannot start a conversation with this user due to privacy/block settings.');
        }

        // ৩. বিদ্যমান ডিরেক্ট চ্যাট আছে কিনা যাচাই
        $existing = Conversation::where('type', Conversation::TYPE_DIRECT)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $recipientId))
            ->first();

        if ($existing) {
            $existing->load(['users.profile', 'participants', 'lastMessage']);

            return $existing;
        }

        // ৪. গোপনীয়তা যাচাই (Who can message me)
        $privacy = $recipient->privacySettings()->firstOrCreate(['user_id' => $recipient->id]);
        $whoCanMessage = $privacy->who_can_message_me ?? 'everyone';

        $isFriend = in_array($user->id, $recipient->getFriendIds(), true);
        $isFollower = $recipient->isFollowedBy($user);

        if ($whoCanMessage === 'nobody') {
            throw new AuthorizationException('This user does not accept direct messages.');
        }

        if ($whoCanMessage === 'friends' && ! $isFriend) {
            throw new AuthorizationException('Only friends can message this user.');
        }

        if ($whoCanMessage === 'followers' && ! $isFollower && ! $isFriend) {
            throw new AuthorizationException('Only followers can message this user.');
        }

        // নন-ফ্রেন্ড হলে প্রাপকের জন্য মেসেজ রিকোয়েস্ট হিসেবে পেন্ডিং থাকবে
        $isMessageRequest = ! $isFriend;

        return DB::transaction(function () use ($user, $recipient, $isMessageRequest) {
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_DIRECT,
                'creator_id' => $user->id,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'role' => 'member',
                'is_request' => false,
                'request_status' => 'accepted',
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $recipient->id,
                'role' => 'member',
                'is_request' => $isMessageRequest,
                'request_status' => $isMessageRequest ? 'pending' : 'accepted',
            ]);

            $conversation->load(['users.profile', 'participants']);

            return $conversation;
        });
    }

    /**
     * একাধিক সদস্য নিয়ে গ্রুপ চ্যাট তৈরি করা।
     */
    public function createGroupConversation(User $creator, string $title, array $participantIds, ?string $description = null, ?string $avatarUrl = null): Conversation
    {
        $allUserIds = array_unique(array_merge([$creator->id], array_map('intval', $participantIds)));

        if (count($allUserIds) < 2) {
            throw new InvalidArgumentException('A group conversation must have at least 2 members.');
        }

        // গ্রুপ পারমিশন যাচাই (Who can add me to groups)
        $validUserIds = [$creator->id];
        foreach ($allUserIds as $userId) {
            if ($userId === $creator->id) {
                continue;
            }

            $candidate = User::find($userId);
            if (! $candidate) {
                continue;
            }

            $privacy = $candidate->privacySettings()->firstOrCreate(['user_id' => $candidate->id]);
            $whoCanAdd = $privacy->who_can_add_to_groups ?? 'everyone';

            if ($whoCanAdd === 'nobody') {
                continue;
            }

            if ($whoCanAdd === 'friends' && ! in_array($creator->id, $candidate->getFriendIds(), true)) {
                continue;
            }

            $validUserIds[] = $userId;
        }

        return DB::transaction(function () use ($creator, $title, $validUserIds, $description, $avatarUrl) {
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_GROUP,
                'title' => trim($title),
                'description' => $description ? trim($description) : null,
                'avatar_url' => $avatarUrl,
                'creator_id' => $creator->id,
                'settings' => [
                    'can_send_messages' => 'all',
                    'can_add_members' => 'all',
                    'can_edit_info' => 'admins',
                ],
            ]);

            foreach ($validUserIds as $userId) {
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                    'role' => $userId === $creator->id ? 'admin' : 'member',
                    'is_request' => false,
                    'request_status' => 'accepted',
                ]);
            }

            // সিস্টেম মেসেজ সংরক্ষণ
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $creator->id,
                'type' => 'system',
                'body' => "{$creator->name} created the group \"{$title}\".",
                'delivery_status' => Message::STATUS_SEEN,
                'sent_at' => now(),
            ]);

            $conversation->load(['users.profile', 'participants', 'lastMessage']);

            return $conversation;
        });
    }

    /**
     * নির্দিষ্ট কনভার্সনের মেসেজ হিস্ট্রি পেজিনেশন ও সার্চ সহ লোড করা।
     */
    public function getMessages(Conversation $conversation, User $user, int $perPage = 30, array $filters = []): LengthAwarePaginator
    {
        if (! $conversation->hasParticipant($user->id)) {
            throw new AuthorizationException('You are not a participant in this conversation.');
        }

        $participant = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first();

        $query = Message::where('conversation_id', $conversation->id)
            ->with(['sender.profile', 'media', 'reactions.user.profile', 'replyTo.sender.profile']);

        // ব্যবহারকারীর নিজের জন্য ডিলিট করা মেসেজ ফিল্টার আউট
        $query->whereDoesntHave('userDeletions', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // যদি পূর্বে চ্যাট হিস্ট্রি মুছে ফেলা হয়ে থাকে (cleared_at)
        if ($participant && $participant->cleared_at) {
            $query->where('created_at', '>', $participant->cleared_at);
        }

        // ইন-চ্যাট টেক্সট সার্চ
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where('body', 'like', "%{$search}%");
        }

        // টাইপ ফিল্টার (media, voice, files, links)
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $paginator = $query->latest('id')->paginate($perPage);

        $paginator->getCollection()->transform(fn (Message $m) => $m->toResponseArray($user));

        return $paginator;
    }

    /**
     * নতুন মেসেজ পাঠানো এবং WebSocket ব্রডকাস্ট করা।
     */
    public function sendMessage(User $sender, Conversation $conversation, array $data): Message
    {
        if (! $conversation->hasParticipant($sender->id)) {
            throw new AuthorizationException('You are not a participant in this conversation.');
        }

        $participant = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $sender->id)
            ->first();

        // গ্রুপ পারমিশন চেক: শুধুমাত্র এডমিনরা পাঠাতে পারবে কিনা
        if ($conversation->isGroup()) {
            $settings = $conversation->settings ?? [];
            if (($settings['can_send_messages'] ?? 'all') === 'admins' && $participant->role !== 'admin') {
                throw new AuthorizationException('Only group admins are permitted to send messages in this group.');
            }
        } elseif ($conversation->type === Conversation::TYPE_DIRECT) {
            $otherParticipant = ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', '!=', $sender->id)
                ->first();
            if ($otherParticipant) {
                $otherUser = User::find($otherParticipant->user_id);
                if ($otherUser && app(ProfilePrivacyService::class)->isBlocked($otherUser, $sender)) {
                    throw new AuthorizationException('Communication is blocked between these users.');
                }
            }
        }

        $body = trim($data['body'] ?? '');
        $mediaIds = $data['media_ids'] ?? [];
        $type = $data['type'] ?? 'text';
        $replyToId = isset($data['reply_to_message_id']) ? (int) $data['reply_to_message_id'] : (isset($data['reply_to_id']) ? (int) $data['reply_to_id'] : null);

        if (empty($body) && empty($mediaIds)) {
            throw new InvalidArgumentException('A message must contain text, media, or voice recording.');
        }

        // টাইপ নির্ধারণ
        if ($type === 'text') {
            if (! empty($mediaIds)) {
                $type = 'media';
            } elseif (preg_match('/https?:\/\/[^\s]+/i', $body)) {
                $type = 'link';
            }
        }

        // যদি মেসেজে মিডিয়া থাকে এবং বডি শুধুমাত্র ফাইলের নাম হয়, তবে বডি ফাঁকা রাখা যাতে চ্যাটে টেক্সট হিসেবে ফাইলের নাম না দেখায়
        if (! empty($mediaIds) && ! empty($body)) {
            $attachedMedia = Media::whereIn('id', $mediaIds)->where('user_id', $sender->id)->get();
            foreach ($attachedMedia as $med) {
                $origFilename = $med->metadata['original_filename'] ?? basename($med->original_path);
                $baseFilename = basename($med->original_path);
                if ($body === $origFilename || $body === $baseFilename || (strlen($origFilename) > 3 && str_ends_with($body, $origFilename))) {
                    $body = '';
                    break;
                }
            }
        }

        // Idempotency / Offline Outbox Check
        $clientUuid = $data['client_message_id'] ?? $data['client_uuid'] ?? $data['idempotency_key'] ?? null;
        if ($clientUuid) {
            $existing = Message::where('conversation_id', $conversation->id)
                ->where('sender_id', $sender->id)
                ->where(function ($q) use ($clientUuid) {
                    $q->where('metadata->client_uuid', $clientUuid)
                        ->orWhere('metadata->client_message_id', $clientUuid)
                        ->orWhere('metadata->idempotency_key', $clientUuid);
                })
                ->first();
            if ($existing) {
                $existing->load(['sender.profile', 'media', 'reactions.user', 'replyTo.sender']);

                return $existing;
            }
        }

        return DB::transaction(function () use ($sender, $conversation, $body, $type, $mediaIds, $replyToId, $data, $clientUuid) {
            $metadata = $data['metadata'] ?? [];
            if ($clientUuid) {
                $metadata['client_uuid'] = $clientUuid;
                $metadata['client_message_id'] = $clientUuid;
                $metadata['idempotency_key'] = $clientUuid;
            }

            // লিঙ্ক প্রিভিউ এক্সট্রাকশন (যদি লিঙ্ক থাকে)
            if ($type === 'link' && preg_match('/https?:\/\/[^\s]+/i', $body, $matches)) {
                $url = $matches[0];
                $metadata['link_preview'] = [
                    'url' => $url,
                    'host' => parse_url($url, PHP_URL_HOST) ?? $url,
                ];
            }

            // ১. মেসেজ তৈরি
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'reply_to_message_id' => $replyToId,
                'sender_id' => $sender->id,
                'type' => $type,
                'body' => $body ?: null,
                'delivery_status' => Message::STATUS_SENT,
                'sent_at' => now(),
                'metadata' => $metadata,
            ]);

            // ২. মিডিয়া ফাইল থাকলে মেসেজের সাথে লিঙ্ক করা
            if (! empty($mediaIds)) {
                Media::whereIn('id', $mediaIds)
                    ->where('user_id', $sender->id)
                    ->update([
                        'mediable_type' => Message::class,
                        'mediable_id' => $message->id,
                        'collection' => 'message',
                    ]);
            }

            // ৩. কনভার্সন আপডেট
            $conversation->update([
                'last_message_id' => $message->id,
                'last_message_at' => now(),
            ]);

            // ৪. প্রাপকদের আইডি সংগ্রহ
            $recipientIds = $conversation->participants()
                ->where('user_id', '!=', $sender->id)
                ->pluck('user_id')
                ->toArray();

            // ৫. প্রাপকদের জন্য Redis আনরিড কাউন্টার বাড়ানো
            foreach ($recipientIds as $recipientId) {
                $this->cacheService->increment("conversation:{$conversation->id}:unread:{$recipientId}", 1);
                $this->cacheService->increment("user:{$recipientId}:unread_messages", 1);
            }

            $message->load(['sender.profile', 'media', 'reactions.user', 'replyTo.sender']);

            // ৬. লাইভ WebSocket ব্রডকাস্ট পাঠানো
            $payload = $message->toResponseArray($sender);
            $this->realtimeService->broadcastNewMessage(
                conversationId: $conversation->id,
                messageData: $payload,
                recipientIds: $recipientIds
            );

            app(SyncEventService::class)->recordEvent('message.created', $payload, null, $conversation->id);

            return $message;
        });
    }

    /**
     * প্রেরক কর্তৃক নিজের পূর্ববর্তী মেসেজ এডিট করা।
     */
    public function editMessage(User $user, int $messageId, string $newBody, ?int $expectedVersion = null): Message
    {
        $message = Message::with(['sender.profile', 'media', 'reactions.user', 'replyTo.sender'])->findOrFail($messageId);

        if ((int) $message->sender_id !== (int) $user->id) {
            throw new AuthorizationException('You can only edit your own messages.');
        }

        if ($message->is_deleted_for_everyone) {
            throw new InvalidArgumentException('Deleted messages cannot be edited.');
        }

        // Concurrency: check expected version if supplied
        if ($expectedVersion !== null && (int) ($message->version ?? 1) !== (int) $expectedVersion) {
            throw new ConflictHttpException('This message has been modified on another device. Please refresh and try again.');
        }

        $newBody = trim($newBody);
        if (empty($newBody)) {
            throw new InvalidArgumentException('Message content cannot be empty.');
        }

        $history = $message->edit_history ?? [];
        $history[] = [
            'previous_body' => $message->body,
            'edited_at' => now()->toIso8601String(),
            'version' => $message->version ?? 1,
        ];

        $now = now();
        $newVersion = ($message->version ?? 1) + 1;
        $message->update([
            'body' => $newBody,
            'is_edited' => true,
            'edited_at' => $now,
            'version' => $newVersion,
            'edit_history' => $history,
        ]);

        // লাইভ এডিট ইভেন্ট ব্রডকাস্ট
        $this->realtimeService->broadcastMessageUpdated(
            conversationId: $message->conversation_id,
            messageId: $message->id,
            body: $newBody,
            editedAt: $now->toIso8601String(),
            isEdited: true,
            version: $newVersion,
            senderId: $user->id
        );

        app(SyncEventService::class)->recordEvent('message.updated', [
            'message_id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $user->id,
            'body' => $newBody,
            'edited_at' => $now->toIso8601String(),
            'is_edited' => true,
            'version' => $newVersion,
        ], null, $message->conversation_id);

        return $message;
    }

    /**
     * মেসেজ ডিলিট করা (নিজের জন্য অথবা সবার জন্য)।
     */
    public function deleteMessage(User $user, int $messageId, bool $forEveryone = false): bool
    {
        $message = Message::findOrFail($messageId);
        $conversation = $message->conversation;

        if (! $conversation->hasParticipant($user->id)) {
            throw new AuthorizationException('You are not a participant in this conversation.');
        }

        if ($forEveryone) {
            // সবার জন্য ডিলিট করার অনুমতি: শুধুমাত্র প্রেরক অথবা গ্রুপ এডমিন
            $isSender = (int) $message->sender_id === (int) $user->id;
            $isAdmin = false;

            if ($conversation->isGroup()) {
                $participant = $conversation->participants->firstWhere('user_id', $user->id);
                $isAdmin = $participant && $participant->role === 'admin';
            }

            if (! $isSender && ! $isAdmin) {
                throw new AuthorizationException('You do not have permission to delete this message for everyone.');
            }

            $now = now();
            $newVersion = ($message->version ?? 1) + 1;
            $message->update([
                'is_deleted_for_everyone' => true,
                'deleted_at' => $now,
                'deleted_by' => $user->id,
                'body' => null,
                'version' => $newVersion,
            ]);

            // মিডিয়া ফাইল unlink করা
            $message->media()->update(['mediable_id' => null, 'mediable_type' => null]);

            $this->realtimeService->broadcastMessageDeleted(
                conversationId: $message->conversation_id,
                messageId: $message->id,
                forEveryone: true
            );

            app(SyncEventService::class)->recordEvent('message.deleted', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'for_everyone' => true,
                'deleted_by' => $user->id,
                'version' => $newVersion,
                'deleted_at' => $now->toIso8601String(),
            ], null, $message->conversation_id);

            return true;
        }

        // নিজের জন্য ডিলিট (Delete for Me)
        MessageUserDeletion::firstOrCreate([
            'message_id' => $message->id,
            'user_id' => $user->id,
        ]);

        $this->realtimeService->broadcastMessageDeleted(
            conversationId: $message->conversation_id,
            messageId: $message->id,
            forEveryone: false,
            userId: $user->id
        );

        app(SyncEventService::class)->recordEvent('message.deleted_for_me', [
            'message_id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'user_id' => $user->id,
        ], $user->id, $message->conversation_id);

        return true;
    }

    /**
     * মেসেজে রিঅ্যাকশন যোগ, পরিবর্তন বা প্রত্যাহার করা।
     */
    public function reactToMessage(User $user, int $messageId, string $reaction): array
    {
        $message = Message::findOrFail($messageId);
        if (! $message->conversation->hasParticipant($user->id)) {
            throw new AuthorizationException('You cannot react to messages in conversations you are not part of.');
        }

        $reaction = trim($reaction);
        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->first();

        $action = 'added';

        if ($existing && $existing->reaction === $reaction) {
            // একই রিঅ্যাকশনে পুনরায় ক্লিক করলে প্রত্যাহার
            $existing->delete();
            $action = 'removed';
        } elseif ($existing) {
            // ভিন্ন রিঅ্যাকশনে পরিবর্তন
            $existing->update(['reaction' => $reaction]);
            $action = 'changed';
        } else {
            // নতুন রিঅ্যাকশন
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'reaction' => $reaction,
            ]);
            $action = 'added';
        }

        // নতুন রিঅ্যাকশন সামারি তৈরি
        $allReactions = MessageReaction::where('message_id', $message->id)->get();
        $counts = [];
        foreach ($allReactions as $rx) {
            $counts[$rx->reaction] = ($counts[$rx->reaction] ?? 0) + 1;
        }

        $summary = [
            'action' => $action,
            'counts' => $counts,
            'total' => $allReactions->count(),
            'my_reaction' => $action === 'removed' ? null : $reaction,
            'user_reaction' => $action === 'removed' ? null : $reaction,
        ];

        // লাইভ ব্রডকাস্ট
        $this->realtimeService->broadcastMessageReaction(
            conversationId: $message->conversation_id,
            messageId: $message->id,
            userId: $user->id,
            userName: $user->name ?? $user->username,
            reaction: $reaction,
            action: $action,
            reactionsSummary: $summary
        );

        app(SyncEventService::class)->recordEvent('message.reaction', [
            'message_id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'user_id' => $user->id,
            'reaction' => $reaction,
            'action' => $action,
        ], null, $message->conversation_id);

        return $summary;
    }

    /**
     * এক বা একাধিক চ্যাটে মেসেজ ফরোয়ার্ড করা।
     */
    public function forwardMessage(User $user, int $messageId, array $targetConversationIds): array
    {
        $sourceMessage = Message::with(['media'])->findOrFail($messageId);
        if (! $sourceMessage->conversation->hasParticipant($user->id)) {
            throw new AuthorizationException('You cannot forward messages from conversations you are not member of.');
        }

        $createdMessages = [];

        foreach ($targetConversationIds as $convId) {
            $convId = (int) $convId;
            $targetConv = Conversation::find($convId);
            if (! $targetConv || ! $targetConv->hasParticipant($user->id)) {
                continue;
            }

            $fwd = DB::transaction(function () use ($user, $targetConv, $sourceMessage) {
                $msg = Message::create([
                    'conversation_id' => $targetConv->id,
                    'sender_id' => $user->id,
                    'type' => $sourceMessage->type,
                    'body' => $sourceMessage->body,
                    'is_forwarded' => true,
                    'delivery_status' => Message::STATUS_SENT,
                    'sent_at' => now(),
                    'metadata' => $sourceMessage->metadata,
                ]);

                // মিডিয়া ডুপ্লিকেট লিঙ্ক
                foreach ($sourceMessage->media as $media) {
                    Media::create([
                        'user_id' => $user->id,
                        'mediable_type' => Message::class,
                        'mediable_id' => $msg->id,
                        'collection' => 'message',
                        'disk' => $media->disk,
                        'original_path' => $media->original_path,
                        'thumbnail_path' => $media->thumbnail_path,
                        'medium_path' => $media->medium_path,
                        'large_path' => $media->large_path,
                        'mime_type' => $media->mime_type,
                        'size' => $media->size,
                        'processing_status' => 'ready',
                    ]);
                }

                $targetConv->update([
                    'last_message_id' => $msg->id,
                    'last_message_at' => now(),
                ]);

                $recipientIds = $targetConv->participants()
                    ->where('user_id', '!=', $user->id)
                    ->pluck('user_id')
                    ->toArray();

                foreach ($recipientIds as $recId) {
                    $this->cacheService->increment("conversation:{$targetConv->id}:unread:{$recId}", 1);
                    $this->cacheService->increment("user:{$recId}:unread_messages", 1);
                }

                $msg->load(['sender.profile', 'media', 'reactions.user']);

                $this->realtimeService->broadcastNewMessage(
                    conversationId: $targetConv->id,
                    messageData: $msg->toResponseArray($user),
                    recipientIds: $recipientIds
                );

                return $msg;
            });

            $createdMessages[] = $fwd->toResponseArray($user);
        }

        return $createdMessages;
    }

    /**
     * মেসেজ ডেলিভারি স্ট্যাটাস মার্ক করা।
     */
    public function markAsDelivered(Message $message, User $recipient): Message
    {
        if ($message->sender_id === $recipient->id) {
            return $message;
        }

        if ($message->delivery_status === Message::STATUS_SENT) {
            $now = now();
            $message->update([
                'delivery_status' => Message::STATUS_DELIVERED,
                'delivered_at' => $now,
            ]);

            $this->realtimeService->broadcastMessageDelivered(
                conversationId: $message->conversation_id,
                messageId: $message->id,
                deliveredAt: $now->toIso8601String()
            );

            app(SyncEventService::class)->recordEvent('message.delivered', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'delivered_at' => $now->toIso8601String(),
            ], null, $message->conversation_id);
        }

        return $message;
    }

    /**
     * কনভার্সন রিড (seen) মার্ক করা এবং আনরিড কাউন্টার রিসেট করা।
     */
    public function markConversationAsRead(Conversation $conversation, User $user): int
    {
        $participant = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            throw new AuthorizationException('You are not a participant in this conversation.');
        }

        $now = now();

        $updatedCount = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->where('delivery_status', '!=', Message::STATUS_SEEN)
            ->update([
                'delivery_status' => Message::STATUS_SEEN,
                'read_at' => $now,
            ]);

        $participant->update([
            'last_read_message_id' => $conversation->last_message_id,
            'last_read_at' => $now,
        ]);

        $unreadKey = "conversation:{$conversation->id}:unread:{$user->id}";
        $previousUnread = (int) ($this->cacheService->get($unreadKey) ?? 0);
        $this->cacheService->set($unreadKey, 0, 86400 * 7);

        if ($previousUnread > 0) {
            $this->cacheService->decrement("user:{$user->id}:unread_messages", $previousUnread);
        }

        $this->realtimeService->broadcastMessageRead(
            conversationId: $conversation->id,
            readerId: $user->id,
            lastReadMessageId: $conversation->last_message_id,
            readAt: $now->toIso8601String()
        );

        app(SyncEventService::class)->recordEvent('conversation.read', [
            'reader_id' => $user->id,
            'conversation_id' => $conversation->id,
            'last_read_message_id' => $conversation->last_message_id,
            'read_at' => $now->toIso8601String(),
        ], null, $conversation->id);

        return $updatedCount;
    }

    /**
     * কনভার্সন আনরিড হিসেবে মার্ক করা।
     */
    public function markConversationAsUnread(User $user, int $conversationId): bool
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $participant->update(['last_read_message_id' => 0]);

        $unreadKey = "conversation:{$conversationId}:unread:{$user->id}";
        $this->cacheService->set($unreadKey, 1, 86400 * 7);
        $this->cacheService->increment("user:{$user->id}:unread_messages", 1);

        return true;
    }

    /**
     * পিন বা আনপিন করা।
     */
    public function togglePinConversation(User $user, int $conversationId): bool
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $newPinned = ! $participant->is_pinned;
        $participant->update(['is_pinned' => $newPinned]);

        return $newPinned;
    }

    /**
     * আর্কাইভ বা আনআর্কাইভ করা।
     */
    public function toggleArchiveConversation(User $user, int $conversationId): bool
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $newArchived = ! $participant->is_archived;
        $participant->update(['is_archived' => $newArchived]);

        return $newArchived;
    }

    /**
     * মিউট বা আনমিউট করা।
     */
    public function muteConversation(User $user, int $conversationId, ?string $duration = null): bool
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($duration === null || $duration === 'unmute') {
            $participant->update([
                'is_muted' => false,
                'muted_until' => null,
            ]);

            return false;
        }

        $mutedUntil = match ($duration) {
            '1h', '1_hour' => now()->addHour(),
            '8h', '8_hours' => now()->addHours(8),
            '24h', '24_hours' => now()->addDay(),
            default => null, // forever
        };

        $participant->update([
            'is_muted' => true,
            'muted_until' => $mutedUntil,
        ]);

        return true;
    }

    /**
     * চ্যাট হিস্ট্রি নিজের জন্য মুছে ফেলা।
     */
    public function clearConversation(User $user, int $conversationId): bool
    {
        $conversation = Conversation::findOrFail($conversationId);
        if (! $conversation->hasParticipant($user->id)) {
            throw new AuthorizationException('You are not a participant in this conversation.');
        }

        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $now = now();
        $participant->update(['cleared_at' => $now]);

        // Reset unread counts in cache for this conversation
        $this->cacheService->set("conversation:{$conversationId}:unread:{$user->id}", 0);

        // Broadcast to user's devices
        $this->realtimeService->broadcastConversationCleared(
            conversationId: $conversationId,
            userId: $user->id,
            clearedAt: $now->toIso8601String()
        );

        // Record in SyncEventService for user
        app(SyncEventService::class)->recordEvent('conversation.cleared', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'cleared_at' => $now->toIso8601String(),
        ], $user->id, $conversationId);

        return true;
    }

    /**
     * গ্রুপ চ্যাট ত্যাগ করা।
     */
    public function leaveGroup(User $user, int $conversationId): bool
    {
        $conversation = Conversation::where('id', $conversationId)->where('type', Conversation::TYPE_GROUP)->firstOrFail();
        $participant = ConversationParticipant::where('conversation_id', $conversation->id)->where('user_id', $user->id)->firstOrFail();

        $participant->delete();

        // যদি কোনো মেম্বার না থাকে, কনভার্সনটি মুছে ফেলা
        $remainingCount = $conversation->participants()->count();
        if ($remainingCount === 0) {
            $conversation->delete();

            return true;
        }

        // যদি এডমিন ত্যাগ করেন এবং আর কোনো এডমিন না থাকে, অন্য একজনকে এডমিন করা
        $adminExists = $conversation->participants()->where('role', 'admin')->exists();
        if (! $adminExists) {
            $nextMember = $conversation->participants()->first();
            $nextMember?->update(['role' => 'admin']);
        }

        // সিস্টেম মেসেজ প্রেরণ
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => 'system',
            'body' => "{$user->name} left the group.",
            'delivery_status' => Message::STATUS_SEEN,
            'sent_at' => now(),
        ]);

        $this->realtimeService->broadcastConversationUpdated(
            conversationId: $conversation->id,
            action: 'member_removed',
            payload: ['user_id' => $user->id, 'reason' => 'left']
        );

        return true;
    }

    /**
     * গ্রুপের নাম, ছবি ও সেটিংস আপডেট করা।
     */
    public function updateGroupInfo(User $user, int $conversationId, array $data): Conversation
    {
        $conversation = Conversation::where('id', $conversationId)->where('type', Conversation::TYPE_GROUP)->firstOrFail();
        $participant = ConversationParticipant::where('conversation_id', $conversation->id)->where('user_id', $user->id)->firstOrFail();

        $settings = $conversation->settings ?? [];
        if (($settings['can_edit_info'] ?? 'admins') === 'admins' && $participant->role !== 'admin') {
            throw new AuthorizationException('Only group admins can update group information.');
        }

        $updateData = [];
        if (isset($data['title'])) {
            $updateData['title'] = trim((string) $data['title']);
        }
        if (isset($data['description'])) {
            $updateData['description'] = trim((string) $data['description']);
        }
        if (isset($data['avatar_url'])) {
            $updateData['avatar_url'] = (string) $data['avatar_url'];
        }
        if (isset($data['settings']) && is_array($data['settings'])) {
            $updateData['settings'] = array_merge($settings, $data['settings']);
        }

        $conversation->update($updateData);

        // সিস্টেম মেসেজ
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => 'system',
            'body' => "{$user->name} updated the group settings.",
            'delivery_status' => Message::STATUS_SEEN,
            'sent_at' => now(),
        ]);

        $this->realtimeService->broadcastConversationUpdated(
            conversationId: $conversation->id,
            action: 'updated',
            payload: $conversation->toArray()
        );

        return $conversation;
    }

    /**
     * গ্রুপে নতুন সদস্য যুক্ত করা।
     */
    public function addGroupMembers(User $user, int $conversationId, array $newUserIds): array
    {
        $conversation = Conversation::where('id', $conversationId)->where('type', Conversation::TYPE_GROUP)->firstOrFail();
        $participant = ConversationParticipant::where('conversation_id', $conversation->id)->where('user_id', $user->id)->firstOrFail();

        $settings = $conversation->settings ?? [];
        if (($settings['can_add_members'] ?? 'all') === 'admins' && $participant->role !== 'admin') {
            throw new AuthorizationException('Only group admins can add new members.');
        }

        $added = [];
        foreach ($newUserIds as $newId) {
            $newId = (int) $newId;
            if ($conversation->hasParticipant($newId)) {
                continue;
            }

            $newUser = User::find($newId);
            if (! $newUser) {
                continue;
            }

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $newUser->id,
                'role' => 'member',
                'is_request' => false,
                'request_status' => 'accepted',
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'type' => 'system',
                'body' => "{$user->name} added {$newUser->name} to the group.",
                'delivery_status' => Message::STATUS_SEEN,
                'sent_at' => now(),
            ]);

            $added[] = $newUser->id;
        }

        $this->realtimeService->broadcastConversationUpdated(
            conversationId: $conversation->id,
            action: 'member_added',
            payload: ['added_user_ids' => $added]
        );

        return $added;
    }

    /**
     * গ্রুপ থেকে সদস্য অপসারণ করা।
     */
    public function removeGroupMember(User $user, int $conversationId, int $targetUserId): bool
    {
        $conversation = Conversation::where('id', $conversationId)->where('type', Conversation::TYPE_GROUP)->firstOrFail();
        $participant = ConversationParticipant::where('conversation_id', $conversation->id)->where('user_id', $user->id)->first();

        if (! $participant || $participant->role !== 'admin') {
            throw new AuthorizationException('Only group admins can remove members.');
        }

        if ($targetUserId === (int) $conversation->creator_id) {
            throw new AuthorizationException('The group creator cannot be removed from the group.');
        }

        $targetPart = ConversationParticipant::where('conversation_id', $conversation->id)->where('user_id', $targetUserId)->firstOrFail();
        $targetUser = $targetPart->user;
        $targetPart->delete();

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => 'system',
            'body' => "{$user->name} removed {$targetUser?->name} from the group.",
            'delivery_status' => Message::STATUS_SEEN,
            'sent_at' => now(),
        ]);

        $this->realtimeService->broadcastConversationUpdated(
            conversationId: $conversation->id,
            action: 'member_removed',
            payload: ['user_id' => $targetUserId, 'removed_by' => $user->id]
        );

        return true;
    }

    /**
     * গ্রুপ সদস্যের ভূমিকা পরিবর্তন করা (এডমিন / মেম্বার)।
     */
    public function updateMemberRole(User $user, int $conversationId, int $targetUserId, string $role): bool
    {
        $conversation = Conversation::where('id', $conversationId)->where('type', Conversation::TYPE_GROUP)->firstOrFail();
        $participant = ConversationParticipant::where('conversation_id', $conversation->id)->where('user_id', $user->id)->first();

        if (! $participant || $participant->role !== 'admin') {
            throw new AuthorizationException('Only group admins can manage member roles.');
        }

        if ($targetUserId === (int) $conversation->creator_id && $role !== 'admin') {
            throw new AuthorizationException('The group creator cannot be demoted.');
        }

        if (! in_array($role, ['admin', 'member'])) {
            throw new InvalidArgumentException('Invalid role specified.');
        }

        $targetPart = ConversationParticipant::where('conversation_id', $conversation->id)->where('user_id', $targetUserId)->firstOrFail();
        $targetPart->update(['role' => $role]);

        return true;
    }

    /**
     * মেসেজ রিকোয়েস্ট একসেপ্ট, ডিলিট বা ব্লক করা।
     */
    public function handleMessageRequest(User $user, int $conversationId, string $action): bool
    {
        $conversation = Conversation::findOrFail($conversationId);
        $participant = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($action === 'accept') {
            $participant->update([
                'is_request' => false,
                'request_status' => 'accepted',
            ]);

            return true;
        }

        if ($action === 'delete') {
            $participant->update([
                'request_status' => 'declined',
                'cleared_at' => now(),
            ]);

            return true;
        }

        if ($action === 'block') {
            $otherUser = $conversation->getOtherParticipant($user->id);
            if ($otherUser) {
                Friendship::updateOrCreate(
                    ['user_id' => $user->id, 'friend_id' => $otherUser->id],
                    ['status' => Friendship::STATUS_BLOCKED]
                );
            }
            $participant->update(['request_status' => 'declined', 'cleared_at' => now()]);

            return true;
        }

        return false;
    }

    /**
     * ড্রাফট মেসেজ সংরক্ষণ করা।
     */
    public function saveDraft(User $user, int $conversationId, ?string $draft): bool
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            return false;
        }

        $participant->update([
            'draft_message' => $draft ? trim($draft) : null,
        ]);

        return true;
    }

    /**
     * কনভার্সনের শেয়ারকৃত মিডিয়া, ফাইল বা লিঙ্ক তালিকা লোড করা।
     */
    public function getConversationMedia(Conversation $conversation, User $user, string $type = 'media', int $perPage = 30): array
    {
        if (! $conversation->hasParticipant($user->id)) {
            throw new AuthorizationException('You are not a participant in this conversation.');
        }

        if ($type === 'links') {
            $messages = Message::where('conversation_id', $conversation->id)
                ->where('type', 'link')
                ->whereNotNull('metadata->link_preview')
                ->latest('id')
                ->limit($perPage)
                ->get();

            return $messages->map(fn ($m) => [
                'id' => $m->id,
                'link' => $m->metadata['link_preview'] ?? ['url' => $m->body],
                'sent_at' => $m->sent_at?->toIso8601String(),
            ])->toArray();
        }

        // মিডিয়া এবং ফাইলসমূহ Media টেবিল থেকে আনা
        $mediaQuery = Media::where('mediable_type', Message::class)
            ->whereIn('mediable_id', function ($q) use ($conversation) {
                $q->select('id')->from('messages')->where('conversation_id', $conversation->id)->where('is_deleted_for_everyone', false);
            });

        if ($type === 'files') {
            $mediaQuery->where(function ($q) {
                $q->where('mime_type', 'like', '%pdf%')
                    ->orWhere('mime_type', 'like', '%word%')
                    ->orWhere('mime_type', 'like', '%excel%')
                    ->orWhere('mime_type', 'like', '%zip%')
                    ->orWhere('mime_type', 'like', '%document%')
                    ->orWhere('mime_type', 'like', '%text%');
            });
        } else {
            // Photos and videos
            $mediaQuery->where(function ($q) {
                $q->where('mime_type', 'like', 'image/%')
                    ->orWhere('mime_type', 'like', 'video/%');
            });
        }

        $items = $mediaQuery->latest('id')->limit($perPage)->get();

        return $items->map(fn ($m) => $m->toResponseArray())->toArray();
    }

    /**
     * ইউজারের মোট আনরিড মেসেজ সংখ্যা পাওয়া।
     */
    public function getUnreadMessageCount(User $user): int
    {
        $totalKey = "user:{$user->id}:unread_messages";
        $cached = $this->cacheService->get($totalKey);

        if ($cached !== null && (int) $cached >= 0) {
            return (int) $cached;
        }

        $count = Message::whereHas('conversation.participants', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->where('is_archived', false)
                ->where('request_status', 'accepted');
        })
            ->where('sender_id', '!=', $user->id)
            ->where('delivery_status', '!=', Message::STATUS_SEEN)
            ->whereDoesntHave('userDeletions', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        $this->cacheService->set($totalKey, $count, 86400);

        return $count;
    }

    /**
     * কনভার্সনে মেসেজ পিন করা।
     */
    public function pinMessage(User $user, int $conversationId, int $messageId): PinnedMessage
    {
        $conversation = Conversation::findOrFail($conversationId);
        if (! $conversation->hasParticipant($user->id)) {
            throw new AuthorizationException('You are not a participant in this conversation.');
        }

        $message = Message::where('conversation_id', $conversationId)->findOrFail($messageId);

        $pin = PinnedMessage::firstOrCreate(
            [
                'conversation_id' => $conversationId,
                'message_id' => $message->id,
            ],
            [
                'pinned_by_id' => $user->id,
                'pinned_at' => now(),
            ]
        );

        $payload = [
            'conversation_id' => $conversationId,
            'message_id' => $message->id,
            'pinned_by' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'pinned_at' => now()->toIso8601String(),
        ];

        $this->realtimeService->broadcastToConversation($conversationId, 'message.pinned', $payload);
        app(SyncEventService::class)->recordEvent('message.pinned', $payload, null, $conversationId);

        return $pin;
    }

    /**
     * কনভার্সন থেকে মেসেজ আনপিন করা।
     */
    public function unpinMessage(User $user, int $conversationId, int $messageId): bool
    {
        $conversation = Conversation::findOrFail($conversationId);
        if (! $conversation->hasParticipant($user->id)) {
            throw new AuthorizationException('You are not a participant in this conversation.');
        }

        $deleted = PinnedMessage::where('conversation_id', $conversationId)
            ->where('message_id', $messageId)
            ->delete();

        if ($deleted) {
            $payload = [
                'conversation_id' => $conversationId,
                'message_id' => $messageId,
                'unpinned_by' => $user->id,
            ];
            $this->realtimeService->broadcastToConversation($conversationId, 'message.unpinned', $payload);
            app(SyncEventService::class)->recordEvent('message.unpinned', $payload, null, $conversationId);
        }

        return (bool) $deleted;
    }

    /**
     * কনভার্সনের পিন করা মেসেজগুলো পাওয়া।
     */
    public function getPinnedMessages(Conversation $conversation, User $user): array
    {
        if (! $conversation->hasParticipant($user->id)) {
            throw new AuthorizationException('You are not a participant in this conversation.');
        }

        $pins = PinnedMessage::where('conversation_id', $conversation->id)
            ->with(['message.sender.profile', 'message.reactions.user', 'pinnedBy'])
            ->orderBy('pinned_at', 'desc')
            ->get();

        return $pins->map(fn (PinnedMessage $p) => [
            'id' => $p->id,
            'message' => $p->message?->toResponseArray($user),
            'pinned_by' => [
                'id' => $p->pinnedBy?->id,
                'name' => $p->pinnedBy?->name,
            ],
            'pinned_at' => $p->pinned_at?->toIso8601String(),
        ])->all();
    }

    /**
     * মেসেজ সেভ করা (Saved Messages)।
     */
    public function saveMessage(User $user, int $messageId): SavedMessage
    {
        $message = Message::with('conversation')->findOrFail($messageId);
        if (! $message->conversation->hasParticipant($user->id)) {
            throw new AuthorizationException('You do not have access to this message.');
        }

        return SavedMessage::firstOrCreate(
            [
                'user_id' => $user->id,
                'message_id' => $message->id,
            ],
            [
                'saved_at' => now(),
            ]
        );
    }

    /**
     * সেভ করা মেসেজ রিমুভ করা।
     */
    public function unsaveMessage(User $user, int $messageId): bool
    {
        return (bool) SavedMessage::where('user_id', $user->id)
            ->where('message_id', $messageId)
            ->delete();
    }

    /**
     * ইউজারের সেভ করা মেসেজগুলোর তালিকা।
     */
    public function getSavedMessages(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return SavedMessage::where('user_id', $user->id)
            ->with(['message.sender.profile', 'message.conversation', 'message.media'])
            ->orderBy('saved_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * মেসেজ ও কনভার্সনে গ্লোবাল ও ইন-চ্যাট অনুসন্ধান।
     */
    public function searchMessenger(User $user, string $query, ?int $conversationId = null, string $type = 'all', int $perPage = 20): LengthAwarePaginator
    {
        $queryBuilder = Message::whereHas('conversation.participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->where('is_deleted_for_everyone', false)
            ->whereDoesntHave('userDeletions', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['sender.profile', 'conversation', 'media']);

        if ($conversationId) {
            $queryBuilder->where('conversation_id', $conversationId);
        }

        if ($type === 'media') {
            $queryBuilder->where(function ($q) {
                $q->whereIn('type', ['image', 'video'])->orWhereHas('media');
            });
        } elseif ($type === 'file') {
            $queryBuilder->where('type', 'file');
        } elseif ($type === 'voice') {
            $queryBuilder->where('type', 'voice');
        }

        if (! empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('body', 'like', "%{$query}%")
                    ->orWhereHas('sender', function ($sq) use ($query) {
                        $sq->where('name', 'like', "%{$query}%")
                            ->orWhere('username', 'like', "%{$query}%");
                    })
                    ->orWhereHas('media', function ($mq) use ($query) {
                        $mq->where('original_path', 'like', "%{$query}%")
                            ->orWhere('name', 'like', "%{$query}%")
                            ->orWhere('metadata->original_filename', 'like', "%{$query}%");
                    });
            });
        }

        return $queryBuilder->orderBy('id', 'desc')->paginate($perPage);
    }
}
