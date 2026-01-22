<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs\Message;

use Lynkbyte\EvolutionApi\DTOs\BaseDto;

/**
 * DTO for sending template messages (WhatsApp Business API).
 */
final class SendTemplateMessageDto extends BaseDto
{
    /**
     * @param  array<array{type: string, text?: string, currency?: array, date_time?: array}>  $components
     */
    public function __construct(
        public readonly string $number,
        public readonly string $name,
        public readonly string $language,
        public readonly ?array $components = null,
    ) {
        $this->validateRequired(['number', 'name', 'language']);
    }

    /**
     * Create a template message.
     */
    public static function create(string $number, string $templateName, string $language = 'en'): self
    {
        return new self(number: $number, name: $templateName, language: $language);
    }

    /**
     * Add header component.
     *
     * @param  array<array{type: string, text?: string, image?: array, video?: array, document?: array}>  $parameters
     */
    public function withHeader(array $parameters): self
    {
        $components = $this->components ?? [];
        $components[] = [
            'type' => 'header',
            'parameters' => $parameters,
        ];

        return new self(
            number: $this->number,
            name: $this->name,
            language: $this->language,
            components: $components,
        );
    }

    /**
     * Add body component.
     *
     * @param  array<array{type: string, text?: string}>  $parameters
     */
    public function withBody(array $parameters): self
    {
        $components = $this->components ?? [];
        $components[] = [
            'type' => 'body',
            'parameters' => $parameters,
        ];

        return new self(
            number: $this->number,
            name: $this->name,
            language: $this->language,
            components: $components,
        );
    }

    /**
     * Add button component.
     *
     * @param  array<array{type: string, sub_type: string, index: int, parameters: array}>  $buttons
     */
    public function withButtons(array $buttons): self
    {
        $components = $this->components ?? [];

        foreach ($buttons as $button) {
            $components[] = array_merge(['type' => 'button'], $button);
        }

        return new self(
            number: $this->number,
            name: $this->name,
            language: $this->language,
            components: $components,
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
            'language' => $this->language,
        ];

        if ($this->components !== null) {
            $payload['components'] = $this->components;
        }

        return $payload;
    }
}
