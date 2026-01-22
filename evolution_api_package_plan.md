# Laravel Evolution API Package - Master Plan & Blueprint

## 📋 Executive Summary

A production-ready Laravel 12 package for seamless integration with Evolution API, providing developers with a robust, queue-driven, observable, and maintainable solution for WhatsApp messaging automation.

**Package Name:** `laravel-evolution-api`  
**Composer Package:** `lynkbyte/laravel-evolution-api`  
**Minimum PHP Version:** 8.3+  
**Laravel Version:** 12.x

---

## 🎯 Core Objectives

1. **Developer Experience First** - Simple, intuitive API with sensible defaults
2. **Production-Ready** - Queue support, retry logic, rate limiting, observability
3. **Type-Safe** - Full PHP 8.3+ type hints and Laravel IDE Helper support
4. **Observable** - Comprehensive logging, metrics, and event system
5. **Testable** - 100% test coverage with pest/phpunit
6. **Extensible** - Easy to extend and customize

---

## 🏗️ Architecture Overview

### Layer Structure

```
┌─────────────────────────────────────────┐
│         Application Layer               │
│  (Controllers, Commands, Listeners)     │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│          Service Layer                  │
│  (EvolutionService, MessageService)     │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│           API Client Layer              │
│  (HTTP Client, Rate Limiter)            │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│         Data Transfer Objects           │
│  (DTOs for type safety)                 │
└─────────────────────────────────────────┘
```

---

## 📦 Package Structure

```
laravel-evolution-api/
├── config/
│   └── evolution-api.php              # Configuration file
├── database/
│   └── migrations/
│       ├── create_evolution_instances_table.php
│       ├── create_evolution_messages_table.php
│       └── create_evolution_webhooks_table.php
├── src/
│   ├── EvolutionApiServiceProvider.php
│   ├── Facades/
│   │   └── EvolutionApi.php
│   ├── Client/
│   │   ├── EvolutionClient.php      # Main HTTP client
│   │   ├── RateLimiter.php
│   │   └── Concerns/
│   │       ├── HandlesInstances.php
│   │       ├── HandlesMessages.php
│   │       ├── HandlesChats.php
│   │       ├── HandlesGroups.php
│   │       ├── HandlesWebhooks.php
│   │       └── HandlesMedia.php
│   ├── Services/
│   │   ├── EvolutionService.php     # Main service orchestrator
│   │   ├── InstanceService.php
│   │   ├── MessageService.php
│   │   ├── WebhookService.php
│   │   └── MediaService.php
│   ├── Models/
│   │   ├── EvolutionInstance.php
│   │   ├── EvolutionMessage.php
│   │   └── EvolutionWebhook.php
│   ├── DTOs/
│   │   ├── Instance/
│   │   │   ├── CreateInstanceDto.php
│   │   │   └── InstanceSettingsDto.php
│   │   ├── Message/
│   │   │   ├── SendTextMessageDto.php
│   │   │   ├── SendMediaMessageDto.php
│   │   │   └── SendLocationDto.php
│   │   └── Webhook/
│   │       ├── WebhookConfigDto.php
│   │       └── WebhookPayloadDto.php
│   ├── Jobs/
│   │   ├── SendMessageJob.php
│   │   ├── ProcessWebhookJob.php
│   │   ├── SyncInstanceStatusJob.php
│   │   └── RetryFailedMessageJob.php
│   ├── Events/
│   │   ├── MessageSent.php
│   │   ├── MessageReceived.php
│   │   ├── MessageFailed.php
│   │   ├── InstanceConnected.php
│   │   ├── InstanceDisconnected.php
│   │   └── WebhookReceived.php
│   ├── Listeners/
│   │   ├── LogMessageActivity.php
│   │   └── UpdateInstanceStatus.php
│   ├── Observers/
│   │   ├── MessageObserver.php
│   │   └── InstanceObserver.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── WebhookController.php
│   │   ├── Middleware/
│   │   │   ├── VerifyEvolutionWebhook.php
│   │   │   └── RateLimitWebhook.php
│   │   └── Requests/
│   │       ├── SendMessageRequest.php
│   │       └── CreateInstanceRequest.php
│   ├── Console/
│   │   └── Commands/
│   │       ├── InstanceStatusCommand.php
│   │       ├── InstallCommand.php
│   │       ├── RetryFailedMessagesCommand.php
│   │       └── PruneOldDataCommand.php
│   ├── Exceptions/
│   │   ├── EvolutionApiException.php
│   │   ├── InstanceNotFoundException.php
│   │   ├── InvalidWebhookException.php
│   │   └── RateLimitException.php
│   ├── Enums/
│   │   ├── MessageStatus.php
│   │   ├── InstanceStatus.php
│   │   ├── WebhookEvent.php
│   │   └── MessageType.php
│   ├── Contracts/
│   │   ├── EvolutionClientInterface.php
│   │   └── WebhookHandlerInterface.php
│   └── Support/
│       ├── Helpers.php
│       └── WebhookSignatureValidator.php
├── tests/
│   ├── Feature/
│   │   ├── InstanceManagementTest.php
│   │   ├── MessageSendingTest.php
│   │   ├── WebhookHandlingTest.php
│   │   └── QueueIntegrationTest.php
│   ├── Unit/
│   │   ├── ClientTest.php
│   │   ├── ServiceTest.php
│   │   └── DtoTest.php
│   └── Pest.php
├── routes/
│   └── evolution-api.php              # Webhook routes
├── resources/
│   └── views/
│       └── emails/
│           └── instance-disconnected.blade.php
├── composer.json
├── phpunit.xml
├── README.md
├── CHANGELOG.md
├── CONTRIBUTING.md
└── LICENSE
```

