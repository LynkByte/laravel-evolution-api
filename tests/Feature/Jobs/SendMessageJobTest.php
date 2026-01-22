<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Lynkbyte\EvolutionApi\DTOs\ApiResponse;
use Lynkbyte\EvolutionApi\Events\MessageFailed;
use Lynkbyte\EvolutionApi\Events\MessageSent;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;
use Lynkbyte\EvolutionApi\Resources\Message;
use Lynkbyte\EvolutionApi\Services\EvolutionService;

describe('SendMessageJob', function () {

    beforeEach(function () {
        Http::preventStrayRequests();
        Event::fake();
        Queue::fake();
    });

    afterEach(function () {
        // Clean up mocks
        Mockery::close();
    });

    describe('constructor', function () {
        it('creates job with required parameters', function () {
            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => '5511999999999', 'text' => 'Hello']
            );

            expect($job->instanceName)->toBe('test-instance');
            expect($job->messageType)->toBe('text');
            expect($job->message)->toBe(['number' => '5511999999999', 'text' => 'Hello']);
            expect($job->connectionName)->toBeNull();
        });

        it('creates job with connection name', function () {
            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => '5511999999999', 'text' => 'Hello'],
                connectionName: 'secondary'
            );

            expect($job->connectionName)->toBe('secondary');
        });
    });

    describe('static factory methods', function () {
        describe('text', function () {
            it('creates text message job', function () {
                $job = SendMessageJob::text(
                    'test-instance',
                    '5511999999999',
                    'Hello World'
                );

                expect($job->instanceName)->toBe('test-instance');
                expect($job->messageType)->toBe('text');
                expect($job->message['number'])->toBe('5511999999999');
                expect($job->message['text'])->toBe('Hello World');
            });

            it('creates text message job with options', function () {
                $job = SendMessageJob::text(
                    'test-instance',
                    '5511999999999',
                    'Hello World',
                    ['delay' => 1000]
                );

                expect($job->message['delay'])->toBe(1000);
            });

            it('creates text message job with connection', function () {
                $job = SendMessageJob::text(
                    'test-instance',
                    '5511999999999',
                    'Hello World',
                    [],
                    'secondary'
                );

                expect($job->connectionName)->toBe('secondary');
            });
        });

        describe('media', function () {
            it('creates media message job', function () {
                $job = SendMessageJob::media(
                    'test-instance',
                    '5511999999999',
                    'image',
                    'https://example.com/image.jpg'
                );

                expect($job->instanceName)->toBe('test-instance');
                expect($job->messageType)->toBe('media');
                expect($job->message['number'])->toBe('5511999999999');
                expect($job->message['mediatype'])->toBe('image');
                expect($job->message['media'])->toBe('https://example.com/image.jpg');
            });

            it('creates media message job with options', function () {
                $job = SendMessageJob::media(
                    'test-instance',
                    '5511999999999',
                    'image',
                    'https://example.com/image.jpg',
                    ['caption' => 'Check this out']
                );

                expect($job->message['caption'])->toBe('Check this out');
            });
        });
    });

    describe('tags', function () {
        it('returns correct tags', function () {
            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => '5511999999999', 'text' => 'Hello']
            );

            $tags = $job->tags();

            expect($tags)->toContain('evolution-api');
            expect($tags)->toContain('message');
            expect($tags)->toContain('instance:test-instance');
            expect($tags)->toContain('type:text');
        });
    });

    describe('dispatch', function () {
        it('can be dispatched to queue', function () {
            SendMessageJob::dispatch(
                'test-instance',
                'text',
                ['number' => '5511999999999', 'text' => 'Hello']
            );

            Queue::assertPushed(SendMessageJob::class, function ($job) {
                return $job->instanceName === 'test-instance' &&
                    $job->messageType === 'text';
            });
        });
    });

    describe('job configuration', function () {
        it('uses configured queue name', function () {
            config(['evolution-api.queue.queue' => 'messages-queue']);
            
            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => '5511999999999', 'text' => 'Hello']
            );

            expect($job->queue)->toBe('messages-queue');
        });

        it('uses default queue name when not configured', function () {
            config(['evolution-api.queue' => []]);
            
            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => '5511999999999', 'text' => 'Hello']
            );

            expect($job->queue)->toBe('evolution-api');
        });

        it('uses configured connection when set', function () {
            config(['evolution-api.queue.connection' => 'redis']);
            
            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => '5511999999999', 'text' => 'Hello']
            );

            expect($job->connection)->toBe('redis');
        });

        it('sets tries from config', function () {
            config(['evolution-api.queue.max_exceptions' => 5]);
            
            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => '5511999999999', 'text' => 'Hello']
            );

            expect($job->tries)->toBe(5);
            expect($job->maxExceptions)->toBe(5);
        });

        it('sets backoff from config', function () {
            config(['evolution-api.queue.backoff' => [30, 60, 120]]);
            
            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => '5511999999999', 'text' => 'Hello']
            );

            expect($job->backoff)->toBe([30, 60, 120]);
        });
    });

    describe('failed', function () {
        it('dispatches MessageFailed event when job fails after retries', function () {
            Event::fake();

            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => '5511999999999', 'text' => 'Hello']
            );

            $exception = new \Exception('Final failure');
            $job->failed($exception);

            Event::assertDispatched(\Lynkbyte\EvolutionApi\Events\MessageFailed::class, function ($event) {
                return $event->instanceName === 'test-instance'
                    && $event->messageType === 'text'
                    && $event->exception->getMessage() === 'Final failure';
            });
        });
    });

    describe('handle', function () {
        it('sends text message successfully', function () {
            Event::fake();

            $mockResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['key' => ['id' => 'MSG123']],
                message: 'Message sent'
            );

            $mockMessage = Mockery::mock(Message::class);
            $mockMessage->shouldReceive('sendText')
                ->once()
                ->andReturn($mockResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('test-instance')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('messages')
                ->once()
                ->andReturn($mockMessage);

            // Swap the facade with our mock
            EvolutionApi::swap($mockService);

            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => '5511999999999', 'text' => 'Hello']
            );

            $job->handle();

            Event::assertDispatched(MessageSent::class, function ($event) {
                return $event->instanceName === 'test-instance'
                    && $event->messageType === 'text';
            });
        });

        it('sends media message successfully', function () {
            Event::fake();

            $mockResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['key' => ['id' => 'MSG456']],
                message: 'Media sent'
            );

            $mockMessage = Mockery::mock(Message::class);
            $mockMessage->shouldReceive('sendMedia')
                ->once()
                ->andReturn($mockResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('test-instance')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('messages')
                ->once()
                ->andReturn($mockMessage);

            EvolutionApi::swap($mockService);

            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'media',
                message: ['number' => '5511999999999', 'mediatype' => 'image', 'media' => 'https://example.com/image.jpg']
            );

            $job->handle();

            Event::assertDispatched(MessageSent::class, function ($event) {
                return $event->messageType === 'media';
            });
        });

        it('sends audio message successfully', function () {
            Event::fake();

            $mockResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['key' => ['id' => 'MSG789']],
                message: 'Audio sent'
            );

            $mockMessage = Mockery::mock(Message::class);
            $mockMessage->shouldReceive('sendAudio')
                ->once()
                ->andReturn($mockResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('test-instance')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('messages')
                ->once()
                ->andReturn($mockMessage);

            EvolutionApi::swap($mockService);

            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'audio',
                message: ['number' => '5511999999999', 'audio' => 'https://example.com/audio.mp3']
            );

            $job->handle();

            Event::assertDispatched(MessageSent::class, function ($event) {
                return $event->messageType === 'audio';
            });
        });

        it('sends location message successfully', function () {
            Event::fake();

            $mockResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['key' => ['id' => 'MSG101']],
                message: 'Location sent'
            );

            $mockMessage = Mockery::mock(Message::class);
            $mockMessage->shouldReceive('sendLocation')
                ->once()
                ->andReturn($mockResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('test-instance')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('messages')
                ->once()
                ->andReturn($mockMessage);

            EvolutionApi::swap($mockService);

            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'location',
                message: ['number' => '5511999999999', 'latitude' => -23.5505, 'longitude' => -46.6333]
            );

            $job->handle();

            Event::assertDispatched(MessageSent::class, function ($event) {
                return $event->messageType === 'location';
            });
        });

        it('uses connection when provided', function () {
            Event::fake();

            $mockResponse = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['key' => ['id' => 'MSG123']],
                message: 'Message sent'
            );

            $mockMessage = Mockery::mock(Message::class);
            $mockMessage->shouldReceive('sendText')
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
            $mockService->shouldReceive('messages')
                ->once()
                ->andReturn($mockMessage);

            EvolutionApi::swap($mockService);

            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => '5511999999999', 'text' => 'Hello'],
                connectionName: 'secondary'
            );

            $job->handle();

            Event::assertDispatched(MessageSent::class);
        });

        it('dispatches MessageFailed event when response is not successful', function () {
            Event::fake();

            $mockResponse = new ApiResponse(
                success: false,
                statusCode: 400,
                data: ['error' => 'Bad request'],
                message: 'Invalid number format'
            );

            $mockMessage = Mockery::mock(Message::class);
            $mockMessage->shouldReceive('sendText')
                ->once()
                ->andReturn($mockResponse);

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('test-instance')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('messages')
                ->once()
                ->andReturn($mockMessage);

            EvolutionApi::swap($mockService);

            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => 'invalid', 'text' => 'Hello']
            );

            $job->handle();

            Event::assertDispatched(MessageFailed::class, function ($event) {
                return $event->instanceName === 'test-instance'
                    && str_contains($event->exception->getMessage(), 'Invalid number format');
            });
        });

        it('throws exception for unknown message type', function () {
            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('test-instance')
                ->once()
                ->andReturnSelf();

            EvolutionApi::swap($mockService);

            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'unknown',
                message: ['number' => '5511999999999']
            );

            expect(fn () => $job->handle())
                ->toThrow(\InvalidArgumentException::class, 'Unknown message type: unknown');
        });

        it('rethrows exception and dispatches MessageFailed event', function () {
            Event::fake();

            $mockMessage = Mockery::mock(Message::class);
            $mockMessage->shouldReceive('sendText')
                ->once()
                ->andThrow(new \RuntimeException('API Error'));

            $mockService = Mockery::mock(EvolutionService::class);
            $mockService->shouldReceive('for')
                ->with('test-instance')
                ->once()
                ->andReturnSelf();
            $mockService->shouldReceive('messages')
                ->once()
                ->andReturn($mockMessage);

            EvolutionApi::swap($mockService);

            $job = new SendMessageJob(
                instanceName: 'test-instance',
                messageType: 'text',
                message: ['number' => '5511999999999', 'text' => 'Hello']
            );

            try {
                $job->handle();
                $this->fail('Expected exception was not thrown');
            } catch (\RuntimeException $e) {
                expect($e->getMessage())->toBe('API Error');
            }

            Event::assertDispatched(MessageFailed::class, function ($event) {
                return $event->exception->getMessage() === 'API Error';
            });
        });
    });

});
