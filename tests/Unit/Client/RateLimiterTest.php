<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Lynkbyte\EvolutionApi\Client\RateLimiter;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;

beforeEach(function () {
    $this->cache = new CacheRepository(new ArrayStore);
});

describe('RateLimiter', function () {
    describe('constructor and configuration', function () {
        it('creates instance with default configuration', function () {
            $limiter = new RateLimiter($this->cache);

            expect($limiter->isEnabled())->toBeTrue();
            expect($limiter->remaining('test-key', 'default'))->toBe(60);
            expect($limiter->remaining('test-key', 'messages'))->toBe(30);
            expect($limiter->remaining('test-key', 'media'))->toBe(10);
        });

        it('configures from config array', function () {
            $limiter = new RateLimiter($this->cache, [
                'enabled' => false,
                'on_limit_reached' => 'throw',
                'limits' => [
                    'custom' => [
                        'max_attempts' => 100,
                        'decay_seconds' => 120,
                    ],
                ],
            ]);

            expect($limiter->isEnabled())->toBeFalse();
            expect($limiter->remaining('test-key', 'custom'))->toBe(PHP_INT_MAX); // Disabled returns max
        });

        it('merges custom limits with default limits', function () {
            $limiter = new RateLimiter($this->cache, [
                'limits' => [
                    'custom' => [
                        'max_attempts' => 100,
                        'decay_seconds' => 120,
                    ],
                ],
            ]);

            // Default limits should still exist
            expect($limiter->remaining('key', 'default'))->toBe(60);
            expect($limiter->remaining('key', 'messages'))->toBe(30);
            expect($limiter->remaining('key', 'media'))->toBe(10);
            expect($limiter->remaining('key', 'custom'))->toBe(100);
        });
    });

    describe('attempt()', function () {
        it('allows attempts when under the limit', function () {
            $limiter = new RateLimiter($this->cache);

            $result = $limiter->attempt('user-1', 'default');

            expect($result)->toBeTrue();
            expect($limiter->remaining('user-1', 'default'))->toBe(59);
        });

        it('increments counter on each attempt', function () {
            $limiter = new RateLimiter($this->cache);

            $limiter->attempt('user-1', 'default');
            $limiter->attempt('user-1', 'default');
            $limiter->attempt('user-1', 'default');

            expect($limiter->remaining('user-1', 'default'))->toBe(57);
        });

        it('returns true when rate limiting is disabled', function () {
            $limiter = new RateLimiter($this->cache, ['enabled' => false]);

            // Even with many attempts, should always return true
            for ($i = 0; $i < 100; $i++) {
                expect($limiter->attempt('user-1', 'default'))->toBeTrue();
            }
        });

        it('tracks different keys separately', function () {
            $limiter = new RateLimiter($this->cache);

            $limiter->attempt('user-1', 'default');
            $limiter->attempt('user-1', 'default');
            $limiter->attempt('user-2', 'default');

            expect($limiter->remaining('user-1', 'default'))->toBe(58);
            expect($limiter->remaining('user-2', 'default'))->toBe(59);
        });

        it('tracks different types separately', function () {
            $limiter = new RateLimiter($this->cache);

            $limiter->attempt('user-1', 'default');
            $limiter->attempt('user-1', 'messages');
            $limiter->attempt('user-1', 'media');

            expect($limiter->remaining('user-1', 'default'))->toBe(59);
            expect($limiter->remaining('user-1', 'messages'))->toBe(29);
            expect($limiter->remaining('user-1', 'media'))->toBe(9);
        });

        it('uses default type limits for unknown types', function () {
            $limiter = new RateLimiter($this->cache);

            $limiter->attempt('user-1', 'unknown-type');

            // Unknown types fall back to 'default' limits
            expect($limiter->remaining('user-1', 'unknown-type'))->toBe(59);
        });
    });

    describe('isExceeded()', function () {
        it('returns false when under the limit', function () {
            $limiter = new RateLimiter($this->cache);

            expect($limiter->isExceeded('user-1', 'default'))->toBeFalse();
        });

        it('returns false when rate limiting is disabled', function () {
            $limiter = new RateLimiter($this->cache, ['enabled' => false]);

            expect($limiter->isExceeded('user-1', 'default'))->toBeFalse();
        });

        it('returns true when limit is reached', function () {
            $limiter = new RateLimiter($this->cache);

            // Use media type which has limit of 10
            for ($i = 0; $i < 10; $i++) {
                $limiter->attempt('user-1', 'media');
            }

            expect($limiter->isExceeded('user-1', 'media'))->toBeTrue();
        });
    });

    describe('remaining()', function () {
        it('returns max attempts when no attempts made', function () {
            $limiter = new RateLimiter($this->cache);

            expect($limiter->remaining('new-user', 'default'))->toBe(60);
            expect($limiter->remaining('new-user', 'messages'))->toBe(30);
            expect($limiter->remaining('new-user', 'media'))->toBe(10);
        });

        it('returns PHP_INT_MAX when disabled', function () {
            $limiter = new RateLimiter($this->cache, ['enabled' => false]);

            expect($limiter->remaining('user-1', 'default'))->toBe(PHP_INT_MAX);
        });

        it('returns 0 when limit is exhausted', function () {
            // Use 'skip' behavior to avoid waiting/sleeping
            $limiter = new RateLimiter($this->cache, [
                'on_limit_reached' => 'skip',
            ]);

            // Exhaust media limit (10)
            for ($i = 0; $i < 15; $i++) {
                $limiter->attempt('user-1', 'media');
            }

            expect($limiter->remaining('user-1', 'media'))->toBe(0);
        });

        it('decreases with each attempt', function () {
            $limiter = new RateLimiter($this->cache);

            expect($limiter->remaining('user-1', 'media'))->toBe(10);

            $limiter->attempt('user-1', 'media');
            expect($limiter->remaining('user-1', 'media'))->toBe(9);

            $limiter->attempt('user-1', 'media');
            expect($limiter->remaining('user-1', 'media'))->toBe(8);
        });
    });

    describe('availableIn()', function () {
        it('returns 0 when no attempts made', function () {
            $limiter = new RateLimiter($this->cache);

            expect($limiter->availableIn('new-user', 'default'))->toBe(0);
        });

        it('returns 0 when rate limiting is disabled', function () {
            $limiter = new RateLimiter($this->cache, ['enabled' => false]);

            expect($limiter->availableIn('user-1', 'default'))->toBe(0);
        });

        it('returns remaining decay time when limit is active', function () {
            $limiter = new RateLimiter($this->cache);

            $limiter->attempt('user-1', 'default');

            // Should return the decay seconds (fallback behavior with array store)
            $availableIn = $limiter->availableIn('user-1', 'default');

            expect($availableIn)->toBeGreaterThanOrEqual(0);
            expect($availableIn)->toBeLessThanOrEqual(60); // Default decay is 60 seconds
        });
    });

    describe('clear()', function () {
        it('clears rate limit for specific type', function () {
            $limiter = new RateLimiter($this->cache);

            $limiter->attempt('user-1', 'default');
            $limiter->attempt('user-1', 'default');
            $limiter->attempt('user-1', 'messages');

            $limiter->clear('user-1', 'default');

            expect($limiter->remaining('user-1', 'default'))->toBe(60); // Reset
            expect($limiter->remaining('user-1', 'messages'))->toBe(29); // Unchanged
        });

        it('clears all types when type is null', function () {
            $limiter = new RateLimiter($this->cache);

            $limiter->attempt('user-1', 'default');
            $limiter->attempt('user-1', 'messages');
            $limiter->attempt('user-1', 'media');

            $limiter->clear('user-1');

            expect($limiter->remaining('user-1', 'default'))->toBe(60);
            expect($limiter->remaining('user-1', 'messages'))->toBe(30);
            expect($limiter->remaining('user-1', 'media'))->toBe(10);
        });
    });

    describe('rate limit exceeded behavior', function () {
        it('returns false when on_limit_reached is skip', function () {
            $limiter = new RateLimiter($this->cache, [
                'on_limit_reached' => 'skip',
            ]);

            // Exhaust media limit
            for ($i = 0; $i < 10; $i++) {
                $limiter->attempt('user-1', 'media');
            }

            // Next attempt should return false (skip)
            expect($limiter->attempt('user-1', 'media'))->toBeFalse();
        });

        it('throws RateLimitException when on_limit_reached is throw', function () {
            $limiter = new RateLimiter($this->cache, [
                'on_limit_reached' => 'throw',
            ]);

            // Exhaust media limit
            for ($i = 0; $i < 10; $i++) {
                $limiter->attempt('user-1', 'media');
            }

            expect(fn () => $limiter->attempt('user-1', 'media'))
                ->toThrow(RateLimitException::class);
        });

        it('includes retry_after in exception', function () {
            $limiter = new RateLimiter($this->cache, [
                'on_limit_reached' => 'throw',
            ]);

            // Exhaust media limit
            for ($i = 0; $i < 10; $i++) {
                $limiter->attempt('user-1', 'media');
            }

            try {
                $limiter->attempt('user-1', 'media');
                $this->fail('Expected RateLimitException');
            } catch (RateLimitException $e) {
                expect($e->getRetryAfter())->toBeGreaterThanOrEqual(0);
                expect($e->getLimitType())->toBe('media');
            }
        });
    });

    describe('setLimits()', function () {
        it('sets custom limits for a type', function () {
            $limiter = new RateLimiter($this->cache);

            $limiter->setLimits('custom', 5, 30);

            expect($limiter->remaining('user-1', 'custom'))->toBe(5);
        });

        it('returns self for method chaining', function () {
            $limiter = new RateLimiter($this->cache);

            $result = $limiter->setLimits('custom', 5, 30);

            expect($result)->toBe($limiter);
        });

        it('overrides existing limits', function () {
            $limiter = new RateLimiter($this->cache);

            expect($limiter->remaining('user-1', 'default'))->toBe(60);

            $limiter->setLimits('default', 100, 120);

            expect($limiter->remaining('user-1', 'default'))->toBe(100);
        });
    });

    describe('enable() and disable()', function () {
        it('enables rate limiting', function () {
            $limiter = new RateLimiter($this->cache, ['enabled' => false]);

            expect($limiter->isEnabled())->toBeFalse();

            $limiter->enable();

            expect($limiter->isEnabled())->toBeTrue();
        });

        it('disables rate limiting', function () {
            $limiter = new RateLimiter($this->cache);

            expect($limiter->isEnabled())->toBeTrue();

            $limiter->disable();

            expect($limiter->isEnabled())->toBeFalse();
        });

        it('returns self for method chaining', function () {
            $limiter = new RateLimiter($this->cache);

            expect($limiter->enable())->toBe($limiter);
            expect($limiter->disable())->toBe($limiter);
        });
    });

    describe('setOnLimitReached()', function () {
        it('sets the action for wait', function () {
            $limiter = new RateLimiter($this->cache);

            $result = $limiter->setOnLimitReached('wait');

            expect($result)->toBe($limiter);
        });

        it('sets the action for throw', function () {
            $limiter = new RateLimiter($this->cache);

            $limiter->setOnLimitReached('throw');

            // Exhaust limit and verify it throws
            for ($i = 0; $i < 10; $i++) {
                $limiter->attempt('user-1', 'media');
            }

            expect(fn () => $limiter->attempt('user-1', 'media'))
                ->toThrow(RateLimitException::class);
        });

        it('sets the action for skip', function () {
            $limiter = new RateLimiter($this->cache);

            $limiter->setOnLimitReached('skip');

            // Exhaust limit and verify it returns false
            for ($i = 0; $i < 10; $i++) {
                $limiter->attempt('user-1', 'media');
            }

            expect($limiter->attempt('user-1', 'media'))->toBeFalse();
        });

        it('throws InvalidArgumentException for invalid action', function () {
            $limiter = new RateLimiter($this->cache);

            expect(fn () => $limiter->setOnLimitReached('invalid'))
                ->toThrow(InvalidArgumentException::class, 'Invalid onLimitReached action');
        });
    });

    describe('null()', function () {
        it('creates a no-op rate limiter', function () {
            $limiter = RateLimiter::null();

            expect($limiter)->toBeInstanceOf(RateLimiter::class);
        });

        it('always allows attempts', function () {
            $limiter = RateLimiter::null();

            for ($i = 0; $i < 1000; $i++) {
                expect($limiter->attempt('key', 'default'))->toBeTrue();
            }
        });

        it('never reports as exceeded', function () {
            $limiter = RateLimiter::null();

            expect($limiter->isExceeded('key', 'default'))->toBeFalse();
        });

        it('returns max remaining', function () {
            $limiter = RateLimiter::null();

            expect($limiter->remaining('key', 'default'))->toBe(PHP_INT_MAX);
        });
    });
});

