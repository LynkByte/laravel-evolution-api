# Metrics & Monitoring

Track and monitor your WhatsApp messaging operations with built-in metrics.

## Configuration

```php
// config/evolution-api.php

'metrics' => [
    // Enable metrics collection
    'enabled' => env('EVOLUTION_METRICS_ENABLED', true),
    
    // Storage driver: database, prometheus, null
    'driver' => env('EVOLUTION_METRICS_DRIVER', 'database'),
    
    // What to track
    'track' => [
        'messages_sent' => true,
        'messages_received' => true,
        'api_calls' => true,
        'api_errors' => true,
        'webhook_events' => true,
        'response_times' => true,
        'queue_jobs' => true,
    ],
],
```

## Available Metrics

### Message Metrics

| Metric | Description |
|--------|-------------|
| `messages_sent` | Total messages sent |
| `messages_received` | Total messages received via webhook |
| `messages_delivered` | Messages with delivery confirmation |
| `messages_read` | Messages with read confirmation |
| `messages_failed` | Failed message attempts |

### API Metrics

| Metric | Description |
|--------|-------------|
| `api_calls` | Total API requests |
| `api_errors` | Failed API requests |
| `api_response_time` | Response time (ms) |
| `rate_limit_hits` | Rate limit encounters |

### Instance Metrics

| Metric | Description |
|--------|-------------|
| `instances_total` | Total instances |
| `instances_connected` | Currently connected |
| `instances_disconnected` | Disconnected instances |
| `connection_events` | Connection status changes |

### Queue Metrics

| Metric | Description |
|--------|-------------|
| `queue_jobs_dispatched` | Jobs added to queue |
| `queue_jobs_processed` | Jobs completed |
| `queue_jobs_failed` | Failed jobs |
| `queue_wait_time` | Time in queue (ms) |

## Database Driver

Store metrics in your database:

### Migration

```php
Schema::create('evolution_metrics', function (Blueprint $table) {
    $table->id();
    $table->string('metric');
    $table->string('instance')->nullable();
    $table->float('value');
    $table->json('tags')->nullable();
    $table->timestamp('recorded_at');
    $table->timestamps();
    
    $table->index(['metric', 'recorded_at']);
    $table->index(['instance', 'metric']);
});
```

### Recording Metrics

```php
use Lynkbyte\EvolutionApi\Metrics\MetricsRecorder;

$recorder = app(MetricsRecorder::class);

// Count metric
$recorder->increment('messages_sent', [
    'instance' => 'my-instance',
    'type' => 'text',
]);

// Gauge metric
$recorder->gauge('instances_connected', 5);

// Timing metric
$recorder->timing('api_response_time', 234.5, [
    'endpoint' => 'sendText',
]);
```

## Prometheus Integration

Export metrics in Prometheus format:

### Configuration

```php
'metrics' => [
    'driver' => 'prometheus',
],
```

### Endpoint

```php
// routes/web.php
Route::get('/metrics', function () {
    return app(PrometheusExporter::class)->export();
})->middleware('auth:api');
```

### Output Example

```prometheus
# HELP evolution_messages_sent_total Total messages sent
# TYPE evolution_messages_sent_total counter
evolution_messages_sent_total{instance="production"} 15234
evolution_messages_sent_total{instance="staging"} 456

# HELP evolution_api_response_time_seconds API response time
# TYPE evolution_api_response_time_seconds histogram
evolution_api_response_time_seconds_bucket{le="0.1"} 8432
evolution_api_response_time_seconds_bucket{le="0.5"} 12453
evolution_api_response_time_seconds_bucket{le="1"} 14234
evolution_api_response_time_seconds_bucket{le="+Inf"} 15234
```

## Custom Metrics Service

### Creating a Metrics Service

