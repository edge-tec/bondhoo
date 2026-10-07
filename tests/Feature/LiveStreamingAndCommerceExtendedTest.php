<?php

namespace Tests\Feature;

use App\Models\LiveStream;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Services\Streaming\LiveStreamingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LiveStreamingAndCommerceExtendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_streaming_api_endpoints_index(): void
    {
        $user = User::factory()->create();
        LiveStream::create([
            'channel_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Evening Live Show',
            'stream_key' => 'key_'.rand(100, 999),
            'ingest_url' => 'rtmp://live.jugajug.com/show',
            'playback_url' => 'https://cdn.jugajug.com/live/master.m3u8',
            'status' => 'live',
        ]);

        $response = $this->getJson('/api/v2/live/streams');

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'data']);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_live_stream_creation_via_api(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/live/channels', [
            'title' => 'Bangladesh Independence Day Special',
            'description' => 'Live celebration broadcast',
        ]);

        $response->assertStatus(201);
        $this->assertSame('Bangladesh Independence Day Special', $response->json('data.title'));
        $this->assertSame('ready', $response->json('data.status'));
    }

    public function test_live_stream_comment_and_gift_via_api(): void
    {
        $creator = User::factory()->create();
        $viewer = User::factory()->create();

        $stream = app(LiveStreamingService::class)->createChannel($creator, 'Interactive Stream');
        app(LiveStreamingService::class)->startStream($stream);

        // Comment
        $commentRes = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/live/streams/{$stream->id}/comments", [
                'message' => 'Awesome sound quality!',
            ]);
        $commentRes->assertStatus(201);
        $this->assertSame('Awesome sound quality!', $commentRes->json('data.message'));

        // Gift
        $giftRes = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/live/streams/{$stream->id}/gifts", [
                'gift_type' => 'crown',
                'coins' => 25,
            ]);
        $giftRes->assertStatus(201);
        $this->assertSame('crown', $giftRes->json('data.gift_type'));
    }

    public function test_marketplace_product_listing_and_filtering_via_api(): void
    {
        $seller = User::factory()->create();
        $cat = MarketplaceCategory::create(['name' => 'Computers', 'slug' => 'computers']);

        MarketplaceProduct::create([
            'seller_id' => $seller->id,
            'category_id' => $cat->id,
            'title' => 'MacBook Pro M3 Max',
            'description' => 'Space Black 36GB unified memory',
            'price' => 350000.00,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v2/marketplace/products?q=MacBook');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertSame('MacBook Pro M3 Max', $response->json('data.data.0.title'));
    }

    public function test_marketplace_order_and_escrow_release_via_api(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $cat = MarketplaceCategory::create(['name' => 'Furniture', 'slug' => 'furniture']);

        $product = MarketplaceProduct::create([
            'seller_id' => $seller->id,
            'category_id' => $cat->id,
            'title' => 'Ergonomic Office Chair',
            'description' => 'Comfortable ergonomic chair with lumbar support',
            'price' => 15000.00,
            'status' => 'active',
        ]);

        // Place order
        $orderRes = $this->actingAs($buyer, 'sanctum')
            ->postJson("/api/v2/marketplace/products/{$product->id}/order", [
                'shipping_address' => 'Mirpur DOHS, Dhaka',
                'payment_method' => 'nagad',
            ]);
        $orderRes->assertStatus(201);
        $orderId = $orderRes->json('data.id');

        // Release escrow
        $releaseRes = $this->actingAs($buyer, 'sanctum')
            ->postJson("/api/v2/marketplace/orders/{$orderId}/release-escrow");
        $releaseRes->assertStatus(200);
        $this->assertSame('released', $releaseRes->json('data.escrow_status'));
    }
}
