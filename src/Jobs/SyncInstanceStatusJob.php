<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Lynkbyte\EvolutionApi\Events\InstanceStatusChanged;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

/**
 * Job to sync instance status from Evolution API.
 */
class SyncInstanceStatusJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly ?string $instanceName = null,
        public readonly ?string $connectionName = null
    ) {
        $config = config('evolution-api.queue', []);

        $this->onQueue($config['queue'] ?? 'evolution-api');

        if (isset($config['connection'])) {
            $this->onConnection($config['connection']);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = EvolutionApi::getFacadeRoot();

        if ($this->connectionName) {
            $service->connection($this->connectionName);
        }

        if ($this->instanceName) {
            // Sync single instance
            $this->syncInstance($service, $this->instanceName);
        } else {
            // Sync all instances
            $this->syncAllInstances($service);
        }
    }

    /**
     * Sync a single instance status.
     */
    protected function syncInstance($service, string $instanceName): void
    {
        $service->for($instanceName);

        $response = $service->instances()->connectionState($instanceName);

        if ($response->isSuccessful()) {
            $state = $response->get('state') ?? $response->get('instance', [])['state'] ?? 'unknown';

            event(new InstanceStatusChanged(
                instanceName: $instanceName,
                status: \Lynkbyte\EvolutionApi\Enums\InstanceStatus::fromString($state),
                previousStatus: null, // Could cache previous status
                data: $response->getData()
            ));
        }
    }

    /**
     * Sync all instances.
     */
    protected function syncAllInstances($service): void
    {
        $response = $service->instances()->fetchAll();

        if (! $response->isSuccessful()) {
            return;
        }

        $instances = $response->getData();

        foreach ($instances as $instance) {
            $instanceName = $instance['instanceName'] ?? $instance['name'] ?? null;

            if ($instanceName) {
                $this->syncInstance($service, $instanceName);
            }
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        $tags = ['evolution-api', 'sync-status'];

        if ($this->instanceName) {
            $tags[] = "instance:{$this->instanceName}";
        }

        return $tags;
    }
}
