<?php

namespace Tests\Feature;

use App\Models\BlockedUser;
use App\Models\Media;
use App\Models\Reel;
use App\Models\Story;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EphemeralStoriesAndReelsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create a ready video media.
     */
    protected function createVideoMedia(User $user): Media
    {
        return Media::create([
            'user_id' => $user->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'uploads/test_reel.mp4',
            'thumbnail_path' => 'uploads/test_reel_thumb.webp',
            'mime_type' => 'video/mp4',
            'size' => 1048576,
            'processing_status' => 'ready',
            'metadata' => ['duration' => 15.0],
            'width' => 720,
            'height' => 1280,
        ]);
    }

    public function test_story_and_reel_are_assigned_exact_24_hour_lifecycle_on_creation(): void
    {
        $now = Carbon::parse('2026-10-07 10:00:00');
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $media = $this->createVideoMedia($user);

        // 1. Reel
        $reelResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/reels', [
                'media_id' => $media->id,
                'caption' => '২৪ ঘণ্টার সক্রিয় রিল',
                'privacy' => 'public',
            ]);

        $reelResponse->assertStatus(201);
        $reelId = $reelResponse->json('data.id');
        $reel = Reel::findOrFail($reelId);

        $this->assertEquals('2026-10-07 10:00:00', $reel->published_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-10-08 10:00:00', $reel->expires_at->format('Y-m-d H:i:s'));
        $this->assertFalse($reel->is_expired);
        $this->assertFalse($reel->isExpired());
        $this->assertTrue($reel->isActive());

        // 2. Story
        $storyResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/stories', [
                'type' => 'text',
                'content' => '২৪ ঘণ্টার সক্রিয় স্টোরি',
                'privacy' => 'public',
            ]);

        $storyResponse->assertStatus(201);
        $storyId = $storyResponse->json('data.id');
        $story = Story::findOrFail($storyId);

        $this->assertEquals('2026-10-07 10:00:00', $story->published_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-10-08 10:00:00', $story->expires_at->format('Y-m-d H:i:s'));
        $this->assertFalse($story->is_expired);
        $this->assertFalse($story->isExpired());
        $this->assertTrue($story->isActive());

        Carbon::setTestNow();
    }

    public function test_exact_24_hour_boundary_precision(): void
    {
        $publishedAt = Carbon::parse('2026-10-07 12:00:00');
        Carbon::setTestNow($publishedAt);

        $user = User::factory()->create();
        $media = $this->createVideoMedia($user);

        $reel = Reel::create([
            'user_id' => $user->id,
            'caption' => 'টাইম বাউন্ডারি টেস্ট',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'published_at' => $publishedAt,
            'expires_at' => $publishedAt->copy()->addHours(24),
            'is_expired' => false,
        ]);

        $story = Story::create([
            'user_id' => $user->id,
            'type' => 'text',
            'content' => 'টাইম বাউন্ডারি স্টোরি',
            'status' => Story::STATUS_READY,
            'published_at' => $publishedAt,
            'expires_at' => $publishedAt->copy()->addHours(24),
            'is_expired' => false,
        ]);

        // Boundary Point A: T + 23:59:59 (1 second before 24h) -> ACTIVE
        Carbon::setTestNow($publishedAt->copy()->addHours(23)->addMinutes(59)->addSeconds(59));
        $this->assertFalse($reel->fresh()->isExpired());
        $this->assertTrue($reel->fresh()->isActive());
        $this->assertFalse($story->fresh()->isExpired());
        $this->assertTrue($story->fresh()->isActive());

        $this->assertTrue(Reel::active()->where('id', $reel->id)->exists());
        $this->assertTrue(Story::active()->where('id', $story->id)->exists());

        // Boundary Point B: T + 24:00:00 (exact 24-hour mark) -> EXPIRED
        Carbon::setTestNow($publishedAt->copy()->addHours(24));
        $this->assertTrue($reel->fresh()->isExpired());
        $this->assertFalse($reel->fresh()->isActive());
        $this->assertTrue($story->fresh()->isExpired());
        $this->assertFalse($story->fresh()->isActive());

        $this->assertFalse(Reel::active()->where('id', $reel->id)->exists());
        $this->assertFalse(Story::active()->where('id', $story->id)->exists());

        // Boundary Point C: T + 24:00:01 (1 second after 24h) -> EXPIRED
        Carbon::setTestNow($publishedAt->copy()->addHours(24)->addSecond());
        $this->assertTrue($reel->fresh()->isExpired());
        $this->assertFalse($reel->fresh()->isActive());
        $this->assertTrue($story->fresh()->isExpired());
        $this->assertFalse($story->fresh()->isActive());

        Carbon::setTestNow();
    }

    public function test_direct_api_access_to_expired_reel_returns_410_gone(): void
    {
        $publishedAt = Carbon::parse('2026-10-07 10:00:00');
        Carbon::setTestNow($publishedAt);

        $user = User::factory()->create();
        $viewer = User::factory()->create();

        $reel = Reel::create([
            'user_id' => $user->id,
            'caption' => 'মেয়াদোত্তীর্ণ রিল',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'published_at' => $publishedAt,
            'expires_at' => $publishedAt->copy()->addHours(24),
            'is_expired' => false,
        ]);

        // Move clock past 24 hours
        Carbon::setTestNow($publishedAt->copy()->addHours(25));

        // 1. GET show endpoint returns 410
        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/v2/reels/{$reel->id}");

        $response->assertStatus(410)
            ->assertJsonPath('is_expired', true);

        // 2. Reacting to expired reel returns 410
        $reactResponse = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/react", ['type' => 'like']);

        $reactResponse->assertStatus(410)
            ->assertJsonPath('is_expired', true);

        // 3. Commenting on expired reel returns 410
        $commentResponse = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/comments", ['comment' => 'লেট কমেন্ট']);

        $commentResponse->assertStatus(410)
            ->assertJsonPath('is_expired', true);

        // 4. Recording view on expired reel returns 410
        $viewResponse = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/view", ['watch_time_seconds' => 5.0]);

        $viewResponse->assertStatus(410)
            ->assertJsonPath('is_expired', true);

        Carbon::setTestNow();
    }

    public function test_direct_api_access_to_expired_story_returns_410_gone(): void
    {
        $publishedAt = Carbon::parse('2026-10-07 10:00:00');
        Carbon::setTestNow($publishedAt);

        $user = User::factory()->create();
        $viewer = User::factory()->create();

        $story = Story::create([
            'user_id' => $user->id,
            'type' => 'text',
            'content' => 'মেয়াদোত্তীর্ণ স্টোরি',
            'status' => Story::STATUS_READY,
            'published_at' => $publishedAt,
            'expires_at' => $publishedAt->copy()->addHours(24),
            'is_expired' => false,
        ]);

        // Move clock past 24 hours
        Carbon::setTestNow($publishedAt->copy()->addHours(25));

        // 1. GET show endpoint returns 410
        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/v2/stories/{$story->id}");

        $response->assertStatus(410)
            ->assertJsonPath('is_expired', true);

        // 2. Reacting returns 410
        $reactResponse = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/stories/{$story->id}/react", ['type' => 'love']);

        $reactResponse->assertStatus(410)
            ->assertJsonPath('is_expired', true);

        // 3. Replying returns 410
        $replyResponse = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/stories/{$story->id}/reply", ['message' => 'লেট রিপ্লাই']);

        $replyResponse->assertStatus(410)
            ->assertJsonPath('is_expired', true);

        // 4. Recording view returns 410
        $viewResponse = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/stories/{$story->id}/view");

        $viewResponse->assertStatus(410)
            ->assertJsonPath('is_expired', true);

        Carbon::setTestNow();
    }

    public function test_editing_active_reel_does_not_reset_or_extend_expiration(): void
    {
        $publishedAt = Carbon::parse('2026-10-07 10:00:00');
        Carbon::setTestNow($publishedAt);

        $user = User::factory()->create();

        $reel = Reel::create([
            'user_id' => $user->id,
            'caption' => 'আসল ক্যাপশন',
            'location' => 'ঢাকা',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'published_at' => $publishedAt,
            'expires_at' => $publishedAt->copy()->addHours(24),
            'is_expired' => false,
        ]);

        $originalExpiresAt = $reel->expires_at->format('Y-m-d H:i:s');
        $originalPublishedAt = $reel->published_at->format('Y-m-d H:i:s');

        // Move 10 hours into the 24-hour cycle
        Carbon::setTestNow($publishedAt->copy()->addHours(10));

        // Edit reel caption & location
        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v2/reels/{$reel->id}", [
                'caption' => 'আপডেট করা ক্যাপশন (১০ ঘণ্টা পর)',
                'location' => 'চট্টগ্রাম',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.caption', 'আপডেট করা ক্যাপশন (১০ ঘণ্টা পর)')
            ->assertJsonPath('data.location', 'চট্টগ্রাম');

        $freshReel = $reel->fresh();

        // STRICT ASSERTION: published_at and expires_at remain EXACTLY as originally set!
        $this->assertEquals($originalPublishedAt, $freshReel->published_at->format('Y-m-d H:i:s'));
        $this->assertEquals($originalExpiresAt, $freshReel->expires_at->format('Y-m-d H:i:s'));

        // Time remaining should now be 14 hours, NOT 24 hours!
        $timeRemaining = $response->json('data.time_remaining_seconds');
        $this->assertLessThanOrEqual(14 * 3600, $timeRemaining);
        $this->assertGreaterThan(13 * 3600, $timeRemaining);

        Carbon::setTestNow();
    }

    public function test_draft_or_processing_reel_does_not_start_lifecycle_until_published(): void
    {
        $now = Carbon::parse('2026-10-07 10:00:00');
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $media = $this->createVideoMedia($user);

        // 1. Create a draft reel
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/reels', [
                'media_id' => $media->id,
                'caption' => 'ড্রাফট রিল',
                'is_draft' => true,
            ]);

        $response->assertStatus(201);
        $reel = Reel::findOrFail($response->json('data.id'));

        $this->assertTrue((bool) $reel->is_draft);
        $this->assertNull($reel->published_at);
        $this->assertNull($reel->expires_at);

        // Advance time 5 days
        Carbon::setTestNow($now->copy()->addDays(5));

        // 2. Now publish the draft reel
        $publishResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/publish");

        $publishResponse->assertStatus(200)
            ->assertJsonPath('data.is_draft', false);

        $publishedReel = $reel->fresh();
        $this->assertFalse((bool) $publishedReel->is_draft);

        // 24-hour lifecycle begins right NOW (at publication time, day 5)
        $this->assertEquals($now->copy()->addDays(5)->format('Y-m-d H:i:s'), $publishedReel->published_at->format('Y-m-d H:i:s'));
        $this->assertEquals($now->copy()->addDays(6)->format('Y-m-d H:i:s'), $publishedReel->expires_at->format('Y-m-d H:i:s'));
        $this->assertFalse($publishedReel->is_expired);
        $this->assertTrue($publishedReel->isActive());

        Carbon::setTestNow();
    }

    public function test_artisan_expire_commands_broadcast_and_update_state(): void
    {
        $publishedAt = Carbon::parse('2026-10-07 10:00:00');
        Carbon::setTestNow($publishedAt);

        $user = User::factory()->create();

        $reel = Reel::create([
            'user_id' => $user->id,
            'caption' => 'কমান্ড টেস্ট রিল',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'published_at' => $publishedAt,
            'expires_at' => $publishedAt->copy()->addHours(24),
            'is_expired' => false,
        ]);

        $story = Story::create([
            'user_id' => $user->id,
            'type' => 'text',
            'content' => 'কমান্ড টেস্ট স্টোরি',
            'status' => Story::STATUS_READY,
            'published_at' => $publishedAt,
            'expires_at' => $publishedAt->copy()->addHours(24),
            'is_expired' => false,
        ]);

        // Advance past 24 hours
        Carbon::setTestNow($publishedAt->copy()->addHours(25));

        // Run artisan commands
        Artisan::call('reels:expire');
        Artisan::call('stories:expire');

        $this->assertTrue((bool) $reel->fresh()->is_expired);
        $this->assertTrue((bool) $story->fresh()->is_expired);

        Carbon::setTestNow();
    }

    public function test_privacy_and_block_restrictions_enforced_on_reels_and_stories(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $blockedUser = User::factory()->create();

        // Block user
        BlockedUser::create([
            'user_id' => $user->id,
            'identifier' => (string) $blockedUser->id,
            'reason' => 'user_block',
            'blocked_type' => 'user',
            'is_active' => true,
        ]);

        $reel = Reel::create([
            'user_id' => $user->id,
            'caption' => 'প্রাইভেসি রিল',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'privacy' => 'friends',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'is_expired' => false,
        ]);

        $story = Story::create([
            'user_id' => $user->id,
            'type' => 'text',
            'content' => 'প্রাইভেসি স্টোরি',
            'status' => Story::STATUS_READY,
            'privacy' => 'friends',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'is_expired' => false,
        ]);

        // Stranger (not friends) viewing friends-only reel -> 403
        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v2/reels/{$reel->id}")
            ->assertStatus(403);

        // Stranger viewing friends-only story -> 403
        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v2/stories/{$story->id}")
            ->assertStatus(403);

        // Blocked user viewing -> 403
        $this->actingAs($blockedUser, 'sanctum')
            ->getJson("/api/v2/reels/{$reel->id}")
            ->assertStatus(403);

        $this->actingAs($blockedUser, 'sanctum')
            ->getJson("/api/v2/stories/{$story->id}")
            ->assertStatus(403);
    }
}
