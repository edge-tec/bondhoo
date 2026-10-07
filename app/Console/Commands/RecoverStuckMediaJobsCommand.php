<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use App\Models\MediaProcessingJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * স্টাক ব্যাকগ্রাউন্ড জব রিকভারি কমান্ড:
 * কোনো ব্যাকগ্রাউন্ড ওয়ার্কার মাঝপথে ক্র্যাশ বা অফলাইন হলে দীর্ঘক্ষণ ধরে 'processing' অবস্থায় পড়ে থাকা জব সনাক্ত করে পুনরায় শিডিউল বা ডেড-লেটার স্টেটে পাঠায়।
 */
class RecoverStuckMediaJobsCommand extends Command
{
    protected $signature = 'media:recover-stuck-jobs {--timeout-minutes=15 : Minutes after which a job is considered stuck}';

    protected $description = 'Recovers stuck or abandoned media processing jobs caused by worker crashes.';

    public function handle(): int
    {
        $timeoutMinutes = (int) $this->option('timeout-minutes');
        $cutoff = now()->subMinutes($timeoutMinutes);

        $stuckJobs = MediaProcessingJob::where('status', 'processing')
            ->where(function ($query) use ($cutoff) {
                $query->where('started_at', '<', $cutoff)
                    ->orWhereNull('started_at');
            })
            ->get();

        if ($stuckJobs->isEmpty()) {
            $this->info('No stuck media processing jobs found.');

            return Command::SUCCESS;
        }

        $recovered = 0;
        $deadLettered = 0;

        foreach ($stuckJobs as $job) {
            $job->increment('attempts');

            if ($job->attempts >= 3) {
                $job->update([
                    'status' => 'dead_letter',
                    'error_message' => "Worker lease expired. Exceeded maximum retry attempts ({$job->attempts}/3).",
                    'completed_at' => now(),
                ]);

                if ($job->media_id) {
                    Media::where('id', $job->media_id)->update(['processing_status' => 'failed']);
                }

                $deadLettered++;
                Log::error("Media job #{$job->id} marked as dead-letter due to worker crash timeout.");
            } else {
                $job->update([
                    'status' => 'retrying',
                    'error_message' => "Worker timeout detected. Re-dispatching attempt {$job->attempts}.",
                ]);

                if ($job->media_id) {
                    ProcessMediaJob::dispatch($job->media_id);
                }

                $recovered++;
                Log::warning("Media job #{$job->id} re-dispatched after worker crash timeout.");
            }
        }

        $this->info("Recovered and re-queued jobs: {$recovered}");
        $this->info("Moved to dead_letter state: {$deadLettered}");

        return Command::SUCCESS;
    }
}
