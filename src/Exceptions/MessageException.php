<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Exceptions;

/**
 * Exception thrown when message sending fails.
 */
class MessageException extends EvolutionApiException
{
    /**
     * The message ID.
     */
    protected ?string $messageId = null;

    /**
     * The recipient number.
     */
    protected ?string $recipientNumber = null;

    /**
     * The message type.
     */
    protected ?string $messageType = null;

    /**
     * Create a new message exception.
     */
    public function __construct(
        string $message = 'Message operation failed',
        ?string $messageId = null,
        ?string $recipientNumber = null,
        ?string $messageType = null,
        ?string $instanceName = null,
        ?int $statusCode = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            message: $message,
            code: $statusCode ?? 0,
            previous: $previous,
            statusCode: $statusCode,
            instanceName: $instanceName
        );

        $this->messageId = $messageId;
        $this->recipientNumber = $recipientNumber;
        $this->messageType = $messageType;
    }

    /**
     * Get the message ID.
     */
    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    /**
     * Get the recipient number.
     */
    public function getRecipientNumber(): ?string
    {
        return $this->recipientNumber;
    }

    /**
     * Get the message type.
     */
    public function getMessageType(): ?string
    {
        return $this->messageType;
    }

    /**
     * Create exception for send failure.
     */
    public static function sendFailed(
        string $recipientNumber,
        string $reason,
        ?string $messageType = null,
        ?string $instanceName = null
    ): static {
        return new static(
            message: "Failed to send message to {$recipientNumber}: {$reason}",
            recipientNumber: $recipientNumber,
            messageType: $messageType,
            instanceName: $instanceName
        );
    }

    /**
     * Create exception for invalid recipient.
     */
    public static function invalidRecipient(string $recipientNumber, ?string $instanceName = null): static
    {
        return new static(
            message: "Invalid recipient number: {$recipientNumber}",
            recipientNumber: $recipientNumber,
            instanceName: $instanceName,
            statusCode: 400
        );
    }

    /**
     * Create exception for not a WhatsApp number.
     */
    public static function notWhatsApp(string $recipientNumber, ?string $instanceName = null): static
    {
        return new static(
            message: "Number {$recipientNumber} is not registered on WhatsApp",
            recipientNumber: $recipientNumber,
            instanceName: $instanceName,
            statusCode: 400
        );
    }

    /**
     * Create exception for media upload failure.
     */
    public static function mediaUploadFailed(string $reason, ?string $instanceName = null): static
    {
        return new static(
            message: "Media upload failed: {$reason}",
            messageType: 'media',
            instanceName: $instanceName
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'message_id' => $this->messageId,
            'recipient_number' => $this->recipientNumber,
            'message_type' => $this->messageType,
        ]);
    }
}
