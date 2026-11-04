<?php

declare(strict_types=1);

namespace App\Services\Parental;

use App\Models\Parental\AppUsageLog;
use App\Models\Parental\ChildDevice;
use App\Models\Parental\DeviceRestriction;
use App\Models\Parental\FamilyMember;
use App\Models\Parental\ScreenTimeRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Class UsageTelemetryService
 *
 * Ingests granular Android usage telemetry and maintains daily aggregated screen-time statistics.
 */
class UsageTelemetryService
{
    /**
     * Ingest a batch of foreground application usage telemetry events from an active child device.
     *
     * @param array<int, array{
     *     application_package: string,
     *     usage_type?: string,
     *     duration_seconds: int,
     *     event_started_at: string,
     *     event_ended_at?: string|null
     * }> $events
     * @return int Number of processed records
     */
    public function ingestTelemetry(ChildDevice $device, array $events): int
    {
        if ($device->device_status !== 'active') {
            throw new RuntimeException('Cannot ingest telemetry for an inactive or revoked child device.');
        }

        return DB::transaction(function () use ($device, $events): int {
            $count = 0;
            $now = now();

            foreach ($events as $event) {
                $pkg = trim($event['application_package']);
                $duration = max(0, (int) $event['duration_seconds']);
                $startedAt = Carbon::parse($event['event_started_at']);
                $endedAt = ! empty($event['event_ended_at']) ? Carbon::parse($event['event_ended_at']) : null;
                $usageDate = $startedAt->toDateString();

                // 1. Insert granular event log
                AppUsageLog::create([
                    'child_device_id' => $device->id,
                    'application_package' => $pkg,
                    'usage_type' => $event['usage_type'] ?? 'foreground',
                    'duration_seconds' => $duration,
                    'event_started_at' => $startedAt,
                    'event_ended_at' => $endedAt,
                    'collected_at' => $now,
                    'created_at' => $now,
                ]);

                // 2. Upsert daily screen time record
                $record = ScreenTimeRecord::where('child_device_id', $device->id)
                    ->where('application_package', $pkg)
                    ->whereDate('usage_date', $usageDate)
                    ->first();

                if (! $record) {
                    $record = new ScreenTimeRecord([
                        'child_device_id' => $device->id,
                        'child_member_id' => $device->child_member_id,
                        'application_package' => $pkg,
                        'usage_date' => $usageDate,
                        'foreground_duration_seconds' => 0,
                        'collection_started_at' => $startedAt,
                        'collection_ended_at' => $endedAt ?? $startedAt,
                        'source' => 'usage_stats',
                    ]);
                }

                $record->child_member_id = $device->child_member_id;
                $record->foreground_duration_seconds += $duration;
                $record->collection_ended_at = $endedAt ?? $now;
                $record->save();

                $count++;
            }

            // Update device heartbeat
            $device->update(['last_seen_at' => $now]);

            return $count;
        });
    }

    /**
     * Retrieve aggregated screen-time analytics for a child member.
     *
     * @return array{
     *     child_id: int,
     *     date: string,
     *     total_seconds: int,
     *     total_minutes: int,
     *     apps: array<int, array{package: string, duration_seconds: int, duration_minutes: int}>
     * }
     */
    public function getChildDailySummary(FamilyMember $childMember, ?string $date = null): array
    {
        $targetDate = $date ?? now()->toDateString();

        $records = ScreenTimeRecord::where('child_member_id', $childMember->id)
            ->whereDate('usage_date', $targetDate)
            ->orderByDesc('foreground_duration_seconds')
            ->get();

        $totalSeconds = (int) $records->sum('foreground_duration_seconds');

        $apps = $records->map(fn (ScreenTimeRecord $r) => [
            'package' => $r->application_package,
            'duration_seconds' => $r->foreground_duration_seconds,
            'duration_minutes' => (int) round($r->foreground_duration_seconds / 60),
        ])->values()->all();

        return [
            'child_id' => $childMember->id,
            'date' => $targetDate,
            'total_seconds' => $totalSeconds,
            'total_minutes' => (int) round($totalSeconds / 60),
            'apps' => $apps,
        ];
    }

    /**
     * Retrieve weekly aggregated screen time trend (last 7 days).
     *
     * @return array{
     *     child_id: int,
     *     start_date: string,
     *     end_date: string,
     *     total_weekly_minutes: int,
     *     daily_breakdown: array<string, int>,
     *     top_apps: array<int, array{package: string, duration_minutes: int}>
     * }
     */
    public function getChildWeeklySummary(FamilyMember $childMember, ?string $referenceDate = null): array
    {
        $end = $referenceDate ? Carbon::parse($referenceDate) : now();
        $start = $end->copy()->subDays(6);

        $records = ScreenTimeRecord::where('child_member_id', $childMember->id)
            ->whereDate('usage_date', '>=', $start->toDateString())
            ->whereDate('usage_date', '<=', $end->toDateString())
            ->get();

        $dailyBreakdown = [];
        for ($i = 0; $i < 7; $i++) {
            $dayStr = $start->copy()->addDays($i)->toDateString();
            $dailyBreakdown[$dayStr] = 0;
        }

        foreach ($records as $r) {
            $d = Carbon::parse($r->usage_date)->toDateString();
            if (isset($dailyBreakdown[$d])) {
                $dailyBreakdown[$d] += (int) round($r->foreground_duration_seconds / 60);
            }
        }

        $appGroups = $records->groupBy('application_package')->map(function ($group, $pkg) {
            $mins = (int) round($group->sum('foreground_duration_seconds') / 60);

            return ['package' => $pkg, 'duration_minutes' => $mins];
        })->sortByDesc('duration_minutes')->values()->take(5)->all();

        return [
            'child_id' => $childMember->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_weekly_minutes' => array_sum($dailyBreakdown),
            'daily_breakdown' => $dailyBreakdown,
            'top_apps' => $appGroups,
        ];
    }

    /**
     * Calculate remaining screen-time quota for a child for today.
     *
     * @return array{
     *     limit_minutes: int|null,
     *     used_minutes: int,
     *     remaining_minutes: int|null,
     *     is_quota_exceeded: bool
     * }
     */
    public function calculateRemainingQuota(FamilyMember $childMember, ?string $date = null): array
    {
        $targetDate = $date ?? now()->toDateString();
        $dailySummary = $this->getChildDailySummary($childMember, $targetDate);
        $usedMinutes = $dailySummary['total_minutes'];

        // Check if there is an active daily_time_limit restriction
        /** @var DeviceRestriction|null $restriction */
        $restriction = DeviceRestriction::where('family_id', $childMember->family_id)
            ->where('child_member_id', $childMember->id)
            ->where('restriction_type', 'daily_time_limit')
            ->where('enabled', true)
            ->first();

        $limitMinutes = $restriction?->configuration_json['daily_minutes'] ?? null;
        $remainingMinutes = null;
        $isExceeded = false;

        if ($limitMinutes !== null) {
            $remainingMinutes = max(0, $limitMinutes - $usedMinutes);
            $isExceeded = $usedMinutes >= $limitMinutes;
        }

        return [
            'limit_minutes' => $limitMinutes,
            'used_minutes' => $usedMinutes,
            'remaining_minutes' => $remainingMinutes,
            'is_quota_exceeded' => $isExceeded,
        ];
    }
}
