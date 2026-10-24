<?php

namespace Tests\Feature;

use App\Events\PageCommentUpdatedEvent;
use App\Events\PageFollowerUpdatedEvent;
use App\Events\PageInvitationEvent;
use App\Events\PageLiveStreamStateChangedEvent;
use App\Events\PageNotificationEvent;
use App\Events\PagePostDeletedEvent;
use App\Events\PagePostPublishedEvent;
use App\Events\PagePostUpdatedEvent;
use App\Events\PageProfileUpdatedEvent;
use App\Events\PageReelPublishedEvent;
use App\Events\PageSettingsUpdatedEvent;
use App\Events\PageStoryPublishedEvent;
use App\Events\PageTeamMemberUpdatedEvent;
use App\Events\PostReactionUpdatedEvent;
use App\Models\Comment;
use App\Models\LiveStream;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageConversation;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\PageType;
use App\Models\Post;
use App\Models\Reel;
use App\Models\Story;
use App\Models\User;
use App\Services\PageService;
use App\Services\Streaming\LiveGatewayService;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Pusher\Pusher;
use Tests\TestCase;

/**
 * PART 11 — Production Real-Time Infrastructure & Data Consistency Test Suite
 */
class PageRealtimeInfrastructurePart11Test extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $adminMember;

    protected User $editorMember;

    protected User $visitor;

    protected User $otherVisitor;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $pusher = Mockery::mock(Pusher::class);
        $pusher->shouldReceive('authorizeChannel')->andReturn(json_encode(['auth' => 'test-auth-signature']));
        $broadcaster = new PusherBroadcaster($pusher);
        Broadcast::extend('mock_pusher', fn () => $broadcaster);
        config([
            'broadcasting.connections.mock_pusher' => ['driver' => 'mock_pusher'],
            'broadcasting.default' => 'mock_pusher',
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        $this->seed(PageTaxonomySeeder::class);

        $type = PageType::first() ?? PageType::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);
        $category = PageCategory::first() ?? PageCategory::create(['page_type_id' => $type->id, 'name' => 'Technology', 'slug' => 'tech', 'is_active' => true]);

        $this->owner = User::factory()->create(['name' => 'Page Creator', 'username' => 'pagecreator']);
        $this->adminMember = User::factory()->create(['name' => 'Team Admin', 'username' => 'teamadmin']);
        $this->editorMember = User::factory()->create(['name' => 'Team Editor', 'username' => 'teameditor']);
        $this->visitor = User::factory()->create(['name' => 'Regular User', 'username' => 'regularuser']);
        $this->otherVisitor = User::factory()->create(['name' => 'Second User', 'username' => 'seconduser']);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'page_type_id' => $type->id,
            'page_category_id' => $category->id,
            'name' => 'Apex Cloud Inc',
            'slug' => 'apex-cloud',
            'username' => 'apexcloud',
            'category' => 'Technology',
            'followers_count' => 0,
            'posts_count' => 0,
            'is_verified' => true,
            'status' => Page::STATUS_ACTIVE,
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'role' => Page::ROLE_OWNER,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->adminMember->id,
            'role' => Page::ROLE_ADMIN,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->editorMember->id,
            'role' => Page::ROLE_EDITOR,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    /**
     * 1. Test Private Channel Authorization: Verifies managers receive access and unauthorized visitors are 403 forbidden.
     */
    public function test_private_channel_authorization_isolates_audiences_correctly(): void
    {
        // Owner authorizes page admin channel
        $respOwnerAdmin = $this->actingAs($this->owner)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-page.'.$this->page->id.'.admin',
            'socket_id' => '1234.5678',
        ]);
        $respOwnerAdmin->assertStatus(200);

        // Admin member authorizes page admin channel
        $respAdminAdmin = $this->actingAs($this->adminMember)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-page.'.$this->page->id.'.admin',
            'socket_id' => '1234.5678',
        ]);
        $respAdminAdmin->assertStatus(200);

        // Regular visitor trying to access page admin channel is 403 Forbidden
        $respVisitorAdmin = $this->actingAs($this->visitor)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-page.'.$this->page->id.'.admin',
            'socket_id' => '1234.5678',
        ]);
        $respVisitorAdmin->assertStatus(403);

        // Settings channel access
        $respOwnerSettings = $this->actingAs($this->owner)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-page.'.$this->page->id.'.settings',
            'socket_id' => '1234.5678',
        ]);
        $respOwnerSettings->assertStatus(200);

        $respVisitorSettings = $this->actingAs($this->visitor)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-page.'.$this->page->id.'.settings',
            'socket_id' => '1234.5678',
        ]);
        $respVisitorSettings->assertStatus(403);

        // User personal notification channel isolation
        $respUserOwn = $this->actingAs($this->visitor)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.'.$this->visitor->id,
            'socket_id' => '1234.5678',
        ]);
        $respUserOwn->assertStatus(200);

        $respUserOther = $this->actingAs($this->otherVisitor)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.'.$this->visitor->id,
            'socket_id' => '1234.5678',
        ]);
        $respUserOther->assertStatus(403);
    }

    /**
     * 2. Test Page Conversation Channel Authorization for Inbox & Participant.
     */
    public function test_conversation_channel_authorization_isolates_messages(): void
    {
        $conversation = PageConversation::create([
            'page_id' => $this->page->id,
            'user_id' => $this->visitor->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        // Participant user has access
        $respParticipant = $this->actingAs($this->visitor)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-page.'.$this->page->id.'.conversation.'.$conversation->id,
            'socket_id' => '1234.5678',
        ]);
        $respParticipant->assertStatus(200);

        // Page owner has access
        $respOwner = $this->actingAs($this->owner)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-page.'.$this->page->id.'.conversation.'.$conversation->id,
            'socket_id' => '1234.5678',
        ]);
        $respOwner->assertStatus(200);

        // Other visitor has no access
        $respOther = $this->actingAs($this->otherVisitor)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-page.'.$this->page->id.'.conversation.'.$conversation->id,
            'socket_id' => '1234.5678',
        ]);
        $respOther->assertStatus(403);
    }

    /**
     * 3. Test Profile and Settings Updates Real-Time Event Broadcasts.
     */
    public function test_profile_and_settings_updates_broadcast_to_correct_channels(): void
    {
        Event::fake([PageProfileUpdatedEvent::class, PageSettingsUpdatedEvent::class]);

        // Profile update broadcasts public PageProfileUpdatedEvent
        $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/details", [
            'name' => 'Apex Cloud Enterprise',
            'category' => 'Technology',
            'bio' => 'Next generation cloud architecture',
        ])->assertStatus(200);

        Event::assertDispatched(PageProfileUpdatedEvent::class, function (PageProfileUpdatedEvent $event) {
            $channels = $event->broadcastOn();

            return (int) $event->pageId === (int) $this->page->id
                && $channels[0]->name === "page.{$this->page->id}"
                && $event->broadcastAs() === 'page.profile.updated'
                && ! empty($event->eventId)
                && $event->afterCommit === true;
        });

        // Settings update broadcasts private PageSettingsUpdatedEvent
        $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/settings", [
            'settings' => [
                'moderation' => [
                    'allow_comments' => true,
                    'blocked_keywords' => ['spam', 'phishing'],
                ],
            ],
        ])->assertStatus(200);

        Event::assertDispatched(PageSettingsUpdatedEvent::class, function (PageSettingsUpdatedEvent $event) {
            $channels = $event->broadcastOn();

            return (int) $event->pageId === (int) $this->page->id
                && $channels[0]->name === "private-page.{$this->page->id}.admin"
                && $event->broadcastAs() === 'page.settings.updated'
                && ! empty($event->eventId)
                && $event->afterCommit === true;
        });
    }

    /**
     * 4. Test Follow and Unfollow Real-Time Event Broadcasts.
     */
    public function test_follow_and_unfollow_changes_broadcast_in_realtime(): void
    {
        Event::fake([PageFollowerUpdatedEvent::class, PageNotificationEvent::class]);

        $pageService = app(PageService::class);

        // Follow
        $resultFollow = $pageService->toggleFollow($this->page, $this->visitor);
        $this->assertTrue($resultFollow['is_following']);
        $this->assertEquals(1, $resultFollow['followers_count']);

        Event::assertDispatched(PageFollowerUpdatedEvent::class, function (PageFollowerUpdatedEvent $event) {
            return (int) $event->pageId === (int) $this->page->id
                && $event->followersCount === 1
                && $event->action === 'followed'
                && (int) $event->actorId === (int) $this->visitor->id;
        });

        Event::assertDispatched(PageNotificationEvent::class, function (PageNotificationEvent $event) {
            return (int) $event->recipientUserId === (int) $this->owner->id
                && (int) $event->pageId === (int) $this->page->id;
        });

        // Unfollow
        $resultUnfollow = $pageService->toggleFollow($this->page, $this->visitor);
        $this->assertFalse($resultUnfollow['is_following']);
        $this->assertEquals(0, $resultUnfollow['followers_count']);

        Event::assertDispatched(PageFollowerUpdatedEvent::class, function (PageFollowerUpdatedEvent $event) {
            return (int) $event->pageId === (int) $this->page->id
                && $event->followersCount === 0
                && $event->action === 'unfollowed';
        });
    }

    /**
     * 5. Test Published Posts, Updates, Deletions and Pinning Broadcasts.
     */
    public function test_posts_publishing_updates_and_deletions_broadcast_in_realtime(): void
    {
        Event::fake([PagePostPublishedEvent::class, PagePostUpdatedEvent::class, PagePostDeletedEvent::class]);

        // Publish new post
        $createResp = $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/posts", [
            'content' => 'Announcing our real-time synchronization engine!',
            'status' => 'published',
            'audience' => 'public',
        ]);
        $createResp->assertStatus(200);
        $postId = $createResp->json('post.id');

        Event::assertDispatched(PagePostPublishedEvent::class, function (PagePostPublishedEvent $event) {
            return (int) $event->pageId === (int) $this->page->id
                && $event->broadcastOn()[0]->name === "page.{$this->page->id}.feed"
                && $event->broadcastAs() === 'page.post.published';
        });

        // Update post
        $this->actingAs($this->owner)->putJson("/pages/{$this->page->slug}/posts/{$postId}", [
            'content' => 'Updated content with enhanced performance details!',
        ])->assertStatus(200);

        Event::assertDispatched(PagePostUpdatedEvent::class, function (PagePostUpdatedEvent $event) use ($postId) {
            return (int) $event->pageId === (int) $this->page->id
                && (int) $event->postId === (int) $postId
                && $event->action === 'updated';
        });

        // Toggle pin
        $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/posts/{$postId}/pin")->assertStatus(200);

        Event::assertDispatched(PagePostUpdatedEvent::class, function (PagePostUpdatedEvent $event) use ($postId) {
            return (int) $event->postId === (int) $postId
                && $event->action === 'pinned';
        });

        // Delete post
        $this->actingAs($this->owner)->deleteJson("/pages/{$this->page->slug}/posts/{$postId}")->assertStatus(200);

        Event::assertDispatched(PagePostDeletedEvent::class, function (PagePostDeletedEvent $event) use ($postId) {
            return (int) $event->pageId === (int) $this->page->id
                && (int) $event->postId === (int) $postId;
        });
    }

    /**
     * 6. Test Comments and Reactions Real-Time Event Broadcasts.
     */
    public function test_comments_and_reactions_broadcast_in_realtime_on_post_channel(): void
    {
        Event::fake([PageCommentUpdatedEvent::class, PostReactionUpdatedEvent::class]);

        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Engage with our newest release!',
            'status' => 'published',
            'audience' => 'public',
        ]);

        // Add comment
        $commentResp = $this->actingAs($this->visitor)->postJson("/pages/posts/{$post->id}/comments", [
            'body' => 'Incredible update, congrats team!',
        ]);
        $commentResp->assertStatus(201);
        $commentId = $commentResp->json('comment.id');

        Event::assertDispatched(PageCommentUpdatedEvent::class, function (PageCommentUpdatedEvent $event) use ($post, $commentId) {
            return (int) $event->postId === (int) $post->id
                && (int) $event->commentId === (int) $commentId
                && $event->action === 'created'
                && $event->broadcastOn()[0]->name === "post.{$post->id}";
        });

        // Update comment
        $this->actingAs($this->visitor)->putJson("/pages/comments/{$commentId}", [
            'body' => 'Incredible update, congratulations team on this feat!',
        ])->assertStatus(200);

        Event::assertDispatched(PageCommentUpdatedEvent::class, function (PageCommentUpdatedEvent $event) use ($post, $commentId) {
            return (int) $event->postId === (int) $post->id
                && (int) $event->commentId === (int) $commentId
                && $event->action === 'updated';
        });

        // Delete comment
        $this->actingAs($this->visitor)->deleteJson("/pages/comments/{$commentId}")->assertStatus(200);

        Event::assertDispatched(PageCommentUpdatedEvent::class, function (PageCommentUpdatedEvent $event) use ($post, $commentId) {
            return (int) $event->postId === (int) $post->id
                && (int) $event->commentId === (int) $commentId
                && $event->action === 'deleted';
        });

        // React to post
        $this->actingAs($this->visitor)->postJson("/pages/posts/{$post->id}/react", [
            'type' => 'love',
        ])->assertStatus(200);

        Event::assertDispatched(PostReactionUpdatedEvent::class, function (PostReactionUpdatedEvent $event) use ($post) {
            return (int) $event->postId === (int) $post->id
                && $event->totalReactions >= 1
                && $event->broadcastOn()[0]->name === "post.{$post->id}";
        });
    }

    /**
     * 7. Test Story and Reel Publication Broadcasts.
     */
    public function test_story_and_reel_publication_broadcast_in_realtime(): void
    {
        Event::fake([PageStoryPublishedEvent::class, PageReelPublishedEvent::class]);

        // Publish text story
        $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/stories", [
            'type' => 'text',
            'content' => 'Exciting news coming in 2 hours!',
            'background_color' => '#6366f1',
        ])->assertStatus(201);

        Event::assertDispatched(PageStoryPublishedEvent::class, function (PageStoryPublishedEvent $event) {
            $channels = $event->broadcastOn();

            return (int) $event->pageId === (int) $this->page->id
                && $channels[0]->name === "page.{$this->page->id}"
                && $channels[1]->name === 'stories';
        });

        // Publish reel
        $videoFile = UploadedFile::fake()->create('teaser.mp4', 1024, 'video/mp4');

        $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/reels", [
            'video' => $videoFile,
            'caption' => 'A sneak peek into our infrastructure',
            'is_draft' => false,
        ])->assertStatus(201);

        Event::assertDispatched(PageReelPublishedEvent::class, function (PageReelPublishedEvent $event) {
            $channels = $event->broadcastOn();

            return (int) $event->pageId === (int) $this->page->id
                && $channels[0]->name === "page.{$this->page->id}"
                && $channels[1]->name === 'reels';
        });
    }

    /**
     * 8. Test Live Broadcast State Changes.
     */
    public function test_live_broadcast_state_changes_broadcast_in_realtime(): void
    {
        Event::fake([PageLiveStreamStateChangedEvent::class]);

        $stream = LiveStream::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'title' => 'Live Developer Q&A',
            'channel_id' => (string) Str::uuid(),
            'stream_key' => 'live-key-'.Str::random(16),
            'ingest_url' => 'rtmp://stream.example.com/live',
            'playback_url' => 'https://stream.example.com/live/playback.m3u8',
            'status' => LiveStream::STATUS_READY,
            'sfu_host_token' => 'mock-host-token-1234',
        ]);

        $hostToken = app(LiveGatewayService::class)->generateHostToken($stream, $this->owner);

        // Publish live stream
        $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/live/{$stream->id}/publish", [
            'host_token' => $hostToken,
        ])->assertStatus(200);

        Event::assertDispatched(PageLiveStreamStateChangedEvent::class, function (PageLiveStreamStateChangedEvent $event) use ($stream) {
            return (int) $event->pageId === (int) $this->page->id
                && (int) $event->streamId === (int) $stream->id
                && $event->status === LiveStream::STATUS_LIVE;
        });

        // End live stream
        $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/live/{$stream->id}/end")->assertStatus(200);

        Event::assertDispatched(PageLiveStreamStateChangedEvent::class, function (PageLiveStreamStateChangedEvent $event) use ($stream) {
            return (int) $event->pageId === (int) $this->page->id
                && (int) $event->streamId === (int) $stream->id
                && $event->status === LiveStream::STATUS_ENDED;
        });
    }

    /**
     * 9. Test Page Invitations Broadcast to Proper Private Audience.
     */
    public function test_page_invitations_broadcast_to_correct_private_audience(): void
    {
        Event::fake([PageInvitationEvent::class, PageFollowerUpdatedEvent::class]);

        // Send invitation
        $inviteResp = $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/invitations", [
            'invitee_id' => $this->visitor->id,
        ]);
        $inviteResp->assertStatus(201);
        $invitationId = $inviteResp->json('data.0.id');

        Event::assertDispatched(PageInvitationEvent::class, function (PageInvitationEvent $event) {
            $channels = $event->broadcastOn();

            return (int) $event->pageId === (int) $this->page->id
                && (int) $event->inviteeId === (int) $this->visitor->id
                && $event->status === 'sent'
                && $channels[0]->name === "private-user.{$this->visitor->id}"
                && $channels[1]->name === "private-page.{$this->page->id}.admin";
        });

        // Accept invitation
        $this->actingAs($this->visitor)->postJson("/invitations/pages/{$invitationId}/accept")->assertStatus(200);

        Event::assertDispatched(PageInvitationEvent::class, function (PageInvitationEvent $event) use ($invitationId) {
            return (int) $event->invitationId === (int) $invitationId
                && $event->status === 'accepted';
        });

        Event::assertDispatched(PageFollowerUpdatedEvent::class, function (PageFollowerUpdatedEvent $event) {
            return (int) $event->pageId === (int) $this->page->id
                && $event->action === 'followed'
                && (int) $event->actorId === (int) $this->visitor->id;
        });
    }

    /**
     * 10. Test Authorized Team and Role Changes Broadcast.
     */
    public function test_authorized_team_and_role_changes_broadcast_in_realtime(): void
    {
        Event::fake([PageTeamMemberUpdatedEvent::class]);

        $newCandidate = User::factory()->create(['name' => 'Support Manager', 'username' => 'supportmgr', 'email' => 'supportmgr@example.com']);

        // Invite team member
        $inviteResp = $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/team/invite", [
            'identifier' => 'supportmgr@example.com',
            'role' => Page::ROLE_MODERATOR,
        ]);
        $inviteResp->assertStatus(200);
        $memberId = $inviteResp->json('member.id');

        Event::assertDispatched(PageTeamMemberUpdatedEvent::class, function (PageTeamMemberUpdatedEvent $event) use ($newCandidate) {
            $channels = $event->broadcastOn();

            return (int) $event->pageId === (int) $this->page->id
                && (int) $event->userId === (int) $newCandidate->id
                && $event->role === Page::ROLE_MODERATOR
                && $event->action === 'invited'
                && $channels[0]->name === "private-page.{$this->page->id}.admin"
                && $channels[1]->name === "private-user.{$newCandidate->id}";
        });

        // Update team member role
        $this->actingAs($this->owner)->putJson("/pages/{$this->page->slug}/team/{$memberId}", [
            'role' => Page::ROLE_MANAGER,
        ])->assertStatus(200);

        Event::assertDispatched(PageTeamMemberUpdatedEvent::class, function (PageTeamMemberUpdatedEvent $event) use ($newCandidate) {
            return (int) $event->pageId === (int) $this->page->id
                && (int) $event->userId === (int) $newCandidate->id
                && $event->role === Page::ROLE_MANAGER
                && $event->action === 'role_changed';
        });

        // Remove team member
        $this->actingAs($this->owner)->deleteJson("/pages/{$this->page->slug}/team/{$memberId}")->assertStatus(200);

        Event::assertDispatched(PageTeamMemberUpdatedEvent::class, function (PageTeamMemberUpdatedEvent $event) use ($newCandidate) {
            return (int) $event->pageId === (int) $this->page->id
                && (int) $event->userId === (int) $newCandidate->id
                && $event->action === 'removed';
        });
    }

    /**
     * 11. Test Reconnection & Resynchronization Authoritative Delta Endpoint.
     */
    public function test_reconnection_and_resynchronization_returns_authoritative_state(): void
    {
        // Setup initial post and follow
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->visitor->id,
            'created_at' => now(),
        ]);
        $this->page->increment('followers_count');

        $activePost = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Fresh broadcast post',
            'status' => 'published',
            'audience' => 'public',
        ]);

        $deletedPost = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Old post deleted during disconnect',
            'status' => 'published',
            'audience' => 'public',
        ]);
        $deletedPost->delete();

        // Client reconnects asking for updates since 30 minutes ago
        $since = now()->subMinutes(30)->toIso8601String();

        // As visitor (public viewer)
        $respVisitor = $this->actingAs($this->visitor)->getJson("/pages/{$this->page->slug}/realtime/sync?since={$since}");
        $respVisitor->assertStatus(200);
        $respVisitor->assertJsonPath('authoritative_state.page.followers_count', 1);
        $respVisitor->assertJsonPath('authoritative_state.viewer.is_following', true);
        $respVisitor->assertJsonPath('authoritative_state.viewer.can_manage', false);
        $respVisitor->assertJsonPath('authoritative_state.manager_state', null); // Sensitive management data hidden from non-managers!

        // Deleted post id present in delta so client can purge local feed cache
        $this->assertContains($deletedPost->id, $respVisitor->json('authoritative_state.deleted_post_ids'));

        // As owner (manager viewer)
        $respManager = $this->actingAs($this->owner)->getJson("/pages/{$this->page->slug}/realtime/sync?since={$since}");
        $respManager->assertStatus(200);
        $respManager->assertJsonPath('authoritative_state.viewer.can_manage', true);
        $respManager->assertJsonStructure([
            'authoritative_state' => [
                'page' => ['id', 'name', 'slug', 'followers_count', 'updated_at'],
                'viewer' => ['is_authenticated', 'role', 'can_manage'],
                'posts_delta',
                'deleted_post_ids',
                'manager_state' => ['unread_conversations_count', 'pending_invitations_count'],
                'synced_at',
            ],
        ]);
    }

    /**
     * 12. Test Reliability, Idempotency Envelope, and Commit Safety.
     */
    public function test_event_reliability_attributes_and_commit_safety(): void
    {
        $event = new PageProfileUpdatedEvent(
            pageId: $this->page->id,
            profileData: ['name' => 'Updated Name', 'slug' => 'apex-cloud'],
            actorId: $this->owner->id
        );

        // Verify idempotency UUID format
        $this->assertTrue(Str::isUuid($event->eventId));

        // Verify reliability attributes for queue worker
        $this->assertTrue($event->afterCommit);
        $this->assertEquals(3, $event->tries);
        $this->assertEquals([2, 5, 10], $event->backoff);

        $payload = $event->broadcastWith();
        $this->assertEquals($event->eventId, $payload['event_id']);
        $this->assertArrayHasKey('timestamp', $payload);
        $this->assertArrayHasKey('version', $payload);

        // Transactional commit safety: Rollback in transaction does not fire committed events
        Event::fake([PageFollowerUpdatedEvent::class]);

        try {
            DB::transaction(function () {
                $this->page->increment('followers_count');
                DB::afterCommit(function () {
                    broadcast(new PageFollowerUpdatedEvent((int) $this->page->id, 999, 'followed', $this->visitor->id));
                });
                throw new \RuntimeException('Intentional rollback');
            });
        } catch (\RuntimeException $e) {
            // Expected rollback
        }

        Event::assertNotDispatched(PageFollowerUpdatedEvent::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
