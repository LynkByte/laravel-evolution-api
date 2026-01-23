# Multi-Tenancy

Learn how to use the Laravel Evolution API package in multi-tenant applications.

## Overview

The package supports multiple Evolution API connections, making it ideal for:

- **SaaS Applications** - Each tenant has their own WhatsApp instance
- **Agency Setups** - Managing multiple client accounts
- **Distributed Systems** - Multiple Evolution API servers

## Configuration

### Multiple Connections

Define multiple Evolution API connections:

```php
// config/evolution-api.php

'connections' => [
    'default' => [
        'server_url' => env('EVOLUTION_API_URL', 'http://localhost:8080'),
        'api_key' => env('EVOLUTION_API_KEY'),
    ],
    
    'tenant_a' => [
        'server_url' => env('EVOLUTION_API_URL_TENANT_A'),
        'api_key' => env('EVOLUTION_API_KEY_TENANT_A'),
    ],
    
    'tenant_b' => [
        'server_url' => env('EVOLUTION_API_URL_TENANT_B'),
        'api_key' => env('EVOLUTION_API_KEY_TENANT_B'),
    ],
],
```

### Environment Variables

```bash
# .env
EVOLUTION_API_URL=http://localhost:8080
EVOLUTION_API_KEY=your-global-key

EVOLUTION_API_URL_TENANT_A=http://tenant-a-api:8080
EVOLUTION_API_KEY_TENANT_A=tenant-a-key

EVOLUTION_API_URL_TENANT_B=http://tenant-b-api:8080
EVOLUTION_API_KEY_TENANT_B=tenant-b-key
```

## Using Connections

### Switching Connections

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

// Use default connection
$instances = EvolutionApi::instances()->list();

// Switch to tenant connection
$instances = EvolutionApi::connection('tenant_a')
    ->instances()
    ->list();

// Chain with instance
$messages = EvolutionApi::connection('tenant_a')
    ->for('my-instance')
    ->messages()
    ->sendText('5511999999999', 'Hello!');
```

### Connection in Jobs

```php
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;

// Send via specific connection
SendMessageJob::text(
    instanceName: 'my-instance',
    number: '5511999999999',
    text: 'Hello from tenant!',
    connectionName: 'tenant_a'
)->dispatch();
```

## Dynamic Connections

### Runtime Configuration

Create connections dynamically:

```php
use Lynkbyte\EvolutionApi\EvolutionClient;

// Create client with custom config
$client = new EvolutionClient(
    serverUrl: $tenant->evolution_url,
    apiKey: $tenant->evolution_api_key
);

// Use the client
$instances = $client->instances()->list();
```

### Service Provider Registration

```php
// app/Providers/EvolutionServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Lynkbyte\EvolutionApi\EvolutionClient;

class EvolutionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Dynamically register connections for each tenant
        $tenants = Tenant::whereNotNull('evolution_url')->get();
        
        foreach ($tenants as $tenant) {
            config([
                "evolution-api.connections.{$tenant->id}" => [
                    'server_url' => $tenant->evolution_url,
                    'api_key' => $tenant->evolution_api_key,
                ],
            ]);
        }
    }
}
```

## Per-Request Context

### Middleware Approach

Set connection based on current tenant:

```php
// app/Http/Middleware/SetEvolutionConnection.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

class SetEvolutionConnection
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = $request->user()?->tenant;
        
        if ($tenant && $tenant->evolution_connection) {
            EvolutionApi::connection($tenant->evolution_connection);
        }
        
        return $next($request);
    }
}
```

### Service Container Binding

```php
// app/Providers/AppServiceProvider.php
use Lynkbyte\EvolutionApi\EvolutionClient;

public function register(): void
{
    $this->app->singleton(EvolutionClient::class, function ($app) {
        $tenant = $app['request']->user()?->tenant;
        
        if ($tenant) {
            return new EvolutionClient(
                serverUrl: $tenant->evolution_url,
                apiKey: $tenant->evolution_api_key
            );
        }
        
        return new EvolutionClient(
            serverUrl: config('evolution-api.server_url'),
            apiKey: config('evolution-api.api_key')
        );
    });
}
```

## Webhook Handling

### Tenant Identification

Identify tenant from webhook:

```php
// app/Http/Controllers/TenantWebhookController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Lynkbyte\EvolutionApi\Webhooks\WebhookProcessor;

class TenantWebhookController extends Controller
{
    public function handle(Request $request, string $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        
        // Set tenant context
        tenancy()->initialize($tenant);
        
        // Process webhook
        $processor = app(WebhookProcessor::class);
        $processor->process($request->all());
        
        return response()->json(['status' => 'success']);
    }
}
```

### Per-Tenant Webhook URLs

Configure unique webhook URLs per tenant:

```php
// routes/api.php
Route::post('/webhook/{tenant}', [TenantWebhookController::class, 'handle'])
    ->name('evolution.webhook.tenant');

