<?php

namespace Tests\Feature;

use App\Models\CreatorEarning;
use App\Models\CreatorPayoutRequest;
use App\Models\LiveStream;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Creator\CreatorStudioService;
use App\Services\Streaming\LiveStreamingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorMonetizationAndCoinsTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;

    protected User $viewer;

    protected CreatorStudioService $creatorService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->creator = User::factory()->create([
            'name' => 'রাকিব ক্রিয়েটর',
            'username' => 'rakib_creator',
        ]);

        $this->viewer = User::factory()->create([
            'name' => 'সাদিয়া ফ্যান',
            'username' => 'sadia_fan',
        ]);

        $this->creatorService = app(CreatorStudioService::class);
    }

    public function test_user_can_view_coin_packages_catalog(): void
    {
        $response = $this->actingAs($this->viewer, 'sanctum')
            ->getJson('/api/v2/creator/coins/packages');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $packages = $response->json('data');
        $this->assertIsArray($packages);
        $this->assertNotEmpty($packages);
        $this->assertEquals('coin_pkg_100', $packages[0]['id']);
        $this->assertEquals(100, $packages[0]['coins']);
    }

    public function test_user_can_purchase_coins_and_wallet_is_credited(): void
    {
        $response = $this->actingAs($this->viewer, 'sanctum')
            ->postJson('/api/v2/creator/coins/purchase', [
                'package_id' => 'coin_pkg_500',
                'gateway' => 'sandbox',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.coins_credited', 500)
            ->assertJsonPath('data.coins_balance', 500);

        $wallet = Wallet::where('user_id', $this->viewer->id)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(500, $wallet->coins_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->viewer->id,
            'type' => WalletTransaction::TYPE_COIN_PURCHASE,
            'coins' => 500,
        ]);
    }

    public function test_user_can_send_virtual_gift_to_creator(): void
    {
        // 1. Give viewer 200 coins
        $this->creatorService->creditPurchasedCoins(
            $this->viewer,
            200,
            240.00,
            'bkash',
            'TRX_PRE_TEST_123'
        );

        // 2. Viewer sends Diamond gift (50 coins)
        $response = $this->actingAs($this->viewer, 'sanctum')
            ->postJson('/api/v2/creator/gifts/send', [
                'creator_id' => $this->creator->id,
                'gift_type' => 'diamond',
                'coins' => 50,
                'context' => 'profile',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.gift_type', 'diamond')
            ->assertJsonPath('data.sender_coins_left', 150);

        // 3. Verify Sender Wallet (200 - 50 = 150 coins left)
        $viewerWallet = Wallet::where('user_id', $this->viewer->id)->first();
        $this->assertEquals(150, $viewerWallet->coins_balance);

        // 4. Verify Creator Wallet (50 coins * 1.20 BDT * 0.85 net = 51.00 BDT credited)
        $creatorWallet = Wallet::where('user_id', $this->creator->id)->first();
        $this->assertNotNull($creatorWallet);
        $this->assertEquals(51.00, (float) $creatorWallet->balance);

        // 5. Verify Creator Monthly Earnings Record
        $earning = CreatorEarning::where('user_id', $this->creator->id)->first();
        $this->assertNotNull($earning);
        $this->assertEquals(50, $earning->stars_received);

        // 6. Verify Double-entry ledger transactions
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->viewer->id,
            'type' => WalletTransaction::TYPE_GIFT_SENT,
            'coins' => -50,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->creator->id,
            'type' => WalletTransaction::TYPE_GIFT_RECEIVED,
            'amount' => 51.00,
        ]);
    }

    public function test_user_cannot_send_gift_with_insufficient_coins(): void
    {
        // Viewer only has 10 coins
        $this->creatorService->creditPurchasedCoins(
            $this->viewer,
            10,
            12.00,
            'bkash',
            'TRX_LOW_COINS_123'
        );

        $response = $this->actingAs($this->viewer, 'sanctum')
            ->postJson('/api/v2/creator/gifts/send', [
                'creator_id' => $this->creator->id,
                'gift_type' => 'crown',
                'coins' => 200,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_creator_can_request_cashout_payout(): void
    {
        // Seed creator wallet with 2000 BDT
        $wallet = Wallet::create([
            'user_id' => $this->creator->id,
            'balance' => 2000.00,
            'pending_balance' => 0.00,
            'coins_balance' => 0,
            'currency' => 'BDT',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->creator, 'sanctum')
            ->postJson('/api/v2/creator/payout/request', [
                'amount' => 1200.00,
                'payment_method' => 'bkash',
                'account_number' => '01711000000',
                'account_name' => 'রাকিব ক্রিয়েটর',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount', 1200)
            ->assertJsonPath('data.payment_method', 'bkash')
            ->assertJsonPath('data.status', 'pending');

        $wallet->refresh();
        $this->assertEquals(800.00, (float) $wallet->balance);
        $this->assertEquals(1200.00, (float) $wallet->pending_balance);

        $this->assertDatabaseHas('creator_payout_requests', [
            'user_id' => $this->creator->id,
            'amount' => 1200.00,
            'payment_method' => 'bkash',
            'account_number' => '01711000000',
            'status' => CreatorPayoutRequest::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->creator->id,
            'type' => WalletTransaction::TYPE_PAYOUT_HOLD,
            'amount' => -1200.00,
        ]);
    }

    public function test_creator_cannot_request_payout_below_minimum_or_insufficient_balance(): void
    {
        Wallet::create([
            'user_id' => $this->creator->id,
            'balance' => 300.00,
            'pending_balance' => 0.00,
            'coins_balance' => 0,
            'currency' => 'BDT',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        // Below 500 BDT minimum threshold
        $responseBelowMin = $this->actingAs($this->creator, 'sanctum')
            ->postJson('/api/v2/creator/payout/request', [
                'amount' => 200.00,
                'payment_method' => 'bkash',
                'account_number' => '01711000000',
            ]);

        $responseBelowMin->assertStatus(422);

        // Exceeds available balance
        $responseExceeds = $this->actingAs($this->creator, 'sanctum')
            ->postJson('/api/v2/creator/payout/request', [
                'amount' => 800.00,
                'payment_method' => 'bkash',
                'account_number' => '01711000000',
            ]);

        $responseExceeds->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_approve_and_complete_payout(): void
    {
        $admin = User::factory()->create(['name' => 'সিস্টেম অ্যাডমিন']);

        $wallet = Wallet::create([
            'user_id' => $this->creator->id,
            'balance' => 500.00,
            'pending_balance' => 1000.00,
            'coins_balance' => 0,
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $payout = CreatorPayoutRequest::create([
            'user_id' => $this->creator->id,
            'amount' => 1000.00,
            'currency' => 'BDT',
            'payment_method' => 'nagad',
            'account_number' => '01811223344',
            'status' => CreatorPayoutRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v2/creator/payout/{$payout->id}/process", [
                'action' => 'complete',
                'transaction_reference' => 'NAGAD_TRX_888999',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $wallet->refresh();
        $this->assertEquals(0.00, (float) $wallet->pending_balance);
        $this->assertEquals(500.00, (float) $wallet->balance);

        $payout->refresh();
        $this->assertEquals(CreatorPayoutRequest::STATUS_COMPLETED, $payout->status);
        $this->assertEquals('NAGAD_TRX_888999', $payout->transaction_reference);
        $this->assertEquals($admin->id, $payout->processed_by);
    }

    public function test_admin_can_reject_payout_and_refunds_creator_balance(): void
    {
        $admin = User::factory()->create(['name' => 'সিস্টেম অ্যাডমিন']);

        $wallet = Wallet::create([
            'user_id' => $this->creator->id,
            'balance' => 500.00,
            'pending_balance' => 1000.00,
            'coins_balance' => 0,
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $payout = CreatorPayoutRequest::create([
            'user_id' => $this->creator->id,
            'amount' => 1000.00,
            'currency' => 'BDT',
            'payment_method' => 'bkash',
            'account_number' => '01711999999',
            'status' => CreatorPayoutRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v2/creator/payout/{$payout->id}/process", [
                'action' => 'reject',
                'rejection_reason' => 'অ্যাকাউন্ট নম্বরটি ভুল ছিল।',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $wallet->refresh();
        $this->assertEquals(0.00, (float) $wallet->pending_balance);
        $this->assertEquals(1500.00, (float) $wallet->balance); // 500 + 1000 refunded

        $payout->refresh();
        $this->assertEquals(CreatorPayoutRequest::STATUS_REJECTED, $payout->status);
        $this->assertEquals('অ্যাকাউন্ট নম্বরটি ভুল ছিল।', $payout->rejection_reason);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->creator->id,
            'type' => WalletTransaction::TYPE_PAYOUT_REFUND,
            'amount' => 1000.00,
        ]);
    }

    public function test_creator_overview_endpoint_returns_analytics_and_balances(): void
    {
        Wallet::create([
            'user_id' => $this->creator->id,
            'balance' => 2500.00,
            'pending_balance' => 500.00,
            'coins_balance' => 350,
            'currency' => 'BDT',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $this->creatorService->awardStars($this->creator, 100);

        $response = $this->actingAs($this->creator, 'sanctum')
            ->getJson('/api/v2/creator/overview');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.wallet.cashout_balance', 2500)
            ->assertJsonPath('data.wallet.coins_balance', 350)
            ->assertJsonPath('data.monthly_earnings.stars_count', 100);
    }

    public function test_live_stream_send_gift_deducts_coins_and_credits_streamer(): void
    {
        // 1. Create a live stream for creator
        $stream = LiveStream::create([
            'user_id' => $this->creator->id,
            'title' => 'আড্ডায় গান ও লাইভ গিফটিং',
            'channel_id' => 'stream_test_channel_gift_123',
            'stream_key' => 'live_sk_test_gift_123',
            'ingest_url' => 'rtmp://live.jugajug.com/app/live_sk_test_gift_123',
            'playback_url' => 'https://live-cdn.jugajug.com/hls/stream_test_channel_gift_123.m3u8',
            'status' => LiveStream::STATUS_LIVE,
        ]);

        // 2. Pre-credit viewer with 100 coins
        $this->creatorService->creditPurchasedCoins(
            $this->viewer,
            100,
            120.00,
            'bkash',
            'TRX_LIVE_GIFT_123'
        );

        // 3. Viewer sends Rose gift (10 coins) in live stream
        $streamingService = app(LiveStreamingService::class);
        $gift = $streamingService->sendGift($stream, $this->viewer, 'rose', 10);

        $this->assertNotNull($gift);
        $this->assertEquals(10, $gift->coin_amount);

        // 4. Check viewer wallet deducted by 10 coins (100 - 10 = 90)
        $viewerWallet = Wallet::where('user_id', $this->viewer->id)->first();
        $this->assertEquals(90, $viewerWallet->coins_balance);

        // 5. Check streamer wallet credited (10 coins * 1.20 * 0.85 = 10.20 BDT)
        $creatorWallet = Wallet::where('user_id', $this->creator->id)->first();
        $this->assertEquals(10.20, (float) $creatorWallet->balance);
    }
}
