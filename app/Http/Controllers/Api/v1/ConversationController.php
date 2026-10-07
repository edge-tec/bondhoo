<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * কনভার্সন কন্ট্রোলার:
 * ইনবক্স তালিকা, গ্রুপ ক্রিয়েশন, পিন, আর্কাইভ, মিউট, ড্রাফট, মেম্বার ও পারমিশন হ্যান্ডলার।
 */
class ConversationController extends Controller
{
    public function __construct(
        protected MessengerServiceInterface $messengerService
    ) {}

    /**
     * লগইন করা ইউজারের চ্যাট তালিকা ফিল্টার ও সার্চ সহ প্রদর্শন।
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->query('per_page', 20);
        $filters = [
            'filter' => (string) $request->query('filter', 'all'),
            'search' => (string) $request->query('search', ''),
        ];

        $conversations = $this->messengerService->getUserConversations($user, $perPage, $filters);

        return $this->successResponse(
            data: $conversations->items(),
            message: 'Conversations retrieved successfully.',
            meta: [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ]
        );
    }

    /**
     * নতুন কনভার্সন তৈরি (১-অন-১, গ্রুপ অথবা সেভড মেসেজেস)।
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $request->has('recipient_id') && $request->has('participant_id')) {
            $request->merge(['recipient_id' => $request->input('participant_id')]);
        }

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:direct,group,saved'],
            'recipient_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required_if:type,group', 'nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'avatar_url' => ['nullable', 'string', 'url'],
            'participant_ids' => ['required_if:type,group', 'nullable', 'array', 'min:1'],
            'participant_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $type = $validated['type'] ?? (isset($validated['recipient_id']) ? Conversation::TYPE_DIRECT : Conversation::TYPE_GROUP);

        if ($type === Conversation::TYPE_SAVED || ($type === Conversation::TYPE_DIRECT && (int) ($validated['recipient_id'] ?? 0) === $user->id)) {
            $conversation = $this->messengerService->getOrCreateSavedConversation($user);
        } elseif ($type === Conversation::TYPE_DIRECT) {
            $recipientId = (int) ($validated['recipient_id'] ?? 0);
            $conversation = $this->messengerService->getOrCreateDirectConversation($user, $recipientId);
        } else {
            $title = $validated['title'];
            $participantIds = $validated['participant_ids'] ?? [];
            $description = $validated['description'] ?? null;
            $avatarUrl = $validated['avatar_url'] ?? null;
            $conversation = $this->messengerService->createGroupConversation($user, $title, $participantIds, $description, $avatarUrl);
        }

        return $this->successResponse(
            data: $conversation->toResponseArray($user),
            message: 'Conversation ready.',
            statusCode: 201
        );
    }

    /**
     * নির্দিষ্ট কনভার্সনের বিস্তারিত তথ্য প্রদর্শন।
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::with(['users.profile', 'participants.user.profile', 'lastMessage.sender'])->findOrFail($id);

        if (! $conversation->hasParticipant($user->id)) {
            return $this->errorResponse('You are not a participant in this conversation.', 403);
        }

        return $this->successResponse(
            data: $conversation->toResponseArray($user),
            message: 'Conversation retrieved successfully.'
        );
    }

    /**
     * কনভার্সন পিন বা আনপিন টগল।
     */
    public function togglePin(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $isPinned = $this->messengerService->togglePinConversation($user, $id);

        return $this->successResponse(
            data: ['conversation_id' => $id, 'is_pinned' => $isPinned],
            message: $isPinned ? 'Conversation pinned.' : 'Conversation unpinned.'
        );
    }

    /**
     * কনভার্সন আর্কাইভ বা আনআর্কাইভ টগল।
     */
    public function toggleArchive(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $isArchived = $this->messengerService->toggleArchiveConversation($user, $id);

        return $this->successResponse(
            data: ['conversation_id' => $id, 'is_archived' => $isArchived],
            message: $isArchived ? 'Conversation archived.' : 'Conversation unarchived.'
        );
    }

