<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs\Message;

use Lynkbyte\EvolutionApi\DTOs\BaseDto;

/**
 * DTO for sending status/story messages.
 */
final class SendStatusMessageDto extends BaseDto
{
    public function __construct(
        public readonly string $type,
        public readonly ?string $content = null,
        public readonly ?string $caption = null,
        public readonly ?string $backgroundColor = null,
        public readonly ?int $font = null,
        public readonly bool $allContacts = true,
        public readonly ?array $statusJidList = null,
    ) {
        $this->validateRequired(['type']);
    }

    /**
     * Create a text status.
     */
    public static function text(string $content, ?string $backgroundColor = '#000000', int $font = 1): self
    {
        return new self(
            type: 'text',
            content: $content,
            backgroundColor: $backgroundColor,
            font: $font,
        );
    }

    /**
     * Create an image status.
     */
    public static function image(string $imageUrl, ?string $caption = null): self
    {
        return new self(
            type: 'image',
            content: $imageUrl,
            caption: $caption,
        );
    }

    /**
     * Create a video status.
     */
    public static function video(string $videoUrl, ?string $caption = null): self
    {
        return new self(
            type: 'video',
            content: $videoUrl,
            caption: $caption,
        );
    }

    /**
     * Create an audio status.
     */
    public static function audio(string $audioUrl): self
    {
        return new self(
            type: 'audio',
            content: $audioUrl,
        );
    }

    /**
     * Send to specific contacts only.
     *
     * @param array<string> $jids
     */
    public function toContacts(array $jids): self
    {
        return new self(
            type: $this->type,
            content: $this->content,
            caption: $this->caption,
            backgroundColor: $this->backgroundColor,
            font: $this->font,
            allContacts: false,
            statusJidList: $jids,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toApiPayload(): array
    {
        $payload = [
            'type' => $this->type,
            'allContacts' => $this->allContacts,
        ];

        if ($this->content !== null) {
            $payload['content'] = $this->content;
        }

        if ($this->caption !== null) {
            $payload['caption'] = $this->caption;
        }

        if ($this->backgroundColor !== null) {
            $payload['backgroundColor'] = $this->backgroundColor;
        }

        if ($this->font !== null) {
            $payload['font'] = $this->font;
        }

        if ($this->statusJidList !== null) {
            $payload['statusJidList'] = $this->statusJidList;
        }

        return $payload;
    }
}
