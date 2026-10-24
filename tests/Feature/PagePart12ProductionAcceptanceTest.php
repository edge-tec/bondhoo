<?php

namespace Tests\Feature;

use App\Events\PagePostPublishedEvent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\LiveStream;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\PageType;
use App\Models\Post;
use App\Models\Reel;
use App\Models\Story;
use App\Models\User;
use Database\Seeders\PageTaxonomySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Pusher\Pusher;
use Tests\TestCase;

/**
 * PART 12 — Final Security, Regression, Performance & Production Acceptance Test Suite
 *
 * Verifies all 12 End-to-End Scenarios and acceptance criteria for SocialHub Page Section:
 * 1. Page creation and profile editing.
 * 2. Visitor follow and unfollow with exact count updates.
 * 3. Permitted member publishes a post.
 * 4. Real-time post delivery and channel authorization.
 * 5. Reactions, comments, and shares with verified counts.
 * 6. Story publication, 24-hour expiration, and retention policy.
 * 7. Reel publishing and playback access.
 * 8. Live broadcast start and end cycle with scoped credentials.
 * 9. RBAC permission boundaries and privilege escalation denials.
 * 10. Page settings update persistence across reloads.
 * 11. Insights and analytics accuracy against underlying test data.
 * 12. Regression verification of user profiles, Messenger, groups, marketplace, and auth.
 */
