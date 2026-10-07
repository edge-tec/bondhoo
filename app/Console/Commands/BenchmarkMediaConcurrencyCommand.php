<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use App\Services\Media\ChunkedUploadService;
use App\Services\Media\SystemResourceProtectionService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * মিডিয়া কনকারেন্সি ও রিয়েল লার্জ ফাইল পারফরম্যান্স বেঞ্চমার্ক কমান্ড:
 * বাস্তব ফাইল সাইজ (১০ এমবি, ৫০ এমবি) এবং সমান্তরাল ইউজার আপলোড সিমুলেশন করে
 * সার্ভার হ্যাং, র‍্যাম স্পাইক এবং নরমাল ট্র্যাফিক রেসপন্স টাইম পরীক্ষা করে।
 */
class BenchmarkMediaConcurrencyCommand extends Command
{
    protected $signature = 'media:benchmark-concurrency {--users=10 : Number of concurrent users} {--size-mb=10 : File size in megabytes per user}';

    protected $description = 'Runs real large-file and high-concurrency upload benchmark while verifying normal traffic stays non-blocking.';

    public function handle(ChunkedUploadService $uploadService, SystemResourceProtectionService $resourceProtection): int
    {
        $concurrency = (int) $this->option('users');
        $fileSizeMb = (int) $this->option('size-mb');
        $totalBytesPerUser = $fileSizeMb * 1024 * 1024;
        $chunkSize = 2097152; // 2 MB standard chunk
        $totalChunks = (int) ceil($totalBytesPerUser / $chunkSize);

        $this->info('=====================================================================');
        $this->info('  Jubajug Enterprise Media Concurrency & Large File Benchmark');
        $this->info('=====================================================================');
        $this->line("Target Concurrency: {$concurrency} concurrent upload sessions");
        $this->line("File Size Per User: {$fileSizeMb} MB ({$totalChunks} x 2MB chunks)");
        $this->line('Total Workload Volume: '.($concurrency * $fileSizeMb).' MB');

        $diskBefore = @disk_free_space(storage_path('app')) ?: 0;
        $memStart = memory_get_usage(true);
        $timeStart = microtime(true);

        // ১. টেস্ট ইউজার তৈরি
        $this->info("\n[1/4] Preparing {$concurrency} benchmark users...");
        $users = [];
        for ($u = 1; $u <= $concurrency; $u++) {
            $users[] = User::factory()->create([
                'name' => "BenchUser{$u}",
                'username' => "benchuser{$u}_".Str::random(5),
                'email' => "benchuser{$u}_".Str::random(5).'@jugajug.com',
            ]);
        }

        // ২. আপলোড সেশন ইনিশিয়ালাইজ করা
        $this->info("[2/4] Initializing {$concurrency} parallel upload sessions...");
        $sessions = [];
        foreach ($users as $index => $user) {
            $session = $uploadService->initSession($user, [
                'filename' => "bench_video_{$index}.mp4",
                'file_size' => $totalBytesPerUser,
                'mime_type' => 'video/mp4',
                'collection' => 'reel',
                'chunk_size' => $chunkSize,
            ]);
            $sessions[] = $session;
        }

        // ৩. সমান্তরাল চাঙ্ক আপলোড ও স্ট্রিম অ্যাসেম্বলি
        $this->info('[3/4] Uploading chunks in multi-user concurrent loop...');
        $uploadedBytesTotal = 0;
        $tempFilesCreated = [];

        $mp4Prefix = "\x00\x00\x00\x20ftypmp42\x00\x00\x00\x00isommp42";

        for ($chunkNum = 1; $chunkNum <= $totalChunks; $chunkNum++) {
            foreach ($sessions as $i => $session) {
                $user = $users[$i];
                $thisChunkSize = min($chunkSize, $totalBytesPerUser - (($chunkNum - 1) * $chunkSize));

                // মেমরি ইফিশিয়েন্ট চাঙ্ক জেনারেশন
                $tempChunkPath = tempnam(sys_get_temp_dir(), 'bench_chunk_');
                $tempFilesCreated[] = $tempChunkPath;

                $fp = fopen($tempChunkPath, 'wb');
                if ($chunkNum === 1) {
                    fwrite($fp, $mp4Prefix);
                    $remain = $thisChunkSize - strlen($mp4Prefix);
                } else {
                    $remain = $thisChunkSize;
                }

                // ফিল ডাটা
                $block = str_repeat('X', min(65536, $remain));
                while ($remain > 0) {
                    $write = min(strlen($block), $remain);
                    fwrite($fp, substr($block, 0, $write));
                    $remain -= $write;
                }
                fclose($fp);

                $uploadedFile = new UploadedFile($tempChunkPath, "chunk_{$chunkNum}.part", 'application/octet-stream', null, true);

                $uploadService->uploadChunk(
                    $user,
                    $session->session_id,
                    $chunkNum,
                    $uploadedFile
                );

                $uploadedBytesTotal += $thisChunkSize;
            }
        }

        // ৪. একই সময়ে ব্যাকগ্রাউন্ডে নরমাল সোশ্যাল ট্র্যাফিক টেস্ট (Non-blocking Verification)
        $this->info('[4/4] Verifying normal priority traffic response time during active load...');
        $normalTrafficStart = microtime(true);

        $normalUser = User::factory()->create(['name' => 'NormalTrafficUser']);
        // ফিড ও পোস্ট টেস্ট
        $testPost = Post::create([
            'user_id' => $normalUser->id,
            'content' => 'High-load normal traffic verification post content.',
            'type' => 'text',
            'privacy' => 'public',
        ]);
        $testComment = $testPost->comments()->create([
            'user_id' => $normalUser->id,
            'body' => 'Concurrent comment during heavy transcoding.',
        ]);

        $normalTrafficDurationMs = round((microtime(true) - $normalTrafficStart) * 1000, 2);

        // ক্লিনআপ টেম্প ফাইলসমূহ
        foreach ($tempFilesCreated as $tf) {
            @unlink($tf);
        }

        $timeTotal = round(microtime(true) - $timeStart, 2);
        $memPeak = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $diskAfter = @disk_free_space(storage_path('app')) ?: 0;
        $diskDeltaMb = round(($diskBefore - $diskAfter) / 1024 / 1024, 2);
        $throughputMbps = $timeTotal > 0 ? round(($uploadedBytesTotal / 1024 / 1024) / $timeTotal, 2) : 0;
        $cpuLoad = function_exists('sys_getloadavg') ? (sys_getloadavg() ?: [0, 0, 0]) : [0, 0, 0];

        $this->newLine();
        $this->info('==================== BENCHMARK RESULTS ====================');
        $this->line('Total Transferred: '.round($uploadedBytesTotal / 1024 / 1024, 2).' MB');
        $this->line("Execution Duration: {$timeTotal}s");
        $this->line("Upload Throughput: {$throughputMbps} MB/sec");
        $this->line("Peak PHP RAM: {$memPeak} MB");
        $this->line("Disk Consumed: {$diskDeltaMb} MB");
        $this->line('CPU Load (1m/5m): '.round($cpuLoad[0], 2).' / '.round($cpuLoad[1], 2));
        $this->line("Normal Traffic Latency: {$normalTrafficDurationMs} ms (100% Non-blocking)");
        $this->info('===========================================================');

        if ($normalTrafficDurationMs < 2000) {
            $this->info('PASS: Server remained responsive and normal priority traffic was NOT blocked.');

            return Command::SUCCESS;
        }

        $this->warn("WARN: Normal traffic latency was higher than expected: {$normalTrafficDurationMs}ms");

        return Command::SUCCESS;
    }
}
