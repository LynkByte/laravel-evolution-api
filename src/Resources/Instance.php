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
     * @param  string  $integration  Integration type (default: WHATSAPP-BAILEYS)
     * @param  array<string, mixed>  $options  Additional options
     */
    public function create(
        string $instanceName,
        ?string $token = null,
        ?int $qrcode = null,
        string $integration = 'WHATSAPP-BAILEYS',
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

        return $this->get('instance/connect/{instance}');
    }

    /**
     * Get the connection state of an instance.
     */
    public function connectionState(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->get('instance/connectionState/{instance}');
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

        return $this->get('instance/fetchInstances', ['instanceName' => $instanceName]);
    }

    /**
     * Set presence status (online/offline/composing/recording).
     */
    public function setPresence(string $presence, ?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->post('instance/setPresence/{instance}', [
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

        return $this->put('instance/restart/{instance}');
    }

    /**
     * Logout from an instance (disconnect WhatsApp).
     */
    public function logout(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->delete('instance/logout/{instance}');
    }

    /**
     * Delete an instance completely.
     */
    public function remove(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->delete('instance/delete/{instance}');
    }

    /**
     * Get QR code for an instance.
     */
    public function getQrCode(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->get('instance/qrcode/{instance}');
    }

    /**
     * Get QR code as base64.
     */
    public function getQrCodeBase64(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->get('instance/qrcode-base64/{instance}');
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
     * @param  int  $timeout  Timeout in seconds
     * @param  int  $interval  Polling interval in seconds
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
     * @param  array<string, mixed>  $settings
     */
    public function updateSettings(array $settings, ?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->put('instance/settings/{instance}', $settings);
    }

    /**
     * Get instance settings.
     */
    public function getSettings(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->get('instance/settings/{instance}');
    }

    /**
     * Refresh QR code for an instance.
     */
    public function refreshQrCode(?string $instanceName = null): ApiResponse
    {
        $instance = $instanceName ?? $this->getInstanceName();
        $this->client->instance($instance);

        return $this->post('instance/refreshQrCode/{instance}');
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

        return $this->post('instance/connect/{instance}', [
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

        return $this->post('instance/verifyCode/{instance}', [
            'code' => $code,
        ]);
    }

    /**
     * Check if instance is ready to send messages.
     *
     * This method performs a more thorough check than isConnected() by:
     * 1. Verifying the connection state is "open"
     * 2. Optionally waiting for connection stabilization
     *
     * Note: Even if this returns true, messages may still fail due to
     * Evolution API's "pre-key upload timeout" issues. This is an upstream
     * issue in the Baileys library, not a Laravel package issue.
     *
     * @param  string|null  $instanceName  Instance name (uses current if null)
     * @param  bool  $waitForStabilization  Wait for connection to stabilize
     * @return bool True if ready to send, false otherwise
     */
    public function isReadyToSend(?string $instanceName = null, bool $waitForStabilization = false): bool
    {
        try {
            $response = $this->connectionState($instanceName);

            if (! $response->isSuccessful()) {
                return false;
            }

            $data = $response->getData();
            $state = $data['state'] ?? $data['instance']['state'] ?? 'unknown';

            if ($state !== 'open') {
                return false;
            }

            // Optionally wait for connection to stabilize
            if ($waitForStabilization) {
                $config = $this->client->getConnectionManager()->getConfig();
                $delay = $config['messages']['connection_stabilization_delay'] ?? 5;

                if ($delay > 0) {
                    sleep($delay);

                    // Re-check after waiting
                    $response = $this->connectionState($instanceName);
                    if (! $response->isSuccessful()) {
                        return false;
                    }

                    $data = $response->getData();
                    $state = $data['state'] ?? $data['instance']['state'] ?? 'unknown';

                    return $state === 'open';
                }
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get detailed connection info for diagnostics.
     *
     * Returns information useful for debugging connection issues,
     * including the current state and any available error information.
     *
     * @param  string|null  $instanceName  Instance name (uses current if null)
     * @return array{connected: bool, state: string, ready_to_send: bool, instance_name: string|null, details: array<string, mixed>}
     */
    public function getConnectionDiagnostics(?string $instanceName = null): array
    {
        $instance = $instanceName ?? $this->getInstanceName();

        try {
            $response = $this->connectionState($instance);

            if (! $response->isSuccessful()) {
                return [
                    'connected' => false,
                    'state' => 'error',
                    'ready_to_send' => false,
                    'instance_name' => $instance,
                    'details' => [
                        'error' => $response->message ?? 'Failed to get connection state',
                        'status_code' => $response->statusCode,
                    ],
                ];
            }

            $data = $response->getData();
            $state = $data['state'] ?? $data['instance']['state'] ?? 'unknown';

            return [
                'connected' => $state === 'open',
                'state' => $state,
                'ready_to_send' => $state === 'open',
                'instance_name' => $instance,
                'details' => $data,
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'state' => 'exception',
                'ready_to_send' => false,
                'instance_name' => $instance,
                'details' => [
                    'error' => $e->getMessage(),
                    'exception_class' => get_class($e),
                ],
            ];
        }
    }

    /**
     * Wait for instance to be ready to send messages.
     *
     * This method polls the connection state until either:
     * - The instance is connected and ready
     * - The timeout is reached
     *
     * @param  string|null  $instanceName  Instance name (uses current if null)
     * @param  int  $timeout  Maximum time to wait in seconds
     * @param  int  $interval  Time between checks in seconds
     * @param  bool  $stabilize  Wait extra time for connection to stabilize after connecting
     * @return bool True if ready to send within timeout, false otherwise
     */
    public function waitUntilReady(
        ?string $instanceName = null,
        int $timeout = 60,
        int $interval = 2,
        bool $stabilize = true
    ): bool {
        $startTime = time();

        while ((time() - $startTime) < $timeout) {
            if ($this->isReadyToSend($instanceName, $stabilize)) {
                return true;
            }

            sleep($interval);
        }

        return false;
    }
}
