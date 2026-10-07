<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use App\Services\Notification\NotificationDto;
use App\Services\Notification\NotificationTypeRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Enterprise Notification Dispatcher Job:
 * Runs in background queue, performs intelligent grouping/deduplication,
 * persists in-app notification records, updates Redis unread counters,
 * and pushes real-time WebSocket payloads.
 */
class DispatchNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $recipientId,
        public string $type,
        public array $data,
        public array $channels = ['database']
    ) {
        $this->onQueue(QueueServiceInterface::QUEUE_HIGH);
    }

    public function handle(
        CacheServiceInterface $cacheService,
        QueueServiceInterface $queueService,
        RealtimeServiceInterface $realtimeService
    ): void {
        $recipient = User::with('notificationSettings')->find($this->recipientId);
        if (! $recipient) {
            return;
        }

        // 1. In-App Notification Database Persistence & Realtime Broadcast
        if (in_array('database', $this->channels, true)) {
            $category = $this->data['category'] ?? NotificationTypeRegistry::resolveCategory($this->type)->value;
            $groupKey = $this->data['group_key'] ?? null;

            // Intelligent Deduplication / Grouping Logic
            if ($groupKey) {
                $existing = DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $recipient->id)
                    ->where('group_key', $groupKey)
                    ->whereNull('read_at')
                    ->first();

                if ($existing) {
                    $existingData = json_decode($existing->data, true) ?: [];
                    $count = (int) ($existingData['group_count'] ?? 1) + 1;
                    $actors = $existingData['actors'] ?? [];
                    $newActorName = $this->data['actor_name'] ?? $this->data['sender_name'] ?? null;

                    if ($newActorName) {
                        array_unshift($actors, $newActorName);
                        $actors = array_values(array_unique($actors));
                    }

                    $existingData['group_count'] = $count;
                    $existingData['actors'] = array_slice($actors, 0, 5);

                    if ($count > 1) {
                        $otherCount = NotificationDto::toBengaliNumerals($count - 1);
                        $primaryActor = $actors[0] ?? 'একজন ব্যবহারকারী';
                        $existingData['message'] = "{$primaryActor} এবং আরও {$otherCount} জন আপনার কনটেন্টে যুক্ত হয়েছেন।";
                    }

                    DB::table('notifications')
                        ->where('id', $existing->id)
                        ->update([
                            'data' => json_encode($existingData),
                            'updated_at' => now(),
                        ]);

                    $realtimeService->broadcastToUser($recipient->id, 'notification.updated', [
                        'id' => $existing->id,
                        'type' => $this->type,
                        'data' => $existingData,
                        'updated_at' => now()->toIso8601String(),
                    ]);

                    return;
                }
            }

            // Normal Notification Creation
            $notificationId = Str::uuid()->toString();

            DB::table('notifications')->insert([
                'id' => $notificationId,
                'type' => $this->type,
                'notifiable_type' => User::class,
                'notifiable_id' => $recipient->id,
                'data' => json_encode($this->data),
                'read_at' => null,
                'group_key' => $groupKey,
                'category' => $category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Increment Redis unread notification counter
            $cacheService->increment("notifications:user:{$recipient->id}", 1);

            // Realtime WebSocket broadcast to user private channel
            $realtimeService->broadcastToUser($recipient->id, 'notification.new', [
                'id' => $notificationId,
                'type' => $this->type,
                'category' => $category,
                'data' => $this->data,
                'created_at' => now()->toIso8601String(),
            ]);
        }

        // 2. Email channel check
        $settings = $recipient->notificationSettings;
        $wantsEmail = $settings ? $settings->email_notifications : true;

        if (in_array('email', $this->channels, true) && $wantsEmail && ! empty($recipient->email)) {
            $subject = $this->data['title'] ?? 'যুগাজুগে নতুন নোটিফিকেশন';
            $body = $this->data['message'] ?? 'আপনার অ্যাকাউন্টে একটি নতুন আপডেট রয়েছে।';

            $queueService->dispatch(new SendEmailNotificationJob(
                toEmail: $recipient->email,
                subject: $subject,
                messageBody: $body
            ));
        }
    }
}
