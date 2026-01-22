<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Tests;

use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\EvolutionApiServiceProvider;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Prevent any real HTTP requests during tests
        Http::preventStrayRequests();
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            EvolutionApiServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'EvolutionApi' => EvolutionApi::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Set default Evolution API configuration
        $app['config']->set('evolution-api.server_url', 'http://localhost:8080');
        $app['config']->set('evolution-api.api_key', 'test-api-key');
        $app['config']->set('evolution-api.default_instance', 'test-instance');

        // Configure connections
        $app['config']->set('evolution-api.connections', [
            'default' => [
                'server_url' => 'http://localhost:8080',
                'api_key' => 'test-api-key',
            ],
            'secondary' => [
                'server_url' => 'http://secondary:8080',
                'api_key' => 'secondary-api-key',
            ],
        ]);

        // Database configuration for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Cache configuration for testing
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
            'serialize' => false,
        ]);

        // Evolution API specific configs
        $app['config']->set('evolution-api.database.enabled', true);
        $app['config']->set('evolution-api.queue.enabled', false);
        $app['config']->set('evolution-api.rate_limiting.enabled', true);
        $app['config']->set('evolution-api.rate_limiting.driver', 'array');
        $app['config']->set('evolution-api.logging.enabled', false);
        $app['config']->set('evolution-api.metrics.enabled', false);
        $app['config']->set('evolution-api.webhook.enabled', true);
        $app['config']->set('evolution-api.webhook.verify_signature', false);
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Mock HTTP responses for Evolution API.
     *
     * @param  array<string, mixed>  $responses
     */
    protected function mockEvolutionApi(array $responses): void
    {
        Http::fake($responses);
    }

    /**
     * Mock a successful message send response.
     */
    protected function mockMessageSendSuccess(?string $messageId = null, string $recipient = '5511999999999'): void
    {
        $messageId = $messageId ?? 'MSG_'.uniqid();

        Http::fake([
            '*/message/sendText/*' => Http::response([
                'key' => [
                    'remoteJid' => $recipient.'@s.whatsapp.net',
                    'fromMe' => true,
                    'id' => $messageId,
                ],
                'message' => ['conversation' => 'Test'],
                'messageTimestamp' => time(),
                'status' => 'PENDING',
            ]),
        ]);
    }

    /**
     * Mock a failed message send response.
     */
    protected function mockMessageSendFailure(string $error = 'Send failed', int $status = 400): void
    {
        Http::fake([
            '*/message/*' => Http::response([
                'error' => true,
                'message' => $error,
            ], $status),
        ]);
    }

    /**
     * Mock instance connection state.
     */
    protected function mockConnectionState(string $state = 'open'): void
    {
        Http::fake([
            '*/instance/connectionState/*' => Http::response([
                'instance' => [
                    'instanceName' => 'test-instance',
                    'state' => $state,
                ],
            ]),
        ]);
    }

    /**
     * Mock QR code response.
     */
    protected function mockQrCode(): void
    {
        Http::fake([
            '*/instance/connect/*' => Http::response([
                'base64' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                'code' => '2@ABC123',
            ]),
        ]);
    }

    /**
     * Assert that an HTTP request was sent to a specific endpoint.
     */
    protected function assertHttpRequestSent(string $method, string $urlPattern): void
    {
        Http::assertSent(function ($request) use ($method, $urlPattern) {
            return $request->method() === strtoupper($method)
                && preg_match($urlPattern, $request->url());
        });
    }

    /**
     * Get a fresh EvolutionApi service instance.
     */
    protected function getEvolutionService(): \Lynkbyte\EvolutionApi\Services\EvolutionService
    {
        return $this->app->make(\Lynkbyte\EvolutionApi\Services\EvolutionService::class);
    }

    /**
     * Get a fresh Evolution client instance.
     */
    protected function getEvolutionClient(): \Lynkbyte\EvolutionApi\Client\EvolutionClient
    {
        return $this->app->make(\Lynkbyte\EvolutionApi\Client\EvolutionClient::class);
    }
}