// When creating instance for tenant
$evolution->connection($tenant->id)
    ->for($instanceName)
    ->webhooks()
    ->configure([
        'url' => route('evolution.webhook.tenant', $tenant->id),
        'events' => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE'],
    ]);
```

## Database Scoping

### Tenant-Scoped Models

If storing messages/webhooks in database:

```php
// app/Models/Message.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Scopes\TenantScope;

class Message extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
        
        static::creating(function ($model) {
            $model->tenant_id = tenant()->id;
        });
    }
}
```

### Migration with Tenant Column

```php
Schema::create('evolution_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('instance_name');
    $table->string('message_id');
    // ...
    
    $table->index(['tenant_id', 'instance_name']);
});
```

## Integration with Popular Packages

### Spatie Laravel-Multitenancy

```php
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

class SwitchEvolutionConnectionTask implements SwitchTenantTask
{
    public function makeCurrent(Tenant $tenant): void
    {
        if ($tenant->evolution_connection) {
            EvolutionApi::connection($tenant->evolution_connection);
        }
    }

    public function forgetCurrent(): void
    {
        EvolutionApi::connection('default');
    }
}
```

### Stancl/Tenancy

```php
// config/tenancy.php
'bootstrappers' => [
    // ...
    App\Tenancy\EvolutionApiBootstrapper::class,
],

// app/Tenancy/EvolutionApiBootstrapper.php
namespace App\Tenancy;

use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

class EvolutionApiBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        if ($tenant->evolution_connection) {
            EvolutionApi::connection($tenant->evolution_connection);
        }
    }

    public function revert(): void
    {
        EvolutionApi::connection('default');
    }
}
```

## Complete Multi-Tenant Example

### Tenant Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'evolution_url',
        'evolution_api_key',
        'evolution_instance',
    ];
    
    protected $casts = [
        'evolution_api_key' => 'encrypted',
    ];
    
    public function getEvolutionClient(): \Lynkbyte\EvolutionApi\EvolutionClient
    {
        return new \Lynkbyte\EvolutionApi\EvolutionClient(
            serverUrl: $this->evolution_url,
            apiKey: $this->evolution_api_key
        );
    }
}
```

### Tenant WhatsApp Service

```php
namespace App\Services;

use App\Models\Tenant;
use Lynkbyte\EvolutionApi\EvolutionClient;
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;

class TenantWhatsAppService
{
    protected EvolutionClient $client;
    protected Tenant $tenant;
    
    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
        $this->client = $tenant->getEvolutionClient();
    }
    
    public function sendMessage(string $number, string $text): void
    {
        // Queue message with tenant's connection
        SendMessageJob::text(
            instanceName: $this->tenant->evolution_instance,
            number: $number,
            text: $text,
            connectionName: "tenant_{$this->tenant->id}"
        )->dispatch();
    }
    
    public function getStatus(): array
    {
        return $this->client
            ->for($this->tenant->evolution_instance)
            ->instances()
            ->getStatus();
    }
    
    public function getQrCode(): ?string
    {
        $response = $this->client
            ->for($this->tenant->evolution_instance)
            ->instances()
            ->connect();
            
        return $response->getData()['qrcode']['base64'] ?? null;
    }
}
```

### Usage in Controller

```php
namespace App\Http\Controllers;

use App\Services\TenantWhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function sendMessage(Request $request)
    {
        $tenant = $request->user()->tenant;
        $service = new TenantWhatsAppService($tenant);
        
        $service->sendMessage(
            number: $request->input('number'),
            text: $request->input('message')
        );
        
        return response()->json(['status' => 'queued']);
    }
    
    public function status(Request $request)
    {
        $tenant = $request->user()->tenant;
        $service = new TenantWhatsAppService($tenant);
        
        return response()->json($service->getStatus());
    }
}
```

## Best Practices

### 1. Isolate Tenant Data

```php
// Always scope queries to tenant
Message::where('tenant_id', $tenant->id)->get();

// Use global scopes
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('tenant_id', tenant()->id);
    }
}
```

### 2. Cache Connection Configs

```php
// Cache tenant connection config
$config = Cache::remember("tenant_{$tenant->id}_evolution", 3600, function () use ($tenant) {
    return [
        'server_url' => $tenant->evolution_url,
        'api_key' => $tenant->evolution_api_key,
    ];
});
```

### 3. Handle Connection Failures

```php
try {
    $client = $tenant->getEvolutionClient();
    $status = $client->for($instance)->instances()->getStatus();
} catch (ConnectionException $e) {
    Log::error("Tenant {$tenant->id} Evolution API unreachable", [
        'url' => $tenant->evolution_url,
        'error' => $e->getMessage(),
    ]);
    
    // Mark tenant's WhatsApp as unavailable
    $tenant->update(['whatsapp_available' => false]);
}
```

### 4. Separate Queues by Tenant

```php
// High-value tenants get dedicated queue
$queue = $tenant->plan === 'enterprise' 
    ? "tenant_{$tenant->id}" 
    : 'default-tenants';

SendMessageJob::text(...)->onQueue($queue)->dispatch();
```
