<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs\Message;

use Lynkbyte\EvolutionApi\DTOs\BaseDto;

/**
 * DTO for sending text messages.
 */
final class SendTextMessageDto extends BaseDto
{
    /**
     * Flag to indicate if this is a partial DTO (for fluent builder).
     */
    private bool $isPartial = false;

    public function __construct(
        public readonly string $number,
        public readonly string $text,
        public readonly ?int $delay = null,
        public readonly ?bool $linkPreview = true,
        public readonly ?string $mentionsEveryOne = null,
        public readonly ?array $mentioned = null,
        public readonly ?array $quoted = null,
        bool $skipValidation = false,
    ) {
        if (!$skipValidation) {
            $this->validateRequired(['number', 'text']);
        }
        $this->isPartial = $skipValidation;
    }

    /**
     * Create with fluent interface.
     */
    public static function to(string $number): self
    {
        return new self(
            number: $number,
            text: '',
            skipValidation: true
        );
    }

    /**
     * Set the message text.
     */
    public function withText(string $text): self
    {
        return new self(
            number: $this->number,
            text: $text,
            delay: $this->delay,
            linkPreview: $this->linkPreview,
            mentionsEveryOne: $this->mentionsEveryOne,
            mentioned: $this->mentioned,
            quoted: $this->quoted,
            skipValidation: empty($text),
        );
    }

    /**
     * Set message delay.
     */
    public function withDelay(int $milliseconds): self
    {
        return new self(
            number: $this->number,
            text: $this->text,
            delay: $milliseconds,
            linkPreview: $this->linkPreview,
            mentionsEveryOne: $this->mentionsEveryOne,
            mentioned: $this->mentioned,
            quoted: $this->quoted,
            skipValidation: $this->isPartial,
        );
    }

    /**
     * Enable/disable link preview.
     */
    public function withLinkPreview(bool $enable): self
    {
        return new self(
            number: $this->number,
            text: $this->text,
            delay: $this->delay,
            linkPreview: $enable,
            mentionsEveryOne: $this->mentionsEveryOne,
            mentioned: $this->mentioned,
            quoted: $this->quoted,
            skipValidation: $this->isPartial,
        );
    }

    /**
     * Quote a message.
     *
     * @param array{key: array{remoteJid: string, fromMe: bool, id: string}} $quoted
     */
    public function quoting(array $quoted): self
    {
        return new self(
            number: $this->number,
            text: $this->text,
            delay: $this->delay,
            linkPreview: $this->linkPreview,
            mentionsEveryOne: $this->mentionsEveryOne,
            mentioned: $this->mentioned,
            quoted: $quoted,
            skipValidation: $this->isPartial,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toApiPayload(): array
    {
        $payload = [
            'number' => $this->number,
            'text' => $this->text,
        ];

        if ($this->delay !== null) {
            $payload['delay'] = $this->delay;
        }

        if ($this->linkPreview !== null) {
            $payload['linkPreview'] = $this->linkPreview;
        }

        if ($this->mentionsEveryOne !== null) {
            $payload['mentionsEveryOne'] = $this->mentionsEveryOne;
        }

        if ($this->mentioned !== null) {
            $payload['mentioned'] = $this->mentioned;
        }

        if ($this->quoted !== null) {
            $payload['quoted'] = $this->quoted;
        }

        return $payload;
    }
}
