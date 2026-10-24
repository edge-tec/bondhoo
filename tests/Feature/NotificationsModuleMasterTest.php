<?php

namespace Tests\Feature;

use App\Events\NotificationReadAllEvent;
use App\Events\NotificationReadEvent;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationsModuleMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Helper to create a user with a profile.
     */
    protected function createTestUser(string $username): User
    {
        static $counter = 500;
        $counter++;

        $user = User::factory()->create([
            'username' => $username,
            'name' => ucfirst($username).' User',
            'email' => "{$username}{$counter}@example.com",
            'phone' => "+880190000{$counter}",
            'status' => 'active',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => ucfirst($username),
            'last_name' => 'User',
            'display_name' => ucfirst($username).' User',
        ]);

        return $user;
    }

    /**
     * Helper to insert a notification into database.
     */
    protected function insertNotification(User $user, array $overrides = []): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert(array_merge([
            'id' => $id,
            'type' => 'notification.friend_request',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'নতুন ফ্রেন্ড রিকোয়েস্ট',
                'message' => 'আপনাকে একজন ব্যবহারকারী ফ্রেন্ড রিকোয়েস্ট পাঠিয়েছেন।',
                'actor_id' => 999,
                'actor_name' => 'John Doe',
                'action_url' => '/friends/requests',
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return $id;
    }

    /**
     * Test 1: Notifications retrieval with pagination and filtering by unread/read state.
     */
    public function test_database_notifications_pagination_and_read_state_filtering(): void
    {
        $user = $this->createTestUser('notifuser');

        // Create 3 unread notifications and 2 read notifications
        for ($i = 0; $i < 3; $i++) {
            $this->insertNotification($user, ['created_at' => now()->subMinutes(10 - $i)]);
        }
        for ($j = 0; $j < 2; $j++) {
            $this->insertNotification($user, [
                'read_at' => now()->subMinutes(5 - $j),
                'created_at' => now()->subMinutes(20 - $j),
            ]);
        }

        // 1. Fetch all notifications with pagination
        $allResp = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications?per_page=10');

        $allResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notifications retrieved successfully.',
            ])
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.unread_count', 3);

        $this->assertCount(5, $allResp->json('data'));

        // 2. Fetch only unread notifications
        $unreadResp = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications?filter=unread');

        $unreadResp->assertStatus(200)
            ->assertJsonPath('meta.total', 3);

        foreach ($unreadResp->json('data') as $item) {
            $this->assertFalse($item['is_read']);
            $this->assertNull($item['read_at']);
        }

        // 3. Fetch only read notifications
        $readResp = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications?filter=read');

        $readResp->assertStatus(200)
            ->assertJsonPath('meta.total', 2);

        foreach ($readResp->json('data') as $item) {
            $this->assertTrue($item['is_read']);
            $this->assertNotNull($item['read_at']);
        }
    }

    /**
     * Test 2: Unread count endpoint returns accurate count.
     */
    public function test_unread_count_endpoint(): void
    {
        $user = $this->createTestUser('countuser');

        $this->insertNotification($user);
        $this->insertNotification($user);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'unread_count' => 2,
                ],
            ]);
    }

    /**
     * Test 3: Mark single notification as read and verify realtime broadcast.
     */
    public function test_mark_single_notification_as_read_with_realtime_broadcast(): void
    {
        Event::fake([NotificationReadEvent::class]);

        $user = $this->createTestUser('readeruser');
        $notifId = $this->insertNotification($user);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/notifications/{$notifId}/read");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $notifId,
                    'read' => true,
                    'unread_count' => 0,
                ],
            ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notifId,
            'read_at' => null,
        ]);

        Event::assertDispatched(NotificationReadEvent::class, function ($event) use ($user, $notifId) {
            return (int) $event->recipientId === (int) $user->id && $event->notificationId === $notifId;
        });
    }

    /**
     * Test 4: Mark all notifications as read and verify realtime broadcast.
     */
    public function test_mark_all_notifications_as_read_with_realtime_broadcast(): void
    {
        Event::fake([NotificationReadAllEvent::class]);

        $user = $this->createTestUser('readalluser');
        $this->insertNotification($user);
        $this->insertNotification($user);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'updated_count' => 2,
                    'unread_count' => 0,
                ],
            ]);

        $this->assertEquals(0, DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count());

        Event::assertDispatched(NotificationReadAllEvent::class, function ($event) use ($user) {
            return (int) $event->recipientId === (int) $user->id;
        });
    }

    /**
     * Test 5: Access control & IDOR prevention - users cannot modify or delete others' notifications.
     */
    public function test_notification_access_control_and_idor_prevention(): void
    {
        $victim = $this->createTestUser('victimuser');
        $attacker = $this->createTestUser('attackeruser');

        $victimNotifId = $this->insertNotification($victim);

        // Attacker attempts to mark victim's notification as read
        $readAttack = $this->actingAs($attacker, 'sanctum')
            ->putJson("/api/v1/notifications/{$victimNotifId}/read");

        $readAttack->assertStatus(404);

        // Victim's notification must still be unread
        $this->assertDatabaseHas('notifications', [
            'id' => $victimNotifId,
            'read_at' => null,
        ]);

        // Attacker attempts to delete victim's notification
        $deleteAttack = $this->actingAs($attacker, 'sanctum')
            ->deleteJson("/api/v1/notifications/{$victimNotifId}");

        $deleteAttack->assertStatus(404);

        // Victim's notification must still exist
        $this->assertDatabaseHas('notifications', [
            'id' => $victimNotifId,
        ]);
    }

    /**
     * Test 6: User can delete own notification.
     */
    public function test_user_can_delete_own_notification(): void
    {
        $user = $this->createTestUser('delnotifuser');
        $notifId = $this->insertNotification($user);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/notifications/{$notifId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $notifId,
                    'deleted' => true,
                ],
            ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notifId,
        ]);
    }

    /**
     * Test 7: Notification preferences retrieval and update.
     */
    public function test_notification_preferences_get_and_update(): void
    {
        $user = $this->createTestUser('prefuser');

        // Get default preferences
        $getResp = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications/preferences');

        $getResp->assertStatus(200)
            ->assertJsonStructure(['data' => ['email_notifications', 'push_notifications', 'friend_request_alerts']]);

        // Update preferences
        $updateResp = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/notifications/preferences', [
                'email_notifications' => false,
                'push_notifications' => true,
                'friend_request_alerts' => false,
            ]);

        $updateResp->assertStatus(200)
            ->assertJsonPath('data.email_notifications', false)
            ->assertJsonPath('data.friend_request_alerts', false);

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'email_notifications' => false,
            'friend_request_alerts' => false,
        ]);
    }
}
