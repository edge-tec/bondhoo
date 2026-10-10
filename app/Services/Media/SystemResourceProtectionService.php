<?php

namespace App\Services\Media;

use App\Models\MediaProcessingJob;
use App\Models\MediaSystemSetting;
use App\Models\MediaUploadSession;
use App\Models\User;
use App\Models\UserStorageQuota;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * এন্টারপ্রাইজ সিস্টেম রিসোর্স প্রোটেকশন সার্ভিস (Resource Protection 2.0):
 * - ডায়নামিক ডিস্ক স্পেস রিজার্ভেশন (Original + Chunks + Transcoded Renditions + Safety Margin)
 * - রিয়েলটাইম CPU Load, RAM ও Queue লেন্থ থ্রেশহোল্ড মনিটরিং
 * - রেস-কন্ডিশন ফ্রি অ্যাটমিক ইউজার কোটা রিজার্ভেশন
 * - অ্যাবিউজ ও কনকারেন্ট সেশন গার্ড
 */
class SystemResourceProtectionService
{
    protected const DEFAULT_MAX_MEMORY_MB = 512;

    protected const DEFAULT_MIN_FREE_DISK_MB = 1024; // 1 GB minimum safety margin

    protected const DEFAULT_MAX_CPU_LOAD = 4.0;

    protected const DEFAULT_MAX_USER_CONCURRENT_SESSIONS = 5;

    /**
     * নতুন আপলোড সেশন শুরু করার আগে সিস্টেম ক্যাপাসিটি, ডিস্ক রিজার্ভেশন ও ইউজার কোটা যাচাই।
     *
     * @throws Exception
     */
    public function validateUploadCapacity(User $user, int $expectedFileSizeBytes, string $collection = 'general'): void
    {
        // ১. কনকারেন্ট সেশন অ্যাবিউজ প্রতিরোধ
        $this->ensureConcurrentSessionsLimit($user);

        // ২. স্মার্ট ডায়নামিক ডিস্ক স্পেস প্রোটেকশন
        $this->ensureDynamicDiskCapacity($expectedFileSizeBytes, $collection);

        // ৩. সিস্টেম মেমরি ও CPU সুরক্ষা
        $this->ensureSystemResourcesHealthy();

        // ৪. ইউজারের স্টোরেজ কোটা এবং ফাইল সাইজ লিমিট এনফোর্স (অ্যাটমিক রিজার্ভেশন সহ)
        $this->enforceUserQuota($user, $expectedFileSizeBytes, $collection);
    }

    /**
     * কনকারেন্ট অ্যাক্টিভ সেশন লিমিট চেক (অ্যাবিউজ প্রতিরোধ)
     *
     * @throws Exception
     */
    public function ensureConcurrentSessionsLimit(User $user): void
    {
        // ১৫ মিনিটের বেশি নিষ্ক্রিয়/অসম্পূর্ণ সেশন স্বয়ংক্রিয়ভাবে বাতিল (stale session cleanup)
        MediaUploadSession::where('user_id', $user->id)
            ->whereIn('status', ['initialized', 'uploading'])
            ->where('updated_at', '<', now()->subMinutes(15))
            ->update(['status' => 'cancelled']);

        $maxSessions = (int) MediaSystemSetting::get('max_user_concurrent_sessions', self::DEFAULT_MAX_USER_CONCURRENT_SESSIONS);

        $activeSessionsCount = MediaUploadSession::where('user_id', $user->id)
            ->whereIn('status', ['initialized', 'uploading', 'assembling'])
            ->where('expires_at', '>', now())
            ->count();

        if ($activeSessionsCount >= $maxSessions) {
            throw new Exception("You have {$activeSessionsCount} active upload sessions in progress. Please complete or cancel them before starting a new one.");
        }
    }

    /**
     * স্মার্ট ডায়নামিক ডিস্ক রিজার্ভেশন চেক:
     * Required space = Original + Chunks + Expected Transcoded Renditions (1.5x) + Safety Margin
     *
     * @throws Exception
     */
    public function ensureDynamicDiskCapacity(int $fileSizeBytes, string $collection = 'general'): void
    {
        $storagePath = storage_path('app');
        $freeSpace = @disk_free_space($storagePath);

        if ($freeSpace === false) {
            return; // Cannot determine disk free space in this environment
        }

        $safetyMarginMb = (int) MediaSystemSetting::get('min_free_disk_mb', self::DEFAULT_MIN_FREE_DISK_MB);
        $safetyMarginBytes = $safetyMarginMb * 1024 * 1024;

        $isVideo = in_array($collection, ['reel', 'story_video', 'video']);
        // ভিডিওর ক্ষেত্রে: অরিজিনাল + চাঙ্কস + রেন্ডিশনস (360p/720p/1080p) + পোস্টার
        $multiplier = $isVideo ? 2.5 : 2.0;
        $totalRequiredBytes = (int) ceil(($fileSizeBytes * $multiplier) + $safetyMarginBytes);

        if ($freeSpace < $totalRequiredBytes) {
            Log::warning('SystemResourceProtection: Dynamic disk reservation check failed.', [
                'free_bytes' => $freeSpace,
                'file_size_bytes' => $fileSizeBytes,
                'total_required_bytes' => $totalRequiredBytes,
                'collection' => $collection,
            ]);

            throw new Exception('Server storage capacity is currently constrained. Please try again later or upload a smaller file.');
        }
    }

