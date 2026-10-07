<?php

namespace App\Services\Ads;

use App\Models\AdCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class AdsEngineService
{
    /**
     * Create a new advertising campaign.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCampaign(User $user, array $data): AdCampaign
    {
        return AdCampaign::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'objective' => $data['objective'] ?? 'reach',
            'total_budget' => $data['total_budget'],
            'daily_budget' => $data['daily_budget'],
            'amount_spent' => 0.00,
            'status' => 'active',
            'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? null,
            'targeting_criteria' => $data['targeting_criteria'] ?? [],
            'ad_creatives' => $data['ad_creatives'] ?? [],
        ]);
    }

    /**
     * Track tracking pixel event (Pageview, ViewContent, AddToCart, Purchase).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function trackPixelEvent(string $pixelId, string $eventName, array $metadata = []): void
    {
        $key = "pixel:{$pixelId}:".now()->format('Y-m-d');
        Cache::increment("{$key}:events:{$eventName}");
    }

    /**
     * Fetch sponsored ads matching user audience context.
     *
     * @return Collection<int, AdCampaign>
     */
    public function getSponsoredAdsForFeed(int $limit = 2): Collection
    {
        return AdCampaign::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}
