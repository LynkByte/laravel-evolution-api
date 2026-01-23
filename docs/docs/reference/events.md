# Events Reference

Laravel events dispatched by the Evolution API package.

## Available Events

| Event | Dispatched When |
|-------|-----------------|
| `MessageReceived` | Incoming message via webhook |
| `MessageSent` | Message successfully sent |
| `MessageDelivered` | Message delivered to recipient |
| `MessageRead` | Message read by recipient |
| `MessageFailed` | Message sending failed |
| `ConnectionUpdated` | Instance connection status changed |
| `InstanceStatusChanged` | Instance status changed |
| `QrCodeReceived` | QR code generated for pairing |
| `WebhookReceived` | Any webhook received |

## BaseEvent

All events extend `BaseEvent` which provides:

```php
namespace Lynkbyte\EvolutionApi\Events;

abstract class BaseEvent
{
    public readonly string $instanceName;
    public readonly int $timestamp;
    
    public function toArray(): array;
}
```

---

## MessageReceived

Dispatched when an incoming message is received via webhook.

**Namespace:** `Lynkbyte\EvolutionApi\Events\MessageReceived`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `instanceName` | `string` | Instance that received the message |
| `message` | `array` | Raw message data |
| `sender` | `array` | Sender information |
| `messageType` | `?MessageType` | Type of message |
| `isGroup` | `bool` | Whether from a group |
| `groupId` | `?string` | Group ID if applicable |
| `timestamp` | `int` | Event timestamp |

### Methods

```php
$event->getMessageId();    // Message ID
$event->getSenderNumber(); // Sender's phone/JID
$event->getSenderName();   // Sender's push name
$event->getContent();      // Message text content
$event->isFromGroup();     // Is group message
$event->getQuotedMessage(); // Quoted message if reply
$event->isReply();         // Is this a reply
```

### Listening

```php
// EventServiceProvider
protected $listen = [
    \Lynkbyte\EvolutionApi\Events\MessageReceived::class => [
        \App\Listeners\HandleIncomingMessage::class,
    ],
];

// Listener
class HandleIncomingMessage
{
    public function handle(MessageReceived $event): void
    {
        $content = $event->getContent();
        $sender = $event->getSenderNumber();
        
        if (str_contains(strtolower($content), 'help')) {
            // Send help response
        }
    }
}
```

---

## MessageSent

Dispatched when a message is successfully sent.

**Namespace:** `Lynkbyte\EvolutionApi\Events\MessageSent`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `instanceName` | `string` | Instance that sent the message |
| `messageType` | `string` | Type of message sent |
| `message` | `array` | Original message data |
| `response` | `array` | API response |
| `timestamp` | `int` | Event timestamp |

### Methods

```php
$event->getMessageId(); // Message ID from response
$event->getRecipient(); // Recipient phone number
```

### Listening

```php
class LogSentMessage
{
    public function handle(MessageSent $event): void
    {
        Log::info('Message sent', [
            'id' => $event->getMessageId(),
            'to' => $event->getRecipient(),
            'type' => $event->messageType,
        ]);
    }
}
```

---

## MessageDelivered

Dispatched when a message is delivered to the recipient's device.

**Namespace:** `Lynkbyte\EvolutionApi\Events\MessageDelivered`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `instanceName` | `string` | Instance name |
| `messageId` | `string` | Message ID |
| `remoteJid` | `string` | Recipient JID |
| `timestamp` | `int` | Delivery timestamp |

### Listening

```php
class TrackDelivery
{
    public function handle(MessageDelivered $event): void
    {
        Message::where('message_id', $event->messageId)
            ->update(['delivered_at' => now()]);
    }
}
```

---

## MessageRead

Dispatched when a message is read by the recipient.

**Namespace:** `Lynkbyte\EvolutionApi\Events\MessageRead`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `instanceName` | `string` | Instance name |
| `messageId` | `string` | Message ID |
| `remoteJid` | `string` | Recipient JID |
| `timestamp` | `int` | Read timestamp |

### Listening

```php
class TrackReadReceipts
{
    public function handle(MessageRead $event): void
    {
        Message::where('message_id', $event->messageId)
            ->update(['read_at' => now()]);
    }
}
```

---

## MessageFailed

Dispatched when a message fails to send.

