---
title: Configuration
description: Complete configuration reference for Laravel Evolution API
---

# Configuration

This page documents all configuration options available in `config/evolution-api.php`.

## Configuration File

After publishing, the configuration file is located at `config/evolution-api.php`. Below is a complete reference of all options.

## Server Connection

### Basic Connection

```php
'server_url' => env('EVOLUTION_API_URL', 'http://localhost:8080'),
'api_key' => env('EVOLUTION_API_KEY'),
'default_instance' => env('EVOLUTION_DEFAULT_INSTANCE'),
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `server_url` | string | `http://localhost:8080` | Base URL of your Evolution API server |
| `api_key` | string | `null` | Global API key for authentication |
| `default_instance` | string | `null` | Default instance name when not specified |

!!! tip "Default Instance"
    Setting a default instance allows you to omit the instance name in API calls:
    ```php
    // Without default instance
    EvolutionApi::message()->sendText('my-instance', [...]);
    
    // With default instance configured
    EvolutionApi::message()->sendText([...]);
    ```

### Multiple Connections (Multi-Tenancy)

For applications connecting to multiple Evolution API servers:

```php
'connections' => [
    'default' => [
        'server_url' => env('EVOLUTION_API_URL', 'http://localhost:8080'),
        'api_key' => env('EVOLUTION_API_KEY'),
    ],
    'secondary' => [
        'server_url' => env('EVOLUTION_API_URL_SECONDARY'),
        'api_key' => env('EVOLUTION_API_KEY_SECONDARY'),
    ],
],
```

Switch between connections in your code:

```php
// Use default connection
EvolutionApi::message()->sendText('instance', [...]);

// Use secondary connection
EvolutionApi::connection('secondary')->message()->sendText('instance', [...]);
```

See [Multi-Tenancy](../advanced/multi-tenancy.md) for detailed usage.

---

## HTTP Client

Configure the underlying HTTP client behavior:

