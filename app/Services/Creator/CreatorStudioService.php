<?php

namespace App\Services\Creator;

use App\Models\CreatorEarning;
use App\Models\User;

class CreatorStudioService
{
    /**
     * Credit stars to creator and calculate monetary value.
     */
    public function awardStars(User $creator, int $starsCount): CreatorEarning
    {
        $period = now()->format('Y-m');
        // Each star is worth 0.01 USD (~ 1.20 BDT)
        $starValueUsd = 0.01;
        $earnedAmount = $starsCount * $starValueUsd;

        $earning = CreatorEarning::firstOrCreate(
            ['user_id' => $creator->id, 'period_month' => $period],
            [
                'stars_received' => 0,
                'stars_amount' => 0.00,
                'subscription_amount' => 0.00,
                'ad_rev_share' => 0.00,
                'gross_total' => 0.00,
                'platform_fee' => 0.00,
                'net_payout' => 0.00,
                'payout_status' => 'pending',
            ]
        );

        $newStars = $earning->stars_received + $starsCount;
        $newStarsAmount = $earning->stars_amount + $earnedAmount;
        $gross = $newStarsAmount + $earning->subscription_amount + $earning->ad_rev_share;
        $fee = round($gross * 0.15, 2); // 15% platform fee
        $net = $gross - $fee;

        $earning->update([
            'stars_received' => $newStars,
            'stars_amount' => $newStarsAmount,
            'gross_total' => $gross,
            'platform_fee' => $fee,
            'net_payout' => $net,
        ]);

        return $earning;
    }

    /**
     * Get Creator Studio dashboard overview.
     *
     * @return array<string, mixed>
     */
    public function getOverview(User $creator): array
    {
        $period = now()->format('Y-m');
        $earning = CreatorEarning::where('user_id', $creator->id)->where('period_month', $period)->first();

        return [
            'creator_id' => $creator->id,
            'month' => $period,
            'stars_count' => $earning?->stars_received ?? 0,
            'stars_revenue_usd' => (float) ($earning?->stars_amount ?? 0),
            'subscription_revenue_usd' => (float) ($earning?->subscription_amount ?? 0),
            'ad_revenue_usd' => (float) ($earning?->ad_rev_share ?? 0),
            'gross_revenue_usd' => (float) ($earning?->gross_total ?? 0),
            'net_payout_usd' => (float) ($earning?->net_payout ?? 0),
            'payout_status' => $earning?->payout_status ?? 'pending',
        ];
    }
}
