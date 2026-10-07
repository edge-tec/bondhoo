<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PinnedMessageController extends Controller
{
    public function __construct(
        protected MessengerServiceInterface $messengerService
    ) {}

    /**
     * List all pinned messages in a conversation.
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        $pins = $this->messengerService->getPinnedMessages($conversation, $request->user());

        return $this->successResponse(
            data: $pins,
            message: 'Pinned messages retrieved successfully.'
        );
    }

    /**
     * Pin a message in conversation.
     */
    public function store(Request $request, int $id, int $messageId): JsonResponse
    {
        $pin = $this->messengerService->pinMessage($request->user(), $id, $messageId);

        return $this->successResponse(
            data: [
                'id' => $pin->id,
                'conversation_id' => $pin->conversation_id,
                'message_id' => $pin->message_id,
                'pinned_at' => $pin->pinned_at->toIso8601String(),
            ],
            message: 'Message pinned successfully.',
            statusCode: 201
        );
    }

    /**
     * Unpin a message from conversation.
     */
    public function destroy(Request $request, int $id, int $messageId): JsonResponse
    {
        $unpinned = $this->messengerService->unpinMessage($request->user(), $id, $messageId);

        return $this->successResponse(
            data: ['unpinned' => $unpinned],
            message: 'Message unpinned successfully.'
        );
    }
}
