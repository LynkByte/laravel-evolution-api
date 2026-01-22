<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs\Message;

use Lynkbyte\EvolutionApi\DTOs\BaseDto;

/**
 * DTO for sending poll messages.
 */
final class SendPollMessageDto extends BaseDto
{
    /**
     * @param array<string> $values Poll options
     */
    public function __construct(
        public readonly string $number,
        public readonly string $name,
        public readonly array $values,
        public readonly int $selectableCount = 1,
        public readonly ?int $delay = null,
        public readonly ?array $quoted = null,
    ) {
        $this->validateRequired(['number', 'name', 'values']);
    }

    /**
     * Create a poll message.
     *
     * @param array<string> $options
     */
    public static function create(string $number, string $question, array $options): self
    {
        return new self(number: $number, name: $question, values: $options);
    }

    /**
     * Allow multiple selections.
     */
    public function multipleChoice(int $maxSelections): self
    {
        return new self(
            number: $this->number,
            name: $this->name,
            values: $this->values,
            selectableCount: $maxSelections,
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
            'name' => $this->name,
            'values' => $this->values,
            'selectableCount' => $this->selectableCount,
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
