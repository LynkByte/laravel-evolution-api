<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs\Message;

use Lynkbyte\EvolutionApi\DTOs\BaseDto;

/**
 * DTO for sending location messages.
 */
final class SendLocationMessageDto extends BaseDto
{
    public function __construct(
        public readonly string $number,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?string $name = null,
        public readonly ?string $address = null,
        public readonly ?int $delay = null,
        public readonly ?array $quoted = null,
    ) {
        $this->validateRequired(['number']);
    }

    /**
     * Create a location message.
     */
    public static function to(string $number, float $latitude, float $longitude): self
    {
        return new self(number: $number, latitude: $latitude, longitude: $longitude);
    }

    /**
     * Add location name.
     */
    public function withName(string $name): self
    {
        return new self(
            number: $this->number,
            latitude: $this->latitude,
            longitude: $this->longitude,
            name: $name,
            address: $this->address,
            delay: $this->delay,
            quoted: $this->quoted,
        );
    }

    /**
     * Add address.
     */
    public function withAddress(string $address): self
    {
        return new self(
            number: $this->number,
            latitude: $this->latitude,
            longitude: $this->longitude,
            name: $this->name,
            address: $address,
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
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];

        if ($this->name !== null) {
            $payload['name'] = $this->name;
        }

        if ($this->address !== null) {
            $payload['address'] = $this->address;
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
