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
    protected $signature = 'stories:expire {--cleanup : Also clean up media files for stories exceeding retention policy} {--retention=24 : Retention period in hours}';

    protected $description = 'Expire stories that have passed their 24-hour expiration threshold and clean up media per retention policy';

    public function handle(StoryEnterpriseService $storyService): int
    {
        $expiredCount = $storyService->expireStaleStories();

        $this->info("Successfully expired {$expiredCount} stories.");

        if ($this->option('cleanup')) {
            $retentionHours = (int) $this->option('retention');
            $cleanupResult = $storyService->cleanupExpiredStories($retentionHours);
            $this->info("Cleaned up media for {$cleanupResult['cleaned_stories']} stories ({$cleanupResult['deleted_files']} files removed).");
        }

        return Command::SUCCESS;
    }
}
