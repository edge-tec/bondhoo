<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase168StoriesAndReelsEcosystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Alice Storyteller',
            'username' => 'alicestory',
            'email' => 'alice@bondhoo.test',
        ]);

        $this->otherUser = User::factory()->create([
            'name' => 'Bob Reels',
            'username' => 'bobreels',
            'email' => 'bob@bondhoo.test',
        ]);
    }

    public function test_story_creation_sets_24_hour_expiration(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v2/stories', [
            'type' => 'text',
            'content' => 'Hello Bondhoo Story 24h expiration test!',
            'background_color' => '#6366F1',
            'font_family' => 'Roboto',
            'privacy' => 'public',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $storyId = $response->json('data.id');
        $this->assertNotNull($storyId);

        $story = Story::findOrFail($storyId);
        $this->assertFalse($story->is_expired);
        $this->assertFalse($story->is_archived);
        $this->assertNotNull($story->published_at);
        $this->assertNotNull($story->expires_at);

        $diffHours = $story->published_at->diffInHours($story->expires_at);
        $this->assertEquals(24, $diffHours);
    }

    public function test_expired_story_is_rejected_with_410_gone(): void
    {
        $story = Story::create([
            'user_id' => $this->user->id,
            'type' => 'text',
            'caption' => 'Expired Story',
            'published_at' => now()->subHours(25),
            'expires_at' => now()->subHours(1),
            'is_expired' => true,
            'status' => 'active',
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($this->otherUser, 'sanctum')->getJson("/api/v2/stories/{$story->id}");

        $response->assertStatus(410)
            ->assertJsonPath('error_code', 'STORY_EXPIRED');
    }

    public function test_story_owner_can_archive_and_view_private_archive(): void
    {
        $story = Story::create([
            'user_id' => $this->user->id,
            'type' => 'text',
            'caption' => 'Archived Story',
            'published_at' => now()->subHours(2),
            'expires_at' => now()->addHours(22),
            'is_expired' => false,
            'is_archived' => false,
            'status' => 'active',
            'privacy' => 'public',
        ]);

        // Unauthorized user cannot archive
        $unauthResp = $this->actingAs($this->otherUser, 'sanctum')->postJson("/api/v2/stories/{$story->id}/archive");
        $unauthResp->assertStatus(403);

        // Owner can archive
        $authResp = $this->actingAs($this->user, 'sanctum')->postJson("/api/v2/stories/{$story->id}/archive");
        $authResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue($story->fresh()->is_archived);

        // Owner can fetch archived stories
        $archiveListResp = $this->actingAs($this->user, 'sanctum')->getJson('/api/v2/stories/archived');
        $archiveListResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $ids = collect($archiveListResp->json('data'))->pluck('id')->toArray();
        $this->assertContains($story->id, $ids);
    }

    public function test_stories_expire_command_marks_stale_stories(): void
    {
        $staleStory = Story::create([
            'user_id' => $this->user->id,
            'type' => 'text',
            'caption' => 'Stale Story',
            'published_at' => now()->subHours(25),
            'expires_at' => now()->subHour(),
            'is_expired' => false,
            'status' => 'active',
            'privacy' => 'public',
        ]);

        $this->artisan('stories:expire')
            ->assertExitCode(0);

        $this->assertTrue($staleStory->fresh()->is_expired);
    }

    public function test_reel_creation_and_24_hour_expiration(): void
    {
        $media = Media::create([
            'user_id' => $this->user->id,
            'original_path' => 'reels/sample.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024000,
        ]);

        $reel = Reel::create([
            'user_id' => $this->user->id,
            'caption' => 'Exciting 24h Reel!',
            'duration' => 15,
            'width' => 1080,
            'height' => 1920,
            'aspect_ratio' => '9:16',
            'privacy' => 'public',
            'status' => Reel::STATUS_READY,
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'is_expired' => false,
            'is_archived' => false,
        ]);

        ReelMedia::create([
            'reel_id' => $reel->id,
            'media_id' => $media->id,
            'quality' => 'original',
            'video_path' => 'reels/sample.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024000,
        ]);

        $this->assertFalse($reel->is_expired);
        $this->assertFalse($reel->is_archived);
        $this->assertEquals(24, $reel->published_at->diffInHours($reel->expires_at));
    }

    public function test_expired_reel_returns_410_gone(): void
    {
        $reel = Reel::create([
            'user_id' => $this->user->id,
            'caption' => 'Expired Reel',
            'duration' => 15,
            'status' => Reel::STATUS_READY,
            'published_at' => now()->subHours(26),
            'expires_at' => now()->subHours(2),
            'is_expired' => true,
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($this->otherUser, 'sanctum')->getJson("/api/v2/reels/{$reel->id}");

        $response->assertStatus(410)
            ->assertJsonPath('error_code', 'REEL_EXPIRED');
    }

    public function test_reel_feeds_with_tabs(): void
    {
        $activeReel = Reel::create([
            'user_id' => $this->user->id,
            'caption' => 'Active Public Reel',
            'duration' => 10,
            'status' => Reel::STATUS_READY,
            'privacy' => 'public',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'is_expired' => false,
            'is_archived' => false,
        ]);

        // For You tab
        $respForYou = $this->actingAs($this->otherUser, 'sanctum')->getJson('/api/v2/reels?tab=for_you');
        $respForYou->assertStatus(200);

        // Following tab
        $respFollowing = $this->actingAs($this->otherUser, 'sanctum')->getJson('/api/v2/reels?tab=following');
        $respFollowing->assertStatus(200);

        // Friends tab
        $respFriends = $this->actingAs($this->otherUser, 'sanctum')->getJson('/api/v2/reels?tab=friends');
        $respFriends->assertStatus(200);

        // Popular tab
        $respPopular = $this->actingAs($this->otherUser, 'sanctum')->getJson('/api/v2/reels?tab=popular');
        $respPopular->assertStatus(200);

        // Latest tab
        $respLatest = $this->actingAs($this->otherUser, 'sanctum')->getJson('/api/v2/reels?tab=latest');
        $respLatest->assertStatus(200);
    }

    public function test_reel_archive_and_owner_private_archive(): void
    {
        $reel = Reel::create([
            'user_id' => $this->user->id,
            'caption' => 'Archive Test Reel',
            'duration' => 12,
            'status' => Reel::STATUS_READY,
            'privacy' => 'public',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'is_expired' => false,
            'is_archived' => false,
        ]);

        // Unauthorized user cannot archive
        $unauth = $this->actingAs($this->otherUser, 'sanctum')->postJson("/api/v2/reels/{$reel->id}/archive");
        $unauth->assertStatus(403);

        // Owner archives
        $auth = $this->actingAs($this->user, 'sanctum')->postJson("/api/v2/reels/{$reel->id}/archive");
        $auth->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue($reel->fresh()->is_archived);

        // Owner reads archived list
        $archivedList = $this->actingAs($this->user, 'sanctum')->getJson('/api/v2/reels/archived');
        $archivedList->assertStatus(200)
            ->assertJsonPath('success', true);

        $ids = collect($archivedList->json('data'))->pluck('id')->toArray();
        $this->assertContains($reel->id, $ids);
    }

    public function test_reels_expire_command_marks_stale_reels(): void
    {
        $staleReel = Reel::create([
            'user_id' => $this->user->id,
            'caption' => 'Stale Reel',
            'duration' => 20,
            'status' => Reel::STATUS_READY,
            'privacy' => 'public',
            'published_at' => now()->subHours(25),
            'expires_at' => now()->subHour(),
            'is_expired' => false,
            'is_archived' => false,
        ]);

        $this->artisan('reels:expire')
            ->assertExitCode(0);

        $this->assertTrue($staleReel->fresh()->is_expired);
    }
}
