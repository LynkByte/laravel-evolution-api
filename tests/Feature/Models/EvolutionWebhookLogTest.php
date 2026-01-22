<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lynkbyte\EvolutionApi\Models\EvolutionInstance;
use Lynkbyte\EvolutionApi\Models\EvolutionWebhookLog;

describe('EvolutionWebhookLog Model', function () {

    uses(RefreshDatabase::class);

    describe('fillable attributes', function () {
        it('has correct fillable attributes', function () {
            $log = new EvolutionWebhookLog([
                'instance_name' => 'test-instance',
                'event' => 'MESSAGES_UPSERT',
                'payload' => ['key' => 'value'],
                'status' => 'received',
                'error_message' => null,
                'processing_time_ms' => 150,
                'ip_address' => '192.168.1.1',
                'user_agent' => 'WhatsApp/2.0',
            ]);

            expect($log->instance_name)->toBe('test-instance');
            expect($log->event)->toBe('MESSAGES_UPSERT');
            expect($log->payload)->toBe(['key' => 'value']);
            expect($log->status)->toBe('received');
            expect($log->processing_time_ms)->toBe(150);
            expect($log->ip_address)->toBe('192.168.1.1');
            expect($log->user_agent)->toBe('WhatsApp/2.0');
        });
    });

    describe('casts', function () {
        it('casts payload to array', function () {
            $log = new EvolutionWebhookLog();
            $log->payload = ['key' => 'value'];

            expect($log->payload)->toBeArray();
        });

        it('casts processing_time_ms to integer', function () {
            $log = new EvolutionWebhookLog(['processing_time_ms' => '150']);

            expect($log->processing_time_ms)->toBeInt();
        });
    });

    describe('status checks', function () {
        describe('isSuccessful', function () {
            it('returns true for processed status', function () {
                $log = new EvolutionWebhookLog(['status' => 'processed']);

                expect($log->isSuccessful())->toBeTrue();
            });

            it('returns false for received status', function () {
                $log = new EvolutionWebhookLog(['status' => 'received']);

                expect($log->isSuccessful())->toBeFalse();
            });

            it('returns false for failed status', function () {
                $log = new EvolutionWebhookLog(['status' => 'failed']);

                expect($log->isSuccessful())->toBeFalse();
            });
        });

        describe('isFailed', function () {
            it('returns true for failed status', function () {
                $log = new EvolutionWebhookLog(['status' => 'failed']);

                expect($log->isFailed())->toBeTrue();
            });

            it('returns false for processed status', function () {
                $log = new EvolutionWebhookLog(['status' => 'processed']);

                expect($log->isFailed())->toBeFalse();
            });

            it('returns false for received status', function () {
                $log = new EvolutionWebhookLog(['status' => 'received']);

                expect($log->isFailed())->toBeFalse();
            });
        });
    });

    describe('relationships', function () {
        it('defines instance relationship', function () {
            $log = new EvolutionWebhookLog();

            expect($log->instance())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        });
    });

    describe('scopes', function () {
        it('has forInstance scope', function () {
            $query = EvolutionWebhookLog::forInstance('test-instance');

            expect($query->toSql())->toContain('instance_name');
        });

        it('has forEvent scope', function () {
            $query = EvolutionWebhookLog::forEvent('MESSAGES_UPSERT');

            expect($query->toSql())->toContain('event');
        });

        it('has successful scope', function () {
            $query = EvolutionWebhookLog::successful();

            expect($query->toSql())->toContain('status');
        });

        it('has failed scope', function () {
            $query = EvolutionWebhookLog::failed();

            expect($query->toSql())->toContain('status');
        });
    });

    describe('table configuration', function () {
        it('uses correct table name', function () {
            $log = new EvolutionWebhookLog();

            expect($log->getTable())->toBe('evolution_webhook_logs');
        });
    });

});
