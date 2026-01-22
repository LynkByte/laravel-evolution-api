<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs\Message;

use Lynkbyte\EvolutionApi\DTOs\BaseDto;

/**
 * DTO for sending reaction messages.
 */
final class SendReactionMessageDto extends BaseDto
{
    public function __construct(
        public readonly array $key,
        public readonly string $reaction,
        bool $skipValidation = false,
    ) {
        if (!$skipValidation) {
            $this->validateRequired(['key', 'reaction']);
        }
    }

    /**
     * Create a reaction to a message.
     */
    public static function react(string $remoteJid, string $messageId, bool $fromMe, string $reaction): self
    {
        return new self(
            key: [
                'remoteJid' => $remoteJid,
                'fromMe' => $fromMe,
                'id' => $messageId,
            ],
            reaction: $reaction
        );
    }

    /**
     * Remove reaction from a message.
     */
    public static function remove(string $remoteJid, string $messageId, bool $fromMe): self
    {
        return new self(
            key: [
                'remoteJid' => $remoteJid,
                'fromMe' => $fromMe,
                'id' => $messageId,
            ],
            reaction: '',
            skipValidation: true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toApiPayload(): array
    {
        return [
            'key' => $this->key,
            'reaction' => $this->reaction,
        ];
    }
}
