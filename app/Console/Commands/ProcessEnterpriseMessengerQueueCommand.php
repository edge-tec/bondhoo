<?php

namespace App\Console\Commands;

use App\Services\Messenger\EnterpriseMessengerService;
use Illuminate\Console\Command;

class ProcessEnterpriseMessengerQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:process-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due scheduled messages and purge expired disappearing messages';

    /**
     * Execute the console command.
     */
    public function handle(EnterpriseMessengerService $service): int
    {
        $this->info('Processing Ultra Enterprise Messenger queue...');

        $dispatched = $service->dispatchDueScheduledMessages();
        $this->info("Dispatched {$dispatched} due scheduled messages.");

        $purged = $service->purgeExpiredMessages();
        $this->info("Purged {$purged} expired disappearing messages.");

        $this->info('Done.');

        return self::SUCCESS;
    }
}