class PagePart12ProductionAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $pageOwner;

    protected User $pageAdmin;

    protected User $pageEditor;

    protected User $visitor;

    protected User $stranger;

    protected PageType $pageType;

    protected PageCategory $pageCategory;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(RolePermissionSeeder::class);
        $this->seed(PageTaxonomySeeder::class);

        // Configure mock pusher for real-time channel testing
        $pusher = Mockery::mock(Pusher::class);
        $pusher->shouldReceive('authorizeChannel')->andReturn(json_encode(['auth' => 'mock-auth-sig']));
        $pusher->shouldReceive('trigger')->andReturn((object) ['status' => 200]);
        $broadcaster = new PusherBroadcaster($pusher);
        Broadcast::extend('mock_pusher', fn () => $broadcaster);
        config([
            'broadcasting.connections.mock_pusher' => ['driver' => 'mock_pusher'],
            'broadcasting.default' => 'mock_pusher',
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        $this->pageType = PageType::first() ?? PageType::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);
        $this->pageCategory = PageCategory::first() ?? PageCategory::create(['page_type_id' => $this->pageType->id, 'name' => 'Software', 'slug' => 'software', 'is_active' => true]);

        $this->pageOwner = User::factory()->create([
            'name' => 'Bondhoo Page Owner',
            'username' => 'pageowner',
            'email' => 'owner@bondhoo.example.com',
        ]);

        $this->pageAdmin = User::factory()->create([
            'name' => 'Page Administrator',
            'username' => 'pageadmin',
            'email' => 'admin@bondhoo.example.com',
        ]);

        $this->pageEditor = User::factory()->create([
            'name' => 'Page Editor',
            'username' => 'pageeditor',
            'email' => 'editor@bondhoo.example.com',
        ]);

        $this->visitor = User::factory()->create([
            'name' => 'Active Visitor',
            'username' => 'activevisitor',
            'email' => 'visitor@bondhoo.example.com',
        ]);

        $this->stranger = User::factory()->create([
            'name' => 'Restricted Stranger',
            'username' => 'stranger',
            'email' => 'stranger@bondhoo.example.com',
        ]);
    }

    /**
     * Scenario 1: A user creates a Page and edits its profile.
     */
    public function test_scenario_01_user_creates_page_and_edits_profile(): void
    {
        // 1. Create Page
        $createResponse = $this->actingAs($this->pageOwner)->post(route('pages.store'), [
            'name' => 'Apex Cloud Solutions',
            'page_type_id' => $this->pageType->id,
            'page_category_id' => $this->pageCategory->id,
            'category' => $this->pageCategory->name,
            'bio' => 'Enterprise grade cloud infrastructure for modern apps.',
            'description' => 'Detailed technical profile of Apex Cloud Solutions Bangladesh.',
            'email' => 'contact@apexcloud.example.com',
            'phone' => '+8801700112233',
            'website' => 'https://apexcloud.example.com',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
        ]);

        $createResponse->assertRedirect();

        $page = Page::where('name', 'Apex Cloud Solutions')->first();
        $this->assertNotNull($page);
        $this->assertEquals($this->pageOwner->id, $page->owner_id);
        $this->assertContains($page->status, ['active', 'published']);

        // 2. Edit Profile Details
        $updateResponse = $this->actingAs($this->pageOwner)
            ->putJson(route('pages.updateDetails', ['slug' => $page->slug]), [
                'name' => 'Apex Cloud Solutions Global',
                'category' => 'Technology',
                'bio' => 'Global scale enterprise infrastructure.',
                'description' => 'Updated high performance cloud platform.',
                'email' => 'support@apexcloud.example.com',
                'phone' => '+8801700998877',
                'website' => 'https://global.apexcloud.example.com',
                'city' => 'Chittagong',
                'country' => 'Bangladesh',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('success', true);

        $page->refresh();
        $this->assertEquals('Apex Cloud Solutions Global', $page->name);
        $this->assertEquals('Global scale enterprise infrastructure.', $page->bio);
        $this->assertEquals('support@apexcloud.example.com', $page->email);
        $this->assertEquals('Chittagong', $page->city);

        // 3. Upload Branding (Avatar)
        $avatarFile = UploadedFile::fake()->image('brand_avatar.jpg', 400, 400);
        $brandingResponse = $this->actingAs($this->pageOwner)
            ->postJson(route('pages.branding', ['slug' => $page->slug]), [
                'avatar' => $avatarFile,
            ]);

        $brandingResponse->assertOk()
            ->assertJsonPath('success', true);
        $page->refresh();
        $this->assertNotNull($page->avatar_url);
    }

    /**
     * Scenario 2: A visitor follows and unfollows the Page.
     */
    public function test_scenario_02_visitor_follows_and_unfollows_page_with_accurate_counts(): void
    {
        $page = $this->createTestPage();

        $this->assertEquals(0, $page->followers_count);
        $this->assertDatabaseMissing('page_followers', [
            'page_id' => $page->id,
            'user_id' => $this->visitor->id,
        ]);

        // 1. Follow Page
        $followResponse = $this->actingAs($this->visitor)
            ->postJson(route('pages.follow.slug', ['slug' => $page->slug]));

        $followResponse->assertOk()
            ->assertJsonPath('is_following', true);

        $page->refresh();
        $this->assertEquals(1, $page->followers_count);
        $this->assertDatabaseHas('page_followers', [
            'page_id' => $page->id,
            'user_id' => $this->visitor->id,
        ]);

        // 2. Unfollow Page
        $unfollowResponse = $this->actingAs($this->visitor)
            ->postJson(route('pages.unfollow.slug', ['slug' => $page->slug]));

        $unfollowResponse->assertOk()
            ->assertJsonPath('is_following', false);

        $page->refresh();
        $this->assertEquals(0, $page->followers_count);
        $this->assertDatabaseMissing('page_followers', [
            'page_id' => $page->id,
            'user_id' => $this->visitor->id,
        ]);
    }

    /**
     * Scenario 3: A permitted Page member publishes a post.
     */
    public function test_scenario_03_permitted_page_member_publishes_post(): void
    {
        $page = $this->createTestPage();

        // Add editor with posts.create permission
        PageMember::create([
            'page_id' => $page->id,
            'user_id' => $this->pageEditor->id,
            'role' => Page::ROLE_EDITOR,
            'status' => 'active',
            'custom_permissions' => ['posts.create' => true, 'posts.edit' => true],
        ]);

        $this->assertEquals(0, $page->posts_count);

        $postResponse = $this->actingAs($this->pageEditor)
            ->postJson(route('pages.posts.store', ['slug' => $page->slug]), [
                'content' => 'Announcing our Q4 Roadmap & Enterprise Updates!',
                'type' => 'text',
                'audience' => 'public',
            ]);

        $this->assertContains($postResponse->status(), [200, 201]);
        $postResponse->assertJsonPath('success', true);

        $page->refresh();
        $this->assertEquals(1, $page->posts_count);

        $this->assertDatabaseHas('posts', [
            'page_id' => $page->id,
            'user_id' => $this->pageEditor->id,
            'content' => 'Announcing our Q4 Roadmap & Enterprise Updates!',
            'status' => 'published',
        ]);
    }

    /**
     * Scenario 4: Another authorized viewer sees the new post without a full-page reload when real-time delivery is supported.
     */
    public function test_scenario_04_realtime_event_dispatched_and_channel_authorized(): void
    {
        Event::fake([PagePostPublishedEvent::class]);

        $page = $this->createTestPage();

        $this->actingAs($this->pageOwner)
            ->postJson(route('pages.posts.store', ['slug' => $page->slug]), [
                'content' => 'Realtime broadcast verification message.',
                'type' => 'text',
                'audience' => 'public',
            ]);

        Event::assertDispatched(PagePostPublishedEvent::class, function (PagePostPublishedEvent $event) use ($page) {
            $channels = $event->broadcastOn();
            $channelNames = array_map(fn ($c) => $c->name, $channels);

            return in_array("page.{$page->id}", $channelNames)
                && $event->pageId === $page->id
                && ($event->postData['content'] ?? null) === 'Realtime broadcast verification message.';
        });

        // Verify channel authorization logic allows authorized team members on private channel
        $authResponse = $this->actingAs($this->pageOwner)->post('/broadcasting/auth', [
            'channel_name' => "private-page.{$page->id}.admin",
            'socket_id' => '12345.67890',
        ]);

        $authResponse->assertOk();

        // Stranger is denied
        $strangerAuth = $this->actingAs($this->stranger)->post('/broadcasting/auth', [
            'channel_name' => "private-page.{$page->id}.admin",
            'socket_id' => '12345.67890',
        ]);
        $strangerAuth->assertForbidden();
    }

    /**
     * Scenario 5: Users react, comment, and share with correct counts.
     */
    public function test_scenario_05_users_react_comment_and_share_with_correct_counts(): void
    {
        $page = $this->createTestPage();

        $post = Post::create([
            'user_id' => $this->pageOwner->id,
            'page_id' => $page->id,
            'content' => 'Major milestone reached: 10,000 active instances deployed!',
            'status' => 'published',
            'audience' => 'public',
            'type' => 'text',
        ]);

        $this->assertEquals(0, $post->likes_count);
        $this->assertEquals(0, $post->comments_count);
        $this->assertEquals(0, $post->shares_count);

        // 1. User reacts (like)
        $reactResponse = $this->actingAs($this->visitor)
            ->postJson(route('posts.react', ['id' => $post->id]), [
                'reaction' => 'like',
                'type' => 'like',
            ]);

        $reactResponse->assertOk();
        $post->refresh();
        $this->assertEquals(1, $post->likes_count);

        // 2. User comments
        $commentResponse = $this->actingAs($this->visitor)
            ->postJson(route('pages.comments.store', ['id' => $post->id]), [
                'body' => 'Congratulations to the entire team on this milestone!',
            ]);

        $this->assertContains($commentResponse->status(), [200, 201]);
        $post->refresh();
        $this->assertEquals(1, $post->comments_count);

        // 3. User shares
        $shareResponse = $this->actingAs($this->visitor)
            ->postJson(route('pages.posts.share', ['id' => $post->id]), [
                'caption' => 'Proud to be using this platform!',
            ]);

        $this->assertContains($shareResponse->status(), [200, 201]);
        $post->refresh();
        $this->assertEquals(1, $post->shares_count);
    }

    /**
     * Scenario 6: A Page owner uploads a Story and it expires according to the configured policy.
     */
    public function test_scenario_06_story_uploads_and_expires_according_to_policy(): void
    {
        $page = $this->createTestPage();

        // 1. Upload Story
        $storyResponse = $this->actingAs($this->pageOwner)
            ->postJson("/pages/{$page->slug}/stories", [
                'type' => 'text',
                'content' => 'Flash 24h Announcement from Page Owner!',
                'privacy' => 'public',
                'background_color' => '#1e293b',
            ]);

        $storyResponse->assertCreated()
            ->assertJsonPath('success', true);

        $storyId = $storyResponse->json('story.id');
        $story = Story::find($storyId);

        $this->assertNotNull($story);
        $this->assertEquals($page->id, $story->page_id);
        $this->assertFalse((bool) $story->is_expired);
        $this->assertTrue($story->expires_at->isFuture());

        // 2. Active story appears in active feed
        $activeStories = Story::where('page_id', $page->id)
            ->where('is_expired', false)
            ->where('expires_at', '>', now())
            ->get();

        $this->assertTrue($activeStories->contains('id', $storyId));

        // 3. Simulate passage of time past policy (>24h)
        $story->update([
            'published_at' => now()->subHours(26),
            'expires_at' => now()->subHours(2),
        ]);

        $this->artisan('stories:expire', ['--retention' => 24])
            ->assertExitCode(0);

        $story->refresh();
        $this->assertTrue((bool) $story->is_expired);

        // Verify expired story no longer appears in active query
        $activeAfter = Story::where('page_id', $page->id)
            ->where('is_expired', false)
            ->where('expires_at', '>', now())
            ->get();

        $this->assertFalse($activeAfter->contains('id', $storyId));
    }

    /**
     * Scenario 7: A Page member publishes a Reel and another user can play it.
     */
    public function test_scenario_07_page_member_publishes_reel_and_user_can_access(): void
    {
        $page = $this->createTestPage();

        // Admin member publishes Reel
        PageMember::create([
            'page_id' => $page->id,
            'user_id' => $this->pageAdmin->id,
            'role' => Page::ROLE_ADMIN,
            'status' => 'active',
        ]);

        $fakeVideo = UploadedFile::fake()->create('quick_demo.mp4', 15000, 'video/mp4');
        $fakeCover = UploadedFile::fake()->image('cover.jpg', 720, 1280);

        $publishResponse = $this->actingAs($this->pageAdmin)
            ->postJson("/pages/{$page->slug}/reels", [
                'video' => $fakeVideo,
                'cover_image' => $fakeCover,
                'caption' => 'Discover our new real-time features! #tech #demo',
                'privacy' => 'public',
                'aspect_ratio' => '9:16',
                'allow_comments' => true,
                'is_draft' => false,
            ]);

        $publishResponse->assertCreated()
            ->assertJsonPath('success', true);

        $reelId = $publishResponse->json('reel.id');
        $reel = Reel::find($reelId);
        $this->assertNotNull($reel);
        $this->assertEquals($page->id, $reel->page_id);
        $this->assertEquals('public', $reel->privacy);

        // Visitor views reels feed / playback
        $indexResponse = $this->actingAs($this->visitor)
            ->getJson("/pages/{$page->slug}/reels");

        $indexResponse->assertOk()
            ->assertJsonPath('success', true);

        $reelsData = $indexResponse->json('reels.data') ?? $indexResponse->json('data') ?? [];
        $ids = collect($reelsData)->pluck('id')->toArray();
        $this->assertContains($reelId, $ids);
    }

    /**
     * Scenario 8: A broadcast starts and ends using real streaming infrastructure, if Live is supported.
     */
    public function test_scenario_08_live_broadcast_lifecycle_start_and_end(): void
    {
        $page = $this->createTestPage();

        // 1. Create broadcast session
        $createResponse = $this->actingAs($this->pageOwner)
            ->postJson(route('pages.live.create', ['slug' => $page->slug]), [
                'title' => 'Global Developer Keynote 2026',
                'description' => 'Real-time platform overview and live Q&A.',
                'privacy' => 'public',
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $streamId = $createResponse->json('stream.id');
        $hostToken = $createResponse->json('host_token');
        $this->assertNotEmpty($hostToken);

        // 2. Start / Publish stream
        $publishResponse = $this->actingAs($this->pageOwner)
            ->postJson(route('pages.live.publish', ['slug' => $page->slug, 'streamId' => $streamId]), [
                'host_token' => $hostToken,
            ]);

        $publishResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('stream.status', LiveStream::STATUS_LIVE);

        // 3. Follower checks active stream
        $activeResponse = $this->actingAs($this->visitor)
            ->getJson(route('pages.live.active', ['slug' => $page->slug]));

        $activeResponse->assertOk()
            ->assertJsonPath('active', true)
            ->assertJsonPath('stream.id', $streamId);

        // 4. End broadcast session
        $endResponse = $this->actingAs($this->pageOwner)
            ->postJson(route('pages.live.end', ['slug' => $page->slug, 'streamId' => $streamId]), [
                'host_token' => $hostToken,
            ]);

        $endResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('stream.status', LiveStream::STATUS_ENDED);

        // 5. Active stream now returns false
        $afterEndResponse = $this->actingAs($this->visitor)
            ->getJson(route('pages.live.active', ['slug' => $page->slug]));

        $afterEndResponse->assertOk()
            ->assertJsonPath('active', false);
    }

    /**
     * Scenario 9: A member with insufficient permissions is denied a restricted action.
     */
    public function test_scenario_09_unauthorized_member_denied_restricted_actions(): void
    {
        $page = $this->createTestPage();

        // Stranger attempts to delete a page post
        $post = Post::create([
            'user_id' => $this->pageOwner->id,
            'page_id' => $page->id,
            'content' => 'Protected page publication.',
            'status' => 'published',
            'audience' => 'public',
        ]);

        $deleteResponse = $this->actingAs($this->stranger)
            ->deleteJson(route('pages.posts.delete', ['slug' => $page->slug, 'postId' => $post->id]));

        $deleteResponse->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);

        // Stranger attempts to modify page settings
        $settingsResponse = $this->actingAs($this->stranger)
            ->putJson(route('pages.updateSettings', ['slug' => $page->slug]), [
                'settings' => ['visibility' => 'private'],
            ]);

        $settingsResponse->assertForbidden();

        // Stranger attempts to invite team members
        $inviteResponse = $this->actingAs($this->stranger)
            ->postJson(route('pages.team.invite', ['slug' => $page->slug]), [
                'user_id' => $this->visitor->id,
                'role' => 'admin',
            ]);

        $inviteResponse->assertForbidden();
    }

    /**
     * Scenario 10: A Page owner changes a setting and the persisted value remains correct after reload.
     */
    public function test_scenario_10_owner_changes_settings_persisted_correctly_on_reload(): void
    {
        $page = $this->createTestPage();

        $newSettings = [
            'visibility' => 'public',
            'discoverability' => [
                'search_engine_indexing' => true,
                'show_in_directory' => true,
            ],
            'messaging' => [
                'allow_messages' => true,
                'auto_reply_enabled' => true,
                'auto_reply_message' => 'Welcome to our verified enterprise page! We will get back shortly.',
            ],
            'moderation' => [
                'allow_comments' => true,
                'profanity_filter' => true,
                'blocked_keywords' => ['spam', 'abuse', 'crypto-scam'],
            ],
            'notifications' => [
                'email_alerts' => true,
                'in_app_follows' => true,
                'in_app_comments' => true,
                'in_app_messages' => true,
            ],
        ];

        $updateResponse = $this->actingAs($this->pageOwner)
            ->putJson(route('pages.updateSettings', ['slug' => $page->slug]), [
                'settings' => $newSettings,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('success', true);

        // Reload page fresh from DB and verify values
        $freshPage = Page::find($page->id);
        $this->assertNotNull($freshPage);
        $this->assertTrue((bool) ($freshPage->settings['messaging']['auto_reply_enabled'] ?? false));
        $this->assertEquals(
            'Welcome to our verified enterprise page! We will get back shortly.',
            $freshPage->settings['messaging']['auto_reply_message'] ?? null
        );
        $this->assertContains('crypto-scam', $freshPage->settings['moderation']['blocked_keywords'] ?? []);

        // Also verify settings appear in management view
        $manageResponse = $this->actingAs($this->pageOwner)
            ->get(route('pages.manage', ['slug' => $page->slug]));

        $manageResponse->assertOk();
    }

    /**
     * Scenario 11: Insights match the underlying test data without fake placeholder numbers.
     */
    public function test_scenario_11_insights_match_underlying_test_data(): void
    {
        $page = $this->createTestPage();

        // 1. Add 3 verified followers
        $followerUsers = User::factory()->count(3)->create();
        foreach ($followerUsers as $u) {
            PageFollower::create([
                'page_id' => $page->id,
                'user_id' => $u->id,
                'created_at' => now(),
            ]);
        }
        $page->update(['followers_count' => 3]);

        // 2. Add 2 posts with real engagement
        Post::create([
            'user_id' => $this->pageOwner->id,
            'page_id' => $page->id,
            'content' => 'First analytical update',
            'status' => 'published',
            'audience' => 'public',
            'likes_count' => 5,
            'comments_count' => 2,
            'shares_count' => 1,
        ]);

        Post::create([
            'user_id' => $this->pageOwner->id,
            'page_id' => $page->id,
            'content' => 'Second analytical update',
            'status' => 'published',
            'audience' => 'public',
            'likes_count' => 3,
            'comments_count' => 4,
            'shares_count' => 2,
        ]);
        $page->update(['posts_count' => 2]);

        $insightsResponse = $this->actingAs($this->pageOwner)
            ->getJson(route('pages.analytics', ['slug' => $page->slug, 'period' => '30d']));

        $insightsResponse->assertOk()
            ->assertJsonPath('success', true);

        $summary = $insightsResponse->json('data.summary') ?? [];
        $this->assertEquals(3, $summary['total_followers'] ?? null);
        $this->assertEquals(8, $summary['total_likes'] ?? null);
        $this->assertEquals(6, $summary['total_comments'] ?? null);
        $this->assertEquals(3, $summary['total_shares'] ?? null);
    }

    /**
     * Scenario 12: Existing modules and user data remain intact after deployment.
     */
    public function test_scenario_12_regression_existing_modules_and_user_data_intact(): void
    {
        // 1. User Profile Timeline continues to work
        $profileUser = User::factory()->create([
            'name' => 'Profile User',
            'username' => 'profileuser12',
        ]);
        $profileResponse = $this->actingAs($profileUser)->get("/@{$profileUser->username}");
        $this->assertContains($profileResponse->status(), [200, 302]);

        // 2. Personal post creation works
        $personalPostResponse = $this->actingAs($profileUser)->postJson('/api/v1/posts', [
            'content' => 'Personal update from regular user timeline.',
            'audience' => 'public',
        ]);
        $this->assertContains($personalPostResponse->status(), [200, 201]);

        // 3. Messenger and Direct Chat continue to work
        $chatPartner = User::factory()->create();
        $conv = Conversation::create(['type' => Conversation::TYPE_DIRECT]);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $profileUser->id]);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $chatPartner->id]);

        $msgResponse = $this->actingAs($profileUser)->postJson("/messages/{$conv->id}/send", [
            'body' => 'Hello from Messenger module!',
        ]);
        $this->assertContains($msgResponse->status(), [200, 201]);

        // 4. Groups module works
        $group = Group::create([
            'creator_id' => $profileUser->id,
            'name' => 'Tech Innovators Group',
            'slug' => 'tech-innovators-group',
            'privacy' => 'public',
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $profileUser->id,
            'role' => 'admin',
        ]);
        $groupResponse = $this->actingAs($profileUser)->get("/groups/{$group->slug}");
        $groupResponse->assertOk();

        // 5. Marketplace module works
        $category = MarketplaceCategory::firstOrCreate(
            ['slug' => 'p12-electronics'],
            ['name' => 'P12 Electronics', 'icon' => '💻', 'display_order' => 99]
        );
        MarketplaceProduct::create([
            'seller_id' => $profileUser->id,
            'category_id' => $category->id,
            'title' => 'ThinkPad X1 Carbon Gen 11',
            'description' => 'Mint condition lightweight business laptop.',
            'price' => 145000,
            'currency' => 'BDT',
            'condition' => 'used',
            'location' => 'Dhaka',
            'status' => 'active',
        ]);
        $marketplaceResponse = $this->actingAs($this->visitor)->get('/marketplace');
        $marketplaceResponse->assertOk();

        // 6. User authentication / login works
        auth('web')->logout();
        $loginResponse = $this->get('/login');
        $loginResponse->assertOk();
    }

    /**
     * Helper to create a standard test page.
     */
    protected function createTestPage(array $attributes = []): Page
    {
        return Page::create(array_merge([
            'owner_id' => $this->pageOwner->id,
            'page_type_id' => $this->pageType->id,
            'page_category_id' => $this->pageCategory->id,
            'name' => 'Apex Cloud Enterprise',
            'slug' => 'apex-cloud-ent-'.uniqid(),
            'username' => 'apexcloud'.uniqid(),
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'posts_count' => 0,
            'followers_count' => 0,
        ], $attributes));
    }
}
