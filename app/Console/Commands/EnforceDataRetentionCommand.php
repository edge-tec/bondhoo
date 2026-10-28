<?php

namespace App\Console\Commands;

use App\Services\Admin\DataGovernanceService;
use Illuminate\Console\Command;

class EnforceDataRetentionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:enforce-data-retention {--dry-run : Simulate retention pruning without deleting records}';

    /**
     * The console command description.
     */
    protected $description = 'Enforce enterprise data retention policies and prune expired records';

    /**
     * Execute the console command.
     */
    public function handle(DataGovernanceService $governanceService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'Simulating data retention policy enforcement (DRY RUN)...' : 'Enforcing enterprise data retention policies...');

        $results = $governanceService->enforceRetentionPolicies($dryRun);

        foreach ($results as $res) {
            $this->line(" - [{$res['category']}] {$res['name']}: {$res['affected_count']} records eligible ({$res['retention_days']} days)");
        }

        $this->info('Data retention execution completed successfully.');

        return self::SUCCESS;
    }
}
