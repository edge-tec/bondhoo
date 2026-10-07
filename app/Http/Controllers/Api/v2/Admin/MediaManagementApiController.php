<?php

namespace App\Http\Controllers\Api\v2\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use App\Models\MediaProcessingJob;
use App\Models\MediaSystemSetting;
use App\Models\MediaUploadSession;
use App\Models\MusicTrack;
use App\Models\Reel;
use App\Models\Story;
use App\Models\User;
use App\Models\UserStorageQuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * মিডিয়া সিস্টেম অ্যাডমিন কন্ট্রোলার:
 * মিডিয়া আপলোড লিমিট, সার্ভার রিসোর্স স্ট্যাটিস্টিক্স, স্টোরেজ কোটা, ফেইল্ড জব রিকভারি ও কনফিগারেশন পরিচালনা।
 */
class MediaManagementApiController extends Controller
{
    /**
     * মিডিয়া ও সার্ভার রিসোর্স ম্যাট্রিক্স
     */
    public function metrics(): JsonResponse
    {
        $totalMedia = Media::count();
        $totalReels = Reel::count();
        $totalStories = Story::count();
        $totalMusicTracks = MusicTrack::count();
        $totalStorageBytes = (int) Media::sum('size');
        $activeSessions = MediaUploadSession::whereIn('status', ['initialized', 'uploading', 'assembling'])->count();
        $queuedJobs = MediaProcessingJob::where('status', 'queued')->count();
        $processingJobs = MediaProcessingJob::where('status', 'processing')->count();
        $retryingJobs = MediaProcessingJob::where('status', 'retrying')->count();
        $failedJobs = MediaProcessingJob::where('status', 'failed')->count();
        $deadLetterJobs = MediaProcessingJob::where('status', 'dead_letter')->count();

        $storagePath = storage_path('app');
        $freeDiskBytes = @disk_free_space($storagePath) ?: 0;
        $totalDiskBytes = @disk_total_space($storagePath) ?: 0;

        $cpuLoad = function_exists('sys_getloadavg') ? (sys_getloadavg() ?: [0, 0, 0]) : [0, 0, 0];

        // টপ স্টোরেজ ব্যবহারকারী
        $topUsers = UserStorageQuota::with('user:id,name,username,email')
            ->orderByDesc('used_storage_bytes')
            ->limit(5)
            ->get()
            ->map(fn ($q) => [
                'user_id' => $q->user_id,
                'name' => $q->user?->name ?? 'Unknown',
                'username' => $q->user?->username ?? 'unknown',
                'used_storage_mb' => round($q->used_storage_bytes / 1024 / 1024, 2),
                'max_storage_mb' => round($q->max_storage_bytes / 1024 / 1024, 2),
                'tier' => $q->tier,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'total_media_count' => $totalMedia,
                'total_reels_count' => $totalReels,
                'total_stories_count' => $totalStories,
                'total_music_tracks' => $totalMusicTracks,
                'total_storage_mb' => round($totalStorageBytes / 1024 / 1024, 2),
                'active_upload_sessions' => $activeSessions,
                'processing_jobs' => [
                    'queued' => $queuedJobs,
                    'processing' => $processingJobs,
                    'retrying' => $retryingJobs,
                    'failed' => $failedJobs,
                    'dead_letter' => $deadLetterJobs,
                ],
                'server_resources' => [
                    'disk_free_gb' => round($freeDiskBytes / 1024 / 1024 / 1024, 2),
                    'disk_total_gb' => round($totalDiskBytes / 1024 / 1024 / 1024, 2),
                    'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'cpu_load_1min' => (float) ($cpuLoad[0] ?? 0),
                    'cpu_load_5min' => (float) ($cpuLoad[1] ?? 0),
                ],
                'top_storage_users' => $topUsers,
                'settings' => [
                    'max_video_size_mb' => MediaSystemSetting::get('max_video_size_mb', 100),
                    'max_image_size_mb' => MediaSystemSetting::get('max_image_size_mb', 20),
                    'max_concurrent_workers' => MediaSystemSetting::get('max_concurrent_workers', 3),
                    'min_free_disk_mb' => MediaSystemSetting::get('min_free_disk_mb', 1024),
                    'storage_driver' => config('filesystems.default', 'public'),
                ],
            ],
        ]);
    }

    /**
     * অ্যাডমিন সেটিংস আপডেট করা
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'max_video_size_mb' => ['nullable', 'integer', 'min:1', 'max:2048'],
            'max_image_size_mb' => ['nullable', 'integer', 'min:1', 'max:100'],
            'max_concurrent_workers' => ['nullable', 'integer', 'min:1', 'max:16'],
            'min_free_disk_mb' => ['nullable', 'integer', 'min:100'],
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                MediaSystemSetting::set($key, $value, 'integer', 'limits');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Media system settings updated successfully.',
        ]);
    }

    /**
     * নির্দিষ্ট ইউজারের স্টোরেজ কোটা আপডেট করা
     */
    public function updateUserQuota(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'tier' => ['nullable', 'string', 'in:free,basic,pro,enterprise'],
            'max_storage_mb' => ['nullable', 'integer', 'min:100'],
            'max_video_size_mb' => ['nullable', 'integer', 'min:10'],
            'is_unlimited' => ['nullable', 'boolean'],
            'max_daily_uploads' => ['nullable', 'integer', 'min:1'],
        ]);

        $quota = UserStorageQuota::firstOrCreate(['user_id' => $user->id]);

        $updates = [];
        if (isset($validated['tier'])) {
            $updates['tier'] = $validated['tier'];
        }
        if (isset($validated['max_storage_mb'])) {
            $updates['max_storage_bytes'] = $validated['max_storage_mb'] * 1024 * 1024;
        }
        if (isset($validated['max_video_size_mb'])) {
            $updates['max_video_size_bytes'] = $validated['max_video_size_mb'] * 1024 * 1024;
        }
        if (isset($validated['is_unlimited'])) {
            $updates['is_unlimited'] = $validated['is_unlimited'];
        }
        if (isset($validated['max_daily_uploads'])) {
            $updates['max_daily_uploads'] = $validated['max_daily_uploads'];
        }

        $quota->update($updates);

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} storage quota updated.",
            'data' => $quota,
        ]);
    }

    /**
     * ব্যর্থ ও ডেড-লেটার জব তালিকা
     */
    public function failedJobs(): JsonResponse
    {
        $jobs = MediaProcessingJob::whereIn('status', ['failed', 'dead_letter'])
            ->with(['user:id,name,username', 'media:id,original_path,mime_type,size'])
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * ব্যর্থ মিডিয়া প্রসেসিং জব রিট্রাই করা
     */
    public function retryJob(int $id): JsonResponse
    {
        $job = MediaProcessingJob::find($id);
        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found.',
            ], 404);
        }

        if (! $job->media_id) {
            return response()->json([
                'success' => false,
                'message' => 'Job has no associated media ID.',
            ], 422);
        }

        $job->update([
            'status' => 'queued',
            'progress' => 0,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
        ]);

        ProcessMediaJob::dispatch($job->media_id);

        return response()->json([
            'success' => true,
            'message' => "Media processing job {$id} re-dispatched.",
        ]);
    }
}
