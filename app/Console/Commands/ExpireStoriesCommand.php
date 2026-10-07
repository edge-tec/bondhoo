<?php

namespace App\Console\Commands;

use App\Services\StoryEnterpriseService;
use Illuminate\Console\Command;

/**
 * স্টোরি এক্সপায়ারেশন কমান্ড:
 * ২৪ ঘণ্টা অতিক্রান্ত হওয়া স্টোরিগুলোকে চিহ্নিত করে স্বয়ংক্রিয়ভাবে এক্সপায়ার করে এবং রিয়েল-টাইমে ব্রডকাস্ট করে।
 */
class ExpireStoriesCommand extends Command
{
    protected $signature = 'stories:expire';

    protected $description = 'Expire stories that have passed their 24-hour expiration threshold';

    public function handle(StoryEnterpriseService $storyService): int
    {
        $expiredCount = $storyService->expireStaleStories();

        $this->info("Successfully expired {$expiredCount} stories.");

        return Command::SUCCESS;
    }
}
