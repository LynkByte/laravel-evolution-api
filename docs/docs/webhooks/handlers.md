# Custom Webhook Handlers

Learn how to create custom webhook handlers for advanced webhook processing.

## Why Custom Handlers?

While Laravel events work great for simple cases, custom handlers offer:

- **Organized Code** - All webhook logic in dedicated classes
- **Filtering** - Built-in instance and event filtering
- **Event Methods** - Override specific methods for each event type
- **Reusability** - Share handlers across projects

## Creating a Handler

### Basic Handler

Create a handler by extending `AbstractWebhookHandler`:

```php
namespace App\Webhooks;

use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;
use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;

class MyWebhookHandler extends AbstractWebhookHandler
{
    protected function onMessageReceived(WebhookPayloadDto $payload): void
    {
        $message = $payload->getMessageData();
        $sender = $payload->getSenderData();
        
        Log::info('Message received', [
            'from' => $sender['pushName'] ?? 'Unknown',
            'content' => $message['message']['conversation'] ?? '',
        ]);
    }
}
```

### Available Event Methods

Override these methods to handle specific events:

```php
class MyWebhookHandler extends AbstractWebhookHandler
{
    // Message Events
    protected function onMessageReceived(WebhookPayloadDto $payload): void {}
    protected function onMessageUpdated(WebhookPayloadDto $payload): void {}
    protected function onMessageSent(WebhookPayloadDto $payload): void {}
    protected function onMessageDeleted(WebhookPayloadDto $payload): void {}
    
    // Connection Events
    protected function onConnectionUpdated(WebhookPayloadDto $payload): void {}
    protected function onQrCodeReceived(WebhookPayloadDto $payload): void {}
    
    // Presence Events
    protected function onPresenceUpdated(WebhookPayloadDto $payload): void {}
    
    // Group Events
    protected function onGroupCreated(WebhookPayloadDto $payload): void {}
    protected function onGroupUpdated(WebhookPayloadDto $payload): void {}
    protected function onGroupParticipantsUpdated(WebhookPayloadDto $payload): void {}
    
    // Contact Events
    protected function onContactCreated(WebhookPayloadDto $payload): void {}
    protected function onContactUpdated(WebhookPayloadDto $payload): void {}
    
    // Chat Events
    protected function onChatCreated(WebhookPayloadDto $payload): void {}
    protected function onChatUpdated(WebhookPayloadDto $payload): void {}
    protected function onChatDeleted(WebhookPayloadDto $payload): void {}
    
    // Other Events
    protected function onCallReceived(WebhookPayloadDto $payload): void {}
    protected function onLabelsEdited(WebhookPayloadDto $payload): void {}
    protected function onLabelsAssociated(WebhookPayloadDto $payload): void {}
    
    // Catch-all for unknown events
    protected function onUnknownEvent(WebhookPayloadDto $payload): void {}
    
    // Called for EVERY webhook (after specific handler)
    protected function onWebhookReceived(WebhookPayloadDto $payload): void {}
}
```

## Registering Handlers

### In a Service Provider

Register handlers in your `AppServiceProvider` or a dedicated provider:

```php
namespace App\Providers;

use App\Webhooks\MyWebhookHandler;
use App\Webhooks\MessageHandler;
use App\Webhooks\ConnectionHandler;
use Illuminate\Support\ServiceProvider;
use Lynkbyte\EvolutionApi\Webhooks\WebhookProcessor;

class WebhookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $processor = $this->app->make(WebhookProcessor::class);
        
        // Register for specific event
        $processor->registerHandler(
            'MESSAGES_UPSERT',
            new MessageHandler()
        );
        
        // Register for multiple events
        $processor->registerHandler(
            'CONNECTION_UPDATE',
            new ConnectionHandler()
        );
        
        // Register wildcard handler (receives ALL events)
        $processor->registerWildcardHandler(new MyWebhookHandler());
    }
}
```

### Dynamic Registration

Register handlers dynamically:

```php
use Lynkbyte\EvolutionApi\Webhooks\WebhookProcessor;

$processor = app(WebhookProcessor::class);

// Add handler
$processor->registerHandler('MESSAGES_UPSERT', $handler);

// Remove handler
$processor->removeHandler('MESSAGES_UPSERT');
```

## Filtering

### Filter by Instance

Process webhooks only from specific instances:

```php
class ProductionHandler extends AbstractWebhookHandler
{
    protected array $allowedInstances = [
        'production-instance',
        'production-instance-2',
    ];
    
    protected function onMessageReceived(WebhookPayloadDto $payload): void
    {
        // Only called for production instances
    }
}

// Or set dynamically
$handler = new MyHandler();
$handler->forInstances(['instance-1', 'instance-2']);
```