    /**
     * কনভার্সন মিউট বা আনমিউট করা।
     */
    public function mute(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'duration' => ['nullable', 'string', 'in:1h,8h,24h,forever,unmute,1_hour,8_hours,24_hours'],
        ]);

        $duration = $validated['duration'] ?? 'forever';
        $isMuted = $this->messengerService->muteConversation($user, $id, $duration);

        return $this->successResponse(
            data: ['conversation_id' => $id, 'is_muted' => $isMuted, 'duration' => $duration],
            message: $isMuted ? 'Conversation muted.' : 'Conversation unmuted.'
        );
    }

    /**
     * কনভার্সন আনরিড হিসেবে মার্ক করা।
     */
    public function markAsUnread(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $this->messengerService->markConversationAsUnread($user, $id);

        return $this->successResponse(
            data: ['conversation_id' => $id, 'is_unread' => true],
            message: 'Conversation marked as unread.'
        );
    }

    /**
     * চ্যাট হিস্ট্রি নিজের জন্য ক্লিয়ার করা।
     */
    public function clear(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $this->messengerService->clearConversation($user, $id);

        return $this->successResponse(
            data: ['conversation_id' => $id, 'cleared' => true],
            message: 'Conversation history cleared for you.'
        );
    }

    /**
     * ড্রাফট মেসেজ সংরক্ষণ করা।
     */
    public function saveDraft(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'draft' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->messengerService->saveDraft($user, $id, $validated['draft'] ?? null);

        return $this->successResponse(
            data: ['conversation_id' => $id, 'draft' => $validated['draft'] ?? null, 'draft_message' => $validated['draft'] ?? null],
            message: 'Draft saved.'
        );
    }

    /**
     * গ্রুপ চ্যাট ত্যাগ করা।
     */
    public function leave(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $this->messengerService->leaveGroup($user, $id);

        return $this->successResponse(
            data: ['conversation_id' => $id, 'left' => true],
            message: 'You have left the group.'
        );
    }

    /**
     * কনভার্সন ডিলিট করা (গ্রুপ হলে ত্যাগ, ডিরেক্ট চ্যাট হলে হিস্ট্রি ক্লিয়ার ও আর্কাইভ)।
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($id);

        if (! $conversation->hasParticipant($user->id)) {
            return $this->errorResponse('You are not a participant in this conversation.', 403);
        }

        if ($conversation->isGroup()) {
            $this->messengerService->leaveGroup($user, $id);
        } else {
            $this->messengerService->clearConversation($user, $id);
            $this->messengerService->toggleArchiveConversation($user, $id);
        }

        return $this->successResponse(
            data: ['conversation_id' => $id, 'deleted' => true],
            message: 'Conversation deleted successfully.'
        );
    }

    /**
     * গ্রুপ চ্যাটের তথ্য ও সেটিংস আপডেট করা।
     */
    public function updateGroup(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'avatar_url' => ['nullable', 'string', 'url'],
            'settings' => ['nullable', 'array'],
        ]);

        $conversation = $this->messengerService->updateGroupInfo($user, $id, $validated);

        return $this->successResponse(
            data: $conversation->toResponseArray($user),
            message: 'Group information updated successfully.'
        );
    }

    /**
     * গ্রুপে নতুন সদস্য যুক্ত করা।
     */
    public function addMembers(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $added = $this->messengerService->addGroupMembers($user, $id, $validated['user_ids']);

        return $this->successResponse(
            data: ['conversation_id' => $id, 'added_user_ids' => $added],
            message: 'Members added successfully.'
        );
    }

    /**
     * গ্রুপ থেকে সদস্য অপসারণ করা।
     */
    public function removeMember(Request $request, int $id, int $userId): JsonResponse
    {
        $user = $request->user();
        $this->messengerService->removeGroupMember($user, $id, $userId);

        return $this->successResponse(
            data: ['conversation_id' => $id, 'removed_user_id' => $userId],
            message: 'Member removed successfully.'
        );
    }

    /**
     * গ্রুপের সদস্যের ভূমিকা পরিবর্তন করা (admin / member)।
     */
    public function updateMemberRole(Request $request, int $id, int $userId): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,member'],
        ]);

        $this->messengerService->updateMemberRole($user, $id, $userId, $validated['role']);

        return $this->successResponse(
            data: ['conversation_id' => $id, 'user_id' => $userId, 'role' => $validated['role']],
            message: 'Member role updated.'
        );
    }

    /**
     * মেসেজ রিকোয়েস্ট একসেপ্ট, ডিলিট বা ব্লক করা।
     */
    public function handleRequest(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:accept,delete,block'],
        ]);

        $handled = $this->messengerService->handleMessageRequest($user, $id, $validated['action']);

        return $this->successResponse(
            data: [
                'conversation_id' => $id,
                'action' => $validated['action'],
                'request_status' => $validated['action'] === 'accept' ? 'accepted' : 'rejected',
                'is_request' => false,
                'success' => $handled,
            ],
            message: "Message request {$validated['action']}ed successfully."
        );
    }

    /**
     * কনভার্সনে শেয়ারকৃত মিডিয়া, ফাইল বা লিঙ্ক তালিকা।
     */
    public function media(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($id);
        $type = (string) $request->query('type', 'media');
        $perPage = (int) $request->query('per_page', 30);

        $items = $this->messengerService->getConversationMedia($conversation, $user, $type, $perPage);

        return $this->successResponse(
            data: $items,
            message: 'Shared items loaded successfully.'
        );
    }
}
