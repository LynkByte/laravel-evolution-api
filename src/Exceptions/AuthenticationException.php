<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Exceptions;

/**
 * Exception thrown when authentication fails.
 */
class AuthenticationException extends EvolutionApiException
{
    /**
     * Create a new authentication exception.
     */
    public static function invalidApiKey(?string $instanceName = null): static
    {
        return new static(
            message: 'Invalid API key provided',
            code: 401,
            statusCode: 401,
            instanceName: $instanceName
        );
    }

    /**
     * Create exception for missing API key.
     */
    public static function missingApiKey(): static
    {
        return new static(
            message: 'API key is required but not configured',
            code: 401,
            statusCode: 401
        );
    }

    /**
     * Create exception for expired API key.
     */
    public static function expiredApiKey(?string $instanceName = null): static
    {
        return new static(
            message: 'API key has expired',
            code: 401,
            statusCode: 401,
            instanceName: $instanceName
        );
    }
}
