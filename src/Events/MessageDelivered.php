<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Events;

/**
 * Event fired when a message is delivered to the recipient.
 */
class MessageDelivered extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>  $data  The raw delivery data from webhook
     */
    public function __construct(
        string $instanceName,
        public readonly string $messageId,
        public readonly string $remoteJid,
        public readonly array $data = []
    ) {
        parent::__construct($instanceName);
    }

    /**
     * Get the recipient's phone number.
     */
    public function getRecipient(): string
    {
        return $this->remoteJid;
    }

    /**
     * Get the delivery timestamp if available.
     */
    public function getDeliveredAt(): ?int
    {
        return $this->data['messageTimestamp'] ?? $this->timestamp;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'message_id' => $this->messageId,
            'remote_jid' => $this->remoteJid,
            'recipient' => $this->getRecipient(),
            'delivered_at' => $this->getDeliveredAt(),
            'data' => $this->data,
        ]);
    }
}
