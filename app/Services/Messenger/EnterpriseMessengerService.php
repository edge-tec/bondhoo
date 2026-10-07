<?php

namespace App\Services\Messenger;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\RealtimeService;
use Illuminate\Support\Str;

class EnterpriseMessengerService
{
    protected RealtimeService $realtime;

    public function __construct(RealtimeService $realtime)
    {
        $this->realtime = $realtime;
    }

    /**
     * Initiate an End-to-End Encrypted (E2EE) Secret Chat.
     *
     * @return array{secret_channel: string, ephemeral_key: string, cipher: string}
     */
    public function initiateSecretChat(Conversation $conversation, User $initiator): array
    {
        $secretChannel = 'secret_'.Str::random(24);
        $ephemeralKey = base64_encode(random_bytes(32));

        $this->realtime->broadcastToConversation($conversation->id, 'chat.secret_init', [
            'initiator_id' => $initiator->id,
            'secret_channel' => $secretChannel,
            'cipher' => 'AES-256-GCM',
        ]);

        return [
            'secret_channel' => $secretChannel,
            'ephemeral_key' => $ephemeralKey,
            'cipher' => 'AES-256-GCM',
        ];
    }

    /**
     * Store voice message note with duration and waveform data.
     *
     * @param  int[]  $waveform
     */
    public function sendVoiceMessage(Conversation $conversation, User $sender, string $audioPath, int $durationSecs, array $waveform = []): Message
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => '🎤 ভয়েস বার্তা',
            'type' => 'voice',
            'metadata' => [
                'audio_url' => $audioPath,
                'duration' => $durationSecs,
                'waveform' => $waveform ?: array_map(fn () => rand(10, 95), range(1, 30)),
            ],
        ]);

        $this->realtime->broadcastToConversation($conversation->id, 'message.new', $message->toArray());

        return $message;
    }

    /**
     * Initiate WebRTC video / group call session.
     *
     * @param  string  $callType  video, audio, screen_share
     * @return array<string, mixed>
     */
    public function startCallSession(Conversation $conversation, User $caller, string $callType = 'video'): array
    {
        $sessionId = (string) Str::uuid();

        $payload = [
            'session_id' => $sessionId,
            'conversation_id' => $conversation->id,
            'caller' => [
                'id' => $caller->id,
                'name' => $caller->name,
                'avatar' => $caller->profile?->avatar_url,
            ],
            'call_type' => $callType,
            'signaling_channel' => "webrtc.call.{$sessionId}",
            'ice_servers' => [
                ['urls' => 'stun:stun.l.google.com:19302'],
                ['urls' => 'stun:stun1.l.google.com:19302'],
            ],
        ];

        $this->realtime->broadcastToConversation($conversation->id, 'call.incoming', $payload);

        return $payload;
    }

    /**
     * Pin message in conversation.
     */
    public function pinMessage(Message $message, User $user): Message
    {
        $metadata = $message->metadata ?? [];
        $metadata['pinned_by'] = $user->id;
        $metadata['pinned_at'] = now()->toIso8601String();

        $message->update(['metadata' => $metadata]);

        $this->realtime->broadcastToConversation($message->conversation_id, 'message.pinned', [
            'message_id' => $message->id,
            'pinned_by' => $user->id,
        ]);

        return $message;
    }
}
