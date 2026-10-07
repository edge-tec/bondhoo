<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\Contracts\RealtimeServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallSignalController extends Controller
{
    public function __construct(
        protected RealtimeServiceInterface $realtimeService
    ) {}

    /**
     * WebRTC কল সিগন্যালিং এন্ডপয়েন্ট:
     * অডিও ও ভিডিও কল সংক্রান্ত অফার, অ্যান্সার, ক্যান্ডিডেট ইত্যাদি ব্রডকাস্ট করে।
     */
    public function signal(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'signal_type' => ['required', 'string', 'in:offer,answer,candidate,reject,end'],
            'call_type' => ['required', 'string', 'in:audio,video'],
            'payload' => ['nullable', 'array'],
        ]);

        $conversation = Conversation::find($id);

        if (! $conversation) {
            return $this->errorResponse('Conversation not found.', 404);
        }

        if (! $conversation->hasParticipant($user->id)) {
            return $this->errorResponse('You are not authorized to send call signals in this conversation.', 403);
        }

        $this->realtimeService->broadcastCallSignal(
            conversationId: $conversation->id,
            senderId: $user->id,
            senderName: $user->name,
            signalType: $validated['signal_type'],
            callType: $validated['call_type'],
            payload: $validated['payload'] ?? []
        );

        return $this->successResponse([
            'signal_type' => $validated['signal_type'],
            'call_type' => $validated['call_type'],
            'conversation_id' => $conversation->id,
        ], 'Call signal broadcasted successfully.');
    }
}
