<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\MediaStorageServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * SystemController — সিস্টেম হেলথ ও সার্ভার মেট্রিক্স কন্ট্রোলার
 *
 * এই কন্ট্রোলারটি সোশ্যাল নেটওয়ার্কের কোর আর্কিটেকচার উপাদানসমূহের
 * (ডাটাবেজ, রেডিস, কিউ, অবজেক্ট স্টোরেজ) রিয়েল-টাইম স্বাস্থ্য ও সার্ভার
 * রিসোর্স মেট্রিক্স নিরীক্ষণ করে।
 */
class SystemController extends Controller
{
    public function __construct(
        protected CacheServiceInterface $cacheService,
        protected QueueServiceInterface $queueService,
        protected MediaStorageServiceInterface $mediaStorageService
    ) {}

    /**
     * সিস্টেম হেলথ চেক — ডাটাবেজ, রেডিস, কিউ এবং অবজেক্ট স্টোরেজের স্বাস্থ্য পরীক্ষা।
     */
    public function health(): JsonResponse
    {
        // ১. ডাটাবেজ হেলথ ও লেটেন্সি টেস্ট
        $dbStatus = 'healthy';
        $dbLatencyMs = 0;
        $dbError = null;

        try {
            $startDb = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $dbLatencyMs = round((microtime(true) - $startDb) * 1000, 2);
        } catch (Exception $e) {
            $dbStatus = 'unhealthy';
            $dbError = $e->getMessage();
        }

        // ২. রেডিস ক্যাশ হেলথ ও পিং টেস্ট
        $redisHealthy = $this->cacheService->isHealthy();
        $cacheLatencyMs = 0;
        if ($redisHealthy) {
            $startCache = microtime(true);
            $this->cacheService->set('health_check_ping', 'pong', 5);
            $this->cacheService->get('health_check_ping');
            $cacheLatencyMs = round((microtime(true) - $startCache) * 1000, 2);
        }

        // ৩. কিউ (Queue) সার্ভিস হেলথ টেস্ট
        $queueHealthy = $this->queueService->isHealthy();
        $queueDriver = config('queue.default');

        // ৪. অবজেক্ট স্টোরেজ (Storage Disk) হেলথ টেস্ট
        $storageStatus = 'healthy';
        $storageError = null;
        try {
            $disk = config('filesystems.default', 'public');
            $testFile = 'health-checks/probe_'.uniqid().'.txt';
            Storage::disk($disk)->put($testFile, 'ok');
            Storage::disk($disk)->delete($testFile);
        } catch (Exception $e) {
            $storageStatus = 'degraded';
            $storageError = $e->getMessage();
        }

        // সার্বিক সিস্টেম স্ট্যাটাস মূল্যায়ন
        $isOperational = ($dbStatus === 'healthy') && $redisHealthy && $queueHealthy;
        $overallStatus = $isOperational ? 'operational' : 'degraded';

        return $this->successResponse(
            data: [
                'status' => $overallStatus,
                'services' => [
                    'database' => [
                        'status' => $dbStatus,
                        'driver' => DB::connection()->getDriverName(),
                        'latency_ms' => $dbLatencyMs,
                        'error' => $dbError,
                    ],
                    'redis_cache' => [
                        'status' => $redisHealthy ? 'connected' : 'degraded_fallback',
                        'client' => config('database.redis.client', 'predis'),
                        'latency_ms' => $cacheLatencyMs,
                    ],
                    'queue' => [
                        'status' => $queueHealthy ? 'ready' : 'degraded',
                        'default_connection' => $queueDriver,
                        'priorities' => [
                            QueueServiceInterface::QUEUE_HIGH,
                            QueueServiceInterface::QUEUE_DEFAULT,
                            QueueServiceInterface::QUEUE_MEDIA,
                            QueueServiceInterface::QUEUE_LOW,
                        ],
                    ],
                    'storage' => [
                        'status' => $storageStatus,
                        'disk' => config('filesystems.default', 'public'),
                        'cdn_configured' => ! empty(config('filesystems.cdn_url')),
                        'error' => $storageError,
                    ],
                ],
                'timestamp' => now()->toIso8601String(),
                'version' => '1.0.0-phase9',
            ],
            message: 'System health check completed.'
        );
    }

    /**
     * অ্যাডমিন মেট্রিক্স — মেমোরি কনজাম্পশন, পিএইচপি ও সিস্টেম রিসোর্স সংক্রান্ত তথ্য।
     */
    public function metrics(): JsonResponse
    {
        $memoryUsageBytes = memory_get_usage(true);
        $peakMemoryBytes = memory_get_peak_usage(true);

        $loadAverage = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];

        return $this->successResponse(
            data: [
                'application' => [
                    'name' => config('app.name', 'Jugajug'),
                    'environment' => app()->environment(),
                    'debug_mode' => config('app.debug'),
                    'laravel_version' => app()->version(),
                    'php_version' => PHP_VERSION,
                ],
                'memory' => [
                    'current_usage_mb' => round($memoryUsageBytes / 1024 / 1024, 2),
                    'peak_usage_mb' => round($peakMemoryBytes / 1024 / 1024, 2),
                    'memory_limit' => ini_get('memory_limit'),
                ],
                'system' => [
                    'load_average' => [
                        '1_min' => $loadAverage[0] ?? 0,
                        '5_min' => $loadAverage[1] ?? 0,
                        '15_min' => $loadAverage[2] ?? 0,
                    ],
                    'server_time' => now()->toIso8601String(),
                    'timezone' => config('app.timezone'),
                ],
                'queues' => [
                    'connection' => config('queue.default'),
                    'queues_monitored' => [
                        QueueServiceInterface::QUEUE_HIGH,
                        QueueServiceInterface::QUEUE_DEFAULT,
                        QueueServiceInterface::QUEUE_MEDIA,
                        QueueServiceInterface::QUEUE_LOW,
                    ],
                ],
            ],
            message: 'System metrics retrieved successfully.'
        );
    }
}
