<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase81EnterpriseMobileReelsExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Helper to create a Reel with attached video media.
     */
    protected function createReelWithVideo(User $author, array $overrides = []): Reel
    {
        $reel = Reel::create(array_merge([
            'user_id' => $author->id,
            'caption' => 'Phase 8.1 Mobile Reel #jugajug',
            'audio_title' => 'Original Audio - '.$author->name,
            'privacy' => 'public',
            'status' => Reel::STATUS_READY,
            'published_at' => now(),
            'is_draft' => false,
            'duration' => 15,
            'width' => 1080,
            'height' => 1920,
            'aspect_ratio' => '9:16',
        ], $overrides));

        $media = Media::create([
            'user_id' => $author->id,
            'mediable_type' => Reel::class,
            'mediable_id' => $reel->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'reels/sample-reel-'.$reel->id.'.mp4',
            'mime_type' => 'video/mp4',
            'width' => 1080,
            'height' => 1920,
            'size' => 1024000,
            'processing_status' => 'completed',
        ]);

        ReelMedia::create([
            'reel_id' => $reel->id,
            'media_id' => $media->id,
            'quality' => 'original',
            'video_path' => 'reels/sample-reel-'.$reel->id.'.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024000,
        ]);

        return $reel->fresh(['user.profile', 'media']);
    }

    public function test_reels_page_loads_inertia_props_and_supports_tabs(): void
    {
        $viewer = User::factory()->create([
            'username' => 'reels_viewer_81',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $creator = User::factory()->create([
            'username' => 'reels_creator_81',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $reel1 = $this->createReelWithVideo($creator, ['caption' => 'First Reel Video #one']);
        $reel2 = $this->createReelWithVideo($viewer, ['caption' => 'My Own Reel Video #two']);

        // 1. Default For You tab
        $response = $this->actingAs($viewer, 'web')->get('/reels');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reels')
            ->where('activeTab', 'for_you')
            ->has('reels.data', 2)
        );

        // 2. My Reels tab filters to viewer's own reels
        $myReelsResponse = $this->actingAs($viewer, 'web')->get('/reels?tab=my_reels');
        $myReelsResponse->assertStatus(200);
        $myReelsResponse->assertInertia(fn (Assert $page) => $page
            ->component('Reels')
            ->where('activeTab', 'my_reels')
            ->has('reels.data', 1)
            ->where('reels.data.0.id', $reel2->id)
        );
    }

    public function test_reels_deep_link_supports_both_route_param_and_reel_query_param(): void
    {
        $viewer = User::factory()->create([
            'username' => 'deeplink_viewer_81',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $reelA = $this->createReelWithVideo($viewer, ['caption' => 'Older Reel A']);
        $reelB = $this->createReelWithVideo($viewer, ['caption' => 'Newer Reel B']);

        // 1. Route parameter /reels/{id} places requested reel first
        $routeResponse = $this->actingAs($viewer, 'web')->get("/reels/{$reelA->id}");
        $routeResponse->assertStatus(200);
        $routeResponse->assertInertia(fn (Assert $page) => $page
            ->component('Reels')
            ->where('initialReelId', $reelA->id)
            ->where('reels.data.0.id', $reelA->id)
        );

        // 2. Query parameter /reels?reel={id} (used by Profile, PostCard, and Messenger shared links) places requested reel first
        $queryResponse = $this->actingAs($viewer, 'web')->get("/reels?reel={$reelA->id}");
        $queryResponse->assertStatus(200);
        $queryResponse->assertInertia(fn (Assert $page) => $page
            ->component('Reels')
            ->where('initialReelId', $reelA->id)
            ->where('reels.data.0.id', $reelA->id)
        );
    }

    public function test_reels_v2_api_pagination_and_interaction_endpoints_work_end_to_end(): void
    {
        $viewer = User::factory()->create([
            'username' => 'api_reel_user_81',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $reel1 = $this->createReelWithVideo($viewer, ['caption' => 'API Reel 1']);
        $reel2 = $this->createReelWithVideo($viewer, ['caption' => 'API Reel 2']);
        $reel3 = $this->createReelWithVideo($viewer, ['caption' => 'API Reel 3']);

        // 1. Paginated Feed API (/api/v2/reels)
        $feedRes = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/reels?per_page=2&page=1');
        $feedRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1);

        $page2Res = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/reels?per_page=2&page=2');
        $page2Res->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);

        // 2. Record View (/api/v2/reels/{id}/view)
        $viewRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/reels/{$reel1->id}/view", [
            'watch_time_seconds' => 4.2,
        ]);
        $viewRes->assertStatus(200);

        // 3. Like / React (/api/v2/reels/{id}/react)
        $likeRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/reels/{$reel1->id}/react", [
            'type' => 'like',
            'reaction' => 'like',
        ]);
        $likeRes->assertStatus(200);
        $this->assertDatabaseHas('reel_reactions', [
            'reel_id' => $reel1->id,
            'user_id' => $viewer->id,
        ]);

        // 4. Save / Bookmark (/api/v2/reels/{id}/save)
        $saveRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/reels/{$reel1->id}/save");
        $saveRes->assertStatus(200);
        $this->assertDatabaseHas('reel_saves', [
            'reel_id' => $reel1->id,
            'user_id' => $viewer->id,
        ]);

        // 5. Post & List Comments (/api/v2/reels/{id}/comments)
        $commentRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/reels/{$reel1->id}/comments", [
            'comment' => 'Smooth auto-advance and swipe on mobile!',
            'content' => 'Smooth auto-advance and swipe on mobile!',
        ]);
        $commentRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $listCommentsRes = $this->actingAs($viewer, 'sanctum')->getJson("/api/v2/reels/{$reel1->id}/comments");
        $listCommentsRes->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // 6. Share Reel (/api/v2/reels/{id}/share)
        $shareRes = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/reels/{$reel1->id}/share", [
            'platform' => 'copy_link',
        ]);
        $shareRes->assertStatus(200);

        // 7. Delete Reel (/api/v2/reels/{id})
        $deleteRes = $this->actingAs($viewer, 'sanctum')->deleteJson("/api/v2/reels/{$reel3->id}");
        $deleteRes->assertStatus(200);
    }

    public function test_centralized_reel_player_engine_auto_advance_loop_sync_and_mobile_swipe_architecture(): void
    {
        $engineContent = file_get_contents(resource_path('js/Composables/useReelPlayerEngine.ts'));
        $modalContent = file_get_contents(resource_path('js/Components/Reels/ReelViewerModal.vue'));
        $pageContent = file_get_contents(resource_path('js/Pages/Reels.vue'));
        $pagesShowContent = file_get_contents(resource_path('js/Pages/Pages/Show.vue'));

        // 1. Verify centralized composable auto-advance & loop state synchronization
        $this->assertStringContainsString('jugajug_reels_auto_advance', $engineContent);
        $this->assertStringContainsString('jugajug:reels-auto-advance-changed', $engineContent);
        $this->assertStringContainsString('syncVideoLoopAttribute', $engineContent);
        $this->assertStringContainsString('completedReelToken', $engineContent);
        $this->assertStringContainsString('advanceLockUntil', $engineContent);
        $this->assertStringContainsString('nextReelPreloadUrl', $engineContent);
        $this->assertStringContainsString('onVideoError', $engineContent);
        $this->assertStringContainsString('retryVideo', $engineContent);

        // 2. Verify full-surface unified touch, pointer, mouse & wheel gesture controller
        $this->assertStringContainsString('startedOnInteractive', $engineContent);
        $this->assertStringContainsString('suppressClickUntil', $engineContent);
        $this->assertStringContainsString('handleCaptureClick', $engineContent);
        $this->assertStringContainsString('handlePointerDown', $engineContent);
        $this->assertStringContainsString('handlePointerMove', $engineContent);
        $this->assertStringContainsString('handlePointerUp', $engineContent);
        $this->assertStringContainsString('data-reel-no-swipe="true"', $engineContent);

        // 3. Verify ReelViewerModal.vue uses useReelPlayerEngine and binds full-surface gesture + preloader + error recovery
        $this->assertStringContainsString('useReelPlayerEngine', $modalContent);
        $this->assertStringContainsString('@pointerdown="handlePointerDown"', $modalContent);
        $this->assertStringContainsString('@click.capture="handleCaptureClick"', $modalContent);
        $this->assertStringContainsString('@error="onVideoError"', $modalContent);
        $this->assertStringContainsString('nextReelPreloadUrl', $modalContent);
        $this->assertStringContainsString('h-[100dvh]', $modalContent);
        $this->assertStringContainsString('data-reel-no-swipe="true"', $modalContent);

        // 4. Verify Reels.vue uses useReelPlayerEngine, supports background pagination load-more, and does not block bottom-edge swipes
        $this->assertStringContainsString('useReelPlayerEngine', $pageContent);
        $this->assertStringContainsString('fetchMoreReels', $pageContent);
        $this->assertStringContainsString('@pointerdown="handlePointerDown"', $pageContent);
        $this->assertStringContainsString('@click.capture="handleCaptureClick"', $pageContent);
        $this->assertStringContainsString('@error="onVideoError"', $pageContent);
        $this->assertStringContainsString('nextReelPreloadUrl', $pageContent);
        $this->assertStringContainsString('data-reel-no-swipe="true"', $pageContent);

        // 5. Verify Pages/Show.vue inline Reel modal also supports auto-advance and touch swipe
        $this->assertStringContainsString('@ended="nextPageReel"', $pagesShowContent);
        $this->assertStringContainsString('onPageReelTouchStart', $pagesShowContent);
        $this->assertStringContainsString('onPageReelTouchEnd', $pagesShowContent);
    }
}