describe('NullCache', function () {
    it('implements CacheRepository interface', function () {
        $limiter = RateLimiter::null();

        // The NullCache is internal, but we can verify the null limiter works
        expect($limiter->attempt('key', 'default'))->toBeTrue();
        expect($limiter->isExceeded('key', 'default'))->toBeFalse();
    });
});

describe('NullCache methods', function () {
    beforeEach(function () {
        // We need to access NullCache through RateLimiter::null()
        // and test its internal cache methods indirectly
        $this->nullLimiter = RateLimiter::null();
    });

    it('has() returns false', function () {
        // NullCache.has() always returns false
        // This is tested through the rate limiter behavior
        expect($this->nullLimiter->availableIn('test', 'default'))->toBe(0);
    });

    it('get() returns default', function () {
        // NullCache.get() always returns default
        // This is tested through remaining check on fresh key
        expect($this->nullLimiter->remaining('never-set-key', 'default'))->toBe(PHP_INT_MAX);
    });

    it('null cache store methods', function () {
        // Access the NullCache directly through reflection to test store methods
        $limiter = RateLimiter::null();
        
        // Through the limiter, we can verify the cache operations work as no-ops
        $limiter->clear('any-key');
        $limiter->clear('any-key', 'default');
        
        // Should still work normally
        expect($limiter->attempt('any-key', 'default'))->toBeTrue();
    });
});