---

## 🔧 Core Features

### 1. Instance Management

**Features:**
- Create, update, delete instances
- QR code generation and retrieval
- Connection status monitoring
- Auto-reconnect on disconnect
- Multi-instance support

**Usage:**
```php
use YourVendor\EvolutionApi\Facades\EvolutionApi;

// Create instance
$instance = EvolutionApi::createInstance('my-instance', [
    'webhook' => [
        'enabled' => true,
        'url' => route('evolution.webhook'),
        'events' => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE']
    ]
]);

// Get QR code
$qrCode = EvolutionApi::getQrCode('my-instance');

// Check status
$status = EvolutionApi::instanceStatus('my-instance');
```

### 2. Message Sending (Queue-Based)

**Features:**
- Text, media, location, contact messages
- Bulk messaging with rate limiting
- Retry logic with exponential backoff
- Message scheduling
- Message templates

**Usage:**
```php
use YourVendor\EvolutionApi\DTOs\Message\SendTextMessageDto;

// Immediate send (queued)
EvolutionApi::sendMessage(
    instance: 'my-instance',
    dto: new SendTextMessageDto(
        number: '5511999999999',
        text: 'Hello from Laravel!'
    )
);

// Scheduled send
EvolutionApi::sendMessage($dto)
    ->delay(now()->addMinutes(10));

// Bulk send with rate limiting
EvolutionApi::sendBulkMessages($recipients, $message)
    ->rateLimit(30, 60); // 30 messages per 60 seconds
```

### 3. Webhook System

**Features:**
- Automatic webhook registration
- Signature verification
- Event-driven architecture
- Async processing via queues
- Dead letter queue for failed webhooks

**Webhook Events Supported:**
- `MESSAGES_UPSERT` - New message received
- `MESSAGES_UPDATE` - Message status updated
- `MESSAGES_DELETE` - Message deleted
- `CONNECTION_UPDATE` - Connection status changed
- `QRCODE_UPDATED` - QR code updated
- `SEND_MESSAGE` - Message sent confirmation

**Usage:**
```php
// In EventServiceProvider
protected $listen = [
    MessageReceived::class => [
        ProcessIncomingMessage::class,
        LogMessageActivity::class,
    ],
];

// Custom webhook handler
class ProcessIncomingMessage
{
    public function handle(MessageReceived $event)
    {
        $payload = $event->payload;
        
        // Your business logic
        if ($payload->isFromCustomer()) {
            $this->createTicket($payload);
        }
    }
}
```

### 4. Queue Integration

**Queue Jobs:**
- `SendMessageJob` - Send messages asynchronously
- `ProcessWebhookJob` - Process webhook payloads
- `SyncInstanceStatusJob` - Periodic status checks
- `RetryFailedMessageJob` - Retry failed messages

**Configuration:**
```php
// config/evolution-api.php
'queue' => [
    'connection' => env('EVOLUTION_QUEUE_CONNECTION', 'redis'),
    'queue' => env('EVOLUTION_QUEUE_NAME', 'evolution-api'),
    'retries' => 3,
    'backoff' => [60, 300, 900], // seconds
],
```

