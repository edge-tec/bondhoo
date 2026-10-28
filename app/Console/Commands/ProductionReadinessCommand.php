<?php

namespace App\Console\Commands;

use App\Services\Admin\ProductionReadinessService;
use Illuminate\Console\Command;

/**
 * ProductionReadinessCommand — প্রোডাকশন কনফিগারেশন, নিরাপত্তা ও ডেপ্লয়মেন্ট প্রস্তুতি যাচাই কমান্ড
 */
class ProductionReadinessCommand extends Command
{
    protected $signature = 'admin:production-readiness
                            {--json : Output full structured JSON report}
                            {--fail-on-warning : Exit with failure code when warnings exist}';

    protected $description = 'প্রোডাকশন কনফিগারেশন, নিরাপত্তা, ব্যাকআপ এবং অডিট চেইনের প্রস্তুতি যাচাই করে';

    public function __construct(
        protected ProductionReadinessService $readinessService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $report = $this->readinessService->runReadinessAudit(null, true);

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->info("=== JUGAJUG ENTERPRISE PRODUCTION READINESS AUDIT ({$report['run_uid']}) ===");
            $this->line("Environment: {$report['environment']} | Score: {$report['readiness_score']}/100 | Overall: ".strtoupper($report['overall_status']));

            $rows = array_map(fn (array $c) => [
                $c['id'],
                strtoupper($c['status']),
                strtoupper($c['severity']),
                $c['title'],
            ], $report['checks']);

            $this->table(['Check ID', 'Status', 'Severity', 'Title'], $rows);
        }

        if ($report['summary']['fail_count'] > 0) {
            return Command::FAILURE;
        }

        if ($this->option('fail-on-warning') && $report['summary']['warning_count'] > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