**Namespace:** `Lynkbyte\EvolutionApi\Events\MessageFailed`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `instanceName` | `string` | Instance name |
| `messageType` | `string` | Type of message |
| `message` | `array` | Original message data |
| `error` | `string` | Error message |
| `exception` | `?Throwable` | Original exception |
| `timestamp` | `int` | Failure timestamp |

### Listening

```php
class HandleFailedMessage
{
    public function handle(MessageFailed $event): void
    {
        Log::error('Message failed', [
            'error' => $event->error,
            'message' => $event->message,
        ]);
        
        // Queue for retry
        RetryMessage::dispatch($event->message)
            ->delay(now()->addMinutes(5));
    }
}
```

---

## ConnectionUpdated

Dispatched when instance connection status changes.

**Namespace:** `Lynkbyte\EvolutionApi\Events\ConnectionUpdated`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `instanceName` | `string` | Instance name |
| `status` | `InstanceStatus` | New connection status |
| `previousStatus` | `?InstanceStatus` | Previous status |
| `timestamp` | `int` | Event timestamp |

### Listening

```php
class MonitorConnection
{
    public function handle(ConnectionUpdated $event): void
    {
        if ($event->status === InstanceStatus::DISCONNECTED) {
            // Alert admin
            Notification::send($admins, new InstanceDisconnected($event));
        }
    }
}
```

---

## InstanceStatusChanged

Dispatched when any instance status changes.

**Namespace:** `Lynkbyte\EvolutionApi\Events\InstanceStatusChanged`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `instanceName` | `string` | Instance name |
| `status` | `string` | New status |
| `data` | `array` | Additional data |
| `timestamp` | `int` | Event timestamp |

---

## QrCodeReceived

Dispatched when a QR code is generated for instance pairing.

**Namespace:** `Lynkbyte\EvolutionApi\Events\QrCodeReceived`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `instanceName` | `string` | Instance name |
| `qrCode` | `string` | QR code as base64 |
| `code` | `?string` | Pairing code string |
| `timestamp` | `int` | Event timestamp |

### Listening

```php
class NotifyQrCode
{
    public function handle(QrCodeReceived $event): void
    {
        // Send QR code to admin dashboard via broadcast
        broadcast(new QrCodeGenerated(
            $event->instanceName,
            $event->qrCode
        ));
    }
}
```

---

## WebhookReceived

Dispatched for every incoming webhook (before specific event processing).

**Namespace:** `Lynkbyte\EvolutionApi\Events\WebhookReceived`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `instanceName` | `string` | Instance name |
| `event` | `WebhookEvent` | Event type enum |
| `payload` | `array` | Raw webhook payload |
| `timestamp` | `int` | Event timestamp |

### Listening

```php
class LogAllWebhooks
{
    public function handle(WebhookReceived $event): void
    {
        Log::channel('webhooks')->info('Webhook received', [
            'instance' => $event->instanceName,
            'event' => $event->event->value,
        ]);
    }
}
```

---

## Registering Listeners

### In EventServiceProvider

```php
// app/Providers/EventServiceProvider.php

use Lynkbyte\EvolutionApi\Events\MessageReceived;
use Lynkbyte\EvolutionApi\Events\MessageSent;
use Lynkbyte\EvolutionApi\Events\MessageFailed;
use Lynkbyte\EvolutionApi\Events\ConnectionUpdated;

protected $listen = [
    MessageReceived::class => [
        \App\Listeners\HandleIncomingMessage::class,
        \App\Listeners\LogIncomingMessage::class,
    ],
    MessageSent::class => [
        \App\Listeners\TrackSentMessage::class,
    ],
    MessageFailed::class => [
        \App\Listeners\RetryFailedMessage::class,
        \App\Listeners\AlertOnFailure::class,
    ],
    ConnectionUpdated::class => [
        \App\Listeners\MonitorConnection::class,
    ],
];
```

### Using Closures

```php
// AppServiceProvider or dedicated provider
use Illuminate\Support\Facades\Event;
use Lynkbyte\EvolutionApi\Events\MessageReceived;

Event::listen(MessageReceived::class, function ($event) {
    // Handle event
});
```

### Queueable Listeners

```php
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessIncomingMessage implements ShouldQueue
{
    public $queue = 'webhooks';
    
    public function handle(MessageReceived $event): void
    {
        // Process in background
    }
}
```

## Event Broadcasting

Events can be broadcast for real-time updates:

```php
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageReceived extends BaseEvent implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [
            new Channel('whatsapp.' . $this->instanceName),
        ];
    }
}
```
