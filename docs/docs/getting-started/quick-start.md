---
title: Quick Start
description: Get up and running with Laravel Evolution API in minutes
---

# Quick Start

This guide will have you sending WhatsApp messages in under 5 minutes.

## Prerequisites

Before starting, ensure you have:

- [x] [Installed the package](installation.md)
- [x] Configured your `.env` with `EVOLUTION_API_URL` and `EVOLUTION_API_KEY`
- [x] A running Evolution API server with at least one connected WhatsApp instance

## Step 1: Verify Connection

First, let's verify your application can connect to Evolution API:

```bash
php artisan evolution-api:health
```

You should see output like:

```
Evolution API Health Check
==========================

 ✓ Configuration valid
 ✓ Server reachable (https://your-server.com)
 ✓ API key valid
 ✓ Database tables exist

All checks passed!
```

## Step 2: List Your Instances

Check which WhatsApp instances are available:

```bash
php artisan evolution-api:instances
```

Output:

```
Evolution API Instances
=======================

+---------------+--------+-----------------+
| Instance      | Status | Phone           |
+---------------+--------+-----------------+
| my-instance   | open   | +55 11 99999999 |
| test-instance | close  | -               |
+---------------+--------+-----------------+
```

!!! info "Instance Status"
    - `open` - Connected and ready to send messages
    - `close` - Not connected, needs QR code scan
    - `connecting` - Currently connecting

## Step 3: Send Your First Message

### Using the Facade

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

// Send a text message
$response = EvolutionApi::message()->sendText('my-instance', [
    'number' => '5511999999999',
    'text' => 'Hello from Laravel! 🚀',
]);

// Check if successful
if ($response->successful()) {
    $messageId = $response->json('key.id');
    echo "Message sent! ID: {$messageId}";
} else {
    echo "Error: " . $response->json('message');
}
```

### Using Dependency Injection

```php
use Lynkbyte\EvolutionApi\Services\EvolutionService;

class NotificationController extends Controller
{
    public function __construct(
        private EvolutionService $evolution
    ) {}

    public function notify(Request $request)
    {
        $response = $this->evolution->message()->sendText('my-instance', [
            'number' => $request->phone,
            'text' => $request->message,
        ]);

        return response()->json([
            'success' => $response->successful(),
            'message_id' => $response->json('key.id'),
        ]);
    }
}
```

### Using the Helper Function

```php
// If you prefer a helper function approach
$response = evolution_api()->message()->sendText('my-instance', [
    'number' => '5511999999999',
    'text' => 'Hello!',
]);
```

## Step 4: Send Different Message Types

### Image Message

```php
$response = EvolutionApi::message()->sendMedia('my-instance', [
    'number' => '5511999999999',
    'mediatype' => 'image',
    'media' => 'https://example.com/image.jpg',
    'caption' => 'Check out this image!',
]);
```

### Document Message

```php
$response = EvolutionApi::message()->sendMedia('my-instance', [
    'number' => '5511999999999',
    'mediatype' => 'document',
    'media' => 'https://example.com/document.pdf',
    'fileName' => 'report.pdf',
]);
```

### Audio Message

```php
$response = EvolutionApi::message()->sendAudio('my-instance', [
    'number' => '5511999999999',
    'audio' => 'https://example.com/audio.mp3',
]);
```

### Location Message

```php
$response = EvolutionApi::message()->sendLocation('my-instance', [
    'number' => '5511999999999',
    'latitude' => -23.5505,
    'longitude' => -46.6333,
    'name' => 'São Paulo',
    'address' => 'São Paulo, Brazil',
]);
```

### Contact Card

```php
$response = EvolutionApi::message()->sendContact('my-instance', [
    'number' => '5511999999999',
    'contact' => [
        [
            'fullName' => 'John Doe',
            'wuid' => '5511888888888',
            'phoneNumber' => '+55 11 88888-8888',
        ],
    ],
]);
```

## Step 5: Send Messages via Queue

For better performance, send messages asynchronously:

```php
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;
use Lynkbyte\EvolutionApi\DTOs\Message\SendTextMessageDto;

// Create a DTO
$message = SendTextMessageDto::from([
    'number' => '5511999999999',
    'text' => 'This message is sent via queue!',
]);

// Dispatch to queue
SendMessageJob::dispatch('my-instance', $message);
```

Or use the facade with queue option:

```php
EvolutionApi::message()->sendText('my-instance', [
    'number' => '5511999999999',
    'text' => 'Queued message!',
], queue: true);
```

!!! tip "Queue Worker"
    Make sure your queue worker is running:
    ```bash
    php artisan queue:work --queue=evolution-api
    ```

## Step 6: Handle Incoming Messages

### Register Webhook Handler

Create a webhook handler to process incoming messages:

```php
// app/Webhooks/MessageReceivedHandler.php
namespace App\Webhooks;

use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;
use Lynkbyte\EvolutionApi\DTOs\WebhookPayloadDto;

class MessageReceivedHandler extends AbstractWebhookHandler
{
    protected array $events = ['MESSAGES_UPSERT'];

