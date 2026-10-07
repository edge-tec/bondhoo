<?php

namespace App\Console\Commands;

use App\Services\EnterpriseStorageService;
use Illuminate\Console\Command;

/**
 * StorageLifecycleCommand — অবজেক্ট স্টোরেজ লাইফসাইকেল ও ২৪-ঘণ্টার স্টোরিজ ক্লিনার
 */
class StorageLifecycleCommand extends Command
{
    protected $signature = 'jugajug:storage-lifecycle';

    protected $description = 'মেয়াদোত্তীর্ণ স্টোরিজ ও অস্থায়ী আপলোড ফাইলসমূহ স্বয়ংক্রিয়ভাবে মুছে ফেলে';

    public function __construct(
        protected EnterpriseStorageService $storageService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('স্টোরেজ লাইফসাইকেল পলিসি কার্যকর হচ্ছে...');

        $result = $this->storageService->cleanupExpiredStories();

        $this->info("মুছে ফেলা স্টোরি: {$result['deleted_stories']}");
        $this->info("মুছে ফেলা স্টোরেজ মিডিয়া: {$result['deleted_files']}");
        $this->info('লাইফসাইকেল ক্লিনআপ সফলভাবে সম্পন্ন হয়েছে!');

        return Command::SUCCESS;
    }
}
