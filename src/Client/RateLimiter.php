<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Client;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Lynkbyte\EvolutionApi\Contracts\RateLimiterInterface;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;

/**
 * Rate limiter implementation using Laravel's cache.
 */
class RateLimiter implements RateLimiterInterface
{
    /**
     * Cache prefix for rate limit keys.
     */
    protected const CACHE_PREFIX = 'evolution_api_rate_limit:';

    /**
     * Default rate limits by type.
     *
     * @var array<string, array{max_attempts: int, decay_seconds: int}>
     */
    protected array $limits = [
        'default' => [
            'max_attempts' => 60,
            'decay_seconds' => 60,
        ],
        'messages' => [
            'max_attempts' => 30,
            'decay_seconds' => 60,
        ],
        'media' => [
            'max_attempts' => 10,
            'decay_seconds' => 60,
        ],
    ];

    /**
     * Whether rate limiting is enabled.
     */
    protected bool $enabled = true;

    /**
     * Action to take when limit is reached.
     */
    protected string $onLimitReached = 'wait';

    /**
     * Create a new rate limiter instance.
     *
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected CacheRepository $cache,
        array $config = []
    ) {
        $this->configure($config);
    }

    /**
     * Configure the rate limiter from config array.
     *
     * @param array<string, mixed> $config
     */
    protected function configure(array $config): void
    {
        $this->enabled = $config['enabled'] ?? true;
        $this->onLimitReached = $config['on_limit_reached'] ?? 'wait';

        if (isset($config['limits']) && is_array($config['limits'])) {
            foreach ($config['limits'] as $type => $limits) {
                if (isset($limits['max_attempts']) && isset($limits['decay_seconds'])) {
                    $this->limits[$type] = [
                        'max_attempts' => (int) $limits['max_attempts'],
                        'decay_seconds' => (int) $limits['decay_seconds'],
                    ];
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attempt(string $key, string $type = 'default'): bool
    {
        if (! $this->enabled) {
            return true;
        }

        $cacheKey = $this->getCacheKey($key, $type);
        $limits = $this->getLimits($type);

        $attempts = (int) $this->cache->get($cacheKey, 0);

        if ($attempts >= $limits['max_attempts']) {
            return $this->handleLimitExceeded($key, $type);
        }

        // Increment the counter
        if ($attempts === 0) {
            $this->cache->put($cacheKey, 1, $limits['decay_seconds']);
        } else {
            $this->cache->increment($cacheKey);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isExceeded(string $key, string $type = 'default'): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $cacheKey = $this->getCacheKey($key, $type);
        $limits = $this->getLimits($type);

        $attempts = (int) $this->cache->get($cacheKey, 0);

        return $attempts >= $limits['max_attempts'];
    }

    /**
     * {@inheritdoc}
     */
    public function availableIn(string $key, string $type = 'default'): int
    {
        if (! $this->enabled) {
            return 0;
        }

        $cacheKey = $this->getCacheKey($key, $type);
        $limits = $this->getLimits($type);

        // Check if the key exists and get TTL
        if (! $this->cache->has($cacheKey)) {
            return 0;
        }

        // Try to get TTL if cache store supports it
        $store = $this->cache->getStore();

        if (method_exists($store, 'connection')) {
            $connection = $store->connection();
            if (method_exists($connection, 'ttl')) {
                $ttl = $connection->ttl($this->getCachePrefix() . $cacheKey);

                return max(0, $ttl);
            }
        }

        // Fallback: assume full decay period
        return $limits['decay_seconds'];
    }

    /**
     * {@inheritdoc}
     */
    public function remaining(string $key, string $type = 'default'): int
    {
        if (! $this->enabled) {
            return PHP_INT_MAX;
        }

        $cacheKey = $this->getCacheKey($key, $type);
        $limits = $this->getLimits($type);

        $attempts = (int) $this->cache->get($cacheKey, 0);

        return max(0, $limits['max_attempts'] - $attempts);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key, ?string $type = null): void
    {
        if ($type !== null) {
            $this->cache->forget($this->getCacheKey($key, $type));

            return;
        }

        // Clear all types for this key
        foreach (array_keys($this->limits) as $limitType) {
            $this->cache->forget($this->getCacheKey($key, $limitType));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function wait(string $key, string $type = 'default', int $maxWait = 0): bool
    {
        if (! $this->enabled) {
            return true;
        }

        $startTime = time();
        $waitTime = $this->availableIn($key, $type);

        if ($waitTime === 0) {
            return true;
        }

        if ($maxWait > 0 && $waitTime > $maxWait) {
            return false;
        }

        // Sleep until rate limit resets
        sleep($waitTime);

        // Verify the limit has reset (another process might have used it)
        if (! $this->isExceeded($key, $type)) {
            return true;
        }

        // If still exceeded and we have time left, recurse
        $elapsed = time() - $startTime;
        $remainingWait = $maxWait > 0 ? $maxWait - $elapsed : 0;

        if ($remainingWait > 0 || $maxWait === 0) {
            return $this->wait($key, $type, max(0, $remainingWait));
        }

        return false;
    }

    /**
     * Handle when rate limit is exceeded.
     *
     * @throws RateLimitException
     */
    protected function handleLimitExceeded(string $key, string $type): bool
    {
        $retryAfter = $this->availableIn($key, $type);

        return match ($this->onLimitReached) {
            'wait' => $this->wait($key, $type),
            'skip' => false,
            'throw' => throw new RateLimitException(
                message: "Rate limit exceeded for key [{$key}] type [{$type}].",
                retryAfter: $retryAfter,
                limitType: $type
            ),
            default => throw new RateLimitException(
                message: "Rate limit exceeded for key [{$key}] type [{$type}].",
                retryAfter: $retryAfter,
                limitType: $type
            ),
        };
    }

    /**
     * Get the cache key for a rate limit.
     */
    protected function getCacheKey(string $key, string $type): string
    {
        return self::CACHE_PREFIX . "{$type}:{$key}";
    }

    /**
     * Get the cache prefix.
     */
    protected function getCachePrefix(): string
    {
        return self::CACHE_PREFIX;
    }

    /**
     * Get limits for a specific type.
     *
     * @return array{max_attempts: int, decay_seconds: int}
     */
    protected function getLimits(string $type): array
    {
        return $this->limits[$type] ?? $this->limits['default'];
    }

    /**
     * Set custom limits for a type.
     */
    public function setLimits(string $type, int $maxAttempts, int $decaySeconds): self
    {
        $this->limits[$type] = [
            'max_attempts' => $maxAttempts,
            'decay_seconds' => $decaySeconds,
        ];

        return $this;
    }

    /**
     * Enable rate limiting.
     */
    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    /**
     * Disable rate limiting.
     */
    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * Check if rate limiting is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set the action to take when limit is reached.
     *
     * @param string $action One of: wait, throw, skip
     */
    public function setOnLimitReached(string $action): self
    {
        if (! in_array($action, ['wait', 'throw', 'skip'])) {
            throw new \InvalidArgumentException(
                "Invalid onLimitReached action [{$action}]. Must be one of: wait, throw, skip."
            );
        }

        $this->onLimitReached = $action;

        return $this;
    }

    /**
     * Create a null (no-op) rate limiter for testing.
     */
    public static function null(): self
    {
        return new class(new NullCache) extends RateLimiter
        {
            public function attempt(string $key, string $type = 'default'): bool
            {
                return true;
            }

            public function isExceeded(string $key, string $type = 'default'): bool
            {
                return false;
            }

            public function remaining(string $key, string $type = 'default'): int
            {
                return PHP_INT_MAX;
            }
        };
    }
}

/**
 * Null cache implementation for testing.
 *
 * @internal
 */
class NullCache implements CacheRepository
{
    public function has($key): bool
    {
        return false;
    }

    public function get($key, $default = null): mixed
    {
        return $default;
    }

    public function pull($key, $default = null): mixed
    {
        return $default;
    }

    public function put($key, $value, $ttl = null): bool
    {
        return true;
    }

    public function add($key, $value, $ttl = null): bool
    {
        return true;
    }

    public function increment($key, $value = 1): int|bool
    {
        return 1;
    }

    public function decrement($key, $value = 1): int|bool
    {
        return 0;
    }

    public function forever($key, $value): bool
    {
        return true;
    }

    public function forget($key): bool
    {
        return true;
    }

    public function flush(): bool
    {
        return true;
    }

    public function getPrefix(): string
    {
        return '';
    }

    public function getStore(): \Illuminate\Contracts\Cache\Store
    {
        return new class implements \Illuminate\Contracts\Cache\Store {
            public function get($key): mixed
            {
                return null;
            }

            public function many(array $keys): array
            {
                return [];
            }

            public function put($key, $value, $seconds): bool
            {
                return true;
            }

            public function putMany(array $values, $seconds): bool
            {
                return true;
            }

            public function increment($key, $value = 1): int|bool
            {
                return 1;
            }

            public function decrement($key, $value = 1): int|bool
            {
                return 0;
            }

            public function forever($key, $value): bool
            {
                return true;
            }

            public function forget($key): bool
            {
                return true;
            }

            public function flush(): bool
            {
                return true;
            }

            public function getPrefix(): string
            {
                return '';
            }
        };
    }

    public function set($key, $value, $ttl = null): bool
    {
        return true;
    }

    public function delete($key): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        return [];
    }

    public function setMultiple($values, $ttl = null): bool
    {
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        return true;
    }

    public function remember($key, $ttl, $callback): mixed
    {
        return $callback();
    }

    public function sear($key, $callback): mixed
    {
        return $callback();
    }

    public function rememberForever($key, $callback): mixed
    {
        return $callback();
    }
}
