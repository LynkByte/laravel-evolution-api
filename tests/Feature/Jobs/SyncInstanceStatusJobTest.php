<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Lynkbyte\EvolutionApi\Jobs\SyncInstanceStatusJob;

describe('SyncInstanceStatusJob', function () {

    beforeEach(function () {
        Http::preventStrayRequests();
        Event::fake();
        Queue::fake();
    });

    describe('constructor', function () {
        it('creates job without parameters', function () {
            $job = new SyncInstanceStatusJob;

            expect($job->instanceName)->toBeNull();
            expect($job->connectionName)->toBeNull();
        });

        it('creates job with instance name', function () {
            $job = new SyncInstanceStatusJob('test-instance');

            expect($job->instanceName)->toBe('test-instance');
            expect($job->connectionName)->toBeNull();
        });

        it('creates job with instance and connection name', function () {
            $job = new SyncInstanceStatusJob(
                instanceName: 'test-instance',
                connectionName: 'secondary'
            );

            expect($job->instanceName)->toBe('test-instance');
            expect($job->connectionName)->toBe('secondary');
        });
    });

    describe('tags', function () {
        it('returns base tags without instance', function () {
            $job = new SyncInstanceStatusJob;

            $tags = $job->tags();

            expect($tags)->toBe(['evolution-api', 'sync-status']);
        });

        it('includes instance tag when set', function () {
            $job = new SyncInstanceStatusJob('test-instance');

            $tags = $job->tags();

            expect($tags)->toBe([
                'evolution-api',
                'sync-status',
                'instance:test-instance',
            ]);
        });
    });

    describe('dispatch', function () {
        it('can be dispatched to queue without instance', function () {
            SyncInstanceStatusJob::dispatch();

            Queue::assertPushed(SyncInstanceStatusJob::class, function ($job) {
                return $job->instanceName === null;
            });
        });

        it('can be dispatched to queue with instance', function () {
            SyncInstanceStatusJob::dispatch('test-instance');

            Queue::assertPushed(SyncInstanceStatusJob::class, function ($job) {
                return $job->instanceName === 'test-instance';
            });
        });

        it('can be dispatched with connection', function () {
            SyncInstanceStatusJob::dispatch('test-instance', 'secondary');

            Queue::assertPushed(SyncInstanceStatusJob::class, function ($job) {
                return $job->instanceName === 'test-instance' &&
                    $job->connectionName === 'secondary';
            });
        });
    });

    describe('job configuration', function () {
        it('has default tries', function () {
            $job = new SyncInstanceStatusJob;

            expect($job->tries)->toBe(3);
        });

        it('uses configured queue name', function () {
            config(['evolution-api.queue.queue' => 'sync-queue']);
            
            $job = new SyncInstanceStatusJob;

            expect($job->queue)->toBe('sync-queue');
        });

        it('uses default queue name when not configured', function () {
            config(['evolution-api.queue' => []]);
            
            $job = new SyncInstanceStatusJob;

            expect($job->queue)->toBe('evolution-api');
        });

        it('uses configured connection when set', function () {
            config(['evolution-api.queue.connection' => 'redis']);
            
            $job = new SyncInstanceStatusJob;

            expect($job->connection)->toBe('redis');
        });
    });

});
