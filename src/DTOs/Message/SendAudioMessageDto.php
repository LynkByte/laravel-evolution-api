<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs\Message;

use Lynkbyte\EvolutionApi\DTOs\BaseDto;

/**
 * DTO for sending audio messages.
 */
final class SendAudioMessageDto extends BaseDto
{
    public function __construct(
        public readonly string $number,
        public readonly string $audio,
        public readonly ?int $delay = null,
        public readonly ?array $quoted = null,
    ) {
        $this->validateRequired(['number', 'audio']);
    }

    /**
     * Create an audio message.
     */
    public static function to(string $number, string $audioUrl): self
    {
        return new self(number: $number, audio: $audioUrl);
    }

    /**
     * Set message delay.
     */
    public function withDelay(int $milliseconds): self
    {
        return new self(
            number: $this->number,
            audio: $this->audio,
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
            'audio' => $this->audio,
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
