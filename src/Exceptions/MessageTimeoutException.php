<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Exceptions;

/**
 * Exception thrown when message sending times out.
 *
 * This typically occurs when Evolution API experiences "pre-key upload timeout"
 * errors. This is a known issue in the Baileys library where the encryption
 * key exchange with WhatsApp servers times out. Without successful pre-key
 * uploads, messages cannot be sent.
 *
 * Common causes:
 * - Network instability between Evolution API and WhatsApp servers
 * - WhatsApp server rate limiting or temporary blocks
 * - Evolution API/Baileys connection stability issues
 *
 * This is NOT a Laravel package issue - it's an upstream Evolution API/Baileys issue.
 *
 * @see https://github.com/WhiskeySockets/Baileys/issues
 */
class MessageTimeoutException extends MessageException
{
    /**
     * The timeout duration in seconds.
     */
    protected int $timeout;

    /**
     * Whether this might be a pre-key issue.
     */
    protected bool $possiblePreKeyIssue = false;

    /**
     * Suggestions for resolving the timeout.
     *
     * @var array<string>
     */
    protected array $suggestions = [];

    /**
     * Create a new message timeout exception.
     */
    public function __construct(
        string $message = 'Message sending timed out',
        int $timeout = 0,
        ?string $recipientNumber = null,
        ?string $messageType = null,
        ?string $instanceName = null,
        bool $possiblePreKeyIssue = false,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            message: $message,
            recipientNumber: $recipientNumber,
            messageType: $messageType,
            instanceName: $instanceName,
            statusCode: 408, // Request Timeout
            previous: $previous
        );

        $this->timeout = $timeout;
        $this->possiblePreKeyIssue = $possiblePreKeyIssue;
        $this->suggestions = $this->buildSuggestions();
    }

    /**
     * Get the timeout duration.
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Check if this might be a pre-key issue.
     */
    public function isPossiblePreKeyIssue(): bool
    {
        return $this->possiblePreKeyIssue;
    }

    /**
     * Get suggestions for resolving the timeout.
     *
     * @return array<string>
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    /**
     * Build suggestions based on the error type.
     *
     * @return array<string>
     */
    protected function buildSuggestions(): array
    {
        $suggestions = [
            'Wait a few moments and retry the message',
            'Check if the WhatsApp instance is still connected',
            'Verify network connectivity to Evolution API server',
        ];

        if ($this->possiblePreKeyIssue) {
            $suggestions = array_merge($suggestions, [
                'This may be a "pre-key upload timeout" issue in the Baileys library',
                'Try disconnecting and reconnecting the WhatsApp instance',
                'Check Evolution API logs for "Pre-key upload timeout" errors',
                'Consider using a newer version of Evolution API if available',
            ]);
        }

        return $suggestions;
    }

    /**
     * Create exception for a message send timeout.
     */
    public static function messageSendTimeout(
        string $recipientNumber,
        int $timeout,
        ?string $messageType = null,
        ?string $instanceName = null,
        ?\Throwable $previous = null
    ): self {
        return new self(
            message: "Message to {$recipientNumber} timed out after {$timeout} seconds. ".
                     'The Evolution API server did not respond in time. '.
                     'This may indicate the WhatsApp connection is unstable.',
            timeout: $timeout,
            recipientNumber: $recipientNumber,
            messageType: $messageType,
            instanceName: $instanceName,
            possiblePreKeyIssue: true,
            previous: $previous
        );
    }

    /**
     * Create exception from a connection timeout.
     */
    public static function fromConnectionTimeout(
        int $timeout,
        ?string $instanceName = null,
        ?\Throwable $previous = null
    ): self {
        return new self(
            message: "Connection to Evolution API timed out after {$timeout} seconds",
            timeout: $timeout,
            instanceName: $instanceName,
            previous: $previous
        );
    }

    /**
     * Create exception for potential pre-key issue.
     */
    public static function preKeyIssue(
        ?string $instanceName = null,
        ?\Throwable $previous = null
    ): self {
        return new self(
            message: 'Message sending failed, possibly due to pre-key upload timeout. '.
                     'This is a known Evolution API/Baileys issue where the encryption '.
                     'key exchange with WhatsApp servers fails. Try reconnecting the instance.',
            timeout: 0,
            instanceName: $instanceName,
            possiblePreKeyIssue: true,
            previous: $previous
        );
    }

    /**
     * Check if a throwable represents a timeout error.
     */
    public static function isTimeoutError(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        $timeoutIndicators = [
            'timeout',
            'timed out',
            'connection reset',
            'operation timed out',
            'curl error 28',
            'curl_error 28',
            'request timeout',
            'gateway timeout',
        ];

        foreach ($timeoutIndicators as $indicator) {
            if (str_contains($message, $indicator)) {
                return true;
            }
        }

        // Check for HTTP status codes
        if ($e instanceof EvolutionApiException && $e->getStatusCode() !== null) {
            $statusCode = $e->getStatusCode();
            if ($statusCode === 408 || $statusCode === 504) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a throwable might be a pre-key related issue.
     */
    public static function isPossiblePreKeyError(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        $preKeyIndicators = [
            'pre-key',
            'prekey',
            'pre key',
            'encryption',
            'noise-handler',
            'connection closed',
            'signal protocol',
        ];

        foreach ($preKeyIndicators as $indicator) {
            if (str_contains($message, $indicator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'timeout' => $this->timeout,
            'possible_pre_key_issue' => $this->possiblePreKeyIssue,
            'suggestions' => $this->suggestions,
        ]);
    }

    /**
     * Get a user-friendly error message with suggestions.
     */
    public function getDetailedMessage(): string
    {
        $message = $this->getMessage();

        if (! empty($this->suggestions)) {
            $message .= "\n\nSuggestions:\n";
            foreach ($this->suggestions as $index => $suggestion) {
                $message .= sprintf("%d. %s\n", $index + 1, $suggestion);
            }
        }

        return $message;
    }
}
