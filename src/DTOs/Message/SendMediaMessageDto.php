<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs\Message;

use Lynkbyte\EvolutionApi\DTOs\BaseDto;
use Lynkbyte\EvolutionApi\Enums\MediaType;

/**
 * DTO for sending media messages (image, video, document).
 */
final class SendMediaMessageDto extends BaseDto
{
    public function __construct(
        public readonly string $number,
        public readonly string $mediatype,
        public readonly ?string $media = null,
        public readonly ?string $mimetype = null,
        public readonly ?string $caption = null,
        public readonly ?string $fileName = null,
        public readonly ?int $delay = null,
        public readonly ?array $quoted = null,
    ) {
        $this->validateRequired(['number', 'mediatype']);
    }

    /**
     * Create an image message.
     */
    public static function image(string $number, string $url, ?string $caption = null): self
    {
        return new self(
            number: $number,
            mediatype: MediaType::IMAGE->value,
            media: $url,
            caption: $caption,
        );
    }

    /**
     * Create a video message.
     */
    public static function video(string $number, string $url, ?string $caption = null): self
    {
        return new self(
            number: $number,
            mediatype: MediaType::VIDEO->value,
            media: $url,
            caption: $caption,
        );
    }

    /**
     * Create a document message.
     */
    public static function document(string $number, string $url, ?string $fileName = null): self
    {
        return new self(
            number: $number,
            mediatype: MediaType::DOCUMENT->value,
            media: $url,
            fileName: $fileName,
        );
    }

    /**
     * Set message delay.
     */
    public function withDelay(int $milliseconds): self
    {
        return new self(
            number: $this->number,
            mediatype: $this->mediatype,
            media: $this->media,
            mimetype: $this->mimetype,
            caption: $this->caption,
            fileName: $this->fileName,
            delay: $milliseconds,
            quoted: $this->quoted,
        );
    }

    /**
     * Set custom MIME type.
     */
    public function withMimeType(string $mimetype): self
    {
        return new self(
            number: $this->number,
            mediatype: $this->mediatype,
            media: $this->media,
            mimetype: $mimetype,
            caption: $this->caption,
            fileName: $this->fileName,
            delay: $this->delay,
            quoted: $this->quoted,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toApiPayload(): array
    {
        $payload = [
            'number' => $this->number,
            'mediatype' => $this->mediatype,
        ];

        if ($this->media !== null) {
            $payload['media'] = $this->media;
        }

        if ($this->mimetype !== null) {
            $payload['mimetype'] = $this->mimetype;
        }

        if ($this->caption !== null) {
            $payload['caption'] = $this->caption;
        }

        if ($this->fileName !== null) {
            $payload['fileName'] = $this->fileName;
        }

        if ($this->delay !== null) {
            $payload['delay'] = $this->delay;
        }

        if ($this->quoted !== null) {
            $payload['quoted'] = $this->quoted;
        }

        return $payload;
    }
}
