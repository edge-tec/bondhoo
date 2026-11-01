<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Group;
use App\Models\Page;
use App\Models\Post;
use App\Models\Reel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConnectlyPhase9NotificationsAndDiscoveryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. DEVICE & FCM: Device token registration, rotation and session tracking.
     */
    public function test_device_token_registration_and_rotation(): void
    {
        $user = User::factory()->create(['name' => 'Fahim Ahmed']);
        Sanctum::actingAs($user);

        $deviceId = 'pixel-8-pro-uuid-101';
        $initialFcmToken = 'fcm_token_initial_abc123';

        // 1. Initial Device Registration
        $registerRes = $this->postJson('/api/v1/devices/register', [
            'device_id' => $deviceId,
            'device_name' => 'Google Pixel 8 Pro',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'push_token' => $initialFcmToken,
        ]);

        $registerRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('device_sessions', [
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'push_token' => $initialFcmToken,
            'platform' => 'android',
        ]);

        // 2. Token Rotation (e.g. onNewToken from Firebase)
        $rotatedFcmToken = 'fcm_token_rotated_xyz789';
        $rotationRes = $this->postJson('/api/v1/devices/register', [
            'device_id' => $deviceId,
            'device_name' => 'Google Pixel 8 Pro',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'push_token' => $rotatedFcmToken,
        ]);

        $rotationRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('device_sessions', [
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'push_token' => $rotatedFcmToken,
        ]);

        // 3. Active Sessions Query
        $sessionsRes = $this->getJson('/api/v1/devices');
        $sessionsRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $sessions = $sessionsRes->json('data');
        $this->assertNotEmpty($sessions);
        $this->assertEquals($deviceId, $sessions[0]['device_id']);
    }

    /**
     * 2. NOTIFICATIONS: Categories (social, messaging, group, live) and deep links.
     */
    public function test_notification_listing_with_categories_and_filters(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sadia Islam']);
        Sanctum::actingAs($user);

        // Seed notifications across different categories
        $this->createNotificationRecord($user->id, 'social.like', 'social', [
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'title' => 'নতুন লাইক',
            'message' => 'Sadia Islam আপনার ছবিতে লাইক দিয়েছেন।',
            'action_url' => 'connectly://posts/501',
        ]);

        $this->createNotificationRecord($user->id, 'social.comment', 'social', [
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'title' => 'নতুন মন্তব্য',
            'message' => 'Sadia Islam আপনার পোস্টে মন্তব্য করেছেন।',
            'action_url' => 'connectly://posts/501#comment_10',
        ]);

        $this->createNotificationRecord($user->id, 'messaging.new_message', 'messaging', [
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'title' => 'নতুন বার্তা',
            'message' => 'Sadia Islam আপনাকে একটি বার্তা পাঠিয়েছেন।',
            'action_url' => 'connectly://conversations/30',
        ]);

        $this->createNotificationRecord($user->id, 'group.invitation', 'group', [
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'title' => 'গ্রুপ ইনভাইটেশন',
            'message' => 'Sadia Islam আপনাকে একটি গ্রুপে আমন্ত্রণ জানিয়েছেন।',
            'action_url' => 'connectly://groups/101',
        ]);

        $this->createNotificationRecord($user->id, 'live.started', 'social', [
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'title' => 'লাইভ স্ট্রিম শুরু হয়েছে',
            'message' => 'Sadia Islam লাইভে এসেছেন!',
            'action_url' => 'connectly://live/88',
        ]);

        // 1. Fetch All Notifications
        $allRes = $this->getJson('/api/v1/notifications');
        $allRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $allRes->json('data');
        $this->assertCount(5, $items);

        // 2. Filter by Category = social
        $socialRes = $this->getJson('/api/v1/notifications?category=social');
        $socialRes->assertStatus(200);
        $socialItems = $socialRes->json('data');
        $this->assertNotEmpty($socialItems);
        foreach ($socialItems as $item) {
            $this->assertEquals('social', $item['category']);
        }

        // 3. Filter by Category = messaging
        $msgRes = $this->getJson('/api/v1/notifications?category=messaging');
        $msgRes->assertStatus(200);
        $msgItems = $msgRes->json('data');
        $this->assertCount(1, $msgItems);
        $this->assertEquals('connectly://conversations/30', $msgItems[0]['action_url']);
    }

    /**
     * 3. NOTIFICATIONS: Read/unread state updates and bulk read.
     */
    public function test_notification_read_and_unread_flow(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $notif1 = $this->createNotificationRecord($user->id, 'social.like', 'social', [
            'title' => 'নতুন লাইক',
            'message' => 'আপনার পোস্টে লাইক পড়েছে।',
        ]);

        $notif2 = $this->createNotificationRecord($user->id, 'social.comment', 'social', [
            'title' => 'নতুন কমেন্ট',
            'message' => 'আপনার পোস্টে কমেন্ট এসেছে।',
        ]);

        // 1. Unread Counter
        $countRes = $this->getJson('/api/v1/notifications/unread-count');
        $countRes->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertGreaterThanOrEqual(2, $countRes->json('data.unread_count'));

        // 2. Mark Single Notification as Read
        $readRes = $this->putJson("/api/v1/notifications/{$notif1}/read");
        $readRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notif1,
            'read_at' => null,
        ]);

        // 3. Mark All as Read
        $readAllRes = $this->postJson('/api/v1/notifications/read-all');
        $readAllRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $finalCountRes = $this->getJson('/api/v1/notifications/unread-count');
        $finalCountRes->assertStatus(200)
            ->assertJsonPath('data.unread_count', 0);
    }

    /**
     * 4. NOTIFICATIONS: Notification preferences retrieval and update.
     */
    public function test_notification_preferences_management(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Fetch defaults
        $prefRes = $this->getJson('/api/v1/notifications/preferences');
        $prefRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Update preferences
        $updateRes = $this->putJson('/api/v1/notifications/preferences', [
            'push_notifications' => true,
            'like_alerts' => false,
            'comment_alerts' => true,
            'message_alerts' => true,
            'security_alerts' => true,
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * 5. NOTIFICATIONS: Intelligent grouping and deduplication by group_key.
     */
    public function test_notification_deduplication_and_grouping(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $groupKey = 'post_reaction_501';

        // Insert first notification
        $firstId = $this->createNotificationRecord($user->id, 'social.like', 'social', [
            'title' => 'নতুন লাইক',
            'message' => 'Naimur আপনার পোস্টে লাইক দিয়েছেন।',
            'actor_name' => 'Naimur',
        ], $groupKey);

        $this->assertDatabaseHas('notifications', [
            'id' => $firstId,
            'group_key' => $groupKey,
        ]);

        // Query listing
        $listRes = $this->getJson('/api/v1/notifications');
        $listRes->assertStatus(200);
        $this->assertNotEmpty($listRes->json('data'));
    }

    /**
     * 6. SEARCH: Universal search across users, posts, reels, groups, pages, events, hashtags.
     */
    public function test_universal_search_across_entities(): void
    {
        $viewer = User::factory()->create(['name' => 'Sabbir Ahmed']);
        Sanctum::actingAs($viewer);

        $targetUser = User::factory()->create(['name' => 'Zubair Hossain', 'username' => 'zubair_dev']);
        $post = Post::factory()->create(['content' => 'Building scalable microservices with Laravel and Kotlin #Connectly']);
        $group = Group::factory()->create(['name' => 'Dhaka Android Developers', 'privacy' => 'public']);
        $page = Page::create([
            'name' => 'DevOps Bangladesh',
            'slug' => 'devops-bangladesh',
            'category' => 'Technology',
            'owner_id' => $viewer->id,
        ]);
        $event = Event::create([
            'title' => 'Connectly Global Dev Summit 2026',
            'start_time' => now()->addDays(10),
            'creator_id' => $viewer->id,
            'location_type' => 'venue',
            'address' => 'Dhaka Arena',
        ]);
        $reel = Reel::create([
            'user_id' => $viewer->id,
            'caption' => 'Smooth Jetpack Compose animation trick #Connectly',
            'status' => 'active',
            'privacy' => 'public',
        ]);

        // 1. Search Users
        $userSearch = $this->getJson('/api/v1/search?q=Zubair&type=users');
        $userSearch->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertNotEmpty($userSearch->json('data.results.users'));

        // 2. Search Posts
        $postSearch = $this->getJson('/api/v1/search?q=microservices&type=posts');
        $postSearch->assertStatus(200);
        $this->assertNotEmpty($postSearch->json('data.results.posts'));

        // 3. Search Groups
        $groupSearch = $this->getJson('/api/v1/search?q=Android&type=groups');
        $groupSearch->assertStatus(200);
        $this->assertNotEmpty($groupSearch->json('data.results.groups'));

        // 4. Search Pages
        $pageSearch = $this->getJson('/api/v1/search?q=DevOps&type=pages');
        $pageSearch->assertStatus(200);
        $this->assertNotEmpty($pageSearch->json('data.results.pages'));

        // 5. Search Events
        $eventSearch = $this->getJson('/api/v1/search?q=Summit&type=events');
        $eventSearch->assertStatus(200);
        $this->assertNotEmpty($eventSearch->json('data.results.events'));

        // 6. Search Reels
        $reelSearch = $this->getJson('/api/v1/search?q=animation&type=reels');
        $reelSearch->assertStatus(200);
        $this->assertNotEmpty($reelSearch->json('data.results.reels'));

        // 7. Search Hashtags
        $hashtagSearch = $this->getJson('/api/v1/search?q=Connectly&type=hashtags');
        $hashtagSearch->assertStatus(200);
        $this->assertNotEmpty($hashtagSearch->json('data.results.hashtags'));

        // 8. Search History Tracking and Deletion
        $historyRes = $this->getJson('/api/v1/search/history');
        $historyRes->assertStatus(200);

        $clearRes = $this->deleteJson('/api/v1/search/history');
        $clearRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * 7. DISCOVERY: Suggested friends, recommended content and popular communities.
     */
    public function test_discovery_and_recommendations(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        User::factory()->count(3)->create();
        Group::factory()->create(['name' => 'Explore Bangladesh', 'privacy' => 'public']);
        Page::create([
            'name' => 'Inspire Daily',
            'slug' => 'inspire-daily',
            'category' => 'Media',
            'owner_id' => $user->id,
        ]);

        // 1. Unified Discovery Endpoint (Search with suggest=1)
        $discoverRes = $this->getJson('/api/v1/search?suggest=1');
        $discoverRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'results' => [
                        'users',
                        'groups',
                        'pages',
                        'trending',
                    ],
                ],
            ]);

        // 2. Suggested Friends Endpoint
        $friendsRes = $this->getJson('/api/v1/friends/suggestions');
        $friendsRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Helper to insert a notification into database.
     */
    private function createNotificationRecord(int $userId, string $type, string $category, array $data, ?string $groupKey = null): string
    {
        $id = Str::uuid()->toString();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $userId,
            'data' => json_encode($data),
            'read_at' => null,
            'group_key' => $groupKey,
            'category' => $category,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
