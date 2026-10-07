<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

/**
 * BackupCommand — ব্যাকআপ তৈরি ও ডিজাস্টার রিকভারি আর্ট্টিজান কমান্ড
 */
class BackupCommand extends Command
{
    protected $signature = 'jugajug:backup 
                            {--type=full : ব্যাকআপের ধরন (full, db, media, redis)}
                            {--disk=local : স্টোরেজ ডিস্ক (local, s3, r2)}';

    protected $description = 'ডাটাবেজ ও মিডিয়ার এনক্রিপ্টেড ব্যাকআপ তৈরি করে';

    public function __construct(
        protected BackupService $backupService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $type = (string) $this->option('type');
        $disk = (string) $this->option('disk');

        $this->info("{$type} ব্যাকআপ তৈরি শুরু হচ্ছে (Disk: {$disk})...");

        $backup = $this->backupService->createBackup($type, $disk);

        if ($backup->status === 'completed') {
            $this->info("ব্যাকআপ সফল! ফাইল: {$backup->filename}");
            $this->line("সাইজ: {$backup->size_bytes} bytes");
            $this->line("SHA-256 Checksum: {$backup->checksum}");

            return Command::SUCCESS;
        }

        $this->error("ব্যাকআপ ব্যর্থ হয়েছে: {$backup->error_message}");

        return Command::FAILURE;
    }
}
