<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;

describe('SendMessageJob', function () {

    beforeEach(function () {
        Http::preventStrayRequests();
        Event::fake();
        Queue::fake();
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

});
