<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Logging;

use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Custom logger wrapper for Evolution API operations.
 * 
 * Provides centralized logging with support for:
 * - Configurable log levels
 * - Request/response logging
 * - Webhook event logging
 * - Sensitive data redaction
 */
class EvolutionApiLogger
{
    /**
     * The underlying logger instance.
     */
    protected LoggerInterface $logger;

    /**
     * Logging configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Default sensitive fields to redact.
     *
     * @var array<string>
     */
    protected array $sensitiveFields = [
        'apikey',
        'api_key',
        'token',
        'password',
        'secret',
        'authorization',
    ];

    /**
     * Create a new logger instance.
     *
     * @param LogManager $logManager
     * @param array<string, mixed> $config
     */
    public function __construct(LogManager $logManager, array $config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'channel' => null,
            'level' => LogLevel::INFO,
            'log_requests' => true,
            'log_responses' => true,
            'log_webhooks' => true,
            'redact_sensitive' => true,
            'sensitive_fields' => [],
            'debug' => false,  // Add debug option to config
        ], $config);

        // Use specified channel or default
        $this->logger = $this->config['channel']
            ? $logManager->channel($this->config['channel'])
            : $logManager->driver();

        // Merge custom sensitive fields
        if (!empty($this->config['sensitive_fields'])) {
            $this->sensitiveFields = array_merge(
                $this->sensitiveFields,
                $this->config['sensitive_fields']
            );
        }
    }

    /**
     * Log an API request.
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array<string, mixed> $options Request options
     */
    public function logRequest(string $method, string $url, array $options = []): void
    {
        if (!$this->shouldLog() || !$this->config['log_requests']) {
            return;
        }

        $context = [
            'method' => $method,
            'url' => $this->redactUrl($url),
            'options' => $this->redactSensitive($options),
        ];

        $this->info('Evolution API Request', $context);
    }

    /**
     * Log an API response.
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param int $statusCode HTTP status code
     * @param array<string, mixed>|string $body Response body
     * @param float|null $duration Request duration in seconds
     */
    public function logResponse(
        string $method,
        string $url,
        int $statusCode,
        array|string $body = [],
        ?float $duration = null
    ): void {
        if (!$this->shouldLog() || !$this->config['log_responses']) {
            return;
        }

        $context = [
            'method' => $method,
            'url' => $this->redactUrl($url),
            'status_code' => $statusCode,
            'duration_ms' => $duration ? round($duration * 1000, 2) : null,
        ];

        // Only include body for non-successful responses or in debug mode
        if ($statusCode >= 400 || $this->isDebugMode()) {
            $context['body'] = is_array($body)
                ? $this->redactSensitive($body)
                : $body;
        }

        $level = $statusCode >= 500 ? LogLevel::ERROR
            : ($statusCode >= 400 ? LogLevel::WARNING : LogLevel::INFO);

        $this->log($level, 'Evolution API Response', $context);
    }

    /**
     * Log a webhook event.
     *
     * @param string $event Event type
     * @param string $instanceName Instance name
     * @param array<string, mixed> $payload Webhook payload
     */
    public function logWebhook(string $event, string $instanceName, array $payload = []): void
    {
        if (!$this->shouldLog() || !$this->config['log_webhooks']) {
            return;
        }

        $context = [
            'event' => $event,
            'instance' => $instanceName,
        ];

        // Include payload in debug mode only
        if ($this->isDebugMode()) {
            $context['payload'] = $this->redactSensitive($payload);
        }

        $this->info('Evolution API Webhook', $context);
    }

    /**
     * Log an error.
     *
     * @param string $message Error message
     * @param array<string, mixed> $context Additional context
     * @param \Throwable|null $exception Optional exception
     */
    public function logError(string $message, array $context = [], ?\Throwable $exception = null): void
    {
        if (!$this->shouldLog()) {
            return;
        }

        if ($exception) {
            $context['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];

            if ($this->isDebugMode()) {
                $context['exception']['trace'] = $exception->getTraceAsString();
            }
        }

        $this->error($message, $context);
    }

    /**
     * Log instance status change.
     *
     * @param string $instanceName Instance name
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     */
    public function logStatusChange(string $instanceName, string $oldStatus, string $newStatus): void
    {
        if (!$this->shouldLog()) {
            return;
        }

        $this->info('Instance status changed', [
            'instance' => $instanceName,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
    }

    /**
     * Log a message send operation.
     *
     * @param string $instanceName Instance name
     * @param string $recipient Message recipient
     * @param string $type Message type
     * @param bool $success Whether the send was successful
     * @param string|null $messageId Message ID if successful
     */
    public function logMessageSend(
        string $instanceName,
        string $recipient,
        string $type,
        bool $success,
        ?string $messageId = null
    ): void {
        if (!$this->shouldLog()) {
            return;
        }

        $context = [
            'instance' => $instanceName,
            'recipient' => $this->maskPhoneNumber($recipient),
            'type' => $type,
            'success' => $success,
        ];

        if ($messageId) {
            $context['message_id'] = $messageId;
        }

        $level = $success ? LogLevel::INFO : LogLevel::WARNING;
        $message = $success ? 'Message sent' : 'Message send failed';

        $this->log($level, $message, $context);
    }

    /**
     * Log a debug message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Log a message with the specified level.
     *
     * @param string $level
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog()) {
            return;
        }

        // Add package identifier to all log entries
        $context['_package'] = 'evolution-api';

        $this->logger->log($level, "[EvolutionApi] {$message}", $context);
    }

    /**
     * Determine if logging is enabled.
     */
    protected function shouldLog(): bool
    {
        return $this->config['enabled'];
    }

    /**
     * Redact sensitive data from an array.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function redactSensitive(array $data): array
    {
        if (!$this->config['redact_sensitive']) {
            return $data;
        }

        return $this->recursiveRedact($data);
    }

    /**
     * Recursively redact sensitive fields.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function recursiveRedact(array $data): array
    {
        foreach ($data as $key => $value) {
            $lowercaseKey = strtolower((string) $key);

            // Check if this key should be redacted
            foreach ($this->sensitiveFields as $sensitiveField) {
                if (str_contains($lowercaseKey, strtolower($sensitiveField))) {
                    $data[$key] = '[REDACTED]';
                    continue 2;
                }
            }

            // Recursively process nested arrays
            if (is_array($value)) {
                $data[$key] = $this->recursiveRedact($value);
            }
        }

        return $data;
    }

    /**
     * Redact sensitive query parameters from URL.
     *
     * @param string $url
     * @return string
     */
    protected function redactUrl(string $url): string
    {
        if (!$this->config['redact_sensitive']) {
            return $url;
        }

        $parsed = parse_url($url);

        if (!isset($parsed['query'])) {
            return $url;
        }

        parse_str($parsed['query'], $queryParams);
        $redactedParams = $this->redactSensitive($queryParams);

        $parsed['query'] = http_build_query($redactedParams);

        return $this->buildUrl($parsed);
    }

    /**
     * Build URL from parsed components.
     *
     * @param array<string, mixed> $parts
     * @return string
     */
    protected function buildUrl(array $parts): string
    {
        $url = '';

        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'] . '://';
        }

        if (isset($parts['host'])) {
            $url .= $parts['host'];
        }

        if (isset($parts['port'])) {
            $url .= ':' . $parts['port'];
        }

        if (isset($parts['path'])) {
            $url .= $parts['path'];
        }

        if (isset($parts['query'])) {
            $url .= '?' . $parts['query'];
        }

        if (isset($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }

    /**
     * Mask a phone number for privacy.
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function maskPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters except the leading +
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);

        if (strlen($cleaned) <= 4) {
            return $cleaned;
        }

        // Show first 4 and last 2 digits
        $length = strlen($cleaned);
        $visible = 4;
        $masked = $length - $visible - 2;

        if ($masked <= 0) {
            return $cleaned;
        }

        return substr($cleaned, 0, $visible)
            . str_repeat('*', $masked)
            . substr($cleaned, -2);
    }

    /**
     * Get the underlying logger instance.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Check if a specific log type is enabled.
     */
    public function isEnabled(string $type = 'general'): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }

        return match ($type) {
            'requests' => $this->config['log_requests'],
            'responses' => $this->config['log_responses'],
            'webhooks' => $this->config['log_webhooks'],
            default => true,
        };
    }

    /**
     * Check if debug mode is enabled.
     */
    protected function isDebugMode(): bool
    {
        // First check instance config, then fall back to Laravel config if available
        if (isset($this->config['debug'])) {
            return (bool) $this->config['debug'];
        }

        // Try to use Laravel's config helper, fall back to false if unavailable
        if (function_exists('config')) {
            try {
                return (bool) config('evolution-api.debug', false);
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
    }
}