### 5. Logging & Observability

**Features:**
- Structured logging with context
- Message audit trail
- Performance metrics
- Integration with Laravel Pulse/Telescope
- Custom log channels

**Configuration:**
```php
'logging' => [
    'enabled' => true,
    'channel' => 'evolution-api',
    'level' => 'info',
    'include_payload' => env('EVOLUTION_LOG_PAYLOADS', false),
],
```

### 6. Rate Limiting

**Features:**
- Configurable rate limits per instance
- Automatic backoff on rate limit hit
- Queue-based throttling
- Per-endpoint rate limits

**Configuration:**
```php
'rate_limiting' => [
    'enabled' => true,
    'max_attempts' => 60,
    'decay_seconds' => 60,
    'strategy' => 'sliding_window',
],
```

### 7. Error Handling & Retry Logic

**Features:**
- Automatic retry with exponential backoff
- Dead letter queue for permanent failures
- Error notifications via email/Slack
- Detailed error tracking

**Configuration:**
```php
'retry' => [
    'max_attempts' => 3,
    'backoff_strategy' => 'exponential',
    'base_delay' => 60,
    'max_delay' => 3600,
],

'notifications' => [
    'channels' => ['mail', 'slack'],
    'recipients' => [
        'mail' => env('EVOLUTION_ALERT_EMAIL'),
        'slack' => env('EVOLUTION_SLACK_WEBHOOK'),
    ],
],
```

---

## 🗄️ Database Schema

### `evolution_instances` Table
```php
Schema::create('evolution_instances', function (Blueprint $table) {
    $table->id();
    $table->string('instance_name')->unique();
    $table->string('status')->default('disconnected');
    $table->text('qr_code')->nullable();
    $table->json('settings')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('connected_at')->nullable();
    $table->timestamp('last_activity_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index('status');
    $table->index('connected_at');
});
```

### `evolution_messages` Table
```php
Schema::create('evolution_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('instance_id')->constrained('evolution_instances');
    $table->string('message_id')->nullable();
    $table->string('remote_jid');
    $table->enum('direction', ['outbound', 'inbound']);
    $table->string('type'); // text, image, video, audio, document, etc.
    $table->text('content');
    $table->json('metadata')->nullable();
    $table->string('status')->default('pending');
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
    
    $table->index(['instance_id', 'remote_jid']);
    $table->index('status');
    $table->index('created_at');
});
```

### `evolution_webhooks` Table
```php
Schema::create('evolution_webhooks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('instance_id')->constrained('evolution_instances');
    $table->string('event');
    $table->json('payload');
    $table->string('status')->default('pending');
    $table->text('error_message')->nullable();
    $table->integer('retry_count')->default(0);
    $table->timestamp('processed_at')->nullable();
    $table->timestamps();
    
    $table->index(['instance_id', 'event']);
    $table->index('status');
    $table->index('created_at');
});
```

---

## 📝 Configuration File

```php
// config/evolution-api.php
return [
    /*
    |--------------------------------------------------------------------------
    | Evolution API Server URL
    |--------------------------------------------------------------------------
    */
    'server_url' => env('EVOLUTION_API_URL', 'http://localhost:8080'),

    /*
    |--------------------------------------------------------------------------
    | Global API Key
    |--------------------------------------------------------------------------
    */
    'api_key' => env('EVOLUTION_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default Instance
    |--------------------------------------------------------------------------
    */
    'default_instance' => env('EVOLUTION_DEFAULT_INSTANCE'),

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'connection' => env('EVOLUTION_DB_CONNECTION', config('database.default')),
        'store_messages' => env('EVOLUTION_STORE_MESSAGES', true),
        'store_webhooks' => env('EVOLUTION_STORE_WEBHOOKS', true),
        'prune_after_days' => env('EVOLUTION_PRUNE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'connection' => env('EVOLUTION_QUEUE_CONNECTION', 'redis'),
        'queue' => env('EVOLUTION_QUEUE_NAME', 'evolution-api'),
        'retries' => 3,
        'backoff' => [60, 300, 900],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'enabled' => env('EVOLUTION_WEBHOOK_ENABLED', true),
        'base_url' => env('EVOLUTION_WEBHOOK_URL', env('APP_URL') . '/evolution/webhook'),
        'verify_signature' => env('EVOLUTION_VERIFY_WEBHOOK', true),
        'secret' => env('EVOLUTION_WEBHOOK_SECRET'),
        'default_events' => [
            'MESSAGES_UPSERT',
            'MESSAGES_UPDATE',
            'CONNECTION_UPDATE',
            'QRCODE_UPDATED',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_seconds' => 60,
        'strategy' => 'sliding_window',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'evolution-api',
        'level' => env('EVOLUTION_LOG_LEVEL', 'info'),
        'include_payload' => env('EVOLUTION_LOG_PAYLOADS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'max_attempts' => 3,
        'backoff_strategy' => 'exponential',
        'base_delay' => 60,
        'max_delay' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enabled' => env('EVOLUTION_NOTIFICATIONS_ENABLED', false),
        'channels' => ['mail'],
        'recipients' => [
            'mail' => env('EVOLUTION_ALERT_EMAIL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => 30,
        'retry_times' => 2,
        'retry_sleep' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Configuration
    |--------------------------------------------------------------------------
    */
    'media' => [
        'disk' => env('EVOLUTION_MEDIA_DISK', 'local'),
        'path' => 'evolution-api/media',
        'max_size' => 16777216, // 16MB
    ],
];
```

