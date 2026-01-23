<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Client\EvolutionClient;
use Lynkbyte\EvolutionApi\Resources\Chat;

describe('Chat Resource', function () {

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
        $this->resource = new Chat($this->client);
    });

    describe('checkNumber', function () {
        it('checks if number is on WhatsApp', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    ['exists' => true, 'jid' => '5511999999999@s.whatsapp.net'],
                ], 200),
            ]);

            $response = $this->resource->checkNumber('5511999999999');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/whatsappNumbers/test-instance') &&
                    $request['numbers'] === ['5511999999999'];
            });
        });

        it('checks multiple numbers', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([], 200),
            ]);

            $this->resource->checkNumber(['5511999999999', '5511888888888']);

            Http::assertSent(function (Request $request) {
                return count($request['numbers']) === 2;
            });
        });
    });

    describe('findAll', function () {
        it('fetches all chats', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    ['jid' => '5511999999999@s.whatsapp.net'],
                ], 200),
            ]);

            $response = $this->resource->findAll();

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/findChats/test-instance');
            });
        });
    });

    describe('findPaginated', function () {
        it('fetches chats with pagination', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([], 200),
            ]);

            $this->resource->findPaginated(2, 50);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'page=2') &&
                    str_contains($request->url(), 'limit=50');
            });
        });
    });

    describe('find', function () {
        it('finds specific chat', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['chat' => []], 200),
            ]);

            $this->resource->find('5511999999999@s.whatsapp.net');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/findChat/test-instance');
            });
        });
    });

    describe('findContacts', function () {
        it('fetches all contacts', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([], 200),
            ]);

            $this->resource->findContacts();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/findContacts/test-instance');
            });
        });
    });

    describe('searchContacts', function () {
        it('searches contacts by query', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([], 200),
            ]);

            $this->resource->searchContacts('John');

            Http::assertSent(function (Request $request) {
                return $request['where']['pushName']['contains'] === 'John';
            });
        });
    });

    describe('findMessages', function () {
        it('finds messages in chat', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['messages' => []], 200),
            ]);

            $this->resource->findMessages('5511999999999@s.whatsapp.net', 50);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/findMessages/test-instance') &&
                    $request['limit'] === 50;
            });
        });

        it('supports cursor pagination', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([], 200),
            ]);

            $this->resource->findMessages('5511999999999', 20, 'cursor-token');

            Http::assertSent(function (Request $request) {
                return $request['cursor'] === 'cursor-token';
            });
        });
    });

    describe('archive and unarchive', function () {
        it('archives chat', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->archive('5511999999999@s.whatsapp.net');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/archiveChat') &&
                    $request['archive'] === true;
            });
        });

        it('unarchives chat', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->unarchive('5511999999999@s.whatsapp.net');

            Http::assertSent(function (Request $request) {
                return $request['archive'] === false;
            });
        });
    });

    describe('mute and unmute', function () {
        it('mutes chat', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->mute('5511999999999@s.whatsapp.net', 86400);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/muteChat') &&
                    $request['expiration'] === 86400;
            });
        });

        it('unmutes chat', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->unmute('5511999999999@s.whatsapp.net');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/unmuteChat');
            });
        });
    });

    describe('pin and unpin', function () {
        it('pins chat', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->pin('5511999999999@s.whatsapp.net');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/pinChat') &&
                    $request['pin'] === true;
            });
        });

        it('unpins chat', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->unpin('5511999999999@s.whatsapp.net');

            Http::assertSent(function (Request $request) {
                return $request['pin'] === false;
            });
        });
    });

    describe('block and unblock', function () {
        it('blocks contact', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->block('5511999999999');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/blockContact') &&
                    $request['status'] === 'block';
            });
        });

        it('unblocks contact', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->unblock('5511999999999');

            Http::assertSent(function (Request $request) {
                return $request['status'] === 'unblock';
            });
        });
    });

    describe('deleteChat', function () {
        it('deletes chat', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'deleted'], 200),
            ]);

            $this->resource->deleteChat('5511999999999@s.whatsapp.net');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'DELETE' &&
                    str_contains($request->url(), 'chat/deleteChat');
            });
        });
    });

    describe('fetchProfilePicture', function () {
        it('fetches profile picture URL', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'profilePictureUrl' => 'https://example.com/pic.jpg',
                ], 200),
            ]);

            $response = $this->resource->fetchProfilePicture('5511999999999');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/fetchProfilePictureUrl');
            });
        });
    });

    describe('getPresence', function () {
        it('gets contact presence', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'presence' => 'available',
                ], 200),
            ]);

            $this->resource->getPresence('5511999999999');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/fetchPresence');
            });
        });
    });

    describe('updateContactName', function () {
        it('updates contact name', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->updateContactName('5511999999999@s.whatsapp.net', 'John Doe');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/updateContact') &&
                    $request['name'] === 'John Doe';
            });
        });
    });

    describe('isOnWhatsApp', function () {
        it('is an alias for checkNumber', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    ['exists' => true, 'jid' => '5511999999999@s.whatsapp.net'],
                ], 200),
            ]);

            $response = $this->resource->isOnWhatsApp('5511999999999');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/whatsappNumbers/test-instance') &&
                    $request['numbers'] === ['5511999999999'];
            });
        });
    });

    describe('findAllMessages', function () {
        it('fetches all messages without filter', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['messages' => []], 200),
            ]);

            $response = $this->resource->findAllMessages();

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/findMessages/test-instance') &&
                    $request['limit'] === 100;
            });
        });

        it('fetches messages with filter and custom limit', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['messages' => []], 200),
            ]);

            $this->resource->findAllMessages(['key' => ['remoteJid' => '5511999999999']], 50);

            Http::assertSent(function (Request $request) {
                return $request['limit'] === 50 &&
                    isset($request['where']);
            });
        });
    });

    describe('findMessagesByDate', function () {
        it('fetches messages within date range', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['messages' => []], 200),
            ]);

            $startTimestamp = 1700000000;
            $endTimestamp = 1700086400;

            $this->resource->findMessagesByDate(
                '5511999999999@s.whatsapp.net',
                $startTimestamp,
                $endTimestamp,
                50
            );

            Http::assertSent(function (Request $request) use ($startTimestamp, $endTimestamp) {
                return str_contains($request->url(), 'chat/findMessages/test-instance') &&
                    $request['where']['messageTimestamp']['gte'] === $startTimestamp &&
                    $request['where']['messageTimestamp']['lte'] === $endTimestamp &&
                    $request['limit'] === 50;
            });
        });
    });

    describe('findStatusMessages', function () {
        it('fetches status/story messages', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['statuses' => []], 200),
            ]);

            $response = $this->resource->findStatusMessages();

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/findStatusMessages/test-instance') &&
                    $request->method() === 'GET';
            });
        });
    });

    describe('findLabels', function () {
        it('fetches all labels', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['labels' => []], 200),
            ]);

            $response = $this->resource->findLabels();

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/findLabels/test-instance') &&
                    $request->method() === 'GET';
            });
        });
    });

    describe('markChatUnread', function () {
        it('marks chat as unread', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->markChatUnread('5511999999999@s.whatsapp.net');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/markChatUnread/test-instance') &&
                    $request['chat'] === '5511999999999@s.whatsapp.net';
            });
        });
    });

    describe('clearMessages', function () {
        it('clears all messages in a chat', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'cleared'], 200),
            ]);

            $this->resource->clearMessages('5511999999999@s.whatsapp.net');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'DELETE' &&
                    str_contains($request->url(), 'chat/clearMessages/test-instance') &&
                    $request['remoteJid'] === '5511999999999@s.whatsapp.net';
            });
        });
    });

    describe('getBusinessProfile', function () {
        it('fetches business profile', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'businessProfile' => ['description' => 'Business'],
                ], 200),
            ]);

            $response = $this->resource->getBusinessProfile('5511999999999');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'chat/fetchBusinessProfile/test-instance') &&
                    $request['number'] === '5511999999999';
            });
        });
    });

});
