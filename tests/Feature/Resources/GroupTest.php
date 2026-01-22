<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Client\EvolutionClient;
use Lynkbyte\EvolutionApi\Resources\Group;

describe('Group Resource', function () {

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
        $this->resource = new Group($this->client);
    });

    describe('create', function () {
        it('creates a new group', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'groupJid' => '120363123456789@g.us',
                ], 200),
            ]);

            $response = $this->resource->create(
                'My Group',
                ['5511999999999', '5511888888888'],
                'Group description'
            );

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'group/create/test-instance') &&
                    $request['subject'] === 'My Group' &&
                    count($request['participants']) === 2 &&
                    $request['description'] === 'Group description';
            });
        });
    });

    describe('updateSubject', function () {
        it('updates group name', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->updateSubject('120363123456789@g.us', 'New Name');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'PUT' &&
                    str_contains($request->url(), 'group/updateSubject') &&
                    $request['subject'] === 'New Name';
            });
        });
    });

    describe('updateDescription', function () {
        it('updates group description', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->updateDescription('120363123456789@g.us', 'New description');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'PUT' &&
                    str_contains($request->url(), 'group/updateDescription') &&
                    $request['description'] === 'New description';
            });
        });
    });

    describe('fetchAll', function () {
        it('fetches all groups', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    ['groupJid' => '120363123456789@g.us', 'subject' => 'Group 1'],
                ], 200),
            ]);

            $response = $this->resource->fetchAll();

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'group/fetchAllGroups/test-instance');
            });
        });

        it('includes participants when requested', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([], 200),
            ]);

            $this->resource->fetchAll(true);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'getParticipants=true');
            });
        });
    });

    describe('fetchOne', function () {
        it('fetches single group', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'groupJid' => '120363123456789@g.us',
                    'subject' => 'My Group',
                ], 200),
            ]);

            $response = $this->resource->fetchOne('120363123456789@g.us');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'group/findGroupInfos');
            });
        });
    });

    describe('participants', function () {
        it('gets group participants', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    ['id' => '5511999999999@s.whatsapp.net', 'admin' => 'superadmin'],
                ], 200),
            ]);

            $response = $this->resource->participants('120363123456789@g.us');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'group/participants');
            });
        });
    });

    describe('addParticipants', function () {
        it('adds participants to group', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->addParticipants('120363123456789@g.us', ['5511999999999']);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'group/updateParticipant') &&
                    $request['action'] === 'add' &&
                    $request['participants'] === ['5511999999999'];
            });
        });
    });

    describe('removeParticipants', function () {
        it('removes participants from group', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->removeParticipants('120363123456789@g.us', ['5511999999999']);

            Http::assertSent(function (Request $request) {
                return $request['action'] === 'remove';
            });
        });
    });

    describe('promoteToAdmin', function () {
        it('promotes participant to admin', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->promoteToAdmin('120363123456789@g.us', ['5511999999999']);

            Http::assertSent(function (Request $request) {
                return $request['action'] === 'promote';
            });
        });
    });

    describe('demoteFromAdmin', function () {
        it('demotes participant from admin', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->demoteFromAdmin('120363123456789@g.us', ['5511999999999']);

            Http::assertSent(function (Request $request) {
                return $request['action'] === 'demote';
            });
        });
    });

    describe('inviteCode', function () {
        it('gets group invite code', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'code' => 'ABC123XYZ',
                ], 200),
            ]);

            $response = $this->resource->inviteCode('120363123456789@g.us');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'group/inviteCode');
            });
        });
    });

    describe('revokeInviteCode', function () {
        it('revokes invite code', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'revoked'], 200),
            ]);

            $this->resource->revokeInviteCode('120363123456789@g.us');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'PUT' &&
                    str_contains($request->url(), 'group/revokeInviteCode');
            });
        });
    });

    describe('acceptInvite', function () {
        it('accepts group invite', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['groupJid' => '120363123456789@g.us'], 200),
            ]);

            $this->resource->acceptInvite('ABC123XYZ');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'group/acceptInviteCode') &&
                    $request['inviteCode'] === 'ABC123XYZ';
            });
        });
    });

    describe('leave', function () {
        it('leaves group', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'left'], 200),
            ]);

            $this->resource->leave('120363123456789@g.us');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'DELETE' &&
                    str_contains($request->url(), 'group/leaveGroup');
            });
        });
    });

    describe('updateSettings', function () {
        it('updates group settings', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->updateSettings('120363123456789@g.us', 'announce');

            Http::assertSent(function (Request $request) {
                return $request->method() === 'PUT' &&
                    str_contains($request->url(), 'group/updateSetting') &&
                    $request['action'] === 'announce';
            });
        });
    });

    describe('setAnnouncementMode', function () {
        it('enables announcement mode', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->setAnnouncementMode('120363123456789@g.us', true);

            Http::assertSent(function (Request $request) {
                return $request['action'] === 'announce';
            });
        });

        it('disables announcement mode', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->setAnnouncementMode('120363123456789@g.us', false);

            Http::assertSent(function (Request $request) {
                return $request['action'] === 'not_announce';
            });
        });
    });

    describe('toggleEphemeral', function () {
        it('enables disappearing messages', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->toggleEphemeral('120363123456789@g.us', 86400);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'group/toggleEphemeral') &&
                    $request['expiration'] === 86400;
            });
        });
    });

});
