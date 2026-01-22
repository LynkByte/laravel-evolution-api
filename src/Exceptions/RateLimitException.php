<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Exceptions;

/**
 * Exception thrown when rate limit is exceeded.
 */
class RateLimitException extends EvolutionApiException
{
    /**
     * The number of seconds until the rate limit resets.
     */
    protected int $retryAfter;

    /**
     * The rate limit type that was exceeded.
     */
    protected string $limitType;

    /**
     * Create a new rate limit exception.
     */
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $retryAfter = 60,
        string $limitType = 'default',
        ?string $instanceName = null
    ) {
        parent::__construct(
            message: $message,
            code: 429,
            statusCode: 429,
            instanceName: $instanceName
        );

        $this->retryAfter = $retryAfter;
        $this->limitType = $limitType;
    }

    /**
     * Get the number of seconds until the rate limit resets.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Get the rate limit type.
     */
    public function getLimitType(): string
    {
        return $this->limitType;
    }

    /**
     * Create exception for API rate limit.
     */
    public static function apiLimitExceeded(int $retryAfter = 60, ?string $instanceName = null): static
    {
        return new static(
            message: "API rate limit exceeded. Retry after {$retryAfter} seconds.",
            retryAfter: $retryAfter,
            limitType: 'api',
            instanceName: $instanceName
        );
    }

    /**
     * Create exception for message rate limit.
     */
    public static function messageLimitExceeded(int $retryAfter = 60, ?string $instanceName = null): static
    {
        return new static(
            message: "Message rate limit exceeded. Retry after {$retryAfter} seconds.",
            retryAfter: $retryAfter,
            limitType: 'messages',
            instanceName: $instanceName
        );
    }

    /**
     * Create exception for media rate limit.
     */
    public static function mediaLimitExceeded(int $retryAfter = 60, ?string $instanceName = null): static
    {
        return new static(
            message: "Media upload rate limit exceeded. Retry after {$retryAfter} seconds.",
            retryAfter: $retryAfter,
            limitType: 'media',
            instanceName: $instanceName
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'retry_after' => $this->retryAfter,
            'limit_type' => $this->limitType,
        ]);
    }
}
