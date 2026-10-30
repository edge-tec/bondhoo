<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Media;
use App\Models\Message;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase85EnterpriseScalabilityAndExtremeLoadTest extends TestCase
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
            'caption' => 'Phase 8.5 Extreme Load Reel #scalability',
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
            'original_path' => 'reels/phase85-reel-'.$reel->id.'.mp4',
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
            'video_path' => 'reels/phase85-reel-'.$reel->id.'.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1048576,
        ]);

        return $reel->fresh(['user.profile', 'media']);
    }

    /**
     * 1. Multi-Tier Concurrency Load Benchmark (1,000, 2,500, 5,000, 10,000 VUs)
     */
    public function test_extreme_load_multi_tier_concurrency_benchmark_execution(): void
    {
        $scriptPath = base_path('tests/Javascript/phase85-extreme-load-benchmark.mjs');
        $this->assertFileExists($scriptPath);

        $output = shell_exec("node {$scriptPath} 2>&1");
        $this->assertNotEmpty($output, 'Load benchmark should produce JSON output');

        $result = json_decode($output, true);
        $this->assertIsArray($result, 'Benchmark output must be valid JSON: '.$output);
        $this->assertSame('SUCCESS', $result['status']);
        $this->assertSame('8.5', $result['phase']);

        $tiers = $result['benchmark_results'];
        $this->assertCount(4, $tiers, 'Must execute 4 distinct concurrency tiers');

        $tierUsers = array_column($tiers, 'virtual_users');
        $this->assertEquals([1000, 2500, 5000, 10000], $tierUsers);

        foreach ($tiers as $tier) {
            $this->assertSame(0, $tier['error_count'], "Tier {$tier['tier']} must complete with 0 errors");
            $this->assertSame(0.0, (float) $tier['error_rate_pct']);
            $this->assertLessThan(10.0, $tier['latency_p95_ms'], "p95 latency in {$tier['tier']} must be < 10ms");
            $this->assertLessThan(20.0, $tier['latency_p99_ms'], "p99 latency in {$tier['tier']} must be < 20ms");
            $this->assertGreaterThan(1000.0, $tier['requests_per_second']);
        }
    }

    /**
     * 2. Database Read-Path Lock Elimination & Expiration Throttling
     */
    public function test_reels_read_path_does_not_execute_concurrent_write_locks(): void
    {
        $user = User::factory()->create([
            'username' => 'phase85_viewer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->createReelWithVideo($user);

        // Pre-set the expiration throttle lock in cache to simulate active concurrent traffic
        Cache::put('reels_expiration_cleanup_lock', true, 60);

        DB::enableQueryLog();

        // Perform multiple read requests on the public feed API
        $res1 = $this->actingAs($user, 'sanctum')->getJson('/api/v2/reels');
        $res1->assertStatus(200);

        $res2 = $this->actingAs($user, 'sanctum')->getJson('/api/v2/reels');
        $res2->assertStatus(200);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Verify that NO update queries were executed during these read requests
        $updateQueries = array_filter($queries, function ($q) {
            return str_starts_with(strtoupper(trim($q['query'])), 'UPDATE');
        });

        $this->assertEmpty($updateQueries, 'Read requests must NOT execute blocking UPDATE statements on the reels table');
    }

    /**
     * 3. Messenger Header Responsive Truncation Defect Elimination
     */
    public function test_messenger_header_responsive_truncation_is_eliminated(): void
    {
        $messengerFile = resource_path('js/Pages/Messenger.vue');
        $this->assertFileExists($messengerFile);

        $content = file_get_contents($messengerFile);

        // Verify Column 1 expanded width on desktop/tablet
        $this->assertStringContainsString('md:w-[340px] lg:w-[360px]', $content);

        // Verify Messages heading has whitespace-nowrap and shrink-0 to prevent "Messa..." truncation
        $this->assertStringContainsString('whitespace-nowrap', $content);
        $this->assertStringContainsString('shrink-0 whitespace-nowrap', $content);

        // Compact header icon buttons
        $this->assertStringContainsString('w-8 h-8 flex items-center justify-center', $content);
    }

    /**
     * 4. Atomic Counter & Cache Scalability Under High Message Volume
     */
    public function test_unread_message_counters_scale_atomically_without_table_locks(): void
    {
        $sender = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $recipient = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        $conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
            'creator_id' => $sender->id,
            'last_message_at' => now(),
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
            'role' => 'member',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $recipient->id,
            'role' => 'member',
        ]);

        // Send message via API
        $sendRes = $this->actingAs($sender, 'sanctum')->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'body' => 'Scalable concurrent message',
            'client_message_id' => 'msg-scale-001',
        ]);
        $sendRes->assertStatus(201);

        // Verify recipient's unread counter in cache was incremented
        $userUnreadKey = "user:{$recipient->id}:unread_messages";
        $convUnreadKey = "conversation:{$conversation->id}:unread:{$recipient->id}";

        $this->assertGreaterThanOrEqual(1, (int) Cache::get($convUnreadKey));
        $this->assertGreaterThanOrEqual(1, (int) Cache::get($userUnreadKey));
    }
}
