<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Events;

/**
 * Event fired when a message fails to send.
 */
class MessageFailed extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $message
     */
    public function __construct(
        string $instanceName,
        public readonly string $messageType,
        public readonly array $message,
        public readonly \Throwable $exception
    ) {
        parent::__construct($instanceName);
    }

    /**
     * Get the error message.
     */
    public function getErrorMessage(): string
    {
        return $this->exception->getMessage();
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
            'error' => $this->getErrorMessage(),
            'recipient' => $this->getRecipient(),
        ]);
    }
}
