<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Events;

/**
 * Event fired when a message is read by the recipient.
 */
class MessageRead extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @param array<string>|string $messageIds The message ID(s) that were read
     * @param array<string, mixed> $data The raw read receipt data from webhook
     */
    public function __construct(
        string $instanceName,
        public readonly array|string $messageIds,
        public readonly string $remoteJid,
        public readonly array $data = []
    ) {
        parent::__construct($instanceName);
    }

    /**
     * Get the message IDs as an array.
     *
     * @return array<string>
     */
    public function getMessageIds(): array
    {
        return is_array($this->messageIds) ? $this->messageIds : [$this->messageIds];
    }

    /**
     * Get the recipient's phone number.
     */
    public function getRecipient(): string
    {
        return $this->remoteJid;
    }

    /**
     * Get the read timestamp if available.
     */
    public function getReadAt(): ?int
    {
        return $this->data['readTimestamp'] ?? $this->timestamp;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'message_ids' => $this->getMessageIds(),
            'remote_jid' => $this->remoteJid,
            'recipient' => $this->getRecipient(),
            'read_at' => $this->getReadAt(),
            'data' => $this->data,
        ]);
    }
}
