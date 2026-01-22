<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Client\EvolutionClient;
use Lynkbyte\EvolutionApi\DTOs\ApiResponse;
use Lynkbyte\EvolutionApi\Exceptions\InstanceNotFoundException;
use Lynkbyte\EvolutionApi\Resources\Resource;

/**
 * Testable concrete subclass of Resource to access protected methods.
 */
class TestableResource extends Resource
{
    // Expose protected methods for testing
    public function callGet(string $endpoint, array $query = []): ApiResponse
    {
        return $this->get($endpoint, $query);
    }

    public function callPost(string $endpoint, array $data = []): ApiResponse
    {
        return $this->post($endpoint, $data);
    }

    public function callPut(string $endpoint, array $data = []): ApiResponse
    {
        return $this->put($endpoint, $data);
    }

    public function callDelete(string $endpoint, array $data = []): ApiResponse
    {
        return $this->delete($endpoint, $data);
    }

    public function callPatch(string $endpoint, array $data = []): ApiResponse
    {
        return $this->patch($endpoint, $data);
    }

    public function callInstanceEndpoint(string $path): string
    {
        return $this->instanceEndpoint($path);
    }

    public function callBuildInstancePath(string $basePath, string $suffix = ''): string
    {
        return $this->buildInstancePath($basePath, $suffix);
    }

    public function callFilterNull(array $data): array
    {
        return $this->filterNull($data);
    }

    public function callBuildPayload(array $data, array $optionalFields = []): array
    {
        return $this->buildPayload($data, $optionalFields);
    }

    public function callFormatPhoneNumber(string $number): string
    {
        return $this->formatPhoneNumber($number);
    }

    public function callFormatRemoteJid(string $number): string
    {
        return $this->formatRemoteJid($number);
    }

    public function callFormatGroupJid(string $groupId): string
    {
        return $this->formatGroupJid($groupId);
    }

    public function callEnsureInstance(): void
    {
        $this->ensureInstance();
    }

    public function callGetInstanceName(): ?string
    {
        return $this->getInstanceName();
    }
}

