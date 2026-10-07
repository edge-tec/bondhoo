<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\UserStorageQuota;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * স্টোরেজ একাউন্টিং ও রিকনসিলিয়েশন কমান্ড:
 * ১. ডাটাবেজ ব্যবহার বনাম ফিজিক্যাল স্টোরেজের অমিল শনাক্ত ও সমাধান
 * ২. অরফান ফাইল (Orphan files) ও মিসিং ফাইল রিপোর্ট
 */
class ReconcileStorageCommand extends Command
{
    protected $signature = 'media:reconcile-storage {--repair : Automatically update user quotas to match actual database records}';

    protected $description = 'Reconciles user storage quota usage against actual media records and identifies orphan files.';

    public function handle(): int
    {
        $this->info('Starting media storage accounting and reconciliation...');
        $shouldRepair = (bool) $this->option('repair');

        // ১. প্রতিটি ইউজারের ব্যবহৃত স্টোরেজ যাচাই
        $quotas = UserStorageQuota::all();
        $mismatches = 0;
        $fixed = 0;

        foreach ($quotas as $quota) {
            $actualDbBytes = (int) Media::where('user_id', $quota->user_id)->sum('size');

            if ($actualDbBytes !== (int) $quota->used_storage_bytes) {
                $mismatches++;
                $this->warn("User #{$quota->user_id} mismatch: Recorded = {$quota->used_storage_bytes} bytes, Actual DB = {$actualDbBytes} bytes.");

                if ($shouldRepair) {
                    $quota->update(['used_storage_bytes' => $actualDbBytes]);
                    $fixed++;
                }
            }
        }

        // ২. ডাটাবেজে রেকর্ড আছে কিন্তু ডিস্কে ফাইল মিসিং
        $missingPhysicalFiles = 0;
        $mediaRecords = Media::all();
        foreach ($mediaRecords as $media) {
            $fullPath = storage_path("app/public/{$media->original_path}");
            if (! file_exists($fullPath)) {
                $missingPhysicalFiles++;
                $this->error("Missing physical file: Media #{$media->id} -> {$media->original_path}");
            }
        }

        // ৩. স্টোরেজে ফাইল আছে কিন্তু ডাটাবেজে রেকর্ড নেই (Orphan Files, 24 ঘণ্টার বেশি পুরনো)
        $uploadsDir = storage_path('app/public/uploads');
        $orphanFilesCount = 0;
        if (File::isDirectory($uploadsDir)) {
            $allFiles = File::allFiles($uploadsDir);
            $graceTime = now()->subHours(24)->getTimestamp();

            foreach ($allFiles as $file) {
                if ($file->getMTime() > $graceTime) {
                    continue; // Skip files uploaded within the last 24h grace period
                }

                $relative = 'uploads/'.str_replace('\\', '/', $file->getRelativePathname());
                $exists = Media::where('original_path', $relative)
                    ->orWhere('thumbnail_path', $relative)
                    ->orWhere('medium_path', $relative)
                    ->orWhere('large_path', $relative)
                    ->exists();

                if (! $exists) {
                    $orphanFilesCount++;
                }
            }
        }

        $this->newLine();
        $this->info('Reconciliation Summary:');
        $this->line("- Quota Mismatches: {$mismatches}");
        if ($shouldRepair) {
            $this->line("- Repaired Quotas: {$fixed}");
        }
        $this->line("- Missing Physical Files: {$missingPhysicalFiles}");
        $this->line("- Detected Orphan Files (>24h grace): {$orphanFilesCount}");

        return Command::SUCCESS;
    }
}
