<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Reel;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4CompleteReelsSystemTest extends TestCase
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

    protected function createReel(User $creator, array $attributes = []): Reel
    {
        $media = Media::create([
            'user_id' => $creator->id,
            'original_path' => 'reels/reel_test.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024 * 1024 * 5, // 5MB
            'disk' => 'public',
        ]);

        $reel = Reel::create(array_merge([
            'user_id' => $creator->id,
            'caption' => 'Exciting sunset vibes #nature #beauty @alice',
            'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            'thumbnail_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80',
            'duration' => 30,
            'audio_title' => 'Golden Hour Instrumental',
            'audio_artist' => 'Sunset Producer',
            'views_count' => 0,
            'likes_count' => 0,
            'comments_count' => 0,
            'shares_count' => 0,
            'is_published' => true,
            'privacy' => 'public',
        ], $attributes));

        $media->update([
            'mediable_type' => Reel::class,
            'mediable_id' => $reel->id,
            'collection' => 'reel',
        ]);

        return $reel;
    }

    /**
     * 1. Test Reel Upload and Metadata Persistence
     */
    public function test_reel_creation_and_metadata_persistence(): void
    {
        $creator = $this->createUser('reel_creator', 'Reel Creator');

        $media = Media::create([
            'user_id' => $creator->id,
            'original_path' => 'reels/upload.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024 * 1024 * 2,
            'disk' => 'public',
        ]);

        $res = $this->actingAs($creator, 'sanctum')->postJson('/api/v2/reels', [
            'media_id' => $media->id,
            'caption' => 'My first professional short video! #connectly #tech',
            'audio_title' => 'Tech Beat 2026',
            'audio_artist' => 'Connectly Music',
            'privacy' => 'public',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('reels', [
            'user_id' => $creator->id,
            'caption' => 'My first professional short video! #connectly #tech',
            'audio_title' => 'Tech Beat 2026',
        ]);

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'mediable_type' => Reel::class,
            'collection' => 'reel',
        ]);
    }

    /**
     * 2. Test Feed Pagination, Tabs and View Count Increment
     */
    public function test_reel_feed_pagination_and_view_counting(): void
    {
        $creator = $this->createUser('creator_feed', 'Creator Feed');
        $viewer = $this->createUser('viewer_feed', 'Viewer Feed');

        $reel = $this->createReel($creator);

        // 1. Fetch For You feed
        $feedRes = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/reels?tab=for_you');
        $feedRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $reels = $feedRes->json('data.data') ?? $feedRes->json('data');
        $this->assertNotEmpty($reels);

        // 2. Increment view count
        $viewRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/reels/{$reel->id}/view");
        $viewRes->assertStatus(200);

        $reel->refresh();
        $this->assertEquals(1, $reel->views_count);
    }

    /**
     * 3. Test Like, Unlike, and Commenting on Reel
     */
    public function test_like_unlike_and_commenting_on_reel(): void
    {
        $creator = $this->createUser('creator_act', 'Creator Act');
        $viewer = $this->createUser('viewer_act', 'Viewer Act');

        $reel = $this->createReel($creator);

        // 1. Like / react reel
        $likeRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/reels/{$reel->id}/react", [
            'type' => 'like',
        ]);
        $likeRes->assertStatus(200)
            ->assertJsonPath('data.reacted', true);

        $reel->refresh();
        $this->assertEquals(1, $reel->likes_count);

        // 2. Unlike reel (toggle off)
        $unlikeRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/reels/{$reel->id}/react", [
            'type' => 'like',
        ]);
        $unlikeRes->assertStatus(200)
            ->assertJsonPath('data.reacted', false);

        $reel->refresh();
        $this->assertEquals(0, $reel->likes_count);

        // 3. Comment on reel
        $commentRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/reels/{$reel->id}/comments", [
            'comment' => 'Incredible vertical composition! 🔥',
        ]);
        $commentRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $reel->refresh();
        $this->assertEquals(1, $reel->comments_count);
    }

    /**
     * 4. Test Save Reel to Collections
     */
    public function test_save_and_unsave_reel(): void
    {
        $creator = $this->createUser('creator_save', 'Creator Save');
        $viewer = $this->createUser('viewer_save', 'Viewer Save');

        $reel = $this->createReel($creator);

        // Save reel
        $saveRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/reels/{$reel->id}/save");
        $saveRes->assertStatus(200);

        $isSaved = $saveRes->json('data.saved') ?? $saveRes->json('data.is_saved');
        $this->assertTrue((bool) $isSaved);

        // Verify Saved Tab contains the saved reel
        $savedTabRes = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/reels?tab=saved');
        $savedTabRes->assertStatus(200);
        $savedList = $savedTabRes->json('data.data') ?? $savedTabRes->json('data');
        $this->assertNotEmpty($savedList);
    }

    /**
     * 5. Test Reel Deletion and Authorization Security
     */
    public function test_reel_deletion_authorization(): void
    {
        $creator = $this->createUser('reel_owner', 'Reel Owner');
        $intruder = $this->createUser('reel_intruder', 'Reel Intruder');

        $reel = $this->createReel($creator);

        // 1. Intruder cannot delete creator's reel -> 403 Forbidden
        $intruderRes = $this->actingAs($intruder, 'sanctum')->deleteJson("/api/v2/reels/{$reel->id}");
        $intruderRes->assertStatus(403);
        $this->assertDatabaseHas('reels', ['id' => $reel->id]);

        // 2. Creator can delete their own reel
        $creatorRes = $this->actingAs($creator, 'sanctum')->deleteJson("/api/v2/reels/{$reel->id}");
        $creatorRes->assertStatus(200);
        $this->assertDatabaseMissing('reels', ['id' => $reel->id]);
    }

    /**
     * 6. Test Deep Link Support for Reels Web Page
     */
    public function test_reels_web_page_deep_link_resolution(): void
    {
        $creator = $this->createUser('web_creator', 'Web Creator');
        $reel = $this->createReel($creator);

        // Direct web route param resolution
        $res = $this->actingAs($creator, 'web')->get("/reels/{$reel->id}");
        $res->assertStatus(200);

        // Query param resolution
        $queryRes = $this->actingAs($creator, 'web')->get("/reels?reel={$reel->id}");
        $queryRes->assertStatus(200);
    }
}