describe('Resource Base Class', function () {
    beforeEach(function () {
        Http::preventStrayRequests();

        $config = [
            'connections' => [
                'default' => [
                    'server_url' => 'https://api.evolution.test',
                    'api_key' => 'test-api-key',
                ],
            ],
            'retry' => ['enabled' => false],
        ];

        $this->connectionManager = new ConnectionManager($config);
        $this->client = new EvolutionClient($this->connectionManager);
        $this->resource = new TestableResource($this->client);
    });

    describe('constructor', function () {
        it('accepts EvolutionClient', function () {
            expect($this->resource)->toBeInstanceOf(Resource::class);
        });
    });

    describe('getClient()', function () {
        it('returns the underlying client', function () {
            expect($this->resource->getClient())->toBe($this->client);
        });
    });

    describe('instance()', function () {
        it('sets instance name and returns self', function () {
            $result = $this->resource->instance('my-instance');

            expect($result)->toBe($this->resource);
            expect($this->resource->callGetInstanceName())->toBe('my-instance');
        });
    });

    describe('getInstanceName()', function () {
        it('returns null when no instance set', function () {
            expect($this->resource->callGetInstanceName())->toBeNull();
        });

        it('returns instance name when set', function () {
            $this->resource->instance('test-instance');
            expect($this->resource->callGetInstanceName())->toBe('test-instance');
        });
    });

    describe('HTTP methods', function () {
        beforeEach(function () {
            $this->resource->instance('test-instance');
        });

        it('makes GET request', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['data' => 'test'], 200),
            ]);

            $response = $this->resource->callGet('/test');

            expect($response)->toBeInstanceOf(ApiResponse::class);
            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return $request->method() === 'GET';
            });
        });

        it('makes GET request with query params', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['data' => []], 200),
            ]);

            $this->resource->callGet('/test', ['status' => 'open', 'page' => 1]);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'status=open') &&
                    str_contains($request->url(), 'page=1');
            });
        });

        it('makes POST request', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['ok' => true], 200),
            ]);

            $response = $this->resource->callPost('/test', ['key' => 'value']);

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return $request->method() === 'POST' &&
                    $request['key'] === 'value';
            });
        });

        it('makes PUT request', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['updated' => true], 200),
            ]);

            $response = $this->resource->callPut('/test', ['field' => 'new-value']);

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return $request->method() === 'PUT' &&
                    $request['field'] === 'new-value';
            });
        });

        it('makes DELETE request', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['deleted' => true], 200),
            ]);

            $response = $this->resource->callDelete('/test', ['id' => 123]);

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return $request->method() === 'DELETE';
            });
        });

        it('makes PATCH request', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['patched' => true], 200),
            ]);

            $response = $this->resource->callPatch('/test', ['status' => 'active']);

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return $request->method() === 'PATCH' &&
                    $request['status'] === 'active';
            });
        });
    });

    describe('instanceEndpoint()', function () {
        it('builds endpoint path with instance placeholder', function () {
            $result = $this->resource->callInstanceEndpoint('/message/sendText');
            expect($result)->toBe('/message/sendText/{instance}');
        });

        it('handles empty path', function () {
            $result = $this->resource->callInstanceEndpoint('');
            expect($result)->toBe('/{instance}');
        });
    });

    describe('buildInstancePath()', function () {
        it('builds path with base path and instance placeholder', function () {
            $result = $this->resource->callBuildInstancePath('/chat');
            expect($result)->toBe('/chat/{instance}');
        });

        it('builds path with suffix', function () {
            $result = $this->resource->callBuildInstancePath('/chat', 'messages');
            expect($result)->toBe('/chat/{instance}/messages');
        });

        it('removes leading slash from suffix', function () {
            $result = $this->resource->callBuildInstancePath('/chat', '/messages');
            expect($result)->toBe('/chat/{instance}/messages');
        });

        it('handles empty suffix', function () {
            $result = $this->resource->callBuildInstancePath('/instance', '');
            expect($result)->toBe('/instance/{instance}');
        });
    });

    describe('filterNull()', function () {
        it('removes null values from array', function () {
            $result = $this->resource->callFilterNull([
                'name' => 'John',
                'email' => null,
                'age' => 30,
                'phone' => null,
            ]);

            expect($result)->toBe(['name' => 'John', 'age' => 30]);
        });

        it('keeps false and empty string values', function () {
            $result = $this->resource->callFilterNull([
                'active' => false,
                'name' => '',
                'count' => 0,
                'removed' => null,
            ]);

            expect($result)->toBe([
                'active' => false,
                'name' => '',
                'count' => 0,
            ]);
        });

        it('returns empty array when all values are null', function () {
            $result = $this->resource->callFilterNull([
                'a' => null,
                'b' => null,
            ]);

            expect($result)->toBe([]);
        });
    });

    describe('buildPayload()', function () {
        it('filters null values from payload', function () {
            $result = $this->resource->callBuildPayload([
                'number' => '5511999999999',
                'text' => 'Hello',
                'delay' => null,
            ]);

            expect($result)->toBe([
                'number' => '5511999999999',
                'text' => 'Hello',
            ]);
        });

        it('includes false values for optional fields', function () {
            $result = $this->resource->callBuildPayload([
                'number' => '5511999999999',
                'linkPreview' => false,
                'delay' => null,
            ], ['linkPreview']);

            expect($result)->toBe([
                'number' => '5511999999999',
                'linkPreview' => false,
            ]);
        });

        it('does not add optional fields if not in data', function () {
            $result = $this->resource->callBuildPayload([
                'number' => '5511999999999',
            ], ['linkPreview']);

            expect($result)->toBe([
                'number' => '5511999999999',
            ]);
        });

        it('handles multiple optional fields', function () {
            $result = $this->resource->callBuildPayload([
                'number' => '5511999999999',
                'linkPreview' => false,
                'mentions' => false,
                'notify' => null,
            ], ['linkPreview', 'mentions', 'notify']);

            expect($result)->toHaveKey('linkPreview');
            expect($result)->toHaveKey('mentions');
            expect($result)->not->toHaveKey('notify');
            expect($result['linkPreview'])->toBeFalse();
            expect($result['mentions'])->toBeFalse();
        });
    });

    describe('formatPhoneNumber()', function () {
        it('removes non-numeric characters', function () {
            $result = $this->resource->callFormatPhoneNumber('+55 (11) 99999-9999');
            expect($result)->toBe('5511999999999');
        });

        it('returns original if contains @', function () {
            $result = $this->resource->callFormatPhoneNumber('5511999999999@s.whatsapp.net');
            expect($result)->toBe('5511999999999@s.whatsapp.net');
        });

        it('handles clean number', function () {
            $result = $this->resource->callFormatPhoneNumber('5511999999999');
            expect($result)->toBe('5511999999999');
        });
    });

    describe('formatRemoteJid()', function () {
        it('adds WhatsApp suffix to number', function () {
            $result = $this->resource->callFormatRemoteJid('5511999999999');
            expect($result)->toBe('5511999999999@s.whatsapp.net');
        });

        it('returns original if already formatted', function () {
            $result = $this->resource->callFormatRemoteJid('5511999999999@s.whatsapp.net');
            expect($result)->toBe('5511999999999@s.whatsapp.net');
        });

        it('cleans and formats number with special characters', function () {
            $result = $this->resource->callFormatRemoteJid('+55 (11) 99999-9999');
            expect($result)->toBe('5511999999999@s.whatsapp.net');
        });
    });

    describe('formatGroupJid()', function () {
        it('adds group suffix to ID', function () {
            $result = $this->resource->callFormatGroupJid('123456789');
            expect($result)->toBe('123456789@g.us');
        });

        it('returns original if already formatted', function () {
            $result = $this->resource->callFormatGroupJid('123456789@g.us');
            expect($result)->toBe('123456789@g.us');
        });
    });

    describe('ensureInstance()', function () {
        it('throws InstanceNotFoundException when no instance is set', function () {
            expect(fn () => $this->resource->callEnsureInstance())
                ->toThrow(InstanceNotFoundException::class, 'No instance selected');
        });

        it('does not throw when instance is set', function () {
            $this->resource->instance('my-instance');

            // Should not throw
            $this->resource->callEnsureInstance();

            expect(true)->toBeTrue(); // Test passes if no exception thrown
        });
    });
});
