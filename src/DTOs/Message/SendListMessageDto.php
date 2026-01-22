<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs\Message;

use Lynkbyte\EvolutionApi\DTOs\BaseDto;

/**
 * DTO for sending list messages.
 */
final class SendListMessageDto extends BaseDto
{
    /**
     * @param array<array{title: string, rows: array<array{title: string, description?: string, rowId: string}>}> $sections
     */
    public function __construct(
        public readonly string $number,
        public readonly string $title,
        public readonly string $description,
        public readonly string $buttonText,
        public readonly string $footerText,
        public readonly array $sections,
        public readonly ?int $delay = null,
        public readonly ?array $quoted = null,
    ) {
        $this->validateRequired(['number', 'title', 'buttonText', 'sections']);
    }

    /**
     * Create a list message with builder pattern.
     */
    public static function create(string $number, string $title): ListMessageBuilder
    {
        return new ListMessageBuilder($number, $title);
    }

    /**
     * {@inheritdoc}
     */
    public function toApiPayload(): array
    {
        $payload = [
            'number' => $this->number,
            'title' => $this->title,
            'description' => $this->description,
            'buttonText' => $this->buttonText,
            'footerText' => $this->footerText,
            'sections' => $this->sections,
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

/**
 * Builder for list messages.
 */
final class ListMessageBuilder
{
    private string $description = '';
    private string $buttonText = 'Options';
    private string $footerText = '';
    /** @var array<array{title: string, rows: array<array{title: string, description?: string, rowId: string}>}> */
    private array $sections = [];
    private ?int $delay = null;

    public function __construct(
        private readonly string $number,
        private readonly string $title,
    ) {}

    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function buttonText(string $buttonText): self
    {
        $this->buttonText = $buttonText;
        return $this;
    }

    public function footerText(string $footerText): self
    {
        $this->footerText = $footerText;
        return $this;
    }

    /**
     * Add a section with rows.
     *
     * @param array<array{title: string, description?: string, rowId: string}> $rows
     */
    public function addSection(string $title, array $rows): self
    {
        $this->sections[] = [
            'title' => $title,
            'rows' => $rows,
        ];
        return $this;
    }

    /**
     * Add a single row to the last section.
     */
    public function addRow(string $title, string $rowId, ?string $description = null): self
    {
        if (empty($this->sections)) {
            $this->sections[] = ['title' => '', 'rows' => []];
        }

        $lastIndex = count($this->sections) - 1;
        $row = ['title' => $title, 'rowId' => $rowId];

        if ($description !== null) {
            $row['description'] = $description;
        }

        $this->sections[$lastIndex]['rows'][] = $row;
        return $this;
    }

    public function delay(int $milliseconds): self
    {
        $this->delay = $milliseconds;
        return $this;
    }

    public function build(): SendListMessageDto
    {
        return new SendListMessageDto(
            number: $this->number,
            title: $this->title,
            description: $this->description,
            buttonText: $this->buttonText,
            footerText: $this->footerText,
            sections: $this->sections,
            delay: $this->delay,
        );
    }
}
