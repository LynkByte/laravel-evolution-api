# Frequently Asked Questions

Common questions and answers about the Laravel Evolution API package.

---

## General Questions

### What is Evolution API?

Evolution API is an open-source WhatsApp API server built on top of the [Baileys library](https://github.com/WhiskeySockets/Baileys). It provides a REST API interface for WhatsApp messaging, allowing you to:

- Send and receive messages
- Manage WhatsApp instances
- Handle webhooks for real-time events
- Work with groups, contacts, and media

This Laravel package provides a clean, Laravel-native interface to interact with Evolution API.

---

### Is this the official WhatsApp API?

No. This package works with Evolution API, which uses the **unofficial Baileys library** that reverse-engineers the WhatsApp Web protocol.

**Key differences:**

| Feature | Evolution API (Baileys) | WhatsApp Business API (Official) |
|---------|------------------------|----------------------------------|
| Cost | Free (self-hosted) | Per-conversation pricing |
| Setup | Install Evolution API | Apply through Meta |
| Approval | None needed | Business verification required |
| Message templates | Not required | Required for outbound |
| Rate limits | Self-managed | Meta-enforced |
| Risk | Account ban possible | Supported by Meta |

---

### Can I use this in production?

Yes, many businesses use Evolution API in production. However, be aware of:

1. **Terms of Service**: Using unofficial APIs may violate WhatsApp's ToS
2. **Ban risk**: Aggressive usage can result in account bans
3. **Stability**: Depends on Baileys library and WhatsApp changes
4. **No SLA**: No guaranteed uptime or support

**Recommendations for production:**

- Use a dedicated phone number (not your personal one)
- Start with low message volumes and increase gradually
- Implement proper error handling and monitoring
- Have a backup communication channel

---

### What Evolution API version should I use?

We recommend **Evolution API v2.3.7 or later**, built from source.

| Version | Recommendation |
|---------|---------------|
| v2.3.7+ | Best choice |
| v2.3.x | Good |
| v2.2.x | Acceptable |
| v2.1.x | Avoid (Docker Hub images have issues) |
| v2.0.x | Not tested |

See [Known Limitations](known-limitations.md#evolution-api-version-compatibility) for details.

---

### What's the difference between WHATSAPP-BAILEYS and WHATSAPP-BUSINESS?

When creating instances, you can specify the integration type:

```php
EvolutionApi::instances()->create([
    'instanceName' => 'my-instance',
    'integration' => 'WHATSAPP-BAILEYS',  // or 'WHATSAPP-BUSINESS'
]);
```

| Integration | Description |
|-------------|-------------|
| `WHATSAPP-BAILEYS` | Standard WhatsApp connection via Baileys (most common) |
| `WHATSAPP-BUSINESS` | WhatsApp Business app connection (different features) |

Most users should use `WHATSAPP-BAILEYS`.

---

## Setup & Configuration

### How do I connect multiple WhatsApp numbers?

Create multiple instances, one per phone number:

```php
// Create instances
EvolutionApi::instances()->create(['instanceName' => 'sales-line']);
EvolutionApi::instances()->create(['instanceName' => 'support-line']);
EvolutionApi::instances()->create(['instanceName' => 'marketing-line']);

// Send from specific instance
EvolutionApi::for('sales-line')
    ->messages()
    ->sendText('5511999999999', 'Hello from sales!');

EvolutionApi::for('support-line')
    ->messages()
    ->sendText('5511999999999', 'Hello from support!');
```

Each instance requires scanning a QR code with a different phone.

---

### How do I use multiple Evolution API servers?

Configure multiple connections in your config:

```php
// config/evolution-api.php
'connections' => [
    'default' => [
        'server_url' => env('EVOLUTION_API_URL'),
        'api_key' => env('EVOLUTION_API_KEY'),
    ],
    'backup' => [
        'server_url' => env('EVOLUTION_BACKUP_URL'),
        'api_key' => env('EVOLUTION_BACKUP_KEY'),
    ],
    'eu-server' => [
        'server_url' => env('EVOLUTION_EU_URL'),
        'api_key' => env('EVOLUTION_EU_KEY'),
    ],
],
```

Then specify the connection:

```php
// Use specific connection
EvolutionApi::connection('backup')
    ->for('my-instance')
    ->messages()
    ->sendText(...);

// Or set default
EvolutionApi::setDefaultConnection('eu-server');
```

---

### How do I handle webhooks?

**Step 1: Configure webhook URL in Evolution API**

```php
EvolutionApi::for('my-instance')->webhooks()->set([
    'url' => 'https://your-app.com/api/webhooks/evolution/my-instance',
    'events' => [
        'MESSAGES_UPSERT',
        'MESSAGES_UPDATE', 
        'CONNECTION_UPDATE',
    ],
]);
```

**Step 2: The package auto-registers routes, or add manually:**

```php
// routes/api.php
Route::post('/webhooks/evolution/{instance}', [
    \Lynkbyte\EvolutionApi\Http\Controllers\WebhookController::class,
    'handle'
]);
```

**Step 3: Create event listeners**

```php
// app/Listeners/HandleIncomingMessage.php
use Lynkbyte\EvolutionApi\Events\MessageReceived;

class HandleIncomingMessage
{
    public function handle(MessageReceived $event): void
    {
        $from = $event->payload->data['key']['remoteJid'] ?? null;
        $text = $event->payload->data['message']['conversation'] ?? null;
        
        Log::info("Message from {$from}: {$text}");
    }
}
```

**Step 4: Register in EventServiceProvider**

```php
protected $listen = [
    \Lynkbyte\EvolutionApi\Events\MessageReceived::class => [
        \App\Listeners\HandleIncomingMessage::class,
    ],
];
```

---

### How do I run Evolution API with Docker?

Basic `docker-compose.yml`:

```yaml
version: '3'

services:
  evolution-api:
    image: atendai/evolution-api:latest
    ports:
      - "8080:8080"
    environment:
      - AUTHENTICATION_API_KEY=your-secret-api-key
      - AUTHENTICATION_EXPOSE_IN_FETCH_INSTANCES=true
    volumes:
      - evolution_store:/evolution/store
      - evolution_instances:/evolution/instances

volumes:
  evolution_store:
  evolution_instances:
```

Then configure Laravel:

```env
EVOLUTION_API_BASE_URL=http://localhost:8080
EVOLUTION_API_KEY=your-secret-api-key
```

---

## Messaging Questions

### Why do my messages show as sent but never deliver?

Several possible causes:

1. **Wrong number format**
   ```php
   // Correct
   '5511999999999'
   
   // Wrong
   '+5511999999999'
   '55 11 99999-9999'
   ```

2. **Number not on WhatsApp**
   ```php
   $check = EvolutionApi::for('instance')->chats()->checkNumber('5511999999999');
   ```

3. **Recipient blocked you** - No way to detect via API

4. **Pre-key issue** - See [Known Limitations](known-limitations.md#pre-key-upload-timeout-issue)

---

### How do I send to groups?

Use the group JID instead of phone number:

```php
// Get group JID first
$groups = EvolutionApi::for('instance')->groups()->fetchAll(true);
// Returns: [{ "id": "123456789@g.us", "subject": "My Group", ... }]

// Send to group
EvolutionApi::for('instance')
    ->messages()
    ->sendText('123456789@g.us', 'Hello group!');
```

---

### What media formats are supported?

| Type | Formats | Max Size |
|------|---------|----------|
| Images | JPEG, PNG | 5 MB |
| Videos | MP4, 3GPP | 16 MB |
| Audio | MP3, OGG, AAC, AMR | 16 MB |
| Documents | PDF, DOC, XLS, etc. | 100 MB |
| Stickers | WebP | 500 KB |

```php
// Send image
EvolutionApi::for('instance')->messages()->sendMedia([
    'number' => '5511999999999',
    'mediatype' => 'image',
    'media' => 'https://example.com/image.jpg',
    'caption' => 'Check this out!',
]);

// Send document
EvolutionApi::for('instance')->messages()->sendMedia([
    'number' => '5511999999999',
    'mediatype' => 'document',
    'media' => 'https://example.com/report.pdf',
    'fileName' => 'Monthly Report.pdf',
]);
```

---

### How do I handle message status updates?

Listen for `MESSAGES_UPDATE` webhook events:

```php
use Lynkbyte\EvolutionApi\Events\MessageStatusUpdated;

class HandleMessageStatus
{
    public function handle(MessageStatusUpdated $event): void
    {
        $messageId = $event->payload->data['key']['id'] ?? null;
        $status = $event->payload->data['status'] ?? null;
        
        // Status values: PENDING, SENT, DELIVERED, READ, PLAYED, FAILED
        
        // Update your database
        Message::where('whatsapp_id', $messageId)
            ->update(['status' => $status]);
    }
}
```

---

### Can I send template messages?

Evolution API (Baileys) doesn't use WhatsApp-approved templates like the official Business API. You can send any message content directly:

```php
// No templates needed - send directly
EvolutionApi::for('instance')
    ->messages()
    ->sendText('5511999999999', 'Your order #123 has shipped!');
```

However, for interactive messages (lists, buttons), use:

```php
// List message
EvolutionApi::for('instance')->messages()->sendList([
    'number' => '5511999999999',
    'title' => 'Menu',
    'description' => 'Choose an option',
    'buttonText' => 'View Options',
    'sections' => [
        [
            'title' => 'Products',
            'rows' => [
                ['title' => 'Product A', 'rowId' => 'prod_a'],
                ['title' => 'Product B', 'rowId' => 'prod_b'],
            ],
        ],
    ],
]);
```

---

## Error & Issue Questions

### Why am I getting "pre-key timeout" errors?

This is a known upstream issue in the Baileys library. See [Pre-Key Upload Timeout Issue](known-limitations.md#pre-key-upload-timeout-issue) for full details.

**Quick fixes:**

1. Wait a few minutes and retry
2. Reconnect the instance
3. Increase timeout: `EVOLUTION_HTTP_MESSAGE_TIMEOUT=120`
4. Use `waitUntilReady()` after connecting

---

### Why does my instance keep disconnecting?

Common causes:

1. **Phone internet issues** - Phone must stay connected
2. **WhatsApp logged out** - Check phone for "Logged out of all devices" 
3. **Session corruption** - Delete and recreate instance
4. **Network instability** - Check Evolution API server connectivity
5. **Memory issues** - Evolution API needs sufficient RAM

**Monitoring disconnects:**

```php
// Listen for CONNECTION_UPDATE events
if ($payload->event === 'CONNECTION_UPDATE') {
    $state = $payload->data['state'];
    
    if ($state === 'close') {
        // Alert and attempt reconnect
        EvolutionApi::for($instance)->instances()->connect();
    }
}
```

---

### How do I handle rate limits?

```php
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;

try {
    EvolutionApi::for('instance')
        ->messages()
        ->sendText($number, $message);
} catch (RateLimitException $e) {
    // Get retry delay
    $retryAfter = $e->getRetryAfter();
    
    // Re-queue with delay
    SendMessageJob::dispatch($instance, 'text', $dto)
        ->delay(now()->addSeconds($retryAfter));
}
```

**Prevention:**

- Add delays between messages (100-500ms)
- Use queue with rate limiting
- Monitor your sending patterns

---

### What do the different connection states mean?

| State | Meaning | Action |
|-------|---------|--------|
| `open` | Connected and ready | Can send messages |
| `connecting` | Establishing connection | Wait |
| `close` | Disconnected | Reconnect |
| `qr` | Waiting for QR scan | Display QR code |
| `unknown` | State cannot be determined | Check logs |

```php
$state = EvolutionApi::for('instance')
    ->instances()
    ->connectionState();
    
$currentState = $state->getData()['state'] ?? 'unknown';
```

---

## Advanced Questions

### How do I scale to multiple instances?

**Architecture recommendations:**

1. **Single Evolution API server**: Up to ~20-50 instances
2. **Multiple servers**: Load balance across Evolution API instances
3. **Database sharding**: Separate databases for high-volume logging

**Laravel queue configuration:**

```php
// config/queue.php
'connections' => [
    'whatsapp' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'whatsapp',
        'retry_after' => 90,
        'block_for' => 5,
    ],
],

// Start multiple workers
// php artisan queue:work whatsapp --queue=whatsapp-high,whatsapp-default
```

**Horizontal scaling:**

```php
// config/evolution-api.php
'connections' => [
    'server-1' => [...],
    'server-2' => [...],
    'server-3' => [...],
],

// Distribute instances across servers
$server = 'server-' . (crc32($instanceName) % 3 + 1);
EvolutionApi::connection($server)->for($instanceName)->...
```

---

### What's the recommended queue configuration?

For production WhatsApp messaging:

```php
// config/evolution-api.php
'queue' => [
    'enabled' => true,
    'connection' => 'redis',  // Use Redis, not database
    'queue' => 'whatsapp',
],

// Create dedicated queue for messages
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 120,  // Longer for message timeouts
        'block_for' => 5,
    ],
],
```

**Supervisor configuration:**

```ini
[program:whatsapp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --queue=whatsapp --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/supervisor/whatsapp-worker.log
```

---

### How do I implement retry logic?

**In jobs:**

```php
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;

class CustomSendMessageJob extends SendMessageJob
{
    public $tries = 3;
    public $backoff = [30, 60, 120];  // Exponential backoff
    
    public function handle(): void
    {
        try {
            parent::handle();
        } catch (MessageTimeoutException $e) {
            if ($e->isPossiblePreKeyIssue()) {
                // Don't retry pre-key issues immediately
                $this->release(300);  // Wait 5 minutes
            } else {
                throw $e;  // Normal retry
            }
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        // Log failed message
        FailedMessage::create([
            'instance' => $this->instanceName,
            'recipient' => $this->dto->number,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

**Manual retry:**

```php
try {
    $response = EvolutionApi::for('instance')
        ->messages()
        ->sendText($number, $message);
} catch (MessageTimeoutException $e) {
    // Retry up to 3 times
    retry(3, function () use ($number, $message) {
        return EvolutionApi::for('instance')
            ->messages()
            ->sendText($number, $message);
    }, 1000);  // 1 second between retries
}
```

---

### Can I run multiple Evolution API servers?

Yes, for high availability or geographic distribution:

**Load balancer setup:**

```
                    ┌──────────────────┐
                    │   Load Balancer  │
                    └────────┬─────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
┌───────▼───────┐   ┌───────▼───────┐   ┌───────▼───────┐
│ Evolution API │   │ Evolution API │   │ Evolution API │
│   Server 1    │   │   Server 2    │   │   Server 3    │
└───────────────┘   └───────────────┘   └───────────────┘
```

**Important**: Each instance should exist on only ONE server. Don't share instances across servers.

**Laravel configuration:**

```php
'connections' => [
    'server-1' => [
        'server_url' => 'https://evo1.example.com',
        'api_key' => env('EVO1_API_KEY'),
    ],
    'server-2' => [
        'server_url' => 'https://evo2.example.com',
        'api_key' => env('EVO2_API_KEY'),
    ],
],

// Track which instance is on which server
// Use database or cache to map instance -> server
```

---

### How do I monitor message delivery rates?

**Using package metrics:**

```php
use Lynkbyte\EvolutionApi\Metrics\MetricsCollector;

$metrics = app(MetricsCollector::class);

$stats = [
    'sent' => $metrics->get('messages_sent'),
    'delivered' => $metrics->get('messages_delivered'),
    'failed' => $metrics->get('messages_failed'),
    'delivery_rate' => $metrics->get('messages_delivered') / $metrics->get('messages_sent') * 100,
];
```

**Custom tracking:**

```php
// In your message sending service
public function send(string $to, string $message): void
{
    $response = EvolutionApi::for('instance')
        ->messages()
        ->sendText($to, $message);
    
    // Store for tracking
    SentMessage::create([
        'whatsapp_id' => $response->getData()['key']['id'],
        'recipient' => $to,
        'status' => 'sent',
        'sent_at' => now(),
    ]);
}

// In webhook handler for MESSAGES_UPDATE
public function handle(WebhookPayloadDto $payload): void
{
    $messageId = $payload->data['key']['id'];
    $status = $payload->data['status'];
    
    SentMessage::where('whatsapp_id', $messageId)
        ->update([
            'status' => $status,
            'delivered_at' => $status === 'DELIVERED' ? now() : null,
            'read_at' => $status === 'READ' ? now() : null,
        ]);
}
```

---

## Related Pages

- [Known Limitations](known-limitations.md) - Understand platform constraints
- [Troubleshooting](troubleshooting.md) - Step-by-step problem solving
- [Error Handling](../advanced/error-handling.md) - Exception handling patterns
- [Queue Configuration](../queues/overview.md) - Setting up queues
