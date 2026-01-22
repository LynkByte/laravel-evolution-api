<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs\Webhook;

use Lynkbyte\EvolutionApi\DTOs\BaseDto;
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;

/**
 * Data Transfer Object for webhook payloads from Evolution API.
 */
class WebhookPayloadDto extends BaseDto
{
    /**
     * Create a new webhook payload DTO.
     *
     * @param array<string, mixed> $data The raw webhook data
     */
    public function __construct(
        public readonly string $event,
        public readonly string $instanceName,
        public readonly array $data,
        public readonly ?WebhookEvent $webhookEvent = null,
        public readonly ?string $apiKey = null,
        public readonly int $receivedAt = 0
    ) {
    }

    /**
     * Create from raw webhook payload.
     *
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $event = $payload['event'] ?? 'UNKNOWN';
        $instanceName = $payload['instance'] ?? $payload['instanceName'] ?? 'unknown';
        
        // Remove event and instance from data to keep only the actual payload
        $data = $payload;
        unset($data['event'], $data['instance'], $data['instanceName']);

        return new self(
            event: $event,
            instanceName: $instanceName,
            data: $data,
            webhookEvent: WebhookEvent::fromString($event),
            apiKey: $payload['apiKey'] ?? null,
            receivedAt: time()
        );
    }

    /**
     * Get the webhook event type.
     */
    public function getEventType(): WebhookEvent
    {
        return $this->webhookEvent ?? WebhookEvent::UNKNOWN;
    }

    /**
     * Check if this is a known event type.
     */
    public function isKnownEvent(): bool
    {
        return $this->webhookEvent !== null && $this->webhookEvent !== WebhookEvent::UNKNOWN;
    }

    /**
     * Get a value from the data using dot notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->data;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Check if the data has a specific key.
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get the message data if this is a message event.
     *
     * @return array<string, mixed>|null
     */
    public function getMessageData(): ?array
    {
        return $this->get('data') ?? $this->get('message') ?? null;
    }

    /**
     * Get the sender information if available.
     *
     * @return array<string, mixed>|null
     */
    public function getSenderData(): ?array
    {
        return $this->get('sender') ?? null;
    }

    /**
     * Get the remote JID (sender/recipient identifier).
     */
    public function getRemoteJid(): ?string
    {
        return $this->get('data.key.remoteJid')
            ?? $this->get('key.remoteJid')
            ?? $this->get('remoteJid')
            ?? null;
    }

    /**
     * Get the message ID if available.
     */
    public function getMessageId(): ?string
    {
        return $this->get('data.key.id')
            ?? $this->get('key.id')
            ?? $this->get('messageId')
            ?? null;
    }

    /**
     * Check if this message is from a group.
     */
    public function isFromGroup(): bool
    {
        $remoteJid = $this->getRemoteJid();
        
        if ($remoteJid === null) {
            return false;
        }

        return str_contains($remoteJid, '@g.us');
    }

    /**
     * Get the group ID if this is from a group.
     */
    public function getGroupId(): ?string
    {
        if (!$this->isFromGroup()) {
            return null;
        }

        return $this->getRemoteJid();
    }

    /**
     * Check if this is a message event.
     */
    public function isMessageEvent(): bool
    {
        return $this->webhookEvent?->isMessageEvent() ?? false;
    }

    /**
     * Check if this is a connection event.
     */
    public function isConnectionEvent(): bool
    {
        return $this->webhookEvent?->isConnectionEvent() ?? false;
    }

    /**
     * Check if this is a group event.
     */
    public function isGroupEvent(): bool
    {
        return $this->webhookEvent?->isGroupEvent() ?? false;
    }

    /**
     * Get the connection status if this is a connection event.
     */
    public function getConnectionStatus(): ?string
    {
        return $this->get('data.state')
            ?? $this->get('state')
            ?? $this->get('status')
            ?? null;
    }

    /**
     * Get the QR code if this is a QR code event.
     */
    public function getQrCode(): ?string
    {
        return $this->get('data.qrcode.base64')
            ?? $this->get('qrcode.base64')
            ?? $this->get('qrcode')
            ?? $this->get('base64')
            ?? null;
    }

    /**
     * Get the pairing code if available.
     */
    public function getPairingCode(): ?string
    {
        return $this->get('data.pairingCode')
            ?? $this->get('pairingCode')
            ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'instance_name' => $this->instanceName,
            'webhook_event' => $this->webhookEvent?->value,
            'is_known_event' => $this->isKnownEvent(),
            'data' => $this->data,
            'received_at' => $this->receivedAt,
        ];
    }
}
