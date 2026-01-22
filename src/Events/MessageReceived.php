<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Events;

use Lynkbyte\EvolutionApi\Enums\MessageType;

/**
 * Event fired when a message is received via webhook.
 */
class MessageReceived extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $message The raw message data from webhook
     * @param array<string, mixed> $sender The sender information
     */
    public function __construct(
        string $instanceName,
        public readonly array $message,
        public readonly array $sender = [],
        public readonly ?MessageType $messageType = null,
        public readonly bool $isGroup = false,
        public readonly ?string $groupId = null
    ) {
        parent::__construct($instanceName);
    }

    /**
     * Get the message ID.
     */
    public function getMessageId(): ?string
    {
        return $this->message['key']['id'] ?? $this->message['id'] ?? null;
    }

    /**
     * Get the sender's phone number (JID).
     */
    public function getSenderNumber(): ?string
    {
        return $this->sender['pushName'] 
            ?? $this->message['key']['remoteJid'] 
            ?? null;
    }

    /**
     * Get the sender's push name.
     */
    public function getSenderName(): ?string
    {
        return $this->sender['pushName'] ?? null;
    }

    /**
     * Get the message content/body.
     */
    public function getContent(): ?string
    {
        return $this->message['message']['conversation']
            ?? $this->message['message']['extendedTextMessage']['text']
            ?? $this->message['body']
            ?? null;
    }

    /**
     * Check if the message is from a group.
     */
    public function isFromGroup(): bool
    {
        return $this->isGroup;
    }

    /**
     * Get the quoted message if this is a reply.
     *
     * @return array<string, mixed>|null
     */
    public function getQuotedMessage(): ?array
    {
        return $this->message['message']['extendedTextMessage']['contextInfo']['quotedMessage'] ?? null;
    }

    /**
     * Check if this message is a reply to another message.
     */
    public function isReply(): bool
    {
        return $this->getQuotedMessage() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'message_id' => $this->getMessageId(),
            'message_type' => $this->messageType?->value,
            'sender_number' => $this->getSenderNumber(),
            'sender_name' => $this->getSenderName(),
            'content' => $this->getContent(),
            'is_group' => $this->isGroup,
            'group_id' => $this->groupId,
            'is_reply' => $this->isReply(),
            'raw_message' => $this->message,
        ]);
    }
}
