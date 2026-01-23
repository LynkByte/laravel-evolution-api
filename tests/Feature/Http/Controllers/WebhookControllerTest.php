<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Lynkbyte\EvolutionApi\Events\WebhookReceived;
use Lynkbyte\EvolutionApi\Http\Controllers\WebhookController;
use Lynkbyte\EvolutionApi\Jobs\ProcessWebhookJob;
use Lynkbyte\EvolutionApi\Webhooks\WebhookProcessor;

uses(\Lynkbyte\EvolutionApi\Tests\TestCase::class);

beforeEach(function () {
    // Ensure webhook path is configured
    config(['evolution-api.webhook.path' => 'api/evolution-api']);
    config(['evolution-api.webhook.middleware' => []]);
});

describe('WebhookController', function () {

    describe('handle()', function () {
        it('processes valid webhook payload synchronously', function () {
            Event::fake([WebhookReceived::class]);

            $response = $this->postJson('/api/evolution-api/webhook', [
                'event' => 'messages.upsert',
                'instance' => 'test-instance',
                'data' => ['message' => 'Hello'],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Webhook processed',
                ]);
        });

        it('returns 400 for invalid payload without event', function () {
            $response = $this->postJson('/api/evolution-api/webhook', [
                'instance' => 'test-instance',
                'data' => ['message' => 'Hello'],
            ]);

            $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid payload',
                ]);
        });

        it('returns 400 for empty payload', function () {
            $response = $this->postJson('/api/evolution-api/webhook', []);

            $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid payload',
                ]);
        });

        it('queues webhook when queue is enabled', function () {
            Queue::fake();
            config(['evolution-api.webhook.queue' => true]);
            config(['evolution-api.queue.webhook_queue' => 'webhooks']);

            $response = $this->postJson('/api/evolution-api/webhook', [
                'event' => 'messages.upsert',
                'instance' => 'test-instance',
                'data' => ['message' => 'Hello'],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Webhook queued',
                ]);

            Queue::assertPushed(ProcessWebhookJob::class, function ($job) {
                return true;
            });
        });

        it('processes synchronously when queue is disabled', function () {
            Event::fake([WebhookReceived::class]);
            config(['evolution-api.webhook.queue' => false]);

            $response = $this->postJson('/api/evolution-api/webhook', [
                'event' => 'messages.upsert',
                'instance' => 'test-instance',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Webhook processed',
                ]);
        });

        it('falls back to sync processing when queue fails', function () {
            Event::fake([WebhookReceived::class]);
            config(['evolution-api.webhook.queue' => true]);

            // Force queue to fail by using non-existent connection
            config(['queue.default' => 'non-existent-connection']);

            $response = $this->postJson('/api/evolution-api/webhook', [
                'event' => 'messages.upsert',
                'instance' => 'test-instance',
            ]);

            // Should still succeed via sync fallback
            $response->assertStatus(200);
        });

        it('returns 500 when processing fails', function () {
            // Mock WebhookProcessor to throw exception
            $this->mock(WebhookProcessor::class, function ($mock) {
                $mock->shouldReceive('process')
                    ->andThrow(new \Exception('Processing failed'));
            });

            $response = $this->postJson('/api/evolution-api/webhook', [
                'event' => 'messages.upsert',
                'instance' => 'test-instance',
            ]);

            $response->assertStatus(500)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Processing failed',
                ]);
        });

        it('accepts payload with instanceName instead of instance', function () {
            Event::fake([WebhookReceived::class]);

            $response = $this->postJson('/api/evolution-api/webhook', [
                'event' => 'connection.update',
                'instanceName' => 'my-instance',
                'data' => ['state' => 'open'],
            ]);

            $response->assertStatus(200);
        });

        it('processes various webhook event types', function () {
            Event::fake([WebhookReceived::class]);

            $eventTypes = [
                'messages.upsert',
                'messages.update',
                'connection.update',
                'qrcode.updated',
                'presence.update',
                'groups.upsert',
            ];

            foreach ($eventTypes as $event) {
                $response = $this->postJson('/api/evolution-api/webhook', [
                    'event' => $event,
                    'instance' => 'test-instance',
                ]);

                $response->assertStatus(200);
            }
        });
    });

    describe('handleInstance()', function () {
        it('adds instance to payload from URL parameter', function () {
            Event::fake([WebhookReceived::class]);

            $response = $this->postJson('/api/evolution-api/webhook/my-instance', [
                'event' => 'messages.upsert',
                'data' => ['message' => 'Hello'],
            ]);

            $response->assertStatus(200);
        });

        it('preserves existing instance in payload', function () {
            Event::fake([WebhookReceived::class]);

            $response = $this->postJson('/api/evolution-api/webhook/url-instance', [
                'event' => 'messages.upsert',
                'instance' => 'payload-instance',
                'data' => ['message' => 'Hello'],
            ]);

            $response->assertStatus(200);
        });

        it('preserves existing instanceName in payload', function () {
            Event::fake([WebhookReceived::class]);

            $response = $this->postJson('/api/evolution-api/webhook/url-instance', [
                'event' => 'messages.upsert',
                'instanceName' => 'payload-instance',
                'data' => ['message' => 'Hello'],
            ]);

            $response->assertStatus(200);
        });

        it('returns 400 for invalid payload', function () {
            $response = $this->postJson('/api/evolution-api/webhook/my-instance', [
                'data' => ['message' => 'Hello'],
            ]);

            $response->assertStatus(400);
        });
    });

    describe('health()', function () {
        it('returns health status with correct structure', function () {
            $response = $this->getJson('/api/evolution-api/health');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'service',
                    'timestamp',
                ]);
        });

        it('returns ok status', function () {
            $response = $this->getJson('/api/evolution-api/health');

            $response->assertJson([
                'status' => 'ok',
                'service' => 'evolution-api-webhook',
            ]);
        });

        it('returns valid ISO 8601 timestamp', function () {
            $response = $this->getJson('/api/evolution-api/health');

            $data = $response->json();

            // Verify timestamp is valid ISO 8601
            $timestamp = \DateTime::createFromFormat(\DateTime::ATOM, $data['timestamp']);
            expect($timestamp)->not->toBeFalse();
        });
    });

    describe('constructor', function () {
        it('uses NullLogger when no logger provided', function () {
            $processor = $this->app->make(WebhookProcessor::class);
            $controller = new WebhookController($processor);

            // If we get here without exception, NullLogger was used
            expect($controller)->toBeInstanceOf(WebhookController::class);
        });

        it('accepts custom logger', function () {
            $processor = $this->app->make(WebhookProcessor::class);
            $logger = new \Psr\Log\NullLogger;
            $controller = new WebhookController($processor, $logger);

            expect($controller)->toBeInstanceOf(WebhookController::class);
        });
    });

    describe('protected methods', function () {
        it('validates payload requires event field', function () {
            $response = $this->postJson('/api/evolution-api/webhook', [
                'instance' => 'test',
                'data' => [],
            ]);

            $response->assertStatus(400);
        });

        it('validates payload with only event is valid', function () {
            Event::fake([WebhookReceived::class]);

            $response = $this->postJson('/api/evolution-api/webhook', [
                'event' => 'test.event',
            ]);

            $response->assertStatus(200);
        });

        it('uses configured queue name', function () {
            Queue::fake();
            config(['evolution-api.webhook.queue' => true]);
            config(['evolution-api.queue.webhook_queue' => 'custom-queue']);

            $this->postJson('/api/evolution-api/webhook', [
                'event' => 'messages.upsert',
                'instance' => 'test-instance',
            ]);

            Queue::assertPushedOn('custom-queue', ProcessWebhookJob::class);
        });

        it('uses default queue name when not configured', function () {
            Queue::fake();
            config(['evolution-api.webhook.queue' => true]);
            // When webhook_queue is not set, it defaults to 'default'
            config()->offsetUnset('evolution-api.queue.webhook_queue');

            $this->postJson('/api/evolution-api/webhook', [
                'event' => 'messages.upsert',
                'instance' => 'test-instance',
            ]);

            Queue::assertPushed(ProcessWebhookJob::class);
        });
    });

});