### Filter by Event Type

Process only specific event types:

```php
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;

class MessageOnlyHandler extends AbstractWebhookHandler
{
    protected array $allowedEvents = [
        WebhookEvent::MESSAGES_UPSERT,
        WebhookEvent::MESSAGES_UPDATE,
        WebhookEvent::SEND_MESSAGE,
    ];
}

// Or use helper methods
$handler = new MyHandler();
$handler->onlyMessageEvents();      // Message events only
$handler->onlyConnectionEvents();   // Connection events only
$handler->onlyGroupEvents();        // Group events only

// Or set specific events
$handler->forEvents([
    WebhookEvent::MESSAGES_UPSERT,
    WebhookEvent::CONNECTION_UPDATE,
]);
```

### Custom Filtering

Override `shouldHandle` for custom logic:

```php
class BusinessHoursHandler extends AbstractWebhookHandler
{
    public function shouldHandle(WebhookPayloadDto $payload): bool
    {
        // Call parent to check instance/event filters
        if (!parent::shouldHandle($payload)) {
            return false;
        }
        
        // Only handle during business hours
        $hour = now()->hour;
        return $hour >= 9 && $hour < 18;
    }
}
```

## Complete Examples

### Auto-Reply Handler

```php
namespace App\Webhooks;

use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;
use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;

class AutoReplyHandler extends AbstractWebhookHandler
{
    protected array $allowedInstances = ['support-instance'];
    
    protected function onMessageReceived(WebhookPayloadDto $payload): void
    {
        // Don't reply to our own messages
        if ($payload->get('data.key.fromMe')) {
            return;
        }
        
        // Don't reply to group messages
        if ($payload->isFromGroup()) {
            return;
        }
        
        $message = $this->extractMessageContent($payload);
        $sender = $payload->getRemoteJid();
        
        // Auto-reply based on content
        $reply = $this->getAutoReply($message);
        
        if ($reply) {
            EvolutionApi::messages($payload->instanceName)->sendText(
                number: $sender,
                text: $reply
            );
        }
    }
    
    private function extractMessageContent(WebhookPayloadDto $payload): string
    {
        $data = $payload->getMessageData();
        
        return $data['message']['conversation'] 
            ?? $data['message']['extendedTextMessage']['text']
            ?? '';
    }
    
    private function getAutoReply(string $message): ?string
    {
        $message = strtolower(trim($message));
        
        return match (true) {
            str_contains($message, 'hello') => 'Hi! How can I help you today?',
            str_contains($message, 'hours') => 'We are open Mon-Fri, 9 AM - 6 PM.',
            str_contains($message, 'help') => 'Please visit our FAQ: https://example.com/faq',
            default => null,
        };
    }
}
```

### Message Logger Handler

```php
namespace App\Webhooks;

use App\Models\MessageLog;
use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;
use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;

class MessageLoggerHandler extends AbstractWebhookHandler
{
    public function __construct()
    {
        $this->onlyMessageEvents();
    }
    
    protected function onMessageReceived(WebhookPayloadDto $payload): void
    {
        $this->logMessage($payload, 'received');
    }
    
    protected function onMessageSent(WebhookPayloadDto $payload): void
    {
        $this->logMessage($payload, 'sent');
    }
    
    protected function onMessageUpdated(WebhookPayloadDto $payload): void
    {
        $messageId = $payload->getMessageId();
        $status = $payload->get('data.status');
        
        MessageLog::where('message_id', $messageId)
            ->update(['status' => $this->mapStatus($status)]);
    }
    
    private function logMessage(WebhookPayloadDto $payload, string $direction): void
    {
        MessageLog::create([
            'instance' => $payload->instanceName,
            'message_id' => $payload->getMessageId(),
            'remote_jid' => $payload->getRemoteJid(),
            'direction' => $direction,
            'content' => $this->extractContent($payload),
            'is_group' => $payload->isFromGroup(),
            'raw_payload' => $payload->data,
        ]);
    }
    
    private function extractContent(WebhookPayloadDto $payload): ?string
    {
        $data = $payload->getMessageData();
        return $data['message']['conversation'] ?? null;
    }
    
    private function mapStatus(int|string|null $status): string
    {
        return match ($status) {
            1 => 'pending',
            2 => 'sent',
            3 => 'delivered',
            4 => 'read',
            default => 'unknown',
        };
    }
}
```

### Connection Monitor Handler