```php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EvolutionMetrics
{
    public function recordMessageSent(string $instance, string $type): void
    {
        $this->increment("messages_sent:{$instance}:{$type}");
        $this->increment("messages_sent:total");
    }
    
    public function recordApiCall(string $endpoint, float $duration): void
    {
        $this->increment("api_calls:{$endpoint}");
        $this->timing("api_duration:{$endpoint}", $duration);
    }
    
    public function recordError(string $type, string $instance): void
    {
        $this->increment("errors:{$type}:{$instance}");
    }
    
    protected function increment(string $key): void
    {
        Cache::increment("metrics:{$key}");
        
        // Also store in database for persistence
        DB::table('evolution_metrics')->insert([
            'metric' => $key,
            'value' => 1,
            'recorded_at' => now(),
        ]);
    }
    
    protected function timing(string $key, float $value): void
    {
        DB::table('evolution_metrics')->insert([
            'metric' => $key,
            'value' => $value,
            'recorded_at' => now(),
        ]);
    }
    
    public function getStats(string $instance, string $period = '24h'): array
    {
        $since = match ($period) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay(),
        };
        
        return [
            'messages_sent' => $this->count("messages_sent:{$instance}:*", $since),
            'messages_received' => $this->count("messages_received:{$instance}", $since),
            'api_calls' => $this->count("api_calls:*", $since),
            'errors' => $this->count("errors:*:{$instance}", $since),
            'avg_response_time' => $this->average("api_duration:*", $since),
        ];
    }
}
```

## Event-Based Metrics

### Listen to Package Events

```php
// app/Providers/EventServiceProvider.php
use Lynkbyte\EvolutionApi\Events\MessageSent;
use Lynkbyte\EvolutionApi\Events\MessageReceived;
use Lynkbyte\EvolutionApi\Events\MessageFailed;

protected $listen = [
    MessageSent::class => [
        RecordMessageMetric::class,
    ],
    MessageReceived::class => [
        RecordMessageMetric::class,
    ],
    MessageFailed::class => [
        RecordErrorMetric::class,
    ],
];
```

### Metrics Listener

```php
namespace App\Listeners;

use App\Services\EvolutionMetrics;
use Lynkbyte\EvolutionApi\Events\MessageSent;
use Lynkbyte\EvolutionApi\Events\MessageReceived;

class RecordMessageMetric
{
    public function __construct(protected EvolutionMetrics $metrics) {}
    
    public function handle($event): void
    {
        match (true) {
            $event instanceof MessageSent => $this->metrics->recordMessageSent(
                $event->instanceName,
                $event->messageType
            ),
            $event instanceof MessageReceived => $this->metrics->recordMessageReceived(
                $event->instanceName,
                $event->messageType?->value ?? 'unknown'
            ),
        };
    }
}
```

## Dashboard Integration

### Laravel Pulse

Integrate with Laravel Pulse for real-time monitoring:

```php
// app/Providers/AppServiceProvider.php
use Laravel\Pulse\Facades\Pulse;

public function boot(): void
{
    Pulse::users(function ($ids) {
        return User::findMany($ids)->mapWithKeys(fn ($user) => [
            $user->id => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    });
}
```

### Custom Dashboard Card

```php
namespace App\Pulse;

use Laravel\Pulse\Livewire\Card;

class EvolutionMetrics extends Card
{
    public function render()
    {
        $metrics = app(EvolutionMetrics::class)->getStats('*', '1h');
        
        return view('pulse.evolution-metrics', [
            'metrics' => $metrics,
        ]);
    }
}
```

## Grafana Integration

### Query Examples

```sql
-- Messages per hour
SELECT 
    DATE_TRUNC('hour', recorded_at) as time,
    COUNT(*) as count
FROM evolution_metrics
WHERE metric LIKE 'messages_sent%'
    AND recorded_at > NOW() - INTERVAL '24 hours'
GROUP BY 1
ORDER BY 1;

-- Error rate
SELECT 
    DATE_TRUNC('hour', recorded_at) as time,
    SUM(CASE WHEN metric LIKE 'errors%' THEN 1 ELSE 0 END) as errors,
    SUM(CASE WHEN metric LIKE 'api_calls%' THEN 1 ELSE 0 END) as total,
    ROUND(
        SUM(CASE WHEN metric LIKE 'errors%' THEN 1 ELSE 0 END)::numeric / 
        NULLIF(SUM(CASE WHEN metric LIKE 'api_calls%' THEN 1 ELSE 0 END), 0) * 100,
        2
    ) as error_rate
FROM evolution_metrics
WHERE recorded_at > NOW() - INTERVAL '24 hours'
GROUP BY 1;
```

