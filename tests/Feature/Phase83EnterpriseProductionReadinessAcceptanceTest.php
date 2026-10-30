<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Media;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\ReelSave;
use App\Models\User;
use App\Models\UserFollower;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase83EnterpriseProductionReadinessAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createReelWithVideo(User $author, array $overrides = []): Reel
    {
        $reel = Reel::create(array_merge([
            'user_id' => $author->id,
            'caption' => 'Phase 8.3 Enterprise Production Validated Reel #jugajug',
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
            'original_path' => 'reels/phase83-reel-'.$reel->id.'.mp4',
            'mime_type' => 'video/mp4',
            'width' => 1080,
            'height' => 1920,
            'size' => 1048576,
            'processing_status' => 'completed',
        ]);

        ReelMedia::create([
            'reel_id' => $reel->id,
            'media_id' => $media->id,
            'quality' => 'original',
            'video_path' => 'reels/phase83-reel-'.$reel->id.'.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1048576,
        ]);

        return $reel->fresh(['user.profile', 'media']);
    }

    public function test_reel_player_engine_150_consecutive_transitions_stress_and_stability(): void
    {
        $stressScript = base_path('tests/Javascript/reel-player-engine-stress.mjs');
        $this->assertFileExists($stressScript);

        $output = shell_exec("node {$stressScript} 2>&1");
        $this->assertNotEmpty($output, 'Stress runner should yield json output');

        $result = json_decode($output, true);
        $this->assertIsArray($result, 'Output must be valid JSON: '.$output);
        $this->assertSame('SUCCESS', $result['status']);

        $metrics = $result['metrics'];
        $this->assertGreaterThanOrEqual(100, $metrics['transitionsCompleted']);
        $this->assertSame(0, $metrics['concurrentPlayViolations']);
        $this->assertSame(0, $metrics['unhandledPromiseRejections']);
        $this->assertGreaterThanOrEqual(1, $metrics['failedPreloadsIsolated']);
        $this->assertGreaterThanOrEqual(1, $metrics['visibilityResumesWithoutReset']);
    }

    public function test_frontend_reels_engine_network_resilience_and_memory_leak_mitigation(): void
    {
        $engineContent = file_get_contents(resource_path('js/Composables/useReelPlayerEngine.ts'));
        $modalContent = file_get_contents(resource_path('js/Components/Reels/ReelViewerModal.vue'));
        $pageContent = file_get_contents(resource_path('js/Pages/Reels.vue'));
        $creatorContent = file_get_contents(resource_path('js/Components/Reels/ReelCreatorModal.vue'));

        // 1. Buffering state, network diagnostics, and event hooks
        $this->assertStringContainsString('isBuffering', $engineContent);
        $this->assertStringContainsString('videoErrorMessage', $engineContent);
        $this->assertStringContainsString('isOffline', $engineContent);
        $this->assertStringContainsString('failedPreloadUrls', $engineContent);
        $this->assertStringContainsString('onVideoWaiting', $engineContent);
        $this->assertStringContainsString('onVideoPlaying', $engineContent);
        $this->assertStringContainsString('onVideoCanPlay', $engineContent);
        $this->assertStringContainsString('onPreloadError', $engineContent);

        // 2. Video resource release & online/offline listeners
        $this->assertStringContainsString('releaseVideoResources', $engineContent);
        $this->assertStringContainsString("v.removeAttribute('src')", $engineContent);
        $this->assertStringContainsString('v.load()', $engineContent);
        $this->assertStringContainsString("window.addEventListener('online'", $engineContent);
        $this->assertStringContainsString("window.addEventListener('offline'", $engineContent);

        // 3. Playback timestamp preservation on visibility restore
        $this->assertStringContainsString('playActiveVideo(false)', $engineContent);

        // 4. ReelViewerModal bindings & Share modal props
        $this->assertStringContainsString('@waiting="onVideoWaiting"', $modalContent);
        $this->assertStringContainsString('@playing="onVideoPlaying"', $modalContent);
        $this->assertStringContainsString('@canplay="onVideoCanPlay"', $modalContent);
        $this->assertStringContainsString('@error="onPreloadError"', $modalContent);
        $this->assertStringContainsString('v-if="isBuffering && !videoError"', $modalContent);
        $this->assertStringContainsString(':current-user="currentUser"', $modalContent);
        $this->assertStringContainsString('payload?.shares_count', $modalContent);
        $this->assertStringContainsString('payload?.count', $modalContent);

        // 5. Reels.vue bindings, pagination cooldown, 4 mobile tabs, and Share modal props
        $this->assertStringContainsString('@waiting="onVideoWaiting"', $pageContent);
        $this->assertStringContainsString('@playing="onVideoPlaying"', $pageContent);
        $this->assertStringContainsString('@canplay="onVideoCanPlay"', $pageContent);
        $this->assertStringContainsString('@error="onPreloadError"', $pageContent);
        $this->assertStringContainsString('paginationErrorCooldownUntil', $pageContent);
        $this->assertStringContainsString("switchTab('for_you')", $pageContent);
        $this->assertStringContainsString("switchTab('following')", $pageContent);
        $this->assertStringContainsString("switchTab('my_reels')", $pageContent);
        $this->assertStringContainsString("switchTab('saved')", $pageContent);
        $this->assertStringContainsString('max-w-[185px] xs:max-w-[230px]', $pageContent);
        $this->assertStringContainsString(':current-user="currentUser"', $pageContent);
        $this->assertStringContainsString('payload?.shares_count', $pageContent);
        $this->assertStringContainsString('payload?.count', $pageContent);

        // 6. ReelCreatorModal memory leak prevention (safe duration probing and XHR abort)
        $this->assertStringContainsString("tempVideo.removeAttribute('src')", $creatorContent);
        $this->assertStringContainsString('tempVideo.load()', $creatorContent);
        $this->assertStringContainsString('activeXhr.abort()', $creatorContent);
        $this->assertStringContainsString('URL.revokeObjectURL(videoPreviewUrl.value)', $creatorContent);
        $this->assertStringContainsString('URL.revokeObjectURL(coverPreviewUrl.value)', $creatorContent);
    }

    public function test_backend_privacy_draft_expired_and_blocked_user_security_enforcement(): void
    {
        $viewer = User::factory()->create([
            'username' => 'phase83_viewer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $author = User::factory()->create([
            'username' => 'phase83_author',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $blockedAuthor = User::factory()->create([
            'username' => 'phase83_blocked_author',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Establish follow relationship between viewer and author
        UserFollower::create([
            'user_id' => $author->id,
            'follower_id' => $viewer->id,
        ]);

        // Establish block relationship: viewer blocked $blockedAuthor
        Friendship::create([
            'user_id' => $viewer->id,
            'friend_id' => $blockedAuthor->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);

        // Create Reels: public, only_me, draft, expired, and blocked
        $publicReel = $this->createReelWithVideo($author, ['caption' => 'Public Reel Author']);
        $onlyMeReel = $this->createReelWithVideo($author, ['caption' => 'Private Reel Author', 'privacy' => 'only_me']);
        $draftReel = $this->createReelWithVideo($author, ['caption' => 'Draft Reel Author', 'is_draft' => true]);
        $expiredReel = $this->createReelWithVideo($author, ['caption' => 'Expired Reel Author', 'expires_at' => now()->subHour()]);
        $blockedReel = $this->createReelWithVideo($blockedAuthor, ['caption' => 'Blocked Creator Reel']);

        // 1. Blocked User Exclusions
        // Feed API excludes blocked user's reel
        $feedApi = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/reels');
        $feedApi->assertStatus(200);
        $feedReelIds = collect($feedApi->json('data'))->pluck('id')->all();
        $this->assertNotContains($blockedReel->id, $feedReelIds);
        $this->assertContains($publicReel->id, $feedReelIds);

        // Saved reels API excludes blocked user's reel even if saved previously
        ReelSave::create([
            'reel_id' => $blockedReel->id,
            'user_id' => $viewer->id,
        ]);
        $savedApi = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/reels?tab=saved');
        $savedApi->assertStatus(200);
        $savedReelIds = collect($savedApi->json('data'))->pluck('id')->all();
        $this->assertNotContains($blockedReel->id, $savedReelIds);

        // Web /reels page excludes blocked user's reel
        $webPage = $this->actingAs($viewer, 'web')->get('/reels');
        $webPage->assertStatus(200);
        $webPage->assertInertia(fn (Assert $page) => $page
            ->component('Reels')
            ->where('reels.data', fn ($items) => collect($items)->pluck('id')->doesntContain($blockedReel->id))
        );

        // 2. Draft Security: Non-author cannot access draft reel
        $draftApiUnauthorized = $this->actingAs($viewer, 'sanctum')->getJson("/api/v2/reels/{$draftReel->id}");
        $draftApiUnauthorized->assertStatus(403);

        // Author CAN access their own draft reel
        $draftApiAuthor = $this->actingAs($author, 'sanctum')->getJson("/api/v2/reels/{$draftReel->id}");
        $draftApiAuthor->assertStatus(200);

        // 3. Only Me Security: Non-author cannot access only_me reel
        $onlyMeApiUnauthorized = $this->actingAs($viewer, 'sanctum')->getJson("/api/v2/reels/{$onlyMeReel->id}");
        $onlyMeApiUnauthorized->assertStatus(403);

        // Author CAN access their own only_me reel
        $onlyMeApiAuthor = $this->actingAs($author, 'sanctum')->getJson("/api/v2/reels/{$onlyMeReel->id}");
        $onlyMeApiAuthor->assertStatus(200);

        // 4. Expired Reel (HTTP 410 Gone)
        $expiredApi = $this->actingAs($viewer, 'sanctum')->getJson("/api/v2/reels/{$expiredReel->id}");
        $expiredApi->assertStatus(410);

        $expiredViewApi = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/reels/{$expiredReel->id}/view");
        $expiredViewApi->assertStatus(410);

        // 5. Following Feed API: /api/v2/reels?tab=following returns followed creator reels
        $followingApi = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/reels?tab=following');
        $followingApi->assertStatus(200);
        $followingReelIds = collect($followingApi->json('data'))->pluck('id')->all();
        $this->assertContains($publicReel->id, $followingReelIds);
        $this->assertNotContains($onlyMeReel->id, $followingReelIds);

        // 6. Reel Deletion Authorization: Non-author cannot delete author's reel
        $unauthorizedDelete = $this->actingAs($viewer, 'sanctum')->deleteJson("/api/v2/reels/{$publicReel->id}");
        $unauthorizedDelete->assertStatus(403);

        // Author CAN delete their own reel
        $authorizedDelete = $this->actingAs($author, 'sanctum')->deleteJson("/api/v2/reels/{$publicReel->id}");
        $authorizedDelete->assertStatus(200);
    }
}