```php
namespace App\Webhooks;

use App\Notifications\InstanceDisconnected;
use App\Notifications\QrCodeGenerated;
use Illuminate\Support\Facades\Notification;
use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;
use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;

class ConnectionMonitorHandler extends AbstractWebhookHandler
{
    public function __construct()
    {
        $this->onlyConnectionEvents();
    }
    
    protected function onConnectionUpdated(WebhookPayloadDto $payload): void
    {
        $status = $payload->getConnectionStatus();
        
        match ($status) {
            'close', 'disconnected' => $this->handleDisconnection($payload),
            'open', 'connected' => $this->handleConnection($payload),
            default => null,
        };
    }
    
    protected function onQrCodeReceived(WebhookPayloadDto $payload): void
    {
        $qrCode = $payload->getQrCode();
        $pairingCode = $payload->getPairingCode();
        $attempt = $payload->get('data.count', 1);
        
        // Notify admins about new QR code
        Notification::route('slack', config('services.slack.ops_webhook'))
            ->notify(new QrCodeGenerated(
                instance: $payload->instanceName,
                qrCode: $qrCode,
                pairingCode: $pairingCode,
                attempt: $attempt
            ));
    }
    
    private function handleDisconnection(WebhookPayloadDto $payload): void
    {
        // Update database
        Instance::where('name', $payload->instanceName)
            ->update(['status' => 'disconnected', 'disconnected_at' => now()]);
        
        // Notify team
        Notification::route('slack', config('services.slack.ops_webhook'))
            ->notify(new InstanceDisconnected($payload->instanceName));
    }
    
    private function handleConnection(WebhookPayloadDto $payload): void
    {
        Instance::where('name', $payload->instanceName)
            ->update(['status' => 'connected', 'connected_at' => now()]);
    }
}
```

### Multi-Tenant Handler

```php
namespace App\Webhooks;

use App\Models\Tenant;
use App\Services\TenantMessageService;
use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;
use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;

class MultiTenantHandler extends AbstractWebhookHandler
{
    public function __construct(
        private TenantMessageService $messageService
    ) {}
    
    public function shouldHandle(WebhookPayloadDto $payload): bool
    {
        // Only handle if instance belongs to a known tenant
        return Tenant::where('whatsapp_instance', $payload->instanceName)->exists();
    }
    
    protected function onMessageReceived(WebhookPayloadDto $payload): void
    {
        $tenant = Tenant::where('whatsapp_instance', $payload->instanceName)->first();
        
        // Set tenant context
        tenancy()->initialize($tenant);
        
        // Process message in tenant context
        $this->messageService->handleIncoming(
            sender: $payload->getRemoteJid(),
            content: $payload->getMessageData(),
            isGroup: $payload->isFromGroup()
        );
        
        // End tenant context
        tenancy()->end();
    }
}
```

## The WebhookHandlerInterface

If you need full control, implement the interface directly:

```php
use Lynkbyte\EvolutionApi\Contracts\WebhookHandlerInterface;
use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;

class CustomHandler implements WebhookHandlerInterface
{
    public function handle(WebhookPayloadDto $payload): void
    {
        // Your processing logic
    }
    
    public function shouldHandle(WebhookPayloadDto $payload): bool
    {
        return true; // Your filtering logic
    }
    
    public function events(): array
    {
        return ['MESSAGES_UPSERT', 'MESSAGES_UPDATE'];
    }
}
```

## Best Practices

### 1. Keep Handlers Focused

Create separate handlers for different concerns:

```php
// Good - focused handlers
$processor->registerHandler('MESSAGES_UPSERT', new MessageLoggerHandler());
$processor->registerHandler('MESSAGES_UPSERT', new AutoReplyHandler());
$processor->registerHandler('CONNECTION_UPDATE', new ConnectionMonitorHandler());

// Avoid - one handler doing everything
$processor->registerWildcardHandler(new DoEverythingHandler());
```

### 2. Handle Errors Gracefully

```php
protected function onMessageReceived(WebhookPayloadDto $payload): void
{
    try {
        $this->processMessage($payload);
    } catch (\Exception $e) {
        Log::error('Handler error', [
            'handler' => static::class,
            'instance' => $payload->instanceName,
            'error' => $e->getMessage(),
        ]);
        
        // Don't rethrow - allow other handlers to run
    }
}
```

### 3. Use Dependency Injection

```php
class MessageHandler extends AbstractWebhookHandler
{
    public function __construct(
        private MessageRepository $messages,
        private NotificationService $notifications,
        private LoggerInterface $logger
    ) {}
}

// Register with DI
$processor->registerHandler(
    'MESSAGES_UPSERT',
    app(MessageHandler::class)
);
```

### 4. Queue Heavy Processing

```php
protected function onMessageReceived(WebhookPayloadDto $payload): void
{
    // Quick validation in handler
    if (!$this->shouldProcess($payload)) {
        return;
    }
    
    // Queue heavy work
    ProcessIncomingMessage::dispatch(
        $payload->instanceName,
        $payload->toArray()
    );
}
```
