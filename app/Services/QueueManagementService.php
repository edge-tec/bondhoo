<?php

namespace App\Services;

use App\Services\Contracts\CacheServiceInterface;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * QueueManagementService — এন্টারপ্রাইজ কিউ ও হরাইজন ম্যানেজমেন্ট
 *
 * এই সার্ভিসটি কিউ পজ, রিজিউম, ক্লিয়ার, ফেইল্ড জব রিট্রাই এবং ওয়ার্কার রিস্টার্ট পরিচালনা করে।
 */
class QueueManagementService
{
    public function __construct(
        protected CacheServiceInterface $cacheService
    ) {}

    /**
     * নির্দিষ্ট কিউ পজ (Pause) করা।
     */
    public function pauseQueue(string $queue): bool
    {
        $this->cacheService->set("queue:paused:{$queue}", true, 86400);

        return true;
    }

    /**
     * নির্দিষ্ট কিউ পুনরায় চালু (Resume) করা।
     */
    public function resumeQueue(string $queue): bool
    {
        $this->cacheService->forget("queue:paused:{$queue}");

        return true;
    }

    /**
     * নির্দিষ্ট কিউ পজ করা আছে কিনা যাচাই।
     */
    public function isQueuePaused(string $queue): bool
    {
        return (bool) $this->cacheService->get("queue:paused:{$queue}", false);
    }

    /**
     * নির্দিষ্ট কিউ খালি (Clear) করা।
     */
    public function clearQueue(string $queue): int
    {
        try {
            if ($this->cacheService->isHealthy()) {
                $count = (int) Redis::llen("queues:{$queue}");
                Redis::del("queues:{$queue}");

                return $count;
            }
        } catch (Exception) {
            // fallback
        }

        return 0;
    }

    /**
     * ফেইল্ড জব রিট্রাই করা।
     */
    public function retryFailedJob(string|int $id): bool
    {
        try {
            Artisan::call('queue:retry', ['id' => [(string) $id]]);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * সকল কিউ ওয়ার্কার বা হরাইজন মাস্টার সুপারভাইজার রিস্টার্ট করা।
     */
    public function restartWorkers(): bool
    {
        try {
            Artisan::call('queue:restart');

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * কিউ স্ট্যাটাস ও ফেইল্ড জবের তালিকা সংগ্রহ।
     *
     * @return array<string, mixed>
     */
    public function getQueueOverview(): array
    {
        $failedCount = 0;
        try {
            $failedCount = DB::table('failed_jobs')->count();
        } catch (Exception) {
            // table might not be queried or empty
        }

        $queues = [
            'high' => ['paused' => $this->isQueuePaused('high'), 'size' => 0],
            'default' => ['paused' => $this->isQueuePaused('default'), 'size' => 0],
            'media' => ['paused' => $this->isQueuePaused('media'), 'size' => 0],
            'notifications' => ['paused' => $this->isQueuePaused('notifications'), 'size' => 0],
            'analytics' => ['paused' => $this->isQueuePaused('analytics'), 'size' => 0],
            'search' => ['paused' => $this->isQueuePaused('search'), 'size' => 0],
            'emails' => ['paused' => $this->isQueuePaused('emails'), 'size' => 0],
            'stories' => ['paused' => $this->isQueuePaused('stories'), 'size' => 0],
        ];

        return [
            'horizon_status' => 'active',
            'failed_jobs_count' => $failedCount,
            'queues' => $queues,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
