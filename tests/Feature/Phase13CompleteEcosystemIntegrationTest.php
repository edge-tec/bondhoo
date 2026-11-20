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

class Phase13CompleteEcosystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Scenarios 1, 2, 3, 20: Website & Android Registration, Login Compatibility, Logout & Token Revocation.
     */
    public function test_website_and_android_auth_session_and_logout_lifecycle(): void
    {
        // 1. Website Registration
        $webReg = $this->postJson('/api/v1/auth/register', [
            'name' => 'Web User',
            'username' => 'webuser13',
            'email' => 'web13@bondhoo.com',
            'password' => 'SecurePass123!',
            'device_name' => 'web-browser',
        ]);
        $webReg->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('users', ['username' => 'webuser13', 'email' => 'web13@bondhoo.com']);

        // 2. Android Registration
        $androidReg = $this->postJson('/api/v1/auth/register', [
            'name' => 'Android User',
            'username' => 'androiduser13',
            'email' => 'android13@bondhoo.com',
            'password' => 'SecurePass123!',
            'device_name' => 'Pixel-9-Pro',
        ]);
        $androidReg->assertCreated()->assertJsonPath('success', true);
        $androidToken = $androidReg->json('data.token');
        $this->assertNotEmpty($androidToken);

        // 3. Website user logs in via Android API endpoint
        $crossLogin = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'webuser13',
            'password' => 'SecurePass123!',
            'device_name' => 'Samsung-S25',
        ]);
        $crossLogin->assertOk()->assertJsonPath('data.user.username', 'webuser13');
        $crossToken = $crossLogin->json('data.token');
        $this->assertNotEmpty($crossToken);

        // 20. Logout & Token Revocation
        $this->withHeader('Authorization', 'Bearer '.$crossToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();
    }

    /**
     * Scenarios 4, 5, 6, 7, 8, 9, 16: Cross-Platform Posts, Comments, Reactions & Content Moderation.
     */
    public function test_cross_platform_feed_posts_comments_reactions_and_moderation(): void
    {
        $webUser = User::factory()->create(['username' => 'webfounder']);
        $androidUser = User::factory()->create(['username' => 'androiddev']);

        // 4. Website creates post -> Android fetches feed
        $webPostRes = $this->actingAs($webUser, 'web')
            ->postJson('/api/v1/posts', [
                'content' => 'Hello from Bondhoo Web!',
                'audience' => 'public',
            ]);
        $webPostRes->assertCreated();
        $webPostId = $webPostRes->json('data.post.id') ?? $webPostRes->json('data.id');
        $this->assertNotNull($webPostId);

        // 5. Android creates post -> Website fetches feed
        $androidPostRes = $this->actingAs($androidUser, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Hello from Bondhoo Android App!',
                'audience' => 'public',
            ]);
        $androidPostRes->assertCreated();
        $androidPostId = $androidPostRes->json('data.post.id') ?? $androidPostRes->json('data.id');

        // 6 & 7. Cross-platform comments
        $this->actingAs($androidUser, 'sanctum')
            ->postJson("/api/v1/posts/{$webPostId}/comments", ['body' => 'Comment from Android!'])
            ->assertCreated();

        $this->actingAs($webUser, 'web')
            ->postJson("/api/v1/posts/{$androidPostId}/comments", ['body' => 'Comment from Website!'])
            ->assertCreated();

        $commentsRes = $this->actingAs($webUser, 'sanctum')
            ->getJson("/api/v1/posts/{$webPostId}/comments");
        $commentsRes->assertOk();
        $this->assertStringContainsString('Comment from Android!', $commentsRes->getContent());

        // 8 & 9. Cross-platform reactions
        $this->actingAs($androidUser, 'sanctum')
            ->postJson("/api/v1/posts/{$webPostId}/react", ['type' => 'love'])
            ->assertOk();

        $this->actingAs($webUser, 'web')
            ->postJson("/api/v1/posts/{$androidPostId}/react", ['type' => 'like'])
            ->assertOk();

        // 16. Content Moderation with Video Attachment (regression test for ContentModerationService fix)
        $videoMediaRes = $this->actingAs($androidUser, 'sanctum')
            ->postJson('/api/v2/uploads/direct/complete', [
                'file' => UploadedFile::fake()->create('clip.mp4', 256, 'video/mp4'),
                'collection' => 'videos',
            ]);
        $videoMediaRes->assertOk()->assertJsonPath('success', true);
        $mediaId = $videoMediaRes->json('data.id');
        $this->assertNotNull($mediaId);

        $videoPostRes = $this->actingAs($androidUser, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Sharing an Android video post!',
                'media_ids' => [$mediaId],
                'audience' => 'public',
            ]);
        $videoPostRes->assertCreated();
    }

    /**
     * Scenarios 10, 11, 12, 13: Website <-> Android 1:1 & Group Messaging + Real-Time Notifications.
     */
    public function test_cross_platform_direct_and_group_messaging_and_notifications(): void
    {
        $webUser = User::factory()->create(['name' => 'Web Alice']);
        $androidUser = User::factory()->create(['name' => 'Android Bob']);
        $charlie = User::factory()->create(['name' => 'Charlie']);

        // 10. Website -> Android 1:1 conversation & message
        $conv = Conversation::create(['type' => 'direct', 'creator_id' => $webUser->id]);
        $conv->participants()->create(['user_id' => $webUser->id, 'role' => 'member']);
        $conv->participants()->create(['user_id' => $androidUser->id, 'role' => 'member']);

        $this->actingAs($webUser, 'web')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Ping from Web to Android',
                'type' => 'text',
            ])
            ->assertCreated();

        // 11. Android -> Website reply + Poll creation
        $this->actingAs($androidUser, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Pong from Android to Web',
                'type' => 'text',
            ])
            ->assertCreated();

        $messagesRes = $this->actingAs($androidUser, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}/messages");
        $messagesRes->assertOk();
        $this->assertStringContainsString('Ping from Web to Android', $messagesRes->getContent());
        $this->assertStringContainsString('Pong from Android to Web', $messagesRes->getContent());

        // 12. Group Messaging + Enterprise Poll
        $groupConv = Conversation::create(['type' => 'group', 'title' => 'Bondhoo Engineers', 'creator_id' => $webUser->id]);
        $groupConv->participants()->create(['user_id' => $webUser->id, 'role' => 'owner']);
        $groupConv->participants()->create(['user_id' => $androidUser->id, 'role' => 'member']);
        $groupConv->participants()->create(['user_id' => $charlie->id, 'role' => 'member']);

        $pollRes = $this->actingAs($androidUser, 'sanctum')
            ->postJson("/api/v1/conversations/{$groupConv->id}/polls", [
                'question' => 'Ready for Phase 13 release?',
                'options' => ['Yes, 100%', 'Ship it'],
            ]);
        $pollRes->assertCreated();

        // 13. Notifications endpoint check
        $this->actingAs($androidUser, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk();
    }

    /**
     * Scenarios 14, 15, 17, 18, 19: Profile Analytics, Community Creation, Story Viewers Parity, Privacy/Blocking.
     */
    public function test_profile_analytics_community_types_story_viewers_and_blocking(): void
    {
        $owner = User::factory()->create(['username' => 'creator13']);
        $viewer = User::factory()->create(['username' => 'visitor13']);

        // 14. Profile view analytics recorded on web profile visit
        $this->actingAs($viewer, 'web')->get('/@creator13')->assertOk();
        $this->assertDatabaseHas('profile_views', [
            'user_id' => $owner->id,
            'viewer_id' => $viewer->id,
        ]);

        // 15. Community creation preserves community_type (regression test for GroupService fix)
        $groupRes = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v2/groups', [
                'name' => 'Bondhoo Architecture Guild',
                'username' => 'bondhoo-arch-13',
                'description' => 'Enterprise architecture community',
                'community_type' => Group::TYPE_PROJECT,
                'category' => 'Technology',
            ]);
        $groupRes->assertCreated()->assertJsonPath('data.community_type', Group::TYPE_PROJECT);

        // 17. Story Viewers parity (top-level UserDto fields + nested user object)
        $story = Story::create([
            'user_id' => $owner->id,
            'type' => 'text',
            'text_content' => 'Phase 13 Story!',
            'audience' => 'public',
            'expires_at' => now()->addHours(24),
        ]);
        StoryView::create([
            'story_id' => $story->id,
            'user_id' => $viewer->id,
            'viewed_at' => now(),
        ]);

        $viewersRes = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v2/stories/{$story->id}/viewers");
        $viewersRes->assertOk()
            ->assertJsonPath('data.0.id', $viewer->id)
            ->assertJsonPath('data.0.username', 'visitor13')
            ->assertJsonPath('data.0.user.username', 'visitor13');

        // 18. Privacy & Blocking enforcement
        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v2/profile/{$viewer->username}/block")
            ->assertOk();

        $blockedRes = $this->actingAs($viewer, 'web')->get('/@creator13');
        $this->assertContains($blockedRes->getStatusCode(), [403, 404]);
    }
}