---

## 🚀 Installation & Setup

### Step 1: Install Package
```bash
composer require yourvendor/laravel-evolution-api
```

### Step 2: Publish Configuration
```bash
php artisan vendor:publish --tag=evolution-api-config
php artisan vendor:publish --tag=evolution-api-migrations
```

### Step 3: Run Migrations
```bash
php artisan migrate
```

### Step 4: Configure Environment
```env
EVOLUTION_API_URL=http://your-evolution-api:8080
EVOLUTION_API_KEY=your-global-api-key
EVOLUTION_DEFAULT_INSTANCE=my-default-instance

# Queue
EVOLUTION_QUEUE_CONNECTION=redis
EVOLUTION_QUEUE_NAME=evolution-api

# Webhooks
EVOLUTION_WEBHOOK_ENABLED=true
EVOLUTION_WEBHOOK_URL=https://your-app.com/evolution/webhook
EVOLUTION_WEBHOOK_SECRET=your-webhook-secret

# Logging
EVOLUTION_LOG_LEVEL=info
EVOLUTION_LOG_PAYLOADS=false

# Database
EVOLUTION_STORE_MESSAGES=true
EVOLUTION_PRUNE_DAYS=30
```

### Step 5: Run Install Command
```bash
php artisan evolution:install
```

This command will:
- Verify Evolution API connection
- Setup webhook routes
- Configure queue workers
- Run health checks

---

## 📚 Usage Examples

### Basic Message Sending

```php
use YourVendor\EvolutionApi\Facades\EvolutionApi;
use YourVendor\EvolutionApi\DTOs\Message\SendTextMessageDto;

// Simple text message
EvolutionApi::instance('my-instance')
    ->sendText('5511999999999', 'Hello World!');

// Using DTO for type safety
$dto = new SendTextMessageDto(
    number: '5511999999999',
    text: 'Hello from Laravel!',
    delay: 1000,
    linkPreview: true
);

EvolutionApi::sendMessage('my-instance', $dto);
```

### Media Messages

```php
use YourVendor\EvolutionApi\DTOs\Message\SendMediaMessageDto;

// Send image
$dto = new SendMediaMessageDto(
    number: '5511999999999',
    mediaType: 'image',
    media: 'https://example.com/image.jpg',
    caption: 'Check this out!'
);

EvolutionApi::sendMessage('my-instance', $dto);

// Send document
EvolutionApi::instance('my-instance')
    ->sendDocument(
        number: '5511999999999',
        file: storage_path('app/invoice.pdf'),
        filename: 'invoice.pdf'
    );
```

### Bulk Messaging with Rate Limiting

```php
$recipients = ['5511111111111', '5522222222222', '5533333333333'];
$message = 'Important announcement!';

EvolutionApi::instance('my-instance')
    ->sendBulk($recipients, $message)
    ->rateLimit(30, 60) // 30 messages per minute
    ->onQueue('high-priority');
```

### Instance Management

