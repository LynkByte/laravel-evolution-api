<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Lynkbyte\EvolutionApi\DTOs\Message\SendTextMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendMediaMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendAudioMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendLocationMessageDto;
use Lynkbyte\EvolutionApi\Events\MessageSent;
use Lynkbyte\EvolutionApi\Events\MessageFailed;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

/**
 * Job to send WhatsApp messages via queue.
 */
class SendMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int>
     */
    public array $backoff;

    /**
     * The maximum number of exceptions to allow before failing.
     */
    public int $maxExceptions;

    /**
     * Create a new job instance.
     *
     * @param array<string, mixed> $message
     */
    public function __construct(
        public readonly string $instanceName,
        public readonly string $messageType,
        public readonly array $message,
        public readonly ?string $connectionName = null
    ) {
        $config = config('evolution-api.queue', []);

        $this->tries = $config['max_exceptions'] ?? 3;
        $this->backoff = $config['backoff'] ?? [60, 300, 900];
        $this->maxExceptions = $config['max_exceptions'] ?? 3;

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

        $service->for($this->instanceName);

        try {
            $response = match ($this->messageType) {
                'text' => $service->messages()->sendText(
                    SendTextMessageDto::fromArray($this->message)
                ),
                'media' => $service->messages()->sendMedia(
                    SendMediaMessageDto::fromArray($this->message)
                ),
                'audio' => $service->messages()->sendAudio(
                    SendAudioMessageDto::fromArray($this->message)
                ),
                'location' => $service->messages()->sendLocation(
                    SendLocationMessageDto::fromArray($this->message)
                ),
                default => throw new \InvalidArgumentException(
                    "Unknown message type: {$this->messageType}"
                ),
            };

            if ($response->isSuccessful()) {
                event(new MessageSent(
                    instanceName: $this->instanceName,
                    messageType: $this->messageType,
                    message: $this->message,
                    response: $response->getData()
                ));
            } else {
                $this->handleFailure(new \Exception($response->message ?? 'Message sending failed'));
            }
        } catch (\Throwable $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    protected function handleFailure(\Throwable $exception): void
    {
        event(new MessageFailed(
            instanceName: $this->instanceName,
            messageType: $this->messageType,
            message: $this->message,
            exception: $exception
        ));
    }

    /**
     * Handle a job failure after all retries.
     */
    public function failed(\Throwable $exception): void
    {
        $this->handleFailure($exception);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'evolution-api',
            'message',
            "instance:{$this->instanceName}",
            "type:{$this->messageType}",
        ];
    }

    /**
     * Create a text message job.
     *
     * @param array<string, mixed> $options
     */
    public static function text(
        string $instanceName,
        string $number,
        string $text,
        array $options = [],
        ?string $connectionName = null
    ): self {
        return new self(
            instanceName: $instanceName,
            messageType: 'text',
            message: array_merge(['number' => $number, 'text' => $text], $options),
            connectionName: $connectionName
        );
    }

    /**
     * Create a media message job.
     *
     * @param array<string, mixed> $options
     */
    public static function media(
        string $instanceName,
        string $number,
        string $mediatype,
        string $media,
        array $options = [],
        ?string $connectionName = null
    ): self {
        return new self(
            instanceName: $instanceName,
            messageType: 'media',
            message: array_merge([
                'number' => $number,
                'mediatype' => $mediatype,
                'media' => $media,
            ], $options),
            connectionName: $connectionName
        );
    }
}
