<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Page;
use App\Models\PageAuditLog;
use App\Models\PageConversation;
use App\Models\PageMember;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * এন্টারপ্রাইজ সোশ্যাল পেজ অপারেটিং সিস্টেম - টেস্ট সুইট
 * (GATES 1 to 20: Core CRUD, RBAC, Custom Permissions, Tenant Isolation,
 * Scheduled Posts Worker, Inbox Messaging, Moderation, Real Analytics, Immutable Audit).
 */
class EnterprisePageSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $adminMember;

    protected User $moderatorMember;

    protected User $viewerMember;

    protected User $strangerUser;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Page Owner', 'username' => 'pageowner']);
        $this->adminMember = User::factory()->create(['name' => 'Page Admin', 'username' => 'pageadmin']);
        $this->moderatorMember = User::factory()->create(['name' => 'Page Moderator', 'username' => 'pagemod']);
        $this->viewerMember = User::factory()->create(['name' => 'Page Viewer', 'username' => 'pageviewer']);
        $this->strangerUser = User::factory()->create(['name' => 'Stranger User', 'username' => 'stranger']);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'name' => 'Dhaka Enterprise Tech',
            'slug' => 'dhaka-enterprise-tech',
            'username' => 'dhakatech',
            'category' => 'Technology',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 0,
            'posts_count' => 0,
        ]);

        // Assign Roles
        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->adminMember->id,
            'role' => Page::ROLE_ADMIN,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->moderatorMember->id,
            'role' => Page::ROLE_MODERATOR,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->viewerMember->id,
            'role' => Page::ROLE_VIEWER,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    /**
     * GATE 1: Core Page CRUD & Lifecycle
     */
    public function test_can_create_enterprise_page_with_full_metadata(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v2/pages', [
            'name' => 'Silicon Bengal Inc',
            'username' => 'siliconbengal',
            'category' => 'Business',
            'bio' => 'Enterprise cloud software provider.',
            'website' => 'https://siliconbengal.com',
            'email' => 'contact@siliconbengal.com',
            'cta_type' => 'visit_website',
            'cta_url' => 'https://siliconbengal.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Silicon Bengal Inc')
            ->assertJsonPath('data.username', 'siliconbengal');

        $this->assertDatabaseHas('pages', [
            'name' => 'Silicon Bengal Inc',
            'username' => 'siliconbengal',
            'owner_id' => $this->owner->id,
        ]);
    }

    public function test_can_update_page_and_settings(): void
    {
        $response = $this->actingAs($this->owner)->putJson("/api/v2/pages/{$this->page->id}", [
            'bio' => 'Updated official bio statement.',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.bio', 'Updated official bio statement.')
            ->assertJsonPath('data.city', 'Dhaka');

        // Update settings
        $settingsRes = $this->actingAs($this->owner)->putJson("/api/v2/pages/{$this->page->id}/settings", [
            'messaging' => [
                'auto_reply_enabled' => true,
                'auto_reply_message' => 'Thanks for messaging Dhaka Enterprise Tech!',
            ],
            'moderation' => [
                'blocked_keywords' => ['fake', 'scam', 'fraud'],
            ],
        ]);

        $settingsRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.messaging.auto_reply_enabled', true);
    }

    public function test_can_trash_and_restore_page(): void
    {
        // Owner soft deletes page
        $deleteRes = $this->actingAs($this->owner)->deleteJson("/api/v2/pages/{$this->page->id}");
        $deleteRes->assertStatus(200)->assertJsonPath('success', true);

        $this->assertSoftDeleted('pages', ['id' => $this->page->id]);

        // Restore page
        $restoreRes = $this->actingAs($this->owner)->postJson("/api/v2/pages/{$this->page->id}/restore");
        $restoreRes->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('pages', ['id' => $this->page->id, 'deleted_at' => null]);
    }

    public function test_can_transfer_page_ownership(): void
    {
        $newOwner = User::factory()->create();

        $response = $this->actingAs($this->owner)->postJson("/api/v2/pages/{$this->page->id}/transfer-ownership", [
            'new_owner_id' => $newOwner->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $fresh = $this->page->fresh();
        $this->assertSame((int) $newOwner->id, (int) $fresh->owner_id);

        // Previous owner becomes admin member
        $this->assertTrue(PageMember::where('page_id', $this->page->id)->where('user_id', $this->owner->id)->where('role', Page::ROLE_ADMIN)->exists());
    }

    /**
     * GATE 2 & 3: Authorization, RBAC & Custom Permissions
     */
    public function test_rbac_server_side_enforcement(): void
    {
        // 1. Viewer cannot create post -> 403 Forbidden
        $viewerPostRes = $this->actingAs($this->viewerMember)->postJson("/api/v2/pages/{$this->page->id}/posts", [
            'content' => 'Unauthorized post attempt by viewer',
        ]);
        $viewerPostRes->assertStatus(403);

        // 2. Stranger cannot manage team -> 403 Forbidden
        $strangerTeamRes = $this->actingAs($this->strangerUser)->postJson("/api/v2/pages/{$this->page->id}/team/invite", [
            'email_or_username' => 'someuser@jugajug.com',
            'role' => 'editor',
        ]);
        $strangerTeamRes->assertStatus(403);

        // 3. Admin can create post -> 201 Created
        $adminPostRes = $this->actingAs($this->adminMember)->postJson("/api/v2/pages/{$this->page->id}/posts", [
            'content' => 'Official tech release announcement.',
        ]);
        $adminPostRes->assertStatus(201);
    }

    public function test_custom_permission_override_allows_action(): void
    {
        // Give viewer custom override: ['posts.create' => true]
        $member = PageMember::where('page_id', $this->page->id)->where('user_id', $this->viewerMember->id)->first();
        $member->update([
            'custom_permissions' => ['posts.create' => true],
        ]);

        $this->actingAs($this->viewerMember);
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/posts", [
            'content' => 'Post created due to custom permission override!',
        ]);

        $res->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_tenant_isolation_prevents_cross_page_tampering(): void
    {
        // Create second page owned by stranger
        $otherPage = Page::create([
            'owner_id' => $this->strangerUser->id,
            'name' => 'Chittagong Maritime',
            'slug' => 'chittagong-maritime',
            'category' => 'Logistics',
        ]);

        $postOnOtherPage = Post::create([
            'user_id' => $this->strangerUser->id,
            'page_id' => $otherPage->id,
            'content' => 'Shipping schedule release.',
            'status' => 'published',
        ]);

        // Owner of page 1 tries to delete post on other page
        $tamperRes = $this->actingAs($this->owner)->deleteJson("/api/v2/pages/{$this->page->id}/posts/{$postOnOtherPage->id}");
        $tamperRes->assertStatus(404);

        $this->assertDatabaseHas('posts', ['id' => $postOnOtherPage->id]);
    }

    /**
     * GATE 5 & 6: Content Lifecycle & Background Scheduled Post Worker
     */
    public function test_scheduled_post_published_by_background_worker(): void
    {
        // 1. Create a post scheduled 5 minutes ago (due for publishing)
        $pastTime = Carbon::now()->subMinutes(5);
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Automated future announcement now live!',
            'status' => 'scheduled',
            'scheduled_at' => $pastTime,
        ]);

        $this->assertSame('scheduled', $post->status);

        // 2. Run the scheduled publishing background command
        $this->artisan('pages:publish-scheduled')
            ->expectsOutputToContain('1 scheduled post(s) published successfully')
            ->assertExitCode(0);

        // 3. Verify post status transitioned to published
        $fresh = $post->fresh();
        $this->assertSame('published', $fresh->status);
    }

    /**
     * GATE 8: Multi-Agent Inbox Messaging
     */
    public function test_page_visitor_messaging_and_team_reply(): void
    {
        // 1. Visitor sends message to page
        $visitorMsgRes = $this->actingAs($this->strangerUser)->postJson("/api/v2/pages/{$this->page->id}/inbox/message", [
            'body' => 'Hello, I have an inquiry regarding enterprise licensing.',
        ]);
        $visitorMsgRes->assertStatus(201);

        $conv = PageConversation::where('page_id', $this->page->id)->where('user_id', $this->strangerUser->id)->first();
        $this->assertNotNull($conv);

        // 2. Admin assigns conversation to moderator
        $assignRes = $this->actingAs($this->adminMember)->postJson("/api/v2/pages/{$this->page->id}/inbox/conversations/{$conv->id}/assign", [
            'assigned_to' => $this->moderatorMember->id,
        ]);
        $assignRes->assertStatus(200)->assertJsonPath('data.assigned_agent.id', $this->moderatorMember->id);

        // 3. Moderator replies to visitor
        $replyRes = $this->actingAs($this->moderatorMember)->postJson("/api/v2/pages/{$this->page->id}/inbox/conversations/{$conv->id}/reply", [
            'body' => 'Thank you for reaching out! Our enterprise team will contact you shortly.',
        ]);
        $replyRes->assertStatus(201);

        // 4. Add staff internal note
        $noteRes = $this->actingAs($this->moderatorMember)->postJson("/api/v2/pages/{$this->page->id}/inbox/conversations/{$conv->id}/notes", [
            'body' => 'VIP lead - follow up via email within 2 hours.',
        ]);
        $noteRes->assertStatus(201)->assertJsonPath('data.body', 'VIP lead - follow up via email within 2 hours.');
    }

    /**
     * GATE 9: Moderation Center & Keyword Filtering
     */
    public function test_moderation_center_blocking_and_comments(): void
    {
        // 1. Block abusive user
        $blockRes = $this->actingAs($this->moderatorMember)->postJson("/api/v2/pages/{$this->page->id}/moderation/block", [
            'target_user_id' => $this->strangerUser->id,
            'reason' => 'Repeated spam and advertising in comments',
        ]);
        $blockRes->assertStatus(201);

        $this->assertTrue($this->page->isBlocked($this->strangerUser->id));

        // 2. Blocked user is restricted from messaging page
        $msgRes = $this->actingAs($this->strangerUser)->postJson("/api/v2/pages/{$this->page->id}/inbox/message", [
            'body' => 'Attempting to message while blocked.',
        ]);
        $msgRes->assertStatus(403);

        // 3. Unblock user
        $unblockRes = $this->actingAs($this->moderatorMember)->deleteJson("/api/v2/pages/{$this->page->id}/moderation/unblock/{$this->strangerUser->id}");
        $unblockRes->assertStatus(200);
        $this->assertFalse($this->page->isBlocked($this->strangerUser->id));
    }

    public function test_comment_moderation_delete(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Discussion topic for feedback.',
            'status' => 'published',
            'comments_count' => 1,
        ]);

        $comment = Comment::create([
            'user_id' => $this->strangerUser->id,
            'post_id' => $post->id,
            'body' => 'Spam comment offering get-rich-quick schemes.',
        ]);

        $res = $this->actingAs($this->moderatorMember)->postJson("/api/v2/pages/{$this->page->id}/moderation/comments", [
            'comment_id' => $comment->id,
            'action' => 'delete',
        ]);

        $res->assertStatus(200)->assertJsonPath('success', true);
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    /**
     * GATE 10: Real Database Analytics Aggregation (Zero Mock Data)
     */
    public function test_real_analytics_aggregation_from_database(): void
    {
        // Create 2 real posts with likes and comments
        Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'First post',
            'status' => 'published',
            'likes_count' => 15,
            'comments_count' => 5,
            'shares_count' => 2,
        ]);

        Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Second post',
            'status' => 'published',
            'likes_count' => 25,
            'comments_count' => 10,
            'shares_count' => 3,
        ]);

        $res = $this->actingAs($this->owner)->getJson("/api/v2/pages/{$this->page->id}/analytics/overview?days=30");

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total_likes', 40)
            ->assertJsonPath('data.summary.total_comments', 15)
            ->assertJsonPath('data.summary.total_shares', 5)
            ->assertJsonPath('data.summary.total_engagements', 60);
    }

    /**
     * GATE 12: Immutable Audit Logging
     */
    public function test_critical_mutations_create_immutable_audit_logs(): void
    {
        // Team invite triggers audit log
        $this->actingAs($this->owner)->postJson("/api/v2/pages/{$this->page->id}/team/invite", [
            'email_or_username' => $this->strangerUser->email,
            'role' => 'editor',
        ]);

        $log = PageAuditLog::where('page_id', $this->page->id)
            ->where('action', 'team.invite')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame((int) $this->owner->id, (int) $log->actor_id);
        $this->assertSame((int) $this->strangerUser->id, (int) $log->target_id);

        // Fetch audit logs via API
        $res = $this->actingAs($this->owner)->getJson("/api/v2/pages/{$this->page->id}/audit-logs");
        $res->assertStatus(200)->assertJsonPath('success', true);
    }

    /**
     * GATE 28: Events & Products
     */
    public function test_can_manage_events_and_products(): void
    {
        // 1. Create Event
        $eventRes = $this->actingAs($this->adminMember)->postJson("/api/v2/pages/{$this->page->id}/events", [
            'title' => 'Bangladesh Tech Expo 2026',
            'location' => 'Bangabandhu International Conference Center',
            'start_time' => Carbon::now()->addDays(7)->toDateTimeString(),
            'description' => 'Flagship tech convention.',
        ]);
        $eventRes->assertStatus(201)->assertJsonPath('data.title', 'Bangladesh Tech Expo 2026');

        $eventId = $eventRes->json('data.id');

        // 2. User RSVPs
        $rsvpRes = $this->actingAs($this->strangerUser)->postJson("/api/v2/pages/{$this->page->id}/events/{$eventId}/rsvp", [
            'status' => 'going',
        ]);
        $rsvpRes->assertStatus(200)->assertJsonPath('data.going_count', 1);

        // 3. Create Commerce Product
        $prodRes = $this->actingAs($this->adminMember)->postJson("/api/v2/pages/{$this->page->id}/products", [
            'title' => 'Jugajug Cloud Developer Pro Pass',
            'price' => 1500.00,
            'currency' => 'BDT',
            'stock_quantity' => 50,
            'description' => 'Full access dev badge.',
        ]);
        $prodRes->assertStatus(201)
            ->assertJsonPath('data.title', 'Jugajug Cloud Developer Pro Pass')
            ->assertJsonPath('data.price', '1500.00');
    }
}
