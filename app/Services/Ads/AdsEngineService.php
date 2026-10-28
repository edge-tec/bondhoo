<?php

namespace App\Services\Ads;

use App\Models\AdCampaign;
use App\Models\Post;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * সেলফ-সার্ভ বিজ্ঞাপন ও পোস্ট বুস্টিং ইঞ্জিন সার্ভিস:
 * ক্যাম্পেইন তৈরি, পোস্ট বুস্টিং, বাজেট ও টার্গেটিং, ইমপ্রেশন ও ক্লিক ট্র্যাকিং,
 * স্পেন্ডিং ক্যালকুলেশন এবং স্পনসরড অ্যাড ডেলিভারি হ্যান্ডেল করে।
 */
class AdsEngineService
{
    /**
     * Default Cost Per Mille (CPM) - 10 BDT per 1,000 impressions.
     */
    public const DEFAULT_CPM_BDT = 10.00;

    /**
     * Default Cost Per Click (CPC) - 1.50 BDT per click.
     */
    public const DEFAULT_CPC_BDT = 1.50;

    /**
     * Minimum campaign budget in BDT.
     */
    public const MIN_BUDGET_BDT = 100.00;

    /**
     * Create a standard advertising campaign.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCampaign(User $user, array $data): AdCampaign
    {
        return AdCampaign::create([
            'user_id' => $user->id,
            'post_id' => $data['post_id'] ?? null,
            'name' => $data['name'],
            'objective' => $data['objective'] ?? AdCampaign::OBJECTIVE_REACH,
            'total_budget' => (float) ($data['total_budget'] ?? self::MIN_BUDGET_BDT),
            'daily_budget' => (float) ($data['daily_budget'] ?? 50.00),
            'amount_spent' => 0.00,
            'cpm_rate' => (float) ($data['cpm_rate'] ?? self::DEFAULT_CPM_BDT),
            'cpc_rate' => (float) ($data['cpc_rate'] ?? self::DEFAULT_CPC_BDT),
            'status' => $data['status'] ?? AdCampaign::STATUS_ACTIVE,
            'billing_status' => $data['billing_status'] ?? AdCampaign::BILLING_PAID,
            'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? null,
            'targeting_criteria' => $data['targeting_criteria'] ?? [],
            'ad_creatives' => $data['ad_creatives'] ?? [],
            'cta_text' => $data['cta_text'] ?? null,
            'cta_link' => $data['cta_link'] ?? null,
        ]);
    }

    /**
     * Boost an existing post with audience targeting, budget, and wallet deduction.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws Exception
     */
    public function boostPost(User $user, int $postId, array $params): AdCampaign
    {
        $post = Post::findOrFail($postId);

        // Authorization check: user must own the post or manage the page
        if ($post->user_id !== $user->id) {
            if (! $post->page_id || ! $post->page?->isOwnerOrAdmin($user)) {
                throw new Exception('এই পোস্টটি বুস্ট করার অনুমতি আপনার নেই।');
            }
        }

        $totalBudget = (float) ($params['total_budget'] ?? 0.0);
        if ($totalBudget < self::MIN_BUDGET_BDT) {
            throw new InvalidArgumentException('সর্বনিম্ন বুস্টিং বাজেট '.self::MIN_BUDGET_BDT.' BDT হতে হবে।');
        }

        $durationDays = max(1, (int) ($params['duration_days'] ?? 3));
        $dailyBudget = round($totalBudget / $durationDays, 2);
        $startDate = isset($params['start_date']) ? now()->parse($params['start_date']) : now();
        $endDate = $startDate->copy()->addDays($durationDays);

        $payFromWallet = $params['pay_from_wallet'] ?? true;

        return DB::transaction(function () use ($user, $post, $totalBudget, $dailyBudget, $startDate, $endDate, $params, $payFromWallet): AdCampaign {
            if ($payFromWallet) {
                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0.00, 'pending_balance' => 0.00, 'coins_balance' => 0, 'status' => Wallet::STATUS_ACTIVE]
                );

                $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

                if (! $lockedWallet->hasSufficientBalance($totalBudget)) {
                    throw new Exception("ওয়ালেটে পর্যাপ্ত ব্যালেন্স নেই। আপনার ব্যালেন্স: {$lockedWallet->balance} BDT, প্রয়োজনীয় বাজেট: {$totalBudget} BDT।");
                }

                $newBalance = $lockedWallet->balance - $totalBudget;
                $lockedWallet->update(['balance' => $newBalance]);

                WalletTransaction::create([
                    'wallet_id' => $lockedWallet->id,
                    'user_id' => $user->id,
                    'type' => 'ad_spend',
                    'amount' => -$totalBudget,
                    'coins' => 0,
                    'currency' => 'BDT',
                    'reference_id' => "BOOST_POST_{$post->id}",
                    'description' => "পোস্ট #{$post->id} বুস্টিং ক্যাম্পেইন বাবদ চার্জ।",
                    'balance_after' => $newBalance,
                    'coins_after' => $lockedWallet->coins_balance,
                ]);
            }

            $campaign = AdCampaign::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'name' => $params['name'] ?? "পোস্ট বুস্ট: {$post->id}",
                'objective' => $params['objective'] ?? AdCampaign::OBJECTIVE_ENGAGEMENT,
                'total_budget' => $totalBudget,
                'daily_budget' => $dailyBudget,
                'amount_spent' => 0.00,
                'cpm_rate' => (float) ($params['cpm_rate'] ?? self::DEFAULT_CPM_BDT),
                'cpc_rate' => (float) ($params['cpc_rate'] ?? self::DEFAULT_CPC_BDT),
                'status' => AdCampaign::STATUS_ACTIVE,
                'billing_status' => AdCampaign::BILLING_PAID,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'targeting_criteria' => [
                    'gender' => $params['gender'] ?? 'all', // all, male, female
                    'age_min' => (int) ($params['age_min'] ?? 18),
                    'age_max' => (int) ($params['age_max'] ?? 65),
                    'locations' => $params['locations'] ?? ['Bangladesh'],
                    'interests' => $params['interests'] ?? [],
                ],
                'ad_creatives' => [
                    'headline' => $params['headline'] ?? mb_substr($post->content ?? '', 0, 80),
                    'body' => $params['body'] ?? $post->content,
                ],
                'cta_text' => $params['cta_text'] ?? 'Learn More',
                'cta_link' => $params['cta_link'] ?? url("/posts/{$post->id}"),
            ]);

            Log::info("AdsEngineService: Post {$post->id} boosted successfully by User {$user->id}.", [
                'campaign_id' => $campaign->id,
                'total_budget' => $totalBudget,
            ]);

            return $campaign;
        });
    }

    /**
     * Record an ad impression and calculate dynamic CPM cost deduction.
     *
     * @return array<string, mixed>
     */
    public function recordImpression(int $campaignId, ?int $viewerId = null): array
    {
        $campaign = AdCampaign::find($campaignId);
        if (! $campaign || $campaign->status !== AdCampaign::STATUS_ACTIVE) {
            return ['recorded' => false, 'reason' => 'inactive_or_not_found'];
        }

        // Deduct proportional CPM cost: (CPM / 1000)
        $impressionCost = round($campaign->cpm_rate / 1000, 4);
        $newSpent = (float) $campaign->amount_spent + $impressionCost;
        $newImpressions = $campaign->impressions_count + 1;

        $isCompleted = $newSpent >= (float) $campaign->total_budget;

        $campaign->update([
            'impressions_count' => $newImpressions,
            'amount_spent' => $newSpent,
            'status' => $isCompleted ? AdCampaign::STATUS_COMPLETED : AdCampaign::STATUS_ACTIVE,
        ]);

        return [
            'recorded' => true,
            'campaign_id' => $campaign->id,
            'impressions_count' => $newImpressions,
            'amount_spent' => $newSpent,
            'is_completed' => $isCompleted,
        ];
    }

    /**
     * Record an ad click and calculate CPC cost deduction.
     *
     * @return array<string, mixed>
     */
    public function recordClick(int $campaignId, ?int $viewerId = null): array
    {
        $campaign = AdCampaign::find($campaignId);
        if (! $campaign || $campaign->status !== AdCampaign::STATUS_ACTIVE) {
            return ['recorded' => false, 'reason' => 'inactive_or_not_found'];
        }

        $clickCost = (float) $campaign->cpc_rate;
        $newSpent = (float) $campaign->amount_spent + $clickCost;
        $newClicks = $campaign->clicks_count + 1;

        $isCompleted = $newSpent >= (float) $campaign->total_budget;

        $campaign->update([
            'clicks_count' => $newClicks,
            'amount_spent' => $newSpent,
            'status' => $isCompleted ? AdCampaign::STATUS_COMPLETED : AdCampaign::STATUS_ACTIVE,
        ]);

        return [
            'recorded' => true,
            'campaign_id' => $campaign->id,
            'clicks_count' => $newClicks,
            'amount_spent' => $newSpent,
            'is_completed' => $isCompleted,
        ];
    }

    /**
     * Pause an active campaign.
     *
     * @throws Exception
     */
    public function pauseCampaign(User $user, int $campaignId): AdCampaign
    {
        $campaign = AdCampaign::where('id', $campaignId)->where('user_id', $user->id)->firstOrFail();

        if ($campaign->status === AdCampaign::STATUS_COMPLETED) {
            throw new Exception('সম্পূর্ণ হওয়া ক্যাম্পেইন পজ করা সম্ভব নয়।');
        }

        $campaign->update(['status' => AdCampaign::STATUS_PAUSED]);

        return $campaign;
    }

    /**
     * Resume a paused campaign.
     *
     * @throws Exception
     */
    public function resumeCampaign(User $user, int $campaignId): AdCampaign
    {
        $campaign = AdCampaign::where('id', $campaignId)->where('user_id', $user->id)->firstOrFail();

        if (! $campaign->hasBudgetRemaining()) {
            throw new Exception('ক্যাম্পেইনের মোট বাজেট শেষ হয়ে গেছে। পুনরায় বাজেট বৃদ্ধি করুন।');
        }

        $campaign->update(['status' => AdCampaign::STATUS_ACTIVE]);

        return $campaign;
    }

    /**
     * Get detailed campaign performance metrics.
     *
     * @return array<string, mixed>
     */
    public function getCampaignAnalytics(User $user, int $campaignId): array
    {
        $campaign = AdCampaign::where('id', $campaignId)->where('user_id', $user->id)->firstOrFail();

        $budgetRemaining = max(0.00, (float) $campaign->total_budget - (float) $campaign->amount_spent);
        $ctr = $campaign->calculateCtr();

        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'post_id' => $campaign->post_id,
            'objective' => $campaign->objective,
            'status' => $campaign->status,
            'is_active' => $campaign->isActive(),
            'total_budget' => (float) $campaign->total_budget,
            'daily_budget' => (float) $campaign->daily_budget,
            'amount_spent' => (float) $campaign->amount_spent,
            'budget_remaining' => $budgetRemaining,
            'impressions_count' => $campaign->impressions_count,
            'clicks_count' => $campaign->clicks_count,
            'ctr_percentage' => $ctr,
            'cpm_rate' => (float) $campaign->cpm_rate,
            'cpc_rate' => (float) $campaign->cpc_rate,
            'start_date' => $campaign->start_date?->toIso8601String(),
            'end_date' => $campaign->end_date?->toIso8601String(),
            'cta_text' => $campaign->cta_text,
            'cta_link' => $campaign->cta_link,
            'targeting' => $campaign->targeting_criteria,
        ];
    }

    /**
     * Get user's ad campaigns with pagination.
     */
    public function getUserCampaigns(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return AdCampaign::where('user_id', $user->id)
            ->with(['post.author', 'post.media'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Fetch sponsored ads for social feed matching active criteria.
     *
     * @return Collection<int, AdCampaign>
     */
    public function getSponsoredAdsForFeed(int $limit = 2): Collection
    {
        return AdCampaign::where('status', AdCampaign::STATUS_ACTIVE)
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->whereColumn('amount_spent', '<', 'total_budget')
            ->with(['post.author', 'post.media'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
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
}
