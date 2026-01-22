<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs;

use JsonSerializable;

/**
 * Base Data Transfer Object class.
 */
abstract class BaseDto implements JsonSerializable
{
    /**
     * Create a new DTO instance.
     *
     * @param array<string, mixed> $data
     */
    public static function make(array $data = []): static
    {
        return new static(...$data);
    }

    /**
     * Create a DTO from an array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return static::make($data);
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value instanceof BaseDto ? $value->toArray() : $value;
            }
        }

        return $data;
    }

    /**
     * Convert to API payload format.
     *
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        return $this->toArray();
    }

    /**
     * JSON serialize the DTO.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get a property value with a default.
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->{$key} ?? $default;
    }

    /**
     * Validate required fields.
     *
     * @param array<string> $fields
     * @throws \InvalidArgumentException
     */
    protected function validateRequired(array $fields): void
    {
        foreach ($fields as $field) {
            if (empty($this->{$field})) {
                throw new \InvalidArgumentException("The {$field} field is required.");
            }
        }
    }
}
