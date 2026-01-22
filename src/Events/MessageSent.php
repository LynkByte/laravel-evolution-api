<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Events;

/**
 * Event fired when a message is successfully sent.
 */
class MessageSent extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        string $instanceName,
        public readonly string $messageType,
        public readonly array $message,
        public readonly array $response = []
    ) {
        parent::__construct($instanceName);
    }

    /**
     * Get the message ID from the response.
     */
    public function getMessageId(): ?string
    {
        return $this->response['key']['id'] ?? $this->response['messageId'] ?? null;
    }

    /**
     * Get the recipient number.
     */
    public function getRecipient(): ?string
    {
        return $this->message['number'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'message_type' => $this->messageType,
            'message' => $this->message,
            'response' => $this->response,
            'message_id' => $this->getMessageId(),
            'recipient' => $this->getRecipient(),
        ]);
    }
}