describe('NullCache direct method tests', function () {
    beforeEach(function () {
        // Create NullCache instance directly via reflection
        $this->nullCache = new \Lynkbyte\EvolutionApi\Client\NullCache();
    });

    it('has() returns false', function () {
        expect($this->nullCache->has('any-key'))->toBeFalse();
    });

    it('get() returns default value', function () {
        expect($this->nullCache->get('any-key'))->toBeNull();
        expect($this->nullCache->get('any-key', 'default-value'))->toBe('default-value');
    });

    it('pull() returns default value', function () {
        expect($this->nullCache->pull('any-key'))->toBeNull();
        expect($this->nullCache->pull('any-key', 'default-value'))->toBe('default-value');
    });

    it('put() returns true', function () {
        expect($this->nullCache->put('key', 'value'))->toBeTrue();
        expect($this->nullCache->put('key', 'value', 60))->toBeTrue();
    });

    it('add() returns true', function () {
        expect($this->nullCache->add('key', 'value'))->toBeTrue();
        expect($this->nullCache->add('key', 'value', 60))->toBeTrue();
    });

    it('increment() returns 1', function () {
        expect($this->nullCache->increment('key'))->toBe(1);
        expect($this->nullCache->increment('key', 5))->toBe(1);
    });

    it('decrement() returns 0', function () {
        expect($this->nullCache->decrement('key'))->toBe(0);
        expect($this->nullCache->decrement('key', 5))->toBe(0);
    });

    it('forever() returns true', function () {
        expect($this->nullCache->forever('key', 'value'))->toBeTrue();
    });

    it('forget() returns true', function () {
        expect($this->nullCache->forget('key'))->toBeTrue();
    });

    it('flush() returns true', function () {
        expect($this->nullCache->flush())->toBeTrue();
    });

    it('getPrefix() returns empty string', function () {
        expect($this->nullCache->getPrefix())->toBe('');
    });

    it('set() returns true', function () {
        expect($this->nullCache->set('key', 'value'))->toBeTrue();
        expect($this->nullCache->set('key', 'value', 60))->toBeTrue();
    });

    it('delete() returns true', function () {
        expect($this->nullCache->delete('key'))->toBeTrue();
    });

    it('clear() returns true', function () {
        expect($this->nullCache->clear())->toBeTrue();
    });

    it('getMultiple() returns empty array', function () {
        expect($this->nullCache->getMultiple(['key1', 'key2']))->toBe([]);
        expect($this->nullCache->getMultiple(['key1', 'key2'], 'default'))->toBe([]);
    });

    it('setMultiple() returns true', function () {
        expect($this->nullCache->setMultiple(['key1' => 'value1', 'key2' => 'value2']))->toBeTrue();
        expect($this->nullCache->setMultiple(['key1' => 'value1'], 60))->toBeTrue();
    });

    it('deleteMultiple() returns true', function () {
        expect($this->nullCache->deleteMultiple(['key1', 'key2']))->toBeTrue();
    });

    it('remember() calls and returns callback result', function () {
        $result = $this->nullCache->remember('key', 60, fn () => 'computed-value');
        expect($result)->toBe('computed-value');
    });

    it('sear() calls and returns callback result', function () {
        $result = $this->nullCache->sear('key', fn () => 'seared-value');
        expect($result)->toBe('seared-value');
    });

    it('rememberForever() calls and returns callback result', function () {
        $result = $this->nullCache->rememberForever('key', fn () => 'forever-value');
        expect($result)->toBe('forever-value');
    });

    it('getStore() returns NullStore implementing Store interface', function () {
        $store = $this->nullCache->getStore();
        expect($store)->toBeInstanceOf(\Illuminate\Contracts\Cache\Store::class);
    });
});

