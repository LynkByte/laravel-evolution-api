<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for Evolution API errors.
 */
class EvolutionApiException extends Exception
{
    /**
     * The response data from the API.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $responseData = null;

    /**
     * The HTTP status code.
     */
    protected ?int $statusCode = null;

    /**
     * The instance name involved in the error.
     */
    protected ?string $instanceName = null;

    /**
     * Create a new exception instance.
     *
     * @param  array<string, mixed>|null  $responseData
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?array $responseData = null,
        ?int $statusCode = null,
        ?string $instanceName = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->responseData = $responseData;
        $this->statusCode = $statusCode;
        $this->instanceName = $instanceName;
    }

    /**
     * Get the response data.
     *
     * @return array<string, mixed>|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Get the instance name.
     */
    public function getInstanceName(): ?string
    {
        return $this->instanceName;
    }

    /**
     * Create an exception from an API response.
     *
     * @param  array<string, mixed>  $response
     */
    public static function fromResponse(array $response, int $statusCode, ?string $instanceName = null): static
    {
        // Evolution API returns errors in different formats:
        // 1. { "message": "Error message" }
        // 2. { "error": "Error message" }
        // 3. { "response": { "message": "Connection Closed" }, "error": "Internal Server Error" }
        // Prefer the nested response.message as it's more specific
        $message = $response['response']['message']
            ?? $response['message']
            ?? $response['error']
            ?? 'Unknown error';

        return new static(
            message: $message,
            code: $statusCode,
            responseData: $response,
            statusCode: $statusCode,
            instanceName: $instanceName
        );
    }

    /**
     * Get the exception as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'status_code' => $this->statusCode,
            'instance_name' => $this->instanceName,
            'response_data' => $this->responseData,
        ];
    }

    /**
     * Get the exception context for logging.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->toArray();
    }
}
