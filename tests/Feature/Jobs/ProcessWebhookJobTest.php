<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Lynkbyte\EvolutionApi\Jobs\ProcessWebhookJob;

describe('ProcessWebhookJob', function () {

    beforeEach(function () {
        Event::fake();
        Queue::fake();
    });

    describe('constructor', function () {
        it('creates job with payload', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => ['key' => ['id' => 'msg-123']],
            ];

            $job = new ProcessWebhookJob($payload);

            expect($job->payload)->toBe($payload);
            expect($job->instanceName)->toBeNull();
            expect($job->event)->toBeNull();
        });

        it('creates job with all parameters', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ];

            $job = new ProcessWebhookJob(
                payload: $payload,
                instanceName: 'test-instance',
                event: 'MESSAGES_UPSERT'
            );

            expect($job->payload)->toBe($payload);
            expect($job->instanceName)->toBe('test-instance');
            expect($job->event)->toBe('MESSAGES_UPSERT');
        });
    });

    describe('fromWebhook factory', function () {
        it('creates job from webhook data with instance field', function () {
            $data = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => ['key' => 'value'],
            ];

            $job = ProcessWebhookJob::fromWebhook($data);

            expect($job->payload)->toBe($data);
            expect($job->instanceName)->toBe('test-instance');
            expect($job->event)->toBe('MESSAGES_UPSERT');
        });

        it('creates job from webhook data with instanceName field', function () {
            $data = [
                'event' => 'CONNECTION_UPDATE',
                'instanceName' => 'my-instance',
                'data' => [],
            ];

            $job = ProcessWebhookJob::fromWebhook($data);

            expect($job->instanceName)->toBe('my-instance');
        });

        it('handles missing optional fields', function () {
            $data = [
                'data' => ['key' => 'value'],
            ];

            $job = ProcessWebhookJob::fromWebhook($data);

            expect($job->payload)->toBe($data);
            expect($job->instanceName)->toBeNull();
            expect($job->event)->toBeNull();
        });
    });

    describe('tags', function () {
        it('returns base tags', function () {
            $job = new ProcessWebhookJob(['data' => []]);

            $tags = $job->tags();

            expect($tags)->toContain('evolution-api');
            expect($tags)->toContain('webhook');
            expect($tags)->not->toContain('instance:test-instance');
            expect($tags)->not->toContain('event:MESSAGES_UPSERT');
        });

        it('includes instance tag when set', function () {
            $job = new ProcessWebhookJob(
                payload: ['data' => []],
                instanceName: 'test-instance'
            );

            $tags = $job->tags();

            expect($tags)->toContain('instance:test-instance');
        });

        it('includes event tag when set', function () {
            $job = new ProcessWebhookJob(
                payload: ['data' => []],
                event: 'MESSAGES_UPSERT'
            );

            $tags = $job->tags();

            expect($tags)->toContain('event:MESSAGES_UPSERT');
        });

        it('includes all tags when fully configured', function () {
            $job = new ProcessWebhookJob(
                payload: ['data' => []],
                instanceName: 'test-instance',
                event: 'MESSAGES_UPSERT'
            );

            $tags = $job->tags();

            expect($tags)->toBe([
                'evolution-api',
                'webhook',
                'instance:test-instance',
                'event:MESSAGES_UPSERT',
            ]);
        });
    });

    describe('dispatch', function () {
        it('can be dispatched to queue', function () {
            ProcessWebhookJob::dispatch([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ]);

            Queue::assertPushed(ProcessWebhookJob::class, function ($job) {
                return $job->payload['event'] === 'MESSAGES_UPSERT';
            });
        });

        it('can be dispatched from webhook data', function () {
            $data = [
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'my-instance',
                'data' => ['state' => 'open'],
            ];

            $job = ProcessWebhookJob::fromWebhook($data);

            // Test that the job was created correctly
            expect($job)->toBeInstanceOf(ProcessWebhookJob::class);
            expect($job->payload)->toBe($data);
            expect($job->instanceName)->toBe('my-instance');
            expect($job->event)->toBe('CONNECTION_UPDATE');
        });
    });

    describe('job configuration', function () {
        it('has default tries', function () {
            $job = new ProcessWebhookJob(['data' => []]);

            expect($job->tries)->toBe(3);
        });

        it('has default backoff', function () {
            $job = new ProcessWebhookJob(['data' => []]);

            expect($job->backoff)->toBe([10, 30, 60]);
        });

        it('uses configured queue name', function () {
            config(['evolution-api.queue.queue' => 'custom-queue']);
            
            $job = new ProcessWebhookJob(['data' => []]);

            expect($job->queue)->toBe('custom-queue');
        });

        it('uses default queue name when not configured', function () {
            config(['evolution-api.queue' => []]);
            
            $job = new ProcessWebhookJob(['data' => []]);

            expect($job->queue)->toBe('evolution-api');
        });

        it('uses configured connection when set', function () {
            config(['evolution-api.queue.connection' => 'redis']);
            
            $job = new ProcessWebhookJob(['data' => []]);

            expect($job->connection)->toBe('redis');
        });
    });

});
