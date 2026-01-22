<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs\Message;

use Lynkbyte\EvolutionApi\DTOs\BaseDto;

/**
 * DTO for sending sticker messages.
 */
final class SendStickerMessageDto extends BaseDto
{
    public function __construct(
        public readonly string $number,
        public readonly string $sticker,
        public readonly ?int $delay = null,
        public readonly ?array $quoted = null,
    ) {
        $this->validateRequired(['number', 'sticker']);
    }

    /**
     * Create a sticker message.
     */
    public static function to(string $number, string $stickerUrl): self
    {
        return new self(number: $number, sticker: $stickerUrl);
    }

    /**
     * Set message delay.
     */
    public function withDelay(int $milliseconds): self
    {
        return new self(
            number: $this->number,
            sticker: $this->sticker,
            delay: $milliseconds,
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
            'sticker' => $this->sticker,
        ];

        if ($this->delay !== null) {
            $payload['delay'] = $this->delay;
        }

        if ($this->quoted !== null) {
            $payload['quoted'] = $this->quoted;
        }

        return $payload;
    }
}
