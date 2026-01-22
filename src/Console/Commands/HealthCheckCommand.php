<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Console\Commands;

use Illuminate\Console\Command;
use Lynkbyte\EvolutionApi\Services\EvolutionService;

/**
 * Command to check Evolution API connectivity and health.
 */
class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'evolution-api:health 
                            {--connection= : Specific connection to check}';

    /**
     * The console command description.
     */
    protected $description = 'Check Evolution API server health and connectivity';

    /**
     * Execute the console command.
     */
    public function handle(EvolutionService $evolution): int
    {
        $this->info('Checking Evolution API health...');
        $this->newLine();

        $connection = $this->option('connection');

        try {
            // Switch connection if specified
            if ($connection) {
                $evolution->connection($connection);
                $this->comment("Using connection: {$connection}");
            }

            // Check server connectivity
            $this->checkServerConnectivity($evolution);

            // List instances
            $this->listInstances($evolution);

            $this->newLine();
            $this->info('Health check completed successfully!');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Health check failed: ' . $e->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Check server connectivity.
     */
    protected function checkServerConnectivity(EvolutionService $evolution): void
    {
        $this->comment('Checking server connectivity...');

        $serverUrl = config('evolution-api.server_url');
        $this->line("  Server URL: <info>{$serverUrl}</info>");

        // Try to fetch instances as a connectivity test
        $startTime = microtime(true);
        $response = $evolution->instance()->fetchAll();
        $responseTime = round((microtime(true) - $startTime) * 1000);

        $this->line("  Status: <info>Connected</info>");
        $this->line("  Response time: <info>{$responseTime}ms</info>");
    }

    /**
     * List instances and their status.
     */
    protected function listInstances(EvolutionService $evolution): void
    {
        $this->newLine();
        $this->comment('Fetching instances...');

        $response = $evolution->instance()->fetchAll();

        if (!$response->isSuccess()) {
            $this->warn('  Could not fetch instances: ' . ($response->getError() ?? 'Unknown error'));
            return;
        }

        $instances = $response->getData();

        if (empty($instances)) {
            $this->line('  No instances found.');
            return;
        }

        $this->newLine();
        $this->info('Instances:');

        $tableData = [];
        foreach ($instances as $instance) {
            $name = $instance['instance']['instanceName'] ?? $instance['name'] ?? 'Unknown';
            $status = $instance['instance']['status'] ?? $instance['status'] ?? 'Unknown';
            $owner = $instance['instance']['owner'] ?? $instance['owner'] ?? '-';

            $statusFormatted = match (strtolower($status)) {
                'open', 'connected' => "<fg=green>{$status}</>",
                'close', 'disconnected' => "<fg=red>{$status}</>",
                'connecting' => "<fg=yellow>{$status}</>",
                default => $status,
            };

            $tableData[] = [$name, $statusFormatted, $owner];
        }

        $this->table(['Instance', 'Status', 'Owner'], $tableData);
    }
}
