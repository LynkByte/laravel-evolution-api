<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Resources\Chat;
use Lynkbyte\EvolutionApi\Resources\Group;
use Lynkbyte\EvolutionApi\Resources\Instance;
use Lynkbyte\EvolutionApi\Resources\Message;
use Lynkbyte\EvolutionApi\Resources\Profile;
use Lynkbyte\EvolutionApi\Resources\Settings;
use Lynkbyte\EvolutionApi\Resources\Webhook;
use Lynkbyte\EvolutionApi\Services\EvolutionService;

describe('EvolutionService', function () {

    beforeEach(function () {
        Http::preventStrayRequests();

        $this->config = [
            'connections' => [
                'default' => [
                    'server_url' => 'https://api.evolution.test',
                    'api_key' => 'test-api-key',
                ],
                'secondary' => [
                    'server_url' => 'https://secondary.evolution.test',
                    'api_key' => 'secondary-api-key',
                ],
            ],
            'retry' => ['enabled' => false],
            'messages' => ['verify_connection_before_send' => false],
        ];

        $this->connectionManager = new ConnectionManager($this->config);
        $this->service = new EvolutionService($this->connectionManager);
    });

    describe('static factory', function () {
        it('creates service from config array', function () {
            $service = EvolutionService::make($this->config);

            expect($service)->toBeInstanceOf(EvolutionService::class);
        });
    });

    describe('resource access', function () {
        it('returns Instance resource', function () {
            $resource = $this->service->instances();

            expect($resource)->toBeInstanceOf(Instance::class);
        });

        it('returns Message resource', function () {
            $resource = $this->service->messages();

            expect($resource)->toBeInstanceOf(Message::class);
        });

        it('returns Chat resource', function () {
            $resource = $this->service->chats();

            expect($resource)->toBeInstanceOf(Chat::class);
        });

        it('returns Profile resource', function () {
            $resource = $this->service->profile();

            expect($resource)->toBeInstanceOf(Profile::class);
        });

        it('returns Group resource', function () {
            $resource = $this->service->groups();

            expect($resource)->toBeInstanceOf(Group::class);
        });

        it('returns Webhook resource', function () {
            $resource = $this->service->webhooks();

            expect($resource)->toBeInstanceOf(Webhook::class);
        });

        it('returns Settings resource', function () {
            $resource = $this->service->settings();

            expect($resource)->toBeInstanceOf(Settings::class);
        });

        it('caches resource instances', function () {
            $resource1 = $this->service->messages();
            $resource2 = $this->service->messages();

            expect($resource1)->toBe($resource2);
        });
    });

    describe('dynamic method access', function () {
        it('accesses instance via singular name', function () {
            $resource = $this->service->instance();

            expect($resource)->toBeInstanceOf(Instance::class);
        });

        it('accesses message via singular name', function () {
            $resource = $this->service->message();

            expect($resource)->toBeInstanceOf(Message::class);
        });

        it('accesses chat via singular name', function () {
            $resource = $this->service->chat();

            expect($resource)->toBeInstanceOf(Chat::class);
        });

        it('accesses group via singular name', function () {
            $resource = $this->service->group();

            expect($resource)->toBeInstanceOf(Group::class);
        });

        it('accesses webhook via singular name', function () {
            $resource = $this->service->webhook();

            expect($resource)->toBeInstanceOf(Webhook::class);
        });

        it('accesses setting via singular name', function () {
            $resource = $this->service->setting();

            expect($resource)->toBeInstanceOf(Settings::class);
        });

        it('throws exception for unknown method', function () {
            $this->service->unknownMethod();
        })->throws(BadMethodCallException::class);
    });

    describe('connection management', function () {
        it('switches connection', function () {
            $result = $this->service->connection('secondary');

            expect($result)->toBe($this->service);
            expect($this->service->getConnectionManager()->getActiveConnectionName())->toBe('secondary');
        });

        it('clears cached resources when switching connections', function () {
            $resource1 = $this->service->messages();
            $this->service->connection('secondary');
            $resource2 = $this->service->messages();

            expect($resource1)->not->toBe($resource2);
        });
    });

    describe('instance selection', function () {
        it('sets instance with for() method', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $result = $this->service->for('my-instance');

            expect($result)->toBe($this->service);
        });

        it('sets instance with use() method', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $result = $this->service->use('my-instance');

            expect($result)->toBe($this->service);
        });

        it('allows chaining after setting instance', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => ['id' => 'msg-123']], 200),
            ]);

            $response = $this->service
                ->for('my-instance')
                ->messages()
                ->text('5511999999999@s.whatsapp.net', 'Hello');

            expect($response->isSuccessful())->toBeTrue();
        });
    });

    describe('client access', function () {
        it('returns underlying client', function () {
            $client = $this->service->getClient();

            expect($client)->toBeObject();
        });

        it('returns connection manager', function () {
            $manager = $this->service->getConnectionManager();

            expect($manager)->toBeInstanceOf(ConnectionManager::class);
        });
    });

    describe('ping', function () {
        it('returns true when API is reachable', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'ok'], 200),
            ]);

            $result = $this->service->ping();

            expect($result)->toBeTrue();
        });

        it('returns false when API is not reachable', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([], 500),
            ]);

            $result = $this->service->ping();

            expect($result)->toBeFalse();
        });
    });

    describe('info', function () {
        it('returns API server information', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'version' => '1.7.0',
                    'env' => 'production',
                ], 200),
            ]);

            $info = $this->service->info();

            expect($info)->toBeArray();
            expect($info['version'])->toBe('1.7.0');
        });
    });

    describe('shortcut methods', function () {
        describe('sendText', function () {
            it('sends text message', function () {
                Http::fake([
                    'api.evolution.test/*' => Http::response(['key' => ['id' => 'msg-123']], 200),
                ]);

                $this->service->for('test-instance');
                $response = $this->service->sendText('5511999999999@s.whatsapp.net', 'Hello World');

                expect($response->isSuccessful())->toBeTrue();

                Http::assertSent(function (Request $request) {
                    return str_contains($request->url(), 'message/sendText') &&
                        $request['number'] === '5511999999999@s.whatsapp.net' &&
                        $request['text'] === 'Hello World';
                });
            });
        });

        describe('sendImage', function () {
            it('sends image message', function () {
                Http::fake([
                    'api.evolution.test/*' => Http::response(['key' => ['id' => 'msg-123']], 200),
                ]);

                $this->service->for('test-instance');
                $response = $this->service->sendImage(
                    '5511999999999@s.whatsapp.net',
                    'https://example.com/image.jpg',
                    'Check this out'
                );

                expect($response->isSuccessful())->toBeTrue();

                Http::assertSent(function (Request $request) {
                    return str_contains($request->url(), 'message/sendMedia') &&
                        $request['mediatype'] === 'image';
                });
            });
        });

        describe('sendDocument', function () {
            it('sends document message', function () {
                Http::fake([
                    'api.evolution.test/*' => Http::response(['key' => ['id' => 'msg-123']], 200),
                ]);

                $this->service->for('test-instance');
                $response = $this->service->sendDocument(
                    '5511999999999@s.whatsapp.net',
                    'https://example.com/doc.pdf',
                    'document.pdf'
                );

                expect($response->isSuccessful())->toBeTrue();

                Http::assertSent(function (Request $request) {
                    return str_contains($request->url(), 'message/sendMedia') &&
                        $request['mediatype'] === 'document';
                });
            });
        });

        describe('sendLocation', function () {
            it('sends location message', function () {
                Http::fake([
                    'api.evolution.test/*' => Http::response(['key' => ['id' => 'msg-123']], 200),
                ]);

                $this->service->for('test-instance');
                $response = $this->service->sendLocation(
                    '5511999999999@s.whatsapp.net',
                    -23.5505,
                    -46.6333,
                    'São Paulo'
                );

                expect($response->isSuccessful())->toBeTrue();

                Http::assertSent(function (Request $request) {
                    return str_contains($request->url(), 'message/sendLocation');
                });
            });
        });

        describe('isOnWhatsApp', function () {
            it('checks if number is on WhatsApp', function () {
                Http::fake([
                    'api.evolution.test/*' => Http::response([
                        ['exists' => true, 'jid' => '5511999999999@s.whatsapp.net'],
                    ], 200),
                ]);

                $this->service->for('test-instance');
                $response = $this->service->isOnWhatsApp('5511999999999');

                expect($response->isSuccessful())->toBeTrue();

                Http::assertSent(function (Request $request) {
                    return str_contains($request->url(), 'chat/whatsappNumbers');
                });
            });
        });

        describe('createInstance', function () {
            it('creates a new instance', function () {
                Http::fake([
                    'api.evolution.test/*' => Http::response([
                        'instance' => ['instanceName' => 'new-instance'],
                    ], 200),
                ]);

                $response = $this->service->createInstance('new-instance');

                expect($response->isSuccessful())->toBeTrue();

                Http::assertSent(function (Request $request) {
                    return str_contains($request->url(), 'instance/create') &&
                        $request['instanceName'] === 'new-instance';
                });
            });
        });

        describe('getQrCode', function () {
            it('gets QR code for instance', function () {
                Http::fake([
                    'api.evolution.test/*' => Http::response([
                        'base64' => 'data:image/png;base64,ABC123',
                    ], 200),
                ]);

                $this->service->for('test-instance');
                $response = $this->service->getQrCode();

                expect($response->isSuccessful())->toBeTrue();

                Http::assertSent(function (Request $request) {
                    return str_contains($request->url(), 'instance/qrcode/test-instance');
                });
            });

            it('gets QR code with explicit instance name', function () {
                Http::fake([
                    'api.evolution.test/*' => Http::response([
                        'base64' => 'data:image/png;base64,ABC123',
                    ], 200),
                ]);

                $response = $this->service->getQrCode('explicit-instance');

                Http::assertSent(function (Request $request) {
                    return str_contains($request->url(), 'instance/qrcode/explicit-instance');
                });
            });
        });

        describe('connectionState', function () {
            it('gets connection state', function () {
                Http::fake([
                    'api.evolution.test/*' => Http::response([
                        'state' => 'open',
                    ], 200),
                ]);

                $this->service->for('test-instance');
                $response = $this->service->connectionState();

                expect($response->isSuccessful())->toBeTrue();

                Http::assertSent(function (Request $request) {
                    return str_contains($request->url(), 'instance/connectionState');
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

                $this->service->for('test-instance');
                $result = $this->service->isConnected();

                expect($result)->toBeTrue();
            });

            it('returns false when not connected', function () {
                Http::fake([
                    'api.evolution.test/*' => Http::response([
                        'state' => 'close',
                    ], 200),
                ]);

                $this->service->for('test-instance');
                $result = $this->service->isConnected();

                expect($result)->toBeFalse();
            });
        });

        describe('createGroup', function () {
            it('creates a new group', function () {
                Http::fake([
                    'api.evolution.test/*' => Http::response([
                        'id' => '120363123456789012@g.us',
                        'subject' => 'New Group',
                    ], 200),
                ]);

                $this->service->for('test-instance');
                $response = $this->service->createGroup(
                    'New Group',
                    ['5511999999999@s.whatsapp.net', '5511888888888@s.whatsapp.net'],
                    'Group description'
                );

                expect($response->isSuccessful())->toBeTrue();

                Http::assertSent(function (Request $request) {
                    return str_contains($request->url(), 'group/create') &&
                        $request['subject'] === 'New Group' &&
                        $request['description'] === 'Group description';
                });
            });
        });
    });

    describe('fluent interface', function () {
        it('supports full fluent chain', function () {
            Http::fake([
                'secondary.evolution.test/*' => Http::response(['key' => ['id' => 'msg-123']], 200),
            ]);

            $response = $this->service
                ->connection('secondary')
                ->for('production-instance')
                ->messages()
                ->text('5511999999999@s.whatsapp.net', 'Hello from fluent chain');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'secondary.evolution.test') &&
                    str_contains($request->url(), 'production-instance');
            });
        });
    });

});
