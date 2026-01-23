# Logging

The package provides comprehensive logging for debugging and monitoring.

## Configuration

```php
// config/evolution-api.php

'logging' => [
    // Enable/disable logging
    'enabled' => env('EVOLUTION_LOGGING_ENABLED', true),
    
    // Log channel (null = default Laravel channel)
    'channel' => env('EVOLUTION_LOG_CHANNEL', null),
    
    // Minimum log level
    'level' => env('EVOLUTION_LOG_LEVEL', 'info'),
    
    // What to log
    'log_requests' => env('EVOLUTION_LOG_REQUESTS', true),
    'log_responses' => env('EVOLUTION_LOG_RESPONSES', true),
    'log_webhooks' => env('EVOLUTION_LOG_WEBHOOKS', true),
    
    // Security
    'redact_sensitive' => true,
    'sensitive_fields' => [
        'apikey',
        'api_key',
        'token',
        'password',
        'secret',
    ],
],
```

## Dedicated Log Channel

Create a dedicated channel for Evolution API logs:

```php
// config/logging.php

'channels' => [
    // ... existing channels
    
    'evolution' => [
        'driver' => 'daily',
        'path' => storage_path('logs/evolution.log'),
        'level' => env('EVOLUTION_LOG_LEVEL', 'debug'),
        'days' => 14,
        'replace_placeholders' => true,
    ],
    
    // Or stack multiple channels
    'evolution-stack' => [
        'driver' => 'stack',
        'channels' => ['evolution', 'slack'],
        'ignore_exceptions' => false,
    ],
],
```

Then configure:

```php
// config/evolution-api.php
'logging' => [
    'channel' => 'evolution',
],
```

## What Gets Logged

### API Requests

When `log_requests` is enabled:

```
[2024-01-15 10:30:45] evolution.INFO: Evolution API Request {
    "method": "POST",
    "url": "http://localhost:8080/message/sendText/my-instance",
    "headers": {
        "apikey": "[REDACTED]",
        "Content-Type": "application/json"
    },
    "body": {
        "number": "5511999999999",
        "text": "Hello, World!"
    }
}
```

### API Responses

When `log_responses` is enabled:

```
[2024-01-15 10:30:46] evolution.INFO: Evolution API Response {
    "status": 200,
    "duration_ms": 1234,
    "body": {
        "key": {
            "remoteJid": "5511999999999@s.whatsapp.net",
            "fromMe": true,
            "id": "BAE5F5B1C2A3D4E6"
        },
        "status": "PENDING"
    }
}
```

### Webhooks

When `log_webhooks` is enabled:

```
[2024-01-15 10:31:00] evolution.INFO: Webhook Received {
    "event": "MESSAGES_UPSERT",
    "instance": "my-instance",
    "payload": {
        "key": {
            "remoteJid": "5511888888888@s.whatsapp.net",
            "id": "MSG123"
        },
        "message": {
            "conversation": "Hello!"
        }
    }
}
```

### Errors

Errors are always logged:

```
[2024-01-15 10:32:00] evolution.ERROR: Evolution API Error {
    "message": "Instance not found",
    "status_code": 404,
    "instance": "non-existent",
    "response": {
        "error": "Instance not found"
    }
}
```

## Sensitive Data Redaction

The package automatically redacts sensitive fields:

```php
'logging' => [
    'redact_sensitive' => true,
    'sensitive_fields' => [
        'apikey',
        'api_key',
        'token',
        'password',
        'secret',
        'authorization',
    ],
],
```

Result:
```json
{
    "headers": {
        "apikey": "[REDACTED]",
        "Content-Type": "application/json"
    }
}
```

## Custom Logger

### Creating a Custom Logger

```php
namespace App\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Illuminate\Support\Facades\Log;

class EvolutionLogger implements LoggerInterface
{
    protected string $channel;
    
    public function __construct(string $channel = 'evolution')
    {
        $this->channel = $channel;
    }
    
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }
    
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }
    
    public function log($level, $message, array $context = []): void
    {
        $context = $this->addMetadata($context);
        $context = $this->redactSensitive($context);
        
        Log::channel($this->channel)->log($level, $message, $context);
    }
    
    protected function addMetadata(array $context): array
    {
        return array_merge($context, [
            'timestamp' => now()->toIso8601String(),
            'request_id' => request()->header('X-Request-ID'),
            'user_id' => auth()->id(),
        ]);
    }
    
    protected function redactSensitive(array $context): array
    {
        $sensitive = ['apikey', 'api_key', 'password', 'secret'];
        
        array_walk_recursive($context, function (&$value, $key) use ($sensitive) {
            if (in_array(strtolower($key), $sensitive)) {
                $value = '[REDACTED]';
            }
        });
        
        return $context;
    }
    
    // Implement other PSR-3 methods...
    public function emergency($message, array $context = []): void { $this->log(LogLevel::EMERGENCY, $message, $context); }
    public function alert($message, array $context = []): void { $this->log(LogLevel::ALERT, $message, $context); }
    public function critical($message, array $context = []): void { $this->log(LogLevel::CRITICAL, $message, $context); }
    public function warning($message, array $context = []): void { $this->log(LogLevel::WARNING, $message, $context); }
    public function notice($message, array $context = []): void { $this->log(LogLevel::NOTICE, $message, $context); }
    public function debug($message, array $context = []): void { $this->log(LogLevel::DEBUG, $message, $context); }
}
```

### Registering Custom Logger

