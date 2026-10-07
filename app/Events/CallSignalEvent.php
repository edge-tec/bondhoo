<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * WebRTC অডিও এবং ভিডিও কল সিগন্যালিং ইভেন্ট:
 * পিয়ার-টু-পিয়ার অডিও/ভিডিও সংযোগ স্থাপনে অফার, অ্যান্সার এবং ICE ক্যান্ডিডেট ব্রডকাস্ট করে।
 */
class CallSignalEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $senderId,
        public string $senderName,
        public string $signalType, // offer, answer, candidate, reject, end
        public string $callType,   // audio, video
        public array $payload = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'call.signal';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'sender_id' => $this->senderId,
            'sender_name' => $this->senderName,
            'signal_type' => $this->signalType,
            'call_type' => $this->callType,
            'payload' => $this->payload,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