## Health Checks

### Instance Health

```php
namespace App\Health;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

class EvolutionApiCheck extends Check
{
    public function run(): Result
    {
        try {
            $instances = EvolutionApi::instances()->list();
            $connected = collect($instances->getData())
                ->filter(fn ($i) => ($i['connectionStatus'] ?? '') === 'open')
                ->count();
            
            if ($connected === 0) {
                return Result::make()
                    ->failed('No instances connected')
                    ->shortSummary('0 connected');
            }
            
            return Result::make()
                ->ok()
                ->shortSummary("{$connected} connected");
                
        } catch (\Exception $e) {
            return Result::make()
                ->failed('API unreachable: ' . $e->getMessage());
        }
    }
}
```

### Register Health Check

```php
// app/Providers/AppServiceProvider.php
use Spatie\Health\Facades\Health;
use App\Health\EvolutionApiCheck;

public function boot(): void
{
    Health::checks([
        EvolutionApiCheck::new(),
    ]);
}
```

## Alerting

### Alert on High Error Rate

```php
namespace App\Console\Commands;

use App\Services\EvolutionMetrics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckMetrics extends Command
{
    protected $signature = 'evolution:check-metrics';
    
    public function handle(EvolutionMetrics $metrics): void
    {
        $stats = $metrics->getStats('*', '1h');
        
        $errorRate = $stats['errors'] / max($stats['api_calls'], 1) * 100;
        
        if ($errorRate > 5) {
            Notification::route('slack', config('services.slack.ops'))
                ->notify(new HighErrorRate($errorRate, $stats));
        }
    }
}
```

### Schedule Checks

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('evolution:check-metrics')
        ->everyFiveMinutes();
}
```

## API Endpoint

Expose metrics via API:

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/evolution/metrics', function () {
        $metrics = app(EvolutionMetrics::class);
        
        return response()->json([
            'hourly' => $metrics->getStats('*', '1h'),
            'daily' => $metrics->getStats('*', '24h'),
            'weekly' => $metrics->getStats('*', '7d'),
        ]);
    });
    
    Route::get('/evolution/metrics/{instance}', function ($instance) {
        return response()->json(
            app(EvolutionMetrics::class)->getStats($instance, '24h')
        );
    });
});
```

## Best Practices

### 1. Use Tags for Segmentation

```php
$metrics->increment('messages_sent', [
    'instance' => $instance,
    'type' => $messageType,
    'country' => $country,
]);
```

### 2. Sample High-Volume Metrics

```php
// Sample 10% of response times
if (rand(1, 10) === 1) {
    $metrics->timing('api_response_time', $duration);
}
```

### 3. Aggregate Before Storage

```php
// Aggregate in memory, flush periodically
class MetricsBuffer
{
    protected array $buffer = [];
    
    public function increment(string $key): void
    {
        $this->buffer[$key] = ($this->buffer[$key] ?? 0) + 1;
    }
    
    public function flush(): void
    {
        foreach ($this->buffer as $key => $value) {
            DB::table('evolution_metrics')->insert([
                'metric' => $key,
                'value' => $value,
                'recorded_at' => now(),
            ]);
        }
        $this->buffer = [];
    }
}
```

### 4. Set Up Data Retention

```php
// Clean up old metrics
$schedule->command('model:prune', [
    '--model' => EvolutionMetric::class,
])->daily();

// In the model
protected function prunable()
{
    return static::where('recorded_at', '<', now()->subDays(30));
}
```