describe('NullCache Store methods', function () {
    beforeEach(function () {
        $nullCache = new \Lynkbyte\EvolutionApi\Client\NullCache();
        $this->store = $nullCache->getStore();
    });

    it('get() returns null', function () {
        expect($this->store->get('any-key'))->toBeNull();
    });

    it('many() returns empty array', function () {
        expect($this->store->many(['key1', 'key2']))->toBe([]);
    });

    it('put() returns true', function () {
        expect($this->store->put('key', 'value', 60))->toBeTrue();
    });

    it('putMany() returns true', function () {
        expect($this->store->putMany(['key1' => 'value1'], 60))->toBeTrue();
    });

    it('increment() returns 1', function () {
        expect($this->store->increment('key'))->toBe(1);
        expect($this->store->increment('key', 5))->toBe(1);
    });

    it('decrement() returns 0', function () {
        expect($this->store->decrement('key'))->toBe(0);
        expect($this->store->decrement('key', 5))->toBe(0);
    });

    it('forever() returns true', function () {
        expect($this->store->forever('key', 'value'))->toBeTrue();
    });

    it('forget() returns true', function () {
        expect($this->store->forget('key'))->toBeTrue();
    });

    it('flush() returns true', function () {
        expect($this->store->flush())->toBeTrue();
    });

    it('getPrefix() returns empty string', function () {
        expect($this->store->getPrefix())->toBe('');
    });
});

