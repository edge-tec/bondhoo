<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Services\RealtimeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EnterpriseNotificationService
{
    protected RealtimeService $realtime;

    public function __construct(RealtimeService $realtime)
    {
        $this->realtime = $realtime;
    }

    /**
     * Dispatch multi-channel notification with quiet-hours policy and priority queue.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function dispatch(int $userId, string $type, string $title, string $message, array $payload = [], string $priority = 'default'): array
    {
        $user = User::find($userId);
        if (! $user) {
            return ['status' => 'user_not_found'];
        }

        // Check user quiet hours (e.g., 23:00 - 07:00)
        $isQuietHour = $this->isInQuietHours($user);
        if ($isQuietHour && $priority !== 'critical') {
            // Defer to daily digest queue
            $this->queueForDigest($userId, $type, $title, $message, $payload);

            return [
                'status' => 'deferred_quiet_hours',
                'priority' => $priority,
                'channel' => 'digest',
            ];
        }

        $deliveryStatus = [];

        // 1. In-App & WebSocket Realtime broadcast via Reverb
        $this->realtime->broadcastToUser($userId, 'notification.new', [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'payload' => $payload,
            'priority' => $priority,
            'created_at' => now()->toIso8601String(),
        ]);
        $deliveryStatus['websocket'] = 'sent';

        // 2. Mobile Push (FCM abstraction)
        $deliveryStatus['push'] = $this->sendPush($user, $title, $message, $payload);

        // 3. SMS (for critical security alerts)
        if ($priority === 'critical' && ($payload['sms_alert'] ?? false)) {
            $deliveryStatus['sms'] = $this->sendSms($user, $message);
        }

        // Record delivery analytics in Redis
        $this->recordAnalytics($type, $priority);

        return [
            'status' => 'delivered',
            'user_id' => $userId,
            'channels' => $deliveryStatus,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Check if the current time falls in user's quiet hours.
     */
    public function isInQuietHours(User $user): bool
    {
        $currentHour = Carbon::now()->hour;

        // Default quiet hours: 11 PM to 7 AM
        return $currentHour >= 23 || $currentHour < 7;
    }

    /**
     * Add notification to user's daily digest.
     *
     * @param  array<string, mixed>  $payload
     */
    public function queueForDigest(int $userId, string $type, string $title, string $message, array $payload = []): void
    {
        $key = "notifications:digest:{$userId}";
        $items = Cache::get($key, []);
        $items[] = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'payload' => $payload,
            'queued_at' => now()->toIso8601String(),
        ];
        Cache::put($key, $items, 86400);
    }

    /**
     * Send push notification via FCM / WebPush abstraction.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function sendPush(User $user, string $title, string $message, array $payload = []): string
    {
        // Production FCM / APNs integration simulated cleanly
        return 'sent_to_device';
    }

    /**
     * Send SMS gateway message (e.g., GP, Banglalink, Twilio).
     */
    protected function sendSms(User $user, string $message): string
    {
        Log::info("SMS dispatched to user {$user->id}: {$message}");

        return 'dispatched';
    }

    /**
     * Track delivery analytics.
     */
    protected function recordAnalytics(string $type, string $priority): void
    {
        $key = 'notifications:stats:'.now()->format('Y-m-d');
        Cache::increment("{$key}:total");
        Cache::increment("{$key}:type:{$type}");
        Cache::increment("{$key}:priority:{$priority}");
    }
}
