<?php

namespace App\Console\Commands;

use App\Services\EnterpriseSearchService;
use Illuminate\Console\Command;

/**
 * SearchReindexCommand — সার্চ ইন্ডেক্স ব্যাকগ্রাউন্ড রি-ইন্ডেক্সিং কমান্ড
 */
class SearchReindexCommand extends Command
{
    protected $signature = 'jugajug:search-reindex';

    protected $description = 'সমস্ত ইউজার, পোস্ট, পেজ ও গ্রুপ সার্চ ক্লাস্টারে পুনরায় ইন্ডেক্স করে';

    public function __construct(
        protected EnterpriseSearchService $searchService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Meilisearch সার্চ ইন্ডেক্সিং শুরু হয়েছে...');

        $stats = $this->searchService->reindexAll();

        $this->table(
            ['মডেল', 'ইন্ডেক্স সংখ্যা'],
            [
                ['Users', $stats['indexed_users']],
                ['Posts', $stats['indexed_posts']],
                ['Pages', $stats['indexed_pages']],
                ['Groups', $stats['indexed_groups']],
            ]
        );

        $this->info('সার্চ রি-ইন্ডেক্সিং সফলভাবে সম্পন্ন হয়েছে!');

        return Command::SUCCESS;
    }
}
