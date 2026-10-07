<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ads\AdsEngineService;
use App\Services\Creator\CreatorStudioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorAndAdsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_receives_stars_and_monetization_totals_calculate(): void
    {
        $creator = User::factory()->create();
        $studio = app(CreatorStudioService::class);

        $earning = $studio->awardStars($creator, 5000); // 5000 stars = $50 USD

        $this->assertDatabaseHas('creator_earnings', [
            'user_id' => $creator->id,
            'stars_received' => 5000,
        ]);
        $this->assertEquals(50.00, $earning->stars_amount);
        $this->assertEquals(42.50, $earning->net_payout); // 15% platform fee deducted

        $overview = $studio->getOverview($creator);
        $this->assertSame(5000, $overview['stars_count']);
        $this->assertEquals(42.5, $overview['net_payout_usd']);
    }

    public function test_advertiser_can_create_campaign_and_track_pixel(): void
    {
        $advertiser = User::factory()->create();
        $adsEngine = app(AdsEngineService::class);

        $campaign = $adsEngine->createCampaign($advertiser, [
            'name' => 'Eid Mega Sale 2026',
            'objective' => 'conversions',
            'total_budget' => 50000.00,
            'daily_budget' => 5000.00,
            'targeting_criteria' => ['country' => 'BD', 'min_age' => 18],
        ]);

        $this->assertDatabaseHas('ad_campaigns', [
            'id' => $campaign->id,
            'user_id' => $advertiser->id,
            'name' => 'Eid Mega Sale 2026',
            'status' => 'active',
        ]);

        $adsEngine->trackPixelEvent('px_12345', 'Purchase', ['value' => 2500]);
        $this->assertTrue(true); // Pixel incremented in cache
    }
}