```php
// app/Providers/AppServiceProvider.php
use App\Logging\EvolutionLogger;
use Lynkbyte\EvolutionApi\Webhooks\WebhookProcessor;

public function register(): void
{
    $this->app->when(WebhookProcessor::class)
        ->needs(LoggerInterface::class)
        ->give(function () {
            return new EvolutionLogger('evolution');
        });
}
```

## Structured Logging

### JSON Format

Configure JSON logging for log aggregation:

```php
// config/logging.php
'channels' => [
    'evolution-json' => [
        'driver' => 'daily',
        'path' => storage_path('logs/evolution.json'),
        'level' => 'debug',
        'days' => 14,
        'formatter' => Monolog\Formatter\JsonFormatter::class,
    ],
],
```

### Log to External Services

#### Papertrail

```php
'evolution-papertrail' => [
    'driver' => 'monolog',
    'level' => env('LOG_LEVEL', 'debug'),
    'handler' => SyslogUdpHandler::class,
    'handler_with' => [
        'host' => env('PAPERTRAIL_URL'),
        'port' => env('PAPERTRAIL_PORT'),
    ],
],
```

#### Datadog

```php
'evolution-datadog' => [
    'driver' => 'custom',
    'via' => App\Logging\DatadogLogger::class,
    'level' => 'debug',
],
```

## Log Levels

Control verbosity with log levels:

| Level | Use Case |
|-------|----------|
| `debug` | Detailed request/response data |
| `info` | Normal operations |
| `notice` | Unusual but not error conditions |
| `warning` | Rate limits, retries |
| `error` | Failed operations |
| `critical` | Authentication failures |

```php
// .env
EVOLUTION_LOG_LEVEL=info  # Production
EVOLUTION_LOG_LEVEL=debug # Development
```

## Context-Aware Logging

### Add Request Context

```php
// app/Http/Middleware/AddLogContext.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class AddLogContext
{
    public function handle($request, Closure $next)
    {
        Log::shareContext([
            'request_id' => $request->header('X-Request-ID', uniqid()),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);
        
        return $next($request);
    }
}
```

### Instance-Specific Logging

```php
use Illuminate\Support\Facades\Log;

class InstanceLogger
{
    public static function for(string $instance): \Psr\Log\LoggerInterface
    {
        return Log::channel('evolution')->withContext([
            'instance' => $instance,
        ]);
    }
}

// Usage
InstanceLogger::for('my-instance')->info('Message sent', ['to' => $number]);
```

## Webhook Logging

### Detailed Webhook Logs

```php
use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;
use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;

class LoggingWebhookHandler extends AbstractWebhookHandler
{
    protected function onWebhookReceived(WebhookPayloadDto $payload): void
    {
        Log::channel('evolution')->info('Webhook processed', [
            'event' => $payload->event,
            'instance' => $payload->instanceName,
            'is_known_event' => $payload->isKnownEvent(),
            'message_id' => $payload->getMessageId(),
            'remote_jid' => $payload->getRemoteJid(),
            'processing_time_ms' => $this->getProcessingTime(),
        ]);
    }
}
```

## Debug Mode

Enable verbose debugging:

```php
// config/evolution-api.php
'debug' => env('EVOLUTION_DEBUG', false),
```

When enabled:
- Full request/response bodies are logged
- Stack traces included in errors
- Timing information for all operations

```bash
# Enable in development
EVOLUTION_DEBUG=true
```

## Log Rotation and Retention

### Automatic Rotation

```php
// config/logging.php
'evolution' => [
    'driver' => 'daily',
    'path' => storage_path('logs/evolution.log'),
    'days' => 14,  // Keep 14 days
],
```

### Size-Based Rotation

```php
'evolution' => [
    'driver' => 'single',
    'path' => storage_path('logs/evolution.log'),
    'formatter' => JsonFormatter::class,
],

// Use logrotate for size-based rotation
```

## Monitoring Integration

### Query Logs

```php
// Get recent errors
$errors = collect(file(storage_path('logs/evolution.log')))
    ->filter(fn($line) => str_contains($line, 'ERROR'))
    ->take(10);
```

### Log Metrics

```php
// Count errors per instance
$errorCounts = DB::table('evolution_logs')
    ->select('instance', DB::raw('count(*) as count'))
    ->where('level', 'error')
    ->where('created_at', '>=', now()->subHour())
    ->groupBy('instance')
    ->get();
```

## Best Practices

### 1. Use Appropriate Log Levels

```php
Log::debug('Request details', $fullPayload);   // Development only
Log::info('Message sent', ['id' => $messageId]); // Normal operation
Log::warning('Rate limit approaching');          // Warnings
Log::error('Message failed', $exception->context()); // Errors
```

### 2. Include Correlation IDs

```php
$correlationId = Str::uuid();

Log::info('Starting broadcast', ['correlation_id' => $correlationId]);

foreach ($recipients as $recipient) {
    SendMessageJob::text(...)->dispatch();
    Log::debug('Message queued', [
        'correlation_id' => $correlationId,
        'recipient' => $recipient,
    ]);
}
```

### 3. Separate Concerns

```bash
# Different log files for different purposes
storage/logs/evolution-api.log      # API calls
storage/logs/evolution-webhooks.log # Webhooks
storage/logs/evolution-errors.log   # Errors only
```

### 4. Don't Log in Hot Paths

```php
// Avoid excessive logging in loops
foreach ($messages as $message) {
    // Don't log every iteration
    $this->send($message);
}

// Log summary instead
Log::info("Sent {$count} messages");
```
