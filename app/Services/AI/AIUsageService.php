<?php

namespace App\Services\AI;

use App\Models\AiUsageLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AIUsageService
{
    /**
     * Get aggregate usage statistics for the platform.
     *
     * @return array<string, mixed>
     */
    public function getSystemUsageSummary(int $days = 30): array
    {
        $since = Carbon::now()->subDays($days);

        $totalTokens = (int) AiUsageLog::where('created_at', '>=', $since)->sum('total_tokens');
        $totalCost = (float) AiUsageLog::where('created_at', '>=', $since)->sum('cost_usd');
        $totalCalls = (int) AiUsageLog::where('created_at', '>=', $since)->count();
        $avgLatency = (int) AiUsageLog::where('created_at', '>=', $since)->avg('latency_ms');

        $byProvider = AiUsageLog::where('created_at', '>=', $since)
            ->select('provider', DB::raw('count(*) as count'), DB::raw('sum(total_tokens) as tokens'), DB::raw('sum(cost_usd) as cost'))
            ->groupBy('provider')
            ->get()
            ->keyBy('provider')
            ->toArray();

        return [
            'period_days' => $days,
            'total_calls' => $totalCalls,
            'total_tokens' => $totalTokens,
            'total_cost_usd' => round($totalCost, 4),
            'average_latency_ms' => $avgLatency,
            'providers' => $byProvider,
        ];
    }

    /**
     * Get per-user AI usage metrics.
     *
     * @return array<string, mixed>
     */
    public function getUserUsage(int $userId): array
    {
        $sinceToday = Carbon::today();

        $dailyTokens = (int) AiUsageLog::where('user_id', $userId)->where('created_at', '>=', $sinceToday)->sum('total_tokens');
        $dailyCalls = (int) AiUsageLog::where('user_id', $userId)->where('created_at', '>=', $sinceToday)->count();

        // 100,000 tokens daily free quota per user
        $dailyQuota = 100000;

        return [
            'user_id' => $userId,
            'daily_calls' => $dailyCalls,
            'daily_tokens' => $dailyTokens,
            'daily_quota' => $dailyQuota,
            'quota_remaining' => max(0, $dailyQuota - $dailyTokens),
            'quota_exceeded' => $dailyTokens >= $dailyQuota,
        ];
    }
}
