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

        it('logs requests when logger is set', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $logger = new class implements \Psr\Log\LoggerInterface
            {
                public array $logs = [];

                public function emergency(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['emergency', $message, $context];
                }

                public function alert(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['alert', $message, $context];
                }

                public function critical(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['critical', $message, $context];
                }

                public function error(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['error', $message, $context];
                }

                public function warning(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['warning', $message, $context];
                }

                public function notice(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['notice', $message, $context];
                }

                public function info(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['info', $message, $context];
                }

                public function debug(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['debug', $message, $context];
                }

                public function log($level, \Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = [$level, $message, $context];
                }
            };

            $this->client->setLogger($logger);
            $this->client->get('/test');

            // Should have logged request and response
            expect(count($logger->logs))->toBeGreaterThanOrEqual(2);
            expect($logger->logs[0][1])->toBe('Evolution API Request');
            expect($logger->logs[1][1])->toBe('Evolution API Response');
        });

        it('logs error responses with error level', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['error' => 'Bad request'], 400),
            ]);

            $logger = new class implements \Psr\Log\LoggerInterface
            {
                public array $logs = [];

                public function emergency(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['emergency', $message, $context];
                }

                public function alert(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['alert', $message, $context];
                }

                public function critical(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['critical', $message, $context];
                }

                public function error(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['error', $message, $context];
                }

                public function warning(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['warning', $message, $context];
                }

                public function notice(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['notice', $message, $context];
                }

                public function info(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['info', $message, $context];
                }

                public function debug(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['debug', $message, $context];
                }

                public function log($level, \Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = [$level, $message, $context];
                }
            };

            $this->client->setLogger($logger);
            $this->client->withoutThrowing()->get('/test');

            // Response should be logged as error
            $responseLogs = array_filter($logger->logs, fn ($log) => $log[1] === 'Evolution API Response');
            $responseLog = array_values($responseLogs)[0];
            expect($responseLog[0])->toBe('error');
        });

        it('respects log_requests config', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://api.evolution.test',
                        'api_key' => 'test-api-key',
                    ],
                ],
                'logging' => [
                    'log_requests' => false,
                    'log_responses' => true,
                ],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            $logger = new class implements \Psr\Log\LoggerInterface
            {
                public array $logs = [];

                public function emergency(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['emergency', $message, $context];
                }

                public function alert(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['alert', $message, $context];
                }

                public function critical(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['critical', $message, $context];
                }

                public function error(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['error', $message, $context];
                }

                public function warning(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['warning', $message, $context];
                }

                public function notice(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['notice', $message, $context];
                }

                public function info(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['info', $message, $context];
                }

                public function debug(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['debug', $message, $context];
                }

                public function log($level, \Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = [$level, $message, $context];
                }
            };

            $client->setLogger($logger);
            $client->get('/test');

            // Should only have response log, not request log
            $requestLogs = array_filter($logger->logs, fn ($log) => $log[1] === 'Evolution API Request');
            expect(count($requestLogs))->toBe(0);
        });

        it('respects log_responses config', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://api.evolution.test',
                        'api_key' => 'test-api-key',
                    ],
                ],
                'logging' => [
                    'log_requests' => true,
                    'log_responses' => false,
                ],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            $logger = new class implements \Psr\Log\LoggerInterface
            {
                public array $logs = [];

                public function emergency(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['emergency', $message, $context];
                }

                public function alert(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['alert', $message, $context];
                }

                public function critical(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['critical', $message, $context];
                }

                public function error(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['error', $message, $context];
                }

                public function warning(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['warning', $message, $context];
                }

                public function notice(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['notice', $message, $context];
                }

                public function info(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['info', $message, $context];
                }

                public function debug(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['debug', $message, $context];
                }

                public function log($level, \Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = [$level, $message, $context];
                }
            };

            $client->setLogger($logger);
            $client->get('/test');

            // Should only have request log, not response log
            $responseLogs = array_filter($logger->logs, fn ($log) => $log[1] === 'Evolution API Response');
            expect(count($responseLogs))->toBe(0);
        });

        it('redacts sensitive fields when configured', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $logger = new class implements \Psr\Log\LoggerInterface
            {
                public array $logs = [];

                public function emergency(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['emergency', $message, $context];
                }

                public function alert(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['alert', $message, $context];
                }

                public function critical(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['critical', $message, $context];
                }

                public function error(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['error', $message, $context];
                }

                public function warning(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['warning', $message, $context];
                }

                public function notice(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['notice', $message, $context];
                }

                public function info(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['info', $message, $context];
                }

                public function debug(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['debug', $message, $context];
                }

                public function log($level, \Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = [$level, $message, $context];
                }
            };

            $this->client->setLogger($logger);
            $this->client->post('/test', ['token' => 'secret-token', 'data' => 'value']);

            $requestLogs = array_filter($logger->logs, fn ($log) => $log[1] === 'Evolution API Request');
            $requestLog = array_values($requestLogs)[0];

            // Token should be redacted
            expect($requestLog[2]['options']['json']['token'])->toBe('[REDACTED]');
            expect($requestLog[2]['options']['json']['data'])->toBe('value');
        });

        it('does not redact when redact_sensitive is disabled', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://api.evolution.test',
                        'api_key' => 'test-api-key',
                    ],
                ],
                'logging' => [
                    'log_requests' => true,
                    'log_responses' => true,
                    'redact_sensitive' => false,
                    'sensitive_fields' => ['token'],
                ],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            $logger = new class implements \Psr\Log\LoggerInterface
            {
                public array $logs = [];

                public function emergency(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['emergency', $message, $context];
                }

                public function alert(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['alert', $message, $context];
                }

                public function critical(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['critical', $message, $context];
                }

                public function error(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['error', $message, $context];
                }

                public function warning(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['warning', $message, $context];
                }

                public function notice(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['notice', $message, $context];
                }

                public function info(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['info', $message, $context];
                }

                public function debug(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['debug', $message, $context];
                }

                public function log($level, \Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = [$level, $message, $context];
                }
            };

            $client->setLogger($logger);
            $client->post('/test', ['token' => 'secret-token']);

            $requestLogs = array_filter($logger->logs, fn ($log) => $log[1] === 'Evolution API Request');
            $requestLog = array_values($requestLogs)[0];

            // Token should NOT be redacted
            expect($requestLog[2]['options']['json']['token'])->toBe('secret-token');
        });

        it('redacts sensitive fields in deeply nested arrays', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $logger = new class implements \Psr\Log\LoggerInterface
            {
                public array $logs = [];

                public function emergency(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['emergency', $message, $context];
                }

                public function alert(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['alert', $message, $context];
                }

                public function critical(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['critical', $message, $context];
                }

                public function error(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['error', $message, $context];
                }

                public function warning(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['warning', $message, $context];
                }

                public function notice(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['notice', $message, $context];
                }

                public function info(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['info', $message, $context];
                }

                public function debug(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['debug', $message, $context];
                }

                public function log($level, \Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = [$level, $message, $context];
                }
            };

            $this->client->setLogger($logger);
            $this->client->post('/test', [
                'data' => 'value',
                'nested' => [
                    'token' => 'nested-secret',
                    'deep' => [
                        'apikey' => 'deeply-nested-secret',
                        'safe' => 'visible',
                    ],
                ],
            ]);

            $requestLogs = array_filter($logger->logs, fn ($log) => $log[1] === 'Evolution API Request');
            $requestLog = array_values($requestLogs)[0];

            // Top-level data should remain
            expect($requestLog[2]['options']['json']['data'])->toBe('value');
            // Nested token should be redacted
            expect($requestLog[2]['options']['json']['nested']['token'])->toBe('[REDACTED]');
            // Deeply nested apikey should be redacted
            expect($requestLog[2]['options']['json']['nested']['deep']['apikey'])->toBe('[REDACTED]');
            // Safe data should remain
            expect($requestLog[2]['options']['json']['nested']['deep']['safe'])->toBe('visible');
        });

        it('includes instance name in logs when set', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $logger = new class implements \Psr\Log\LoggerInterface
            {
                public array $logs = [];

                public function emergency(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['emergency', $message, $context];
                }

                public function alert(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['alert', $message, $context];
                }

                public function critical(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['critical', $message, $context];
                }

                public function error(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['error', $message, $context];
                }

                public function warning(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['warning', $message, $context];
                }

                public function notice(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['notice', $message, $context];
                }

                public function info(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['info', $message, $context];
                }

                public function debug(\Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = ['debug', $message, $context];
                }

                public function log($level, \Stringable|string $message, array $context = []): void
                {
                    $this->logs[] = [$level, $message, $context];
                }
            };

            $this->client->setLogger($logger);
            $this->client->instance('my-instance')->get('/test');

            $requestLogs = array_filter($logger->logs, fn ($log) => $log[1] === 'Evolution API Request');
            $requestLog = array_values($requestLogs)[0];

            expect($requestLog[2]['instance'])->toBe('my-instance');
        });
    });

    describe('rate limit type detection', function () {
        it('detects media endpoints', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $cache = new CacheRepository(new ArrayStore);
            $rateLimiter = new RateLimiter($cache, [
                'enabled' => true,
                'on_limit_reached' => 'skip',
                'limits' => [
                    'media' => ['max_attempts' => 5, 'decay_seconds' => 60],
                ],
            ]);

            $client = new EvolutionClient($this->connectionManager, $rateLimiter);

            // Make requests to media endpoints
            $client->post('/sendMedia/test', []);
            $client->post('/sendImage/test', []);
            $client->post('/sendVideo/test', []);
            $client->post('/sendAudio/test', []);
            $client->post('/sendDocument/test', []);

            // Should have used media rate limit (5 attempts)
            expect($rateLimiter->remaining('default', 'media'))->toBe(0);
        });

        it('detects message endpoints', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $cache = new CacheRepository(new ArrayStore);
            $rateLimiter = new RateLimiter($cache, [
                'enabled' => true,
                'on_limit_reached' => 'skip',
                'limits' => [
                    'messages' => ['max_attempts' => 3, 'decay_seconds' => 60],
                ],
            ]);

            $client = new EvolutionClient($this->connectionManager, $rateLimiter);

            // Make requests to message endpoints
            $client->post('/send/test', []);
            $client->post('/message/send/test', []);
            $client->post('/sendText/test', []);

            // Should have used messages rate limit (3 attempts)
            expect($rateLimiter->remaining('default', 'messages'))->toBe(0);
        });

        it('uses default rate limit for other endpoints', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $cache = new CacheRepository(new ArrayStore);
            $rateLimiter = new RateLimiter($cache, [
                'enabled' => true,
                'on_limit_reached' => 'skip',
                'limits' => [
                    'default' => ['max_attempts' => 2, 'decay_seconds' => 60],
                ],
            ]);

            $client = new EvolutionClient($this->connectionManager, $rateLimiter);

            // Make requests to non-media/message endpoints
            $client->get('/instance/list');
            $client->get('/settings/test');

            // Should have used default rate limit (2 attempts)
            expect($rateLimiter->remaining('default', 'default'))->toBe(0);
        });

        it('includes instance in rate limit key when set', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $cache = new CacheRepository(new ArrayStore);
            $rateLimiter = new RateLimiter($cache, [
                'enabled' => true,
                'limits' => [
                    'default' => ['max_attempts' => 60, 'decay_seconds' => 60],
                ],
            ]);

            $client = new EvolutionClient($this->connectionManager, $rateLimiter);

            // Make request without instance
            $client->get('/test');
            expect($rateLimiter->remaining('default', 'default'))->toBe(59);

            // Make request with instance
            $client->instance('my-instance')->get('/test');
            expect($rateLimiter->remaining('default:my-instance', 'default'))->toBe(59);
        });
    });

    describe('rate limit throws exception', function () {
        it('throws RateLimitException when rate limiter is configured to throw', function () {
            $cache = new CacheRepository(new ArrayStore);
            $rateLimiter = new RateLimiter($cache, [
                'enabled' => true,
                'on_limit_reached' => 'throw',
                'limits' => [
                    'default' => ['max_attempts' => 1, 'decay_seconds' => 60],
                ],
            ]);

            $client = new EvolutionClient($this->connectionManager, $rateLimiter);

            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            // First request should succeed
            $client->get('/test');

            // Second request should throw rate limit exception
            expect(fn () => $client->get('/test'))
                ->toThrow(RateLimitException::class);
        });
    });

    describe('upload error handling', function () {
        it('throws AuthenticationException on upload 401', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Invalid API key',
                ], 401),
            ]);

            expect(fn () => $this->client->upload('/sendMedia/test', [], []))
                ->toThrow(AuthenticationException::class);
        });

        it('throws InstanceNotFoundException on upload 404', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Instance not found',
                ], 404),
            ]);

            expect(fn () => $this->client->upload('/sendMedia/test', [], []))
                ->toThrow(InstanceNotFoundException::class);
        });

        it('throws RateLimitException on upload 429', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Too many requests',
                ], 429, ['Retry-After' => '60']),
            ]);

            expect(fn () => $this->client->upload('/sendMedia/test', [], []))
                ->toThrow(RateLimitException::class);
        });

        it('throws ConnectionException on upload connection failure', function () {
            Http::fake([
                'api.evolution.test/*' => function () {
                    throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
                },
            ]);

            expect(fn () => $this->client->upload('/sendMedia/test', [], []))
                ->toThrow(ConnectionException::class);
        });

        it('can disable throwing on upload errors', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['error' => 'Bad request'], 400),
            ]);

            $response = $this->client
                ->throwOnError(false)
                ->upload('/sendMedia/test', [], []);

            expect($response->isSuccessful())->toBeFalse();
            expect($response->statusCode)->toBe(400);
        });

        it('uploads multiple files', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $response = $this->client->upload(
                '/sendMedia/test',
                ['number' => '5511999999999', 'caption' => 'Multiple files'],
                [
                    ['name' => 'file1', 'contents' => 'content1', 'filename' => 'file1.jpg'],
                    ['name' => 'file2', 'contents' => 'content2', 'filename' => 'file2.jpg'],
                ]
            );

            expect($response->isSuccessful())->toBeTrue();
        });

        it('uploads file without filename', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $response = $this->client->upload(
                '/sendMedia/test',
                [],
                [
                    ['name' => 'file', 'contents' => 'content'],
                ]
            );

            expect($response->isSuccessful())->toBeTrue();
        });

        it('rethrows EvolutionApiException during upload', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Server error',
                ], 500),
            ]);

            expect(fn () => $this->client->upload('/sendMedia/test', [], []))
                ->toThrow(EvolutionApiException::class);
        });
    });

    describe('SSL verification', function () {
        it('disables SSL verification when configured', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://api.evolution.test',
                        'api_key' => 'test-api-key',
                    ],
                ],
                'http' => [
                    'verify_ssl' => false,
                ],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            $response = $client->get('/test');

            expect($response->isSuccessful())->toBeTrue();
        });
    });

    describe('API response message handling', function () {
        it('extracts message from response', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Operation successful',
                ], 200),
            ]);

            $response = $this->client->get('/test');

            expect($response->message)->toBe('Operation successful');
        });

        it('converts non-string message to JSON', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => ['error' => 'details'],
                ], 200),
            ]);

            $response = $this->client->get('/test');

            expect($response->message)->toBe('{"error":"details"}');
        });

        it('uses reason phrase for error responses without message', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'data' => [],
                ], 400),
            ]);

            $response = $this->client->withoutThrowing()->get('/test');

            expect($response->message)->toBe('Bad Request');
        });
    });

    describe('exception details', function () {
        it('includes instance name in exceptions', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Instance not found',
                ], 404),
            ]);

            try {
                $this->client->instance('my-instance')->get('/test');
                $this->fail('Expected InstanceNotFoundException');
            } catch (InstanceNotFoundException $e) {
                expect($e->getInstanceName())->toBe('my-instance');
            }
        });

        it('includes retry after in rate limit exception', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Rate limited',
                ], 429, ['Retry-After' => '30']),
            ]);

            try {
                $this->client->get('/test');
                $this->fail('Expected RateLimitException');
            } catch (RateLimitException $e) {
                expect($e->getRetryAfter())->toBe(30);
            }
        });

        it('handles error response status code in exception', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Server error',
                ], 503),
            ]);

            try {
                $this->client->get('/test');
                $this->fail('Expected EvolutionApiException');
            } catch (EvolutionApiException $e) {
                expect($e->getStatusCode())->toBe(503);
            }
        });
    });

    describe('retry logic', function () {
        it('retries on retryable status codes', function () {
            $attempts = 0;

            Http::fake([
                'api.evolution.test/*' => function () use (&$attempts) {
                    $attempts++;
                    if ($attempts < 3) {
                        return Http::response(['error' => 'Server error'], 503);
                    }

                    return Http::response(['ok' => true], 200);
                },
            ]);

            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://api.evolution.test',
                        'api_key' => 'test-api-key',
                    ],
                ],
                'retry' => [
                    'enabled' => true,
                    'max_attempts' => 3,
                    'base_delay' => 10, // 10ms for fast test
                    'retryable_status_codes' => [503],
                ],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            $response = $client->get('/test');

            expect($response->isSuccessful())->toBeTrue();
            expect($attempts)->toBe(3);
        });

        it('retries on connection exceptions', function () {
            $attempts = 0;

            Http::fake([
                'api.evolution.test/*' => function () use (&$attempts) {
                    $attempts++;
                    if ($attempts < 3) {
                        throw new \Illuminate\Http\Client\ConnectionException('Connection failed');
                    }

                    return Http::response(['ok' => true], 200);
                },
            ]);

            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://api.evolution.test',
                        'api_key' => 'test-api-key',
                    ],
                ],
                'retry' => [
                    'enabled' => true,
                    'max_attempts' => 3,
                    'base_delay' => 10, // 10ms for fast test
                    'retryable_status_codes' => [503],
                ],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            $response = $client->get('/test');

            expect($response->isSuccessful())->toBeTrue();
            expect($attempts)->toBe(3);
        });

        it('does not retry on non-retryable status codes', function () {
            $attempts = 0;

            Http::fake([
                'api.evolution.test/*' => function () use (&$attempts) {
                    $attempts++;

                    return Http::response(['error' => 'Bad request'], 400);
                },
            ]);

            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://api.evolution.test',
                        'api_key' => 'test-api-key',
                    ],
                ],
                'retry' => [
                    'enabled' => true,
                    'max_attempts' => 3,
                    'base_delay' => 10,
                    'retryable_status_codes' => [503],
                ],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            $response = $client->withoutThrowing()->get('/test');

            expect($response->isFailed())->toBeTrue();
            // Should only attempt once since 400 is not in retryable list
            expect($attempts)->toBe(1);
        });
    });

    describe('convertRequestException', function () {
        it('converts RequestException without response to ConnectionException', function () {
            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://api.evolution.test',
                        'api_key' => 'test-api-key',
                    ],
                ],
                'retry' => ['enabled' => false],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            // Simulate a connection error (no response)
            Http::fake([
                'api.evolution.test/*' => function () {
                    throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
                },
            ]);

            expect(fn () => $client->get('/test'))
                ->toThrow(ConnectionException::class, 'Failed to connect');
        });

        it('handles connection failures on upload', function () {
            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://api.evolution.test',
                        'api_key' => 'test-api-key',
                    ],
                ],
                'retry' => ['enabled' => false],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            Http::fake([
                'api.evolution.test/*' => function () {
                    throw new \Illuminate\Http\Client\ConnectionException('Upload failed');
                },
            ]);

            expect(fn () => $client->upload('/sendMedia/test', [], []))
                ->toThrow(ConnectionException::class);
        });

        it('properly handles error responses with instance context', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => 'Rate limited',
                ], 429, ['Retry-After' => '45']),
            ]);

            try {
                $this->client->instance('my-instance')->get('/test');
                test()->fail('Expected RateLimitException');
            } catch (RateLimitException $e) {
                // The createExceptionFromResponse handles this case
                expect($e->getInstanceName())->toBe('my-instance');
                expect($e->getRetryAfter())->toBe(45);
            }
        });

        it('handles generic throwable during request as ConnectionException', function () {
            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://api.evolution.test',
                        'api_key' => 'test-api-key',
                    ],
                ],
                'retry' => ['enabled' => false],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            Http::fake([
                'api.evolution.test/*' => function () {
                    throw new \RuntimeException('Unexpected error');
                },
            ]);

            expect(fn () => $client->get('/test'))
                ->toThrow(ConnectionException::class, 'Failed to connect');
        });

        it('rethrows EvolutionApiException without wrapping', function () {
            $config = [
                'connections' => [
                    'default' => [
                        'server_url' => 'https://api.evolution.test',
                        'api_key' => 'test-api-key',
                    ],
                ],
                'retry' => ['enabled' => false],
            ];

            $connectionManager = new ConnectionManager($config);
            $client = new EvolutionClient($connectionManager);

            Http::fake([
                'api.evolution.test/*' => function () {
                    throw new AuthenticationException('Already an auth exception');
                },
            ]);

            expect(fn () => $client->get('/test'))
                ->toThrow(AuthenticationException::class, 'Already an auth exception');
        });
    });

});
