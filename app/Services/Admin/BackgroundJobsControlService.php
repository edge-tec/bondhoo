<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BackgroundJobsControlService
{
    /**
     * Get Overview of Queue and Background Jobs Health.
     */
    public function getQueueHealth(): array
    {
        $pendingJobsCount = 0;
        $failedJobsCount = 0;

        try {
            if (Schema::hasTable('jobs')) {
                $pendingJobsCount = DB::table('jobs')->count();
            }
        } catch (\Throwable) {
        }

        try {
            if (Schema::hasTable('failed_jobs')) {
                $failedJobsCount = DB::table('failed_jobs')->count();
            }
        } catch (\Throwable) {
        }

        // Check recent queue backlog by queue name
        $queues = [];
        try {
            if (Schema::hasTable('jobs')) {
                $queues = DB::table('jobs')
                    ->select('queue', DB::raw('count(*) as count'))
                    ->groupBy('queue')
                    ->get()
                    ->pluck('count', 'queue')
                    ->toArray();
            }
        } catch (\Throwable) {
        }

        return [
            'queue_driver' => config('queue.default'),
            'pending_jobs' => $pendingJobsCount,
            'failed_jobs' => $failedJobsCount,
            'queues' => $queues,
            'status' => $failedJobsCount > 50 ? 'degraded' : 'healthy',
        ];
    }

    /**
     * Inspect Failed Jobs with Sensitive Information Safely Redacted.
     */
    public function getFailedJobs(int $limit = 20): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')
            ->latest('failed_at')
            ->take($limit)
            ->get()
            ->map(function ($job) {
                // Parse and sanitize payload
                $payloadSnippet = Str::limit($job->payload, 300);
                $exceptionSnippet = Str::limit($job->exception, 400);

                // Redact common secret keys
                $payloadSnippet = preg_replace('/"(password|secret|token|key)":\s*"[^"]+"/i', '"$1": "[REDACTED]"', $payloadSnippet);

                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid ?? null,
                    'connection' => $job->connection,
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                    'exception_class' => explode("\n", $job->exception)[0] ?? 'UnknownException',
                    'exception' => $exceptionSnippet,
                    'payload' => $payloadSnippet,
                ];
            })
            ->toArray();
    }

    /**
     * Safely Retry a Failed Job by ID.
     */
    public function retryJob(string|int $jobId, User $admin): array
    {
        $exitCode = Artisan::call('queue:retry', ['id' => [$jobId]]);
        $output = trim(Artisan::output());

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'admin_queue_retry_job',
            'entity_type' => 'failed_jobs',
            'entity_id' => is_numeric($jobId) ? (int) $jobId : 0,
            'new_values' => ['job_id' => $jobId, 'exit_code' => $exitCode, 'output' => $output],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'success' => $exitCode === 0,
            'message' => $output ?: "জব #{$jobId} পুনরায় কিউতে পাঠানো হয়েছে।",
        ];
    }

    /**
     * Safely Retry All Failed Jobs.
     */
    public function retryAllFailedJobs(User $admin): array
    {
        $exitCode = Artisan::call('queue:retry', ['id' => ['all']]);
        $output = trim(Artisan::output());

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'admin_queue_retry_all_jobs',
            'entity_type' => 'failed_jobs',
            'entity_id' => 0,
            'new_values' => ['exit_code' => $exitCode, 'output' => $output],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'success' => $exitCode === 0,
            'message' => $output ?: 'সমস্ত ব্যর্থ জব পুনরায় কিউতে পাঠানো হয়েছে।',
        ];
    }

    /**
     * Delete a Failed Job from failed_jobs table.
     */
    public function deleteFailedJob(string|int $jobId, User $admin): bool
    {
        $deleted = DB::table('failed_jobs')->where('id', $jobId)->delete();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'admin_queue_delete_failed_job',
            'entity_type' => 'failed_jobs',
            'entity_id' => is_numeric($jobId) ? (int) $jobId : 0,
            'new_values' => ['job_id' => $jobId],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $deleted > 0;
    }

    /**
     * Get Registered Scheduled Tasks.
     */
    public function getScheduledTasks(): array
    {
        // Core automated schedules configured for the platform
        return [
            [
                'command' => 'admin:aggregate-analytics',
                'expression' => '0 0 * * *',
                'description' => 'দৈনিক এনালিটিক্স প্রিক্যালকুলেশন ও এগ্রিগেশন',
                'frequency' => 'প্রতিদিন মধ্যরাতে (Daily at 00:00)',
                'next_run' => Carbon::tomorrow()->startOfDay()->toIso8601String(),
                'status' => 'active',
            ],
            [
                'command' => 'admin:enforce-data-retention',
                'expression' => '0 2 * * *',
                'description' => 'ডাটা রিটেনশন নীতি প্রয়োগ ও মেয়াদোত্তীর্ণ লগ অপসারণ',
                'frequency' => 'প্রতিদিন রাত ০২:০০ টায় (Daily at 02:00)',
                'next_run' => Carbon::tomorrow()->setHour(2)->startOfHour()->toIso8601String(),
                'status' => 'active',
            ],
            [
                'command' => 'queue:prune-failed',
                'expression' => '0 3 * * *',
                'description' => 'ব্যর্থ জব টেবিল হতে ১৪ দিনের পুরনো লগ ছাঁটাই',
                'frequency' => 'প্রতিদিন রাত ০৩:০০ টায়',
                'next_run' => Carbon::tomorrow()->setHour(3)->startOfHour()->toIso8601String(),
                'status' => 'active',
            ],
            [
                'command' => 'sanctum:prune-expired',
                'expression' => '0 4 * * 0',
                'description' => 'মেয়াদোত্তীর্ণ ব্যক্তিগত অ্যাক্সেস টোকেন পরিচ্ছন্নকরণ',
                'frequency' => 'সাপ্তাহিক রবিবার রাত ০৪:০০ টায়',
                'next_run' => Carbon::now()->next(Carbon::SUNDAY)->setHour(4)->startOfHour()->toIso8601String(),
                'status' => 'active',
            ],
        ];
    }
}
