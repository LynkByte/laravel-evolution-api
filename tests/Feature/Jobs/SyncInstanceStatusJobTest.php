<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Lynkbyte\EvolutionApi\DTOs\ApiResponse;
use Lynkbyte\EvolutionApi\Enums\InstanceStatus;
use Lynkbyte\EvolutionApi\Events\InstanceStatusChanged;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;
use Lynkbyte\EvolutionApi\Jobs\SyncInstanceStatusJob;
use Lynkbyte\EvolutionApi\Resources\Instance;
use Lynkbyte\EvolutionApi\Services\EvolutionService;

describe('SyncInstanceStatusJob', function () {

    beforeEach(function () {
        Http::preventStrayRequests();
        Event::fake();
        Queue::fake();
    });

    afterEach(function () {
        Mockery::close();
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

    describe('handle', function () {
        it('syncs single instance when instanceName is provided', function () {
            Event::fake();

            $mockResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['state' => 'open'],
                message: 'OK'
            );

            $mockInstance = Mockery::mock(Instance::class);
            $mockInstance->shouldReceive('connectionState')
                ->with('test-instance')
                ->once()
                ->andReturn($mockResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('test-instance')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('instances')
                ->once()
                ->andReturn($mockInstance);

            EvolutionApi::swap($mockService);

            $job = new SyncInstanceStatusJob('test-instance');
            $job->handle();

            Event::assertDispatched(InstanceStatusChanged::class, function ($event) {
                return $event->instanceName === 'test-instance'
                    && $event->status === InstanceStatus::OPEN;
            });
        });

        it('uses connection when provided', function () {
            Event::fake();

            $mockResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['state' => 'open'],
                message: 'OK'
            );

            $mockInstance = Mockery::mock(Instance::class);
            $mockInstance->shouldReceive('connectionState')
                ->with('test-instance')
                ->once()
                ->andReturn($mockResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('connection')
                ->with('secondary')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('for')
                ->with('test-instance')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('instances')
                ->once()
                ->andReturn($mockInstance);

            EvolutionApi::swap($mockService);

            $job = new SyncInstanceStatusJob('test-instance', 'secondary');
            $job->handle();

            Event::assertDispatched(InstanceStatusChanged::class);
        });

        it('syncs all instances when instanceName is null', function () {
            Event::fake();

            // Response for fetchAll
            $mockFetchAllResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: [
                    ['instanceName' => 'instance-1'],
                    ['instanceName' => 'instance-2'],
                ],
                message: 'OK'
            );

            // Response for connectionState
            $mockStateResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['state' => 'open'],
                message: 'OK'
            );

            $mockInstance = Mockery::mock(Instance::class);
            $mockInstance->shouldReceive('fetchAll')
                ->once()
                ->andReturn($mockFetchAllResponse);
            $mockInstance->shouldReceive('connectionState')
                ->twice()
                ->andReturn($mockStateResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->twice()
                ->andReturnSelf();
            $mockService->shouldReceive('instances')
                ->andReturn($mockInstance);

            EvolutionApi::swap($mockService);

            $job = new SyncInstanceStatusJob;
            $job->handle();

            Event::assertDispatched(InstanceStatusChanged::class, 2);
        });

        it('handles state from instance.state nested path', function () {
            Event::fake();

            $mockResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['instance' => ['state' => 'close']],
                message: 'OK'
            );

            $mockInstance = Mockery::mock(Instance::class);
            $mockInstance->shouldReceive('connectionState')
                ->with('test-instance')
                ->once()
                ->andReturn($mockResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('test-instance')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('instances')
                ->once()
                ->andReturn($mockInstance);

            EvolutionApi::swap($mockService);

            $job = new SyncInstanceStatusJob('test-instance');
            $job->handle();

            Event::assertDispatched(InstanceStatusChanged::class, function ($event) {
                return $event->status === InstanceStatus::CLOSE;
            });
        });

        it('defaults to unknown status when neither state path exists', function () {
            Event::fake();

            $mockResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: [],
                message: 'OK'
            );

            $mockInstance = Mockery::mock(Instance::class);
            $mockInstance->shouldReceive('connectionState')
                ->with('test-instance')
                ->once()
                ->andReturn($mockResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('test-instance')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('instances')
                ->once()
                ->andReturn($mockInstance);

            EvolutionApi::swap($mockService);

            $job = new SyncInstanceStatusJob('test-instance');
            $job->handle();

            Event::assertDispatched(InstanceStatusChanged::class, function ($event) {
                return $event->status === InstanceStatus::UNKNOWN;
            });
        });

        it('does not dispatch event when connectionState fails', function () {
            Event::fake();

            $mockResponse = new ApiResponse(
                success: false,
                statusCode: 500,
                data: [],
                message: 'Error'
            );

            $mockInstance = Mockery::mock(Instance::class);
            $mockInstance->shouldReceive('connectionState')
                ->with('test-instance')
                ->once()
                ->andReturn($mockResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('test-instance')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('instances')
                ->once()
                ->andReturn($mockInstance);

            EvolutionApi::swap($mockService);

            $job = new SyncInstanceStatusJob('test-instance');
            $job->handle();

            Event::assertNotDispatched(InstanceStatusChanged::class);
        });

        it('returns early when fetchAll fails', function () {
            Event::fake();

            $mockFetchAllResponse = new ApiResponse(
                success: false,
                statusCode: 500,
                data: [],
                message: 'Error'
            );

            $mockInstance = Mockery::mock(Instance::class);
            $mockInstance->shouldReceive('fetchAll')
                ->once()
                ->andReturn($mockFetchAllResponse);
            $mockInstance->shouldNotReceive('connectionState');

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('instances')
                ->once()
                ->andReturn($mockInstance);

            EvolutionApi::swap($mockService);

            $job = new SyncInstanceStatusJob;
            $job->handle();

            Event::assertNotDispatched(InstanceStatusChanged::class);
        });

        it('handles instances with name key instead of instanceName', function () {
            Event::fake();

            $mockFetchAllResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: [
                    ['name' => 'instance-using-name'],
                ],
                message: 'OK'
            );

            $mockStateResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['state' => 'open'],
                message: 'OK'
            );

            $mockInstance = Mockery::mock(Instance::class);
            $mockInstance->shouldReceive('fetchAll')
                ->once()
                ->andReturn($mockFetchAllResponse);
            $mockInstance->shouldReceive('connectionState')
                ->with('instance-using-name')
                ->once()
                ->andReturn($mockStateResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('instance-using-name')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('instances')
                ->andReturn($mockInstance);

            EvolutionApi::swap($mockService);

            $job = new SyncInstanceStatusJob;
            $job->handle();

            Event::assertDispatched(InstanceStatusChanged::class, function ($event) {
                return $event->instanceName === 'instance-using-name';
            });
        });

        it('skips instances without name or instanceName', function () {
            Event::fake();

            $mockFetchAllResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: [
                    ['status' => 'open'],  // No name or instanceName
                    ['other' => 'data'],   // No name or instanceName
                ],
                message: 'OK'
            );

            $mockInstance = Mockery::mock(Instance::class);
            $mockInstance->shouldReceive('fetchAll')
                ->once()
                ->andReturn($mockFetchAllResponse);
            $mockInstance->shouldNotReceive('connectionState');

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldNotReceive('for');
            $mockService->shouldReceive('instances')
                ->once()
                ->andReturn($mockInstance);

            EvolutionApi::swap($mockService);

            $job = new SyncInstanceStatusJob;
            $job->handle();

            Event::assertNotDispatched(InstanceStatusChanged::class);
        });

        it('prefers instanceName over name key', function () {
            Event::fake();

            $mockFetchAllResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: [
                    ['instanceName' => 'primary-name', 'name' => 'fallback-name'],
                ],
                message: 'OK'
            );

            $mockStateResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['state' => 'open'],
                message: 'OK'
            );

            $mockInstance = Mockery::mock(Instance::class);
            $mockInstance->shouldReceive('fetchAll')
                ->once()
                ->andReturn($mockFetchAllResponse);
            $mockInstance->shouldReceive('connectionState')
                ->with('primary-name')
                ->once()
                ->andReturn($mockStateResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('primary-name')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('instances')
                ->andReturn($mockInstance);

            EvolutionApi::swap($mockService);

            $job = new SyncInstanceStatusJob;
            $job->handle();

            Event::assertDispatched(InstanceStatusChanged::class, function ($event) {
                return $event->instanceName === 'primary-name';
            });
        });
    });

});
