<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Group;
use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase10CompleteEventsSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(string $username, string $name, string $role = 'USER'): User
    {
        $user = User::factory()->create([
            'username' => $username,
            'name' => $name,
            'email' => "{$username}@example.com",
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'avatar_url' => "https://ui-avatars.com/api/?name={$username}",
                'bio' => "Bio of {$name}",
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'dark_mode' => true,
            ]
        );

        $roleModel = Role::where('name', $role)->first();
        if ($roleModel) {
            $user->roles()->syncWithoutDetaching([$roleModel->id]);
        }

        return $user;
    }

    /**
     * 1. Test Event Creation with Location Types, Metadata, and Automatic Host RSVP
     */
    public function test_event_creation_with_metadata_and_host_rsvp(): void
    {
        $host = $this->createUser('event_host', 'Event Host');

        $payload = [
            'title' => 'Google Developer Group DevFest 2026',
            'description' => 'The biggest annual conference for AI, cloud, and mobile developers.',
            'start_time' => now()->addDays(10)->setTime(10, 0)->toIso8601String(),
            'end_time' => now()->addDays(10)->setTime(18, 0)->toIso8601String(),
            'location_type' => 'hybrid',
            'address' => 'Bangabandhu International Conference Centre, Dhaka',
            'online_link' => 'https://meet.google.com/devfest-2026',
            'cover_image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=80',
            'max_attendees' => 1500,
        ];

        $res = $this->actingAs($host, 'sanctum')->postJson('/api/v1/events', $payload);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.event.title', 'Google Developer Group DevFest 2026')
            ->assertJsonPath('data.event.location_type', 'hybrid');

        $eventId = $res->json('data.event.id');
        $this->assertDatabaseHas('events', [
            'id' => $eventId,
            'creator_id' => $host->id,
            'title' => 'Google Developer Group DevFest 2026',
            'location_type' => 'hybrid',
            'going_count' => 1,
        ]);

        // Host is automatically marked as going
        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $eventId,
            'user_id' => $host->id,
            'status' => 'going',
        ]);
    }

    /**
     * 2. Test Event Filtering by Location Type and Keyword Search
     */
    public function test_event_filtering_and_search(): void
    {
        $host = $this->createUser('search_host', 'Search Host');
        $viewer = $this->createUser('event_viewer', 'Event Viewer');

        Event::create([
            'creator_id' => $host->id,
            'title' => 'Global AI Hackathon 2026',
            'description' => 'Build innovative agentic AI applications with Gemini.',
            'start_time' => now()->addDays(5),
            'location_type' => 'online',
            'online_link' => 'https://meet.google.com/hackathon-2026',
            'going_count' => 1,
        ]);

        Event::create([
            'creator_id' => $host->id,
            'title' => 'Dhaka Art & Photography Expo',
            'description' => 'Physical gallery showcase of contemporary visual artists.',
            'start_time' => now()->addDays(7),
            'location_type' => 'venue',
            'address' => 'Drik Gallery, Panthapath, Dhaka',
            'going_count' => 1,
        ]);

        // 1. Filter by location_type = online
        $onlineRes = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/events?location_type=online');
        $onlineRes->assertStatus(200);
        $onlineItems = $onlineRes->json('data.events') ?? $onlineRes->json('events');
        $this->assertCount(1, $onlineItems);
        $this->assertEquals('Global AI Hackathon 2026', $onlineItems[0]['title']);

        // 2. Keyword search
        $searchRes = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/events?search=Photography');
        $searchRes->assertStatus(200);
        $searchItems = $searchRes->json('data.events') ?? $searchRes->json('events');
        $this->assertCount(1, $searchItems);
        $this->assertEquals('Dhaka Art & Photography Expo', $searchItems[0]['title']);
    }

    /**
     * 3. Test RSVP Lifecycle: Going, Interested, and Toggle-Off Removal
     */
    public function test_event_rsvp_lifecycle_and_counts(): void
    {
        $host = $this->createUser('rsvp_host', 'RSVP Host');
        $attendee = $this->createUser('attendee_user', 'Attendee User');

        $event = Event::create([
            'creator_id' => $host->id,
            'title' => 'Connectly Engineering Summit',
            'start_time' => now()->addDays(14),
            'location_type' => 'venue',
            'going_count' => 1,
            'interested_count' => 0,
        ]);

        // 1. Attendee RSVPs as 'going'
        $goingRes = $this->actingAs($attendee, 'sanctum')->postJson("/api/v1/events/{$event->id}/rsvp", [
            'status' => 'going',
        ]);
        $goingRes->assertStatus(200)
            ->assertJsonPath('data.going_count', 1)
            ->assertJsonPath('data.user_rsvp', 'going');

        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $attendee->id,
            'status' => 'going',
        ]);

        // 2. Attendee changes RSVP to 'interested'
        $interestedRes = $this->actingAs($attendee, 'sanctum')->postJson("/api/v1/events/{$event->id}/rsvp", [
            'status' => 'interested',
        ]);
        $interestedRes->assertStatus(200)
            ->assertJsonPath('data.interested_count', 1)
            ->assertJsonPath('data.user_rsvp', 'interested');

        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $attendee->id,
            'status' => 'interested',
        ]);

        // 3. Attendee clicks same status to toggle off / cancel RSVP
        $cancelRes = $this->actingAs($attendee, 'sanctum')->postJson("/api/v1/events/{$event->id}/rsvp", [
            'status' => 'interested',
        ]);
        $cancelRes->assertStatus(200)
            ->assertJsonPath('data.interested_count', 0)
            ->assertJsonPath('data.user_rsvp', null);

        $this->assertDatabaseMissing('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $attendee->id,
        ]);
    }

    /**
     * 4. Test Group Event Creation and Listing
     */
    public function test_group_event_integration(): void
    {
        $admin = $this->createUser('group_event_admin', 'Group Event Admin');
        $group = Group::create([
            'name' => 'Mobile Dev Community',
            'slug' => 'mobile-dev-community',
            'creator_id' => $admin->id,
            'privacy' => 'public',
        ]);

        $res = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/groups/{$group->id}/events", [
            'title' => 'Android Jetpack Compose Meetup #12',
            'start_time' => now()->addDays(8)->toIso8601String(),
            'location' => 'Gulshan Club, Dhaka',
        ]);
        $res->assertStatus(201);

        $listRes = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/groups/{$group->id}/events");
        $listRes->assertStatus(200);
        $this->assertNotEmpty($listRes->json('data'));
    }

    /**
     * 5. Test Page Event Creation and RSVP
     */
    public function test_page_event_integration(): void
    {
        $owner = $this->createUser('page_event_owner', 'Page Event Owner');
        $attendee = $this->createUser('page_event_attendee', 'Page Event Attendee');

        $page = Page::create([
            'name' => 'Music Academy BD',
            'slug' => 'music-academy-bd',
            'username' => 'music-academy-bd',
            'owner_id' => $owner->id,
        ]);

        $createRes = $this->actingAs($owner, 'sanctum')->postJson("/api/v2/pages/{$page->id}/events", [
            'title' => 'Classical Sitar Concert Live',
            'start_time' => now()->addDays(12)->toIso8601String(),
            'location_type' => 'venue',
            'location' => 'Chhayanaut Auditorium, Dhaka',
        ]);
        $createRes->assertStatus(201);
        $eventId = $createRes->json('data.id');

        // Attendee RSVPs to Page Event
        $rsvpRes = $this->actingAs($attendee, 'sanctum')->postJson("/api/v2/pages/{$page->id}/events/{$eventId}/rsvp", [
            'status' => 'going',
        ]);
        $rsvpRes->assertStatus(200);
    }

    /**
     * 6. Test Web Route Resolution for Events
     */
    public function test_events_web_routes_and_navigation(): void
    {
        $user = $this->createUser('web_events_user', 'Web Events User');

        // /events route redirects to dashboard?tab=events
        $res = $this->actingAs($user, 'web')->get('/events');
        $res->assertStatus(302)
            ->assertRedirect('/dashboard?tab=events');

        // /dashboard?tab=events renders upcoming events grid view successfully
        $dashboardRes = $this->actingAs($user, 'web')->get('/dashboard?tab=events');
        $dashboardRes->assertStatus(200);
    }
}
