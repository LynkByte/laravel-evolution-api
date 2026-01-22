<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Client;

use Lynkbyte\EvolutionApi\Exceptions\ConnectionException;

/**
 * Manages multiple Evolution API connections for multi-tenancy support.
 */
class ConnectionManager
{
    /**
     * The active connection name.
     */
    protected string $activeConnection = 'default';

    /**
     * Resolved connection configurations.
     *
     * @var array<string, array{server_url: string, api_key: string}>
     */
    protected array $resolvedConnections = [];

    /**
     * Runtime connection configurations (added programmatically).
     *
     * @var array<string, array{server_url: string, api_key: string}>
     */
    protected array $runtimeConnections = [];

    /**
     * Create a new connection manager instance.
     *
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config
    ) {
        $this->resolveDefaultConnection();
    }

    /**
     * Get a connection configuration by name.
     *
     * @return array{server_url: string, api_key: string}
     *
     * @throws ConnectionException
     */
    public function connection(string $name = 'default'): array
    {
        if (isset($this->resolvedConnections[$name])) {
            return $this->resolvedConnections[$name];
        }

        if (isset($this->runtimeConnections[$name])) {
            return $this->runtimeConnections[$name];
        }

        return $this->resolveConnection($name);
    }

    /**
     * Set the active connection.
     */
    public function setActiveConnection(string $name): self
    {
        // Validate connection exists
        $this->connection($name);
        $this->activeConnection = $name;

        return $this;
    }

    /**
     * Get the active connection name.
     */
    public function getActiveConnection(): string
    {
        return $this->activeConnection;
    }

    /**
     * Get the active connection name (alias for getActiveConnection).
     */
    public function getActiveConnectionName(): string
    {
        return $this->activeConnection;
    }

    /**
     * Get the active connection configuration.
     *
     * @return array{server_url: string, api_key: string}
     */
    public function getActiveConnectionConfig(): array
    {
        return $this->connection($this->activeConnection);
    }

    /**
     * Add a connection at runtime.
     *
     * @param  array{server_url: string, api_key: string}  $config
     */
    public function addConnection(string $name, array $config): self
    {
        $this->validateConnectionConfig($config, $name);
        $this->runtimeConnections[$name] = $this->normalizeConnectionConfig($config);

        return $this;
    }

    /**
     * Remove a runtime connection.
     */
    public function removeConnection(string $name): self
    {
        unset($this->runtimeConnections[$name]);

        if ($this->activeConnection === $name) {
            $this->activeConnection = 'default';
        }

        return $this;
    }

    /**
     * Check if a connection exists.
     */
    public function hasConnection(string $name): bool
    {
        if (isset($this->resolvedConnections[$name]) || isset($this->runtimeConnections[$name])) {
            return true;
        }

        $connections = $this->config['connections'] ?? [];

        return isset($connections[$name]);
    }

    /**
     * Get all available connection names.
     *
     * @return array<string>
     */
    public function getAvailableConnections(): array
    {
        $configConnections = array_keys($this->config['connections'] ?? []);
        $runtimeConnections = array_keys($this->runtimeConnections);

        return array_unique([...$configConnections, ...$runtimeConnections]);
    }

    /**
     * Get the server URL for a connection.
     */
    public function getServerUrl(?string $name = null): string
    {
        $name ??= $this->activeConnection;

        return $this->connection($name)['server_url'];
    }

    /**
     * Get the API key for a connection.
     */
    public function getApiKey(?string $name = null): string
    {
        $name ??= $this->activeConnection;

        return $this->connection($name)['api_key'];
    }

    /**
     * Resolve a connection configuration from config.
     *
     * @return array{server_url: string, api_key: string}
     *
     * @throws ConnectionException
     */
    protected function resolveConnection(string $name): array
    {
        $connections = $this->config['connections'] ?? [];

        if (! isset($connections[$name])) {
            throw new ConnectionException(
                "Evolution API connection [{$name}] is not configured.",
                'CONNECTION_NOT_FOUND'
            );
        }

        $config = $connections[$name];
        $this->validateConnectionConfig($config, $name);

        $this->resolvedConnections[$name] = $this->normalizeConnectionConfig($config);

        return $this->resolvedConnections[$name];
    }

    /**
     * Resolve the default connection on instantiation.
     */
    protected function resolveDefaultConnection(): void
    {
        // Check for legacy single-connection config
        if (isset($this->config['server_url']) && isset($this->config['api_key'])) {
            $this->resolvedConnections['default'] = [
                'server_url' => $this->normalizeServerUrl($this->config['server_url']),
                'api_key' => $this->config['api_key'],
            ];

            return;
        }

        // Try to resolve default connection from connections array
        try {
            $this->resolveConnection('default');
        } catch (ConnectionException) {
            // Default connection may not exist, that's okay
        }
    }

    /**
     * Validate a connection configuration.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws ConnectionException
     */
    protected function validateConnectionConfig(array $config, string $name): void
    {
        if (empty($config['server_url'])) {
            throw new ConnectionException(
                "Evolution API connection [{$name}] is missing 'server_url'.",
                'MISSING_SERVER_URL'
            );
        }

        if (empty($config['api_key'])) {
            throw new ConnectionException(
                "Evolution API connection [{$name}] is missing 'api_key'.",
                'MISSING_API_KEY'
            );
        }

        if (! filter_var($config['server_url'], FILTER_VALIDATE_URL)) {
            throw new ConnectionException(
                "Evolution API connection [{$name}] has an invalid 'server_url'.",
                'INVALID_SERVER_URL'
            );
        }
    }

    /**
     * Normalize a connection configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array{server_url: string, api_key: string}
     */
    protected function normalizeConnectionConfig(array $config): array
    {
        return [
            'server_url' => $this->normalizeServerUrl($config['server_url']),
            'api_key' => $config['api_key'],
        ];
    }

    /**
     * Normalize a server URL (remove trailing slash).
     */
    protected function normalizeServerUrl(string $url): string
    {
        return rtrim($url, '/');
    }

    /**
     * Get the full configuration array.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get a configuration value by key using dot notation.
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Purge all resolved connections (useful for testing).
     */
    public function purge(): void
    {
        $this->resolvedConnections = [];
        $this->runtimeConnections = [];
        $this->activeConnection = 'default';
        $this->resolveDefaultConnection();
    }
}
