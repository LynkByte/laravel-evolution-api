<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Client\EvolutionClient;
use Lynkbyte\EvolutionApi\DTOs\ApiResponse;
use Lynkbyte\EvolutionApi\Enums\InstanceStatus;
use Lynkbyte\EvolutionApi\Resources\Instance;

describe('Instance Resource', function () {

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
        $this->resource = new Instance($this->client);
    });

    describe('create', function () {
        it('creates a new instance', function () {
            Http::fake([
                'api.evolution.test/instance/create' => Http::response([
                    'instance' => [
                        'instanceName' => 'new-instance',
                        'status' => 'created',
                    ],
                ], 200),
            ]);

            $response = $this->resource->create('new-instance');

            expect($response)->toBeInstanceOf(ApiResponse::class);
            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return $request->method() === 'POST' &&
                    str_contains($request->url(), 'instance/create') &&
                    $request['instanceName'] === 'new-instance';
            });
        });

        it('creates instance with all options', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['instance' => []], 200),
            ]);

            $this->resource->create(
                instanceName: 'my-instance',
                token: 'secret-token',
                qrcode: 1,
                integration: 'WHATSAPP-BAILEYS',
                number: '5511999999999',
                businessId: 'business-123',
                options: ['webhook' => 'https://example.com/webhook']
            );

            Http::assertSent(function (Request $request) {
                return $request['instanceName'] === 'my-instance' &&
                    $request['token'] === 'secret-token' &&
                    $request['qrcode'] === 1 &&
                    $request['integration'] === 'WHATSAPP-BAILEYS' &&
                    $request['number'] === '5511999999999' &&
                    $request['businessId'] === 'business-123' &&
                    $request['webhook'] === 'https://example.com/webhook';
            });
        });
    });

    describe('connect', function () {
        it('connects to instance', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'qrcode' => ['base64' => 'data:image/png;base64,...'],
                ], 200),
            ]);

            $response = $this->resource->connect('my-instance');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'instance/connect/my-instance');
            });
        });
    });

    describe('connectionState', function () {
        it('gets connection state', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'instance' => [
                        'instanceName' => 'test',
                        'state' => 'open',
                    ],
                ], 200),
            ]);

            $response = $this->resource->connectionState('test-instance');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'instance/connectionState/test-instance');
            });
        });
    });

    describe('fetchAll', function () {
        it('fetches all instances', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    ['instance' => ['instanceName' => 'instance-1']],
                    ['instance' => ['instanceName' => 'instance-2']],
                ], 200),
            ]);

            $response = $this->resource->fetchAll();

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'instance/fetchInstances');
            });
        });
    });

    describe('fetch', function () {
        it('fetches specific instance', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'instance' => ['instanceName' => 'my-instance'],
                ], 200),
            ]);

            $response = $this->resource->fetch('my-instance');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'instanceName=my-instance');
            });
        });
    });

    describe('setPresence', function () {
        it('sets presence status', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setPresence('available', 'test-instance');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'instance/setPresence/test-instance') &&
                    $request['presence'] === 'available';
            });
        });
    });

    describe('restart', function () {
        it('restarts instance', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'restarted'], 200),
            ]);

            $this->resource->restart('my-instance');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'PUT' &&
                    str_contains($request->url(), 'instance/restart/my-instance');
            });
        });
    });

    describe('logout', function () {
        it('logs out from instance', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'logged_out'], 200),
            ]);

            $this->resource->logout('my-instance');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'DELETE' &&
                    str_contains($request->url(), 'instance/logout/my-instance');
            });
        });
    });

    describe('remove', function () {
        it('removes instance', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'deleted'], 200),
            ]);

            $this->resource->remove('my-instance');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'DELETE' &&
                    str_contains($request->url(), 'instance/delete/my-instance');
            });
        });
    });

    describe('getQrCode', function () {
        it('gets QR code', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'base64' => 'data:image/png;base64,ABC123',
                    'code' => 'qr-code-string',
                ], 200),
            ]);

            $response = $this->resource->getQrCode('my-instance');

            expect($response->isSuccessful())->toBeTrue();
            expect($response->get('base64'))->toContain('data:image/png;base64');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'instance/qrcode/my-instance');
            });
        });
    });

    describe('getQrCodeBase64', function () {
        it('gets QR code as base64', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'base64' => 'data:image/png;base64,ABC123',
                ], 200),
            ]);

            $response = $this->resource->getQrCodeBase64('my-instance');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'instance/qrcode-base64/my-instance');
            });
        });
    });

    describe('isConnected', function () {
        it('returns true when connected', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'state' => 'open',
                ], 200),
            ]);

            expect($this->resource->isConnected('my-instance'))->toBeTrue();
        });

        it('returns true when state is in instance object', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'instance' => ['state' => 'open'],
                ], 200),
            ]);

            expect($this->resource->isConnected('my-instance'))->toBeTrue();
        });

        it('returns false when disconnected', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'state' => 'close',
                ], 200),
            ]);

            expect($this->resource->isConnected('my-instance'))->toBeFalse();
        });

        it('returns false on error', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['error' => 'Not found'], 404),
            ]);

            expect($this->resource->isConnected('my-instance'))->toBeFalse();
        });
    });

    describe('getStatus', function () {
        it('returns connected status', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['state' => 'open'], 200),
            ]);

            $status = $this->resource->getStatus('my-instance');

            expect($status)->toBe(InstanceStatus::OPEN);
        });

        it('returns unknown status on error', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['error' => 'Error'], 500),
            ]);

            $status = $this->resource->getStatus('my-instance');

            expect($status)->toBe(InstanceStatus::UNKNOWN);
        });

        it('returns unknown for unrecognized status', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['state' => 'invalid-state'], 200),
            ]);

            $status = $this->resource->getStatus('my-instance');

            expect($status)->toBe(InstanceStatus::UNKNOWN);
        });
    });

    describe('updateSettings', function () {
        it('updates instance settings', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->updateSettings([
                'reject_call' => true,
                'msg_call' => 'Sorry, I cannot answer calls.',
            ], 'my-instance');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'PUT' &&
                    str_contains($request->url(), 'instance/settings/my-instance') &&
                    $request['reject_call'] === true;
            });
        });
    });

    describe('getSettings', function () {
        it('gets instance settings', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'reject_call' => false,
                    'groups_ignore' => true,
                ], 200),
            ]);

            $response = $this->resource->getSettings('my-instance');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'instance/settings/my-instance');
            });
        });
    });

    describe('refreshQrCode', function () {
        it('refreshes QR code', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'base64' => 'data:image/png;base64,NEW_QR',
                ], 200),
            ]);

            $response = $this->resource->refreshQrCode('my-instance');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return $request->method() === 'POST' &&
                    str_contains($request->url(), 'instance/refreshQrCode/my-instance');
            });
        });
    });

    describe('connectWithNumber', function () {
        it('connects with phone number', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'connecting'], 200),
            ]);

            $this->resource->connectWithNumber('5511999999999', 'my-instance');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'POST' &&
                    str_contains($request->url(), 'instance/connect/my-instance') &&
                    $request['number'] === '5511999999999';
            });
        });
    });

    describe('verifyCode', function () {
        it('verifies pairing code', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'verified'], 200),
            ]);

            $this->resource->verifyCode('12345678', 'my-instance');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'POST' &&
                    str_contains($request->url(), 'instance/verifyCode/my-instance') &&
                    $request['code'] === '12345678';
            });
        });
    });

    describe('instance chaining', function () {
        it('uses default instance from client', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['state' => 'open'], 200),
            ]);

            $this->client->instance('default-instance');
            $resource = new Instance($this->client);

            $resource->connectionState();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'connectionState/default-instance');
            });
        });
    });

});
