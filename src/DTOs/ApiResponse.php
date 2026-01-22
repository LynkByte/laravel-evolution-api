<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs;

/**
 * API Response wrapper DTO.
 */
final class ApiResponse
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    public function __construct(
        public readonly bool $success,
        public readonly int $statusCode,
        public readonly array $data = [],
        public readonly ?string $message = null,
        public readonly array $headers = [],
        public readonly ?float $responseTime = null,
    ) {}

    /**
     * Create a successful response.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    public static function success(
        array $data = [],
        int $statusCode = 200,
        ?string $message = null,
        array $headers = [],
        ?float $responseTime = null
    ): self {
        return new self(
            success: true,
            statusCode: $statusCode,
            data: $data,
            message: $message,
            headers: $headers,
            responseTime: $responseTime
        );
    }

    /**
     * Create a failed response.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    public static function failure(
        string $message,
        int $statusCode = 400,
        array $data = [],
        array $headers = [],
        ?float $responseTime = null
    ): self {
        return new self(
            success: false,
            statusCode: $statusCode,
            data: $data,
            message: $message,
            headers: $headers,
            responseTime: $responseTime
        );
    }

    /**
     * Check if the response was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if the response failed.
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Get a specific data key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get all data.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status_code' => $this->statusCode,
            'data' => $this->data,
            'message' => $this->message,
            'response_time' => $this->responseTime,
        ];
    }

    /**
     * Throw an exception if the response failed.
     *
     * @throws \Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException
     */
    public function throw(): self
    {
        if ($this->isFailed()) {
            throw \Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException::fromResponse(
                $this->data,
                $this->statusCode
            );
        }

        return $this;
    }
}
