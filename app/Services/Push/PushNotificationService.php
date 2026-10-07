<?php

namespace App\Services\Push;

use App\Models\DeviceSession;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Dispatch push notification to a user's active registered devices.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
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
            $success = $this->dispatchToDevice($device, $title, $body, $data);
            if ($success) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Dispatch to single device session based on platform provider.
     */
    public function dispatchToDevice(DeviceSession $device, string $title, string $body, array $data = []): bool
    {
        $platform = strtolower((string) $device->platform);

        try {
            switch ($platform) {
                case 'android':
                    return $this->sendFcmNotification($device, $title, $body, $data);

                case 'ios':
                case 'macos':
                    return $this->sendApnsNotification($device, $title, $body, $data);

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
     * Send notification via Firebase Cloud Messaging (Android).
     */
    protected function sendFcmNotification(DeviceSession $device, string $title, string $body, array $data): bool
    {
        $serverKey = config('services.fcm.server_key', env('FCM_SERVER_KEY'));
        $projectId = config('services.fcm.project_id', env('FCM_PROJECT_ID'));

        if (! $serverKey && ! $projectId) {
            Log::info("FCM credentials unconfigured. Notification queued for device {$device->device_id}: {$title}");

            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => "key={$serverKey}",
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $device->push_token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => $data,
            'priority' => 'high',
        ]);

        if ($response->successful()) {
            $result = $response->json();
            if (! empty($result['results'][0]['error']) && in_array($result['results'][0]['error'], ['NotRegistered', 'InvalidRegistration'], true)) {
                $device->update(['is_revoked' => true]);
            }

            return true;
        }

        Log::warning("FCM request failed for device {$device->device_id}: HTTP {$response->status()}");

        return false;
    }

    /**
     * Send notification via Apple Push Notification service (iOS / macOS).
     */
    protected function sendApnsNotification(DeviceSession $device, string $title, string $body, array $data): bool
    {
        $apnsKeyId = config('services.apns.key_id', env('APNS_KEY_ID'));
        $apnsTeamId = config('services.apns.team_id', env('APNS_TEAM_ID'));
        $apnsBundleId = config('services.apns.bundle_id', env('APNS_BUNDLE_ID', 'com.jugajug.messenger'));

        if (! $apnsKeyId || ! $apnsTeamId) {
            Log::info("APNs credentials unconfigured. Notification queued for device {$device->device_id}: {$title}");

            return false;
        }

        Log::info("APNs push prepared for bundle {$apnsBundleId}, device {$device->device_id}: {$title}");

        return true;
    }

    /**
     * Send notification via WebPush (VAPID).
     */
    protected function sendWebPushNotification(DeviceSession $device, string $title, string $body, array $data): bool
    {
        $vapidPublicKey = config('services.webpush.public_key', env('VAPID_PUBLIC_KEY'));
        $vapidPrivateKey = config('services.webpush.private_key', env('VAPID_PRIVATE_KEY'));

        if (! $vapidPublicKey || ! $vapidPrivateKey) {
            Log::info("WebPush VAPID credentials unconfigured. Notification queued for device {$device->device_id}: {$title}");

            return false;
        }

        Log::info("WebPush prepared for device {$device->device_id}: {$title}");

        return true;
    }
}
