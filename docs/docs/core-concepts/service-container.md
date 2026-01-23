---
title: Service Container
description: Laravel service container integration for Evolution API
---

# Service Container Integration

This page explains how the Laravel Evolution API package integrates with Laravel's service container, including service registration, dependency injection, and customization options.

## Service Registration

The package registers its services in the `EvolutionApiServiceProvider`:

### Core Services

```php
// EvolutionService - Main entry point (Singleton)
$this->app->singleton(EvolutionService::class, function ($app) {
    return new EvolutionService(
        new ConnectionManager(config('evolution-api')),
        $app->make(RateLimiterInterface::class),
        $app->make(LoggerInterface::class)
    );
});

// Facade binding
$this->app->alias(EvolutionService::class, 'evolution-api');
```

### Supporting Services

```php
// ConnectionManager - Multi-tenancy support
$this->app->singleton(ConnectionManager::class, function ($app) {
    return new ConnectionManager(config('evolution-api'));
});

// RateLimiter - Rate limiting
$this->app->singleton(RateLimiterInterface::class, function ($app) {
    $config = config('evolution-api.rate_limiting');
    return new RateLimiter($config);
});

// WebhookProcessor - Webhook handling
$this->app->singleton(WebhookProcessor::class, function ($app) {
    return new WebhookProcessor(
        config('evolution-api.webhook')
    );
});

// MetricsCollector - Metrics collection
$this->app->singleton(MetricsCollector::class, function ($app) {
    return MetricsCollector::make(config('evolution-api.metrics'));
});
```

## Accessing Services

### Via Facade

The simplest way to access the service:

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

// Access any method on EvolutionService
EvolutionApi::messages()->text($to, $text);
EvolutionApi::instances()->fetchAll();
EvolutionApi::ping();
```

### Via Dependency Injection

Inject the service into your classes:

```php
use Lynkbyte\EvolutionApi\Services\EvolutionService;

class NotificationService
{
    public function __construct(
        private EvolutionService $evolution
    ) {}

    public function sendWelcome(User $user): void
    {
        $this->evolution->messages()->text(
            $user->phone,
            "Welcome to our service, {$user->name}!"
        );
    }
}
```

### Via Helper Function

A convenient helper function is available:

```php
// Using the helper
evolution_api()->messages()->text($to, $text);
```

### Via Container Resolution

Resolve directly from the container:

```php
// Using app() helper
$evolution = app(EvolutionService::class);

// Using make()
$evolution = app()->make(EvolutionService::class);

// Using resolve()
$evolution = resolve(EvolutionService::class);
```

## Singleton Behavior

The `EvolutionService` is registered as a singleton, meaning:

- Only one instance exists per request
- State is preserved (connection, instance context)
- Resources are cached for performance

```php
// Both calls return the same instance
$service1 = app(EvolutionService::class);
$service2 = app(EvolutionService::class);

assert($service1 === $service2); // true
```

### Connection State

Be aware that connection state persists:

```php
// Set connection for tenant
EvolutionApi::connection('tenant-1');

// Later in the same request...
// Still using 'tenant-1' connection!
EvolutionApi::messages()->text($to, $text);
```

For multi-tenant applications, always explicitly set the connection:

```php
// Always specify connection in multi-tenant apps
EvolutionApi::connection($tenant->connection_name)
    ->messages()
    ->text($to, $text);
```

## Interface Bindings

The package binds interfaces to implementations:

```php
// Rate Limiter interface
$this->app->bind(
    RateLimiterInterface::class,
    RateLimiter::class
);

// Client interface
$this->app->bind(
    EvolutionClientInterface::class,
    EvolutionClient::class
);
```

### Swapping Implementations

You can swap implementations in your `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
use Lynkbyte\EvolutionApi\Contracts\RateLimiterInterface;

public function register(): void
{
    $this->app->bind(
        RateLimiterInterface::class,
        MyCustomRateLimiter::class
    );
}
```

## Testing Support

### Using Fakes

The package provides a fake for testing:

```php
use Lynkbyte\EvolutionApi\Testing\Fakes\EvolutionApiFake;

public function test_sends_welcome_message(): void
{
    // Replace with fake
    $fake = EvolutionApiFake::fake();

    // Run your code
    $service = new NotificationService(app(EvolutionService::class));
    $service->sendWelcome($user);

    // Assert
    $fake->assertSentText($user->phone);
}
```

### Binding Fake in Tests

For integration tests, bind the fake in the container:

```php
// tests/TestCase.php
protected function setUp(): void
{
    parent::setUp();
    
    $this->fake = new EvolutionApiFake();
    
    $this->app->instance(EvolutionService::class, $this->fake);
}
```

### Mocking with Mockery

You can also use Mockery:

```php
use Lynkbyte\EvolutionApi\Services\EvolutionService;
use Mockery;

