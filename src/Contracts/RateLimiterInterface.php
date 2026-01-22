<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Contracts;

/**
 * Interface for rate limiting API requests.
 */
interface RateLimiterInterface
{
    /**
     * Attempt to acquire a rate limit slot.
     *
     * @param string $key The rate limit key
     * @param string $type The type of operation (default, messages, media)
     * @return bool Whether the request is allowed
     */
    public function attempt(string $key, string $type = 'default'): bool;

    /**
     * Check if rate limit is currently exceeded.
     *
     * @param string $key The rate limit key
     * @param string $type The type of operation
     * @return bool Whether the limit is exceeded
     */
    public function isExceeded(string $key, string $type = 'default'): bool;

    /**
     * Get the number of seconds until the rate limit resets.
     *
     * @param string $key The rate limit key
     * @param string $type The type of operation
     * @return int Seconds until reset
     */
    public function availableIn(string $key, string $type = 'default'): int;

    /**
     * Get the number of remaining attempts.
     *
     * @param string $key The rate limit key
     * @param string $type The type of operation
     * @return int Remaining attempts
     */
    public function remaining(string $key, string $type = 'default'): int;

    /**
     * Clear the rate limit for a key.
     *
     * @param string $key The rate limit key
     * @param string|null $type The type of operation (null = all types)
     */
    public function clear(string $key, ?string $type = null): void;

    /**
     * Wait until a rate limit slot is available.
     *
     * @param string $key The rate limit key
     * @param string $type The type of operation
     * @param int $maxWait Maximum seconds to wait (0 = unlimited)
     * @return bool Whether a slot became available
     */
    public function wait(string $key, string $type = 'default', int $maxWait = 0): bool;
}
