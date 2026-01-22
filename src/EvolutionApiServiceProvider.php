<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi;

use Illuminate\Support\ServiceProvider;
use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Client\EvolutionClient;
use Lynkbyte\EvolutionApi\Client\RateLimiter;
use Lynkbyte\EvolutionApi\Console\Commands\HealthCheckCommand;
use Lynkbyte\EvolutionApi\Console\Commands\InstallCommand;
use Lynkbyte\EvolutionApi\Console\Commands\InstanceStatusCommand;
use Lynkbyte\EvolutionApi\Console\Commands\PruneOldDataCommand;
use Lynkbyte\EvolutionApi\Console\Commands\RetryFailedMessagesCommand;
use Lynkbyte\EvolutionApi\Contracts\EvolutionClientInterface;
use Lynkbyte\EvolutionApi\Contracts\RateLimiterInterface;
use Lynkbyte\EvolutionApi\Logging\EvolutionApiLogger;
use Lynkbyte\EvolutionApi\Metrics\MetricsCollector;
use Lynkbyte\EvolutionApi\Services\EvolutionService;
use Lynkbyte\EvolutionApi\Webhooks\WebhookProcessor;

class EvolutionApiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/evolution-api.php',
            'evolution-api'
        );

        $this->registerConnectionManager();
        $this->registerRateLimiter();
        $this->registerLogger();
        $this->registerMetrics();
        $this->registerClient();
        $this->registerService();
        $this->registerWebhookProcessor();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->registerCommands();
        $this->registerRoutes();
        $this->registerEventListeners();
    }

    /**
     * Register the connection manager.
     */
    protected function registerConnectionManager(): void
    {
        $this->app->singleton(ConnectionManager::class, function ($app) {
            return new ConnectionManager(
                config('evolution-api.connections', []),
                config('evolution-api.server_url'),
                config('evolution-api.api_key')
            );
        });
    }

    /**
     * Register the rate limiter.
     */
    protected function registerRateLimiter(): void
    {
        $this->app->singleton(RateLimiterInterface::class, function ($app) {
            return new RateLimiter(
                $app['cache']->store(config('evolution-api.rate_limiting.driver')),
                config('evolution-api.rate_limiting', [])
            );
        });

        $this->app->alias(RateLimiterInterface::class, RateLimiter::class);
    }

    /**
     * Register the logger.
     */
    protected function registerLogger(): void
    {
        $this->app->singleton(EvolutionApiLogger::class, function ($app) {
            return new EvolutionApiLogger(
                $app['log'],
                config('evolution-api.logging', [])
            );
        });
    }

    /**
     * Register the metrics collector.
     */
    protected function registerMetrics(): void
    {
        $this->app->singleton(MetricsCollector::class, function ($app) {
            return new MetricsCollector(
                config('evolution-api.metrics', [])
            );
        });
    }

    /**
     * Register the Evolution API client.
     */
    protected function registerClient(): void
    {
        $this->app->singleton(EvolutionClientInterface::class, function ($app) {
            return new EvolutionClient(
                $app->make(ConnectionManager::class),
                $app->make(RateLimiterInterface::class),
                $app->make(EvolutionApiLogger::class),
                $app->make(MetricsCollector::class),
                config('evolution-api.http', []),
                config('evolution-api.retry', [])
            );
        });

        $this->app->alias(EvolutionClientInterface::class, EvolutionClient::class);
        $this->app->alias(EvolutionClientInterface::class, 'evolution-api.client');
    }

    /**
     * Register the main Evolution service.
     */
    protected function registerService(): void
    {
        $this->app->singleton(EvolutionService::class, function ($app) {
            return new EvolutionService(
                $app->make(EvolutionClientInterface::class),
                config('evolution-api.default_instance'),
                config('evolution-api.queue', [])
            );
        });

        $this->app->alias(EvolutionService::class, 'evolution-api');
    }

    /**
     * Register the webhook processor.
     */
    protected function registerWebhookProcessor(): void
    {
        $this->app->singleton(WebhookProcessor::class, function ($app) {
            return new WebhookProcessor(
                $app['events'],
                $app['log']
            );
        });

        $this->app->alias(WebhookProcessor::class, 'evolution-api.webhook');
    }

    /**
     * Publish the configuration file.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/evolution-api.php' => config_path('evolution-api.php'),
        ], 'evolution-api-config');
    }

    /**
     * Publish the database migrations.
     */
    protected function publishMigrations(): void
    {
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'evolution-api-migrations');

        // Optionally load migrations automatically if enabled
        if (config('evolution-api.database.enabled', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Register Artisan commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                HealthCheckCommand::class,
                InstanceStatusCommand::class,
                PruneOldDataCommand::class,
                RetryFailedMessagesCommand::class,
            ]);
        }
    }

    /**
     * Register webhook routes.
     */
    protected function registerRoutes(): void
    {
        if (config('evolution-api.webhook.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/evolution-api.php');
        }
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        // Event listeners will be registered here
        // They can be overridden in the application's EventServiceProvider
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            'evolution-api',
            'evolution-api.client',
            'evolution-api.webhook',
            EvolutionService::class,
            EvolutionClientInterface::class,
            EvolutionClient::class,
            ConnectionManager::class,
            RateLimiterInterface::class,
            RateLimiter::class,
            EvolutionApiLogger::class,
            MetricsCollector::class,
            WebhookProcessor::class,
        ];
    }
}