    public function handle(WebhookPayloadDto $payload): void
    {
        $message = $payload->data['message'] ?? [];
        $from = $payload->sender;
        $text = $message['conversation'] ?? $message['extendedTextMessage']['text'] ?? null;

        if ($text) {
            logger()->info("Message from {$from}: {$text}");
            
            // Auto-reply example
            if (str_contains(strtolower($text), 'hello')) {
                $this->reply($payload, 'Hi there! How can I help you?');
            }
        }
    }
    
    protected function reply(WebhookPayloadDto $payload, string $text): void
    {
        \Lynkbyte\EvolutionApi\Facades\EvolutionApi::message()
            ->sendText($payload->instance, [
                'number' => $payload->sender,
                'text' => $text,
            ]);
    }
}
```

### Register in Service Provider

```php
// app/Providers/AppServiceProvider.php
use App\Webhooks\MessageReceivedHandler;
use Lynkbyte\EvolutionApi\Webhooks\WebhookProcessor;

public function boot(): void
{
    $this->app->make(WebhookProcessor::class)
        ->registerHandler(new MessageReceivedHandler());
}
```

### Configure Webhook URL in Evolution API

Set your webhook URL in Evolution API to:

```
https://your-app.com/evolution/webhook/{instance-name}
```

## Step 7: Check Message Status

### Listen for Status Updates

```php
// app/Webhooks/MessageStatusHandler.php
namespace App\Webhooks;

use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;
use Lynkbyte\EvolutionApi\DTOs\WebhookPayloadDto;

class MessageStatusHandler extends AbstractWebhookHandler
{
    protected array $events = ['MESSAGES_UPDATE'];

    public function handle(WebhookPayloadDto $payload): void
    {
        $status = $payload->data['status'] ?? null;
        $messageId = $payload->data['key']['id'] ?? null;

        logger()->info("Message {$messageId} status: {$status}");
        
        // Status can be: PENDING, SENT, DELIVERED, READ, PLAYED, FAILED
    }
}
```

### Query Message from Database

If you have database storage enabled:

```php
use Lynkbyte\EvolutionApi\Models\Message;

// Find by message ID
$message = Message::where('message_id', $messageId)->first();

// Get message status
echo $message->status; // sent, delivered, read, failed

// Get all messages to a number
$messages = Message::where('remote_jid', '5511999999999@s.whatsapp.net')
    ->latest()
    ->get();
```

## Complete Example: Order Notification

Here's a complete example of sending order notifications:

```php
// app/Services/WhatsAppNotificationService.php
namespace App\Services;

use Lynkbyte\EvolutionApi\Facades\EvolutionApi;
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;
use Lynkbyte\EvolutionApi\DTOs\Message\SendTextMessageDto;
use App\Models\Order;

class WhatsAppNotificationService
{
    public function __construct(
        private string $instance = 'my-instance'
    ) {}

    public function notifyOrderCreated(Order $order): void
    {
        $message = SendTextMessageDto::from([
            'number' => $order->customer_phone,
            'text' => $this->buildOrderCreatedMessage($order),
        ]);

        SendMessageJob::dispatch($this->instance, $message);
    }

    public function notifyOrderShipped(Order $order): void
    {
        $text = "📦 Your order #{$order->id} has been shipped!\n\n";
        $text .= "Tracking: {$order->tracking_number}\n";
        $text .= "Estimated delivery: {$order->estimated_delivery->format('M d, Y')}";

        EvolutionApi::message()->sendText($this->instance, [
            'number' => $order->customer_phone,
            'text' => $text,
        ]);
    }

    private function buildOrderCreatedMessage(Order $order): string
    {
        $message = "✅ *Order Confirmed!*\n\n";
        $message .= "Order: #{$order->id}\n";
        $message .= "Total: \${$order->total}\n\n";
        $message .= "*Items:*\n";
        
        foreach ($order->items as $item) {
            $message .= "• {$item->name} x{$item->quantity}\n";
        }
        
        $message .= "\nThank you for your purchase!";
        
        return $message;
    }
}
```

Usage:

```php
// In your controller or event listener
$notifier = app(WhatsAppNotificationService::class);
$notifier->notifyOrderCreated($order);
```

---

## Next Steps

Now that you've sent your first messages, explore more features:

<div class="grid cards" markdown>

-   :material-message-text:{ .lg .middle } **Messaging**

    ---

    Learn about all message types including buttons, lists, and templates.

    [:octicons-arrow-right-24: Messaging Guide](../messaging/text-messages.md)

-   :material-webhook:{ .lg .middle } **Webhooks**

    ---

    Set up advanced webhook handlers for all event types.

    [:octicons-arrow-right-24: Webhook Guide](../webhooks/overview.md)

-   :material-cog-sync:{ .lg .middle } **Queues**

    ---

    Configure queue processing for high-volume messaging.

    [:octicons-arrow-right-24: Queue Guide](../queues/overview.md)

-   :material-test-tube:{ .lg .middle } **Testing**

    ---

    Write tests for your WhatsApp integration.

    [:octicons-arrow-right-24: Testing Guide](../testing/fakes.md)

</div>
