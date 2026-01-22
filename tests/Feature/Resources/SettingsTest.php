<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Client\EvolutionClient;
use Lynkbyte\EvolutionApi\Resources\Settings;

describe('Settings Resource', function () {

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
        $this->resource = new Settings($this->client);
    });

    describe('set', function () {
        it('sets instance settings', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $response = $this->resource->set([
                'rejectCall' => true,
                'groupsIgnore' => false,
            ]);

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'settings/set/test-instance') &&
                    $request['rejectCall'] === true &&
                    $request['groupsIgnore'] === false;
            });
        });
    });

    describe('find', function () {
        it('gets instance settings', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'rejectCall' => false,
                    'groupsIgnore' => false,
                    'alwaysOnline' => true,
                    'readMessages' => true,
                ], 200),
            ]);

            $response = $this->resource->find();

            expect($response->isSuccessful())->toBeTrue();
            expect($response->get('alwaysOnline'))->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'settings/find/test-instance') &&
                    $request->method() === 'GET';
            });
        });
    });

    describe('setRejectCalls', function () {
        it('enables reject calls', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setRejectCalls(true);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'settings/set') &&
                    $request['rejectCall'] === true;
            });
        });

        it('enables reject calls with message', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setRejectCalls(true, 'Sorry, I cannot take calls');

            Http::assertSent(function (Request $request) {
                return $request['rejectCall'] === true &&
                    $request['msgCall'] === 'Sorry, I cannot take calls';
            });
        });

        it('disables reject calls', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setRejectCalls(false);

            Http::assertSent(function (Request $request) {
                return $request['rejectCall'] === false &&
                    ! isset($request['msgCall']);
            });
        });
    });

    describe('rejectCallsWithMessage', function () {
        it('enables reject calls with custom message', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->rejectCallsWithMessage('I am busy right now');

            Http::assertSent(function (Request $request) {
                return $request['rejectCall'] === true &&
                    $request['msgCall'] === 'I am busy right now';
            });
        });
    });

    describe('allowCalls', function () {
        it('disables reject calls', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->allowCalls();

            Http::assertSent(function (Request $request) {
                return $request['rejectCall'] === false;
            });
        });
    });

    describe('setReadMessages', function () {
        it('enables read messages', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setReadMessages(true);

            Http::assertSent(function (Request $request) {
                return $request['readMessages'] === true;
            });
        });

        it('disables read messages', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setReadMessages(false);

            Http::assertSent(function (Request $request) {
                return $request['readMessages'] === false;
            });
        });
    });

    describe('enableAutoRead', function () {
        it('enables auto read messages', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->enableAutoRead();

            Http::assertSent(function (Request $request) {
                return $request['readMessages'] === true;
            });
        });
    });

    describe('disableAutoRead', function () {
        it('disables auto read messages', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->disableAutoRead();

            Http::assertSent(function (Request $request) {
                return $request['readMessages'] === false;
            });
        });
    });

    describe('setReadStatus', function () {
        it('enables read status', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setReadStatus(true);

            Http::assertSent(function (Request $request) {
                return $request['readStatus'] === true;
            });
        });

        it('disables read status', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setReadStatus(false);

            Http::assertSent(function (Request $request) {
                return $request['readStatus'] === false;
            });
        });
    });

    describe('setSyncFullHistory', function () {
        it('enables sync full history', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setSyncFullHistory(true);

            Http::assertSent(function (Request $request) {
                return $request['syncFullHistory'] === true;
            });
        });

        it('disables sync full history', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setSyncFullHistory(false);

            Http::assertSent(function (Request $request) {
                return $request['syncFullHistory'] === false;
            });
        });
    });

    describe('setGroupsIgnore', function () {
        it('enables groups ignore', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setGroupsIgnore(true);

            Http::assertSent(function (Request $request) {
                return $request['groupsIgnore'] === true;
            });
        });

        it('disables groups ignore', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setGroupsIgnore(false);

            Http::assertSent(function (Request $request) {
                return $request['groupsIgnore'] === false;
            });
        });
    });

    describe('ignoreGroups', function () {
        it('enables ignoring group messages', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->ignoreGroups();

            Http::assertSent(function (Request $request) {
                return $request['groupsIgnore'] === true;
            });
        });
    });

    describe('processGroups', function () {
        it('enables processing group messages', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->processGroups();

            Http::assertSent(function (Request $request) {
                return $request['groupsIgnore'] === false;
            });
        });
    });

    describe('setAlwaysOnline', function () {
        it('enables always online', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setAlwaysOnline(true);

            Http::assertSent(function (Request $request) {
                return $request['alwaysOnline'] === true;
            });
        });

        it('disables always online', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->setAlwaysOnline(false);

            Http::assertSent(function (Request $request) {
                return $request['alwaysOnline'] === false;
            });
        });
    });

    describe('enableAlwaysOnline', function () {
        it('enables always online mode', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->enableAlwaysOnline();

            Http::assertSent(function (Request $request) {
                return $request['alwaysOnline'] === true;
            });
        });
    });

    describe('disableAlwaysOnline', function () {
        it('disables always online mode', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->disableAlwaysOnline();

            Http::assertSent(function (Request $request) {
                return $request['alwaysOnline'] === false;
            });
        });
    });

    describe('configure', function () {
        it('configures multiple settings at once', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->configure([
                'rejectCall' => true,
                'msgCall' => 'Cannot take calls now',
                'groupsIgnore' => true,
                'alwaysOnline' => true,
                'readMessages' => false,
                'readStatus' => true,
                'syncFullHistory' => false,
            ]);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'settings/set') &&
                    $request['rejectCall'] === true &&
                    $request['msgCall'] === 'Cannot take calls now' &&
                    $request['groupsIgnore'] === true &&
                    $request['alwaysOnline'] === true &&
                    $request['readMessages'] === false &&
                    $request['readStatus'] === true &&
                    $request['syncFullHistory'] === false;
            });
        });

        it('configures partial settings', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->configure([
                'alwaysOnline' => true,
                'groupsIgnore' => false,
            ]);

            Http::assertSent(function (Request $request) {
                return $request['alwaysOnline'] === true &&
                    $request['groupsIgnore'] === false &&
                    ! isset($request['rejectCall']) &&
                    ! isset($request['readMessages']);
            });
        });
    });

});