    /**
     * বেসিক ডিস্ক স্পেস চেক (চাঙ্ক আপলোডের জন্য)
     *
     * @throws Exception
     */
    public function ensureDiskCapacity(int $requiredBytes): void
    {
        $storagePath = storage_path('app');
        $freeSpace = @disk_free_space($storagePath);

        if ($freeSpace !== false) {
            $minFreeBytes = (int) MediaSystemSetting::get('min_free_disk_mb', self::DEFAULT_MIN_FREE_DISK_MB) * 1024 * 1024;
            if (($freeSpace - $requiredBytes) < $minFreeBytes) {
                Log::warning('SystemResourceProtection: Disk space threshold reached during chunk write.', [
                    'free_bytes' => $freeSpace,
                    'required_bytes' => $requiredBytes,
                ]);
                throw new Exception('Server storage is reaching capacity. Cannot accept chunk.');
            }
        }
    }

    /**
     * সিস্টেম মেমরি ও CPU স্বাস্থ্য যাচাই
     *
     * @throws Exception
     */
    public function ensureSystemResourcesHealthy(): void
    {
        $this->ensureMemoryAvailable();
        $this->ensureCpuLoadHealthy();
    }

    /**
     * মেমরি ওভারলোড প্রোটেকশন
     *
     * @throws Exception
     */
    public function ensureMemoryAvailable(): void
    {
        $currentMemoryBytes = memory_get_usage(true);
        $maxAllowedMemoryMb = (int) MediaSystemSetting::get('max_worker_memory_mb', self::DEFAULT_MAX_MEMORY_MB);
        $maxAllowedBytes = $maxAllowedMemoryMb * 1024 * 1024;

        if ($currentMemoryBytes > ($maxAllowedBytes * 0.90)) {
            Log::warning('SystemResourceProtection: High memory usage detected.', [
                'current_mb' => round($currentMemoryBytes / 1024 / 1024, 2),
                'limit_mb' => $maxAllowedMemoryMb,
            ]);
            throw new Exception('Server load is currently high. Please wait a moment before starting new uploads.');
        }
    }

