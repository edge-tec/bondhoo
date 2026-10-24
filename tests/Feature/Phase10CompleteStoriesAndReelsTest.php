<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Reel;
use App\Models\Story;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Phase10CompleteStoriesAndReelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name ?? 'Creator',
            'last_name' => $user->last_name ?? 'User',
        ]);

        return $user;
    }

    protected function createVideoMedia(User $user, string $collection = 'reel'): Media
    {
        return Media::create([
            'user_id' => $user->id,
            'collection' => $collection,
            'disk' => 'public',
            'original_path' => "uploads/{$collection}_sample.mp4",
            'thumbnail_path' => "uploads/{$collection}_thumb.webp",
            'mime_type' => 'video/mp4',
            'size' => 2097152,
            'processing_status' => 'ready',
            'metadata' => ['duration' => 15.0],
            'width' => 720,
            'height' => 1280,
        ]);
    }

    /**
     * Requirement 10.1: Story Creation with exactly 24-hour expiration lifecycle.
     */
    public function test_story_creation_and_24h_lifecycle(): void
    {
        $now = Carbon::parse('2026-10-10 12:00:00');
        Carbon::setTestNow($now);

        $author = $this->createUser();

        $response = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v2/stories', [
                'type' => 'text',
                'content' => 'Ephemeral morning update!',
                'background_color' => '#1877F2',
                'privacy' => 'public',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Ephemeral morning update!')
            ->assertJsonPath('data.type', 'text');

        $storyId = $response->json('data.id');
        $story = Story::findOrFail($storyId);

        $this->assertEquals('2026-10-10 12:00:00', $story->published_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-10-11 12:00:00', $story->expires_at->format('Y-m-d H:i:s'));
        $this->assertFalse($story->is_expired);
        $this->assertTrue($story->isActive());

        // Fast forward 24 hours and 1 minute
        Carbon::setTestNow($now->copy()->addHours(24)->addMinute());

        // Run artisan expiry command
        Artisan::call('stories:expire');

        $story->refresh();
        $this->assertTrue((bool) $story->is_expired);
        $this->assertFalse($story->isActive());

        // Attempting to show expired story returns 410 Gone
        $showRes = $this->actingAs($author, 'sanctum')->getJson("/api/v2/stories/{$storyId}");
        $showRes->assertStatus(410);

        Carbon::setTestNow();
    }

    /**
     * Requirement 10.2: Active Stories Tray Feed and Block Isolation.
     */
    public function test_stories_tray_feed_and_block_isolation(): void
    {
        $viewer = $this->createUser();
        $friend = $this->createUser();
        $blockedUser = $this->createUser();

        // 1. Viewer blocks $blockedUser
        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v1/users/{$blockedUser->id}/block")
            ->assertStatus(200);

        // 2. Friend creates active story
        $this->actingAs($friend, 'sanctum')->postJson('/api/v2/stories', [
            'type' => 'text',
            'content' => 'Friend active story',
            'privacy' => 'public',
        ])->assertStatus(201);

        // 3. Blocked user creates active story
        $this->actingAs($blockedUser, 'sanctum')->postJson('/api/v2/stories', [
            'type' => 'text',
            'content' => 'Blocked active story',
            'privacy' => 'public',
        ])->assertStatus(201);

        // Fetch feed as viewer
        $feedRes = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/stories/feed');
        $feedRes->assertStatus(200);

        $authorsInTray = collect($feedRes->json('data'))->pluck('user.id')->all();

        $this->assertContains($friend->id, $authorsInTray);
        $this->assertNotContains($blockedUser->id, $authorsInTray);
    }

    /**
     * Requirement 10.3: Story Interactions: View recording, Reactions, and Direct Replies.
     */
    public function test_story_interactions_views_reactions_and_replies(): void
    {
        $author = $this->createUser();
        $viewer = $this->createUser();

        $createRes = $this->actingAs($author, 'sanctum')->postJson('/api/v2/stories', [
            'type' => 'text',
            'content' => 'React to this story!',
            'privacy' => 'public',
        ]);
        $storyId = $createRes->json('data.id');

        // 1. Record view
        $viewRes = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/stories/{$storyId}/view");
        $viewRes->assertStatus(200)->assertJsonPath('success', true);

        // Author checks viewer list
        $viewersRes = $this->actingAs($author, 'sanctum')
            ->getJson("/api/v2/stories/{$storyId}/viewers");
        $viewersRes->assertStatus(200);
        $this->assertCount(1, $viewersRes->json('data'));
        $this->assertEquals($viewer->id, $viewersRes->json('data.0.user.id'));

        // 2. Viewer reacts to story
        $reactRes = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/stories/{$storyId}/react", [
                'type' => 'love',
            ]);
        $reactRes->assertStatus(200)->assertJsonPath('success', true);
        $this->assertEquals(1, Story::find($storyId)->reactions_count);

        // 3. Viewer replies to story (sends DM)
        $replyRes = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/stories/{$storyId}/reply", [
                'message' => 'Awesome story, loved it!',
            ]);
        $replyRes->assertStatus(201)->assertJsonPath('success', true);
        $this->assertEquals(1, Story::find($storyId)->replies_count);
    }

    /**
     * Requirement 10.4: Reels Creation, Publishing, and 9:16 Aspect Ratio.
     */
    public function test_reels_creation_and_publishing(): void
    {
        $creator = $this->createUser();
        $media = $this->createVideoMedia($creator);

        // Create draft reel
        $response = $this->actingAs($creator, 'sanctum')
            ->postJson('/api/v2/reels', [
                'media_id' => $media->id,
                'caption' => 'Epic dance performance #reels',
                'crop_aspect' => '9:16',
                'privacy' => 'public',
                'is_draft' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.caption', 'Epic dance performance #reels')
            ->assertJsonPath('data.crop_aspect', '9:16');

        $reelId = $response->json('data.id');
        $reel = Reel::findOrFail($reelId);
        $this->assertTrue((bool) $reel->is_draft);

        // Publish draft reel
        $pubRes = $this->actingAs($creator, 'sanctum')
            ->postJson("/api/v2/reels/{$reelId}/publish");
        $pubRes->assertStatus(200)->assertJsonPath('success', true);

        $reel->refresh();
        $this->assertFalse((bool) $reel->is_draft);
        $this->assertTrue($reel->isActive());
    }

    /**
     * Requirement 10.5: Reels Feed, View Count, Comments, Likes, and Saves.
     */
    public function test_reels_feed_and_social_interactions(): void
    {
        $creator = $this->createUser();
        $viewer = $this->createUser();
        $media = $this->createVideoMedia($creator);

        $reel = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Trending comedy reel',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'privacy' => 'public',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'allow_comments' => true,
        ]);

        // 1. View reel in feed
        $feedRes = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/reels/feed');
        $feedRes->assertStatus(200);
        $feedIds = collect($feedRes->json('data'))->pluck('id')->all();
        $this->assertContains($reel->id, $feedIds);

        // 2. Record view
        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/view")
            ->assertStatus(200);
        $this->assertEquals(1, $reel->fresh()->views_count);

        // 3. Like/React to reel
        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/react")
            ->assertStatus(200);
        $this->assertEquals(1, $reel->fresh()->likes_count);

        // 4. Comment on reel
        $commentRes = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/comments", [
                'comment' => 'Hilarious video!',
            ]);
        $commentRes->assertStatus(201);
        $this->assertEquals(1, $reel->fresh()->comments_count);

        // 5. Bookmark/Save reel
        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/save")
            ->assertStatus(200);
        $this->assertEquals(1, $reel->fresh()->saves_count);

        // 6. Share reel
        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/share")
            ->assertStatus(200);
        $this->assertEquals(1, $reel->fresh()->shares_count);
    }

    /**
     * Requirement 10.6: Deletion Authorization (Author can delete, Stranger is forbidden).
     */
    public function test_story_and_reel_deletion_authorization(): void
    {
        $creator = $this->createUser();
        $stranger = $this->createUser();

        $story = Story::create([
            'user_id' => $creator->id,
            'type' => 'text',
            'content' => 'Story to delete',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        $reel = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Reel to delete',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        // Stranger tries to delete story -> 403 Forbidden
        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/v2/stories/{$story->id}")
            ->assertStatus(403);
        $this->assertNotNull(Story::find($story->id));

        // Stranger tries to delete reel -> 403 Forbidden
        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/v2/reels/{$reel->id}")
            ->assertStatus(403);
        $this->assertNotNull(Reel::find($reel->id));

        // Creator deletes story -> Success
        $this->actingAs($creator, 'sanctum')
            ->deleteJson("/api/v2/stories/{$story->id}")
            ->assertStatus(200);
        $this->assertNull(Story::find($story->id));

        // Creator deletes reel -> Success
        $this->actingAs($creator, 'sanctum')
            ->deleteJson("/api/v2/reels/{$reel->id}")
            ->assertStatus(200);
        $this->assertNull(Reel::find($reel->id));
    }
}
