<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\Contracts\CacheServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * BenchmarkCommand — জুগাজুগ প্ল্যাটফর্ম পারফরম্যান্স ও স্কেলিং বেঞ্চমার্কিং টুল
 *
 * এই কমান্ডটি আর্কিটেকচারের কোর উপাদানসমূহ (Redis, Database, Feed Query)
 * এর থ্রুপুট (Ops/sec) ও রেসপন্স টাইম (Latency) স্বয়ংক্রিয়ভাবে পরিমাপ করে।
 */
class BenchmarkCommand extends Command
{
    /**
     * কনসোল কমান্ড সিগনেচার ও প্যারামিটারসমূহ।
     *
     * @var string
     */
    protected $signature = 'jugajug:benchmark 
                            {--iterations=100 : টেস্টের পুনরাবৃত্তি সংখ্যা (ডিফল্ট ১০০)}
                            {--type=all : বেঞ্চমার্কের ধরন (all, redis, db, feed)}';

    /**
     * কমান্ডের বিবরণ।
     *
     * @var string
     */
    protected $description = 'জুগাজুগ সোশ্যাল নেটওয়ার্কের Redis, Database এবং Feed পারফরম্যান্স বেঞ্চমার্ক টেস্ট করে';

    public function __construct(
        protected CacheServiceInterface $cacheService
    ) {
        parent::__construct();
    }

    /**
     * কমান্ড এক্সিকিউট করা।
     */
    public function handle(): int
    {
        $iterations = (int) $this->option('iterations');
        $type = strtolower((string) $this->option('type'));

        $this->info('======================================================');
        $this->info('   JUGAJUG HIGH-SCALE ARCHITECTURE BENCHMARK TOOL   ');
        $this->info('======================================================');
        $this->line("মোট টেস্ট সাইকেল (Iterations): <comment>{$iterations}</comment>");
        $this->line("টেস্ট ক্যাটাগরি: <comment>{$type}</comment>");
        $this->newLine();

        $results = [];

        // ১. রেডিস ক্যাশ বেঞ্চমার্ক
        if (in_array($type, ['all', 'redis'])) {
            $this->comment('১. Redis Read/Write থ্রুপুট টেস্ট চলছে...');
            $results = array_merge($results, $this->benchmarkRedis($iterations));
        }

        // ২. ডাটাবেজ কোয়েরি বেঞ্চমার্ক
        if (in_array($type, ['all', 'db'])) {
            $this->comment('২. Database Query পারফরম্যান্স টেস্ট চলছে...');
            $results = array_merge($results, $this->benchmarkDatabase($iterations));
        }

        // ৩. সোশ্যাল ফিড জেনারেশন বেঞ্চমার্ক
        if (in_array($type, ['all', 'feed'])) {
            $this->comment('৩. Social Feed রেন্ডারিং স্পিড টেস্ট চলছে...');
            $results = array_merge($results, $this->benchmarkFeed($iterations));
        }

        // ফলাফল টেবিল আকারে প্রদর্শন
        $this->newLine();
        $this->info('বেঞ্চমার্ক ফলাফল রিপোর্ট:');
        $this->table(
            ['কম্পোনেন্ট', 'অপারেশন', 'মোট সময় (ms)', 'থ্রুপুট (Ops/Sec)', 'গড় লেটেন্সি (ms)'],
            $results
        );

        $this->info('বেঞ্চমার্ক সফলভাবে সম্পন্ন হয়েছে!');

        return Command::SUCCESS;
    }

    /**
     * Redis রিড ও রাইট পারফরম্যান্স পরিমাপ করা।
     *
     * @return array<int, array<string, string>>
     */
    protected function benchmarkRedis(int $iterations): array
    {
        // Write Test
        $startWrite = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->cacheService->set("bench:key:{$i}", "benchmark_payload_value_{$i}", 60);
        }
        $writeDuration = microtime(true) - $startWrite;
        $writeDurationMs = round($writeDuration * 1000, 2);
        $writeThroughput = $writeDuration > 0 ? round($iterations / $writeDuration, 1) : $iterations;
        $writeAvgLatency = round($writeDurationMs / $iterations, 3);

        // Read Test
        $startRead = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->cacheService->get("bench:key:{$i}");
        }
        $readDuration = microtime(true) - $startRead;
        $readDurationMs = round($readDuration * 1000, 2);
        $readThroughput = $readDuration > 0 ? round($iterations / $readDuration, 1) : $iterations;
        $readAvgLatency = round($readDurationMs / $iterations, 3);

        // Cleanup
        for ($i = 0; $i < $iterations; $i++) {
            $this->cacheService->forget("bench:key:{$i}");
        }

        return [
            [
                'Redis Cache',
                "Write ({$iterations} keys)",
                "{$writeDurationMs} ms",
                "{$writeThroughput} ops/s",
                "{$writeAvgLatency} ms",
            ],
            [
                'Redis Cache',
                "Read ({$iterations} keys)",
                "{$readDurationMs} ms",
                "{$readThroughput} ops/s",
                "{$readAvgLatency} ms",
            ],
        ];
    }

    /**
     * ডাটাবেজ কোয়েরি এক্সিকিউশন রেট ও লেটেন্সি পরিমাপ করা।
     *
     * @return array<int, array<string, string>>
     */
    protected function benchmarkDatabase(int $iterations): array
    {
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            DB::select('SELECT 1 as ping');
        }
        $duration = microtime(true) - $start;
        $durationMs = round($duration * 1000, 2);
        $throughput = $duration > 0 ? round($iterations / $duration, 1) : $iterations;
        $avgLatency = round($durationMs / $iterations, 3);

        return [
            [
                'Database (SQL)',
                "Ping Query ({$iterations}x)",
                "{$durationMs} ms",
                "{$throughput} queries/s",
                "{$avgLatency} ms",
            ],
        ];
    }

    /**
     * সোশ্যাল নেটওয়ার্ক ফিড কুয়েরি স্পিড পরিমাপ করা।
     *
     * @return array<int, array<string, string>>
     */
    protected function benchmarkFeed(int $iterations): array
    {
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Post::query()
                ->with(['user', 'media'])
                ->where('visibility', 'public')
                ->latest()
                ->limit(10)
                ->get();
        }
        $duration = microtime(true) - $start;
        $durationMs = round($duration * 1000, 2);
        $throughput = $duration > 0 ? round($iterations / $duration, 1) : $iterations;
        $avgLatency = round($durationMs / $iterations, 3);

        return [
            [
                'Feed Generation',
                "Feed Query ({$iterations}x)",
                "{$durationMs} ms",
                "{$throughput} feeds/s",
                "{$avgLatency} ms",
            ],
        ];
    }
}
