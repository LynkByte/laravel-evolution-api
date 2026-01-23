# Configuration Reference

Complete reference for all configuration options in `config/evolution-api.php`.

## Configuration File

After installation, the config file is located at `config/evolution-api.php`.

```bash
php artisan vendor:publish --tag=evolution-api-config
```

## Connection Settings

### Server URL

```php
'server_url' => env('EVOLUTION_API_SERVER_URL', 'http://localhost:8080'),
```

The base URL of your Evolution API server.

**Environment Variable:** `EVOLUTION_API_SERVER_URL`

### API Key

```php
'api_key' => env('EVOLUTION_API_KEY'),
```

Global API key for authentication.

**Environment Variable:** `EVOLUTION_API_KEY`

### Default Instance

```php
'default_instance' => env('EVOLUTION_API_DEFAULT_INSTANCE', 'default'),
```

The default instance name used when none is specified.

**Environment Variable:** `EVOLUTION_API_DEFAULT_INSTANCE`

## Multi-Connection Setup

```php
'connections' => [
    'default' => [
        'server_url' => env('EVOLUTION_API_SERVER_URL', 'http://localhost:8080'),
        'api_key' => env('EVOLUTION_API_KEY'),
        'default_instance' => env('EVOLUTION_API_DEFAULT_INSTANCE', 'default'),
    ],
    
    'secondary' => [
        'server_url' => env('EVOLUTION_API_SECONDARY_URL'),
        'api_key' => env('EVOLUTION_API_SECONDARY_KEY'),
        'default_instance' => 'secondary-instance',
    ],
],
```

Define multiple Evolution API connections for multi-tenant applications.

## HTTP Client Options

