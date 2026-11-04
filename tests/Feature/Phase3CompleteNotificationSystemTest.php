<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\NotificationReadAllEvent;
use App\Events\NotificationReadEvent;
use App\Jobs\DispatchNotificationJob;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase3CompleteNotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(string $username, string $name): User
    {
        $user = User::factory()->create([
            'username' => $username,
            'name' => $name,
            'email' => "{$username}@example.com",
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'avatar_url' => "https://ui-avatars.com/api/?name={$username}",
                'bio' => "Bio of {$name}",
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'dark_mode' => true,
            ]
        );

        return $user;
    }

    /**
     * 1. Test Notification Generation, Dispatching & Persistence
     */
    public function test_notification_generation_dispatching_and_persistence(): void
    {
        $recipient = $this->createUser('notif_recipient', 'Recipient User');
        $sender = $this->createUser('notif_sender', 'Sender User');

        $notificationService = app(NotificationServiceInterface::class);
        $cacheService = app(CacheServiceInterface::class);
        $queueService = app(QueueServiceInterface::class);
        $realtimeService = app(RealtimeServiceInterface::class);

        // Dispatch a social reaction notification
        $job = new DispatchNotificationJob(
            recipientId: $recipient->id,
            type: 'social.reaction',
            data: [
                'title' => 'নতুন প্রতিক্রিয়া',
                'message' => 'Sender User আপনার পোস্টে লাভ রিয়্যাক্ট করেছেন।',
                'actor_id' => $sender->id,
                'actor_name' => $sender->name,
                'actor_username' => $sender->username,
                'actor_avatar' => $sender->avatar_url,
                'action_url' => '/posts/42',
                'category' => 'social',
            ],
            channels: ['database', 'realtime']
        );
        $job->handle($cacheService, $queueService, $realtimeService);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $recipient->id,
            'type' => 'social.reaction',
        ]);

        // Verify unread count is exactly 1
        $unreadCount = $notificationService->getUnreadCount($recipient);
        $this->assertEquals(1, $unreadCount);

        // Verify API retrieves the notification with complete DTO payload
        $apiRes = $this->actingAs($recipient, 'sanctum')->getJson('/api/v1/notifications');
        $apiRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.type', 'social.reaction')
            ->assertJsonPath('data.0.category', 'social')
            ->assertJsonPath('data.0.action_url', '/posts/42')
            ->assertJsonPath('data.0.actor.name', 'Sender User');
    }

    /**
     * 2. Test Category and Unread Filtering
     */
    public function test_category_and_unread_filtering(): void
    {
        $user = $this->createUser('filter_user', 'Filter User');

        // Unread social notification
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'social.friend_request',
            'category' => 'social',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'ফ্রেন্ড রিকোয়েস্ট', 'message' => 'Alice sent you a friend request.']),
            'read_at' => null,
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        // Read security notification
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'security.login',
            'category' => 'security',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'নতুন লগইন', 'message' => 'Logged in from Chrome Mac.']),
            'read_at' => now()->subMinute(),
            'created_at' => now()->subMinutes(20),
            'updated_at' => now()->subMinute(),
        ]);

        // 1. Filter by category = security
        $secRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications?category=security');
        $secRes->assertStatus(200);
        $this->assertCount(1, $secRes->json('data'));
        $this->assertEquals('security', $secRes->json('data.0.category'));

        // 2. Filter by unread = unread
        $unreadRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications?filter=unread');
        $unreadRes->assertStatus(200);
        $this->assertCount(1, $unreadRes->json('data'));
        $this->assertNull($unreadRes->json('data.0.read_at'));
    }

    /**
     * 3. Test Mark Single Notification As Read
     */
    public function test_mark_single_notification_as_read(): void
    {
        Event::fake([NotificationReadEvent::class]);

        $user = $this->createUser('read_user', 'Read User');
        $notifId = Str::uuid()->toString();

        DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'social.comment',
            'category' => 'social',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'New Comment', 'message' => 'Bob commented on your post.']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Mark as read via PATCH
        $patchRes = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/notifications/{$notifId}/read");
        $patchRes->assertStatus(200)
            ->assertJsonPath('data.read', true)
            ->assertJsonPath('data.unread_count', 0);

        $this->assertNotNull(DB::table('notifications')->where('id', $notifId)->value('read_at'));
        Event::assertDispatched(NotificationReadEvent::class);
    }

    /**
     * 4. Test Mark All Notifications As Read (Both /read-all and /mark-all-read endpoints)
     */
    public function test_mark_all_notifications_as_read(): void
    {
        Event::fake([NotificationReadAllEvent::class]);

        $user = $this->createUser('markall_user', 'Mark All User');

        for ($i = 1; $i <= 3; $i++) {
            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'social.like',
                'category' => 'social',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode(['title' => "Like #{$i}"]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Test POST /api/v1/notifications/mark-all-read (frontend compatibility)
        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v1/notifications/mark-all-read');
        $res->assertStatus(200)
            ->assertJsonPath('data.unread_count', 0);

        $unreadRemaining = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $this->assertSame(0, $unreadRemaining);
        Event::assertDispatched(NotificationReadAllEvent::class);
    }

    /**
     * 5. Test Delete Single Notification and Clear Read Notifications
     */
    public function test_delete_notification_and_clear_read(): void
    {
        $user = $this->createUser('delete_user', 'Delete User');
        $unreadId = Str::uuid()->toString();
        $readId = Str::uuid()->toString();

        DB::table('notifications')->insert([
            'id' => $unreadId,
            'type' => 'social.mention',
            'category' => 'social',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Mention']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notifications')->insert([
            'id' => $readId,
            'type' => 'social.share',
            'category' => 'social',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Share']),
            'read_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Clear only read notifications
        $clearRes = $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/notifications?only_read=true');
        $clearRes->assertStatus(200)
            ->assertJsonPath('data.deleted_count', 1);

        $this->assertNull(DB::table('notifications')->where('id', $readId)->first());
        $this->assertNotNull(DB::table('notifications')->where('id', $unreadId)->first());

        // Delete the single unread notification
        $delSingleRes = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/notifications/{$unreadId}");
        $delSingleRes->assertStatus(200)
            ->assertJsonPath('data.deleted', true);

        $this->assertNull(DB::table('notifications')->where('id', $unreadId)->first());
    }

    /**
     * 6. Test Notification Preferences (Retrieve & Update)
     */
    public function test_notification_preferences_management(): void
    {
        $user = $this->createUser('pref_user', 'Preferences User');

        // Retrieve current preferences
        $getRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications/preferences');
        $getRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Update preferences
        $patchRes = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/notifications/preferences', [
            'email_notifications' => false,
            'push_notifications' => true,
            'comment_alerts' => false,
            'like_alerts' => true,
        ]);

        $patchRes->assertStatus(200)
            ->assertJsonPath('data.email_notifications', false)
            ->assertJsonPath('data.push_notifications', true)
            ->assertJsonPath('data.comment_alerts', false)
            ->assertJsonPath('data.like_alerts', true);
    }
}
