<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\NotificationDeletedEvent;
use App\Events\NotificationReadAllEvent;
use App\Events\NotificationReadEvent;
use App\Jobs\DispatchNotificationJob;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase10CompleteProfessionalNotificationSystemTest extends TestCase
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
     * Test notification list with pagination and unread filter.
     */
    public function test_notification_list_with_pagination_and_filters(): void
    {
        $user = $this->createUser('notif_filter_user', 'Filter User');
        $sender = $this->createUser('notif_filter_sender', 'Sender User');

        // Insert 3 unread notifications and 2 read notifications
        for ($i = 1; $i <= 3; $i++) {
            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'social.like',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => "Like #{$i}",
                    'message' => "Sender liked your post #{$i}",
                    'actor_id' => $sender->id,
                    'actor_name' => $sender->name,
                    'category' => 'social',
                ]),
                'read_at' => null,
                'category' => 'social',
                'created_at' => now()->subMinutes($i),
                'updated_at' => now()->subMinutes($i),
            ]);
        }

        for ($i = 4; $i <= 5; $i++) {
            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'group.invitation',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => "Group Invite #{$i}",
                    'message' => 'You were invited to a group',
                    'actor_id' => $sender->id,
                    'actor_name' => $sender->name,
                    'category' => 'group',
                ]),
                'read_at' => now(),
                'category' => 'group',
                'created_at' => now()->subMinutes($i + 10),
                'updated_at' => now()->subMinutes($i + 10),
            ]);
        }

        // 1. Fetch all notifications
        $responseAll = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');
        $responseAll->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.unread_count', 3);

        // 2. Fetch unread notifications only
        $responseUnread = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications?filter=unread');
        $responseUnread->assertOk()
            ->assertJsonPath('meta.total', 3);

        // 3. Fetch group category only
        $responseGroup = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications?category=group');
        $responseGroup->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    /**
     * Test mark as read, mark all read, and unread count endpoint.
     */
    public function test_mark_as_read_and_realtime_events(): void
    {
        Event::fake([NotificationReadEvent::class, NotificationReadAllEvent::class]);

        $user = $this->createUser('notif_read_user', 'Read User');
        $notifId = Str::uuid()->toString();

        DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'social.comment',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Comment', 'message' => 'New comment']),
            'read_at' => null,
            'category' => 'social',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Unread count is 1
        $countRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications/unread-count');
        $countRes->assertOk()->assertJsonPath('data.unread_count', 1);

        // Mark as read
        $readRes = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/notifications/{$notifId}/read");
        $readRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.read', true)
            ->assertJsonPath('data.unread_count', 0);

        Event::assertDispatched(NotificationReadEvent::class);

        // Mark all as read
        $allRes = $this->actingAs($user, 'sanctum')->postJson('/api/v1/notifications/read-all');
        $allRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 0);

        Event::assertDispatched(NotificationReadAllEvent::class);
    }

    /**
     * Test notification deletion with IDOR ownership protection.
     */
    public function test_notification_deletion_and_idor_protection(): void
    {
        Event::fake([NotificationDeletedEvent::class]);

        $owner = $this->createUser('notif_owner', 'Owner User');
        $attacker = $this->createUser('notif_attacker', 'Attacker User');

        $notifId = Str::uuid()->toString();

        DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'social.share',
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
            'data' => json_encode(['title' => 'Share', 'message' => 'Post shared']),
            'read_at' => null,
            'category' => 'social',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attacker attempts to delete owner's notification -> 404 forbidden/not found
        $attackRes = $this->actingAs($attacker, 'sanctum')->deleteJson("/api/v1/notifications/{$notifId}");
        $attackRes->assertStatus(404);

        $this->assertDatabaseHas('notifications', ['id' => $notifId]);

        // Owner deletes their notification -> 200 OK
        $ownerRes = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/notifications/{$notifId}");
        $ownerRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('notifications', ['id' => $notifId]);
        Event::assertDispatched(NotificationDeletedEvent::class);
    }

    /**
     * Test notification preferences retrieval and updates.
     */
    public function test_notification_preferences_get_and_update(): void
    {
        $user = $this->createUser('notif_prefs_user', 'Prefs User');

        // GET preferences
        $getRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications/preferences');
        $getRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email_notifications', true)
            ->assertJsonPath('data.push_notifications', true);

        // UPDATE preferences
        $updateRes = $this->actingAs($user, 'sanctum')->putJson('/api/v1/notifications/preferences', [
            'push_notifications' => false,
            'email_notifications' => false,
            'comment_alerts' => true,
            'group_alerts' => false,
        ]);

        $updateRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.push_notifications', false)
            ->assertJsonPath('data.email_notifications', false)
            ->assertJsonPath('data.comment_alerts', true)
            ->assertJsonPath('data.group_alerts', false);

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'push_notifications' => false,
            'email_notifications' => false,
        ]);
    }

    /**
     * Test device session registration with FCM push token.
     */
    public function test_device_registration_with_fcm_token(): void
    {
        $user = $this->createUser('notif_device_user', 'Device User');

        $registerRes = $this->actingAs($user, 'sanctum')->postJson('/api/v1/devices/register', [
            'device_id' => 'android_pixel_8_pro_test',
            'device_name' => 'Google Pixel 8 Pro',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'push_token' => 'fcm_test_token_sample_abc123',
        ]);

        $registerRes->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('device_sessions', [
            'user_id' => $user->id,
            'device_id' => 'android_pixel_8_pro_test',
            'platform' => 'android',
            'push_token' => 'fcm_test_token_sample_abc123',
            'is_revoked' => false,
        ]);
    }

    /**
     * Test dispatch notification job with deduplication and grouping.
     */
    public function test_dispatch_notification_with_deduplication(): void
    {
        $recipient = $this->createUser('dedup_recipient', 'Dedup Recipient');
        $sender1 = $this->createUser('dedup_sender_1', 'Sender One');
        $sender2 = $this->createUser('dedup_sender_2', 'Sender Two');

        $cacheService = app(CacheServiceInterface::class);
        $queueService = app(QueueServiceInterface::class);
        $realtimeService = app(RealtimeServiceInterface::class);

        $groupKey = "post_like_1001_{$recipient->id}";

        // First like
        $job1 = new DispatchNotificationJob(
            recipientId: $recipient->id,
            type: 'social.like',
            data: [
                'title' => 'পোস্টে লাইক',
                'message' => 'Sender One আপনার পোস্টে লাইক দিয়েছেন।',
                'actor_id' => $sender1->id,
                'actor_name' => $sender1->name,
                'group_key' => $groupKey,
            ],
            channels: ['database']
        );
        $job1->handle($cacheService, $queueService, $realtimeService);

        $this->assertDatabaseCount('notifications', 1);

        // Second like with same groupKey -> should group into single row
        $job2 = new DispatchNotificationJob(
            recipientId: $recipient->id,
            type: 'social.like',
            data: [
                'title' => 'পোস্টে লাইক',
                'message' => 'Sender Two আপনার পোস্টে লাইক দিয়েছেন।',
                'actor_id' => $sender2->id,
                'actor_name' => $sender2->name,
                'group_key' => $groupKey,
            ],
            channels: ['database']
        );
        $job2->handle($cacheService, $queueService, $realtimeService);

        // Still exactly 1 row in database due to intelligent grouping!
        $this->assertDatabaseCount('notifications', 1);

        $row = DB::table('notifications')->where('group_key', $groupKey)->first();
        $this->assertNotNull($row);
        $data = json_decode($row->data, true);
        $this->assertEquals(2, $data['group_count']);
    }
}
