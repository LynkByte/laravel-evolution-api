<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Client\EvolutionClient;
use Lynkbyte\EvolutionApi\Client\RateLimiter;
use Lynkbyte\EvolutionApi\DTOs\ApiResponse;
use Lynkbyte\EvolutionApi\Exceptions\AuthenticationException;
use Lynkbyte\EvolutionApi\Exceptions\ConnectionException;
use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;
use Lynkbyte\EvolutionApi\Exceptions\InstanceNotFoundException;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;

describe('EvolutionClient', function () {

    beforeEach(function () {
        Http::preventStrayRequests();

        $this->config = [
            'connections' => [
                'default' => [
                    'server_url' => 'https://api.evolution.test',
                    'api_key' => 'test-api-key',
                ],
            ],
            'http' => [
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify_ssl' => true,
            ],
            'retry' => [
                'enabled' => false,
                'max_attempts' => 3,
                'base_delay' => 1000,
            ],
            'logging' => [
                'log_requests' => true,
                'log_responses' => true,
                'redact_sensitive' => true,
                'sensitive_fields' => ['apikey', 'token'],
            ],
        ];

        $this->connectionManager = new ConnectionManager($this->config);
        $this->client = new EvolutionClient($this->connectionManager);
    });

    describe('connection', function () {
        it('sets active connection and returns self', function () {
            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://default.test',
                        'api_key' => 'default-key',
                    ],
                    'secondary' => [
                        'server_url' => 'https://secondary.test',
                        'api_key' => 'secondary-key',
                    ],
                ],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            $result = $client->connection('secondary');

            expect($result)->toBe($client);
            expect($client->getConnectionName())->toBe('secondary');
        });
    });

    describe('instance', function () {
        it('sets instance name and returns self', function () {
            $result = $this->client->instance('my-instance');

            expect($result)->toBe($this->client);
            expect($this->client->getInstanceName())->toBe('my-instance');
        });

        it('can be cleared', function () {
            $this->client->instance('test-instance');
            $this->client->clearInstance();

            expect($this->client->getInstanceName())->toBeNull();
        });
    });

    describe('get', function () {
        it('makes GET request and returns ApiResponse', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'instance' => ['instanceName' => 'test'],
                ], 200),
            ]);

            $response = $this->client->get('/instance/test');

            expect($response)->toBeInstanceOf(ApiResponse::class);
            expect($response->isSuccessful())->toBeTrue();
            expect($response->getData())->toHaveKey('instance');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'GET' &&
                    str_contains($request->url(), 'instance/test');
            });
        });

        it('sends query parameters', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['data' => []], 200),
            ]);

            $this->client->get('/instances', ['status' => 'open']);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'status=open');
            });
        });

        it('includes apikey header', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['data' => []], 200),
            ]);

            $this->client->get('/test');

            Http::assertSent(function (Request $request) {
                return $request->hasHeader('apikey', 'test-api-key');
            });
        });
    });

    describe('post', function () {
        it('makes POST request with JSON data', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'key' => ['id' => 'MSG123'],
                ], 200),
            ]);

            $response = $this->client->post('/message/sendText/test', [
                'number' => '5511999999999',
                'text' => 'Hello',
            ]);

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return $request->method() === 'POST' &&
                    $request['number'] === '5511999999999' &&
                    $request['text'] === 'Hello';
            });
        });
    });

    describe('put', function () {
        it('makes PUT request with JSON data', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $response = $this->client->put('/settings/test', ['reject_call' => true]);

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return $request->method() === 'PUT' &&
                    $request['reject_call'] === true;
            });
        });
    });

    describe('delete', function () {
        it('makes DELETE request', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'deleted'], 200),
            ]);

            $response = $this->client->delete('/instance/test');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return $request->method() === 'DELETE';
            });
        });
    });

    describe('patch', function () {
        it('makes PATCH request with JSON data', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'patched'], 200),
            ]);

            $response = $this->client->patch('/profile/test', ['name' => 'New Name']);

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return $request->method() === 'PATCH' &&
                    $request['name'] === 'New Name';
            });
        });
    });

    describe('getBaseUrl', function () {
        it('returns server url from connection manager', function () {
            expect($this->client->getBaseUrl())->toBe('https://api.evolution.test');
        });
    });

    describe('ping', function () {
        it('returns true on successful response', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'ok'], 200),
            ]);

            expect($this->client->ping())->toBeTrue();
        });

        it('returns false on failed response', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['error' => 'Server error'], 500),
            ]);

            expect($this->client->ping())->toBeFalse();
        });

        it('returns false on connection error', function () {
            Http::fake([
                'api.evolution.test/*' => function () {
                    throw new \Exception('Connection refused');
                },
            ]);

            expect($this->client->ping())->toBeFalse();
        });
    });

    describe('info', function () {
        it('returns server info', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'version' => '1.5.0',
                    'instance_count' => 5,
                ], 200),
            ]);

            $info = $this->client->info();

            expect($info)->toHaveKey('version');
            expect($info['version'])->toBe('1.5.0');
        });
    });

    describe('throwOnError', function () {
        it('can disable exception throwing', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['error' => 'Bad request'], 400),
            ]);

            $response = $this->client
                ->throwOnError(false)
                ->get('/test');

            expect($response->isSuccessful())->toBeFalse();
            expect($response->statusCode)->toBe(400);
        });

        it('withoutThrowing is alias for throwOnError(false)', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['error' => 'Error'], 500),
            ]);

            $response = $this->client
                ->withoutThrowing()
                ->get('/test');

            expect($response->isFailed())->toBeTrue();
        });
    });

    describe('withHeaders', function () {
        it('adds custom headers to request', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $this->client
                ->withHeaders(['X-Custom-Header' => 'custom-value'])
                ->get('/test');

            Http::assertSent(function (Request $request) {
                return $request->hasHeader('X-Custom-Header', 'custom-value');
            });
        });

        it('clears custom headers after request', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $this->client
                ->withHeaders(['X-Once' => 'value'])
                ->get('/test');

            $this->client->get('/test2');

            $requests = Http::recorded();
            expect($requests[1][0]->hasHeader('X-Once'))->toBeFalse();
        });
    });

    describe('error handling', function () {
        it('throws AuthenticationException on 401', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Invalid API key',
                ], 401),
            ]);

            expect(fn () => $this->client->get('/test'))
                ->toThrow(AuthenticationException::class);
        });

        it('throws InstanceNotFoundException on 404', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Instance not found',
                ], 404),
            ]);

            expect(fn () => $this->client->get('/instance/unknown'))
                ->toThrow(InstanceNotFoundException::class);
        });

        it('throws RateLimitException on 429', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Too many requests',
                ], 429, ['Retry-After' => '60']),
            ]);

            expect(fn () => $this->client->get('/test'))
                ->toThrow(RateLimitException::class);
        });

        it('throws EvolutionApiException on other errors', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Internal server error',
                ], 500),
            ]);

            expect(fn () => $this->client->get('/test'))
                ->toThrow(EvolutionApiException::class);
        });

        it('throws ConnectionException on connection failure', function () {
            Http::fake([
                'api.evolution.test/*' => function () {
                    throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
                },
            ]);

            expect(fn () => $this->client->get('/test'))
                ->toThrow(ConnectionException::class);
        });
    });

    describe('instance placeholder replacement', function () {
        it('replaces {instance} placeholder in endpoint', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $this->client
                ->instance('my-instance')
                ->get('/message/sendText/{instance}');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/sendText/my-instance');
            });
        });

        it('throws when instance placeholder used without instance set', function () {
            expect(fn () => $this->client->get('/message/{instance}'))
                ->toThrow(InstanceNotFoundException::class, 'Instance name is required');
        });
    });

    describe('rate limiting integration', function () {
        it('accepts rate limiter', function () {
            $cache = new CacheRepository(new ArrayStore);
            $rateLimiter = new RateLimiter($cache, [
                'enabled' => true,
                'limits' => ['default' => ['max_attempts' => 100, 'decay_seconds' => 60]],
            ]);

            $client = new EvolutionClient($this->connectionManager, $rateLimiter);

            expect($client->getRateLimiter())->toBe($rateLimiter);
        });

        it('can set rate limiter after construction', function () {
            $cache = new CacheRepository(new ArrayStore);
            $rateLimiter = new RateLimiter($cache, ['enabled' => true]);

            $result = $this->client->setRateLimiter($rateLimiter);

            expect($result)->toBe($this->client);
            expect($this->client->getRateLimiter())->toBe($rateLimiter);
        });
    });

    describe('connection manager', function () {
        it('returns connection manager', function () {
            expect($this->client->getConnectionManager())->toBe($this->connectionManager);
        });
    });

    describe('upload', function () {
        it('uploads files via multipart form', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'mediaUrl' => 'https://cdn.test/file.jpg',
                ], 200),
            ]);

            $response = $this->client->upload(
                '/sendMedia/test-instance',
                ['number' => '5511999999999'],
                [
                    [
                        'name' => 'file',
                        'contents' => 'fake-file-contents',
                        'filename' => 'image.jpg',
                    ],
                ]
            );

            expect($response->isSuccessful())->toBeTrue();
            expect($response->getData())->toHaveKey('mediaUrl');
        });
    });

    describe('API response creation', function () {
        it('creates successful ApiResponse', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'data' => ['key' => 'value'],
                    'message' => 'Success',
                ], 200),
            ]);

            $response = $this->client->get('/test');

            expect($response->isSuccessful())->toBeTrue();
            expect($response->statusCode)->toBe(200);
            expect($response->getData())->toHaveKey('data');
        });

        it('creates failed ApiResponse when body contains error', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'error' => 'Something went wrong',
                ], 200),
            ]);

            $response = $this->client
                ->withoutThrowing()
                ->get('/test');

            expect($response->isFailed())->toBeTrue();
        });

        it('includes response time', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $response = $this->client->get('/test');

            expect($response->responseTime)->toBeGreaterThan(0);
        });

        it('handles non-JSON responses gracefully', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response('Plain text response', 200, [
                    'Content-Type' => 'text/plain',
                ]),
            ]);

            $response = $this->client->get('/test');

            // Should not throw, just have empty data
            expect($response->isSuccessful())->toBeTrue();
        });
    });

    describe('retry configuration', function () {
        it('can enable retries via config', function () {
            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://api.test',
                        'api_key' => 'key',
                    ],
                ],
                'retry' => [
                    'enabled' => true,
                    'max_attempts' => 3,
                    'base_delay' => 100,
                    'retryable_status_codes' => [500, 502, 503],
                ],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            // Just verify client was created with retry config
            expect($client->getConnectionManager()->getConfig()['retry']['enabled'])->toBeTrue();
        });
    });

    describe('method chaining', function () {
        it('supports fluent interface', function () {
            Http::fake([
                '*' => Http::response(['ok' => true], 200),
            ]);

            $response = $this->client
                ->connection('default')
                ->instance('my-instance')
                ->withHeaders(['X-Custom' => 'value'])
                ->throwOnError(false)
                ->get('/test');

            expect($response)->toBeInstanceOf(ApiResponse::class);
        });
    });

    describe('logger integration', function () {
        it('can set logger', function () {
            $logger = new class implements \Psr\Log\LoggerInterface
            {
                public array $logs = [];

                public function emergency(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['emergency', $message];
                }

                public function alert(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['alert', $message];
                }

                public function critical(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['critical', $message];
                }

                public function error(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['error', $message];
                }

                public function warning(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['warning', $message];
                }

                public function notice(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['notice', $message];
                }

                public function info(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['info', $message];
                }

                public function debug(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['debug', $message];
                }

                public function log($level, \Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = [$level, $message];
                }
            };

            $result = $this->client->setLogger($logger);

            expect($result)->toBe($this->client);
        });
    });

});
