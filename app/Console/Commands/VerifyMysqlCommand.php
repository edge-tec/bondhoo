<?php

namespace App\Console\Commands;

use App\Services\Admin\EnterpriseGovernanceAndSloService;
use Illuminate\Console\Command;

/**
 * VerifyMysqlCommand — Gate B: Real MySQL 8.0+ Integration, Capabilities & ACID Verification Command
 */
class VerifyMysqlCommand extends Command
{
    protected $signature = 'admin:mysql-verify
                            {--connection=mysql : Database connection name to verify}
                            {--database= : Override database name}
                            {--socket= : Override unix socket path}
                            {--host= : Override host}
                            {--port= : Override port}
                            {--json : Output structured JSON report}';

    protected $description = 'Gate B: বাস্তব MySQL 8.0+ সার্ভার সংযোগ, উইন্ডো ফাংশন, CTE, ফরেন-কি এবং ট্রানজেকশন অখণ্ডতা যাচাই করে';

    public function __construct(
        protected EnterpriseGovernanceAndSloService $governanceService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $connection = (string) $this->option('connection');

        // Apply runtime overrides if provided
        if ($this->option('database')) {
            config(["database.connections.{$connection}.database" => $this->option('database')]);
        }
        if ($this->option('socket')) {
            config(["database.connections.{$connection}.unix_socket" => $this->option('socket')]);
        }
        if ($this->option('host')) {
            config(["database.connections.{$connection}.host" => $this->option('host')]);
        }
        if ($this->option('port')) {
            config(["database.connections.{$connection}.port" => $this->option('port')]);
        }

        $this->info("=== GATE B: REAL MYSQL 8.0+ INTEGRATION AUDIT ({$connection}) ===");

        $result = $this->governanceService->verifyMysql80Integration($connection);

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->line("Status: {$result['status']} | Verified: ".($result['verified'] ? 'YES' : 'NO'));
            $this->line("Target: {$result['connection_target']['driver']}://{$result['connection_target']['database']} via {$result['connection_target']['endpoint']}");
            $this->line("Server Version: {$result['server_version']} | Engine: {$result['default_engine']}");

            $this->newLine();
            $this->line('Capability Checks:');
            foreach ($result['checks'] as $checkKey => $check) {
                $statusLabel = $check['passed'] ? '[PASS]' : '[FAIL]';
                $this->line(sprintf('  %-8s %-32s : %s', $statusLabel, $checkKey, $check['detail'] ?? ''));
            }

            if (! $result['verified']) {
                $this->newLine();
                $this->error('Blocker: '.($result['blocker'] ?? 'Verification failed'));
            }
        }

        return $result['verified'] ? Command::SUCCESS : Command::FAILURE;
    }
}
