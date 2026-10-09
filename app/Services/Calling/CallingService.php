<?php

namespace App\Services\Calling;

use App\Models\Call;
use App\Models\CallParticipant;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\PrivacySetting;
use App\Models\User;
use App\Services\Contracts\RealtimeServiceInterface;
use App\Services\Messenger\SyncEventService;
use App\Services\ProfilePrivacyService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CallingService
{
    public function __construct(
        protected RealtimeServiceInterface $realtimeService,
        protected SyncEventService $syncEventService,
        protected ProfilePrivacyService $profilePrivacyService
    ) {}

    /**
     * Standard WebRTC STUN/TURN ICE configuration with ephemeral credentials.
     */
    public function getIceServers(?User $user = null): array
    {
        $servers = [
            [
                'urls' => [
                    'stun:stun.l.google.com:19302',
                    'stun:stun1.l.google.com:19302',
                    'stun:stun2.l.google.com:19302',
                    'stun:stun3.l.google.com:19302',
                    'stun:stun4.l.google.com:19302',
                    'stun:stun.cloudflare.com:3478',
                    'stun:openrelay.metered.ca:80',
                ],
            ],
        ];

        $turnUrl = config('services.turn.url', env('TURN_SERVER_URL'));
        $turnSecret = config('services.turn.secret', env('TURN_SERVER_SECRET'));

        // If custom TURN server is configured in env/config (and not non-existent placeholder)
        if ($turnUrl && ! str_contains($turnUrl, 'turn.jugajug.com')) {
            $timestamp = time() + 86400; // 24 hours validity
            $username = $user ? "{$timestamp}:{$user->id}" : "{$timestamp}:guest";
            $credential = base64_encode(hash_hmac('sha1', $username, (string) $turnSecret, true));

            $turnUrls = [
                $turnUrl.'?transport=udp',
                $turnUrl.'?transport=tcp',
            ];

            // If hostname is available, add turns (TLS) port 5349 fallback
            $host = parse_url($turnUrl, PHP_URL_HOST) ?: parse_url('turn://'.$turnUrl, PHP_URL_HOST);
            if ($host) {
                $turnUrls[] = "turns:{$host}:5349?transport=tcp";
            }

            $servers[] = [
                'urls' => $turnUrls,
                'username' => $username,
                'credential' => $credential,
            ];
        } else {
            // Live public OpenRelay TURN fallback for reliable NAT traversal across mobile CGNAT
            $servers[] = [
                'urls' => [
                    'turn:openrelay.metered.ca:80',
                    'turn:openrelay.metered.ca:443',
                    'turn:openrelay.metered.ca:443?transport=tcp',
                    'turns:openrelay.metered.ca:443?transport=tcp',
                ],
                'username' => 'openrelayproject',
                'credential' => 'openrelayproject',
            ];
        }

        return $servers;
    }

    /**
     * Initiate a 1-to-1 or group audio/video call.
     */
    public function initiateCall(User $caller, Conversation $conversation, string $callType = 'audio', ?int $receiverId = null, array $receiverIds = []): Call
    {
        if (! in_array($callType, [Call::TYPE_AUDIO, Call::TYPE_VIDEO, Call::TYPE_GROUP_AUDIO, Call::TYPE_GROUP_VIDEO], true)) {
            throw new InvalidArgumentException("Invalid call type: {$callType}");
        }

        // Verify caller is a participant in the conversation
        if (! $conversation->hasParticipant($caller->id)) {
            throw new AuthorizationException('You are not a participant in this conversation.');
        }

        // Ensure receiver is added to direct conversation if supplied
        if ($receiverId && ! $conversation->hasParticipant($receiverId)) {
            ConversationParticipant::firstOrCreate([
                'conversation_id' => $conversation->id,
                'user_id' => $receiverId,
            ], [
                'role' => 'member',
                'request_status' => 'accepted',
            ]);
        }

        foreach ($receiverIds as $rId) {
            $rId = (int) $rId;
            if ($rId > 0 && ! $conversation->hasParticipant($rId)) {
                ConversationParticipant::firstOrCreate([
                    'conversation_id' => $conversation->id,
                    'user_id' => $rId,
                ], [
                    'role' => 'member',
                    'request_status' => 'accepted',
                ]);
            }
        }

        // Verify blocking and privacy in direct conversations
        if (! $conversation->isGroup()) {
            $otherParticipant = ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', '!=', $caller->id)
                ->first();

            if (! $otherParticipant && $receiverId) {
                $otherParticipant = ConversationParticipant::firstOrCreate([
                    'conversation_id' => $conversation->id,
                    'user_id' => $receiverId,
                ], [
                    'role' => 'member',
                    'request_status' => 'accepted',
                ]);
            }

            if (! $otherParticipant) {
                throw new InvalidArgumentException('Receiver could not be found for this conversation.');
            }

            $receiver = User::find($otherParticipant->user_id);
            if (! $receiver || $receiver->trashed()) {
                throw new AuthorizationException('User is currently unavailable.');
            }

            if (in_array($receiver->status, ['suspended', 'banned', 'inactive'], true)) {
                throw new AuthorizationException('User account is currently unavailable.');
            }

            // Bi-directional block check: caller blocked receiver OR receiver blocked caller
            if ($this->profilePrivacyService->isBlocked($receiver, $caller)) {
                Log::info('Call initiation blocked due to block relationship', [
                    'caller_id' => $caller->id,
                    'receiver_id' => $receiver->id,
                    'conversation_id' => $conversation->id,
                ]);
                throw new AuthorizationException('Cannot initiate call due to privacy/block settings.');
            }

            // Privacy settings evaluation
            $privacy = $receiver->privacySettings;
            if ($privacy) {
                $whoCanMessage = strtolower((string) ($privacy->who_can_message_me ?? 'everyone'));
                if ($whoCanMessage === PrivacySetting::LEVEL_ONLY_ME) {
                    throw new AuthorizationException('Cannot initiate call due to privacy/block settings.');
                }

                if ($whoCanMessage === PrivacySetting::LEVEL_FRIENDS) {
                    if (! $this->profilePrivacyService->isFriend($receiver, $caller)) {
                        throw new AuthorizationException('Cannot initiate call due to privacy/block settings.');
                    }
                }
            }
        }

        // Concurrency / Duplicate check: re-use active or ringing call in this conversation
        $existingCall = Call::where('conversation_id', $conversation->id)
            ->whereIn('status', [Call::STATUS_RINGING, Call::STATUS_INITIATING, Call::STATUS_ACTIVE])
            ->latest('id')
            ->first();

        if ($existingCall) {
            // Clean up stale ringing calls older than 45 seconds without answer
            if ($existingCall->status === Call::STATUS_RINGING && $existingCall->started_at && $existingCall->started_at->diffInSeconds(now()) > 45) {
                $existingCall->update([
                    'status' => Call::STATUS_MISSED,
                    'ended_at' => now(),
                ]);
            } else {
                // Return existing call session idempotently if same caller or already active
                if ($existingCall->caller_id === $caller->id || $existingCall->status === Call::STATUS_ACTIVE) {
                    $existingCall->load(['caller.profile', 'participants.user.profile']);

                    // Re-broadcast call.incoming to all callees so their devices ring reliably
                    $payload = [
                        'call_id' => $existingCall->id,
                        'uuid' => $existingCall->uuid,
                        'conversation_id' => $conversation->id,
                        'call_type' => $existingCall->call_type,
                        'room_id' => $existingCall->room_id,
                        'caller' => [
                            'id' => $caller->id,
                            'name' => $caller->name,
                            'username' => $caller->username,
                            'avatar_url' => $caller->profile?->avatar_url,
                        ],
                        'ice_servers' => $this->getIceServers($caller),
                    ];
                    $this->realtimeService->broadcastToConversation($conversation->id, 'call.incoming', $payload);
                    $this->syncEventService->recordEvent('call.incoming', $payload, null, $conversation->id);
                    foreach ($existingCall->participants as $part) {
                        if ($part->user_id !== $caller->id) {
                            $this->realtimeService->broadcast("private-user.{$part->user_id}", 'call.incoming', $payload);
                            $this->syncEventService->recordEvent('call.incoming', $payload, (int) $part->user_id, $conversation->id);
                        }
                    }

                    return $existingCall;
                }
            }
        }

        // Adjust group call types automatically if conversation is a group or multiple callees
        $isMultiParty = $conversation->isGroup() || count($receiverIds) > 1;
        if ($isMultiParty) {
            $callType = ($callType === Call::TYPE_VIDEO || $callType === Call::TYPE_GROUP_VIDEO)
                ? Call::TYPE_GROUP_VIDEO
                : Call::TYPE_GROUP_AUDIO;
        } else {
            $callType = ($callType === Call::TYPE_VIDEO || $callType === Call::TYPE_GROUP_VIDEO)
                ? Call::TYPE_VIDEO
                : Call::TYPE_AUDIO;
        }

        return DB::transaction(function () use ($caller, $conversation, $callType, $receiverId, $receiverIds) {
            $call = Call::create([
                'conversation_id' => $conversation->id,
                'caller_id' => $caller->id,
                'call_type' => $callType,
                'status' => Call::STATUS_RINGING,
                'started_at' => now(),
                'metadata' => [
                    'ice_servers' => $this->getIceServers($caller),
                ],
            ]);

            // Add caller as participant
            CallParticipant::firstOrCreate([
                'call_id' => $call->id,
                'user_id' => $caller->id,
            ], [
                'role' => CallParticipant::ROLE_CALLER,
                'status' => CallParticipant::STATUS_ACCEPTED,
                'joined_at' => now(),
            ]);

            // Add all other conversation participants as callees
            $otherUserIds = $conversation->participants()
                ->where('user_id', '!=', $caller->id)
                ->pluck('user_id');

            if ($receiverId && ! $otherUserIds->contains($receiverId)) {
                $otherUserIds->push($receiverId);
            }

            foreach ($receiverIds as $rId) {
                $rId = (int) $rId;
                if ($rId > 0 && $rId !== $caller->id && ! $otherUserIds->contains($rId)) {
                    $otherUserIds->push($rId);
                }
            }

            foreach ($otherUserIds as $otherId) {
                CallParticipant::firstOrCreate([
                    'call_id' => $call->id,
                    'user_id' => $otherId,
                ], [
                    'role' => ($conversation->isGroup() || count($otherUserIds) > 1) ? CallParticipant::ROLE_PARTICIPANT : CallParticipant::ROLE_CALLEE,
                    'status' => CallParticipant::STATUS_RINGING,
                ]);
            }

            $call->load(['caller.profile', 'participants.user.profile']);

            Log::info('WebRTC call session created', [
                'call_id' => $call->id,
                'caller_id' => $caller->id,
                'conversation_id' => $conversation->id,
                'call_type' => $callType,
            ]);

            // Broadcast call.incoming to conversation
            $payload = [
                'call_id' => $call->id,
                'uuid' => $call->uuid,
                'conversation_id' => $conversation->id,
                'call_type' => $call->call_type,
                'room_id' => $call->room_id,
                'caller' => [
                    'id' => $caller->id,
                    'name' => $caller->name,
                    'username' => $caller->username,
                    'avatar_url' => $caller->profile?->avatar_url,
                ],
                'ice_servers' => $this->getIceServers($caller),
            ];

            $this->realtimeService->broadcastToConversation($conversation->id, 'call.incoming', $payload);

            // Record sync event for conversation
            $this->syncEventService->recordEvent('call.incoming', $payload, null, $conversation->id);

            // Directly broadcast and record sync event for every callee's personal channel
            foreach ($otherUserIds as $otherId) {
                $this->realtimeService->broadcast("private-user.{$otherId}", 'call.incoming', $payload);
                $this->syncEventService->recordEvent('call.incoming', $payload, (int) $otherId, $conversation->id);
            }

            return $call;
        });
    }

    /**
     * Respond to an incoming call: accept, reject, or busy.
     */
    public function respondToCall(User $user, int $callId, string $action): Call
    {
        if (! in_array($action, ['accept', 'reject', 'busy'], true)) {
            throw new InvalidArgumentException("Invalid call response action: {$action}");
        }

        return DB::transaction(function () use ($user, $callId, $action) {
            $call = Call::where('id', $callId)->lockForUpdate()->firstOrFail();

            if (in_array($call->status, [Call::STATUS_ENDED, Call::STATUS_REJECTED, Call::STATUS_BUSY, Call::STATUS_MISSED, Call::STATUS_FAILED], true)) {
                throw new InvalidArgumentException("Call is no longer active (current status: {$call->status}).");
            }

            $participant = CallParticipant::where('call_id', $call->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $participant) {
                throw new AuthorizationException('You are not a participant in this call.');
            }

            // If already responded to with the same action, return idempotently
            if ($action === 'accept' && $participant->status === CallParticipant::STATUS_ACCEPTED) {
                return $call->fresh(['participants.user.profile', 'caller.profile']);
            }
            if (($action === 'reject' || $action === 'busy') && in_array($participant->status, [CallParticipant::STATUS_REJECTED, CallParticipant::STATUS_BUSY], true)) {
                return $call->fresh(['participants.user.profile', 'caller.profile']);
            }

            if ($action === 'accept') {
                $participant->update([
                    'status' => CallParticipant::STATUS_ACCEPTED,
                    'joined_at' => now(),
                ]);

                if ($call->status === Call::STATUS_RINGING || $call->status === Call::STATUS_INITIATING) {
                    $call->update([
                        'status' => Call::STATUS_ACTIVE,
                        'started_at' => now(),
                    ]);
                }

                $payload = [
                    'call_id' => $call->id,
                    'user_id' => $user->id,
                    'status' => 'accepted',
                    'call_status' => $call->status,
                ];

                $this->realtimeService->broadcastToConversation($call->conversation_id, 'call.accepted', $payload);
                $this->syncEventService->recordEvent('call.accepted', $payload, null, $call->conversation_id);
                $this->syncEventService->recordEvent('call.history_updated', $payload, null, $call->conversation_id);
                foreach ($call->participants as $cp) {
                    $this->realtimeService->broadcast("private-user.{$cp->user_id}", 'call.accepted', $payload);
                }
            } else {
                // reject or busy
                $status = $action === 'busy' ? CallParticipant::STATUS_BUSY : CallParticipant::STATUS_REJECTED;
                $participant->update([
                    'status' => $status,
                    'left_at' => now(),
                ]);

                // If 1-to-1 call, reject ends the call
                if (! $call->isGroup()) {
                    $callStatus = $action === 'busy' ? Call::STATUS_BUSY : Call::STATUS_REJECTED;
                    $call->update([
                        'status' => $callStatus,
                        'ended_at' => now(),
                    ]);
                    $this->recordCallMessageInConversation($call, $callStatus, 0);
                } else {
                    // For group call, check if all callees rejected
                    $hasOtherAccepted = $call->participants()
                        ->where('user_id', '!=', $call->caller_id)
                        ->where('status', CallParticipant::STATUS_ACCEPTED)
                        ->exists();

                    $hasOtherRinging = $call->participants()
                        ->where('user_id', '!=', $call->caller_id)
                        ->where('status', CallParticipant::STATUS_RINGING)
                        ->exists();

                    if (! $hasOtherAccepted && ! $hasOtherRinging) {
                        $call->update([
                            'status' => Call::STATUS_REJECTED,
                            'ended_at' => now(),
                        ]);
                        $this->recordCallMessageInConversation($call, Call::STATUS_REJECTED, 0);
                    }
                }

                $payload = [
                    'call_id' => $call->id,
                    'user_id' => $user->id,
                    'status' => $status,
                    'call_status' => $call->status,
                ];

                $this->realtimeService->broadcastToConversation($call->conversation_id, 'call.rejected', $payload);
                $this->syncEventService->recordEvent('call.rejected', $payload, null, $call->conversation_id);
                $this->syncEventService->recordEvent('call.history_updated', $payload, null, $call->conversation_id);
                foreach ($call->participants as $cp) {
                    $this->realtimeService->broadcast("private-user.{$cp->user_id}", 'call.rejected', $payload);
                }
            }

            return $call->fresh(['participants.user.profile', 'caller.profile']);
        });
    }

    /**
     * Leave or end a call with single authoritative, idempotent finalization.
     */
    public function leaveCall(User $user, int $callId): Call
    {
        return DB::transaction(function () use ($user, $callId) {
            $call = Call::where('id', $callId)->lockForUpdate()->firstOrFail();

            $participant = CallParticipant::where('call_id', $call->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $participant) {
                throw new AuthorizationException('You are not a participant in this call.');
            }

            $alreadyFinalized = in_array($call->status, [Call::STATUS_ENDED, Call::STATUS_MISSED, Call::STATUS_REJECTED, Call::STATUS_BUSY, Call::STATUS_FAILED], true);

            // If participant already left and session is finalized, return idempotently
            if ($participant->status === CallParticipant::STATUS_LEFT && $alreadyFinalized) {
                return $call->fresh(['participants.user.profile', 'caller.profile']);
            }

            $now = now();
            $durationSeconds = $participant->joined_at ? (int) abs($now->diffInSeconds($participant->joined_at, false)) : 0;

            $participant->update([
                'status' => CallParticipant::STATUS_LEFT,
                'left_at' => $now,
                'duration_seconds' => $durationSeconds,
            ]);

            // If session was ALREADY finalized by the other participant or server, do NOT re-finalize or recreate message
            if ($alreadyFinalized) {
                return $call->fresh(['participants.user.profile', 'caller.profile']);
            }

            // Check if call should be ended
            $activeCount = $call->participants()
                ->where('status', CallParticipant::STATUS_ACCEPTED)
                ->whereNull('left_at')
                ->count();

            $callEnded = false;
            // In 1-on-1 call, if any participant leaves or if caller leaves, call ends
            if (! $call->isGroup() || $activeCount <= 1) {
                $wasActive = ($call->status === Call::STATUS_ACTIVE);
                $callDuration = ($wasActive && $call->started_at) ? max(1, (int) abs($now->diffInSeconds($call->started_at, false))) : 0;
                $finalStatus = Call::STATUS_ENDED;

                $call->participants()
                    ->where('status', CallParticipant::STATUS_RINGING)
                    ->update([
                        'status' => CallParticipant::STATUS_MISSED,
                        'left_at' => $now,
                    ]);

                $call->update([
                    'status' => $finalStatus,
                    'ended_at' => $now,
                    'duration_seconds' => $callDuration,
                ]);
                $callEnded = true;

                $this->recordCallMessageInConversation($call, $finalStatus, $callDuration);
            }

            $payload = [
                'call_id' => $call->id,
                'user_id' => $user->id,
                'call_ended' => $callEnded,
                'duration_seconds' => $durationSeconds,
            ];

            $this->realtimeService->broadcastToConversation($call->conversation_id, $callEnded ? 'call.ended' : 'call.left', $payload);
            $this->syncEventService->recordEvent($callEnded ? 'call.ended' : 'call.left', $payload, null, $call->conversation_id);
            if ($callEnded) {
                $this->syncEventService->recordEvent('call.history_updated', $payload, null, $call->conversation_id);
            }

            foreach ($call->participants as $cp) {
                $this->realtimeService->broadcast("private-user.{$cp->user_id}", $callEnded ? 'call.ended' : 'call.left', $payload);
            }

            return $call->fresh(['participants.user.profile', 'caller.profile']);
        });
    }

    /**
     * Persist a call record into the conversation thread idempotently.
     */
    protected function recordCallMessageInConversation(Call $call, string $finalStatus, int $durationSeconds = 0): ?Message
    {
        $existing = Message::where('conversation_id', $call->conversation_id)
            ->where('type', 'call')
            ->where(function ($q) use ($call) {
                $q->where('metadata->call_id', $call->id)
                    ->orWhere('metadata->call_id', (string) $call->id);
            })
            ->lockForUpdate()
            ->first();

        $callTypeLabel = in_array($call->call_type, [Call::TYPE_VIDEO, Call::TYPE_GROUP_VIDEO], true) ? 'ভিডিও কল' : 'অডিও কল';
        $icon = in_array($call->call_type, [Call::TYPE_VIDEO, Call::TYPE_GROUP_VIDEO], true) ? '📹' : '📞';

        $durStr = $durationSeconds > 0 ? ' ('.gmdate('i:s', $durationSeconds).')' : '';
        $bodyText = match ($finalStatus) {
            Call::STATUS_MISSED => '📞 মিসড '.$callTypeLabel,
            Call::STATUS_REJECTED => '📞 প্রত্যাখ্যাত '.$callTypeLabel,
            Call::STATUS_BUSY => '📞 ব্যস্ত '.$callTypeLabel,
            default => ($durationSeconds === 0) ? '📞 মিসড '.$callTypeLabel : "{$icon} {$callTypeLabel} সম্পন্ন{$durStr}",
        };

        if ($existing) {
            // Preserve valid positive duration if existing had one and incoming duplicate is 0
            $existingDuration = (int) ($existing->metadata['duration'] ?? 0);
            if ($durationSeconds === 0 && $existingDuration > 0) {
                $durationSeconds = $existingDuration;
                $durStr = ' ('.gmdate('i:s', $durationSeconds).')';
                $bodyText = "{$icon} {$callTypeLabel} সম্পন্ন{$durStr}";
            }

            $existing->update([
                'body' => $bodyText,
                'metadata' => array_merge($existing->metadata ?? [], [
                    'call_id' => $call->id,
                    'call_type' => $call->call_type,
                    'status' => $finalStatus,
                    'duration' => $durationSeconds,
                ]),
            ]);

            return $existing;
        }

        $now = now();
        $message = Message::create([
            'conversation_id' => $call->conversation_id,
            'sender_id' => $call->caller_id,
            'type' => 'call',
            'body' => $bodyText,
            'delivery_status' => Message::STATUS_SENT,
            'sent_at' => $now,
            'metadata' => [
                'call_id' => $call->id,
                'call_type' => $call->call_type,
                'status' => $finalStatus,
                'duration' => $durationSeconds,
            ],
        ]);

        $call->conversation?->update([
            'last_message_id' => $message->id,
            'last_message_at' => $now,
        ]);

        $msgPayload = $message->toResponseArray();
        $this->realtimeService->broadcastToConversation($call->conversation_id, 'message.created', $msgPayload);
        $this->syncEventService->recordEvent('message.created', $msgPayload, null, $call->conversation_id);

        return $message;
    }

    /**
     * Update participant's audio, video or screen share state.
     */
    public function updateParticipantState(User $user, int $callId, array $state): CallParticipant
    {
        $call = Call::findOrFail($callId);

        $participant = CallParticipant::where('call_id', $call->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $updateData = [];
        if (array_key_exists('is_muted', $state)) {
            $updateData['is_muted'] = (bool) $state['is_muted'];
        }
        if (array_key_exists('is_camera_off', $state)) {
            $updateData['is_camera_off'] = (bool) $state['is_camera_off'];
        }
        if (array_key_exists('is_screen_sharing', $state)) {
            $updateData['is_screen_sharing'] = (bool) $state['is_screen_sharing'];
        }

        if (! empty($updateData)) {
            $participant->update($updateData);

            $payload = array_merge([
                'call_id' => $call->id,
                'user_id' => $user->id,
            ], $updateData);

            $this->realtimeService->broadcastToConversation($call->conversation_id, 'call.participant_state', $payload);
        }

        return $participant;
    }

    /**
     * Send WebRTC signaling payload (offer, answer, candidate, screen_share).
     */
    public function sendSignal(User $sender, int $callId, string $signalType, array $payload, ?int $targetUserId = null): array
    {
        $call = Call::findOrFail($callId);

        if (in_array($call->status, [Call::STATUS_ENDED, Call::STATUS_REJECTED, Call::STATUS_BUSY, Call::STATUS_MISSED, Call::STATUS_FAILED], true)) {
            throw new InvalidArgumentException('Cannot send signals for an inactive call.');
        }

        // Ensure sender is a participant
        if (! $call->participants()->where('user_id', $sender->id)->exists()) {
            throw new AuthorizationException('You are not authorized in this call.');
        }

        $signalData = [
            'call_id' => $call->id,
            'room_id' => $call->room_id,
            'signal_type' => $signalType,
            'sender_id' => $sender->id,
            'sender_name' => $sender->name,
            'target_user_id' => $targetUserId,
            'payload' => $payload,
        ];

        // Broadcast signal to conversation
        $this->realtimeService->broadcastToConversation($call->conversation_id, 'call.signal', $signalData);

        // Also broadcast directly to target user channel or other participants
        if ($targetUserId) {
            $this->realtimeService->broadcast("private-user.{$targetUserId}", 'call.signal', $signalData);
        } else {
            foreach ($call->participants as $cp) {
                if ($cp->user_id !== $sender->id) {
                    $this->realtimeService->broadcast("private-user.{$cp->user_id}", 'call.signal', $signalData);
                }
            }
        }

        // Persist for the polling sync fallback (works even without a WebSocket server)
        $this->syncEventService->recordEvent('call.signal', $signalData, $targetUserId, $call->conversation_id);

        return $signalData;
    }

    /**
     * Get user's call history with pagination.
     */
    public function getCallHistory(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return Call::where(function ($q) use ($user) {
            $q->where('caller_id', $user->id)
                ->orWhereHas('participants', fn ($pq) => $pq->where('user_id', $user->id));
        })
            ->with(['caller.profile', 'participants.user.profile', 'conversation'])
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get single call details.
     */
    public function getCall(int $callId, User $user): Call
    {
        $call = Call::with(['caller.profile', 'participants.user.profile', 'conversation'])->findOrFail($callId);

        if (! $call->conversation->hasParticipant($user->id)) {
            throw new AuthorizationException('You do not have access to this call.');
        }

        return $call;
    }

    /**
     * Invite one or more friends into an ongoing call in real-time.
     */
    public function inviteToCall(User $user, int $callId, int|array $friendIds): array
    {
        return DB::transaction(function () use ($user, $callId, $friendIds) {
            $call = Call::where('id', $callId)->lockForUpdate()->firstOrFail();

            if (in_array($call->status, [Call::STATUS_ENDED, Call::STATUS_REJECTED, Call::STATUS_MISSED, Call::STATUS_BUSY, Call::STATUS_FAILED], true)) {
                throw new InvalidArgumentException('Call is no longer active.');
            }

            // Ensure inviter is an active participant in this call
            $isParticipant = $call->participants()
                ->where('user_id', $user->id)
                ->whereIn('status', [CallParticipant::STATUS_ACCEPTED, CallParticipant::STATUS_RINGING])
                ->exists();

            if (! $isParticipant) {
                throw new AuthorizationException('You are not an active participant in this call.');
            }

            $conversation = $call->conversation;
            if (! $conversation) {
                throw new InvalidArgumentException('Conversation not found.');
            }

            $ids = is_array($friendIds) ? $friendIds : [$friendIds];
            $ids = array_unique(array_filter(array_map('intval', $ids)));

            $invited = [];

            foreach ($ids as $friendId) {
                if ($friendId === (int) $user->id) {
                    continue;
                }

                $friend = User::with(['profile', 'privacySettings'])->find($friendId);
                if (! $friend || $friend->trashed()) {
                    continue;
                }

                if (in_array($friend->status, ['suspended', 'banned', 'inactive'], true)) {
                    continue;
                }

                // Privacy/blocking checks
                if ($this->profilePrivacyService->isBlocked($friend, $user)) {
                    continue;
                }

                // Ensure friend is in conversation participants so they can access the call route
                ConversationParticipant::firstOrCreate([
                    'conversation_id' => $conversation->id,
                    'user_id' => $friend->id,
                ], [
                    'role' => 'member',
                    'request_status' => 'accepted',
                ]);

                // If conversation was direct, upgrade to group call
                if ($conversation->isDirect()) {
                    $conversation->update([
                        'type' => Conversation::TYPE_GROUP,
                        'title' => $conversation->title ?: 'গ্রুপ কল',
                    ]);
                }

                // Upgrade call type to group if not already
                $newCallType = $call->call_type;
                if ($call->call_type === Call::TYPE_AUDIO) {
                    $newCallType = Call::TYPE_GROUP_AUDIO;
                } elseif ($call->call_type === Call::TYPE_VIDEO) {
                    $newCallType = Call::TYPE_GROUP_VIDEO;
                }
                if ($newCallType !== $call->call_type) {
                    $call->update(['call_type' => $newCallType]);
                }

                // Add or update call participant
                $participant = CallParticipant::firstOrCreate([
                    'call_id' => $call->id,
                    'user_id' => $friend->id,
                ], [
                    'role' => CallParticipant::ROLE_PARTICIPANT,
                    'status' => CallParticipant::STATUS_RINGING,
                ]);

                if ($participant->status !== CallParticipant::STATUS_RINGING && $participant->status !== CallParticipant::STATUS_ACCEPTED) {
                    $participant->update([
                        'status' => CallParticipant::STATUS_RINGING,
                        'left_at' => null,
                    ]);
                }

                // Broadcast call.incoming to the friend's private channel
                $incomingPayload = [
                    'call_id' => $call->id,
                    'uuid' => $call->uuid,
                    'conversation_id' => $conversation->id,
                    'call_type' => $call->call_type,
                    'room_id' => $call->room_id,
                    'caller' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'avatar_url' => $user->profile?->avatar_url,
                    ],
                    'ice_servers' => $this->getIceServers($user),
                    'is_group' => true,
                ];

                $this->realtimeService->broadcast("private-user.{$friend->id}", 'call.incoming', $incomingPayload);
                $this->syncEventService->recordEvent('call.incoming', $incomingPayload, (int) $friend->id, $conversation->id);

                // Broadcast call.participant_invited to the conversation and active call participants
                $invitePayload = [
                    'call_id' => $call->id,
                    'conversation_id' => $conversation->id,
                    'invited_by' => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ],
                    'participant' => [
                        'id' => $friend->id,
                        'name' => $friend->name,
                        'username' => $friend->username,
                        'avatar_url' => $friend->profile?->avatar_url,
                        'status' => 'ringing',
                    ],
                ];

                $this->realtimeService->broadcastToConversation($conversation->id, 'call.participant_invited', $invitePayload);
                $this->syncEventService->recordEvent('call.participant_invited', $invitePayload, null, $conversation->id);

                $invited[] = [
                    'id' => $friend->id,
                    'name' => $friend->name,
                    'username' => $friend->username,
                    'avatar_url' => $friend->profile?->avatar_url,
                    'status' => 'ringing',
                ];
            }

            return [
                'call_id' => $call->id,
                'conversation_id' => $conversation->id,
                'call_type' => $call->call_type,
                'invited' => $invited,
            ];
        });
    }
}
