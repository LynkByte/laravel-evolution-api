<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;
use Lynkbyte\EvolutionApi\Events\WebhookReceived;
use Lynkbyte\EvolutionApi\Webhooks\WebhookProcessor;

/**
 * Job to process incoming webhooks via queue.
 */
class ProcessWebhookJob implements ShouldQueue
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
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     *
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly array $payload,
        public readonly ?string $instanceName = null,
        public readonly ?string $event = null
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
    public function handle(WebhookProcessor $processor): void
    {
        $dto = WebhookPayloadDto::fromArray($this->payload);

        // Dispatch event for listeners
        event(new WebhookReceived($dto));

        // Process through the webhook processor
        $processor->process($dto);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        $tags = ['evolution-api', 'webhook'];

        if ($this->instanceName) {
            $tags[] = "instance:{$this->instanceName}";
        }

        if ($this->event) {
            $tags[] = "event:{$this->event}";
        }

        return $tags;
    }

    /**
     * Create from raw webhook data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromWebhook(array $data): self
    {
        return new self(
            payload: $data,
            instanceName: $data['instance'] ?? $data['instanceName'] ?? null,
            event: $data['event'] ?? null
        );
    }
}
