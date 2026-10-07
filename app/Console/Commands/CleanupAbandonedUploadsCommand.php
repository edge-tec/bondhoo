<?php

namespace App\Console\Commands;

use App\Models\MediaUploadSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * পরিত্যক্ত আপলোড ক্লিনআপ কমান্ড:
 * ইন্টারনেট ডিসকানেক্ট বা ব্রাউজার ক্লোজের পর অসম্পূর্ণ থাকা ২৪ ঘণ্টার পুরনো চাঙ্ক ফাইলগুলো
 * মেমরি ও ডিস্ক স্পেস সংরক্ষণের জন্য ক্লিনআপ করে।
 */
class CleanupAbandonedUploadsCommand extends Command
{
    protected $signature = 'media:cleanup-abandoned';

    protected $description = 'Clean up abandoned chunked upload sessions and temporary chunk files';

    public function handle(): int
    {
        $expiredSessions = MediaUploadSession::where(function ($query) {
            $query->where('expires_at', '<=', now())
                ->orWhereIn('status', ['cancelled', 'failed']);
        })->where('status', '!=', 'completed')->get();

        $cleanedCount = 0;

        foreach ($expiredSessions as $session) {
            if ($session->temp_dir && File::isDirectory($session->temp_dir)) {
                File::deleteDirectory($session->temp_dir);
            }
            $session->delete();
            $cleanedCount++;
        }

        $this->info("Cleaned up {$cleanedCount} abandoned upload sessions.");

        return Command::SUCCESS;
    }
}
