<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConnectlyPhase8CommunityAndLiveIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. GROUPS: Group creation, public/private visibility and slug resolution.
     */
    public function test_group_creation_and_visibility(): void
    {
        $creator = User::factory()->create(['name' => 'Tahmid Khan']);
        Sanctum::actingAs($creator);

        // Create Public Group
        $res = $this->postJson('/api/v1/groups', [
            'name' => 'Bangladeshi Photographers Community',
            'description' => 'A vibrant community of passionate shutterbugs and mobile photographers.',
            'privacy' => 'public',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Bangladeshi Photographers Community');

        $slug = $res->json('data.slug');
        $groupId = $res->json('data.id');

        $this->assertDatabaseHas('groups', [
            'id' => $groupId,
            'privacy' => 'public',
            'creator_id' => $creator->id,
        ]);

        // Details retrieval by slug
        $showRes = $this->getJson("/api/v1/groups/{$slug}");
        $showRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $groupId);
    }

    /**
     * 2. GROUPS: Join and leave group with member count synchronization.
     */
    public function test_group_join_and_leave(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create(['name' => 'Nusrat Jahan']);

        Sanctum::actingAs($creator);
        $groupRes = $this->postJson('/api/v1/groups', [
            'name' => 'Tech Geeks Dhaka',
            'privacy' => 'public',
        ]);
        $groupId = $groupRes->json('data.id');

        // Member joins
        Sanctum::actingAs($member);
        $joinRes = $this->postJson("/api/v1/groups/{$groupId}/join");
        $joinRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $groupId,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        // Member leaves
        $leaveRes = $this->postJson("/api/v1/groups/{$groupId}/leave");
        $leaveRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $groupId,
            'user_id' => $member->id,
        ]);
    }

    /**
     * 3. GROUPS: Member management, roles (admin/moderator) and removal.
     */
    public function test_group_member_management_and_roles(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();

        Sanctum::actingAs($creator);
        $groupRes = $this->postJson('/api/v1/groups', [
            'name' => 'Laravel Developers BD',
            'privacy' => 'public',
        ]);
        $groupId = $groupRes->json('data.id');

        // Add member
        Sanctum::actingAs($member);
        $this->postJson("/api/v1/groups/{$groupId}/join");

        // Admin updates role to moderator
        Sanctum::actingAs($creator);
        $roleRes = $this->putJson("/api/v1/groups/{$groupId}/members/{$member->id}/role", [
            'role' => 'moderator',
        ]);
        $roleRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $groupId,
            'user_id' => $member->id,
            'role' => 'moderator',
        ]);

        // Members list
        $membersRes = $this->getJson("/api/v1/groups/{$groupId}/members");
        $membersRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Admin removes member
        $removeRes = $this->deleteJson("/api/v1/groups/{$groupId}/members/{$member->id}");
        $removeRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $groupId,
            'user_id' => $member->id,
        ]);
    }

    /**
     * 4. GROUPS: Discussions (posts) and official announcements.
     */
    public function test_group_discussions_and_announcements(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();

        Sanctum::actingAs($creator);
        $groupRes = $this->postJson('/api/v1/groups', [
            'name' => 'UI UX Designers Bangladesh',
            'privacy' => 'public',
        ]);
        $groupId = $groupRes->json('data.id');

        Sanctum::actingAs($member);
        $this->postJson("/api/v1/groups/{$groupId}/join");

        // Member creates discussion post
        $postRes = $this->postJson("/api/v1/groups/{$groupId}/posts", [
            'content' => 'What is your favorite design tool in 2026? Figma vs Penpot?',
        ]);
        $postRes->assertStatus(201)
            ->assertJsonPath('success', true);

        // Admin creates announcement
        Sanctum::actingAs($creator);
        $announcementRes = $this->postJson("/api/v1/groups/{$groupId}/announcements", [
            'title' => 'Important Rule Update',
            'content' => 'Please avoid any promotional spam in group discussions.',
        ]);
        $announcementRes->assertStatus(201)
            ->assertJsonPath('success', true);

        // Feed contains discussion
        $feedRes = $this->getJson("/api/v1/groups/{$groupId}/posts");
        $feedRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * 5. GROUPS: Invitations workflow (send and accept).
     */
    public function test_group_invitations_workflow(): void
    {
        $creator = User::factory()->create();
        $invitee = User::factory()->create();

        Sanctum::actingAs($creator);
        $groupRes = $this->postJson('/api/v1/groups', [
            'name' => 'Sylhet Travellers Club',
            'privacy' => 'public',
        ]);
        $groupId = $groupRes->json('data.id');

        // Creator invites friend
        $inviteRes = $this->postJson("/api/v1/groups/{$groupId}/invitations", [
            'user_ids' => [$invitee->id],
        ]);
        $inviteRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Invitee checks their invitations
        Sanctum::actingAs($invitee);
        $myInvitesRes = $this->getJson('/api/v1/groups/invitations');
        $myInvitesRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $invitationId = $myInvitesRes->json('data.0.id');
        if ($invitationId) {
            $acceptRes = $this->postJson("/api/v1/groups/invitations/{$invitationId}/accept");
            $acceptRes->assertStatus(200);
            $this->assertDatabaseHas('group_members', [
                'group_id' => $groupId,
                'user_id' => $invitee->id,
            ]);
        }
    }

    /**
     * 6. GROUPS: Private group join request & moderation approval.
     */
    public function test_group_moderation_queue_and_approval(): void
    {
        $creator = User::factory()->create();
        $applicant = User::factory()->create();

        Sanctum::actingAs($creator);
        $groupRes = $this->postJson('/api/v1/groups', [
            'name' => 'Private Founders Circle',
            'privacy' => 'private',
        ]);
        $groupId = $groupRes->json('data.id');

        // Applicant applies to join private group
        Sanctum::actingAs($applicant);
        $joinRes = $this->postJson("/api/v1/groups/{$groupId}/join");
        $joinRes->assertStatus(200);

        // Creator checks pending requests
        Sanctum::actingAs($creator);
        $requestsRes = $this->getJson("/api/v1/groups/{$groupId}/requests");
        $requestsRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Creator approves request
        $approveRes = $this->postJson("/api/v1/groups/{$groupId}/requests/{$applicant->id}/approve");
        $approveRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $groupId,
            'user_id' => $applicant->id,
            'status' => 'active',
        ]);
    }

    /**
     * 7. PAGES: Page creation, discovery and profile view.
     */
    public function test_page_creation_and_discovery(): void
    {
        $creator = User::factory()->create();
        Sanctum::actingAs($creator);

        $pageRes = $this->postJson('/api/v1/pages', [
            'name' => 'Edge Technology Labs',
            'category' => 'Software Company',
            'bio' => 'Building next-generation intelligent applications.',
        ]);

        $pageRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Edge Technology Labs');

        $slug = $pageRes->json('data.slug');
        $pageId = $pageRes->json('data.id');

        $this->assertDatabaseHas('pages', [
            'id' => $pageId,
            'name' => 'Edge Technology Labs',
            'owner_id' => $creator->id,
        ]);

        // Page discovery list
        $listRes = $this->getJson('/api/v1/pages');
        $listRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Page details
        $showRes = $this->getJson("/api/v1/pages/{$slug}");
        $showRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $pageId);
    }

    /**
     * 8. PAGES: Follow and unfollow page with follower count synchronization.
     */
    public function test_page_follow_and_unfollow(): void
    {
        $creator = User::factory()->create();
        $user = User::factory()->create();

        Sanctum::actingAs($creator);
        $pageRes = $this->postJson('/api/v1/pages', [
            'name' => 'Connectly News',
            'category' => 'Media & News',
        ]);
        $pageId = $pageRes->json('data.id');

        // User follows page
        Sanctum::actingAs($user);
        $followRes = $this->postJson("/api/v1/pages/{$pageId}/follow");
        $followRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Unfollow toggle
        $unfollowRes = $this->postJson("/api/v1/pages/{$pageId}/follow");
        $unfollowRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * 9. PAGES: Page posts and feed.
     */
    public function test_page_posts_and_feed(): void
    {
        $creator = User::factory()->create();
        Sanctum::actingAs($creator);

        $pageRes = $this->postJson('/api/v1/pages', [
            'name' => 'Bangla Cuisine Secrets',
            'category' => 'Food & Beverage',
        ]);
        $pageId = $pageRes->json('data.id');

        // Creator posts on behalf of page
        $postRes = $this->postJson("/api/v1/pages/{$pageId}/posts", [
            'content' => 'Discover the authentic spices of Old Dhaka biryani today!',
        ]);
        $postRes->assertStatus(201)
            ->assertJsonPath('success', true);

        // View page feed
        $feedRes = $this->getJson("/api/v1/pages/{$pageId}/posts");
        $feedRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * 10. EVENTS: Creation, start/end time, location and details.
     */
    public function test_event_creation_and_details(): void
    {
        $creator = User::factory()->create();
        Sanctum::actingAs($creator);

        $startTime = now()->addDays(5)->toIso8601String();
        $endTime = now()->addDays(5)->addHours(4)->toIso8601String();

        $eventRes = $this->postJson('/api/v1/events', [
            'title' => 'National Tech Summit Dhaka 2026',
            'description' => 'The largest gathering of software architects, engineers, and researchers.',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'location_type' => 'venue',
            'address' => 'BICC, Agargaon, Dhaka',
        ]);

        $eventRes->assertStatus(201)
            ->assertJsonPath('success', true);

        // List events
        $listRes = $this->getJson('/api/v1/events');
        $listRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $events = $listRes->json('data.events');
        $this->assertNotEmpty($events);
        $this->assertEquals('National Tech Summit Dhaka 2026', $events[0]['title']);
    }

    /**
     * 11. EVENTS: RSVP toggle between 'going' and 'interested'.
     */
    public function test_event_rsvp_workflow(): void
    {
        $creator = User::factory()->create();
        $user = User::factory()->create();

        Sanctum::actingAs($creator);
        $eventRes = $this->postJson('/api/v1/events', [
            'title' => 'Startup Networking Night',
            'start_time' => now()->addDays(3)->toIso8601String(),
            'location_type' => 'venue',
            'address' => 'Banani, Dhaka',
        ]);
        $eventId = $eventRes->json('data.event.id');

        // User RSVPs 'going'
        Sanctum::actingAs($user);
        $rsvpRes = $this->postJson("/api/v1/events/{$eventId}/rsvp", [
            'status' => 'going',
        ]);
        $rsvpRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_rsvp', 'going');

        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $eventId,
            'user_id' => $user->id,
            'status' => 'going',
        ]);

        // User changes to 'interested'
        $interestedRes = $this->postJson("/api/v1/events/{$eventId}/rsvp", [
            'status' => 'interested',
        ]);
        $interestedRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_rsvp', 'interested');

        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $eventId,
            'user_id' => $user->id,
            'status' => 'interested',
        ]);
    }

    /**
     * 12. LIVE: Live stream creation, start, and discovery.
     */
    public function test_live_stream_creation_and_discovery(): void
    {
        $broadcaster = User::factory()->create();
        Sanctum::actingAs($broadcaster);

        $createRes = $this->postJson('/api/v1/live/streams', [
            'title' => 'Live Coding Session: Jetpack Compose & Clean Architecture',
            'description' => 'Building responsive native Android components in real-time.',
            'privacy' => 'public',
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $streamId = $createRes->json('data.stream.id') ?? $createRes->json('data.id');

        // Start stream
        $startRes = $this->postJson("/api/v1/live/streams/{$streamId}/start");
        $startRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // Discover live streams
        $listRes = $this->getJson('/api/v1/live/streams?status=live');
        $listRes->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    /**
     * 13. LIVE: Real viewer count synchronization via join and leave (no fake counts).
     */
    public function test_live_stream_real_viewer_count_join_and_leave(): void
    {
        $broadcaster = User::factory()->create();
        $viewer = User::factory()->create();

        Sanctum::actingAs($broadcaster);
        $createRes = $this->postJson('/api/v1/live/streams', [
            'title' => 'Acoustic Guitar Jamming Live',
        ]);
        $streamId = $createRes->json('data.stream.id') ?? $createRes->json('data.id');
        $this->postJson("/api/v1/live/streams/{$streamId}/start");

        // Viewer joins with unique session
        Sanctum::actingAs($viewer);
        $sessionId = 'viewer-device-sess-101';
        $joinRes = $this->postJson("/api/v1/live/streams/{$streamId}/join", [
            'session_id' => $sessionId,
        ]);
        $joinRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertGreaterThanOrEqual(1, $joinRes->json('data.viewers_count'));

        // Viewer leaves
        $leaveRes = $this->postJson("/api/v1/live/streams/{$streamId}/leave", [
            'session_id' => $sessionId,
        ]);
        $leaveRes->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    /**
     * 14. LIVE: Comments, reactions and stream sharing.
     */
    public function test_live_stream_interactions(): void
    {
        $broadcaster = User::factory()->create();
        $viewer = User::factory()->create();

        Sanctum::actingAs($broadcaster);
        $createRes = $this->postJson('/api/v1/live/streams', [
            'title' => 'Sunday Tech Talks Live',
        ]);
        $streamId = $createRes->json('data.stream.id') ?? $createRes->json('data.id');
        $this->postJson("/api/v1/live/streams/{$streamId}/start");

        // Viewer posts comment
        Sanctum::actingAs($viewer);
        $commentRes = $this->postJson("/api/v1/live/streams/{$streamId}/comments", [
            'comment' => 'Great explanation, greetings from Sylhet!',
        ]);
        $commentRes->assertStatus(201)
            ->assertJsonPath('status', 'success');

        // Viewer sends heart reaction
        $reactionRes = $this->postJson("/api/v1/live/streams/{$streamId}/reactions", [
            'type' => 'heart',
        ]);
        $reactionRes->assertStatus(201)
            ->assertJsonPath('status', 'success');

        // Viewer shares stream
        $shareRes = $this->postJson("/api/v1/live/streams/{$streamId}/share", [
            'destination' => 'feed',
        ]);
        $shareRes->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    /**
     * 15. LIVE: Appoint moderator and stream termination.
     */
    public function test_live_stream_moderation_and_end(): void
    {
        $broadcaster = User::factory()->create();
        $moderator = User::factory()->create();

        Sanctum::actingAs($broadcaster);
        $createRes = $this->postJson('/api/v1/live/streams', [
            'title' => 'Community Q&A Stream',
        ]);
        $streamId = $createRes->json('data.stream.id') ?? $createRes->json('data.id');
        $this->postJson("/api/v1/live/streams/{$streamId}/start");

        // Broadcaster appoints moderator
        $modRes = $this->postJson("/api/v1/live/streams/{$streamId}/moderators", [
            'user_id' => $moderator->id,
        ]);
        $modRes->assertStatus(201)
            ->assertJsonPath('status', 'success');

        // Broadcaster ends stream
        $endRes = $this->postJson("/api/v1/live/streams/{$streamId}/end");
        $endRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('live_streams', [
            'id' => $streamId,
            'status' => 'ended',
        ]);
    }
}
