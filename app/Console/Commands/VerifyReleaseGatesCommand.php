<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Admin\EnterpriseGovernanceAndSloService;
use Illuminate\Console\Command;

/**
 * VerifyReleaseGatesCommand — ১৪টি রিলিজ গেটের বাস্তব প্রমাণ ও ডেপ্লয়মেন্ট অনুমোদন যাচাই কমান্ড
 */
class VerifyReleaseGatesCommand extends Command
{
    protected $signature = 'admin:release-verify
                            {--json : Output full structured JSON report}
                            {--disk= : Target off-site backup storage disk (e.g. s3, r2)}
                            {--tag= : Release version tag to record}';

    protected $description = '১৪টি রিলিজ গেটের স্বয়ংক্রিয় ও বাস্তব প্রমাণ যাচাই করে প্রোডাকশন ডেপ্লয়মেন্টের অনুমতি নির্ধারণ করে';

    public function __construct(
        protected EnterpriseGovernanceAndSloService $governanceService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $version = $this->option('tag') ? (string) $this->option('tag') : null;
        $disk = $this->option('disk') ? (string) $this->option('disk') : null;
        $result = $this->governanceService->evaluateReleaseGates(null, $version, $disk);

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->info("=== JUGAJUG ENTERPRISE 14 RELEASE GATES EVALUATION ({$result['release_uid']}) ===");
            $this->line("Version: {$result['release_version']} | Commit: {$result['commit_sha']} | Overall: ".strtoupper($result['overall_status']));
            $this->line('Production Deploy Allowed: '.($result['production_deploy_allowed'] ? 'YES' : 'NO'));
            $this->newLine();

            $rows = [];
            foreach ($result['gates'] as $gateKey => $gate) {
                $rows[] = [
                    $gateKey,
                    $gate['status'],
                    $gate['detail'],
                ];
            }

            $this->table(['Gate Identifier', 'Status', 'Evidence & Details'], $rows);

            if (! empty($result['blockers'])) {
                $this->newLine();
                $this->error('MANDATORY RELEASE BLOCKERS ('.count($result['blockers']).'):');
                foreach ($result['blockers'] as $blocker) {
                    $this->warn(" [!] {$blocker}");
                }
            }
        }

        return $result['production_deploy_allowed'] ? Command::SUCCESS : Command::FAILURE;
    }
}
