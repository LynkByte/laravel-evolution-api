<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Client\EvolutionClient;
use Lynkbyte\EvolutionApi\Resources\Profile;

describe('Profile Resource', function () {

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
        $this->resource = new Profile($this->client);
    });

    describe('getName', function () {
        it('gets profile name', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'name' => 'John Doe',
                ], 200),
            ]);

            $response = $this->resource->getName();

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'profile/fetchProfile/test-instance');
            });
        });
    });

    describe('updateName', function () {
        it('updates profile name', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->updateName('New Name');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'profile/updateProfileName') &&
                    $request['name'] === 'New Name';
            });
        });
    });

    describe('updateStatus', function () {
        it('updates profile status', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->updateStatus('Available for chat');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'profile/updateProfileStatus') &&
                    $request['status'] === 'Available for chat';
            });
        });
    });

    describe('getPicture', function () {
        it('gets profile picture', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'profilePictureUrl' => 'https://example.com/pic.jpg',
                ], 200),
            ]);

            $response = $this->resource->getPicture();

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'profile/fetchProfilePicture');
            });
        });
    });

    describe('updatePicture', function () {
        it('updates profile picture', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->updatePicture('data:image/png;base64,ABC123');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'profile/updateProfilePicture') &&
                    $request['picture'] === 'data:image/png;base64,ABC123';
            });
        });
    });

    describe('removePicture', function () {
        it('removes profile picture', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'removed'], 200),
            ]);

            $this->resource->removePicture();

            Http::assertSent(function (Request $request) {
                return $request->method() === 'DELETE' &&
                    str_contains($request->url(), 'profile/removeProfilePicture');
            });
        });
    });

    describe('getPrivacySettings', function () {
        it('gets privacy settings', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'readreceipts' => 'all',
                    'profile' => 'contacts',
                    'status' => 'contacts',
                ], 200),
            ]);

            $response = $this->resource->getPrivacySettings();

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'profile/fetchPrivacySettings');
            });
        });
    });

    describe('updatePrivacySettings', function () {
        it('updates privacy settings', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->updatePrivacySettings([
                'readreceipts' => 'none',
                'profile' => 'contacts',
            ]);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'profile/updatePrivacySettings') &&
                    $request['readreceipts'] === 'none' &&
                    $request['profile'] === 'contacts';
            });
        });
    });

    describe('privacy helper methods', function () {
        it('sets read receipts privacy', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->setReadReceiptsPrivacy('none');

            Http::assertSent(function (Request $request) {
                return $request['readreceipts'] === 'none';
            });
        });

        it('sets profile picture privacy', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->setProfilePicturePrivacy('contacts');

            Http::assertSent(function (Request $request) {
                return $request['profile'] === 'contacts';
            });
        });

        it('sets status privacy', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->setStatusPrivacy('contact_blacklist');

            Http::assertSent(function (Request $request) {
                return $request['status'] === 'contact_blacklist';
            });
        });

        it('sets online privacy', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->setOnlinePrivacy('match_last_seen');

            Http::assertSent(function (Request $request) {
                return $request['online'] === 'match_last_seen';
            });
        });

        it('sets last seen privacy', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->setLastSeenPrivacy('contacts');

            Http::assertSent(function (Request $request) {
                return $request['last'] === 'contacts';
            });
        });

        it('sets group add privacy', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->setGroupAddPrivacy('contacts');

            Http::assertSent(function (Request $request) {
                return $request['groupadd'] === 'contacts';
            });
        });
    });

    describe('fetch', function () {
        it('fetches full profile', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'name' => 'John',
                    'status' => 'Available',
                ], 200),
            ]);

            $response = $this->resource->fetch();

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'profile/fetchProfile');
            });
        });
    });

    describe('getBusinessProfile', function () {
        it('gets business profile', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'description' => 'Business description',
                    'category' => 'Technology',
                ], 200),
            ]);

            $response = $this->resource->getBusinessProfile();

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'profile/fetchBusinessProfile');
            });
        });
    });

    describe('updateBusinessProfile', function () {
        it('updates business profile', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->updateBusinessProfile([
                'description' => 'New description',
                'email' => 'contact@example.com',
            ]);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'profile/updateBusinessProfile') &&
                    $request['description'] === 'New description' &&
                    $request['email'] === 'contact@example.com';
            });
        });
    });

});
