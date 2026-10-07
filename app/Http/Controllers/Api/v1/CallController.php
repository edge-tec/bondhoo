<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MessengerSyncEvent;
use App\Services\Calling\CallingService;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function __construct(
        protected CallingService $callingService
    ) {}

    /**
     * Initiate a 1-to-1 or group audio/video call.
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
            'receiver_id' => ['nullable', 'integer', 'exists:users,id'],
            'call_type' => ['required', 'string', 'in:audio,video,group_audio,group_video'],
        ]);

        if (empty($validated['conversation_id']) && empty($validated['receiver_id'])) {
            return $this->errorResponse('Either conversation_id or receiver_id is required.', 422);
        }

        try {
            if (! empty($validated['conversation_id'])) {
                $conversation = Conversation::findOrFail($validated['conversation_id']);
            } else {
                $messengerService = app(MessengerServiceInterface::class);
                $conversation = $messengerService->getOrCreateDirectConversation($request->user(), (int) $validated['receiver_id']);
            }

            $call = $this->callingService->initiateCall(
                caller: $request->user(),
                conversation: $conversation,
                callType: $validated['call_type']
            );

            return $this->successResponse(
                data: $call->toResponseArray($request->user()),
                message: 'Call initiated successfully.',
                statusCode: 201
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Respond to an incoming call (accept, reject, busy).
     */
    public function respond(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:accept,reject,busy'],
        ]);

        try {
            $call = $this->callingService->respondToCall(
                user: $request->user(),
                callId: $id,
                action: $validated['action']
            );

            return $this->successResponse(
                data: $call->toResponseArray($request->user()),
                message: "Call {$validated['action']}ed successfully."
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Leave or end an ongoing call.
     */
    public function leave(Request $request, int $id): JsonResponse
    {
        try {
            $call = $this->callingService->leaveCall(
                user: $request->user(),
                callId: $id
            );

            return $this->successResponse(
                data: $call->toResponseArray($request->user()),
                message: 'Left the call successfully.'
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Send WebRTC signal (offer, answer, candidate, screen_share).
     */
    public function signal(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'signal_type' => ['required', 'string', 'in:offer,answer,candidate,screen_share,renegotiate'],
            'payload' => ['required', 'array'],
            'target_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        try {
            $signalData = $this->callingService->sendSignal(
                sender: $request->user(),
                callId: $id,
                signalType: $validated['signal_type'],
                payload: $validated['payload'],
                targetUserId: $validated['target_user_id'] ?? null
            );

            return $this->successResponse(
                data: $signalData,
                message: 'Signal transmitted successfully.'
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Retrieve all signaling events for a specific call (offer, answer, candidates).
     */
    public function signals(Request $request, int $id): JsonResponse
    {
        try {
            $call = $this->callingService->getCall($id, $request->user());

            $signals = MessengerSyncEvent::where('conversation_id', $call->conversation_id)
                ->where('event_type', 'call.signal')
                ->where(function ($q) use ($call) {
                    $q->where('payload->call_id', $call->id)
                        ->orWhere('payload->call_id', (string) $call->id);
                })
                ->orderBy('id', 'asc')
                ->get()
                ->pluck('payload')
                ->values()
                ->all();

            return $this->successResponse(
                data: $signals,
                message: 'Call signals retrieved successfully.'
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }

    /**
     * Update participant state (mute, camera, screen sharing).
     */
    public function updateState(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'is_muted' => ['nullable', 'boolean'],
            'is_camera_off' => ['nullable', 'boolean'],
            'is_screen_sharing' => ['nullable', 'boolean'],
        ]);

        try {
            $participant = $this->callingService->updateParticipantState(
                user: $request->user(),
                callId: $id,
                state: $validated
            );

            return $this->successResponse(
                data: $participant->toResponseArray(),
                message: 'Participant state updated successfully.'
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Fetch user's call history.
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $history = $this->callingService->getCallHistory($request->user(), $perPage);

        return $this->successResponse(
            data: $history->getCollection()->map(fn ($call) => $call->toResponseArray($request->user()))->values()->all(),
            message: 'Call history retrieved successfully.',
            meta: [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'total' => $history->total(),
                'per_page' => $history->perPage(),
            ]
        );
    }

    /**
     * Fetch details of a single call.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $call = $this->callingService->getCall($id, $request->user());

            return $this->successResponse(
                data: $call->toResponseArray($request->user()),
                message: 'Call details retrieved successfully.'
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }

    /**
     * Get WebRTC STUN/TURN ICE servers configuration with ephemeral tokens.
     */
    public function iceServers(Request $request): JsonResponse
    {
        $servers = $this->callingService->getIceServers($request->user());

        return $this->successResponse(
            data: ['ice_servers' => $servers],
            message: 'ICE servers retrieved successfully.'
        );
    }
}
