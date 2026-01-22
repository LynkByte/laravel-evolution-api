<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Contracts;

use Lynkbyte\EvolutionApi\DTOs\ApiResponse;

/**
 * Interface for the Evolution API HTTP client.
 */
interface EvolutionClientInterface
{
    /**
     * Set the connection to use for subsequent requests.
     */
    public function connection(string $name): self;

    /**
     * Set the instance to use for subsequent requests.
     */
    public function instance(string $instanceName): self;

    /**
     * Send a GET request to the API.
     *
     * @param array<string, mixed> $query
     */
    public function get(string $endpoint, array $query = []): ApiResponse;

    /**
     * Send a POST request to the API.
     *
     * @param array<string, mixed> $data
     */
    public function post(string $endpoint, array $data = []): ApiResponse;

    /**
     * Send a PUT request to the API.
     *
     * @param array<string, mixed> $data
     */
    public function put(string $endpoint, array $data = []): ApiResponse;

    /**
     * Send a DELETE request to the API.
     *
     * @param array<string, mixed> $data
     */
    public function delete(string $endpoint, array $data = []): ApiResponse;

    /**
     * Send a PATCH request to the API.
     *
     * @param array<string, mixed> $data
     */
    public function patch(string $endpoint, array $data = []): ApiResponse;

    /**
     * Get the current connection name.
     */
    public function getConnectionName(): string;

    /**
     * Get the current instance name.
     */
    public function getInstanceName(): ?string;

    /**
     * Get the base URL for the current connection.
     */
    public function getBaseUrl(): string;

    /**
     * Check if the API is reachable.
     */
    public function ping(): bool;

    /**
     * Get API information.
     *
     * @return array<string, mixed>
     */
    public function info(): array;
}
