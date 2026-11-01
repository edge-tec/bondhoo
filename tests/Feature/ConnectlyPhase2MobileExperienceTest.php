<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectlyPhase2MobileExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->user = User::factory()->create([
            'name' => 'Mizanur Rahman',
            'username' => 'mizanur',
            'email' => 'mizanur@connectly.app',
        ]);
    }

    public function test_feed_endpoint_serves_for_you_and_categories(): void
    {
        $author = User::factory()->create(['name' => 'Afrin Akter']);
        Post::factory()->create([
            'user_id' => $author->id,
            'content' => 'Life is better when you are surrounded by good people. ❤️',
            'audience' => 'public',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/feed?tab=for_you&per_page=5');
        $response->assertSuccessful();
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_friends_suggestions_and_friend_request_lifecycle(): void
    {
        $targetUser = User::factory()->create(['name' => 'Tania Akter']);

        // 1. Suggestions
        $suggestionsRes = $this->actingAs($this->user)->getJson('/api/v1/friends/suggestions');
        $suggestionsRes->assertSuccessful();

        // 2. Send Friend Request
        $requestRes = $this->actingAs($this->user)->postJson("/api/v1/friends/{$targetUser->id}/request");
        $requestRes->assertSuccessful();

        // 3. Accept Friend Request from target user's perspective
        $acceptRes = $this->actingAs($targetUser)->postJson("/api/v1/friends/{$this->user->id}/accept");
        $acceptRes->assertSuccessful();
    }

    public function test_business_pages_discovery_and_follow_endpoint(): void
    {
        $pageRes = $this->actingAs($this->user)->postJson('/api/v1/pages', [
            'name' => 'Tech World BD',
            'category' => 'Technology',
            'bio' => 'Your daily dose of tech trends and gadget reviews.',
        ]);
        $pageRes->assertSuccessful();
        $pageId = $pageRes->json('data.id');

        $pagesListRes = $this->actingAs($this->user)->getJson('/api/v1/pages');
        $pagesListRes->assertSuccessful();

        $follower = User::factory()->create();
        $followRes = $this->actingAs($follower)->postJson("/api/v1/pages/{$pageId}/follow");
        $followRes->assertSuccessful();
    }

    public function test_events_discovery_and_rsvp_endpoints(): void
    {
        $eventRes = $this->actingAs($this->user)->postJson('/api/v1/events', [
            'title' => 'Music Festival 2026',
            'location_type' => 'venue',
            'address' => 'Dhaka Arena, Dhaka',
            'start_time' => now()->addDays(15)->toIso8601String(),
            'end_time' => now()->addDays(15)->addHours(5)->toIso8601String(),
        ]);
        $eventRes->assertSuccessful();
        $eventId = $eventRes->json('data.event.id') ?? $eventRes->json('data.id') ?? $eventRes->json('id');

        $eventsRes = $this->actingAs($this->user)->getJson('/api/v1/events?filter=discover');
        $eventsRes->assertSuccessful();

        $rsvpRes = $this->actingAs($this->user)->postJson("/api/v1/events/{$eventId}/rsvp", [
            'status' => 'going',
        ]);
        $rsvpRes->assertSuccessful();
    }

    public function test_post_reactions_support_six_reaction_types(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'audience' => 'public',
            'status' => 'published',
        ]);
        $reactions = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

        foreach ($reactions as $reactionType) {
            $res = $this->actingAs($this->user)->postJson("/api/v1/posts/{$post->id}/react", [
                'type' => $reactionType,
            ]);
            $res->assertSuccessful();
            $this->assertEquals($reactionType, $res->json('data.type'));
        }
    }
}
