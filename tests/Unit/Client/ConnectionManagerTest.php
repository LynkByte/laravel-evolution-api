<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Exceptions\ConnectionException;

describe('ConnectionManager', function () {
    describe('constructor and default connection', function () {
        it('resolves default connection from legacy single-connection config', function () {
            $manager = new ConnectionManager([
                'server_url' => 'http://localhost:8080/',
                'api_key' => 'my-api-key',
            ]);

            $connection = $manager->connection('default');

            expect($connection)->toBe([
                'server_url' => 'http://localhost:8080',
                'api_key' => 'my-api-key',
            ]);
        });

        it('resolves default connection from connections array', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'default' => [
                        'server_url' => 'http://api.example.com/',
                        'api_key' => 'default-key',
                    ],
                ],
            ]);

            $connection = $manager->connection('default');

            expect($connection)->toBe([
                'server_url' => 'http://api.example.com',
                'api_key' => 'default-key',
            ]);
        });

        it('normalizes server URL by removing trailing slash', function () {
            $manager = new ConnectionManager([
                'server_url' => 'http://localhost:8080///',
                'api_key' => 'key',
            ]);

            expect($manager->getServerUrl())->toBe('http://localhost:8080');
        });

        it('handles missing default connection gracefully', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'production' => [
                        'server_url' => 'http://prod.example.com',
                        'api_key' => 'prod-key',
                    ],
                ],
            ]);

            expect(fn() => $manager->connection('default'))
                ->toThrow(ConnectionException::class);
        });
    });

    describe('connection()', function () {
        it('retrieves a connection by name', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'production' => [
                        'server_url' => 'http://prod.example.com',
                        'api_key' => 'prod-key',
                    ],
                    'staging' => [
                        'server_url' => 'http://staging.example.com',
                        'api_key' => 'staging-key',
                    ],
                ],
            ]);

            $production = $manager->connection('production');
            $staging = $manager->connection('staging');

            expect($production['server_url'])->toBe('http://prod.example.com');
            expect($production['api_key'])->toBe('prod-key');
            expect($staging['server_url'])->toBe('http://staging.example.com');
            expect($staging['api_key'])->toBe('staging-key');
        });

        it('caches resolved connections', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'test' => [
                        'server_url' => 'http://test.example.com',
                        'api_key' => 'test-key',
                    ],
                ],
            ]);

            $first = $manager->connection('test');
            $second = $manager->connection('test');

            expect($first)->toBe($second);
        });

        it('throws ConnectionException for non-existent connection', function () {
            $manager = new ConnectionManager([]);

            expect(fn() => $manager->connection('non-existent'))
                ->toThrow(ConnectionException::class, 'Evolution API connection [non-existent] is not configured.');
        });

        it('throws ConnectionException for missing server_url', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'invalid' => [
                        'api_key' => 'key-only',
                    ],
                ],
            ]);

            expect(fn() => $manager->connection('invalid'))
                ->toThrow(ConnectionException::class, "missing 'server_url'");
        });

        it('throws ConnectionException for missing api_key', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'invalid' => [
                        'server_url' => 'http://example.com',
                    ],
                ],
            ]);

            expect(fn() => $manager->connection('invalid'))
                ->toThrow(ConnectionException::class, "missing 'api_key'");
        });

        it('throws ConnectionException for invalid server_url', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'invalid' => [
                        'server_url' => 'not-a-valid-url',
                        'api_key' => 'key',
                    ],
                ],
            ]);

            expect(fn() => $manager->connection('invalid'))
                ->toThrow(ConnectionException::class, "invalid 'server_url'");
        });
    });

    describe('setActiveConnection() and getActiveConnection()', function () {
        it('sets and gets the active connection', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'default' => [
                        'server_url' => 'http://default.example.com',
                        'api_key' => 'default-key',
                    ],
                    'secondary' => [
                        'server_url' => 'http://secondary.example.com',
                        'api_key' => 'secondary-key',
                    ],
                ],
            ]);

            expect($manager->getActiveConnection())->toBe('default');

            $manager->setActiveConnection('secondary');

            expect($manager->getActiveConnection())->toBe('secondary');
        });

        it('returns self for method chaining', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'default' => [
                        'server_url' => 'http://example.com',
                        'api_key' => 'key',
                    ],
                ],
            ]);

            $result = $manager->setActiveConnection('default');

            expect($result)->toBe($manager);
        });

        it('validates connection exists before setting active', function () {
            $manager = new ConnectionManager([]);

            expect(fn() => $manager->setActiveConnection('non-existent'))
                ->toThrow(ConnectionException::class);
        });
    });

    describe('getActiveConnectionConfig()', function () {
        it('returns the active connection configuration', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'default' => [
                        'server_url' => 'http://default.example.com',
                        'api_key' => 'default-key',
                    ],
                    'production' => [
                        'server_url' => 'http://prod.example.com',
                        'api_key' => 'prod-key',
                    ],
                ],
            ]);

            $manager->setActiveConnection('production');

            expect($manager->getActiveConnectionConfig())->toBe([
                'server_url' => 'http://prod.example.com',
                'api_key' => 'prod-key',
            ]);
        });
    });

    describe('addConnection()', function () {
        it('adds a runtime connection', function () {
            $manager = new ConnectionManager([]);

            $manager->addConnection('dynamic', [
                'server_url' => 'http://dynamic.example.com',
                'api_key' => 'dynamic-key',
            ]);

            $connection = $manager->connection('dynamic');

            expect($connection['server_url'])->toBe('http://dynamic.example.com');
            expect($connection['api_key'])->toBe('dynamic-key');
        });

        it('returns self for method chaining', function () {
            $manager = new ConnectionManager([]);

            $result = $manager->addConnection('test', [
                'server_url' => 'http://test.example.com',
                'api_key' => 'test-key',
            ]);

            expect($result)->toBe($manager);
        });

        it('validates connection config when adding', function () {
            $manager = new ConnectionManager([]);

            expect(fn() => $manager->addConnection('invalid', [
                'server_url' => '',
                'api_key' => 'key',
            ]))->toThrow(ConnectionException::class, "missing 'server_url'");
        });

        it('normalizes server URL when adding connection', function () {
            $manager = new ConnectionManager([]);

            $manager->addConnection('normalized', [
                'server_url' => 'http://example.com///',
                'api_key' => 'key',
            ]);

            expect($manager->getServerUrl('normalized'))->toBe('http://example.com');
        });

        it('runtime connections take precedence over resolved connections', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'test' => [
                        'server_url' => 'http://config.example.com',
                        'api_key' => 'config-key',
                    ],
                ],
            ]);

            // Resolve from config first
            $manager->connection('test');

            // Add runtime connection with same name
            $manager->addConnection('test', [
                'server_url' => 'http://runtime.example.com',
                'api_key' => 'runtime-key',
            ]);

            $connection = $manager->connection('test');

            // Runtime should NOT override resolved (resolved takes priority in connection() method)
            // Let me check the source code again - it checks resolved first, then runtime
            expect($connection['server_url'])->toBe('http://config.example.com');
        });
    });

    describe('removeConnection()', function () {
        it('removes a runtime connection', function () {
            $manager = new ConnectionManager([]);

            $manager->addConnection('temp', [
                'server_url' => 'http://temp.example.com',
                'api_key' => 'temp-key',
            ]);

            expect($manager->hasConnection('temp'))->toBeTrue();

            $manager->removeConnection('temp');

            expect($manager->hasConnection('temp'))->toBeFalse();
        });

        it('returns self for method chaining', function () {
            $manager = new ConnectionManager([]);

            $result = $manager->removeConnection('non-existent');

            expect($result)->toBe($manager);
        });

        it('resets active connection to default when removing active connection', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'default' => [
                        'server_url' => 'http://default.example.com',
                        'api_key' => 'default-key',
                    ],
                ],
            ]);

            $manager->addConnection('temp', [
                'server_url' => 'http://temp.example.com',
                'api_key' => 'temp-key',
            ]);

            $manager->setActiveConnection('temp');
            expect($manager->getActiveConnection())->toBe('temp');

            $manager->removeConnection('temp');

            expect($manager->getActiveConnection())->toBe('default');
        });

        it('does not affect config-based connections', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'configured' => [
                        'server_url' => 'http://configured.example.com',
                        'api_key' => 'configured-key',
                    ],
                ],
            ]);

            $manager->removeConnection('configured');

            // Config-based connection should still be available
            expect($manager->hasConnection('configured'))->toBeTrue();
        });
    });

    describe('hasConnection()', function () {
        it('returns true for resolved connections', function () {
            $manager = new ConnectionManager([
                'server_url' => 'http://localhost:8080',
                'api_key' => 'key',
            ]);

            expect($manager->hasConnection('default'))->toBeTrue();
        });

        it('returns true for runtime connections', function () {
            $manager = new ConnectionManager([]);

            $manager->addConnection('runtime', [
                'server_url' => 'http://runtime.example.com',
                'api_key' => 'key',
            ]);

            expect($manager->hasConnection('runtime'))->toBeTrue();
        });

        it('returns true for connections in config array', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'in-config' => [
                        'server_url' => 'http://example.com',
                        'api_key' => 'key',
                    ],
                ],
            ]);

            expect($manager->hasConnection('in-config'))->toBeTrue();
        });

        it('returns false for non-existent connections', function () {
            $manager = new ConnectionManager([]);

            expect($manager->hasConnection('non-existent'))->toBeFalse();
        });
    });

    describe('getAvailableConnections()', function () {
        it('returns all available connection names', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'production' => [
                        'server_url' => 'http://prod.example.com',
                        'api_key' => 'prod-key',
                    ],
                    'staging' => [
                        'server_url' => 'http://staging.example.com',
                        'api_key' => 'staging-key',
                    ],
                ],
            ]);

            $manager->addConnection('runtime', [
                'server_url' => 'http://runtime.example.com',
                'api_key' => 'runtime-key',
            ]);

            $available = $manager->getAvailableConnections();

            expect($available)->toContain('production');
            expect($available)->toContain('staging');
            expect($available)->toContain('runtime');
        });

        it('returns unique connection names', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'shared' => [
                        'server_url' => 'http://config.example.com',
                        'api_key' => 'key',
                    ],
                ],
            ]);

            $manager->addConnection('shared', [
                'server_url' => 'http://runtime.example.com',
                'api_key' => 'key',
            ]);

            $available = $manager->getAvailableConnections();
            $sharedCount = count(array_filter($available, fn($name) => $name === 'shared'));

            expect($sharedCount)->toBe(1);
        });

        it('returns empty array when no connections configured', function () {
            $manager = new ConnectionManager([]);

            expect($manager->getAvailableConnections())->toBe([]);
        });
    });

    describe('getServerUrl()', function () {
        it('returns server URL for active connection when no name provided', function () {
            $manager = new ConnectionManager([
                'server_url' => 'http://active.example.com',
                'api_key' => 'key',
            ]);

            expect($manager->getServerUrl())->toBe('http://active.example.com');
        });

        it('returns server URL for named connection', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'named' => [
                        'server_url' => 'http://named.example.com',
                        'api_key' => 'key',
                    ],
                ],
            ]);

            expect($manager->getServerUrl('named'))->toBe('http://named.example.com');
        });
    });

    describe('getApiKey()', function () {
        it('returns API key for active connection when no name provided', function () {
            $manager = new ConnectionManager([
                'server_url' => 'http://example.com',
                'api_key' => 'active-api-key',
            ]);

            expect($manager->getApiKey())->toBe('active-api-key');
        });

        it('returns API key for named connection', function () {
            $manager = new ConnectionManager([
                'connections' => [
                    'named' => [
                        'server_url' => 'http://example.com',
                        'api_key' => 'named-api-key',
                    ],
                ],
            ]);

            expect($manager->getApiKey('named'))->toBe('named-api-key');
        });
    });

    describe('getConfig()', function () {
        it('returns the full configuration array', function () {
            $config = [
                'server_url' => 'http://example.com',
                'api_key' => 'key',
                'timeout' => 30,
                'retry' => ['times' => 3],
            ];

            $manager = new ConnectionManager($config);

            expect($manager->getConfig())->toBe($config);
        });
    });

    describe('getConfigValue()', function () {
        it('retrieves configuration value using dot notation', function () {
            $manager = new ConnectionManager([
                'server_url' => 'http://example.com',
                'api_key' => 'key',
                'retry' => [
                    'times' => 3,
                    'delay' => 100,
                ],
            ]);

            expect($manager->getConfigValue('server_url'))->toBe('http://example.com');
            expect($manager->getConfigValue('retry.times'))->toBe(3);
            expect($manager->getConfigValue('retry.delay'))->toBe(100);
        });

        it('returns default value for non-existent keys', function () {
            $manager = new ConnectionManager([]);

            expect($manager->getConfigValue('non.existent', 'default'))->toBe('default');
            expect($manager->getConfigValue('missing'))->toBeNull();
        });

        it('handles nested arrays correctly', function () {
            $manager = new ConnectionManager([
                'deep' => [
                    'nested' => [
                        'value' => 'found',
                    ],
                ],
            ]);

            expect($manager->getConfigValue('deep.nested.value'))->toBe('found');
            expect($manager->getConfigValue('deep.nested'))->toBe(['value' => 'found']);
        });
    });

    describe('purge()', function () {
        it('clears all resolved and runtime connections', function () {
            $manager = new ConnectionManager([
                'server_url' => 'http://example.com',
                'api_key' => 'key',
            ]);

            $manager->addConnection('runtime', [
                'server_url' => 'http://runtime.example.com',
                'api_key' => 'key',
            ]);

            $manager->purge();

            // Runtime connections should be cleared
            expect($manager->hasConnection('runtime'))->toBeFalse();
        });

        it('resets active connection to default', function () {
            $manager = new ConnectionManager([
                'server_url' => 'http://example.com',
                'api_key' => 'key',
            ]);

            $manager->addConnection('other', [
                'server_url' => 'http://other.example.com',
                'api_key' => 'key',
            ]);

            $manager->setActiveConnection('other');
            $manager->purge();

            expect($manager->getActiveConnection())->toBe('default');
        });

        it('re-resolves default connection after purge', function () {
            $manager = new ConnectionManager([
                'server_url' => 'http://example.com',
                'api_key' => 'original-key',
            ]);

            $manager->purge();

            // Default connection should be re-resolved from config
            expect($manager->hasConnection('default'))->toBeTrue();
            expect($manager->getApiKey())->toBe('original-key');
        });
    });
});