    /**
     * CPU লোড এভারেজ চেক (Mac ও Linux সাপোর্টেড, কোর-সচেতন থ্রেশহোল্ড)
     *
     * @throws Exception
     */
    public function ensureCpuLoadHealthy(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if (is_array($load) && isset($load[0])) {
                $coreCount = (int) (@shell_exec('sysctl -n hw.ncpu 2>/dev/null || nproc 2>/dev/null') ?: 4);
                $defaultMaxLoad = max(12.0, (float) ($coreCount * 3.0));
                $settingMaxLoad = (float) MediaSystemSetting::get('max_cpu_load', $defaultMaxLoad);
                $maxLoad = max($defaultMaxLoad, $settingMaxLoad);

                if ($load[0] > $maxLoad) {
                    Log::warning('SystemResourceProtection: CPU load critical threshold exceeded.', [
                        'load_1min' => $load[0],
                        'cores' => $coreCount,
                        'threshold' => $maxLoad,
                    ]);
                    throw new Exception('Server CPU is currently experiencing heavy load. Please try again shortly.');
                }
            }
        }
    }

    /**
     * ইউজারের স্টোরেজ কোটা এবং সাইজ লিমিট যাচাই (অ্যাটমিক রেস-কন্ডিশন প্রোটেকশন সহ)
     *
     * @throws Exception
     */
    public function enforceUserQuota(User $user, int $fileSizeBytes, string $collection): void
    {
        // ডাটাবেজ ট্রানজেকশনে রো-লেভেল লক দিয়ে এক্সেস নিশ্চিত করা হয় যাতে প্যারালাল রিকোয়েস্ট কোটা বাইপাস না করতে পারে
        DB::transaction(function () use ($user, $fileSizeBytes, $collection) {
            $quota = UserStorageQuota::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $quota) {
                $quota = UserStorageQuota::create([
                    'user_id' => $user->id,
                    'tier' => 'free',
                    'max_storage_bytes' => 1073741824, // 1 GB
                    'used_storage_bytes' => 0,
                    'max_video_size_bytes' => 104857600, // 100 MB
                    'max_image_size_bytes' => 20971520,  // 20 MB
                    'max_daily_uploads' => 50,
                    'today_uploads_count' => 0,
                    'last_upload_date' => now()->toDateString(),
                ]);
            }

            if ($quota->is_unlimited) {
                return;
            }

            // দৈনিক আপলোড কাউন্টার রিসেট
            if ($quota->last_upload_date && ! $quota->last_upload_date->isToday()) {
                $quota->update([
                    'today_uploads_count' => 0,
                    'last_upload_date' => now()->toDateString(),
                ]);
            }

            // দৈনিক আপলোড লিমিট চেক
            if ($quota->today_uploads_count >= $quota->max_daily_uploads) {
                throw new Exception("You have reached your daily upload limit ({$quota->max_daily_uploads} uploads).");
            }

            // সক্রিয় সেশনসমূহের সংরক্ষিত স্পেস হিসাব করা (Race Condition Prevention)
            $activeReservedBytes = (int) MediaUploadSession::where('user_id', $user->id)
                ->whereIn('status', ['initialized', 'uploading', 'assembling'])
                ->where('expires_at', '>', now())
                ->sum('file_size');

            $effectiveUsedBytes = $quota->used_storage_bytes + $activeReservedBytes;

            if (($effectiveUsedBytes + $fileSizeBytes) > $quota->max_storage_bytes) {
                $maxMb = round($quota->max_storage_bytes / 1024 / 1024, 1);
                $usedMb = round($effectiveUsedBytes / 1024 / 1024, 1);
                throw new Exception("Storage quota exceeded. Used (including pending): {$usedMb}MB / {$maxMb}MB. Please delete some media or upgrade your plan.");
            }

            // ফাইল টাইপ অনুযায়ী ম্যাক্সিমাম সাইজ লিমিট
            $isVideo = in_array($collection, ['reel', 'story_video', 'video']);
            if ($isVideo && $fileSizeBytes > $quota->max_video_size_bytes) {
                $limitMb = round($quota->max_video_size_bytes / 1024 / 1024);
                throw new Exception("Video exceeds maximum allowed file size of {$limitMb}MB.");
            }

            $isImage = in_array($collection, ['story_photo', 'photo', 'avatar', 'cover', 'image']);
            if ($isImage && $fileSizeBytes > $quota->max_image_size_bytes) {
                $limitMb = round($quota->max_image_size_bytes / 1024 / 1024);
                throw new Exception("Image exceeds maximum allowed file size of {$limitMb}MB.");
            }
        });
    }

    /**
     * ব্যাকগ্রাউন্ড প্রসেসিং কাজের কনকারেন্সি লিমিট চেক
     */
    public function canProcessQueueJob(): bool
    {
        $maxConcurrentJobs = (int) MediaSystemSetting::get('max_concurrent_workers', 3);
        $activeJobsCount = MediaProcessingJob::where('status', 'processing')->count();

        return $activeJobsCount < $maxConcurrentJobs;
    }

    /**
     * সিস্টেম রিয়েলটাইম রিসোর্স মেট্রিক্স
     *
     * @return array<string, mixed>
     */
    public function getSystemHealthMetrics(): array
    {
        $storagePath = storage_path('app');
        $freeDisk = @disk_free_space($storagePath) ?: 0;
        $totalDisk = @disk_total_space($storagePath) ?: 0;

        $load = function_exists('sys_getloadavg') ? (sys_getloadavg() ?: [0, 0, 0]) : [0, 0, 0];

        $pendingJobs = MediaProcessingJob::whereIn('status', ['queued', 'pending'])->count();
        $activeJobs = MediaProcessingJob::where('status', 'processing')->count();
        $failedJobs = MediaProcessingJob::where('status', 'failed')->count();

        return [
            'memory_used_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'cpu_load_1min' => (float) ($load[0] ?? 0),
            'cpu_load_5min' => (float) ($load[1] ?? 0),
            'disk_free_mb' => round($freeDisk / 1024 / 1024, 1),
            'disk_total_mb' => round($totalDisk / 1024 / 1024, 1),
            'disk_used_percent' => $totalDisk > 0 ? round((($totalDisk - $freeDisk) / $totalDisk) * 100, 1) : 0,
            'queue_pending_jobs' => $pendingJobs,
            'queue_active_workers' => $activeJobs,
            'queue_failed_jobs' => $failedJobs,
        ];
    }
}
