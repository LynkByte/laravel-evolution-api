# WebhookPayloadDto Reference

The `WebhookPayloadDto` class provides a structured way to access webhook data.

## Overview

Every webhook from Evolution API is automatically parsed into a `WebhookPayloadDto`:

```php
use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;

// Created automatically from webhook payload
$dto = WebhookPayloadDto::fromPayload($payload);
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `event` | `string` | Raw event type string |
| `instanceName` | `string` | Instance that triggered the webhook |
| `data` | `array` | Raw webhook data (excluding event/instance) |
| `webhookEvent` | `?WebhookEvent` | Typed enum of the event |
| `apiKey` | `?string` | API key if included in payload |
| `receivedAt` | `int` | Unix timestamp when received |

## Creating a DTO

### From Raw Payload

```php
$payload = [
    'event' => 'MESSAGES_UPSERT',
    'instance' => 'my-instance',
    'data' => [
        'key' => [
            'remoteJid' => '5511999999999@s.whatsapp.net',
            'fromMe' => false,
            'id' => 'ABC123',
        ],
        'message' => [
            'conversation' => 'Hello!',
        ],
    ],
];

$dto = WebhookPayloadDto::fromPayload($payload);

$dto->event;          // "MESSAGES_UPSERT"
$dto->instanceName;   // "my-instance"
$dto->webhookEvent;   // WebhookEvent::MESSAGES_UPSERT
$dto->receivedAt;     // 1698765432
```

## Accessing Data

### Dot Notation

Use dot notation to access nested data:

```php
// Get nested values
$dto->get('key.remoteJid');           // "5511999999999@s.whatsapp.net"
$dto->get('key.id');                  // "ABC123"
$dto->get('message.conversation');    // "Hello!"

// With default value
$dto->get('message.caption', '');     // "" (default if not found)

// Check if key exists
$dto->has('message.conversation');    // true
$dto->has('message.imageMessage');    // false
```

### Common Data Accessors

The DTO provides convenient methods for common data:

```php
// Message identifiers
$dto->getMessageId();     // "ABC123"
$dto->getRemoteJid();     // "5511999999999@s.whatsapp.net"

// Message data
$dto->getMessageData();   // ['key' => [...], 'message' => [...]]
$dto->getSenderData();    // ['pushName' => 'John', ...]

// Group information
$dto->isFromGroup();      // false
$dto->getGroupId();       // null (or group JID if from group)

// Connection data
$dto->getConnectionStatus();  // "open", "close", etc.

// QR code data
$dto->getQrCode();        // Base64 QR code image
$dto->getPairingCode();   // "ABCD-EFGH"
```

## Event Type Checking

### Using the Enum

```php
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;

// Get typed event
$event = $dto->webhookEvent;  // WebhookEvent::MESSAGES_UPSERT

// Or use the method
$event = $dto->getEventType();

// Check if known event
$dto->isKnownEvent();  // true
```

### Category Checks

```php
// Check event category
$dto->isMessageEvent();     // true for message-related events
$dto->isConnectionEvent();  // true for connection events
$dto->isGroupEvent();       // true for group events
```

## Converting to Array

```php
$array = $dto->toArray();

// Returns:
[
    'event' => 'MESSAGES_UPSERT',
    'instance_name' => 'my-instance',
    'webhook_event' => 'MESSAGES_UPSERT',
    'is_known_event' => true,
    'data' => [...],
    'received_at' => 1698765432,
]
```

## Examples by Event Type

### Message Received (MESSAGES_UPSERT)

```php
$dto = WebhookPayloadDto::fromPayload([
    'event' => 'MESSAGES_UPSERT',
    'instance' => 'my-instance',
    'data' => [
        'key' => [
            'remoteJid' => '5511999999999@s.whatsapp.net',
            'fromMe' => false,
            'id' => 'MSG123',
        ],
        'pushName' => 'John Doe',
        'message' => [
            'conversation' => 'Hello, world!',
        ],
        'messageType' => 'conversation',
        'messageTimestamp' => 1698765432,
    ],
]);

// Access data
$messageId = $dto->getMessageId();        // "MSG123"
$sender = $dto->getRemoteJid();           // "5511999999999@s.whatsapp.net"
$isGroup = $dto->isFromGroup();           // false
$pushName = $dto->get('pushName');        // "John Doe"
$content = $dto->get('message.conversation'); // "Hello, world!"

// Check if it's from us
$fromMe = $dto->get('key.fromMe');        // false
```

### Group Message

```php
$dto = WebhookPayloadDto::fromPayload([
    'event' => 'MESSAGES_UPSERT',
    'instance' => 'my-instance',
    'data' => [
        'key' => [
            'remoteJid' => '120363123456789@g.us',
            'fromMe' => false,
            'id' => 'MSG456',
            'participant' => '5511999999999@s.whatsapp.net',
        ],
        'message' => [
            'conversation' => 'Hello, group!',
        ],
    ],
]);

$dto->isFromGroup();   // true
$dto->getGroupId();    // "120363123456789@g.us"
$participant = $dto->get('key.participant'); // "5511999999999@s.whatsapp.net"
```

### Message Status Update (MESSAGES_UPDATE)

```php
$dto = WebhookPayloadDto::fromPayload([
    'event' => 'MESSAGES_UPDATE',
    'instance' => 'my-instance',
    'data' => [
        'key' => [
            'remoteJid' => '5511999999999@s.whatsapp.net',
            'fromMe' => true,
            'id' => 'MSG123',
        ],
        'status' => 3, // Delivered
    ],
]);

