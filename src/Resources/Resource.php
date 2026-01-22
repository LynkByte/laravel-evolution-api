<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Resources;

use Lynkbyte\EvolutionApi\Client\EvolutionClient;
use Lynkbyte\EvolutionApi\DTOs\ApiResponse;

/**
 * Base class for Evolution API resources.
 */
abstract class Resource
{
    /**
     * Create a new resource instance.
     */
    public function __construct(
        protected EvolutionClient $client
    ) {}

    /**
     * Get the current instance name from the client.
     */
    protected function getInstanceName(): ?string
    {
        return $this->client->getInstanceName();
    }

    /**
     * Make a GET request.
     *
     * @param array<string, mixed> $query
     */
    protected function get(string $endpoint, array $query = []): ApiResponse
    {
        return $this->client->get($endpoint, $query);
    }

    /**
     * Make a POST request.
     *
     * @param array<string, mixed> $data
     */
    protected function post(string $endpoint, array $data = []): ApiResponse
    {
        return $this->client->post($endpoint, $data);
    }

    /**
     * Make a PUT request.
     *
     * @param array<string, mixed> $data
     */
    protected function put(string $endpoint, array $data = []): ApiResponse
    {
        return $this->client->put($endpoint, $data);
    }

    /**
     * Make a DELETE request.
     *
     * @param array<string, mixed> $data
     */
    protected function delete(string $endpoint, array $data = []): ApiResponse
    {
        return $this->client->delete($endpoint, $data);
    }

    /**
     * Make a PATCH request.
     *
     * @param array<string, mixed> $data
     */
    protected function patch(string $endpoint, array $data = []): ApiResponse
    {
        return $this->client->patch($endpoint, $data);
    }

    /**
     * Build an endpoint path with instance name placeholder.
     */
    protected function instanceEndpoint(string $path): string
    {
        return "{$path}/{instance}";
    }

    /**
     * Build a full endpoint path for instance-specific endpoints.
     */
    protected function buildInstancePath(string $basePath, string $suffix = ''): string
    {
        $path = $basePath . '/{instance}';

        if ($suffix) {
            $path .= '/' . ltrim($suffix, '/');
        }

        return $path;
    }

    /**
     * Get the underlying client.
     */
    public function getClient(): EvolutionClient
    {
        return $this->client;
    }

    /**
     * Set the instance for subsequent requests.
     *
     * @return $this
     */
    public function instance(string $instanceName): static
    {
        $this->client->instance($instanceName);

        return $this;
    }

    /**
     * Filter out null values from an array.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function filterNull(array $data): array
    {
        return array_filter($data, fn ($value) => $value !== null);
    }

    /**
     * Build options array from DTO.
     *
     * @param array<string, mixed> $data
     * @param array<string> $optionalFields
     *
     * @return array<string, mixed>
     */
    protected function buildPayload(array $data, array $optionalFields = []): array
    {
        $payload = $this->filterNull($data);

        // Ensure optional fields with false values are included
        foreach ($optionalFields as $field) {
            if (isset($data[$field]) && $data[$field] === false) {
                $payload[$field] = false;
            }
        }

        return $payload;
    }

    /**
     * Format a phone number for WhatsApp API.
     *
     * Removes any non-numeric characters and ensures proper format.
     */
    protected function formatPhoneNumber(string $number): string
    {
        // Remove all non-numeric characters
        $formatted = preg_replace('/[^0-9]/', '', $number);

        // Ensure it ends with @s.whatsapp.net if needed
        if (! str_contains($number, '@')) {
            return $formatted;
        }

        return $number;
    }

    /**
     * Format remote JID (phone number with WhatsApp suffix).
     */
    protected function formatRemoteJid(string $number): string
    {
        // If already formatted, return as-is
        if (str_contains($number, '@')) {
            return $number;
        }

        // Clean the number
        $cleaned = preg_replace('/[^0-9]/', '', $number);

        // Add WhatsApp suffix
        return $cleaned . '@s.whatsapp.net';
    }

    /**
     * Format group JID.
     */
    protected function formatGroupJid(string $groupId): string
    {
        // If already formatted, return as-is
        if (str_contains($groupId, '@')) {
            return $groupId;
        }

        return $groupId . '@g.us';
    }

    /**
     * Ensure instance is set.
     *
     * @throws \Lynkbyte\EvolutionApi\Exceptions\InstanceNotFoundException
     */
    protected function ensureInstance(): void
    {
        if ($this->client->getInstanceName() === null) {
            throw new \Lynkbyte\EvolutionApi\Exceptions\InstanceNotFoundException(
                'No instance selected. Use ->instance($name) before making this request.'
            );
        }
    }
}
