<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InfrastructureHealthService
{
    /**
     * Get Cached Infrastructure Health Status with explicit freshness.
     */
    public function getHealthSummary(bool $forceRefresh = false): array
    {
        $cacheKey = 'admin_infra_health_summary';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 60, function () {
            // 1. Database Health
            $startDb = microtime(true);
            $dbStatus = 'healthy';
            $dbError = null;
            try {
                DB::select('SELECT 1');
                $dbLatency = round((microtime(true) - $startDb) * 1000, 2);
            } catch (\Throwable $e) {
                $dbStatus = 'critical';
                $dbLatency = -1;
                $dbError = 'ডাটাবেজ সংযোগে ব্যর্থতা';
            }

            // 2. Cache Health
            $startCache = microtime(true);
            $cacheStatus = 'healthy';
            try {
                Cache::put('health_ping', 'pong', 10);
                $cacheRead = Cache::get('health_ping') === 'pong';
                $cacheLatency = round((microtime(true) - $startCache) * 1000, 2);
                if (! $cacheRead) {
                    $cacheStatus = 'degraded';
                }
            } catch (\Throwable) {
                $cacheStatus = 'degraded';
                $cacheLatency = -1;
            }

            // 3. Storage Health & Writable Directories
            $storageWritable = is_writable(storage_path());
            $publicWritable = is_writable(public_path());
            $storageStatus = ($storageWritable && $publicWritable) ? 'healthy' : 'degraded';

            // Disk Free Space (if permitted by environment)
            $diskFree = @disk_free_space(storage_path());
            $diskTotal = @disk_total_space(storage_path());
            $diskUsagePct = ($diskFree && $diskTotal) ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1) : null;

            // 4. Overall Health Status Evaluation
            $overall = 'HEALTHY';
            if ($dbStatus === 'critical') {
                $overall = 'CRITICAL';
            } elseif ($dbStatus === 'degraded' || $cacheStatus === 'degraded' || $storageStatus === 'degraded') {
                $overall = 'DEGRADED';
            }

            return [
                'overall_status' => $overall,
                'database' => [
                    'status' => $dbStatus,
                    'latency_ms' => $dbLatency,
                    'driver' => config('database.default'),
                    'error' => $dbError,
                ],
                'cache' => [
                    'status' => $cacheStatus,
                    'latency_ms' => $cacheLatency,
                    'store' => config('cache.default'),
                ],
                'storage' => [
                    'status' => $storageStatus,
                    'storage_writable' => $storageWritable,
                    'public_writable' => $publicWritable,
                    'disk_usage_pct' => $diskUsagePct,
                ],
                'system' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'environment' => config('app.env'),
                    'server_time' => now()->toIso8601String(),
                ],
                'freshness' => [
                    'cached_at' => now()->toIso8601String(),
                    'freshness_seconds' => 60,
                ],
            ];
        });
    }
}
