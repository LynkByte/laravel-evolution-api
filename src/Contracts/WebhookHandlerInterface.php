<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Contracts;

use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;

/**
 * Interface for webhook event handlers.
 */
interface WebhookHandlerInterface
{
    /**
     * Handle an incoming webhook event.
     */
    public function handle(WebhookPayloadDto $payload): void;

    /**
     * Determine if this handler should process the event.
     */
    public function shouldHandle(WebhookPayloadDto $payload): bool;

    /**
     * Get the events this handler listens to.
     *
     * @return array<string>
     */
    public function events(): array;
}
