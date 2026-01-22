<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Console\Commands;

use Illuminate\Console\Command;
use Lynkbyte\EvolutionApi\Models\EvolutionInstance;
use Lynkbyte\EvolutionApi\Services\EvolutionService;

/**
 * Command to display and manage instance statuses.
 */
class InstanceStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'evolution-api:instances 
                            {action=list : Action to perform (list, sync, connect, disconnect)}
                            {instance? : Instance name for connect/disconnect actions}
                            {--connection= : Specific connection to use}';

    /**
     * The console command description.
     */
    protected $description = 'Manage and display Evolution API instance statuses';

    /**
     * Execute the console command.
     */
    public function handle(EvolutionService $evolution): int
    {
        $action = $this->argument('action');
        $connection = $this->option('connection');

        if ($connection) {
            $evolution->connection($connection);
        }

        return match ($action) {
            'list' => $this->listInstances($evolution),
            'sync' => $this->syncInstances($evolution),
            'connect' => $this->connectInstance($evolution),
            'disconnect' => $this->disconnectInstance($evolution),
            default => $this->invalidAction($action),
        };
    }

    /**
     * List all instances.
     */
    protected function listInstances(EvolutionService $evolution): int
    {
        $this->info('Fetching instances from Evolution API...');

        try {
            $response = $evolution->instance()->fetchAll();

            if (! $response->isSuccess()) {
                $this->error('Failed to fetch instances: '.($response->getError() ?? 'Unknown error'));

                return self::FAILURE;
            }

            $instances = $response->getData();

            if (empty($instances)) {
                $this->warn('No instances found.');

                return self::SUCCESS;
            }

            $tableData = [];
            foreach ($instances as $instance) {
                $name = $instance['instance']['instanceName'] ?? $instance['name'] ?? 'Unknown';
                $status = $instance['instance']['status'] ?? $instance['status'] ?? 'Unknown';
                $owner = $instance['instance']['owner'] ?? $instance['owner'] ?? '-';
                $profileName = $instance['instance']['profileName'] ?? '-';

                $statusColor = match (strtolower($status)) {
                    'open', 'connected' => 'green',
                    'close', 'disconnected' => 'red',
                    'connecting' => 'yellow',
                    'qrcode' => 'cyan',
                    default => 'white',
                };

                $tableData[] = [
                    $name,
                    "<fg={$statusColor}>{$status}</>",
                    $owner,
                    $profileName,
                ];
            }

            $this->newLine();
            $this->table(['Instance', 'Status', 'Owner', 'Profile'], $tableData);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Sync instances from API to database.
     */
    protected function syncInstances(EvolutionService $evolution): int
    {
        $this->info('Syncing instances from Evolution API to database...');

        try {
            $response = $evolution->instance()->fetchAll();

            if (! $response->isSuccess()) {
                $this->error('Failed to fetch instances: '.($response->getError() ?? 'Unknown error'));

                return self::FAILURE;
            }

            $instances = $response->getData();
            $syncedCount = 0;

            foreach ($instances as $instanceData) {
                $name = $instanceData['instance']['instanceName'] ?? $instanceData['name'] ?? null;

                if (! $name) {
                    continue;
                }

                $status = $instanceData['instance']['status'] ?? $instanceData['status'] ?? 'unknown';
                $owner = $instanceData['instance']['owner'] ?? $instanceData['owner'] ?? null;
                $profileName = $instanceData['instance']['profileName'] ?? null;
                $profilePicture = $instanceData['instance']['profilePictureUrl'] ?? null;

                EvolutionInstance::updateOrCreate(
                    ['name' => $name],
                    [
                        'status' => strtolower($status),
                        'phone_number' => $owner,
                        'profile_name' => $profileName,
                        'profile_picture_url' => $profilePicture,
                        'last_seen_at' => now(),
                    ]
                );

                $syncedCount++;
                $this->line("  Synced: <info>{$name}</info> ({$status})");
            }

            $this->newLine();
            $this->info("Synced {$syncedCount} instance(s) to database.");

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Connect an instance.
     */
    protected function connectInstance(EvolutionService $evolution): int
    {
        $instanceName = $this->argument('instance');

        if (! $instanceName) {
            $this->error('Instance name is required for connect action.');

            return self::FAILURE;
        }

        $this->info("Connecting instance: {$instanceName}...");

        try {
            $response = $evolution->for($instanceName)->instance()->connect();

            if (! $response->isSuccess()) {
                $this->error('Failed to connect: '.($response->getError() ?? 'Unknown error'));

                return self::FAILURE;
            }

            $data = $response->getData();

            // Check if QR code is returned
            if (isset($data['qrcode']['base64'])) {
                $this->newLine();
                $this->warn('QR Code generated. Scan to connect:');
                $this->line($data['qrcode']['base64']);

                if (isset($data['pairingCode'])) {
                    $this->newLine();
                    $this->info('Pairing Code: '.$data['pairingCode']);
                }
            } else {
                $this->info('Instance connected successfully!');
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Disconnect an instance.
     */
    protected function disconnectInstance(EvolutionService $evolution): int
    {
        $instanceName = $this->argument('instance');

        if (! $instanceName) {
            $this->error('Instance name is required for disconnect action.');

            return self::FAILURE;
        }

        if (! $this->confirm("Are you sure you want to disconnect instance '{$instanceName}'?")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $this->info("Disconnecting instance: {$instanceName}...");

        try {
            $response = $evolution->for($instanceName)->instance()->logout();

            if (! $response->isSuccess()) {
                $this->error('Failed to disconnect: '.($response->getError() ?? 'Unknown error'));

                return self::FAILURE;
            }

            $this->info('Instance disconnected successfully!');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Handle invalid action.
     */
    protected function invalidAction(string $action): int
    {
        $this->error("Invalid action: {$action}");
        $this->line('Available actions: list, sync, connect, disconnect');

        return self::FAILURE;
    }
}
