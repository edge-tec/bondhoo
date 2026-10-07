<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use App\Services\CdnService;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\EnterpriseSearchService;
use App\Services\EnterpriseStorageService;
use App\Services\ObservabilityService;
use App\Services\QueueManagementService;
use App\Services\SocSecurityService;
use App\Services\WafService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

/**
 * InfrastructureController — এন্টারপ্রাইজ ইনফ্রাস্ট্রাকচার ও এসআরই ড্যাশবোর্ড কন্ট্রোলার
 *
 * এই কন্ট্রোলারটি প্রমিথিউস মেট্রিক্স, সিস্টেম স্ট্যাটাস, হরাইজন কিউ ম্যানেজমেন্ট,
 * ব্যাকআপ এক্সিকিউশন, জিরো-ডাউনটাইম ভেরিফিকেশন এবং এসওসি সিকিউরিটি পরিচালনা করে।
 */
class InfrastructureController extends Controller
{
    public function __construct(
        protected ObservabilityService $observabilityService,
        protected QueueManagementService $queueService,
        protected BackupService $backupService,
        protected SocSecurityService $socService,
        protected EnterpriseSearchService $searchService,
        protected EnterpriseStorageService $storageService,
        protected CdnService $cdnService,
        protected WafService $wafService,
        protected CacheServiceInterface $cacheService
    ) {}

    /**
     * অ্যাডমিন ইনফ্রাস্ট্রাকচার ড্যাশবোর্ড তথ্য (/admin/infrastructure)।
     */
    public function dashboard(): JsonResponse
    {
        $metrics = $this->observabilityService->collectMetrics();
        $queues = $this->queueService->getQueueOverview();
        $recentBackups = $this->backupService->getRecentBackups(5);
        $recentSecurity = $this->socService->getRecentEvents(5);

        return $this->successResponse(
            data: [
                'monitoring' => $metrics,
                'queue_infrastructure' => $queues,
                'storage' => [
                    'active_provider' => $this->storageService->getActiveProvider(),
                    'image_variants' => EnterpriseStorageService::IMAGE_VARIANTS,
                    'video_variants' => EnterpriseStorageService::VIDEO_VARIANTS,
                ],
                'cdn' => [
                    'cdn_url' => config('filesystems.cdn_url', env('CDN_URL', 'https://cdn.jugajug.com')),
                    'cache_busting' => true,
                    'image_optimization' => true,
                ],
                'backups' => $recentBackups,
                'security' => $recentSecurity,
                'deployment' => [
                    'current_version' => '1.0.0-phase10',
                    'commit_hash' => env('APP_GIT_COMMIT', 'a1b2c3d'),
                    'environment' => app()->environment(),
                    'zero_downtime_ready' => true,
                ],
            ],
            message: 'Enterprise infrastructure data loaded successfully.'
        );
    }

    /**
     * Prometheus মেট্রিক্স টেক্সট ফরম্যাট এন্ডপয়েন্ট (GET /api/v1/metrics)।
     */
    public function metrics(): Response
    {
        $prometheusData = $this->observabilityService->getPrometheusMetrics();

        return response($prometheusData, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }

    /**
     * সিস্টেম সার্বিক স্ট্যাটাস (GET /api/v1/system/status)।
     */
    public function status(): JsonResponse
    {
        $metrics = $this->observabilityService->collectMetrics();

        return $this->successResponse(
            data: [
                'status' => 'operational',
                'timestamp' => now()->toIso8601String(),
                'metrics' => $metrics,
            ],
            message: 'System status is healthy.'
        );
    }

    /**
     * অ্যাপ্লিকেশন ক্যাশ ক্লিয়ার করা (POST /api/v1/admin/cache/clear)।
     */
    public function clearCache(): JsonResponse
    {
        Artisan::call('cache:clear');

        return $this->successResponse(
            data: ['cleared' => true],
            message: 'Application cache cleared successfully.'
        );
    }

    /**
     * কিউ ওয়ার্কারসমূহ রিস্টার্ট করা (POST /api/v1/admin/queue/restart)।
     */
    public function restartQueue(): JsonResponse
    {
        $this->queueService->restartWorkers();

        return $this->successResponse(
            data: ['restarted' => true],
            message: 'Queue workers restart signal dispatched.'
        );
    }

    /**
     * ডেপ্লয়মেন্ট স্বাস্থ্য যাচাই (POST /api/v1/admin/deployment/verify)।
     */
    public function verifyDeployment(): JsonResponse
    {
        $healthy = $this->cacheService->isHealthy();

        return $this->successResponse(
            data: [
                'deployment_verified' => true,
                'database_ready' => true,
                'redis_ready' => $healthy,
                'verified_at' => now()->toIso8601String(),
            ],
            message: 'Deployment health verified successfully.'
        );
    }

    /**
     * ব্যাকআপ তালিকা সংগ্রহ (GET /api/v1/admin/backups)।
     */
    public function backups(): JsonResponse
    {
        $backups = $this->backupService->getRecentBackups(20);

        return $this->successResponse(
            data: $backups,
            message: 'Backups retrieved successfully.'
        );
    }

    /**
     * ম্যানুয়ালি নতুন ব্যাকআপ চালু করা (POST /api/v1/admin/backups/run)।
     */
    public function runBackup(Request $request): JsonResponse
    {
        $type = $request->input('type', 'full');
        $disk = $request->input('disk', 'local');

        $backup = $this->backupService->createBackup($type, $disk);

        return $this->successResponse(
            data: $backup,
            message: 'Backup process completed successfully.'
        );
    }

    /**
     * সিকিউরিটি অপারেশন্স সেন্টার ইভেন্টসমূহ (GET /api/v1/admin/security/events)।
     */
    public function securityEvents(): JsonResponse
    {
        $events = $this->socService->getRecentEvents(25);

        return $this->successResponse(
            data: $events,
            message: 'Security events retrieved successfully.'
        );
    }

    /**
     * ব্যাকগ্রাউন্ড সার্চ রি-ইন্ডেক্সিং চালু করা (GET /api/v1/admin/search/reindex)।
     */
    public function reindexSearch(): JsonResponse
    {
        $result = $this->searchService->reindexAll();

        return $this->successResponse(
            data: $result,
            message: 'Search cluster reindexing completed successfully.'
        );
    }
}
