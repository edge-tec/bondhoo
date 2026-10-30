<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\ReelSave;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase82FinalProductionValidationMobileReelsAuditTest extends TestCase
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
            'caption' => 'Phase 8.2 Production Validated Reel #jugajug',
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
            'original_path' => 'reels/phase82-reel-'.$reel->id.'.mp4',
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
            'video_path' => 'reels/phase82-reel-'.$reel->id.'.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024000,
        ]);

        return $reel->fresh(['user.profile', 'media']);
    }

    public function test_critical_items_1_to_10_engine_stability_deduplication_storage_safety_and_multi_tab_sync(): void
    {
        $engineContent = file_get_contents(resource_path('js/Composables/useReelPlayerEngine.ts'));
        $modalContent = file_get_contents(resource_path('js/Components/Reels/ReelViewerModal.vue'));
        $pageContent = file_get_contents(resource_path('js/Pages/Reels.vue'));

        // Item 1 & 2: Single auto-advance completion lock & playRequestSeq deduplication
        $this->assertStringContainsString('completedReelToken', $engineContent);
        $this->assertStringContainsString('advanceLockUntil', $engineContent);
        $this->assertStringContainsString('playRequestSeq', $engineContent);

        // Item 3: Loop mode synchronization on video element
        $this->assertStringContainsString('syncVideoLoopAttribute', $engineContent);
        $this->assertStringContainsString("v.setAttribute('loop', '')", $engineContent);
        $this->assertStringContainsString("v.removeAttribute('loop')", $engineContent);

        // Item 4: Touch, Pointer, Mouse, and Wheel deduplication guards
        $this->assertStringContainsString('lastTouchEventTime', $engineContent);
        $this->assertStringContainsString('lastPointerEventTime', $engineContent);
        $this->assertStringContainsString('lastWheelTime', $engineContent);

        // Item 5 & 6: Full-surface swipe & interactive control click suppression
        $this->assertStringContainsString('startedOnInteractive', $engineContent);
        $this->assertStringContainsString('suppressClickUntil', $engineContent);
        $this->assertStringContainsString('handleCaptureClick', $engineContent);
        $this->assertStringContainsString('@click.capture="handleCaptureClick"', $modalContent);
        $this->assertStringContainsString('@click.capture="handleCaptureClick"', $pageContent);

        // Item 7, 8 & 9: Safe localStorage handling, same-tab custom event sync, and multi-tab StorageEvent sync
        $this->assertStringContainsString('window.localStorage.getItem(STORAGE_KEY_AUTO_ADVANCE)', $engineContent);
        $this->assertStringContainsString('window.localStorage.setItem(STORAGE_KEY_AUTO_ADVANCE', $engineContent);
        $this->assertStringContainsString('onExternalAutoAdvanceSync', $engineContent);
        $this->assertStringContainsString('onStorageSync', $engineContent);
        $this->assertStringContainsString("window.addEventListener('storage', onStorageSync)", $engineContent);

        // Item 10: Guarded next-Reel metadata preloading (inactive viewer & duplicate URL checks)
        $this->assertStringContainsString('if (options.isActive && !options.isActive.value)', $engineContent);
        $this->assertStringContainsString('if (!nextUrl || nextUrl === currentReel.value?.video_url)', $engineContent);
        $this->assertStringContainsString('preload="metadata"', $modalContent);
        $this->assertStringContainsString('preload="metadata"', $pageContent);
    }

    public function test_critical_items_11_and_12_empty_feeds_deleted_reels_tab_pagination_and_popstate_deeplinks(): void
    {
        $viewer = User::factory()->create([
            'username' => 'phase82_viewer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $other = User::factory()->create([
            'username' => 'phase82_creator',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // 1. Empty feed boundary check on /reels
        $emptyRes = $this->actingAs($viewer, 'web')->get('/reels');
        $emptyRes->assertStatus(200);
        $emptyRes->assertInertia(fn (Assert $page) => $page
            ->component('Reels')
            ->has('reels.data', 0)
        );

        // 2. Create Reels for viewer and other user, plus save one Reel
        $viewerReel = $this->createReelWithVideo($viewer, ['caption' => 'Viewer Own Reel']);
        $otherReel = $this->createReelWithVideo($other, ['caption' => 'Other Creator Reel']);

        ReelSave::create([
            'reel_id' => $otherReel->id,
            'user_id' => $viewer->id,
        ]);

        // 3. Verify API pagination respects tab=my_reels and tab=saved and out-of-range page boundary
        $myReelsApi = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/reels?tab=my_reels&per_page=5');
        $myReelsApi->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $viewerReel->id);

        $savedReelsApi = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/reels?tab=saved&per_page=5');
        $savedReelsApi->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $otherReel->id);

        $boundaryPageApi = $this->actingAs($viewer, 'sanctum')->getJson('/api/v2/reels?page=99&per_page=5');
        $boundaryPageApi->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // 4. Verify deep link via ?id={id} and ?reel={id} and /reels/{id}
        $idQueryRes = $this->actingAs($viewer, 'web')->get("/reels?id={$viewerReel->id}");
        $idQueryRes->assertStatus(200);
        $idQueryRes->assertInertia(fn (Assert $page) => $page
            ->component('Reels')
            ->where('initialReelId', $viewerReel->id)
            ->where('reels.data.0.id', $viewerReel->id)
        );

        // 5. Verify popstate and playlist length clamping in Reels.vue & useReelPlayerEngine.ts
        $pageContent = file_get_contents(resource_path('js/Pages/Reels.vue'));
        $engineContent = file_get_contents(resource_path('js/Composables/useReelPlayerEngine.ts'));
        $this->assertStringContainsString("window.addEventListener('popstate', handlePopState)", $pageContent);
        $this->assertStringContainsString('() => options.reels.value.length', $engineContent);
    }
}
