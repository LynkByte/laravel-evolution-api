<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
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
            $log = new EvolutionWebhookLog;
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
            $log = new EvolutionWebhookLog;

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
            $log = new EvolutionWebhookLog;

            expect($log->getTable())->toBe('evolution_webhook_logs');
        });
    });

    describe('markAsProcessed', function () {
        it('updates status to processed', function () {
            $log = EvolutionWebhookLog::create([
                'instance_name' => 'test-instance',
                'event' => 'MESSAGES_UPSERT',
                'payload' => ['key' => 'value'],
                'status' => 'received',
            ]);

            $result = $log->markAsProcessed();

            expect($result)->toBeTrue();
            expect($log->status)->toBe('processed');

            // Verify database was updated
            $log->refresh();
            expect($log->status)->toBe('processed');
        });

        it('updates status to processed with processing time', function () {
            $log = EvolutionWebhookLog::create([
                'instance_name' => 'test-instance',
                'event' => 'CONNECTION_UPDATE',
                'payload' => ['status' => 'connected'],
                'status' => 'received',
            ]);

            $result = $log->markAsProcessed(150);

            expect($result)->toBeTrue();
            expect($log->status)->toBe('processed');
            expect($log->processing_time_ms)->toBe(150);

            // Verify database was updated
            $log->refresh();
            expect($log->processing_time_ms)->toBe(150);
        });
    });

    describe('markAsFailed', function () {
        it('updates status to failed with error message', function () {
            $log = EvolutionWebhookLog::create([
                'instance_name' => 'test-instance',
                'event' => 'MESSAGES_UPSERT',
                'payload' => ['key' => 'value'],
                'status' => 'received',
            ]);

            $result = $log->markAsFailed('Handler threw exception');

            expect($result)->toBeTrue();
            expect($log->status)->toBe('failed');
            expect($log->error_message)->toBe('Handler threw exception');

            // Verify database was updated
            $log->refresh();
            expect($log->status)->toBe('failed');
            expect($log->error_message)->toBe('Handler threw exception');
        });

        it('updates status to failed with error message and processing time', function () {
            $log = EvolutionWebhookLog::create([
                'instance_name' => 'test-instance',
                'event' => 'QRCODE_UPDATED',
                'payload' => ['qr' => 'base64data'],
                'status' => 'received',
            ]);

            $result = $log->markAsFailed('Timeout error', 30000);

            expect($result)->toBeTrue();
            expect($log->status)->toBe('failed');
            expect($log->error_message)->toBe('Timeout error');
            expect($log->processing_time_ms)->toBe(30000);
        });
    });

    describe('createFromWebhook', function () {
        it('creates a webhook log with required fields', function () {
            $log = EvolutionWebhookLog::createFromWebhook(
                'test-instance',
                'MESSAGES_UPSERT',
                ['message' => 'Hello']
            );

            expect($log)->toBeInstanceOf(EvolutionWebhookLog::class);
            expect($log->exists)->toBeTrue();
            expect($log->instance_name)->toBe('test-instance');
            expect($log->event)->toBe('MESSAGES_UPSERT');
            expect($log->payload)->toBe(['message' => 'Hello']);
            expect($log->status)->toBe('received');
            expect($log->ip_address)->toBeNull();
            expect($log->user_agent)->toBeNull();
        });

        it('creates a webhook log with IP address and user agent', function () {
            $log = EvolutionWebhookLog::createFromWebhook(
                'production-instance',
                'CONNECTION_UPDATE',
                ['status' => 'open'],
                '192.168.1.100',
                'Evolution-API/1.0'
            );

            expect($log->instance_name)->toBe('production-instance');
            expect($log->event)->toBe('CONNECTION_UPDATE');
            expect($log->ip_address)->toBe('192.168.1.100');
            expect($log->user_agent)->toBe('Evolution-API/1.0');
        });
    });

    describe('pruneOlderThan', function () {
        it('deletes logs older than specified days', function () {
            // Create old logs - we need to manually update created_at after creation
            $oldLog1 = EvolutionWebhookLog::create([
                'instance_name' => 'test-instance',
                'event' => 'OLD_EVENT_1',
                'payload' => [],
                'status' => 'processed',
            ]);
            $oldLog1->created_at = now()->subDays(31);
            $oldLog1->save();

            $oldLog2 = EvolutionWebhookLog::create([
                'instance_name' => 'test-instance',
                'event' => 'OLD_EVENT_2',
                'payload' => [],
                'status' => 'processed',
            ]);
            $oldLog2->created_at = now()->subDays(45);
            $oldLog2->save();

            // Create recent log
            $recentLog = EvolutionWebhookLog::create([
                'instance_name' => 'test-instance',
                'event' => 'RECENT_EVENT',
                'payload' => [],
                'status' => 'processed',
            ]);
            $recentLog->created_at = now()->subDays(15);
            $recentLog->save();

            $deletedCount = EvolutionWebhookLog::pruneOlderThan(30);

            expect($deletedCount)->toBe(2);
            expect(EvolutionWebhookLog::find($oldLog1->id))->toBeNull();
            expect(EvolutionWebhookLog::find($oldLog2->id))->toBeNull();
            expect(EvolutionWebhookLog::find($recentLog->id))->not->toBeNull();
        });

        it('returns zero when no logs to prune', function () {
            // Create only recent logs
            EvolutionWebhookLog::create([
                'instance_name' => 'test-instance',
                'event' => 'RECENT',
                'payload' => [],
                'status' => 'processed',
            ]);

            $deletedCount = EvolutionWebhookLog::pruneOlderThan(30);

            expect($deletedCount)->toBe(0);
        });

        it('prunes logs exactly at the boundary', function () {
            // Create log exactly at boundary
            $boundaryLog = EvolutionWebhookLog::create([
                'instance_name' => 'test-instance',
                'event' => 'BOUNDARY_EVENT',
                'payload' => [],
                'status' => 'processed',
            ]);
            $boundaryLog->created_at = now()->subDays(30)->subSecond();
            $boundaryLog->save();

            $deletedCount = EvolutionWebhookLog::pruneOlderThan(30);

            expect($deletedCount)->toBe(1);
            expect(EvolutionWebhookLog::find($boundaryLog->id))->toBeNull();
        });
    });

});
