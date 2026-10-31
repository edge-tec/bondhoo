<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseCommunityEventsAndLiveIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Test Group Creation, Listing, Joining, and Leaving.
     */
    public function test_group_lifecycle_and_membership(): void
    {
        $creator = User::factory()->create(['name' => 'Group Admin']);
        $member = User::factory()->create(['name' => 'Group Member']);

        // Create Group
        $createRes = $this->actingAs($creator)->postJson('/api/v1/groups', [
            'name' => 'Travel Lovers BD',
            'description' => 'A community for adventure seekers.',
            'privacy' => 'public',
        ]);
        $createRes->assertStatus(201);
        $groupId = $createRes->json('data.id');
        $this->assertNotNull($groupId);

        // List Groups
        $listRes = $this->actingAs($member)->getJson('/api/v1/groups');
        $listRes->assertStatus(200);

        // Member joins group
        $joinRes = $this->actingAs($member)->postJson("/api/v1/groups/{$groupId}/join");
        $joinRes->assertSuccessful();

        // Member leaves group
        $leaveRes = $this->actingAs($member)->postJson("/api/v1/groups/{$groupId}/leave");
        $leaveRes->assertSuccessful();
    }

    /**
     * 2. Test Page Creation and Following.
     */
    public function test_page_creation_and_following(): void
    {
        $creator = User::factory()->create();
        $follower = User::factory()->create();

        // Create Page
        $pageRes = $this->actingAs($creator)->postJson('/api/v1/pages', [
            'name' => 'Tech World Official',
            'category' => 'Technology',
            'bio' => 'Latest in tech and gadgets.',
        ]);
        $pageRes->assertSuccessful();
        $pageId = $pageRes->json('data.id');

        // Follow Page
        $followRes = $this->actingAs($follower)->postJson("/api/v1/pages/{$pageId}/follow");
        $followRes->assertSuccessful();
    }

    /**
     * 3. Test Event Creation and RSVP (Interested and Going).
     */
    public function test_event_creation_and_rsvp(): void
    {
        $organizer = User::factory()->create();
        $attendee = User::factory()->create();

        // Create Event
        $eventRes = $this->actingAs($organizer)->postJson('/api/v1/events', [
            'title' => 'Music Festival 2026',
            'location_type' => 'venue',
            'address' => 'Dhaka Arena, Dhaka',
            'start_time' => now()->addDays(15)->toIso8601String(),
            'end_time' => now()->addDays(15)->addHours(5)->toIso8601String(),
        ]);
        $eventRes->assertSuccessful();
        $eventId = $eventRes->json('data.event.id') ?? $eventRes->json('data.id') ?? $eventRes->json('id');

        // Attendee RSVPs as Going
        $rsvpGoingRes = $this->actingAs($attendee)->postJson("/api/v1/events/{$eventId}/rsvp", [
            'status' => 'going',
        ]);
        $rsvpGoingRes->assertSuccessful();

        // Attendee updates RSVP to Interested
        $rsvpInterestedRes = $this->actingAs($attendee)->postJson("/api/v1/events/{$eventId}/rsvp", [
            'status' => 'interested',
        ]);
        $rsvpInterestedRes->assertSuccessful();
    }

    /**
     * 4. Test Live Streaming Lifecycle (Start, Viewer Join, Comment, Reaction, Leave, End).
     */
    public function test_live_streaming_complete_lifecycle(): void
    {
        $streamer = User::factory()->create(['name' => 'Streamer Host']);
        $viewer = User::factory()->create(['name' => 'Live Viewer']);

        // Start Live Broadcast
        $startRes = $this->actingAs($streamer)->postJson('/api/v1/live/streams', [
            'title' => 'Acoustic Live Music Session 🎸',
            'description' => 'Playing acoustic guitar songs and answering questions live.',
        ]);
        $startRes->assertSuccessful();
        $streamId = $startRes->json('data.id');
        $this->assertNotNull($streamId);

        // Viewer Joins Stream
        $joinRes = $this->actingAs($viewer)->postJson("/api/v1/live/streams/{$streamId}/join", [
            'session_id' => 'viewer_session_uuid_123',
        ]);
        $joinRes->assertSuccessful();

        // Viewer sends real-time comment
        $commentRes = $this->actingAs($viewer)->postJson("/api/v1/live/streams/{$streamId}/comments", [
            'comment' => 'So beautiful! ❤️',
        ]);
        $commentRes->assertSuccessful();

        // Viewer sends reaction
        $reactRes = $this->actingAs($viewer)->postJson("/api/v1/live/streams/{$streamId}/reactions", [
            'reaction_type' => 'love',
        ]);
        $reactRes->assertSuccessful();

        // Viewer Leaves Stream
        $leaveRes = $this->actingAs($viewer)->postJson("/api/v1/live/streams/{$streamId}/leave", [
            'session_id' => 'viewer_session_uuid_123',
        ]);
        $leaveRes->assertSuccessful();

        // Streamer Ends Stream
        $endRes = $this->actingAs($streamer)->postJson("/api/v1/live/streams/{$streamId}/end");
        $endRes->assertSuccessful();
    }
}