```php
'http' => [
    'timeout' => env('EVOLUTION_HTTP_TIMEOUT', 30),
    'connect_timeout' => env('EVOLUTION_HTTP_CONNECT_TIMEOUT', 10),
    'retry_times' => env('EVOLUTION_HTTP_RETRY_TIMES', 3),
    'retry_sleep' => env('EVOLUTION_HTTP_RETRY_SLEEP', 1000),
    'verify_ssl' => env('EVOLUTION_VERIFY_SSL', true),
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `timeout` | int | `30` | Request timeout in seconds |
| `connect_timeout` | int | `10` | Connection timeout in seconds |
| `retry_times` | int | `3` | Number of retry attempts for failed requests |
| `retry_sleep` | int | `1000` | Delay between retries in milliseconds |
| `verify_ssl` | bool | `true` | Verify SSL certificates |

!!! warning "SSL Verification"
    Only disable SSL verification (`verify_ssl: false`) in development environments. Always keep it enabled in production.

---

## Database

Configure database storage for messages, webhooks, and instances:

```php
'database' => [
    'enabled' => env('EVOLUTION_DB_ENABLED', true),
    'connection' => env('EVOLUTION_DB_CONNECTION', null),
    'table_prefix' => env('EVOLUTION_TABLE_PREFIX', 'evolution_'),
    'store_messages' => env('EVOLUTION_STORE_MESSAGES', true),
    'store_webhooks' => env('EVOLUTION_STORE_WEBHOOKS', true),
    'store_instances' => env('EVOLUTION_STORE_INSTANCES', true),
    'prune_after_days' => env('EVOLUTION_PRUNE_DAYS', 30),
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | `true` | Enable/disable all database features |
| `connection` | string | `null` | Database connection name (null = default) |
| `table_prefix` | string | `evolution_` | Prefix for all package tables |
| `store_messages` | bool | `true` | Store sent/received messages |
| `store_webhooks` | bool | `true` | Store incoming webhooks |
| `store_instances` | bool | `true` | Store instance information |
| `prune_after_days` | int | `30` | Days to keep data before pruning |

### Pruning Old Data

Use the prune command to clean up old records:

```bash
# Prune data older than configured days
php artisan evolution-api:prune

# Prune data older than specific days
php artisan evolution-api:prune --days=7
```

See [Data Retention](../database/data-retention.md) for scheduling automatic cleanup.

---

## Queue

Configure Laravel queue integration for asynchronous processing:

```php
'queue' => [
    'enabled' => env('EVOLUTION_QUEUE_ENABLED', true),
    'connection' => env('EVOLUTION_QUEUE_CONNECTION', null),
    'queue' => env('EVOLUTION_QUEUE_NAME', 'evolution-api'),
    'retry_after' => env('EVOLUTION_QUEUE_RETRY_AFTER', 90),
    'max_exceptions' => env('EVOLUTION_QUEUE_MAX_EXCEPTIONS', 3),
    'backoff' => [60, 300, 900],
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | `true` | Enable/disable queue processing |
| `connection` | string | `null` | Queue connection name (null = default) |
| `queue` | string | `evolution-api` | Queue name for jobs |
| `retry_after` | int | `90` | Seconds before job is retried |
| `max_exceptions` | int | `3` | Max exceptions before job fails |
| `backoff` | array | `[60, 300, 900]` | Seconds between retry attempts |

!!! info "Queue Worker"
    Remember to run a queue worker for the configured queue:
    ```bash
    php artisan queue:work --queue=evolution-api
    ```

---

## Webhooks

Configure webhook handling and signature verification:

```php
'webhook' => [
    'enabled' => env('EVOLUTION_WEBHOOK_ENABLED', true),
    'route_prefix' => env('EVOLUTION_WEBHOOK_ROUTE_PREFIX', 'evolution/webhook'),
    'route_middleware' => ['api'],
    'verify_signature' => env('EVOLUTION_VERIFY_WEBHOOK', true),
    'secret' => env('EVOLUTION_WEBHOOK_SECRET'),
    'tolerance' => env('EVOLUTION_WEBHOOK_TOLERANCE', 300),
    'queue_processing' => env('EVOLUTION_WEBHOOK_QUEUE', true),
    'default_events' => [
        'MESSAGES_UPSERT',
        'MESSAGES_UPDATE',
        'CONNECTION_UPDATE',
        // ... more events
    ],
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | `true` | Enable/disable webhook handling |
| `route_prefix` | string | `evolution/webhook` | URL prefix for webhook endpoint |
| `route_middleware` | array | `['api']` | Middleware for webhook routes |
| `verify_signature` | bool | `true` | Verify webhook signatures |
| `secret` | string | `null` | Secret for signature verification |
| `tolerance` | int | `300` | Timestamp tolerance in seconds |
| `queue_processing` | bool | `true` | Process webhooks via queue |
| `default_events` | array | `[...]` | Events to subscribe to by default |

### Webhook URL

The webhook URL is automatically registered at:

```
https://your-app.com/{route_prefix}/{instance}
```

For example: `https://your-app.com/evolution/webhook/my-instance`

See [Webhooks Overview](../webhooks/overview.md) for detailed documentation.

---

## Rate Limiting

Configure rate limiting to prevent API throttling:

```php
'rate_limiting' => [
    'enabled' => env('EVOLUTION_RATE_LIMIT_ENABLED', true),
    'driver' => env('EVOLUTION_RATE_LIMIT_DRIVER', 'cache'),
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
    'on_limit_reached' => 'wait',
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | `true` | Enable/disable rate limiting |
| `driver` | string | `cache` | Storage driver: `cache`, `redis`, `array` |
| `limits` | array | `[...]` | Rate limits by category |
| `on_limit_reached` | string | `wait` | Behavior: `wait`, `throw`, `skip` |

### Rate Limit Behaviors

| Behavior | Description |
|----------|-------------|
| `wait` | Wait until rate limit resets, then retry |
| `throw` | Throw `RateLimitExceededException` |
| `skip` | Skip the request and return null |

See [Rate Limiting](../advanced/rate-limiting.md) for advanced configuration.

---

## Retry Configuration

Configure automatic retry behavior for failed requests:

```php
'retry' => [
    'enabled' => env('EVOLUTION_RETRY_ENABLED', true),
    'max_attempts' => env('EVOLUTION_RETRY_MAX_ATTEMPTS', 3),
    'backoff_strategy' => 'exponential',
    'base_delay' => 1000,
    'max_delay' => 30000,
    'retryable_status_codes' => [408, 429, 500, 502, 503, 504],
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | `true` | Enable/disable automatic retries |
| `max_attempts` | int | `3` | Maximum retry attempts |
| `backoff_strategy` | string | `exponential` | Strategy: `fixed`, `linear`, `exponential` |
| `base_delay` | int | `1000` | Base delay in milliseconds |
| `max_delay` | int | `30000` | Maximum delay in milliseconds |
| `retryable_status_codes` | array | `[408, 429, ...]` | HTTP codes that trigger retry |

### Backoff Strategies

| Strategy | Description | Example (base=1000ms) |
|----------|-------------|----------------------|
| `fixed` | Same delay every retry | 1s, 1s, 1s |
| `linear` | Delay increases linearly | 1s, 2s, 3s |
| `exponential` | Delay doubles each retry | 1s, 2s, 4s |

---

## Logging

Configure logging for requests, responses, and errors:

```php
'logging' => [
    'enabled' => env('EVOLUTION_LOGGING_ENABLED', true),
    'channel' => env('EVOLUTION_LOG_CHANNEL', null),
    'level' => env('EVOLUTION_LOG_LEVEL', 'info'),
    'log_requests' => env('EVOLUTION_LOG_REQUESTS', true),
    'log_responses' => env('EVOLUTION_LOG_RESPONSES', true),
    'log_webhooks' => env('EVOLUTION_LOG_WEBHOOKS', true),
    'redact_sensitive' => true,
    'sensitive_fields' => [
        'apikey', 'api_key', 'token', 'password', 'secret',
    ],
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | `true` | Enable/disable logging |
| `channel` | string | `null` | Log channel (null = default) |
| `level` | string | `info` | Minimum log level |
| `log_requests` | bool | `true` | Log outgoing requests |
| `log_responses` | bool | `true` | Log API responses |
| `log_webhooks` | bool | `true` | Log incoming webhooks |
| `redact_sensitive` | bool | `true` | Redact sensitive data |
| `sensitive_fields` | array | `[...]` | Fields to redact |

See [Logging](../advanced/logging.md) for setting up dedicated log channels.

---

## Metrics

Configure metrics collection for monitoring:

```php
'metrics' => [
    'enabled' => env('EVOLUTION_METRICS_ENABLED', false),
    'driver' => env('EVOLUTION_METRICS_DRIVER', 'database'),
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

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | `false` | Enable/disable metrics |
| `driver` | string | `database` | Driver: `database`, `prometheus`, `null` |
| `track` | array | `[...]` | Metrics to track |

See [Metrics](../advanced/metrics.md) for dashboard integration.

---

## Notifications

Configure alerts for important events:

```php
'notifications' => [
    'enabled' => env('EVOLUTION_NOTIFICATIONS_ENABLED', false),
    'channels' => ['mail'],
    'recipients' => [
        'mail' => env('EVOLUTION_ALERT_EMAIL'),
        'slack' => env('EVOLUTION_SLACK_WEBHOOK'),
    ],
    'notify_on' => [
        'instance_disconnected' => true,
        'message_failed' => false,
        'rate_limit_reached' => true,
        'api_error' => false,
    ],
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | `false` | Enable/disable notifications |
| `channels` | array | `['mail']` | Notification channels |
| `recipients` | array | `[...]` | Channel-specific recipients |
| `notify_on` | array | `[...]` | Events that trigger notifications |

---

## Media

Configure media file handling:

```php
'media' => [
    'disk' => env('EVOLUTION_MEDIA_DISK', 'local'),
    'path' => env('EVOLUTION_MEDIA_PATH', 'evolution-api/media'),
    'max_size' => env('EVOLUTION_MEDIA_MAX_SIZE', 16777216),
    'allowed_types' => [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'video' => ['mp4', '3gp', 'mov'],
        'audio' => ['mp3', 'ogg', 'wav', 'aac', 'm4a'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
    ],
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `disk` | string | `local` | Filesystem disk for media |
| `path` | string | `evolution-api/media` | Storage path |
| `max_size` | int | `16777216` | Max file size in bytes (16MB) |
| `allowed_types` | array | `[...]` | Allowed file extensions by type |

---

## Cache

Configure caching for performance:

```php
'cache' => [
    'enabled' => env('EVOLUTION_CACHE_ENABLED', true),
    'store' => env('EVOLUTION_CACHE_STORE', null),
    'prefix' => 'evolution_api_',
    'ttl' => [
        'instance_status' => 300,
        'qr_code' => 30,
        'profile' => 3600,
    ],
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | `true` | Enable/disable caching |
| `store` | string | `null` | Cache store (null = default) |
| `prefix` | string | `evolution_api_` | Cache key prefix |
| `ttl` | array | `[...]` | TTL in seconds by data type |

---

## Debug Mode

Enable debug mode for development:

```php
'debug' => env('EVOLUTION_DEBUG', false),
```

!!! danger "Production Warning"
    **Never enable debug mode in production!** Debug mode exposes sensitive information and detailed error messages.

When enabled, debug mode:

- Shows detailed exception messages
- Logs full request/response bodies
- Disables sensitive data redaction

---

## Environment Variables Reference

Quick reference of all environment variables:

```env
# Server Connection
EVOLUTION_API_URL=https://your-server.com
EVOLUTION_API_KEY=your-api-key
EVOLUTION_DEFAULT_INSTANCE=my-instance

# HTTP Client
EVOLUTION_HTTP_TIMEOUT=30
EVOLUTION_HTTP_CONNECT_TIMEOUT=10
EVOLUTION_HTTP_RETRY_TIMES=3
EVOLUTION_HTTP_RETRY_SLEEP=1000
EVOLUTION_VERIFY_SSL=true

# Database
EVOLUTION_DB_ENABLED=true
EVOLUTION_DB_CONNECTION=
EVOLUTION_TABLE_PREFIX=evolution_
EVOLUTION_STORE_MESSAGES=true
EVOLUTION_STORE_WEBHOOKS=true
EVOLUTION_STORE_INSTANCES=true
EVOLUTION_PRUNE_DAYS=30

# Queue
EVOLUTION_QUEUE_ENABLED=true
EVOLUTION_QUEUE_CONNECTION=
EVOLUTION_QUEUE_NAME=evolution-api
EVOLUTION_QUEUE_RETRY_AFTER=90
EVOLUTION_QUEUE_MAX_EXCEPTIONS=3

# Webhooks
EVOLUTION_WEBHOOK_ENABLED=true
EVOLUTION_WEBHOOK_ROUTE_PREFIX=evolution/webhook
EVOLUTION_VERIFY_WEBHOOK=true
EVOLUTION_WEBHOOK_SECRET=your-secret
EVOLUTION_WEBHOOK_TOLERANCE=300
EVOLUTION_WEBHOOK_QUEUE=true

# Rate Limiting
EVOLUTION_RATE_LIMIT_ENABLED=true
EVOLUTION_RATE_LIMIT_DRIVER=cache

# Retry
EVOLUTION_RETRY_ENABLED=true
EVOLUTION_RETRY_MAX_ATTEMPTS=3

# Logging
EVOLUTION_LOGGING_ENABLED=true
EVOLUTION_LOG_CHANNEL=
EVOLUTION_LOG_LEVEL=info
EVOLUTION_LOG_REQUESTS=true
EVOLUTION_LOG_RESPONSES=true
EVOLUTION_LOG_WEBHOOKS=true

# Metrics
EVOLUTION_METRICS_ENABLED=false
EVOLUTION_METRICS_DRIVER=database

# Notifications
EVOLUTION_NOTIFICATIONS_ENABLED=false
EVOLUTION_ALERT_EMAIL=admin@example.com
EVOLUTION_SLACK_WEBHOOK=

# Media
EVOLUTION_MEDIA_DISK=local
EVOLUTION_MEDIA_PATH=evolution-api/media
EVOLUTION_MEDIA_MAX_SIZE=16777216

# Cache
EVOLUTION_CACHE_ENABLED=true
EVOLUTION_CACHE_STORE=

# Debug
EVOLUTION_DEBUG=false
```

---

## Next Steps

- [Quick Start Guide](quick-start.md) - Send your first message
- [Architecture Overview](../core-concepts/architecture.md) - Understand package internals
- [Services](../services/instances.md) - Learn about available services
