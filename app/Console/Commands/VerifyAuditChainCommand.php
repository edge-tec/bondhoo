<?php

namespace App\Console\Commands;

use App\Services\Audit\TamperEvidentAuditService;
use Illuminate\Console\Command;
use Throwable;

/**
 * VerifyAuditChainCommand — ট্যাম্পার-এভিডেন্ট অডিট চেইন ও চেকপয়েন্ট যাচাই কমান্ড
 */
class VerifyAuditChainCommand extends Command
{
    protected $signature = 'admin:audit-verify
                            {--limit=200 : সর্বশেষ কতটি অডিট লগ যাচাই করা হবে}
                            {--checkpoint : যাচাই সফল হলে নতুন স্বাক্ষরিত অডিট চেকপয়েন্ট সিল করুন}';

    protected $description = 'ট্যাম্পার-এভিডেন্ট SHA-256 অডিট লগ চেইন যাচাই এবং স্বাক্ষরিত চেকপয়েন্ট তৈরি করে';

    public function __construct(
        protected TamperEvidentAuditService $auditService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $result = $this->auditService->verifyIntegrity($limit);

        if (! $result['verified']) {
            $this->error("অডিট চেইন যাচাই ব্যর্থ ({$result['error_code']}): {$result['message']}");

            return Command::FAILURE;
        }

        $this->info($result['message']);

        if ($this->option('checkpoint')) {
            try {
                $cp = $this->auditService->createCheckpoint();
                $this->info("স্বাক্ষরিত অডিট চেকপয়েন্ট #{$cp->checkpoint_number} সফলভাবে সিল করা হয়েছে (Root Hash: {$cp->root_hash})।");
            } catch (Throwable $e) {
                $this->error('চেকপয়েন্ট তৈরি ব্যর্থ: '.$e->getMessage());

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