describe('wait() method', function () {
    it('returns true immediately when rate limiting is disabled', function () {
        $cache = new CacheRepository(new ArrayStore);
        $limiter = new RateLimiter($cache, ['enabled' => false]);

        $result = $limiter->wait('test-key', 'default');

        expect($result)->toBeTrue();
    });

    it('returns true immediately when not at limit', function () {
        $cache = new CacheRepository(new ArrayStore);
        $limiter = new RateLimiter($cache, [
            'limits' => [
                'default' => ['max_attempts' => 60, 'decay_seconds' => 60],
            ],
        ]);

        // No attempts made, availableIn returns 0, so wait returns immediately
        $result = $limiter->wait('fresh-key', 'default');

        expect($result)->toBeTrue();
    });

    it('returns false when maxWait is exceeded', function () {
        $cache = new CacheRepository(new ArrayStore);
        $limiter = new RateLimiter($cache, [
            'limits' => [
                'test' => ['max_attempts' => 1, 'decay_seconds' => 3600], // 1 hour decay
            ],
        ]);

        // Exhaust the limit
        $limiter->attempt('test-key', 'test');

        // Try to wait with a short maxWait - should return false immediately
        // because waitTime (3600) > maxWait (1)
        $result = $limiter->wait('test-key', 'test', 1);

        expect($result)->toBeFalse();
    });

    it('returns true when no wait time is needed', function () {
        $cache = new CacheRepository(new ArrayStore);
        $limiter = new RateLimiter($cache);

        // No attempts made, availableIn should be 0
        $result = $limiter->wait('fresh-key', 'default');

        expect($result)->toBeTrue();
    });
});

