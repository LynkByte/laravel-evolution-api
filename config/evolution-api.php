<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Evolution API Server URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your Evolution API server. This should include the
    | protocol (http/https) and port if not using standard ports.
    |
    */
    'server_url' => env('EVOLUTION_API_URL', 'http://localhost:8080'),

    /*
    |--------------------------------------------------------------------------
    | Global API Key
    |--------------------------------------------------------------------------
    |
    | The global API key for authenticating with Evolution API. This key
    | is used for all API requests unless overridden per-instance.
    |
    */
    'api_key' => env('EVOLUTION_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default Instance
    |--------------------------------------------------------------------------
    |
    | The default Evolution API instance to use when no instance is specified.
    | This allows for simpler API calls in applications with a single instance.
    |
    */
    'default_instance' => env('EVOLUTION_DEFAULT_INSTANCE'),

    /*
    |--------------------------------------------------------------------------
    | Multiple Connections (Multi-Tenancy)
    |--------------------------------------------------------------------------
    |
    | Support for multiple Evolution API servers. Each connection can have
    | its own server URL and API key. Use connection('name') to switch.
    |
    */
    'connections' => [
        'default' => [
            'server_url' => env('EVOLUTION_API_URL', 'http://localhost:8080'),
            'api_key' => env('EVOLUTION_API_KEY'),
        ],
        // Add additional connections as needed:
        // 'secondary' => [
        //     'server_url' => env('EVOLUTION_API_URL_SECONDARY'),
        //     'api_key' => env('EVOLUTION_API_KEY_SECONDARY'),
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the underlying HTTP client behavior including timeouts,
    | retries, and SSL verification settings.
    |
    | Note: Message operations typically take longer than other API calls
    | due to WhatsApp's encryption handshake. Use 'message_timeout' for
    | message-specific operations.
    |
    */
    'http' => [
        'timeout' => env('EVOLUTION_HTTP_TIMEOUT', 30),
        'connect_timeout' => env('EVOLUTION_HTTP_CONNECT_TIMEOUT', 10),
        'message_timeout' => env('EVOLUTION_HTTP_MESSAGE_TIMEOUT', 60), // Longer timeout for messages
        'retry_times' => env('EVOLUTION_HTTP_RETRY_TIMES', 3),
        'retry_sleep' => env('EVOLUTION_HTTP_RETRY_SLEEP', 1000), // milliseconds
        'verify_ssl' => env('EVOLUTION_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Sending Configuration
    |--------------------------------------------------------------------------
    |
    | Configure message sending behavior including connection verification,
    | timeout handling, and retry strategies.
    |
    | Known Issue: Evolution API (via Baileys) may experience "pre-key upload
    | timeout" errors where the encryption handshake with WhatsApp servers
    | fails. This is an upstream issue, not a Laravel package issue.
    |
    */
    'messages' => [
        // Verify instance connection before sending messages
        'verify_connection_before_send' => env('EVOLUTION_VERIFY_CONNECTION', true),

        // Throw exception on timeout (false = return error response instead)
        'throw_on_timeout' => env('EVOLUTION_THROW_ON_TIMEOUT', true),

        // Wait time (seconds) for connection to stabilize after connecting
        'connection_stabilization_delay' => env('EVOLUTION_CONNECTION_DELAY', 5),

        // Maximum retries specifically for message sending
        'max_send_retries' => env('EVOLUTION_MESSAGE_MAX_RETRIES', 2),

        // Delay between message send retries (milliseconds)
        'retry_delay' => env('EVOLUTION_MESSAGE_RETRY_DELAY', 2000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the package interacts with your database. You can enable
    | or disable message/webhook storage and configure data retention.
    |
    */
    'database' => [
        'enabled' => env('EVOLUTION_DB_ENABLED', true),
        'connection' => env('EVOLUTION_DB_CONNECTION', null), // null = default
        'table_prefix' => env('EVOLUTION_TABLE_PREFIX', 'evolution_'),
        'store_messages' => env('EVOLUTION_STORE_MESSAGES', true),
        'store_webhooks' => env('EVOLUTION_STORE_WEBHOOKS', true),
        'store_instances' => env('EVOLUTION_STORE_INSTANCES', true),
        'prune_after_days' => env('EVOLUTION_PRUNE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how messages and webhooks are processed via Laravel queues.
    | You can disable queues to process everything synchronously.
    |
    */
    'queue' => [
        'enabled' => env('EVOLUTION_QUEUE_ENABLED', true),
        'connection' => env('EVOLUTION_QUEUE_CONNECTION', null), // null = default
        'queue' => env('EVOLUTION_QUEUE_NAME', 'evolution-api'),
        'retry_after' => env('EVOLUTION_QUEUE_RETRY_AFTER', 90),
        'max_exceptions' => env('EVOLUTION_QUEUE_MAX_EXCEPTIONS', 3),
        'backoff' => [60, 300, 900], // seconds between retries
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook handling including signature verification,
    | URL generation, and default events to subscribe to.
    |
    */
    'webhook' => [
        'enabled' => env('EVOLUTION_WEBHOOK_ENABLED', true),
        'route_prefix' => env('EVOLUTION_WEBHOOK_ROUTE_PREFIX', 'evolution/webhook'),
        'route_middleware' => ['api'],
        'verify_signature' => env('EVOLUTION_VERIFY_WEBHOOK', true),
        'secret' => env('EVOLUTION_WEBHOOK_SECRET'),
        'tolerance' => env('EVOLUTION_WEBHOOK_TOLERANCE', 300), // seconds
        'queue_processing' => env('EVOLUTION_WEBHOOK_QUEUE', true),
        'default_events' => [
            'APPLICATION_STARTUP',
            'QRCODE_UPDATED',
            'MESSAGES_SET',
            'MESSAGES_UPSERT',
            'MESSAGES_UPDATE',
            'MESSAGES_DELETE',
            'SEND_MESSAGE',
            'CONTACTS_SET',
            'CONTACTS_UPSERT',
            'CONTACTS_UPDATE',
            'PRESENCE_UPDATE',
            'CHATS_SET',
            'CHATS_UPSERT',
            'CHATS_UPDATE',
            'CHATS_DELETE',
            'GROUPS_UPSERT',
            'GROUP_UPDATE',
            'GROUP_PARTICIPANTS_UPDATE',
            'CONNECTION_UPDATE',
            'LABELS_EDIT',
            'LABELS_ASSOCIATION',
            'CALL',
            'TYPEBOT_START',
            'TYPEBOT_CHANGE_STATUS',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to prevent overwhelming the Evolution API
    | server and to comply with WhatsApp's messaging limits.
    |
    */
    'rate_limiting' => [
        'enabled' => env('EVOLUTION_RATE_LIMIT_ENABLED', true),
        'store' => env('EVOLUTION_RATE_LIMIT_STORE', null), // null = default, or: file, database, redis, array
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
        'on_limit_reached' => 'wait', // wait, throw, skip
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic retry behavior for failed API calls.
    |
    */
    'retry' => [
        'enabled' => env('EVOLUTION_RETRY_ENABLED', true),
        'max_attempts' => env('EVOLUTION_RETRY_MAX_ATTEMPTS', 3),
        'backoff_strategy' => 'exponential', // fixed, linear, exponential
        'base_delay' => 1000, // milliseconds
        'max_delay' => 30000, // milliseconds
        'retryable_status_codes' => [408, 429, 500, 502, 503, 504],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for API requests, responses, webhooks, and errors.
    | You can use a dedicated channel or the default Laravel logging.
    |
    */
    'logging' => [
        'enabled' => env('EVOLUTION_LOGGING_ENABLED', true),
        'channel' => env('EVOLUTION_LOG_CHANNEL', null), // null = default
        'level' => env('EVOLUTION_LOG_LEVEL', 'info'),
        'log_requests' => env('EVOLUTION_LOG_REQUESTS', true),
        'log_responses' => env('EVOLUTION_LOG_RESPONSES', true),
        'log_webhooks' => env('EVOLUTION_LOG_WEBHOOKS', true),
        'redact_sensitive' => true,
        'sensitive_fields' => [
            'apikey',
            'api_key',
            'token',
            'password',
            'secret',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Configuration
    |--------------------------------------------------------------------------
    |
    | Configure metrics collection for monitoring API usage and performance.
    |
    */
    'metrics' => [
        'enabled' => env('EVOLUTION_METRICS_ENABLED', false),
        'driver' => env('EVOLUTION_METRICS_DRIVER', 'database'), // database, prometheus, null
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

    /*
    |--------------------------------------------------------------------------
    | Notifications Configuration
    |--------------------------------------------------------------------------
    |
    | Configure notifications for important events like connection drops,
    | critical errors, or rate limit warnings.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Media Configuration
    |--------------------------------------------------------------------------
    |
    | Configure media handling for file uploads and downloads.
    |
    */
    'media' => [
        'disk' => env('EVOLUTION_MEDIA_DISK', 'local'),
        'path' => env('EVOLUTION_MEDIA_PATH', 'evolution-api/media'),
        'max_size' => env('EVOLUTION_MEDIA_MAX_SIZE', 16777216), // 16MB in bytes
        'allowed_types' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', '3gp', 'mov'],
            'audio' => ['mp3', 'ogg', 'wav', 'aac', 'm4a'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for frequently accessed data like instance status.
    |
    */
    'cache' => [
        'enabled' => env('EVOLUTION_CACHE_ENABLED', true),
        'store' => env('EVOLUTION_CACHE_STORE', null), // null = default
        'prefix' => 'evolution_api_',
        'ttl' => [
            'instance_status' => 300, // 5 minutes
            'qr_code' => 30, // 30 seconds
            'profile' => 3600, // 1 hour
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable debug mode for detailed error messages and request/response
    | dumps. Should be disabled in production.
    |
    */
    'debug' => env('EVOLUTION_DEBUG', false),
];
