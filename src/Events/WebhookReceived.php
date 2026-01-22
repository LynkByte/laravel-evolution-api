<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Events;

use Lynkbyte\EvolutionApi\Enums\WebhookEvent;

/**
 * Event fired when any webhook is received from Evolution API.
 *
 * This is a generic event that fires for all webhook types.
 * Use this for logging, debugging, or handling custom webhook events.
 */
class WebhookReceived extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>  $payload  The raw webhook payload
     */
    public function __construct(
        string $instanceName,
        public readonly string $event,
        public readonly array $payload,
        public readonly ?WebhookEvent $webhookEvent = null
    ) {
        parent::__construct($instanceName);
    }

    /**
     * Get the webhook event type as string.
     */
    public function getEventType(): string
    {
        return $this->event;
    }

    /**
     * Get the webhook event enum if it's a known event type.
     */
    public function getWebhookEvent(): ?WebhookEvent
    {
        return $this->webhookEvent;
    }

    /**
     * Check if this is a known webhook event type.
     */
    public function isKnownEvent(): bool
    {
        return $this->webhookEvent !== null;
    }

    /**
     * Get a specific value from the payload using dot notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->payload;

        foreach ($keys as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Check if the payload has a specific key.
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Check if this is a message-related event.
     */
    public function isMessageEvent(): bool
    {
        return in_array($this->webhookEvent, [
            WebhookEvent::MESSAGES_SET,
            WebhookEvent::MESSAGES_UPSERT,
            WebhookEvent::MESSAGES_UPDATE,
            WebhookEvent::MESSAGES_DELETE,
            WebhookEvent::SEND_MESSAGE,
        ], true);
    }

    /**
     * Check if this is a connection-related event.
     */
    public function isConnectionEvent(): bool
    {
        return in_array($this->webhookEvent, [
            WebhookEvent::CONNECTION_UPDATE,
            WebhookEvent::QRCODE_UPDATED,
        ], true);
    }

    /**
     * Check if this is a group-related event.
     */
    public function isGroupEvent(): bool
    {
        return in_array($this->webhookEvent, [
            WebhookEvent::GROUPS_UPSERT,
            WebhookEvent::GROUP_UPDATE,
            WebhookEvent::GROUP_PARTICIPANTS_UPDATE,
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'event' => $this->event,
            'webhook_event' => $this->webhookEvent?->value,
            'is_known_event' => $this->isKnownEvent(),
            'payload' => $this->payload,
        ]);
    }
}