public function test_handles_api_error(): void
{
    $mock = Mockery::mock(EvolutionService::class);
    $mock->shouldReceive('messages->text')
        ->andThrow(new ConnectionException('API unavailable'));

    $this->app->instance(EvolutionService::class, $mock);

    // Test error handling...
}
```

## Deferred Loading

The service provider uses deferred loading for better performance:

```php
class EvolutionApiServiceProvider extends ServiceProvider
{
    // Services are only loaded when first accessed
    public function provides(): array
    {
        return [
            EvolutionService::class,
            'evolution-api',
            ConnectionManager::class,
            RateLimiterInterface::class,
            WebhookProcessor::class,
        ];
    }
}
```

This means services aren't instantiated until you actually use them.

## Configuration Loading

Configuration is loaded during the boot phase:

```php
public function boot(): void
{
    // Publish config
    $this->publishes([
        __DIR__.'/../config/evolution-api.php' => config_path('evolution-api.php'),
    ], 'evolution-api-config');

    // Merge config (allows partial override)
    $this->mergeConfigFrom(
        __DIR__.'/../config/evolution-api.php',
        'evolution-api'
    );
}
```

### Config Caching

The package is compatible with Laravel's config caching:

```bash
php artisan config:cache
```

!!! warning "Environment Variables"
    After caching config, changes to `.env` won't be reflected. Run `php artisan config:clear` to reload.

## Event Bindings

The package registers event listeners:

```php
// Webhook events
Event::listen(WebhookReceived::class, LogWebhookEvent::class);
Event::listen(MessageReceived::class, ProcessIncomingMessage::class);

// You can add your own listeners
Event::listen(MessageSent::class, function ($event) {
    logger()->info('Message sent', ['id' => $event->messageId]);
});
```

## Route Registration

Webhook routes are registered conditionally:

```php
public function boot(): void
{
    if (config('evolution-api.webhook.enabled')) {
        $this->loadRoutesFrom(__DIR__.'/../routes/webhook.php');
    }
}
```

The webhook route is:
```
POST /evolution/webhook/{instance}
```

With configurable prefix and middleware:

```php
// config/evolution-api.php
'webhook' => [
    'route_prefix' => 'evolution/webhook',
    'route_middleware' => ['api'],
],
```

## Command Registration

Artisan commands are registered in the service provider:

```php
public function boot(): void
{
    if ($this->app->runningInConsole()) {
        $this->commands([
            InstallCommand::class,
            HealthCheckCommand::class,
            InstanceStatusCommand::class,
            PruneCommand::class,
            RetryFailedMessagesCommand::class,
        ]);
    }
}
```

## Custom Service Provider

Create a custom provider to extend the package:

```php
// app/Providers/EvolutionApiCustomProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Lynkbyte\EvolutionApi\Webhooks\WebhookProcessor;
use App\Webhooks\MessageHandler;

class EvolutionApiCustomProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register custom webhook handlers
        $processor = $this->app->make(WebhookProcessor::class);
        $processor->registerHandler(new MessageHandler());
        
        // Add custom rate limiter
        $this->app->extend(RateLimiterInterface::class, function ($limiter) {
            return new MyCustomRateLimiter($limiter);
        });
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\EvolutionApiCustomProvider::class,
],
```

## Container Events

Listen for container events:

```php
// When EvolutionService is resolved
$this->app->resolving(EvolutionService::class, function ($service, $app) {
    // Add custom configuration
    logger()->debug('EvolutionService resolved');
});

// After resolution
$this->app->afterResolving(EvolutionService::class, function ($service, $app) {
    // Post-resolution setup
});
```

## Contextual Binding

Bind different implementations based on context:

```php
use App\Services\TenantNotifier;
use App\Services\AdminNotifier;

// TenantNotifier gets tenant-specific service
$this->app->when(TenantNotifier::class)
    ->needs(EvolutionService::class)
    ->give(function ($app) {
        $tenant = $app->make(TenantResolver::class)->current();
        return EvolutionService::make([
            'server_url' => $tenant->api_url,
            'api_key' => $tenant->api_key,
        ]);
    });

// AdminNotifier gets default service
$this->app->when(AdminNotifier::class)
    ->needs(EvolutionService::class)
    ->give(EvolutionService::class);
```

---

## Summary

| Access Method | Use Case |
|--------------|----------|
| `EvolutionApi::` facade | Quick access, most common |
| Dependency injection | Controllers, services |
| `evolution_api()` helper | Closures, callbacks |
| `app(EvolutionService::class)` | Dynamic resolution |

---

## Next Steps

- [Architecture](architecture.md) - Understand the full architecture
- [Multi-Tenancy](../advanced/multi-tenancy.md) - Multiple connections
- [Testing](../testing/fakes.md) - Testing with fakes