$messageId = $dto->getMessageId();  // "MSG123"
$status = $dto->get('status');      // 3

// Status codes: 1=pending, 2=sent, 3=delivered, 4=read
```

### Connection Update

```php
$dto = WebhookPayloadDto::fromPayload([
    'event' => 'CONNECTION_UPDATE',
    'instance' => 'my-instance',
    'data' => [
        'state' => 'open',
        'statusReason' => 200,
    ],
]);

$status = $dto->getConnectionStatus();  // "open"
$dto->isConnectionEvent();              // true
```

### QR Code Generated

```php
$dto = WebhookPayloadDto::fromPayload([
    'event' => 'QRCODE_UPDATED',
    'instance' => 'my-instance',
    'data' => [
        'qrcode' => [
            'base64' => 'data:image/png;base64,iVBORw0KGgo...',
            'code' => '2@abc123...',
        ],
        'pairingCode' => 'ABCD-EFGH',
        'count' => 1,
    ],
]);

$qrCode = $dto->getQrCode();        // "data:image/png;base64,..."
$pairing = $dto->getPairingCode();  // "ABCD-EFGH"
$attempt = $dto->get('count');      // 1
```

### Media Message

```php
$dto = WebhookPayloadDto::fromPayload([
    'event' => 'MESSAGES_UPSERT',
    'instance' => 'my-instance',
    'data' => [
        'key' => [
            'remoteJid' => '5511999999999@s.whatsapp.net',
            'id' => 'MEDIA123',
        ],
        'message' => [
            'imageMessage' => [
                'url' => 'https://...',
                'mimetype' => 'image/jpeg',
                'caption' => 'Check this out!',
                'fileSha256' => '...',
                'fileLength' => 123456,
            ],
        ],
        'messageType' => 'imageMessage',
    ],
]);

$hasImage = $dto->has('message.imageMessage');           // true
$caption = $dto->get('message.imageMessage.caption');    // "Check this out!"
$mimetype = $dto->get('message.imageMessage.mimetype');  // "image/jpeg"
$url = $dto->get('message.imageMessage.url');
```

### Group Participants Update

```php
$dto = WebhookPayloadDto::fromPayload([
    'event' => 'GROUP_PARTICIPANTS_UPDATE',
    'instance' => 'my-instance',
    'data' => [
        'id' => '120363123456789@g.us',
        'participants' => ['5511999999999@s.whatsapp.net'],
        'action' => 'add',
    ],
]);

$groupId = $dto->get('id');                    // "120363123456789@g.us"
$participants = $dto->get('participants');      // ["5511999999999@s.whatsapp.net"]
$action = $dto->get('action');                  // "add"
$dto->isGroupEvent();                          // true
```

## Using in Handlers

### In Custom Handlers

```php
use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;
use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;

class MyHandler extends AbstractWebhookHandler
{
    protected function onMessageReceived(WebhookPayloadDto $payload): void
    {
        // Check if text message
        if ($payload->has('message.conversation')) {
            $text = $payload->get('message.conversation');
            $this->handleTextMessage($payload, $text);
        }
        
        // Check if image
        if ($payload->has('message.imageMessage')) {
            $image = $payload->get('message.imageMessage');
            $this->handleImageMessage($payload, $image);
        }
        
        // Check if from group
        if ($payload->isFromGroup()) {
            $groupId = $payload->getGroupId();
            $participant = $payload->get('key.participant');
            // Handle group message differently
        }
    }
    
    protected function onConnectionUpdated(WebhookPayloadDto $payload): void
    {
        $status = $payload->getConnectionStatus();
        
        match ($status) {
            'open' => $this->markInstanceOnline($payload->instanceName),
            'close' => $this->markInstanceOffline($payload->instanceName),
            default => null,
        };
    }
}
```

### In Event Listeners

Event classes also use `WebhookPayloadDto` internally:

```php
use Lynkbyte\EvolutionApi\Events\WebhookReceived;

Event::listen(WebhookReceived::class, function ($event) {
    // The event has similar accessor methods
    $event->instanceName;
    $event->payload;  // Raw array
    $event->get('data.key.id');  // Dot notation access
    $event->has('data.message');
});
```

## Method Reference

| Method | Return Type | Description |
|--------|-------------|-------------|
| `fromPayload(array)` | `self` | Create from raw payload |
| `get(string, mixed)` | `mixed` | Get value by dot notation |
| `has(string)` | `bool` | Check if key exists |
| `getEventType()` | `WebhookEvent` | Get typed event enum |
| `isKnownEvent()` | `bool` | Check if event is recognized |
| `getMessageData()` | `?array` | Get message data array |
| `getSenderData()` | `?array` | Get sender information |
| `getRemoteJid()` | `?string` | Get remote JID |
| `getMessageId()` | `?string` | Get message ID |
| `isFromGroup()` | `bool` | Check if from group |
| `getGroupId()` | `?string` | Get group ID |
| `isMessageEvent()` | `bool` | Check if message event |
| `isConnectionEvent()` | `bool` | Check if connection event |
| `isGroupEvent()` | `bool` | Check if group event |
| `getConnectionStatus()` | `?string` | Get connection state |
| `getQrCode()` | `?string` | Get QR code base64 |
| `getPairingCode()` | `?string` | Get pairing code |
| `toArray()` | `array` | Convert to array |