```php
'http' => [
    'timeout' => env('EVOLUTION_API_TIMEOUT', 30),
    'connect_timeout' => env('EVOLUTION_API_CONNECT_TIMEOUT', 10),
    'retry' => [
        'times' => 3,
        'sleep' => 100, // milliseconds
        'when' => null, // callable to determine retry
    ],
    'verify_ssl' => env('EVOLUTION_API_VERIFY_SSL', true),
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `timeout` | `int` | `30` | Request timeout in seconds |
| `connect_timeout` | `int` | `10` | Connection timeout in seconds |
| `retry.times` | `int` | `3` | Number of retry attempts |
| `retry.sleep` | `int` | `100` | Milliseconds between retries |
| `verify_ssl` | `bool` | `true` | Verify SSL certificates |

## Webhook Configuration

```php
'webhook' => [
    'enabled' => env('EVOLUTION_API_WEBHOOK_ENABLED', true),
    'path' => env('EVOLUTION_API_WEBHOOK_PATH', '/api/evolution-api/webhook'),
    'secret' => env('EVOLUTION_API_WEBHOOK_SECRET'),
    'verify_signature' => env('EVOLUTION_API_WEBHOOK_VERIFY', false),
    'events' => [
        'MESSAGES_UPSERT',
        'MESSAGES_UPDATE',
        'CONNECTION_UPDATE',
        'QRCODE_UPDATED',
    ],
    'queue' => [
        'enabled' => env('EVOLUTION_API_WEBHOOK_QUEUE', true),
        'connection' => env('EVOLUTION_API_WEBHOOK_QUEUE_CONNECTION', 'redis'),
        'queue' => env('EVOLUTION_API_WEBHOOK_QUEUE_NAME', 'webhooks'),
    ],
    'log' => [
        'enabled' => env('EVOLUTION_API_WEBHOOK_LOG', true),
        'channel' => env('EVOLUTION_API_WEBHOOK_LOG_CHANNEL', 'stack'),
    ],
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | `bool` | `true` | Enable webhook endpoint |
| `path` | `string` | `/api/evolution-api/webhook` | Webhook URL path |
| `secret` | `?string` | `null` | Secret for signature verification |
| `verify_signature` | `bool` | `false` | Verify webhook signatures |
| `events` | `array` | `[...]` | Events to process |
| `queue.enabled` | `bool` | `true` | Queue webhook processing |
| `queue.connection` | `string` | `redis` | Queue connection name |
| `queue.queue` | `string` | `webhooks` | Queue name |
| `log.enabled` | `bool` | `true` | Log webhooks |
| `log.channel` | `string` | `stack` | Log channel |

## Queue Configuration

```php
'queue' => [
    'enabled' => env('EVOLUTION_API_QUEUE_ENABLED', true),
    'connection' => env('EVOLUTION_API_QUEUE_CONNECTION', 'redis'),
    'queue' => env('EVOLUTION_API_QUEUE_NAME', 'whatsapp'),
    'retry_after' => 90,
    'max_attempts' => 3,
    'backoff' => [30, 60, 120], // seconds
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | `bool` | `true` | Enable queue for messages |
| `connection` | `string` | `redis` | Queue connection |
| `queue` | `string` | `whatsapp` | Queue name |
| `retry_after` | `int` | `90` | Job timeout in seconds |
| `max_attempts` | `int` | `3` | Max retry attempts |
| `backoff` | `array` | `[30, 60, 120]` | Retry delays |

## Rate Limiting

```php
'rate_limit' => [
    'enabled' => env('EVOLUTION_API_RATE_LIMIT_ENABLED', true),
    'limits' => [
        'default' => [
            'max_attempts' => 60,
            'decay_seconds' => 60,
        ],
        'messages' => [
            'max_attempts' => 30,
            'decay_seconds' => 60,
        ],
        'media' => [
            'max_attempts' => 10,
            'decay_seconds' => 60,
        ],
    ],
    'key_prefix' => 'evolution_api_rate_limit',
],
```

| Limit | Max Attempts | Window | Description |
|-------|--------------|--------|-------------|
| `default` | 60 | 60s | General API calls |
| `messages` | 30 | 60s | Message sending |
| `media` | 10 | 60s | Media uploads |

## Database Configuration

```php
'database' => [
    'enabled' => env('EVOLUTION_API_DATABASE_ENABLED', true),
    'connection' => env('EVOLUTION_API_DATABASE_CONNECTION', null),
    'tables' => [
        'messages' => 'evolution_messages',
        'instances' => 'evolution_instances',
        'contacts' => 'evolution_contacts',
        'webhook_logs' => 'evolution_webhook_logs',
    ],
    'retention' => [
        'messages' => env('EVOLUTION_API_MESSAGE_RETENTION', 30), // days
        'webhook_logs' => env('EVOLUTION_API_WEBHOOK_RETENTION', 7), // days
    ],
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | `bool` | `true` | Enable database features |
| `connection` | `?string` | `null` | Database connection (null = default) |
| `tables.*` | `string` | `evolution_*` | Table names |
| `retention.messages` | `int` | `30` | Message retention (days) |
| `retention.webhook_logs` | `int` | `7` | Webhook log retention (days) |

## Logging Configuration

```php
'logging' => [
    'enabled' => env('EVOLUTION_API_LOGGING_ENABLED', true),
    'channel' => env('EVOLUTION_API_LOG_CHANNEL', 'stack'),
    'level' => env('EVOLUTION_API_LOG_LEVEL', 'debug'),
    'log_requests' => env('EVOLUTION_API_LOG_REQUESTS', false),
    'log_responses' => env('EVOLUTION_API_LOG_RESPONSES', false),
    'sensitive_fields' => [
        'api_key',
        'apikey',
        'token',
        'password',
        'secret',
    ],
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | `bool` | `true` | Enable logging |
| `channel` | `string` | `stack` | Log channel |
| `level` | `string` | `debug` | Minimum log level |
| `log_requests` | `bool` | `false` | Log outgoing requests |
| `log_responses` | `bool` | `false` | Log API responses |
| `sensitive_fields` | `array` | `[...]` | Fields to redact |

## Events Configuration

```php
'events' => [
    'dispatch' => true,
    'broadcast' => false,
    'broadcast_channel' => 'whatsapp',
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `dispatch` | `bool` | `true` | Dispatch Laravel events |
| `broadcast` | `bool` | `false` | Broadcast events |
| `broadcast_channel` | `string` | `whatsapp` | Broadcast channel name |

## Cache Configuration

```php
'cache' => [
    'enabled' => env('EVOLUTION_API_CACHE_ENABLED', true),
    'store' => env('EVOLUTION_API_CACHE_STORE', 'redis'),
    'prefix' => 'evolution_api',
    'ttl' => [
        'instance_status' => 60,        // 1 minute
        'connection_state' => 30,       // 30 seconds
        'profile' => 3600,              // 1 hour
        'contacts' => 1800,             // 30 minutes
    ],
],
```

## Environment Variables Summary

```bash
# Connection
EVOLUTION_API_SERVER_URL=http://localhost:8080
EVOLUTION_API_KEY=your-api-key
EVOLUTION_API_DEFAULT_INSTANCE=default

# HTTP
EVOLUTION_API_TIMEOUT=30
EVOLUTION_API_CONNECT_TIMEOUT=10
EVOLUTION_API_VERIFY_SSL=true

# Webhooks
EVOLUTION_API_WEBHOOK_ENABLED=true
EVOLUTION_API_WEBHOOK_PATH=/api/evolution-api/webhook
EVOLUTION_API_WEBHOOK_SECRET=
EVOLUTION_API_WEBHOOK_VERIFY=false
EVOLUTION_API_WEBHOOK_QUEUE=true
EVOLUTION_API_WEBHOOK_QUEUE_CONNECTION=redis
EVOLUTION_API_WEBHOOK_QUEUE_NAME=webhooks
EVOLUTION_API_WEBHOOK_LOG=true
EVOLUTION_API_WEBHOOK_LOG_CHANNEL=stack

# Queue
EVOLUTION_API_QUEUE_ENABLED=true
EVOLUTION_API_QUEUE_CONNECTION=redis
EVOLUTION_API_QUEUE_NAME=whatsapp

# Rate Limiting
EVOLUTION_API_RATE_LIMIT_ENABLED=true

# Database
EVOLUTION_API_DATABASE_ENABLED=true
EVOLUTION_API_DATABASE_CONNECTION=
EVOLUTION_API_MESSAGE_RETENTION=30
EVOLUTION_API_WEBHOOK_RETENTION=7

# Logging
EVOLUTION_API_LOGGING_ENABLED=true
EVOLUTION_API_LOG_CHANNEL=stack
EVOLUTION_API_LOG_LEVEL=debug
EVOLUTION_API_LOG_REQUESTS=false
EVOLUTION_API_LOG_RESPONSES=false

# Cache
EVOLUTION_API_CACHE_ENABLED=true
EVOLUTION_API_CACHE_STORE=redis
```

## Configuration Validation

Access configuration values:

```php
// Using config helper
$serverUrl = config('evolution-api.server_url');

// Check if feature is enabled
if (config('evolution-api.webhook.enabled')) {
    // Webhooks are enabled
}

// Get with default
$timeout = config('evolution-api.http.timeout', 30);
```

## Runtime Configuration

Override configuration at runtime:

```php
// Temporarily change config
config(['evolution-api.http.timeout' => 60]);

// Or use connection options
$api = EvolutionApi::connection('secondary');
```
