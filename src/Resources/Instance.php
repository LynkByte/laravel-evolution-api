<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Resources;

use Lynkbyte\EvolutionApi\DTOs\ApiResponse;
use Lynkbyte\EvolutionApi\Enums\InstanceStatus;

/**
 * Instance management resource for Evolution API.
 *
 * @see https://doc.evolution-api.com/v1/en/api-reference/instance
 */
class Instance extends Resource
{
    /**
     * Create a new WhatsApp instance.
     *
     * @param array<string, mixed> $options Additional options
     */
    public function create(
        string $instanceName,
        ?string $token = null,
        ?int $qrcode = null,
        ?bool $integration = null,
        ?string $number = null,
        ?string $businessId = null,
        array $options = []
    ): ApiResponse {
        $data = $this->filterNull([
            'instanceName' => $instanceName,
            'token' => $token,
            'qrcode' => $qrcode,
            'integration' => $integration,
            'number' => $number,
            'businessId' => $businessId,
            ...$options,
        ]);

        return $this->post('instance/create', $data);
    }

    /**
     * Connect to an instance (get QR code or connect).
     */
    public function connect(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->get("instance/connect/{instance}");
    }

    /**
     * Get the connection state of an instance.
     */
    public function connectionState(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->get("instance/connectionState/{instance}");
    }

    /**
     * Fetch all instances.
     */
    public function fetchAll(): ApiResponse
    {
        return $this->get('instance/fetchInstances');
    }

    /**
     * Fetch a specific instance by name.
     */
    public function fetch(string $instanceName): ApiResponse
    {
        $this->client->instance($instanceName);

        return $this->get("instance/fetchInstances", ['instanceName' => $instanceName]);
    }

    /**
     * Set presence status (online/offline/composing/recording).
     */
    public function setPresence(string $presence, ?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->post("instance/setPresence/{instance}", [
            'presence' => $presence,
        ]);
    }

    /**
     * Restart an instance.
     */
    public function restart(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->put("instance/restart/{instance}");
    }

    /**
     * Logout from an instance (disconnect WhatsApp).
     */
    public function logout(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->delete("instance/logout/{instance}");
    }

    /**
     * Delete an instance completely.
     */
    public function remove(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->delete("instance/delete/{instance}");
    }

    /**
     * Get QR code for an instance.
     */
    public function getQrCode(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->get("instance/qrcode/{instance}");
    }

    /**
     * Get QR code as base64.
     */
    public function getQrCodeBase64(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->get("instance/qrcode-base64/{instance}");
    }

    /**
     * Check if instance is connected.
     */
    public function isConnected(?string $instanceName = null): bool
    {
        try {
            $response = $this->connectionState($instanceName);

            if (! $response->isSuccessful()) {
                return false;
            }

            $state = $response->get('state') ?? $response->get('instance', [])['state'] ?? null;

            return $state === 'open' || $state === InstanceStatus::CONNECTED->value;
        } catch (\Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException $e) {
            return false;
        }
    }

    /**
     * Wait for connection (poll until connected or timeout).
     *
     * @param int $timeout Timeout in seconds
     * @param int $interval Polling interval in seconds
     */
    public function waitForConnection(
        ?string $instanceName = null,
        int $timeout = 60,
        int $interval = 2
    ): bool {
        $startTime = time();

        while ((time() - $startTime) < $timeout) {
            if ($this->isConnected($instanceName)) {
                return true;
            }

            sleep($interval);
        }

        return false;
    }

    /**
     * Get instance status as enum.
     */
    public function getStatus(?string $instanceName = null): InstanceStatus
    {
        try {
            $response = $this->connectionState($instanceName);

            if (! $response->isSuccessful()) {
                return InstanceStatus::UNKNOWN;
            }

            $state = $response->get('state') ?? $response->get('instance', [])['state'] ?? 'unknown';

            return InstanceStatus::tryFrom($state) ?? InstanceStatus::UNKNOWN;
        } catch (\Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException $e) {
            return InstanceStatus::UNKNOWN;
        }
    }

    /**
     * Update instance settings.
     *
     * @param array<string, mixed> $settings
     */
    public function updateSettings(array $settings, ?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->put("instance/settings/{instance}", $settings);
    }

    /**
     * Get instance settings.
     */
    public function getSettings(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->get("instance/settings/{instance}");
    }

    /**
     * Refresh QR code for an instance.
     */
    public function refreshQrCode(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->post("instance/refreshQrCode/{instance}");
    }

    /**
     * Connect with phone number (for API instances).
     */
    public function connectWithNumber(
        string $phoneNumber,
        ?string $instanceName = null
    ): ApiResponse {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->post("instance/connect/{instance}", [
            'number' => $phoneNumber,
        ]);
    }

    /**
     * Verify connection code (for pairing code connection).
     */
    public function verifyCode(string $code, ?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->post("instance/verifyCode/{instance}", [
            'code' => $code,
        ]);
    }
}
