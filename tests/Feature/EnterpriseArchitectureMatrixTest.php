<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AiPrompt;
use App\Models\AiUsageLog;
use App\Models\CreatorEarning;
use App\Models\Event;
use App\Models\FederatedActivity;
use App\Models\FederatedActor;
use App\Models\LiveStream;
use App\Models\MarketplaceCategory;
use App\Models\SecurityRiskProfile;
use App\Models\User;
use App\Models\VectorEmbedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnterpriseArchitectureMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_prompt_model_attributes_and_casting(): void
    {
        $prompt = AiPrompt::create([
            'name' => 'bangla_poet',
            'version' => '2.1.0',
            'template' => 'Write a poem about {{topic}}',
            'parameters' => ['rhyme' => true],
            'category' => 'creative',
            'is_active' => true,
        ]);

        $this->assertSame('bangla_poet', $prompt->name);
        $this->assertSame('2.1.0', $prompt->version);
        $this->assertTrue($prompt->parameters['rhyme']);
        $this->assertTrue($prompt->is_active);
    }

    public function test_ai_usage_log_model_and_relations(): void
    {
        $user = User::factory()->create();
        $log = AiUsageLog::create([
            'user_id' => $user->id,
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
            'operation' => 'chat',
            'prompt_tokens' => 150,
            'completion_tokens' => 350,
            'total_tokens' => 500,
            'cost_usd' => 0.000125,
            'latency_ms' => 240,
            'status' => 'success',
        ]);

        $this->assertSame($user->id, $log->user->id);
        $this->assertSame(500, $log->total_tokens);
        $this->assertEquals(0.000125, $log->cost_usd);
    }

    public function test_vector_embedding_model_casting(): void
    {
        $embedding = VectorEmbedding::create([
            'entity_type' => 'user',
            'entity_id' => 999,
            'model' => 'text-embedding-3-small',
            'dimension' => 3,
            'vector' => [0.12, 0.45, -0.88],
            'content_snippet' => 'AI Architect profile',
        ]);

        $this->assertSame(3, $embedding->dimension);
        $this->assertCount(3, $embedding->vector);
        $this->assertSame(-0.88, $embedding->vector[2]);
    }

    public function test_live_stream_relations_and_collections(): void
    {
        $user = User::factory()->create();
        $stream = LiveStream::create([
            'channel_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Gaming Championship',
            'stream_key' => 'key_'.rand(1000, 9999),
            'ingest_url' => 'rtmp://live.jugajug.com/stream',
            'playback_url' => 'https://cdn.jugajug.com/hls/master.m3u8',
            'status' => 'live',
            'viewers_count' => 1500,
            'peak_viewers' => 2200,
        ]);

        $viewer = User::factory()->create();
        $comment = $stream->comments()->create([
            'user_id' => $viewer->id,
            'message' => 'Go team BD!',
        ]);
        $gift = $stream->gifts()->create([
            'user_id' => $viewer->id,
            'gift_type' => 'rocket',
            'coin_amount' => 100,
        ]);

        $this->assertSame($user->id, $stream->user->id);
        $this->assertCount(1, $stream->comments);
        $this->assertCount(1, $stream->gifts);
        $this->assertSame($stream->id, $comment->stream->id);
        $this->assertSame($stream->id, $gift->stream->id);
    }

    public function test_marketplace_relations(): void
    {
        $cat = MarketplaceCategory::create(['name' => 'Vehicles', 'slug' => 'vehicles', 'display_order' => 1]);
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $product = $cat->products()->create([
            'seller_id' => $seller->id,
            'title' => 'Toyota Premio 2020',
            'description' => 'Mint condition sedan in Dhaka',
            'price' => 3200000.00,
            'currency' => 'BDT',
            'condition' => 'used_like_new',
            'location' => 'Banani, Dhaka',
            'images' => ['https://images.jugajug.com/car1.jpg'],
        ]);

        $order = $product->orders()->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount' => 3200000.00,
            'status' => 'pending',
            'escrow_status' => 'held',
            'payment_method' => 'bank_transfer',
            'shipping_address' => 'Uttara, Dhaka',
        ]);

        $this->assertSame($cat->id, $product->category->id);
        $this->assertSame($seller->id, $product->seller->id);
        $this->assertSame($buyer->id, $order->buyer->id);
        $this->assertSame($seller->id, $order->seller->id);
    }

    public function test_event_and_attendee_relations(): void
    {
        $creator = User::factory()->create();
        $guest = User::factory()->create();

        $event = Event::create([
            'creator_id' => $creator->id,
            'title' => 'Dhaka Tech Carnival 2026',
            'start_time' => now()->addDays(20),
            'location_type' => 'venue',
            'ticket_price' => 500.00,
        ]);

        $attendee = $event->attendees()->create([
            'user_id' => $guest->id,
            'status' => 'going',
            'ticket_code' => 'TKT-987654',
            'checked_in' => false,
        ]);

        $this->assertSame($creator->id, $event->creator->id);
        $this->assertSame($guest->id, $attendee->user->id);
        $this->assertSame($event->id, $attendee->event->id);
    }

    public function test_federated_actor_and_activity_models(): void
    {
        $actor = FederatedActor::create([
            'actor_uri' => 'https://mastodon.world/users/john_doe',
            'username' => 'john_doe',
            'domain' => 'mastodon.world',
            'name' => 'John Doe',
            'inbox_url' => 'https://mastodon.world/users/john_doe/inbox',
            'outbox_url' => 'https://mastodon.world/users/john_doe/outbox',
            'public_key_pem' => 'key_content',
            'public_key_id' => 'https://mastodon.world/users/john_doe#main-key',
        ]);

        $activity = FederatedActivity::create([
            'activity_id' => 'https://mastodon.world/activities/1001',
            'actor_uri' => $actor->actor_uri,
            'type' => 'Follow',
            'object_uri' => 'https://jugajug.com/users/admin',
            'payload' => ['type' => 'Follow'],
            'direction' => 'inbound',
            'status' => 'processed',
        ]);

        $this->assertSame('john_doe', $actor->username);
        $this->assertSame('Follow', $activity->type);
        $this->assertSame('inbound', $activity->direction);
    }

    public function test_creator_earning_and_ad_campaign_models(): void
    {
        $user = User::factory()->create();

        $earning = CreatorEarning::create([
            'user_id' => $user->id,
            'period_month' => '2026-09',
            'stars_received' => 2000,
            'stars_amount' => 20.00,
            'subscription_amount' => 150.00,
            'ad_rev_share' => 35.00,
            'gross_total' => 205.00,
            'platform_fee' => 30.75,
            'net_payout' => 174.25,
            'payout_status' => 'paid',
        ]);

        $campaign = AdCampaign::create([
            'user_id' => $user->id,
            'name' => 'Brand Awareness',
            'objective' => 'reach',
            'total_budget' => 1000.00,
            'daily_budget' => 50.00,
            'start_date' => now(),
            'targeting_criteria' => ['locations' => ['Dhaka', 'Chittagong']],
        ]);

        $this->assertSame($user->id, $earning->user->id);
        $this->assertSame($user->id, $campaign->user->id);
        $this->assertSame('paid', $earning->payout_status);
        $this->assertSame('Brand Awareness', $campaign->name);
    }

    public function test_security_risk_profile_model(): void
    {
        $user = User::factory()->create();

        $profile = SecurityRiskProfile::create([
            'user_id' => $user->id,
            'device_fingerprint' => 'fp_99887766',
            'risk_score' => 65.5,
            'risk_level' => 'high',
            'last_ip' => '103.20.14.5',
            'country_code' => 'BD',
            'is_suspicious' => true,
            'mfa_required' => true,
            'anomaly_flags' => ['unknown_device'],
            'last_assessed_at' => now(),
        ]);

        $this->assertSame($user->id, $profile->user->id);
        $this->assertTrue($profile->is_suspicious);
        $this->assertTrue($profile->mfa_required);
        $this->assertSame('high', $profile->risk_level);
    }
}