```php
use YourVendor\EvolutionApi\DTOs\Instance\CreateInstanceDto;

// Create instance
$instance = EvolutionApi::createInstance(
    new CreateInstanceDto(
        instanceName: 'sales-bot',
        webhook: [
            'enabled' => true,
            'url' => route('evolution.webhook'),
            'events' => ['MESSAGES_UPSERT']
        ]
    )
);

// Get QR code
$qrCode = EvolutionApi::instance('sales-bot')->qrCode();

// Check connection status
$status = EvolutionApi::instance('sales-bot')->status();

// Disconnect instance
EvolutionApi::instance('sales-bot')->disconnect();

// Delete instance
EvolutionApi::instance('sales-bot')->delete();
```

### Handling Webhooks

```php
// In EventServiceProvider
use YourVendor\EvolutionApi\Events\MessageReceived;

protected $listen = [
    MessageReceived::class => [
        SendAutoReply::class,
        CreateTicket::class,
    ],
];

// Listener example
class SendAutoReply
{
    public function handle(MessageReceived $event)
    {
        $message = $event->payload;
        
        if ($message->isFromCustomer() && !$message->fromMe) {
            EvolutionApi::instance($event->instance)
                ->sendText(
                    $message->from,
                    'Thank you for your message. We will respond shortly.'
                );
        }
    }
}
```

### Advanced Usage with Jobs

```php
use YourVendor\EvolutionApi\Jobs\SendMessageJob;

// Dispatch with custom configuration
SendMessageJob::dispatch($instance, $dto)
    ->onQueue('evolution-api')
    ->delay(now()->addMinutes(5))
    ->afterCommit();

// Chain jobs
SendMessageJob::withChain([
    new LogMessageSent($messageId),
    new NotifyAdmin($messageId),
])->dispatch($instance, $dto);
```

---

## 🧪 Testing Strategy

### Unit Tests
- Client methods
- DTOs validation
- Service layer logic
- Helper functions

### Feature Tests
- Instance creation/management
- Message sending workflows
- Webhook processing
- Queue job execution
- Database interactions

### Integration Tests
- Full message lifecycle
- Webhook end-to-end flow
- Rate limiting behavior
- Retry logic

### Example Test
```php
use YourVendor\EvolutionApi\Facades\EvolutionApi;

it('can send a text message', function () {
    $dto = new SendTextMessageDto(
        number: '5511999999999',
        text: 'Test message'
    );
    
    $response = EvolutionApi::instance('test')
        ->sendMessage($dto);
    
    expect($response)
        ->toBeInstanceOf(MessageResponse::class)
        ->and($response->success)->toBeTrue();
});

it('queues messages for async processing', function () {
    Queue::fake();
    
    EvolutionApi::sendMessage('test', $dto);
    
    Queue::assertPushed(SendMessageJob::class);
});
```

---

## 🔍 Monitoring & Metrics

### Key Metrics to Track
1. **Message Metrics**
   - Messages sent/received per hour
   - Success/failure rate
   - Average delivery time
   - Queue depth

2. **Instance Metrics**
   - Active instances
   - Connection uptime
   - Disconnection frequency
   - QR code scan rate

3. **Webhook Metrics**
   - Webhook processing time
   - Failed webhook count
   - Retry attempts

### Laravel Pulse Integration
```php
// Custom Pulse recorders
Pulse::record('evolution.message.sent', $messageId)
    ->tag(['instance' => $instance])
    ->count();

Pulse::record('evolution.webhook.processed')
    ->avg($processingTime);
```

---

## 📖 Documentation Structure

### README.md
- Quick start guide
- Installation steps
- Basic usage examples
- Configuration overview
- Links to full docs

### Full Documentation (in `/docs`)
1. **Getting Started**
   - Installation
   - Configuration
   - First Steps

2. **Core Concepts**
   - Instances
   - Messages
   - Webhooks
   - Queues

3. **Advanced Usage**
   - Custom webhooks handlers
   - Extending the client
   - Rate limiting strategies
   - Error handling

4. **API Reference**
   - Client methods
   - DTOs
   - Events
   - Commands

5. **Best Practices**
   - Production setup
   - Performance optimization
   - Security considerations
   - Troubleshooting

---

## 🤖 AI Development Blueprint

### Phase 1: Foundation (Week 1-2)
**Prompt Template:**
```
Create a Laravel 12 package service provider for Evolution API integration.
Requirements:
- Modern PHP 8.2+ syntax with type hints
- Follow Laravel conventions
- Include configuration publishing
- Register migrations and routes
- Implement service container bindings

Structure:
[Paste package structure here]
```

