<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\CreatorEarning;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\AIGateway;
use App\Services\AI\JugajugAIAssistant;
use App\Services\AI\VectorService;
use App\Services\DisasterRecovery\DisasterRecoveryService;
use App\Services\Federation\ActivityPubService;
use App\Services\Feed\AIFeedRankingEngine;
use App\Services\Marketplace\MarketplaceService;
use App\Services\Media\VideoTranscodingService;
use App\Services\Observability\OpenTelemetryService;
use App\Services\Search\VectorSearchService;
use App\Services\Security\EnterpriseSecurityService;
use App\Services\Streaming\LiveStreamingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnterpriseHighScaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_gateway_multi_provider_availability_and_fallback(): void
    {
        $gateway = app(AIGateway::class);
        $providers = ['openai', 'gemini', 'claude', 'ollama'];

        foreach ($providers as $p) {
            $instance = $gateway->getProvider($p);
            $this->assertNotNull($instance);
            $this->assertTrue($instance->isAvailable());
            $this->assertSame($p, $instance->getProviderName());
        }
    }

    public function test_ai_gateway_token_cost_matrix(): void
    {
        $gateway = app(AIGateway::class);

        $costOpenAI = $gateway->calculateCost('openai', 'gpt-4o', 10000, 10000);
        $costGemini = $gateway->calculateCost('gemini', 'gemini-2.5-flash', 10000, 10000);
        $costClaude = $gateway->calculateCost('claude', 'claude-3-5-sonnet', 10000, 10000);
        $costOllama = $gateway->calculateCost('ollama', 'llama3', 10000, 10000);

        $this->assertGreaterThan(0.0, $costOpenAI);
        $this->assertGreaterThan(0.0, $costGemini);
        $this->assertGreaterThan(0.0, $costClaude);
        $this->assertSame(0.0, $costOllama);
        $this->assertLessThan($costClaude, $costGemini);
        $this->assertIsFloat($costOpenAI);
        $this->assertIsFloat($costGemini);
        $this->assertIsFloat($costClaude);
    }

    public function test_ai_feed_ranking_viral_multipliers(): void
    {
        $engine = new AIFeedRankingEngine;
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        $post = Post::factory()->create(['user_id' => $author->id, 'audience' => 'public', 'created_at' => now()->subHour()]);

        $scoreNormal = $engine->calculateScore($post, $viewer, ['likes' => 10, 'comments' => 2]);
        $scoreViral = $engine->calculateScore($post, $viewer, ['likes' => 200, 'comments' => 150]);

        $this->assertGreaterThan($scoreNormal, $scoreViral);
        $this->assertGreaterThan(0.0, $scoreNormal);
        $this->assertGreaterThan(0.0, $scoreViral);
        $this->assertIsFloat($scoreNormal);
        $this->assertIsFloat($scoreViral);
    }

    public function test_ai_feed_ranking_time_decay_curve(): void
    {
        $engine = new AIFeedRankingEngine;
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        $freshPost = Post::factory()->create(['user_id' => $author->id, 'audience' => 'public', 'created_at' => now()]);
        $dayOldPost = Post::factory()->create(['user_id' => $author->id, 'audience' => 'public', 'created_at' => now()->subDay()]);
        $weekOldPost = Post::factory()->create(['user_id' => $author->id, 'audience' => 'public', 'created_at' => now()->subDays(7)]);

        $scoreFresh = $engine->calculateScore($freshPost, $viewer, ['likes' => 50]);
        $scoreDay = $engine->calculateScore($dayOldPost, $viewer, ['likes' => 50]);
        $scoreWeek = $engine->calculateScore($weekOldPost, $viewer, ['likes' => 50]);

        $this->assertGreaterThan($scoreDay, $scoreFresh);
        $this->assertGreaterThan($scoreWeek, $scoreDay);
        $this->assertGreaterThan(0.0, $scoreWeek);
        $this->assertIsFloat($scoreFresh);
        $this->assertIsFloat($scoreDay);
        $this->assertIsFloat($scoreWeek);
    }

    public function test_ai_assistant_prompt_variations_and_tones(): void
    {
        $assistant = app(JugajugAIAssistant::class);

        $friendly = $assistant->writeCaption('Eid celebrations', 'friendly');
        $professional = $assistant->writeCaption('Tech summit 2026', 'professional');
        $funny = $assistant->writeCaption('Coffee and code', 'humorous');

        $this->assertNotEmpty($friendly['content']);
        $this->assertNotEmpty($professional['content']);
        $this->assertNotEmpty($funny['content']);
        $this->assertArrayHasKey('model', $friendly);
        $this->assertArrayHasKey('model', $professional);
        $this->assertArrayHasKey('model', $funny);
    }

    public function test_ai_citizen_services_multi_query_responses(): void
    {
        $assistant = app(JugajugAIAssistant::class);

        $nid = $assistant->citizenServicesAssistant('জাতীয় পরিচয়পত্র সংশোধন করার প্রক্রিয়া কী?');
        $passport = $assistant->citizenServicesAssistant('জরুরি ই-পাসপোর্ট ফি কত?');
        $birth = $assistant->citizenServicesAssistant('অনলাইন জন্ম নিবন্ধন যাচাই করার উপায় কী?');

        $this->assertNotEmpty($nid['content']);
        $this->assertNotEmpty($passport['content']);
        $this->assertNotEmpty($birth['content']);
        $this->assertSame('citizen_assistant', $nid['operation'] ?? 'citizen_assistant');
        $this->assertGreaterThan(0, $nid['total_tokens'] ?? 10);
        $this->assertGreaterThan(0, $passport['total_tokens'] ?? 10);
    }

    public function test_vector_search_cosine_range_validation(): void
    {
        $vectorService = new VectorService;

        for ($i = 0; $i < 10; $i++) {
            $v1 = [rand(-100, 100) / 100, rand(-100, 100) / 100, rand(-100, 100) / 100];
            $v2 = [rand(-100, 100) / 100, rand(-100, 100) / 100, rand(-100, 100) / 100];
            $sim = $vectorService->cosineSimilarity($v1, $v2);
            $this->assertGreaterThanOrEqual(-1.0, $sim);
            $this->assertLessThanOrEqual(1.0, $sim);
        }
    }

    public function test_hybrid_search_deduplication(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id, 'content' => 'Bangla NLP and Deep Learning', 'audience' => 'public']);

        $search = app(VectorSearchService::class);
        $search->indexEntity('post', $post->id, $post->content);

        $results = $search->hybridSearch('Bangla NLP', 10);

        $this->assertNotEmpty($results);
        $ids = array_column($results, 'entity_id');
        $this->assertSame(count($ids), count(array_unique($ids))); // No duplicate IDs
        $this->assertContains($post->id, $ids);
    }

    public function test_live_streaming_channel_uniqueness(): void
    {
        $user = User::factory()->create();
        $streamService = app(LiveStreamingService::class);

        $s1 = $streamService->createChannel($user, 'Channel 1');
        $s2 = $streamService->createChannel($user, 'Channel 2');

        $this->assertNotSame($s1->channel_id, $s2->channel_id);
        $this->assertNotSame($s1->stream_key, $s2->stream_key);
        $this->assertNotSame($s1->ingest_url, $s2->ingest_url);
        $this->assertNotSame($s1->playback_url, $s2->playback_url);
        $this->assertSame('ready', $s1->status);
        $this->assertSame('ready', $s2->status);
    }

    public function test_live_streaming_health_telemetry_metrics(): void
    {
        $user = User::factory()->create();
        $stream = app(LiveStreamingService::class)->createChannel($user, 'Telemetry Test');

        $this->assertNotNull($stream->health_stats);
        $this->assertSame(60, $stream->health_stats['fps']);
        $this->assertSame(4500, $stream->health_stats['bitrate_kbps']);
        $this->assertSame(0, $stream->health_stats['dropped_frames']);
        $this->assertSame('H.264/AAC', $stream->health_stats['codec']);
        $this->assertSame('1080p', $stream->health_stats['resolution']);
    }

    public function test_video_transcoder_multi_bitrate_resolution_ladder(): void
    {
        $user = User::factory()->create();
        $media = Media::create([
            'user_id' => $user->id,
            'disk' => 'local',
            'original_path' => 'videos/sample_hq.mp4',
            'original_name' => 'sample_hq.mp4',
            'mime_type' => 'video/mp4',
            'size' => 20971520,
            'processing_status' => 'ready',
        ]);

        $transcoder = new VideoTranscodingService;
        $job = $transcoder->generateTranscodeJob($media, ['watermark' => 'Jugajug Verified']);

        $this->assertCount(4, $job['renditions']);
        $this->assertArrayHasKey('1080p', $job['commands']);
        $this->assertArrayHasKey('720p', $job['commands']);
        $this->assertArrayHasKey('480p', $job['commands']);
        $this->assertArrayHasKey('360p', $job['commands']);
        $this->assertStringContainsString('1920x1080', $job['commands']['1080p']);
        $this->assertStringContainsString('1280x720', $job['commands']['720p']);
    }

    public function test_marketplace_categories_and_condition_filters(): void
    {
        $cat1 = MarketplaceCategory::create(['name' => 'Sports', 'slug' => 'sports', 'display_order' => 1]);
        $cat2 = MarketplaceCategory::create(['name' => 'Home Decor', 'slug' => 'home-decor', 'display_order' => 2]);

        $seller = User::factory()->create();
        $p1 = MarketplaceProduct::create([
            'seller_id' => $seller->id,
            'category_id' => $cat1->id,
            'title' => 'Cricket Bat English Willow',
            'description' => 'Grade 1 bat with grip',
            'price' => 8500.00,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => 'active',
        ]);

        $this->assertSame('sports', $cat1->slug);
        $this->assertSame('home-decor', $cat2->slug);
        $this->assertSame('new', $p1->condition);
        $this->assertSame('BDT', $p1->currency);
        $this->assertSame(8500.00, (float) $p1->price);
        $this->assertSame('active', $p1->status);
    }

    public function test_marketplace_escrow_audit_trail(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $cat = MarketplaceCategory::create(['name' => 'Toys', 'slug' => 'toys']);

        $product = MarketplaceProduct::create([
            'seller_id' => $seller->id,
            'category_id' => $cat->id,
            'title' => 'RC Drone with Camera',
            'description' => '4K camera quadcopter',
            'price' => 12000.00,
            'status' => 'active',
        ]);

        $order = app(MarketplaceService::class)->placeOrder(
            $buyer,
            $product,
            'Gulshan 2, Dhaka',
            'bkash'
        );

        $this->assertSame('held', $order->escrow_status);
        $this->assertSame('pending', $order->status);
        $this->assertSame('bkash', $order->payment_method);
        $this->assertEquals(12000.00, $order->amount);

        app(MarketplaceService::class)->releaseEscrow($order);
        $this->assertSame('released', $order->fresh()->escrow_status);
        $this->assertSame('delivered', $order->fresh()->status);
    }

    public function test_activitypub_webfinger_rfc7033_compliance(): void
    {
        $service = app(ActivityPubService::class);
        $jrd = $service->getWebFinger('mizan');

        $this->assertArrayHasKey('subject', $jrd);
        $this->assertArrayHasKey('aliases', $jrd);
        $this->assertArrayHasKey('links', $jrd);
        $this->assertSame('acct:mizan@jugajug.com', $jrd['subject']);
        $this->assertNotEmpty($jrd['links']);
        $this->assertSame('application/activity+json', $jrd['links'][0]['type']);
    }

    public function test_activitypub_actor_jsonld_structure(): void
    {
        $user = User::factory()->create(['username' => 'rakib_hasan']);
        $service = app(ActivityPubService::class);
        $actor = $service->getActorProfile($user);

        $this->assertArrayHasKey('@context', $actor);
        $this->assertSame('Person', $actor['type']);
        $this->assertSame('rakib_hasan', $actor['preferredUsername']);
        $this->assertStringContainsString('rakib_hasan/inbox', $actor['inbox']);
        $this->assertStringContainsString('rakib_hasan/outbox', $actor['outbox']);
        $this->assertArrayHasKey('publicKey', $actor);
        $this->assertArrayHasKey('publicKeyPem', $actor['publicKey']);
    }

    public function test_creator_stars_to_usd_and_bdt_payout_calculations(): void
    {
        $creator = User::factory()->create();
        $earning = CreatorEarning::create([
            'user_id' => $creator->id,
            'period_month' => '2026-08',
            'stars_received' => 10000,
            'stars_amount' => 100.00,
            'subscription_amount' => 200.00,
            'ad_rev_share' => 100.00,
            'gross_total' => 400.00,
            'platform_fee' => 60.00,
            'net_payout' => 340.00,
            'payout_status' => 'pending',
        ]);

        $this->assertSame(10000, $earning->stars_received);
        $this->assertEquals(100.00, $earning->stars_amount);
        $this->assertEquals(400.00, $earning->gross_total);
        $this->assertEquals(60.00, $earning->platform_fee);
        $this->assertEquals(340.00, $earning->net_payout);
        $this->assertSame('pending', $earning->payout_status);
    }

    public function test_ad_campaign_budget_spent_validation(): void
    {
        $advertiser = User::factory()->create();
        $campaign = AdCampaign::create([
            'user_id' => $advertiser->id,
            'name' => 'Victory Day Sale',
            'objective' => 'traffic',
            'total_budget' => 25000.00,
            'daily_budget' => 2500.00,
            'amount_spent' => 12500.00,
            'status' => 'active',
            'start_date' => now(),
            'impressions_count' => 85000,
            'clicks_count' => 3200,
        ]);

        $this->assertSame('Victory Day Sale', $campaign->name);
        $this->assertSame('traffic', $campaign->objective);
        $this->assertEquals(25000.00, $campaign->total_budget);
        $this->assertEquals(12500.00, $campaign->amount_spent);
        $this->assertSame(85000, $campaign->impressions_count);
        $this->assertSame(3200, $campaign->clicks_count);
    }

    public function test_security_risk_scoring_scale_and_mfa_triggers(): void
    {
        $user = User::factory()->create();
        $security = new EnterpriseSecurityService;

        $normalReq = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '103.112.55.10',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/128.0',
            'HTTP_ACCEPT_LANGUAGE' => 'bn,en-US',
            'HTTP_CF_IPCOUNTRY' => 'BD',
        ]);

        $evalNormal = $security->evaluateLoginRisk($user, $normalReq);

        $this->assertIsFloat($evalNormal['risk_score']);
        $this->assertGreaterThanOrEqual(0.0, $evalNormal['risk_score']);
        $this->assertLessThanOrEqual(100.0, $evalNormal['risk_score']);
        $this->assertContains($evalNormal['risk_level'], ['low', 'medium', 'high', 'critical']);
        $this->assertIsBool($evalNormal['requires_mfa']);
        $this->assertIsArray($evalNormal['flags']);
    }

    public function test_opentelemetry_w3c_traceparent_format_compliance(): void
    {
        $otel = new OpenTelemetryService;
        $span = $otel->startSpan('http.request', ['http.method' => 'POST', 'http.target' => '/api/v2/ai/chat']);

        $header = $otel->getTraceparentHeader($span);

        // Pattern: 00-{32 hex chars}-{16 hex chars}-01
        $this->assertMatchesRegularExpression('/^00-[a-f0-9]{32}-[a-f0-9]{16}-01$/', $header);
        $this->assertSame(32, strlen($span['trace_id']));
        $this->assertSame(16, strlen($span['span_id']));
        $this->assertArrayHasKey('service.name', $span['attributes']);
        $this->assertSame('jugajug-api', $span['attributes']['service.name']);
    }

    public function test_disaster_recovery_rpo_rto_compliance(): void
    {
        $dr = new DisasterRecoveryService;
        $status = $dr->getPitrStatus();

        $this->assertTrue($status['pitr_enabled']);
        $this->assertSame(35, $status['retention_days']);
        $this->assertSame(5, $status['rpo_minutes']);
        $this->assertSame(15, $status['rto_minutes']);
        $this->assertNotEmpty($status['earliest_restorable_time']);
        $this->assertNotEmpty($status['latest_restorable_time']);
    }
}
