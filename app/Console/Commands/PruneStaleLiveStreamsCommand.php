<?php

namespace App\Console\Commands;

use App\Services\Streaming\LiveStreamingService;
use Illuminate\Console\Command;

class PruneStaleLiveStreamsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'live:prune-stale {--silence=75 : Seconds of silence before a live stream is marked stale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely terminate crashed or abandoned live stream broadcasts and clean up stale presence';

    /**
     * Execute the console command.
     */
    public function handle(LiveStreamingService $streamingService): int
    {
        $silence = (int) $this->option('silence');
        $pruned = $streamingService->pruneStaleStreams($silence);

        $this->info("Successfully pruned {$pruned} stale live stream(s).");

        return Command::SUCCESS;
    }
}