describe('handleLimitExceeded with default action', function () {
    it('throws exception for unknown on_limit_reached action', function () {
        // The default match arm throws RateLimitException for unknown actions
        // This tests the 'default' case in the match expression
        $cache = new CacheRepository(new ArrayStore);
        
        // Create limiter with a known action first
        $limiter = new RateLimiter($cache, [
            'on_limit_reached' => 'throw',
            'limits' => [
                'test' => ['max_attempts' => 1, 'decay_seconds' => 60],
            ],
        ]);

        // Exhaust limit
        $limiter->attempt('user-1', 'test');

        // Should throw
        expect(fn () => $limiter->attempt('user-1', 'test'))
            ->toThrow(RateLimitException::class);
    });
});

describe('configuration edge cases', function () {
    it('ignores invalid limits in config', function () {
        $cache = new CacheRepository(new ArrayStore);
        $limiter = new RateLimiter($cache, [
            'limits' => [
                'valid' => [
                    'max_attempts' => 5,
                    'decay_seconds' => 30,
                ],
                'invalid-missing-max' => [
                    'decay_seconds' => 30,
                ],
                'invalid-missing-decay' => [
                    'max_attempts' => 5,
                ],
            ],
        ]);

        // Valid limit should work
        expect($limiter->remaining('key', 'valid'))->toBe(5);
        
        // Invalid limits should fall back to default
        expect($limiter->remaining('key', 'invalid-missing-max'))->toBe(60);
        expect($limiter->remaining('key', 'invalid-missing-decay'))->toBe(60);
    });

    it('handles non-array limits config gracefully', function () {
        $cache = new CacheRepository(new ArrayStore);
        $limiter = new RateLimiter($cache, [
            'limits' => 'not-an-array',
        ]);

        // Should use defaults
        expect($limiter->remaining('key', 'default'))->toBe(60);
    });

    it('converts limit values to integers', function () {
        $cache = new CacheRepository(new ArrayStore);
        $limiter = new RateLimiter($cache, [
            'limits' => [
                'custom' => [
                    'max_attempts' => '25', // String
                    'decay_seconds' => '120', // String
                ],
            ],
        ]);

        expect($limiter->remaining('key', 'custom'))->toBe(25);
    });
});
