<?php

namespace App\Console\Commands;

use App\Services\ReelEnterpriseService;
use Illuminate\Console\Command;

/**
 * রিলস এক্সপায়ারেশন কমান্ড:
 * ২৪ ঘণ্টা অতিক্রান্ত হওয়া রিলস চিহ্নিত করে স্বয়ংক্রিয়ভাবে এক্সপায়ার করে এবং রিয়েল-টাইমে ব্রডকাস্ট করে।
 */
class ExpireReelsCommand extends Command
{
    protected $signature = 'reels:expire';

    protected $description = 'Expire reels that have passed their 24-hour expiration threshold and broadcast realtime event';

    public function handle(ReelEnterpriseService $reelService): int
    {
        $expiredCount = $reelService->expireStaleReels();

        $this->info("Successfully expired {$expiredCount} reels.");

        return Command::SUCCESS;
    }
}
