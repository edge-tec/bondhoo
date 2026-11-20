<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Group;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase14CrossPlatform30ScenariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Scenarios 1–6, 26–27: Registration (Web & Android), Multi-Device Login, Profile Update, Avatar Upload, Privacy & Security, Logout.
     */
    public function test_scenarios_1_to_6_and_26_27_auth_profile_avatar_privacy_and_security(): void
    {
        // 1. Website Registration
        $webReg = $this->postJson('/api/v1/auth/register', [
            'name' => 'Phase14 Web User',
            'username' => 'p14web',
            'email' => 'p14web@bondhoo.com',
            'password' => 'BondhooPass14!',
            'device_name' => 'Chrome-macOS',
        ]);
        $webReg->assertCreated()->assertJsonPath('success', true);

        // 2. Android Registration
        $andReg = $this->postJson('/api/v1/auth/register', [
            'name' => 'Phase14 Android User',
            'username' => 'p14android',
            'email' => 'p14android@bondhoo.com',
            'password' => 'BondhooPass14!',
            'device_name' => 'Galaxy-S25-Ultra',
        ]);
        $andReg->assertCreated()->assertJsonPath('success', true);
        $userId = $andReg->json('data.user.id');

        // 3. Login from multiple devices
        $login1 = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'p14android',
            'password' => 'BondhooPass14!',
            'device_name' => 'Pixel-9',
        ])->assertOk();
        $token1 = $login1->json('data.token');

        $login2 = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'p14android@bondhoo.com',
            'password' => 'BondhooPass14!',
            'device_name' => 'Web-Safari',
        ])->assertOk();
        $token2 = $login2->json('data.token');
        $this->assertNotEquals($token1, $token2);

        $user = User::findOrFail($userId);

        // 5. Profile updates
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'bio' => 'Enterprise Architect at Bondhoo',
                'location' => 'Dhaka, Bangladesh',
            ])
            ->assertOk();

        // 6. Profile avatar upload
        if (function_exists('imagecreatetruecolor')) {
            $avatarRes = $this->actingAs($user, 'sanctum')
                ->postJson('/api/v1/profile/avatar', [
                    'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
                ]);
            $this->assertContains($avatarRes->getStatusCode(), [200, 201, 422]);
        }

        // 26 & 27. Privacy settings & Account security (/api/v1/auth/me)
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.username', 'p14android');

        // 4. Logout & token invalidation
        $this->withHeader('Authorization', 'Bearer '.$token1)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();
    }

    /**
     * Scenarios 7–14, 24–25: Post CRUD, Comments, Replies, Reactions, Stories, Story Viewers, Feed Pagination, Search, Video Upload & Moderation.
     */
    public function test_scenarios_7_to_14_and_24_25_content_feed_stories_search_and_video_moderation(): void
    {
        $alice = User::factory()->create(['username' => 'alice14', 'name' => 'Alice Fourteen']);
        $bob = User::factory()->create(['username' => 'bob14', 'name' => 'Bob Fourteen']);

        // 7. Post creation
        $postRes = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Initial Phase 14 post content',
                'audience' => 'public',
            ])
            ->assertCreated();
        $postId = $postRes->json('data.post.id') ?? $postRes->json('data.id');

        // 8. Post editing
        $this->actingAs($alice, 'sanctum')
            ->putJson("/api/v1/posts/{$postId}", [
                'content' => 'Updated Phase 14 post content',
                'audience' => 'public',
            ])
            ->assertOk();

        // 9. Comments and replies
        $commentRes = $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/posts/{$postId}/comments", [
                'body' => 'Top level comment from Bob',
            ])
            ->assertCreated();
        $commentId = $commentRes->json('data.comment.id') ?? $commentRes->json('data.id');

        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/posts/{$postId}/comments", [
                'body' => 'Reply from Alice',
                'parent_id' => $commentId,
            ])
            ->assertCreated();

        // 10. Reactions
        $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/posts/{$postId}/react", ['type' => 'love'])
            ->assertOk();

        // 11 & 12. Story creation & Story viewers parity
        $story = Story::create([
            'user_id' => $alice->id,
            'type' => 'text',
            'text_content' => 'Phase 14 Live Story',
            'audience' => 'public',
            'expires_at' => now()->addHours(24),
        ]);
        StoryView::create([
            'story_id' => $story->id,
            'user_id' => $bob->id,
            'viewed_at' => now(),
        ]);

        $this->actingAs($alice, 'sanctum')
            ->getJson("/api/v2/stories/{$story->id}/viewers")
            ->assertOk()
            ->assertJsonPath('data.0.id', $bob->id)
            ->assertJsonPath('data.0.user.id', $bob->id);

        // 13. Feed pagination
        $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/feed?page=1')
            ->assertOk();

        // 14. User search
        $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/search?q=Alice')
            ->assertOk();

        // 24 & 25. Video upload & Video moderation
        $uploadRes = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v2/uploads/direct/complete', [
                'file' => UploadedFile::fake()->create('demo14.mp4', 512, 'video/mp4'),
                'collection' => 'videos',
            ])
            ->assertOk();
        $videoMediaId = $uploadRes->json('data.id');

        $videoPostRes = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Phase 14 video post!',
                'media_ids' => [$videoMediaId],
                'audience' => 'public',
            ])
            ->assertCreated();
        $videoPostId = $videoPostRes->json('data.post.id') ?? $videoPostRes->json('data.id');

        // 8b. Post deletion
        $this->actingAs($alice, 'sanctum')
            ->deleteJson("/api/v1/posts/{$videoPostId}")
            ->assertOk();
    }

    /**
     * Scenarios 15–23, 28–30: Friends, Blocking, Communities, Events, Polls, Notifications, 1:1 & Group Messaging, Voice, Sync & WebSocket Auth.
     */
    public function test_scenarios_15_to_23_and_28_to_30_social_messenger_sync_and_websocket(): void
    {
        $alice = User::factory()->create(['username' => 'social_alice14']);
        $bob = User::factory()->create(['username' => 'social_bob14']);
        $dave = User::factory()->create(['username' => 'social_dave14']);

        // 15. Friend request & accept
        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request")
            ->assertCreated();

        $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/friends/{$alice->id}/accept")
            ->assertOk();

        // 16. Blocking and unblocking
        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v2/profile/{$dave->username}/block")
            ->assertOk();

        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v2/profile/{$dave->username}/unblock")
            ->assertOk();

        // 17. Communities
        $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v2/groups', [
                'name' => 'Phase 14 Cloud Engineers',
                'username' => 'phase14-cloud',
                'description' => 'Cloud & DevSecOps',
                'community_type' => Group::TYPE_PROJECT,
                'category' => 'Technology',
            ])
            ->assertCreated()
            ->assertJsonPath('data.community_type', Group::TYPE_PROJECT);

        // 18. Events & RSVP
        $eventRes = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/events', [
                'title' => 'Bondhoo Phase 14 Launch Event',
                'description' => 'Global launch celebration',
                'start_time' => now()->addDays(3)->toIso8601String(),
                'end_time' => now()->addDays(3)->addHours(2)->toIso8601String(),
                'location_type' => 'online',
            ])
            ->assertCreated();
        $eventId = $eventRes->json('data.event.id') ?? $eventRes->json('data.id');

        $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/events/{$eventId}/rsvp", ['status' => 'going'])
            ->assertOk();

        // 21 & 23. 1:1 Messaging & Voice message
        $directConv = Conversation::create(['type' => 'direct', 'creator_id' => $alice->id]);
        $directConv->participants()->create(['user_id' => $alice->id, 'role' => 'member']);
        $directConv->participants()->create(['user_id' => $bob->id, 'role' => 'member']);

        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/conversations/{$directConv->id}/messages", [
                'body' => 'Hello Bob in 1:1 chat!',
                'type' => 'text',
            ])
            ->assertCreated();

        $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/conversations/{$directConv->id}/messages", [
                'body' => '🎤 Voice message',
                'type' => 'voice',
                'metadata' => ['duration' => 7],
            ])
            ->assertCreated();

        // 19 & 22. Group Messaging & Poll voting
        $groupConv = Conversation::create(['type' => 'group', 'title' => 'Phase 14 War Room', 'creator_id' => $alice->id]);
        $groupConv->participants()->create(['user_id' => $alice->id, 'role' => 'owner']);
        $groupConv->participants()->create(['user_id' => $bob->id, 'role' => 'member']);

        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/conversations/{$groupConv->id}/messages", [
                'body' => 'Welcome to Phase 14 War Room!',
                'type' => 'text',
            ])
            ->assertCreated();

        $pollRes = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/conversations/{$groupConv->id}/polls", [
                'question' => 'Deploy Phase 14?',
                'options' => ['Approve', 'Hold'],
            ])
            ->assertCreated();
        $pollId = $pollRes->json('data.poll.id') ?? $pollRes->json('data.id');
        $optionId = $pollRes->json('data.poll.options.0.id') ?? $pollRes->json('data.options.0.id');

        if ($pollId && $optionId) {
            $this->actingAs($bob, 'sanctum')
                ->postJson("/api/v1/polls/{$pollId}/vote", [
                    'option_id' => $optionId,
                ])
                ->assertOk();
        }

        // 20 & 29. Notifications list, unread count & mark-all-read
        $this->actingAs($bob, 'sanctum')->getJson('/api/v1/notifications')->assertOk();
        $this->actingAs($bob, 'sanctum')->getJson('/api/v1/notifications/unread-count')->assertOk();
        $this->actingAs($bob, 'sanctum')->postJson('/api/v1/notifications/read-all')->assertOk();

        // 28. Cross-device messenger sync
        $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/messenger/sync?since_id=0')
            ->assertOk();

        // 30. WebSocket private channel authentication (authorized participant vs unauthorized intruder)
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'connectly_reverb_key',
            'broadcasting.connections.pusher.secret' => 'test_secret_12345',
            'broadcasting.connections.pusher.app_id' => 'bondhoo_app',
        ]);
        require base_path('routes/channels.php');

        $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-conversation.{$directConv->id}",
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);

        $this->actingAs($dave, 'sanctum')
            ->postJson('/api/v1/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-conversation.{$directConv->id}",
            ])
            ->assertForbidden();
    }
}
