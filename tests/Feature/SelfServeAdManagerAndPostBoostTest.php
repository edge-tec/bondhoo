<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\Post;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Ads\AdsEngineService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelfServeAdManagerAndPostBoostTest extends TestCase
{
    use RefreshDatabase;

    protected User $advertiser;

    protected User $otherUser;

    protected AdsEngineService $adsEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->advertiser = User::factory()->create([
            'name' => 'তানভীর উদ্যোক্তা',
            'username' => 'tanvir_business',
        ]);

        $this->otherUser = User::factory()->create([
            'name' => 'অন্য ইউজার',
            'username' => 'other_user',
        ]);

        $this->adsEngine = app(AdsEngineService::class);
    }

    public function test_user_can_boost_an_existing_post_using_wallet(): void
    {
        // 1. Create a post
        $post = Post::create([
            'user_id' => $this->advertiser->id,
            'content' => 'আমাদের প্রিমিয়াম পাঞ্জাবি কালেকশনে চলছে ৫০% বিশেষ ছাড়!',
            'audience' => 'public',
            'status' => 'published',
        ]);

        // 2. Fund advertiser wallet with 1000 BDT
        Wallet::create([
            'user_id' => $this->advertiser->id,
            'balance' => 1000.00,
            'pending_balance' => 0.00,
            'coins_balance' => 0,
            'currency' => 'BDT',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        // 3. Boost post
        $response = $this->actingAs($this->advertiser, 'sanctum')
            ->postJson("/api/v2/ads/boost/{$post->id}", [
                'total_budget' => 300.00,
                'duration_days' => 3,
                'objective' => 'engagement',
                'gender' => 'all',
                'age_min' => 18,
                'age_max' => 45,
                'locations' => ['Dhaka', 'Chittagong'],
                'cta_text' => 'Shop Now',
                'cta_link' => 'https://jugajug.com/shop/panjabi',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.post_id', $post->id)
            ->assertJsonPath('data.total_budget', 300)
            ->assertJsonPath('data.daily_budget', 100)
            ->assertJsonPath('data.status', AdCampaign::STATUS_ACTIVE)
            ->assertJsonPath('data.cta_text', 'Shop Now');

        // 4. Assert DB state
        $this->assertDatabaseHas('ad_campaigns', [
            'user_id' => $this->advertiser->id,
            'post_id' => $post->id,
            'total_budget' => 300.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'cta_text' => 'Shop Now',
        ]);

        // 5. Assert Wallet deducted (1000 - 300 = 700 BDT)
        $wallet = Wallet::where('user_id', $this->advertiser->id)->first();
        $this->assertEquals(700.00, (float) $wallet->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->advertiser->id,
            'type' => 'ad_spend',
            'amount' => -300.00,
        ]);
    }

    public function test_user_cannot_boost_post_with_insufficient_wallet_balance(): void
    {
        $post = Post::create([
            'user_id' => $this->advertiser->id,
            'content' => 'নতুন রেসিপি ভিডিও',
            'audience' => 'public',
            'status' => 'published',
        ]);

        // Wallet only has 50 BDT
        Wallet::create([
            'user_id' => $this->advertiser->id,
            'balance' => 50.00,
            'currency' => 'BDT',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->advertiser, 'sanctum')
            ->postJson("/api/v2/ads/boost/{$post->id}", [
                'total_budget' => 200.00,
                'duration_days' => 2,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_user_cannot_boost_another_users_post(): void
    {
        $otherPost = Post::create([
            'user_id' => $this->otherUser->id,
            'content' => 'ব্যক্তিগত পোস্ট',
            'audience' => 'public',
            'status' => 'published',
        ]);

        Wallet::create([
            'user_id' => $this->advertiser->id,
            'balance' => 500.00,
            'currency' => 'BDT',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->advertiser, 'sanctum')
            ->postJson("/api/v2/ads/boost/{$otherPost->id}", [
                'total_budget' => 200.00,
                'duration_days' => 2,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_user_can_view_campaign_analytics(): void
    {
        $campaign = AdCampaign::create([
            'user_id' => $this->advertiser->id,
            'name' => 'সামার ক্যাম্পেইন',
            'objective' => 'traffic',
            'total_budget' => 500.00,
            'daily_budget' => 100.00,
            'amount_spent' => 150.00,
            'impressions_count' => 2500,
            'clicks_count' => 75,
            'status' => AdCampaign::STATUS_ACTIVE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(4),
            'cta_text' => 'Learn More',
        ]);

        $response = $this->actingAs($this->advertiser, 'sanctum')
            ->getJson("/api/v2/ads/campaigns/{$campaign->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_budget', 500)
            ->assertJsonPath('data.amount_spent', 150)
            ->assertJsonPath('data.budget_remaining', 350)
            ->assertJsonPath('data.impressions_count', 2500)
            ->assertJsonPath('data.clicks_count', 75);

        $this->assertEquals(3.0, (float) $response->json('data.ctr_percentage'));
    }

    public function test_user_can_pause_and_resume_campaign(): void
    {
        $campaign = AdCampaign::create([
            'user_id' => $this->advertiser->id,
            'name' => 'পজ ও রেজুম টেস্ট',
            'total_budget' => 300.00,
            'daily_budget' => 100.00,
            'amount_spent' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'start_date' => now(),
        ]);

        // 1. Pause
        $pauseRes = $this->actingAs($this->advertiser, 'sanctum')
            ->postJson("/api/v2/ads/campaigns/{$campaign->id}/pause");

        $pauseRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', AdCampaign::STATUS_PAUSED);

        $campaign->refresh();
        $this->assertEquals(AdCampaign::STATUS_PAUSED, $campaign->status);

        // 2. Resume
        $resumeRes = $this->actingAs($this->advertiser, 'sanctum')
            ->postJson("/api/v2/ads/campaigns/{$campaign->id}/resume");

        $resumeRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', AdCampaign::STATUS_ACTIVE);

        $campaign->refresh();
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->status);
    }

    public function test_ad_impression_and_click_tracking_exhausts_budget_and_completes(): void
    {
        // Campaign with 5 BDT budget and 2.50 BDT CPC
        $campaign = AdCampaign::create([
            'user_id' => $this->advertiser->id,
            'name' => 'মাইক্রো বাজেট ক্যাম্পেইন',
            'total_budget' => 5.00,
            'daily_budget' => 5.00,
            'amount_spent' => 0.00,
            'cpm_rate' => 10.00,
            'cpc_rate' => 2.50,
            'status' => AdCampaign::STATUS_ACTIVE,
            'start_date' => now(),
        ]);

        // 1. Record Impression
        $impRes = $this->actingAs($this->otherUser, 'sanctum')
            ->postJson("/api/v2/ads/campaigns/{$campaign->id}/impression");
        $impRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.impressions_count', 1);

        // 2. Record First Click (Spend + 2.50 = 2.51 BDT)
        $click1 = $this->actingAs($this->otherUser, 'sanctum')
            ->postJson("/api/v2/ads/campaigns/{$campaign->id}/click");
        $click1->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.clicks_count', 1);

        $campaign->refresh();
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->status);

        // 3. Record Second Click (Spend reaches > 5.00 BDT) -> Auto completes!
        $click2 = $this->actingAs($this->otherUser, 'sanctum')
            ->postJson("/api/v2/ads/campaigns/{$campaign->id}/click");
        $click2->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_completed', true);

        $campaign->refresh();
        $this->assertEquals(AdCampaign::STATUS_COMPLETED, $campaign->status);
    }

    public function test_feed_ads_endpoint_returns_active_sponsored_posts(): void
    {
        $post = Post::create([
            'user_id' => $this->advertiser->id,
            'content' => 'স্পনসরড রিল প্রমোশন!',
            'audience' => 'public',
            'status' => 'published',
        ]);

        AdCampaign::create([
            'user_id' => $this->advertiser->id,
            'post_id' => $post->id,
            'name' => 'লাইভ ফিড অ্যাড',
            'total_budget' => 500.00,
            'daily_budget' => 100.00,
            'amount_spent' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'start_date' => now()->subHour(),
            'end_date' => now()->addDays(5),
            'cta_text' => 'Install App',
            'cta_link' => 'https://play.google.com/store/apps/details?id=com.bondhoo',
        ]);

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->getJson('/api/v2/ads/feed');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $ads = $response->json('data');
        $this->assertNotEmpty($ads);
        $this->assertEquals('Install App', $ads[0]['cta_text']);
        $this->assertEquals($post->id, $ads[0]['post_id']);
    }
}
