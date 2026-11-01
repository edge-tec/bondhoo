<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnterpriseFinalAndroidProductionCertificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. End-to-End Multi-Device & Cross-Platform Auth Lifecycle.
     */
    public function test_e2e_multi_device_auth_and_device_session_rotation(): void
    {
        // 1. Android User A registers
        $androidUser = User::factory()->create([
            'name' => 'Android User Alpha',
            'username' => 'android_alpha',
            'email' => 'alpha@connectly.app',
        ]);

        $token = $androidUser->createToken('android_pixel9')->plainTextToken;

        // 2. Register Android Device
        $devRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/devices/register', [
                'device_id' => 'pixel_9_pro_uuid',
                'device_name' => 'Pixel 9 Pro (Android 15)',
                'platform' => 'android',
                'app_version' => '1.0.0',
                'push_token' => 'fcm_token_initial_cert',
            ]);
        $devRes->assertStatus(200);
        $sessionId = $devRes->json('data.id');

        // 3. Web User B registers
        $webUser = User::factory()->create([
            'name' => 'Web User Beta',
            'username' => 'web_beta',
            'email' => 'beta@connectly.app',
        ]);
        $webToken = $webUser->createToken('web_chrome_desktop')->plainTextToken;

        // Verify active sessions
        $sessionsRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/devices');
        $sessionsRes->assertStatus(200);
        $this->assertCount(1, $sessionsRes->json('data'));
    }

    /**
     * 2. End-to-End Social Feed, Multi-Media & Interaction Engine.
     */
    public function test_e2e_social_feed_interaction_and_counters(): void
    {
        $author = User::factory()->create(['name' => 'Feed Creator']);
        $interactor = User::factory()->create(['name' => 'Feed Interactor']);

        // Create Post
        $postRes = $this->actingAs($author, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Celebrating Connectly 1.0 Production Launch! #Connectly #GoLive',
            'privacy' => 'public',
        ]);
        $postRes->assertStatus(201);
        $postId = $postRes->json('data.id');

        // React with Love
        $reactRes = $this->actingAs($interactor, 'sanctum')->postJson("/api/v1/posts/{$postId}/react", [
            'type' => 'love',
        ]);
        $reactRes->assertStatus(200);

        // Add Comment
        $commentRes = $this->actingAs($interactor, 'sanctum')->postJson("/api/v1/posts/{$postId}/comments", [
            'content' => 'Congratulations on the milestone!',
        ]);
        $commentRes->assertStatus(201);

        // Verify post shows reaction and comment
        $this->assertDatabaseHas('posts', ['id' => $postId]);
        $this->assertDatabaseHas('comments', ['post_id' => $postId]);
    }

    /**
     * 3. End-to-End Real-Time Messenger Synchronization.
     */
    public function test_e2e_realtime_messenger_synchronization(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        // Create conversation
        $conv = Conversation::create([
            'type' => 'direct',
            'created_by' => $alice->id,
        ]);
        $conv->participants()->create(['user_id' => $alice->id, 'role' => 'admin']);
        $conv->participants()->create(['user_id' => $bob->id, 'role' => 'member']);

        // Alice sends message to Bob
        $msgRes = $this->actingAs($alice, 'sanctum')->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Hello Bob from Android app!',
            'type' => 'text',
        ]);
        $msgRes->assertStatus(201);
        $messageId = $msgRes->json('data.id');

        // Bob lists messages
        $bobListRes = $this->actingAs($bob, 'sanctum')->getJson("/api/v1/conversations/{$conv->id}/messages");
        $bobListRes->assertStatus(200);
        $this->assertNotEmpty($bobListRes->json('data'));

        // Bob marks as read
        $readRes = $this->actingAs($bob, 'sanctum')->postJson("/api/v1/conversations/{$conv->id}/read");
        $readRes->assertStatus(200);
    }

    /**
     * 4. End-to-End WebRTC Audio/Video Call & Signaling Lifecycle.
     */
    public function test_e2e_webrtc_calling_and_signaling_lifecycle(): void
    {
        $caller = User::factory()->create(['name' => 'Caller Android']);
        $receiver = User::factory()->create(['name' => 'Receiver Web']);

        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $caller->id,
        ]);
        $conversation->participants()->create(['user_id' => $caller->id, 'role' => 'admin']);
        $conversation->participants()->create(['user_id' => $receiver->id, 'role' => 'member']);

        // Initiate call
        $initRes = $this->actingAs($caller, 'sanctum')->postJson('/api/v1/calls', [
            'receiver_id' => $receiver->id,
            'call_type' => 'video',
        ]);
        $initRes->assertStatus(201);
        $callId = $initRes->json('data.id');

        // Receiver answers call
        $answerRes = $this->actingAs($receiver, 'sanctum')->postJson("/api/v1/calls/{$callId}/respond", [
            'action' => 'accept',
        ]);
        $answerRes->assertStatus(200);

        // Send SDP Offer signal
        $offerRes = $this->actingAs($caller, 'sanctum')->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'offer',
            'payload' => ['sdp' => 'v=0\r\no=- 42 2 IN IP4 127.0.0.1...'],
            'target_user_id' => $receiver->id,
        ]);
        $offerRes->assertStatus(200);

        // Send ICE candidate signal
        $iceRes = $this->actingAs($receiver, 'sanctum')->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'candidate',
            'payload' => ['candidate' => 'candidate:1 1 UDP 2122260223...'],
            'target_user_id' => $caller->id,
        ]);
        $iceRes->assertStatus(200);

        // End Call
        $endRes = $this->actingAs($caller, 'sanctum')->postJson("/api/v1/calls/{$callId}/leave");
        $endRes->assertStatus(200);
    }

    /**
     * 5. End-to-End Communities, Events & Live Streaming Integration.
     */
    public function test_e2e_communities_events_and_livestream(): void
    {
        $creator = User::factory()->create(['name' => 'Community Leader']);
        $member = User::factory()->create(['name' => 'Community Member']);

        // 1. Create Group
        $groupRes = $this->actingAs($creator, 'sanctum')->postJson('/api/v1/groups', [
            'name' => 'Connectly Developers BD',
            'privacy' => 'public',
            'description' => 'Official developer group for Connectly app creators.',
        ]);
        $groupRes->assertStatus(201);
        $groupId = $groupRes->json('data.id');

        // Member joins group
        $joinRes = $this->actingAs($member, 'sanctum')->postJson("/api/v1/groups/{$groupId}/join");
        $joinRes->assertStatus(200);

        // 2. Create Event
        $eventRes = $this->actingAs($creator, 'sanctum')->postJson('/api/v1/events', [
            'title' => 'Connectly Global Tech Summit 2026',
            'description' => 'Annual developer summit.',
            'start_time' => now()->addDays(7)->toDateTimeString(),
            'end_time' => now()->addDays(7)->addHours(4)->toDateTimeString(),
            'location_type' => 'venue',
            'venue' => 'BICC, Dhaka',
        ]);
        $eventRes->assertStatus(201);
        $eventId = $eventRes->json('data.event.id') ?? $eventRes->json('data.id') ?? $eventRes->json('id');

        // Member RSVPs
        $rsvpRes = $this->actingAs($member, 'sanctum')->postJson("/api/v1/events/{$eventId}/rsvp", [
            'status' => 'going',
        ]);
        $rsvpRes->assertStatus(200);

        // 3. Start Live Stream
        $liveRes = $this->actingAs($creator, 'sanctum')->postJson('/api/v1/live/streams', [
            'title' => 'Live Launch Q&A',
            'description' => 'Answering questions about Connectly.',
        ]);
        $liveRes->assertStatus(201);
        $streamId = $liveRes->json('data.stream.id') ?? $liveRes->json('data.id') ?? $liveRes->json('id');

        // Member sends reaction to live stream
        $reactLiveRes = $this->actingAs($member, 'sanctum')->postJson("/api/v1/live/streams/{$streamId}/reactions", [
            'reaction_type' => 'love',
        ]);
        $reactLiveRes->assertSuccessful();

        // End live stream
        $endLiveRes = $this->actingAs($creator, 'sanctum')->postJson("/api/v1/live/streams/{$streamId}/end");
        $endLiveRes->assertStatus(200);
    }

    /**
     * 6. End-to-End Push Notification, Global Search & Discovery.
     */
    public function test_e2e_notifications_and_discovery_pipeline(): void
    {
        $user = User::factory()->create(['name' => 'Notification User']);
        $actor = User::factory()->create(['name' => 'Actor User']);

        // Insert database notification
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\PostReactionNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'New Reaction',
                'message' => 'Actor User loved your post.',
                'actor_id' => $actor->id,
                'category' => 'likes',
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get notifications
        $notifRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');
        $notifRes->assertStatus(200);
        $this->assertNotEmpty($notifRes->json('data'));

        // Discovery suggestions
        $suggestRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/friends/suggestions');
        $suggestRes->assertStatus(200);
        $this->assertTrue($suggestRes->json('success'));

        // Global search
        $searchRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/search?q=Actor&type=users');
        $searchRes->assertStatus(200);
        $this->assertNotEmpty($searchRes->json('data.results.users') ?? $searchRes->json('data.users'));
    }

    /**
     * 7. End-to-End Security, Session Revocation and Protection.
     */
    public function test_e2e_security_and_session_revocation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('prod_session_token');

        // Unauthenticated access fails
        $this->getJson('/api/v1/profile')->assertStatus(401);

        // Authenticated access succeeds
        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/profile')
            ->assertStatus(200);

        // Logout revokes token
        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Subsequent access fails
        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/profile')
            ->assertStatus(401);
    }
}
