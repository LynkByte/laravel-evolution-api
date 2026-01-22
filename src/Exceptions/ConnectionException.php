<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Exceptions;

/**
 * Exception thrown when connection to Evolution API fails.
 */
class ConnectionException extends EvolutionApiException
{
    /**
     * The connection name that failed.
     */
    protected ?string $connectionName = null;

    /**
     * The URL that was being accessed.
     */
    protected ?string $url = null;

    /**
     * Create a new connection exception.
     */
    public function __construct(
        string $message = 'Connection failed',
        ?string $connectionName = null,
        ?string $url = null,
        ?string $instanceName = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            message: $message,
            code: 0,
            previous: $previous,
            instanceName: $instanceName
        );

        $this->connectionName = $connectionName;
        $this->url = $url;
    }

    /**
     * Get the connection name.
     */
    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    /**
     * Get the URL.
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Create exception for connection timeout.
     */
    public static function timeout(string $url, int $timeout, ?string $connectionName = null): static
    {
        return new static(
            message: "Connection to {$url} timed out after {$timeout} seconds",
            connectionName: $connectionName,
            url: $url
        );
    }

    /**
     * Create exception for connection refused.
     */
    public static function refused(string $url, ?string $connectionName = null): static
    {
        return new static(
            message: "Connection to {$url} was refused",
            connectionName: $connectionName,
            url: $url
        );
    }

    /**
     * Create exception for DNS resolution failure.
     */
    public static function dnsFailure(string $url, ?string $connectionName = null): static
    {
        return new static(
            message: "Could not resolve host for {$url}",
            connectionName: $connectionName,
            url: $url
        );
    }

    /**
     * Create exception for SSL certificate error.
     */
    public static function sslError(string $url, string $error, ?string $connectionName = null): static
    {
        return new static(
            message: "SSL certificate error for {$url}: {$error}",
            connectionName: $connectionName,
            url: $url
        );
    }

    /**
     * Create exception for unreachable server.
     */
    public static function unreachable(string $url, ?string $connectionName = null): static
    {
        return new static(
            message: "Evolution API server at {$url} is unreachable",
            connectionName: $connectionName,
            url: $url
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'connection_name' => $this->connectionName,
            'url' => $this->url,
        ]);
    }
}
