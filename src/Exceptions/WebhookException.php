<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Exceptions;

/**
 * Exception thrown when webhook processing fails.
 */
class WebhookException extends EvolutionApiException
{
    /**
     * The webhook event type.
     */
    protected ?string $eventType = null;

    /**
     * The webhook payload.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $payload = null;

    /**
     * Create a new webhook exception.
     *
     * @param array<string, mixed>|null $payload
     */
    public function __construct(
        string $message = 'Webhook processing failed',
        ?string $eventType = null,
        ?array $payload = null,
        ?string $instanceName = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            message: $message,
            code: 0,
            previous: $previous,
            instanceName: $instanceName
        );

        $this->eventType = $eventType;
        $this->payload = $payload;
    }

    /**
     * Get the event type.
     */
    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    /**
     * Get the webhook payload.
     *
     * @return array<string, mixed>|null
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * Create exception for invalid signature.
     */
    public static function invalidSignature(?string $instanceName = null): static
    {
        return new static(
            message: 'Webhook signature verification failed',
            instanceName: $instanceName
        );
    }

    /**
     * Create exception for invalid payload.
     *
     * @param array<string, mixed>|null $payload
     */
    public static function invalidPayload(?array $payload = null, ?string $instanceName = null): static
    {
        return new static(
            message: 'Invalid webhook payload',
            payload: $payload,
            instanceName: $instanceName
        );
    }

    /**
     * Create exception for unknown event.
     *
     * @param array<string, mixed>|null $payload
     */
    public static function unknownEvent(string $eventType, ?array $payload = null, ?string $instanceName = null): static
    {
        return new static(
            message: "Unknown webhook event: {$eventType}",
            eventType: $eventType,
            payload: $payload,
            instanceName: $instanceName
        );
    }

    /**
     * Create exception for handler failure.
     *
     * @param array<string, mixed>|null $payload
     */
    public static function handlerFailed(
        string $eventType,
        \Throwable $previous,
        ?array $payload = null,
        ?string $instanceName = null
    ): static {
        return new static(
            message: "Webhook handler failed for event {$eventType}: {$previous->getMessage()}",
            eventType: $eventType,
            payload: $payload,
            instanceName: $instanceName,
            previous: $previous
        );
    }

    /**
     * Create exception for processing failure.
     */
    public static function processingFailed(string $eventType, \Throwable $previous): static
    {
        return new static(
            message: "Failed to process webhook event {$eventType}: {$previous->getMessage()}",
            eventType: $eventType,
            previous: $previous
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'event_type' => $this->eventType,
            'payload' => $this->payload,
        ]);
    }
}
