<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Exceptions;

/**
 * Exception thrown when instance is not found.
 */
class InstanceNotFoundException extends EvolutionApiException
{
    /**
     * Create a new instance not found exception.
     */
    public function __construct(
        ?string $message = null,
        ?string $instanceName = null
    ) {
        $message ??= $instanceName
            ? "Instance '{$instanceName}' not found"
            : 'Instance not found';

        parent::__construct(
            message: $message,
            code: 404,
            statusCode: 404,
            instanceName: $instanceName
        );
    }

    /**
     * Create exception for instance not found.
     */
    public static function notFound(string $instanceName): static
    {
        return new static(instanceName: $instanceName);
    }
}
