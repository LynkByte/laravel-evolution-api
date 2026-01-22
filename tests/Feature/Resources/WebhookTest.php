<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Client\EvolutionClient;
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;
use Lynkbyte\EvolutionApi\Resources\Webhook;

describe('Webhook Resource', function () {

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
        $this->client->instance('test-instance');
        $this->resource = new Webhook($this->client);
    });

    describe('set', function () {
        it('sets webhook configuration', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'url' => 'https://example.com/webhook',
                    'enabled' => true,
                ], 200),
            ]);

            $response = $this->resource->set('https://example.com/webhook');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'webhook/set/test-instance') &&
                    $request['url'] === 'https://example.com/webhook' &&
                    $request['enabled'] === true;
            });
        });

        it('sets webhook with events', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $events = ['MESSAGES_UPSERT', 'MESSAGES_UPDATE'];
            $this->resource->set('https://example.com/webhook', $events);

            Http::assertSent(function (Request $request) {
                return $request['url'] === 'https://example.com/webhook' &&
                    $request['events'] === ['MESSAGES_UPSERT', 'MESSAGES_UPDATE'] &&
                    $request['enabled'] === true;
            });
        });

        it('sets webhook with optional parameters', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->set(
                url: 'https://example.com/webhook',
                events: ['MESSAGES_UPSERT'],
                enabled: true,
                webhookBase64: true,
                webhookByEvents: true
            );

            Http::assertSent(function (Request $request) {
                return $request['url'] === 'https://example.com/webhook' &&
                    $request['webhookBase64'] === true &&
                    $request['webhookByEvents'] === true;
            });
        });
    });

    describe('find', function () {
        it('gets webhook configuration', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'url' => 'https://example.com/webhook',
                    'enabled' => true,
                    'events' => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE'],
                ], 200),
            ]);

            $response = $this->resource->find();

            expect($response->isSuccessful())->toBeTrue();
            expect($response->get('url'))->toBe('https://example.com/webhook');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'webhook/find/test-instance') &&
                    $request->method() === 'GET';
            });
        });
    });

    describe('enable', function () {
        it('enables webhook', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['enabled' => true], 200),
            ]);

            $this->resource->enable('https://example.com/webhook');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'webhook/set') &&
                    $request['url'] === 'https://example.com/webhook' &&
                    $request['enabled'] === true;
            });
        });

        it('enables webhook with specific events', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['enabled' => true], 200),
            ]);

            $events = ['MESSAGES_UPSERT', 'QRCODE_UPDATED'];
            $this->resource->enable('https://example.com/webhook', $events);

            Http::assertSent(function (Request $request) {
                return $request['url'] === 'https://example.com/webhook' &&
                    $request['events'] === ['MESSAGES_UPSERT', 'QRCODE_UPDATED'] &&
                    $request['enabled'] === true;
            });
        });
    });

    describe('disable', function () {
        it('disables webhook', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['enabled' => false], 200),
            ]);

            $this->resource->disable();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'webhook/set') &&
                    $request['url'] === '' &&
                    $request['enabled'] === false;
            });
        });
    });

    describe('updateUrl', function () {
        it('updates webhook URL while preserving events', function () {
            Http::fake([
                'api.evolution.test/webhook/find/*' => Http::response([
                    'url' => 'https://old-url.com/webhook',
                    'events' => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE'],
                ], 200),
                'api.evolution.test/webhook/set/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->updateUrl('https://new-url.com/webhook');

            Http::assertSent(function (Request $request) {
                if (str_contains($request->url(), 'webhook/set')) {
                    return $request['url'] === 'https://new-url.com/webhook' &&
                        $request['events'] === ['MESSAGES_UPSERT', 'CONNECTION_UPDATE'];
                }
                return true;
            });
        });
    });

    describe('subscribeToEvents', function () {
        it('subscribes to specific events with string array', function () {
            Http::fake([
                'api.evolution.test/webhook/find/*' => Http::response([
                    'url' => 'https://example.com/webhook',
                ], 200),
                'api.evolution.test/webhook/set/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->subscribeToEvents(['MESSAGES_UPSERT', 'MESSAGES_UPDATE']);

            Http::assertSent(function (Request $request) {
                if (str_contains($request->url(), 'webhook/set')) {
                    return $request['events'] === ['MESSAGES_UPSERT', 'MESSAGES_UPDATE'] &&
                        $request['url'] === 'https://example.com/webhook';
                }
                return true;
            });
        });

        it('subscribes to specific events with enum array', function () {
            Http::fake([
                'api.evolution.test/webhook/find/*' => Http::response([
                    'url' => 'https://example.com/webhook',
                ], 200),
                'api.evolution.test/webhook/set/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->subscribeToEvents([
                WebhookEvent::MESSAGES_UPSERT,
                WebhookEvent::CONNECTION_UPDATE,
            ]);

            Http::assertSent(function (Request $request) {
                if (str_contains($request->url(), 'webhook/set')) {
                    return $request['events'] === ['MESSAGES_UPSERT', 'CONNECTION_UPDATE'];
                }
                return true;
            });
        });

        it('subscribes to events with custom URL', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->subscribeToEvents(
                ['MESSAGES_UPSERT'],
                'https://custom-url.com/webhook'
            );

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'webhook/set') &&
                    $request['url'] === 'https://custom-url.com/webhook' &&
                    $request['events'] === ['MESSAGES_UPSERT'];
            });
        });
    });

    describe('subscribeToAll', function () {
        it('subscribes to all available events', function () {
            Http::fake([
                'api.evolution.test/webhook/find/*' => Http::response([
                    'url' => 'https://example.com/webhook',
                ], 200),
                'api.evolution.test/webhook/set/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->subscribeToAll();

            $allEvents = array_map(fn ($e) => $e->value, WebhookEvent::cases());

            Http::assertSent(function (Request $request) use ($allEvents) {
                if (str_contains($request->url(), 'webhook/set')) {
                    return $request['events'] === $allEvents;
                }
                return true;
            });
        });

        it('subscribes to all events with custom URL', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->subscribeToAll('https://all-events.com/webhook');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'webhook/set') &&
                    $request['url'] === 'https://all-events.com/webhook';
            });
        });
    });

    describe('subscribeToMessages', function () {
        it('subscribes to message events only', function () {
            Http::fake([
                'api.evolution.test/webhook/find/*' => Http::response([
                    'url' => 'https://example.com/webhook',
                ], 200),
                'api.evolution.test/webhook/set/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->subscribeToMessages();

            $expectedEvents = [
                'MESSAGES_SET',
                'MESSAGES_UPSERT',
                'MESSAGES_UPDATE',
                'MESSAGES_DELETE',
                'SEND_MESSAGE',
            ];

            Http::assertSent(function (Request $request) use ($expectedEvents) {
                if (str_contains($request->url(), 'webhook/set')) {
                    return $request['events'] === $expectedEvents;
                }
                return true;
            });
        });

        it('subscribes to message events with custom URL', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->subscribeToMessages('https://messages.com/webhook');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'webhook/set') &&
                    $request['url'] === 'https://messages.com/webhook';
            });
        });
    });

    describe('subscribeToConnection', function () {
        it('subscribes to connection events only', function () {
            Http::fake([
                'api.evolution.test/webhook/find/*' => Http::response([
                    'url' => 'https://example.com/webhook',
                ], 200),
                'api.evolution.test/webhook/set/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->subscribeToConnection();

            $expectedEvents = [
                'CONNECTION_UPDATE',
                'QRCODE_UPDATED',
            ];

            Http::assertSent(function (Request $request) use ($expectedEvents) {
                if (str_contains($request->url(), 'webhook/set')) {
                    return $request['events'] === $expectedEvents;
                }
                return true;
            });
        });

        it('subscribes to connection events with custom URL', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->subscribeToConnection('https://connection.com/webhook');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'webhook/set') &&
                    $request['url'] === 'https://connection.com/webhook';
            });
        });
    });

    describe('availableEvents', function () {
        it('returns all available webhook events', function () {
            $events = $this->resource->availableEvents();

            expect($events)->toBeArray();
            expect($events)->toContain('MESSAGES_UPSERT');
            expect($events)->toContain('CONNECTION_UPDATE');
            expect($events)->toContain('QRCODE_UPDATED');
            expect($events)->toContain('SEND_MESSAGE');
        });

        it('matches WebhookEvent enum cases', function () {
            $events = $this->resource->availableEvents();
            $enumValues = array_map(fn ($e) => $e->value, WebhookEvent::cases());

            expect($events)->toBe($enumValues);
        });
    });

});
