<?php

namespace Tests\Feature;

use App\Events\NotificationReadAllEvent;
use App\Events\NotificationReadEvent;
use App\Jobs\DispatchNotificationJob;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase9CompleteNotificationsEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name ?? 'Notif',
            'last_name' => $user->last_name ?? 'User',
        ]);

        return $user;
    }

    /**
     * Requirement 9.1: Notification Service sends async job and handles database persistence.
     */
    public function test_notification_service_dispatches_job_and_persists(): void
    {
        $recipient = $this->createUser();
        $notificationService = app(NotificationServiceInterface::class);

        Queue::fake();

        $notificationService->send(
            recipientId: $recipient->id,
            type: 'notification.friend_request',
            data: [
                'title' => 'New Friend Request',
                'message' => 'Alice sent you a friend request.',
                'sender_id' => 123,
            ],
            channels: ['database']
        );

        Queue::assertPushed(DispatchNotificationJob::class, function ($job) use ($recipient) {
            return $job->recipientId === $recipient->id && $job->type === 'notification.friend_request';
        });

        // Now manually execute job handler to verify database storage & cache
        $cacheService = app(CacheServiceInterface::class);
        $queueService = app(QueueServiceInterface::class);
        $realtimeService = app(RealtimeServiceInterface::class);

        $job = new DispatchNotificationJob(
            recipientId: $recipient->id,
            type: 'notification.friend_request',
            data: [
                'title' => 'New Friend Request',
                'message' => 'Alice sent you a friend request.',
                'sender_id' => 123,
            ],
            channels: ['database']
        );
        $job->handle($cacheService, $queueService, $realtimeService);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $recipient->id,
            'type' => 'notification.friend_request',
        ]);

        $this->assertEquals(1, $notificationService->getUnreadCount($recipient));
    }

    /**
     * Requirement 9.2: Retrieve notifications with pagination and category filtering.
     */
    public function test_retrieve_notifications_with_category_and_unread_filter(): void
    {
        $user = $this->createUser();

        // 1. Unread Social
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'notification.comment',
            'category' => 'social',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Comment on your post', 'message' => 'Nice picture!']),
            'read_at' => null,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        // 2. Read Security
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'notification.security.login',
            'category' => 'security',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'New login detected', 'message' => 'Login from Chrome/Mac.']),
            'read_at' => now()->subMinutes(2),
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(2),
        ]);

        // Test Category Filter
        $securityRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications?category=security');
        $securityRes->assertStatus(200);
        $this->assertCount(1, $securityRes->json('data'));
        $this->assertEquals('security', $securityRes->json('data.0.category'));

        // Test Unread Filter
        $unreadRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications?filter=unread');
        $unreadRes->assertStatus(200);
        $this->assertCount(1, $unreadRes->json('data'));
        $this->assertFalse($unreadRes->json('data.0.is_read'));
    }

    /**
     * Requirement 9.3: Mark single notification as read with real-time broadcasting.
     */
    public function test_mark_single_notification_as_read_with_broadcast(): void
    {
        Event::fake([NotificationReadEvent::class]);

        $user = $this->createUser();
        $notifId = Str::uuid()->toString();

        DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'notification.mention',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Mentioned', 'message' => 'Tagged you in a post']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/notifications/{$notifId}/read");

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.read', true)
            ->assertJsonPath('data.unread_count', 0);

        $this->assertNotNull(DB::table('notifications')->where('id', $notifId)->value('read_at'));
        Event::assertDispatched(NotificationReadEvent::class);
    }

    /**
     * Requirement 9.4: Mark all notifications as read and reset counters.
     */
    public function test_mark_all_notifications_as_read(): void
    {
        Event::fake([NotificationReadAllEvent::class]);

        $user = $this->createUser();

        for ($i = 1; $i <= 3; $i++) {
            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => "notification.alert.{$i}",
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode(['title' => "Alert {$i}"]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $res = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/notifications/read-all');

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.updated_count', 3)
            ->assertJsonPath('data.unread_count', 0);

        $unreadInDb = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
        $this->assertEquals(0, $unreadInDb);

        Event::assertDispatched(NotificationReadAllEvent::class);
    }

    /**
     * Requirement 9.5: IDOR Protection on read and delete actions.
     */
    public function test_idor_protection_preventing_tampering_other_user_notifications(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        $notifId = Str::uuid()->toString();

        DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'notification.private',
            'notifiable_type' => User::class,
            'notifiable_id' => $userB->id,
            'data' => json_encode(['title' => 'Secret alert for B']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // User A attempts to mark User B's notification as read -> 404 Not Found
        $readAttempt = $this->actingAs($userA, 'sanctum')
            ->putJson("/api/v1/notifications/{$notifId}/read");
        $readAttempt->assertStatus(404);
        $this->assertNull(DB::table('notifications')->where('id', $notifId)->value('read_at'));

        // User A attempts to delete User B's notification -> 404 Not Found
        $delAttempt = $this->actingAs($userA, 'sanctum')
            ->deleteJson("/api/v1/notifications/{$notifId}");
        $delAttempt->assertStatus(404);
        $this->assertNotNull(DB::table('notifications')->where('id', $notifId)->first());
    }

    /**
     * Requirement 9.6: User can delete single notification and clear read notifications.
     */
    public function test_delete_single_and_clear_read_notifications(): void
    {
        $user = $this->createUser();

        $unreadId = Str::uuid()->toString();
        $readId = Str::uuid()->toString();

        DB::table('notifications')->insert([
            'id' => $unreadId,
            'type' => 'notification.unread',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Keep me']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notifications')->insert([
            'id' => $readId,
            'type' => 'notification.read',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Already read']),
            'read_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Clear read notifications only
        $clearReadRes = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/notifications?only_read=true');
        $clearReadRes->assertStatus(200)
            ->assertJsonPath('data.deleted_count', 1);

        $this->assertNull(DB::table('notifications')->where('id', $readId)->first());
        $this->assertNotNull(DB::table('notifications')->where('id', $unreadId)->first());

        // Delete single unread notification
        $delSingleRes = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/notifications/{$unreadId}");
        $delSingleRes->assertStatus(200);

        $this->assertNull(DB::table('notifications')->where('id', $unreadId)->first());
    }

    /**
     * Requirement 9.7: User can get and update granular notification preferences.
     */
    public function test_get_and_update_notification_preferences(): void
    {
        $user = $this->createUser();

        $getRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications/preferences');

        $getRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'email_notifications',
                    'push_notifications',
                    'friend_request_alerts',
                    'comment_alerts',
                ],
            ]);

        // Update preferences
        $patchRes = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/notifications/preferences', [
                'email_notifications' => false,
                'push_notifications' => true,
                'comment_alerts' => false,
            ]);

        $patchRes->assertStatus(200)
            ->assertJsonPath('data.email_notifications', false)
            ->assertJsonPath('data.push_notifications', true)
            ->assertJsonPath('data.comment_alerts', false);
    }

    /**
     * Requirement 9.8: Intelligent Notification Grouping.
     */
    public function test_intelligent_notification_grouping_aggregates_events(): void
    {
        $recipient = $this->createUser();
        $cacheService = app(CacheServiceInterface::class);
        $queueService = app(QueueServiceInterface::class);
        $realtimeService = app(RealtimeServiceInterface::class);

        // First like event on post #555
        $job1 = new DispatchNotificationJob(
            recipientId: $recipient->id,
            type: 'notification.reaction',
            data: [
                'group_key' => 'post_reaction_555',
                'actor_name' => 'Maya',
                'message' => 'Maya reacted to your post.',
            ],
            channels: ['database']
        );
        $job1->handle($cacheService, $queueService, $realtimeService);

        $this->assertEquals(1, DB::table('notifications')->where('notifiable_id', $recipient->id)->count());

        // Second like event on post #555
        $job2 = new DispatchNotificationJob(
            recipientId: $recipient->id,
            type: 'notification.reaction',
            data: [
                'group_key' => 'post_reaction_555',
                'actor_name' => 'Tanvir',
                'message' => 'Tanvir reacted to your post.',
            ],
            channels: ['database']
        );
        $job2->handle($cacheService, $queueService, $realtimeService);

        // Count in DB remains 1, grouped together
        $this->assertEquals(1, DB::table('notifications')->where('notifiable_id', $recipient->id)->count());

        $record = DB::table('notifications')->where('notifiable_id', $recipient->id)->first();
        $data = json_decode($record->data, true);
        $this->assertEquals(2, $data['group_count']);
        $this->assertContains('Tanvir', $data['actors']);
    }
}
