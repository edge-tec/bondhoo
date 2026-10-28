<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Console\Command;

/**
 * VerifyBackupCommand — ব্যাকআপ চেকসাম যাচাই এবং বিচ্ছিন্ন (Isolated) পরিবেশে রিস্টোর ড্রিল কমান্ড
 */
class VerifyBackupCommand extends Command
{
    protected $signature = 'admin:backup-verify
                            {--id= : নির্দিষ্ট ব্যাকআপ আইডি যাচাই করুন}
                            {--latest : সর্বশেষ সম্পন্ন ব্যাকআপ যাচাই করুন}
                            {--drill : বিচ্ছিন্ন (Isolated) পরিবেশে পূর্ণাঙ্গ রিস্টোর ড্রিল চালান}
                            {--offsite : Gate C: অফ-সাইট S3-কম্প্যাটিবল রিমোট স্টোরেজ যাচাই করুন}
                            {--disk= : অফ-সাইট স্টোরেজ ডিস্কের নাম (ডিফল্ট: s3)}
                            {--remote-drill : Gate C: রিমোট অফ-সাইট স্টোরেজ ব্যবহার করে বিচ্ছিন্ন DR ড্রিল চালান}
                            {--key-drill : Gate D: এনক্রিপশন কী রোটেশন ও হিস্টোরিক্যাল ব্যাকআপ রিকভারি ড্রিল চালান}
                            {--json : পূর্ণাঙ্গ ফলাফল JSON আকারে আউটপুট দিন}';

    protected $description = 'ব্যাকআপের SHA-256 চেকসাম, এনক্রিপশন, অফ-সাইট স্টোরেজ (Gate C), কী রোটেশন (Gate D) এবং রিস্টোর ড্রিল যাচাই করে';

    public function __construct(
        protected BackupService $backupService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // 1. Gate D: Key Rotation & Historical Decryption Drill
        if ($this->option('key-drill')) {
            $this->info('=== GATE D: ENCRYPTION KEY ROTATION & RECOVERY DRILL ===');
            $drill = $this->backupService->runKeyRotationAndRecoveryDrill();

            if ($this->option('json')) {
                $this->line((string) json_encode($drill, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $status = $drill['passed'] ? 'PASSED' : 'FAILED';
                $this->line("Status: {$status} | Active Key Version: {$drill['active_key_version']}");
                foreach ($drill['steps'] as $stepKey => $step) {
                    $this->line(sprintf('  [%s] %s', $step['passed'] ? 'OK' : 'FAIL', $stepKey));
                }
            }

            return $drill['passed'] ? Command::SUCCESS : Command::FAILURE;
        }

        // 2. Gate C: Off-Site Remote Storage Capability Verification
        if ($this->option('offsite')) {
            $disk = $this->option('disk') ?: null;
            $this->info('=== GATE C: OFF-SITE REMOTE BACKUP STORAGE VERIFICATION ===');
            $res = $this->backupService->verifyOffsiteBackupCapability($disk ? (string) $disk : null);

            if ($this->option('json')) {
                $this->line((string) json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $this->line("Target Disk: {$res['disk']} | Driver: {$res['driver']} | Status: {$res['status']} ({$res['gate_c_status']})");
                if ($res['verified']) {
                    $this->info('Gate C Verified: genuine remote storage, transport encrypted (HTTPS), SHA-256 match, AEAD decryption OK, cleanup OK.');
                } else {
                    $this->error('Gate C Blocked/Failed: '.($res['blocker'] ?? 'Unconfigured'));
                }
            }

            return $res['verified'] ? Command::SUCCESS : Command::FAILURE;
        }

        // 3. Gate C: Isolated Remote Disaster-Recovery Drill
        if ($this->option('remote-drill')) {
            $disk = $this->option('disk') ?: null;
            $this->info('=== GATE C: REMOTE ISOLATED DISASTER-RECOVERY DRILL ===');
            $drill = $this->backupService->runRemoteDisasterRecoveryDrill($disk ? (string) $disk : null);

            if ($this->option('json')) {
                $this->line((string) json_encode($drill, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $this->line("Drill UID: {$drill['drill_uid']} | Disk: {$drill['disk']} | Status: {$drill['status']} ({$drill['gate_c_drill_status']})");
                if ($drill['passed']) {
                    $this->info("Remote DR Drill Passed! Duration: {$drill['duration_ms']}ms | Restored: {$drill['restored_records_count']} rows across {$drill['restored_tables_count']} tables | Negative recovery tests passed.");
                } else {
                    $this->error('Remote DR Drill Failed: '.($drill['error'] ?? ($drill['blocker'] ?? 'Execution error')));
                }
            }

            return $drill['passed'] ? Command::SUCCESS : Command::FAILURE;
        }

        // 4. Default: Local backup checksum & optional isolated restore drill
        $backupId = $this->option('id');

        $backup = $backupId
            ? Backup::find((int) $backupId)
            : Backup::where('status', Backup::STATUS_COMPLETED)->latest('id')->first();

        if (! $backup) {
            $this->error('যাচাই করার জন্য কোনো সম্পন্ন ব্যাকআপ রেকর্ড পাওয়া যায়নি।');

            return Command::FAILURE;
        }

        $this->info("ব্যাকআপ যাচাই শুরু হচ্ছে: #{$backup->id} ({$backup->filename})...");
        $result = $this->backupService->verifyRestore($backup);

        if (! $result['verified']) {
            $this->error("ব্যাকআপ যাচাই ব্যর্থ হয়েছে: {$result['message']}");

            return Command::FAILURE;
        }

        $this->info("ব্যাকআপ চেকসাম ও এনক্রিপশন যাচাই সফল: {$result['message']}");

        if ($this->option('drill')) {
            $this->info('বিচ্ছিন্ন (Isolated) অস্থায়ী ডাটাবেজে রিস্টোর ড্রিল শুরু হচ্ছে...');
            $drill = $this->backupService->runRestoreDrill($backup);

            if ($drill->status !== 'passed') {
                $this->error("রিস্টোর ড্রিল ব্যর্থ হয়েছে ({$drill->drill_uid}): {$drill->error_message}");

                return Command::FAILURE;
            }

            $this->info("রিস্টোর ড্রিল সফল ({$drill->drill_uid})! সময়: {$drill->duration_ms}ms | টেবিল: {$drill->restored_tables_count} | রেকর্ড: {$drill->restored_records_count}");
        }

        return Command::SUCCESS;
    }
}
