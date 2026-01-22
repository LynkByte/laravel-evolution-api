<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Client;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Contracts\EvolutionClientInterface;
use Lynkbyte\EvolutionApi\Contracts\RateLimiterInterface;
use Lynkbyte\EvolutionApi\DTOs\ApiResponse;
use Lynkbyte\EvolutionApi\Exceptions\AuthenticationException;
use Lynkbyte\EvolutionApi\Exceptions\ConnectionException;
use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;
use Lynkbyte\EvolutionApi\Exceptions\InstanceNotFoundException;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;
use Psr\Log\LoggerInterface;

/**
 * Main HTTP client for Evolution API.
 */
class EvolutionClient implements EvolutionClientInterface
{
    /**
     * The current instance name for requests.
     */
    protected ?string $instanceName = null;

    /**
     * Custom headers for the next request.
     *
     * @var array<string, string>
     */
    protected array $pendingHeaders = [];

    /**
     * Whether to throw exceptions on error responses.
     */
    protected bool $throwOnError = true;

    /**
     * Create a new Evolution API client.
     */
    public function __construct(
        protected ConnectionManager $connectionManager,
        protected ?RateLimiterInterface $rateLimiter = null,
        protected ?LoggerInterface $logger = null
    ) {}

    /**
     * {@inheritdoc}
     */
    public function connection(string $name): self
    {
        $this->connectionManager->setActiveConnection($name);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function instance(string $instanceName): self
    {
        $this->instanceName = $instanceName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $endpoint, array $query = []): ApiResponse
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    /**
     * {@inheritdoc}
     */
    public function post(string $endpoint, array $data = []): ApiResponse
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $endpoint, array $data = []): ApiResponse
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $endpoint, array $data = []): ApiResponse
    {
        return $this->request('DELETE', $endpoint, ['json' => $data]);
    }

    /**
     * {@inheritdoc}
     */
    public function patch(string $endpoint, array $data = []): ApiResponse
    {
        return $this->request('PATCH', $endpoint, ['json' => $data]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionName(): string
    {
        return $this->connectionManager->getActiveConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function getInstanceName(): ?string
    {
        return $this->instanceName;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseUrl(): string
    {
        return $this->connectionManager->getServerUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function ping(): bool
    {
        try {
            $response = $this->get('/');

            return $response->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function info(): array
    {
        $response = $this->get('/');

        return $response->getData();
    }

    /**
     * Set whether to throw exceptions on error responses.
     */
    public function throwOnError(bool $throw = true): self
    {
        $this->throwOnError = $throw;

        return $this;
    }

    /**
     * Don't throw exceptions on error responses.
     */
    public function withoutThrowing(): self
    {
        return $this->throwOnError(false);
    }

    /**
     * Add custom headers for the next request.
     *
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        $this->pendingHeaders = array_merge($this->pendingHeaders, $headers);

        return $this;
    }

    /**
     * Make an HTTP request to the Evolution API.
     *
     * @param array<string, mixed> $options
     *
     * @throws EvolutionApiException
     */
    protected function request(string $method, string $endpoint, array $options = []): ApiResponse
    {
        $startTime = microtime(true);

        // Apply rate limiting
        $this->applyRateLimit($method, $endpoint);

        // Build the full URL
        $url = $this->buildUrl($endpoint);

        // Log the request
        $this->logRequest($method, $url, $options);

        try {
            // Create and configure the HTTP client
            $client = $this->createHttpClient();

            // Execute the request
            $response = match ($method) {
                'GET' => $client->get($url, $options['query'] ?? []),
                'POST' => $client->post($url, $options['json'] ?? []),
                'PUT' => $client->put($url, $options['json'] ?? []),
                'DELETE' => $client->delete($url, $options['json'] ?? []),
                'PATCH' => $client->patch($url, $options['json'] ?? []),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            $responseTime = (microtime(true) - $startTime) * 1000;

            // Convert to ApiResponse
            $apiResponse = $this->createApiResponse($response, $responseTime);

            // Log the response
            $this->logResponse($method, $url, $apiResponse);

            // Clear pending state
            $this->clearPendingState();

            // Handle error responses
            if ($apiResponse->isFailed() && $this->throwOnError) {
                $this->handleErrorResponse($apiResponse);
            }

            return $apiResponse;
        } catch (RequestException $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;

            $this->clearPendingState();

            throw $this->convertRequestException($e, $responseTime);
        } catch (\Throwable $e) {
            $this->clearPendingState();

            if ($e instanceof EvolutionApiException) {
                throw $e;
            }

            throw new ConnectionException(
                message: "Failed to connect to Evolution API: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Create and configure the HTTP client.
     */
    protected function createHttpClient(): PendingRequest
    {
        $config = $this->connectionManager->getConfig();
        $httpConfig = $config['http'] ?? [];

        $client = Http::baseUrl($this->getBaseUrl())
            ->timeout($httpConfig['timeout'] ?? 30)
            ->connectTimeout($httpConfig['connect_timeout'] ?? 10)
            ->withHeaders($this->getDefaultHeaders())
            ->withHeaders($this->pendingHeaders);

        // Configure SSL verification
        if (isset($httpConfig['verify_ssl']) && ! $httpConfig['verify_ssl']) {
            $client->withoutVerifying();
        }

        // Configure retries
        if (($config['retry']['enabled'] ?? true)) {
            $retryConfig = $config['retry'] ?? [];
            $client->retry(
                times: $retryConfig['max_attempts'] ?? 3,
                sleepMilliseconds: $retryConfig['base_delay'] ?? 1000,
                when: fn ($exception, $request) => $this->shouldRetry($exception),
                throw: false
            );
        }

        return $client;
    }

    /**
     * Get default headers for all requests.
     *
     * @return array<string, string>
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'apikey' => $this->connectionManager->getApiKey(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Build the full URL for an endpoint.
     */
    protected function buildUrl(string $endpoint): string
    {
        // Clean up the endpoint
        $endpoint = ltrim($endpoint, '/');

        // If endpoint contains {instance} placeholder, replace it
        if (str_contains($endpoint, '{instance}')) {
            if ($this->instanceName === null) {
                throw new InstanceNotFoundException(
                    'Instance name is required for this endpoint. Use ->instance($name) first.'
                );
            }
            $endpoint = str_replace('{instance}', $this->instanceName, $endpoint);
        }

        return $endpoint;
    }

    /**
     * Apply rate limiting to the request.
     *
     * @throws RateLimitException
     */
    protected function applyRateLimit(string $method, string $endpoint): void
    {
        if ($this->rateLimiter === null) {
            return;
        }

        // Determine rate limit type based on endpoint
        $type = $this->getRateLimitType($method, $endpoint);

        // Build rate limit key (connection + instance + type)
        $key = $this->buildRateLimitKey();

        // Attempt to acquire a rate limit slot
        $this->rateLimiter->attempt($key, $type);
    }

    /**
     * Get the rate limit type for an endpoint.
     */
    protected function getRateLimitType(string $method, string $endpoint): string
    {
        // Media endpoints
        if (preg_match('/\/(sendMedia|sendImage|sendVideo|sendAudio|sendDocument|sendSticker)/i', $endpoint)) {
            return 'media';
        }

        // Message endpoints
        if (preg_match('/\/(send|message)/i', $endpoint)) {
            return 'messages';
        }

        return 'default';
    }

    /**
     * Build the rate limit key.
     */
    protected function buildRateLimitKey(): string
    {
        $parts = [$this->connectionManager->getActiveConnection()];

        if ($this->instanceName !== null) {
            $parts[] = $this->instanceName;
        }

        return implode(':', $parts);
    }

    /**
     * Create an ApiResponse from an HTTP response.
     */
    protected function createApiResponse(Response $response, float $responseTime): ApiResponse
    {
        $data = [];

        try {
            $data = $response->json() ?? [];
        } catch (\Throwable) {
            // Response might not be JSON
        }

        $isSuccess = $response->successful();

        // Check for API-level errors in response body
        if ($isSuccess && isset($data['error'])) {
            $isSuccess = false;
        }

        // Extract message, ensuring it's a string
        $message = null;
        if (isset($data['message'])) {
            $message = is_string($data['message']) ? $data['message'] : json_encode($data['message']);
        } elseif (!$isSuccess) {
            $message = $response->reason();
        }

        return new ApiResponse(
            success: $isSuccess,
            statusCode: $response->status(),
            data: is_array($data) ? $data : ['raw' => $data],
            message: $message,
            headers: $response->headers(),
            responseTime: $responseTime
        );
    }

    /**
     * Handle error responses.
     *
     * @throws EvolutionApiException
     */
    protected function handleErrorResponse(ApiResponse $response): void
    {
        $statusCode = $response->statusCode;
        $message = $response->message ?? 'Unknown error';
        $data = $response->getData();

        throw match ($statusCode) {
            401 => new AuthenticationException(
                $message,
                $statusCode,
                null,
                $data,
                $statusCode,
                $this->instanceName
            ),
            404 => new InstanceNotFoundException(
                message: $message,
                instanceName: $this->instanceName
            ),
            429 => new RateLimitException(
                message: $message,
                retryAfter: (int) ($response->headers['Retry-After'][0] ?? 60),
                instanceName: $this->instanceName
            ),
            default => EvolutionApiException::fromResponse(
                $data,
                $statusCode,
                $this->instanceName
            ),
        };
    }

    /**
     * Convert a request exception to an EvolutionApiException.
     */
    protected function convertRequestException(RequestException $exception, float $responseTime): EvolutionApiException
    {
        $response = $exception->response;

        if ($response) {
            $apiResponse = $this->createApiResponse($response, $responseTime);

            return match ($response->status()) {
                401 => new AuthenticationException(
                    $apiResponse->message ?? 'Authentication failed',
                    401,
                    null,
                    $apiResponse->getData(),
                    401,
                    $this->instanceName
                ),
                404 => new InstanceNotFoundException(
                    message: $apiResponse->message ?? 'Resource not found',
                    instanceName: $this->instanceName
                ),
                429 => new RateLimitException(
                    message: $apiResponse->message ?? 'Rate limit exceeded',
                    retryAfter: (int) ($response->header('Retry-After') ?? 60),
                    instanceName: $this->instanceName
                ),
                default => EvolutionApiException::fromResponse(
                    $apiResponse->getData(),
                    $response->status(),
                    $this->instanceName
                ),
            };
        }

        return new ConnectionException(
            message: "Request failed: {$exception->getMessage()}",
            previous: $exception
        );
    }

    /**
     * Determine if a request should be retried.
     */
    protected function shouldRetry(\Throwable $exception): bool
    {
        $config = $this->connectionManager->getConfig();
        $retryConfig = $config['retry'] ?? [];
        $retryableStatusCodes = $retryConfig['retryable_status_codes'] ?? [408, 429, 500, 502, 503, 504];

        if ($exception instanceof RequestException && $exception->response) {
            return in_array($exception->response->status(), $retryableStatusCodes);
        }

        // Retry connection errors
        return $exception instanceof \Illuminate\Http\Client\ConnectionException;
    }

    /**
     * Clear pending request state.
     */
    protected function clearPendingState(): void
    {
        $this->pendingHeaders = [];
        // Note: We don't clear instanceName as it might be set for multiple requests
        // Use a fresh client or call instance() again to change it
    }

    /**
     * Log an outgoing request.
     *
     * @param array<string, mixed> $options
     */
    protected function logRequest(string $method, string $url, array $options): void
    {
        if ($this->logger === null) {
            return;
        }

        $config = $this->connectionManager->getConfig();

        if (! ($config['logging']['log_requests'] ?? true)) {
            return;
        }

        $logData = [
            'method' => $method,
            'url' => $url,
            'connection' => $this->connectionManager->getActiveConnection(),
            'instance' => $this->instanceName,
        ];

        // Redact sensitive data
        if ($config['logging']['redact_sensitive'] ?? true) {
            $logData['options'] = $this->redactSensitive($options, $config['logging']['sensitive_fields'] ?? []);
        } else {
            $logData['options'] = $options;
        }

        $this->logger->info('Evolution API Request', $logData);
    }

    /**
     * Log a response.
     */
    protected function logResponse(string $method, string $url, ApiResponse $response): void
    {
        if ($this->logger === null) {
            return;
        }

        $config = $this->connectionManager->getConfig();

        if (! ($config['logging']['log_responses'] ?? true)) {
            return;
        }

        $logData = [
            'method' => $method,
            'url' => $url,
            'status_code' => $response->statusCode,
            'success' => $response->success,
            'response_time_ms' => $response->responseTime,
        ];

        $logLevel = $response->success ? 'info' : 'error';

        $this->logger->{$logLevel}('Evolution API Response', $logData);
    }

    /**
     * Redact sensitive fields from data.
     *
     * @param array<string, mixed> $data
     * @param array<string> $sensitiveFields
     *
     * @return array<string, mixed>
     */
    protected function redactSensitive(array $data, array $sensitiveFields): array
    {
        $redacted = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), array_map('strtolower', $sensitiveFields))) {
                $redacted[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redactSensitive($value, $sensitiveFields);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }

    /**
     * Get the connection manager.
     */
    public function getConnectionManager(): ConnectionManager
    {
        return $this->connectionManager;
    }

    /**
     * Get the rate limiter.
     */
    public function getRateLimiter(): ?RateLimiterInterface
    {
        return $this->rateLimiter;
    }

    /**
     * Set the rate limiter.
     */
    public function setRateLimiter(?RateLimiterInterface $rateLimiter): self
    {
        $this->rateLimiter = $rateLimiter;

        return $this;
    }

    /**
     * Set the logger.
     */
    public function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Clear the current instance.
     */
    public function clearInstance(): self
    {
        $this->instanceName = null;

        return $this;
    }

    /**
     * Upload a file via multipart form.
     *
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $files Array of ['name' => 'field_name', 'contents' => $contents, 'filename' => 'name.ext']
     */
    public function upload(string $endpoint, array $fields = [], array $files = []): ApiResponse
    {
        $startTime = microtime(true);

        $this->applyRateLimit('POST', $endpoint);

        $url = $this->buildUrl($endpoint);

        $this->logRequest('POST', $url, ['fields' => $fields, 'files' => count($files) . ' files']);

        try {
            $client = $this->createHttpClient()
                ->asMultipart();

            // Add fields
            foreach ($fields as $name => $value) {
                $client->attach($name, $value);
            }

            // Add files
            foreach ($files as $file) {
                $client->attach(
                    $file['name'],
                    $file['contents'],
                    $file['filename'] ?? null
                );
            }

            $response = $client->post($url);

            $responseTime = (microtime(true) - $startTime) * 1000;
            $apiResponse = $this->createApiResponse($response, $responseTime);

            $this->logResponse('POST', $url, $apiResponse);
            $this->clearPendingState();

            if ($apiResponse->isFailed() && $this->throwOnError) {
                $this->handleErrorResponse($apiResponse);
            }

            return $apiResponse;
        } catch (RequestException $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $this->clearPendingState();

            throw $this->convertRequestException($e, $responseTime);
        } catch (\Throwable $e) {
            $this->clearPendingState();

            if ($e instanceof EvolutionApiException) {
                throw $e;
            }

            throw new ConnectionException(
                message: "Upload failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }
}
