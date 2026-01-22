<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lynkbyte\EvolutionApi\Enums\InstanceStatus;
use Lynkbyte\EvolutionApi\Models\EvolutionContact;
use Lynkbyte\EvolutionApi\Models\EvolutionInstance;
use Lynkbyte\EvolutionApi\Models\EvolutionMessage;
use Lynkbyte\EvolutionApi\Models\EvolutionWebhookLog;

describe('EvolutionInstance Model', function () {

    uses(RefreshDatabase::class);

    describe('fillable attributes', function () {
        it('has correct fillable attributes', function () {
            $instance = new EvolutionInstance([
                'name' => 'test-instance',
                'display_name' => 'Test Instance',
                'connection_name' => 'default',
                'phone_number' => '+5511999999999',
                'status' => 'open',
                'profile_name' => 'Test Profile',
                'profile_picture_url' => 'https://example.com/pic.jpg',
                'settings' => ['key' => 'value'],
                'webhook_config' => ['url' => 'https://example.com/webhook'],
            ]);

            expect($instance->name)->toBe('test-instance');
            expect($instance->display_name)->toBe('Test Instance');
            expect($instance->connection_name)->toBe('default');
            expect($instance->phone_number)->toBe('+5511999999999');
            expect($instance->status)->toBe('open');
            expect($instance->profile_name)->toBe('Test Profile');
            expect($instance->profile_picture_url)->toBe('https://example.com/pic.jpg');
            expect($instance->settings)->toBe(['key' => 'value']);
            expect($instance->webhook_config)->toBe(['url' => 'https://example.com/webhook']);
        });
    });

    describe('casts', function () {
        it('casts settings to array', function () {
            $instance = new EvolutionInstance();
            $instance->settings = ['key' => 'value'];

            expect($instance->settings)->toBeArray();
        });

        it('casts webhook_config to array', function () {
            $instance = new EvolutionInstance();
            $instance->webhook_config = ['url' => 'https://example.com'];

            expect($instance->webhook_config)->toBeArray();
        });
    });

    describe('getStatusEnum', function () {
        it('returns InstanceStatus enum for open', function () {
            $instance = new EvolutionInstance(['status' => 'open']);

            $status = $instance->getStatusEnum();

            expect($status)->toBe(InstanceStatus::OPEN);
        });

        it('returns InstanceStatus enum for close', function () {
            $instance = new EvolutionInstance(['status' => 'close']);

            $status = $instance->getStatusEnum();

            expect($status)->toBe(InstanceStatus::CLOSE);
        });

        it('returns InstanceStatus enum for qrcode', function () {
            $instance = new EvolutionInstance(['status' => 'qrcode']);

            $status = $instance->getStatusEnum();

            expect($status)->toBe(InstanceStatus::QRCODE);
        });
    });

    describe('isConnected', function () {
        it('returns true for open status', function () {
            $instance = new EvolutionInstance(['status' => 'open']);

            expect($instance->isConnected())->toBeTrue();
        });

        it('returns true for connected status', function () {
            $instance = new EvolutionInstance(['status' => 'connected']);

            expect($instance->isConnected())->toBeTrue();
        });

        it('returns false for close status', function () {
            $instance = new EvolutionInstance(['status' => 'close']);

            expect($instance->isConnected())->toBeFalse();
        });

        it('returns false for qrcode status', function () {
            $instance = new EvolutionInstance(['status' => 'qrcode']);

            expect($instance->isConnected())->toBeFalse();
        });
    });

    describe('isDisconnected', function () {
        it('returns true for close status', function () {
            $instance = new EvolutionInstance(['status' => 'close']);

            expect($instance->isDisconnected())->toBeTrue();
        });

        it('returns true for disconnected status', function () {
            $instance = new EvolutionInstance(['status' => 'disconnected']);

            expect($instance->isDisconnected())->toBeTrue();
        });

        it('returns false for open status', function () {
            $instance = new EvolutionInstance(['status' => 'open']);

            expect($instance->isDisconnected())->toBeFalse();
        });
    });

    describe('needsQrCode', function () {
        it('returns true for qrcode status', function () {
            $instance = new EvolutionInstance(['status' => 'qrcode']);

            expect($instance->needsQrCode())->toBeTrue();
        });

        it('returns false for other statuses', function () {
            $instance = new EvolutionInstance(['status' => 'open']);

            expect($instance->needsQrCode())->toBeFalse();
        });
    });

    describe('relationships', function () {
        it('defines messages relationship', function () {
            $instance = new EvolutionInstance();

            expect($instance->messages())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        });

        it('defines contacts relationship', function () {
            $instance = new EvolutionInstance();

            expect($instance->contacts())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        });

        it('defines webhookLogs relationship', function () {
            $instance = new EvolutionInstance();

            expect($instance->webhookLogs())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        });
    });

    describe('scopes', function () {
        it('has connected scope', function () {
            $query = EvolutionInstance::connected();

            expect($query->toSql())->toContain('status');
        });

        it('has disconnected scope', function () {
            $query = EvolutionInstance::disconnected();

            expect($query->toSql())->toContain('status');
        });

        it('has forConnection scope', function () {
            $query = EvolutionInstance::forConnection('default');

            expect($query->toSql())->toContain('connection_name');
        });
    });

    describe('table configuration', function () {
        it('uses correct table name', function () {
            $instance = new EvolutionInstance();

            expect($instance->getTable())->toBe('evolution_instances');
        });
    });

    describe('soft deletes', function () {
        it('uses soft deletes', function () {
            $instance = new EvolutionInstance();

            expect(method_exists($instance, 'trashed'))->toBeTrue();
            expect(method_exists($instance, 'restore'))->toBeTrue();
            expect(method_exists($instance, 'forceDelete'))->toBeTrue();
        });
    });

});