**Files to Generate:**
1. Service Provider
2. Configuration file
3. Base client class
4. Exception classes

### Phase 2: Core Features (Week 3-4)
**Prompt Template:**
```
Implement the EvolutionClient class with the following traits:
- HandlesInstances
- HandlesMessages
- HandlesWebhooks

Each trait should:
- Use typed DTOs for parameters
- Include comprehensive PHPDoc
- Implement error handling
- Support method chaining
- Follow PSR-12 standards

Evolution API endpoints: [list endpoints]
```

### Phase 3: Queue Integration (Week 5)
**Prompt Template:**
```
Create queue jobs for Evolution API:
1. SendMessageJob - with retry logic
2. ProcessWebhookJob - async processing
3. SyncInstanceStatusJob - periodic sync

Each job should:
- Implement ShouldQueue
- Have typed constructor parameters
- Include exponential backoff
- Log progress and errors
- Emit relevant events
```

### Phase 4: Events & Observers (Week 6)
**Prompt Template:**
```
Create event system for Evolution API:
Events: MessageSent, MessageReceived, InstanceConnected, etc.
Observers: MessageObserver, InstanceObserver

Requirements:
- Type-safe event properties
- Broadcast capability
- Queue listener support
- Integration with Laravel events
```

### Phase 5: Testing (Week 7)
**Prompt Template:**
```
Generate comprehensive Pest tests for:
- Client methods (mocked HTTP)
- Queue jobs
- Webhook processing
- Event dispatching

Use:
- Pest PHP
- HTTP fakes
- Queue fakes
- Database transactions
```

### Phase 6: Documentation (Week 8)
**Prompt Template:**
```
Generate documentation for Laravel Evolution API package:
- README.md with badges, quick start, examples
- API reference for all public methods
- Configuration guide
- Troubleshooting section
- Migration guide from other packages
```

---

## 🔐 Security Considerations

1. **Webhook Signature Verification**
   - Implement HMAC signature validation
   - Configurable secret key
   - Replay attack prevention

2. **API Key Management**
   - Never hardcode keys
   - Support per-instance keys
   - Key rotation support

3. **Input Validation**
   - Validate all DTOs
   - Sanitize webhook payloads
   - Rate limit webhook endpoints

4. **Data Privacy**
   - Optional payload logging
   - PII redaction in logs
   - GDPR compliance helpers

---

## 📊 Performance Optimization

1. **Caching Strategy**
   - Cache instance status (5 min TTL)
   - Cache QR codes until refresh
   - Cache rate limit state

2. **Database Optimization**
   - Index frequently queried columns
   - Partition large tables
   - Archive old data

3. **Queue Optimization**
   - Use separate queues for priority
   - Batch similar jobs
   - Monitor queue depth

---

## 🛠️ Maintenance & Operations

### Monitoring Commands
```bash
# Check instance status
php artisan evolution:status

# Retry failed messages
php artisan evolution:retry-failed

# Prune old data
php artisan evolution:prune --days=30

# Health check
php artisan evolution:health
```

### Scheduled Tasks
```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Sync instance status every 5 minutes
    $schedule->command('evolution:sync-status')->everyFiveMinutes();
    
    // Prune old webhooks daily
    $schedule->command('evolution:prune')->daily();
    
    // Health check hourly
    $schedule->command('evolution:health')->hourly();
}
```

---

## 📋 Roadmap

### v1.0.0 - Core Features
- ✅ Instance management
- ✅ Message sending (text, media)
- ✅ Webhook handling
- ✅ Queue integration
- ✅ Basic logging

### v1.1.0 - Enhanced Features
- Group management
- Contact management
- Message templates
- Scheduled messages
- Advanced rate limiting

### v1.2.0 - Integrations
- Chatwoot integration
- Typebot support
- n8n compatibility
- Zapier webhooks

### v2.0.0 - Enterprise Features
- Multi-tenant support
- Advanced analytics
- Message campaigns
- A/B testing
- WebSocket support

---

## 🤝 Contributing Guidelines

See CONTRIBUTING.md for:
- Code style guide
- Pull request process
- Testing requirements
- Documentation standards

---

## 📄 License

MIT License - See LICENSE file

---

## 🆘 Support

- Documentation: https://docs.yourpackage.com
- Issues: https://github.com/yourvendor/laravel-evolution-api/issues
- Discussions: https://github.com/yourvendor/laravel-evolution-api