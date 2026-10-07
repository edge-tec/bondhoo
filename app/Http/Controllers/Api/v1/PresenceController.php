<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use App\Services\Messenger\SyncEventService;
use App\Services\ProfilePrivacyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    public function __construct(
        protected CacheServiceInterface $cacheService,
        protected RealtimeServiceInterface $realtimeService
    ) {}

    /**
     * Send heartbeat to keep presence active with multi-session tracking.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = (string) ($request->input('session_id')
            ?? $request->header('X-Session-ID')
            ?? ($request->hasSession() ? $request->session()->getId() : null)
            ?? md5($user->id.$request->ip().($request->userAgent() ?? '')));

        $transitioned = $this->cacheService->registerSessionHeartbeat($user->id, $sessionId, 45);
        $lastSeen = $this->cacheService->getUserLastSeen($user->id);

        if ($transitioned) {
            $this->realtimeService->broadcastPresence($user->id, true, $lastSeen);
            app(SyncEventService::class)->recordEvent('presence.online', [
                'user_id' => $user->id,
                'is_online' => true,
                'last_seen' => $lastSeen,
            ], $user->id);
        }

        return $this->successResponse(
            data: [
                'user_id' => $user->id,
                'online' => true,
                'last_seen' => $lastSeen,
                'session_id' => $sessionId,
                'active_sessions' => $this->cacheService->getActiveSessionCount($user->id),
            ],
            message: 'Heartbeat acknowledged.'
        );
    }

    /**
     * Disconnect/unload heartbeat for a session.
     */
    public function offline(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = (string) ($request->input('session_id')
            ?? $request->header('X-Session-ID')
            ?? ($request->hasSession() ? $request->session()->getId() : null)
            ?? md5($user->id.$request->ip().($request->userAgent() ?? '')));

        $transitioned = $this->cacheService->removeSessionHeartbeat($user->id, $sessionId);
        $isOnline = $this->cacheService->isUserOnline($user->id);
        $lastSeen = $this->cacheService->getUserLastSeen($user->id);

        if ($transitioned) {
            $this->realtimeService->broadcastPresence($user->id, false, $lastSeen);
            app(SyncEventService::class)->recordEvent('presence.offline', [
                'user_id' => $user->id,
                'is_online' => false,
                'last_seen' => $lastSeen,
            ], $user->id);
        }

        return $this->successResponse(
            data: [
                'user_id' => $user->id,
                'online' => $isOnline,
                'last_seen' => $lastSeen,
                'session_id' => $sessionId,
                'active_sessions' => $this->cacheService->getActiveSessionCount($user->id),
            ],
            message: 'Presence session cleared.'
        );
    }

    /**
     * Retrieve a user's online and last-seen presence with privacy authorization.
     */
    public function show(Request $request, int $userId): JsonResponse
    {
        $target = User::with('privacySettings')->find($userId);
        if (! $target) {
            return $this->errorResponse('User not found.', 404);
        }

        $viewer = $request->user();

        // Privacy and blocking authorization check
        if ($viewer && (int) $viewer->id !== (int) $target->id) {
            $privacyService = app(ProfilePrivacyService::class);
            if ($privacyService->isBlocked($target, $viewer) || $privacyService->isBlocked($viewer, $target)) {
                return $this->errorResponse('Presence information is unavailable.', 403);
            }

            $isFriend = in_array($target->id, $viewer->getFriendIds(), true);
            $sharesConversation = DB::table('conversation_participants as cp1')
                ->join('conversation_participants as cp2', 'cp1.conversation_id', '=', 'cp2.conversation_id')
                ->where('cp1.user_id', $viewer->id)
                ->where('cp2.user_id', $target->id)
                ->exists();

            if (! $isFriend && ! $sharesConversation) {
                return $this->errorResponse('You are not authorized to view this user\'s presence.', 403);
            }

            if ($target->privacySettings && ! $target->privacySettings->show_online_status) {
                return $this->successResponse(
                    data: [
                        'user_id' => $userId,
                        'online' => false,
                        'last_seen' => null,
                    ],
                    message: 'Presence status retrieved.'
                );
            }
        }

        $isOnline = $this->cacheService->isUserOnline($userId);
        $lastSeen = $this->cacheService->getUserLastSeen($userId);

        return $this->successResponse(
            data: [
                'user_id' => $userId,
                'online' => $isOnline,
                'last_seen' => $lastSeen,
            ],
            message: 'Presence status retrieved.'
        );
    }

    /**
     * Retrieve currently online friends for the authenticated user.
     */
    public function onlineFriends(Request $request): JsonResponse
    {
        $user = $request->user();
        $friendIds = $user->getFriendIds();

        if (empty($friendIds)) {
            return $this->successResponse(data: [], message: 'No friends found.');
        }

        $friends = User::whereIn('id', $friendIds)
            ->with(['profile', 'privacySettings'])
            ->get();

        $onlineFriends = [];
        foreach ($friends as $friend) {
            if ($friend->privacySettings && ! $friend->privacySettings->show_online_status) {
                continue;
            }

            if ($this->cacheService->isUserOnline($friend->id)) {
                $onlineFriends[] = [
                    'id' => $friend->id,
                    'name' => $friend->name,
                    'username' => $friend->username,
                    'avatar_url' => $friend->profile?->avatar_url,
                    'online' => true,
                    'last_seen' => $this->cacheService->getUserLastSeen($friend->id),
                ];
            }
        }

        return $this->successResponse(
            data: $onlineFriends,
            message: 'Online friends retrieved successfully.'
        );
    }

    /**
     * Set typing status in a conversation.
     * কনভার্সনে ইউজার টাইপ করছেন তা রেকর্ড করে এবং তাৎক্ষণিক ব্রডকাস্ট করে।
     */
    public function typing(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $conversationId = (int) $request->input('conversation_id');

        $this->cacheService->setTyping($conversationId, $user->id);

        // সংশ্লিষ্ট কনভার্সনে লাইভ টাইপিং ইভেন্ট ব্রডকাস্ট করা
        $this->realtimeService->broadcastTyping(
            conversationId: $conversationId,
            userId: $user->id,
            userName: $user->name ?? $user->username,
            isTyping: true
        );

        return $this->successResponse(
            data: [
                'conversation_id' => $conversationId,
                'user_id' => $user->id,
                'is_typing' => true,
            ],
            message: 'Typing status recorded.'
        );
    }

    /**
     * Retrieve typing users in a conversation.
     */
    public function getTyping(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::find($conversationId);
        if (! $conversation || ! $conversation->hasParticipant($request->user()->id)) {
            return $this->errorResponse('You are not a participant in this conversation.', 403);
        }

        $typingUsers = [];
        $participants = $conversation->participants()
            ->where('user_id', '!=', $request->user()->id)
            ->with('user')
            ->get();

        foreach ($participants as $p) {
            if ($this->cacheService->isTyping($conversationId, $p->user_id)) {
                $typingUsers[] = [
                    'id' => $p->user_id,
                    'name' => $p->user?->name ?? 'User',
                ];
            }
        }

        return $this->successResponse(
            data: [
                'conversation_id' => $conversationId,
                'typing_users' => $typingUsers,
            ],
            message: 'Typing users retrieved.'
        );
    }
}
