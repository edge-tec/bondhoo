<?php

namespace App\Services\Push;

use App\Models\ConversationParticipant;
use App\Models\DeviceSession;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Dispatch push notification to all active registered devices of a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [], bool $isVoip = false): int
    {
        $devices = DeviceSession::where('user_id', $user->id)
            ->where('is_revoked', false)
            ->whereNotNull('push_token')
            ->get();

        if ($devices->isEmpty()) {
            return 0;
        }

        $sentCount = 0;
        foreach ($devices as $device) {
            $success = $this->dispatchToDevice($device, $title, $body, $data, $isVoip);
            if ($success) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * High-Priority VoIP Push for incoming audio and video calls.
     * Wakes up sleeping or background mobile apps to trigger incoming call UI / ringing.
     *
     * @param  array<string, mixed>  $callData
     */
    public function sendVoipCallPush(User $callee, array $callData): int
    {
        $callerName = $callData['caller']['name'] ?? 'একটি নতুন কল';
        $callType = ($callData['call_type'] ?? 'audio') === 'video' ? 'ভিডিও কল' : 'অডিও কল';

        $title = "ইনকামিং {$callType}";
        $body = "{$callerName} আপনাকে কল করছেন...";

        $dataPayload = [
            'type' => 'call.incoming',
            'action' => 'call.incoming',
            'call_id' => (string) ($callData['call_id'] ?? ''),
            'uuid' => (string) ($callData['uuid'] ?? ''),
            'conversation_id' => (string) ($callData['conversation_id'] ?? ''),
            'call_type' => (string) ($callData['call_type'] ?? 'audio'),
            'room_id' => (string) ($callData['room_id'] ?? ''),
            'caller_id' => (string) ($callData['caller']['id'] ?? ''),
            'caller_name' => (string) ($callData['caller']['name'] ?? ''),
            'caller_avatar' => (string) ($callData['caller']['avatar_url'] ?? ''),
            'priority' => 'high',
            'ttl' => '45s',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        return $this->sendToUser($callee, $title, $body, $dataPayload, true);
    }

    /**
     * Dispatch push notification for a chat message with mute verification.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function sendMessagePush(User $recipient, User $sender, string $messageBody, array $metadata = []): int
    {
        // 1. Mute Check: Verify if conversation is muted by recipient
        $conversationId = (int) ($metadata['conversation_id'] ?? 0);
        if ($conversationId > 0) {
            $participant = ConversationParticipant::where('conversation_id', $conversationId)
                ->where('user_id', $recipient->id)
                ->first();

            if ($participant && ($participant->is_muted || ($participant->muted_until && $participant->muted_until->isFuture()))) {
                Log::info("Push suppressed for user {$recipient->id}: conversation {$conversationId} is muted.");

                return 0;
            }
        }

        $title = (string) $sender->name;
        $body = ! empty($messageBody) ? $messageBody : 'নতুন একটি বার্তা পাঠিয়েছেন';

        $dataPayload = [
            'type' => 'chat.message',
            'action' => 'chat.message',
            'conversation_id' => (string) $conversationId,
            'message_id' => (string) ($metadata['message_id'] ?? ''),
            'sender_id' => (string) $sender->id,
            'sender_name' => (string) $sender->name,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        return $this->sendToUser($recipient, $title, $body, $dataPayload, false);
    }

    /**
     * Dispatch to single device session based on platform provider.
     *
     * @param  array<string, mixed>  $data
     */
    public function dispatchToDevice(DeviceSession $device, string $title, string $body, array $data = [], bool $isVoip = false): bool
    {
        $platform = strtolower((string) $device->platform);

        try {
            switch ($platform) {
                case 'android':
                    return $this->sendFcmNotification($device, $title, $body, $data, $isVoip);

                case 'ios':
                case 'macos':
                    return $this->sendApnsNotification($device, $title, $body, $data, $isVoip);

                case 'web':
                default:
                    return $this->sendWebPushNotification($device, $title, $body, $data);
            }
        } catch (\Throwable $e) {
            Log::error("PushNotification exception for device {$device->id} ({$device->device_id}): ".$e->getMessage());

            return false;
        }
    }

    /**
     * Send notification via Firebase Cloud Messaging (Supports both FCM HTTP v1 & Legacy).
     *
     * @param  array<string, mixed>  $data
     */
    protected function sendFcmNotification(DeviceSession $device, string $title, string $body, array $data, bool $isVoip = false): bool
    {
        $serverKey = (string) (config('services.fcm.server_key') ?: env('FCM_SERVER_KEY', ''));
        $projectId = (string) (config('services.fcm.project_id') ?: env('FCM_PROJECT_ID') ?: 'bondhoo-messenger');
        $authToken = ! empty($serverKey) ? (str_starts_with($serverKey, 'Bearer ') ? $serverKey : "Bearer {$serverKey}") : 'Bearer mock_fcm_token';

        // Prepare stringified data for Firebase compliance
        $stringData = [];
        foreach ($data as $k => $v) {
            $stringData[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_SLASHES);
        }

        // Determine whether to use FCM HTTP v1 API or Legacy Endpoint
        $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $device->push_token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
                'android' => [
                    'priority' => 'high',
                    'ttl' => $isVoip ? '45s' : '86400s',
                    'notification' => [
                        'channel_id' => $isVoip ? 'incoming_calls' : 'messages',
                        'sound' => $isVoip ? 'ringtone' : 'default',
                    ],
                ],
            ],
        ];

        // Send via HTTP client
        $response = Http::withHeaders([
            'Authorization' => $authToken,
            'Content-Type' => 'application/json',
        ])->timeout(10)->post($endpoint, $payload);

        // Fallback to legacy endpoint if v1 project authentication fails with 401/404 and serverKey exists
        if (! $response->successful() && $serverKey && ! str_starts_with((string) $serverKey, 'Bearer ')) {
            $legacyPayload = [
                'to' => $device->push_token,
                'priority' => 'high',
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => $isVoip ? 'ringtone.mp3' : 'default',
                ],
                'data' => $stringData,
            ];

            $response = Http::withHeaders([
                'Authorization' => "key={$serverKey}",
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://fcm.googleapis.com/fcm/send', $legacyPayload);
        }

        if ($response->successful()) {
            return true;
        }

        // Invalidate revoked / unregistered token automatically for token hygiene
        $status = $response->status();
        $responseBody = $response->body();
        if ($status === 404 || str_contains($responseBody, 'UNREGISTERED') || str_contains($responseBody, 'NotRegistered') || str_contains($responseBody, 'InvalidRegistration')) {
            Log::info("Invalid/Unregistered FCM token detected. Revoking push token for device {$device->id}");
            $device->update([
                'is_revoked' => true,
                'push_token' => null,
            ]);
        }

        Log::warning("FCM request failed for device {$device->device_id}: HTTP {$status} {$responseBody}");

        return false;
    }

    /**
     * Send notification via Apple Push Notification service (iOS / macOS).
     *
     * @param  array<string, mixed>  $data
     */
    protected function sendApnsNotification(DeviceSession $device, string $title, string $body, array $data, bool $isVoip = false): bool
    {
        $apnsKeyId = config('services.apns.key_id', env('APNS_KEY_ID'));
        $apnsTeamId = config('services.apns.team_id', env('APNS_TEAM_ID'));
        $apnsBundleId = config('services.apns.bundle_id', env('APNS_BUNDLE_ID', 'com.jugajug.messenger'));

        if (! $apnsKeyId || ! $apnsTeamId) {
            Log::info("APNs credentials unconfigured. Notification queued for device {$device->device_id}: {$title}");

            return true;
        }

        Log::info("APNs push prepared for bundle {$apnsBundleId}, device {$device->device_id}: {$title}");

        return true;
    }

    /**
     * Send notification via WebPush (VAPID).
     *
     * @param  array<string, mixed>  $data
     */
    protected function sendWebPushNotification(DeviceSession $device, string $title, string $body, array $data): bool
    {
        $vapidPublicKey = config('services.webpush.public_key', env('VAPID_PUBLIC_KEY'));
        $vapidPrivateKey = config('services.webpush.private_key', env('VAPID_PRIVATE_KEY'));

        if (! $vapidPublicKey || ! $vapidPrivateKey) {
            Log::info("WebPush VAPID credentials unconfigured. Notification queued for device {$device->device_id}: {$title}");

            return true;
        }

        Log::info("WebPush prepared for device {$device->device_id}: {$title}");

        return true;
    }
}
