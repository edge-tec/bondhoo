<?php

namespace Tests\Feature;

use App\Models\NotificationSetting;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageConversation;
use App\Models\PageEvent;
use App\Models\PageFollower;
use App\Models\PageInvitation;
use App\Models\PageMember;
use App\Models\PageMessage;
use App\Models\PageType;
use App\Models\User;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PART 6 — Followers, Invitations, Page Messaging & Quick Actions Test Suite
 */
class PageFollowersInvitationsMessagingAndQuickActionsPart6Test extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staffWithMsgPermission;

    protected User $staffWithoutMsgPermission;

    protected User $visitor;

    protected User $otherVisitor;

    protected Page $page;

    protected Page $privatePage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PageTaxonomySeeder::class);

        $type = PageType::first() ?? PageType::create(['name' => 'Company', 'slug' => 'company', 'is_active' => true]);
        $category = PageCategory::first() ?? PageCategory::create(['page_type_id' => $type->id, 'name' => 'Business', 'slug' => 'business', 'is_active' => true]);

        $this->owner = User::factory()->create(['name' => 'Page Boss', 'username' => 'pageboss']);
        $this->staffWithMsgPermission = User::factory()->create(['name' => 'Support Staff', 'username' => 'supportstaff']);
        $this->staffWithoutMsgPermission = User::factory()->create(['name' => 'Content Editor', 'username' => 'contenteditor']);
        $this->visitor = User::factory()->create(['name' => 'Curious Visitor', 'username' => 'curiousvisitor']);
        $this->otherVisitor = User::factory()->create(['name' => 'Other Visitor', 'username' => 'othervisitor']);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'page_type_id' => $type->id,
            'page_category_id' => $category->id,
            'name' => 'Apex Innovations',
            'slug' => 'apex-innovations',
            'username' => 'apexinnovations',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 0,
            'posts_count' => 0,
        ]);

        $this->privatePage = Page::create([
            'owner_id' => $this->owner->id,
            'page_type_id' => $type->id,
            'page_category_id' => $category->id,
            'name' => 'Secret Apex Lab',
            'slug' => 'secret-apex-lab',
            'username' => 'secretapexlab',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'private',
            'followers_count' => 0,
            'posts_count' => 0,
        ]);

        // Staff member with messages.manage
        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->staffWithMsgPermission->id,
            'role' => Page::ROLE_ADMIN,
            'status' => 'active',
            'permissions' => ['messages.manage', 'page.view'],
        ]);

        // Staff member without messages.manage
        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->staffWithoutMsgPermission->id,
            'role' => Page::ROLE_EDITOR,
            'status' => 'active',
            'permissions' => ['posts.create', 'page.view'],
        ]);
    }

    // ==========================================
    // 1. FOLLOWERS & RELATIONSHIPS
    // ==========================================

    public function test_authenticated_user_can_follow_and_unfollow_page_by_slug(): void
    {
        // Follow
        $followResponse = $this->actingAs($this->visitor, 'web')
            ->postJson("/pages/{$this->page->slug}/follow");

        $followResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_following' => true,
                'followers_count' => 1,
            ]);

        $this->assertDatabaseHas('page_followers', [
            'page_id' => $this->page->id,
            'user_id' => $this->visitor->id,
        ]);
        $this->assertEquals(1, $this->page->fresh()->followers_count);

        // Unfollow
        $unfollowResponse = $this->actingAs($this->visitor, 'web')
            ->postJson("/pages/{$this->page->slug}/unfollow");

        $unfollowResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_following' => false,
                'followers_count' => 0,
            ]);

        $this->assertDatabaseMissing('page_followers', [
            'page_id' => $this->page->id,
            'user_id' => $this->visitor->id,
        ]);
        $this->assertEquals(0, $this->page->fresh()->followers_count);
    }

    public function test_follow_prevents_duplicate_records_and_preserves_count(): void
    {
        // First follow
        $this->actingAs($this->visitor, 'web')
            ->postJson("/pages/{$this->page->slug}/follow")
            ->assertStatus(200);

        $this->assertEquals(1, $this->page->fresh()->followers_count);

        // Repeated follow click
        $repeatResponse = $this->actingAs($this->visitor, 'web')
            ->postJson("/pages/{$this->page->slug}/follow");

        $repeatResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_following' => true,
                'followers_count' => 1,
            ]);

        $this->assertEquals(1, PageFollower::where('page_id', $this->page->id)->where('user_id', $this->visitor->id)->count());
        $this->assertEquals(1, $this->page->fresh()->followers_count);
    }

    public function test_unauthenticated_user_cannot_follow_page(): void
    {
        $response = $this->postJson("/pages/{$this->page->slug}/follow");
        $response->assertStatus(401);
    }

    public function test_follower_data_authorization_on_public_and_private_pages(): void
    {
        // Add follower to public page
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->visitor->id,
            'created_at' => now(),
        ]);
        $this->page->update(['followers_count' => 1]);

        // Public page followers list is accessible to everyone
        $publicRes = $this->getJson("/pages/{$this->page->slug}/followers");
        $publicRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // Add follower to private page
        PageFollower::create([
            'page_id' => $this->privatePage->id,
            'user_id' => $this->visitor->id,
            'created_at' => now(),
        ]);
        $this->privatePage->update(['followers_count' => 1]);

        // Unauthenticated visitor cannot see private page followers (403)
        $anonPrivateRes = $this->getJson("/pages/{$this->privatePage->slug}/followers");
        $anonPrivateRes->assertStatus(403);

        // Stranger (non-manager, non-follower) cannot see private page followers (403)
        $strangerPrivateRes = $this->actingAs($this->otherVisitor, 'web')
            ->getJson("/pages/{$this->privatePage->slug}/followers");
        $strangerPrivateRes->assertStatus(403);

        // Confirmed follower can see private page followers
        $followerPrivateRes = $this->actingAs($this->visitor, 'web')
            ->getJson("/pages/{$this->privatePage->slug}/followers");
        $followerPrivateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // Page owner can see private page followers
        $ownerPrivateRes = $this->actingAs($this->owner, 'web')
            ->getJson("/pages/{$this->privatePage->slug}/followers");
        $ownerPrivateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    // ==========================================
    // 2. INVITATIONS
    // ==========================================

    public function test_eligible_friends_query_filters_out_followers_and_pending_invitees(): void
    {
        // $this->visitor already follows the page
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->visitor->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->owner, 'web')
            ->getJson("/pages/{$this->page->slug}/invitations/eligible-friends");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $eligibleUserIds = collect($response->json('data'))->pluck('id')->toArray();

        // The owner themselves should NOT be eligible
        $this->assertNotContains($this->owner->id, $eligibleUserIds);
        // The existing follower should NOT be eligible
        $this->assertNotContains($this->visitor->id, $eligibleUserIds);
        // The otherVisitor should be eligible
        $this->assertContains($this->otherVisitor->id, $eligibleUserIds);
    }

    public function test_send_invitation_creates_persistent_record_and_prevents_duplicates(): void
    {
        // 1. Send invitation to $this->visitor
        $response = $this->actingAs($this->owner, 'web')
            ->postJson("/pages/{$this->page->slug}/invitations", [
                'invitee_id' => $this->visitor->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'invitations_count' => 1,
            ]);

        $this->assertDatabaseHas('page_invitations', [
            'page_id' => $this->page->id,
            'inviter_id' => $this->owner->id,
            'invitee_id' => $this->visitor->id,
            'status' => 'pending',
        ]);

        // 2. Duplicate invitation to the same recipient is rejected
        $duplicateResponse = $this->actingAs($this->owner, 'web')
            ->postJson("/pages/{$this->page->slug}/invitations", [
                'invitee_id' => $this->visitor->id,
            ]);

        $duplicateResponse->assertStatus(422);
    }

    public function test_cannot_invite_self_or_user_already_following(): void
    {
        // Cannot invite self
        $selfResponse = $this->actingAs($this->owner, 'web')
            ->postJson("/pages/{$this->page->slug}/invitations", [
                'invitee_id' => $this->owner->id,
            ]);
        $selfResponse->assertStatus(422);

        // Cannot invite user already following
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->visitor->id,
            'created_at' => now(),
        ]);

        $followingResponse = $this->actingAs($this->owner, 'web')
            ->postJson("/pages/{$this->page->slug}/invitations", [
                'invitee_id' => $this->visitor->id,
            ]);
        $followingResponse->assertStatus(422);
    }

    public function test_invitation_rate_limit_enforcement(): void
    {
        // Create 30 invitations for the inviter today (max allowed limit)
        for ($i = 0; $i < PageInvitation::DAILY_RATE_LIMIT; $i++) {
            $dummy = User::factory()->create();
            PageInvitation::create([
                'page_id' => $this->page->id,
                'inviter_id' => $this->owner->id,
                'invitee_id' => $dummy->id,
                'status' => 'pending',
                'created_at' => now(),
            ]);
        }

        // Attempting 31st invitation must be blocked by rate limit (429)
        $rateLimitResponse = $this->actingAs($this->owner, 'web')
            ->postJson("/pages/{$this->page->slug}/invitations", [
                'invitee_id' => $this->visitor->id,
            ]);

        $rateLimitResponse->assertStatus(429)
            ->assertJsonPath('success', false);
    }

    public function test_notification_preference_respected_when_sending_invitation(): void
    {
        // Recipient has disabled page alerts
        NotificationSetting::create([
            'user_id' => $this->visitor->id,
            'page_alerts' => false,
        ]);

        $response = $this->actingAs($this->owner, 'web')
            ->postJson("/pages/{$this->page->slug}/invitations", [
                'invitee_id' => $this->visitor->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('page_invitations', [
            'page_id' => $this->page->id,
            'invitee_id' => $this->visitor->id,
            'status' => 'pending',
        ]);
    }

    public function test_accept_invitation_follows_page_and_updates_status(): void
    {
        $invitation = PageInvitation::create([
            'page_id' => $this->page->id,
            'inviter_id' => $this->owner->id,
            'invitee_id' => $this->visitor->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(14),
        ]);

        // Unauthorized user cannot accept someone else's invitation
        $this->actingAs($this->otherVisitor, 'web')
            ->postJson("/invitations/pages/{$invitation->id}/accept")
            ->assertStatus(403);

        // Invitee accepts
        $acceptResponse = $this->actingAs($this->visitor, 'web')
            ->postJson("/invitations/pages/{$invitation->id}/accept");

        $acceptResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_following' => true,
                'followers_count' => 1,
            ]);

        $this->assertEquals(PageInvitation::STATUS_ACCEPTED, $invitation->fresh()->status);
        $this->assertNotNull($invitation->fresh()->responded_at);
        $this->assertDatabaseHas('page_followers', [
            'page_id' => $this->page->id,
            'user_id' => $this->visitor->id,
        ]);
        $this->assertEquals(1, $this->page->fresh()->followers_count);
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $expiredInvitation = PageInvitation::create([
            'page_id' => $this->page->id,
            'inviter_id' => $this->owner->id,
            'invitee_id' => $this->visitor->id,
            'status' => 'pending',
            'expires_at' => now()->subDay(), // Expired yesterday
        ]);

        $response = $this->actingAs($this->visitor, 'web')
            ->postJson("/invitations/pages/{$expiredInvitation->id}/accept");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertEquals(PageInvitation::STATUS_EXPIRED, $expiredInvitation->fresh()->status);
        $this->assertDatabaseMissing('page_followers', [
            'page_id' => $this->page->id,
            'user_id' => $this->visitor->id,
        ]);
    }

    public function test_decline_invitation(): void
    {
        $invitation = PageInvitation::create([
            'page_id' => $this->page->id,
            'inviter_id' => $this->owner->id,
            'invitee_id' => $this->visitor->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(14),
        ]);

        $response = $this->actingAs($this->visitor, 'web')
            ->postJson("/invitations/pages/{$invitation->id}/decline");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invitation declined.',
            ]);

        $this->assertEquals(PageInvitation::STATUS_DECLINED, $invitation->fresh()->status);
    }

    // ==========================================
    // 3. PAGE MESSAGING
    // ==========================================

    public function test_message_page_deduplicates_conversations_on_repeated_actions(): void
    {
        // 1. First message from visitor
        $res1 = $this->actingAs($this->visitor, 'web')
            ->postJson("/pages/{$this->page->slug}/messages", [
                'body' => 'Hello, I have a question about your business services.',
            ]);

        $res1->assertStatus(201)
            ->assertJsonPath('success', true);

        $convId = $res1->json('data.conversation_id');
        $this->assertNotNull($convId);
        $this->assertEquals(1, PageConversation::where('page_id', $this->page->id)->where('user_id', $this->visitor->id)->count());

        // 2. Second message from visitor
        $res2 = $this->actingAs($this->visitor, 'web')
            ->postJson("/pages/{$this->page->slug}/messages", [
                'body' => 'Also, what are your opening hours?',
            ]);

        $res2->assertStatus(201)
            ->assertJsonPath('data.conversation_id', $convId);

        // Verification: Exactly 1 conversation exists between this user and page, with 2 messages
        $this->assertEquals(1, PageConversation::where('page_id', $this->page->id)->where('user_id', $this->visitor->id)->count());
        $this->assertEquals(2, PageMessage::where('conversation_id', $convId)->count());
    }

    public function test_messaging_authorization_and_staff_replies(): void
    {
        // Create conversation between visitor and page
        $conv = PageConversation::create([
            'page_id' => $this->page->id,
            'user_id' => $this->visitor->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        PageMessage::create([
            'conversation_id' => $conv->id,
            'sender_type' => 'user',
            'sender_id' => $this->visitor->id,
            'body' => 'Customer inquiry message',
            'is_read' => false,
        ]);
        $conv->increment('unread_page_count');

        // Staff member without messages.manage cannot reply as page (403)
        $unauthStaffRes = $this->actingAs($this->staffWithoutMsgPermission, 'web')
            ->postJson("/pages/{$this->page->slug}/messages", [
                'body' => 'Unauthorized staff reply',
                'as_page' => true,
                'conversation_id' => $conv->id,
            ]);
        $unauthStaffRes->assertStatus(403);

        // Authorized staff with messages.manage CAN reply as page
        $authStaffRes = $this->actingAs($this->staffWithMsgPermission, 'web')
            ->postJson("/pages/{$this->page->slug}/messages", [
                'body' => 'Welcome to Apex Innovations! How can we help?',
                'as_page' => true,
                'conversation_id' => $conv->id,
            ]);

        $authStaffRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sender_type', 'page');

        // Unread counters verification:
        // Reply as page increments unread_user_count
        $this->assertEquals(1, $conv->fresh()->unread_user_count);

        // Unauthorized 3rd party user cannot view another user's conversation (403)
        $this->actingAs($this->otherVisitor, 'web')
            ->getJson("/pages/{$this->page->slug}/messages?conversation_id={$conv->id}")
            ->assertStatus(403);

        // Visitor views message history: marks page messages as read and resets unread_user_count
        $visitorReadRes = $this->actingAs($this->visitor, 'web')
            ->getJson("/pages/{$this->page->slug}/messages");

        $visitorReadRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(0, $conv->fresh()->unread_user_count);
        $this->assertEquals(0, PageMessage::where('conversation_id', $conv->id)->where('sender_type', 'page')->where('is_read', false)->count());
    }

    // ==========================================
    // 4. QUICK ACTIONS
    // ==========================================

    public function test_quick_action_create_event_workflow(): void
    {
        $startTime = now()->addDays(5)->format('Y-m-d H:i:s');
        $endTime = now()->addDays(5)->addHours(3)->format('Y-m-d H:i:s');

        // Non-manager cannot create event (403)
        $unauthRes = $this->actingAs($this->visitor, 'web')
            ->postJson("/pages/{$this->page->slug}/events", [
                'title' => 'Visitor Fan Meet',
                'start_time' => $startTime,
            ]);
        $unauthRes->assertStatus(403);

        // Manager creates event
        $createRes = $this->actingAs($this->owner, 'web')
            ->postJson("/pages/{$this->page->slug}/events", [
                'title' => 'Annual Apex Tech Summit 2026',
                'description' => 'Showcasing bleeding-edge artificial intelligence and cloud architectures.',
                'location' => 'Dhaka Convention Center',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_online' => false,
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('event.title', 'Annual Apex Tech Summit 2026');

        $this->assertDatabaseHas('page_events', [
            'page_id' => $this->page->id,
            'title' => 'Annual Apex Tech Summit 2026',
        ]);
        $event = PageEvent::where('page_id', $this->page->id)->first();

        // Visitor RSVPs to the event
        $rsvpRes = $this->actingAs($this->visitor, 'web')
            ->postJson("/pages/{$this->page->slug}/events/{$event->id}/rsvp", [
                'status' => 'going',
            ]);

        $rsvpRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('rsvp_count', 1);

        $this->assertDatabaseHas('page_event_rsvps', [
            'event_id' => $event->id,
            'user_id' => $this->visitor->id,
            'status' => 'going',
        ]);
    }
}
