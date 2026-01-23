# Enums Reference

The package provides several enums for type-safe constants.

## WebhookEvent

Webhook event types from Evolution API.

**Namespace:** `Lynkbyte\EvolutionApi\Enums\WebhookEvent`

### Values

| Value | Description |
|-------|-------------|
| `APPLICATION_STARTUP` | Application started |
| `QRCODE_UPDATED` | QR code updated |
| `MESSAGES_SET` | Messages set/synced |
| `MESSAGES_UPSERT` | Message received |
| `MESSAGES_UPDATE` | Message updated (status) |
| `MESSAGES_DELETE` | Message deleted |
| `SEND_MESSAGE` | Message sent |
| `CONTACTS_SET` | Contacts synced |
| `CONTACTS_UPSERT` | Contact added |
| `CONTACTS_UPDATE` | Contact updated |
| `PRESENCE_UPDATE` | Presence changed |
| `CHATS_SET` | Chats synced |
| `CHATS_UPSERT` | Chat added |
| `CHATS_UPDATE` | Chat updated |
| `CHATS_DELETE` | Chat deleted |
| `GROUPS_UPSERT` | Group added |
| `GROUP_UPDATE` | Group updated |
| `GROUP_PARTICIPANTS_UPDATE` | Group members changed |
| `CONNECTION_UPDATE` | Connection status changed |
| `LABELS_EDIT` | Labels edited |
| `LABELS_ASSOCIATION` | Labels associated |
| `CALL` | Call received |
| `TYPEBOT_START` | Typebot started |
| `TYPEBOT_CHANGE_STATUS` | Typebot status changed |
| `UNKNOWN` | Unknown event type |

### Methods

```php
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;

$event = WebhookEvent::MESSAGES_UPSERT;

// Check event category
$event->isMessageEvent();    // true
$event->isConnectionEvent(); // false
$event->isGroupEvent();      // false
$event->isContactEvent();    // false
$event->isChatEvent();       // false

// Get human-readable label
$event->label(); // "Message Received"

// Create from string
$event = WebhookEvent::fromString('MESSAGES_UPSERT');
```

---

## MessageType

Message types supported by Evolution API.

**Namespace:** `Lynkbyte\EvolutionApi\Enums\MessageType`

### Values

| Value | Description |
|-------|-------------|
| `TEXT` | Plain text message |
| `IMAGE` | Image message |
| `VIDEO` | Video message |
| `AUDIO` | Audio/voice message |
| `DOCUMENT` | Document/file message |
| `STICKER` | Sticker message |
| `LOCATION` | Location message |
| `CONTACT` | Single contact |
| `CONTACT_ARRAY` | Multiple contacts |
| `POLL` | Poll message |
| `LIST` | List message |
| `BUTTON` | Button message |
| `TEMPLATE` | Template message |
| `REACTION` | Reaction to message |
| `STATUS` | Status update |
| `UNKNOWN` | Unknown type |

### Methods

```php
use Lynkbyte\EvolutionApi\Enums\MessageType;

$type = MessageType::IMAGE;

// Check type category
$type->isMedia();       // true
$type->isInteractive(); // false

// Get API endpoint
$type->endpoint(); // "sendMedia"

// Get human-readable label
$type->label(); // "Image"

// Create from API response
$type = MessageType::fromApi('imagemessage'); // IMAGE
```

---

## MessageStatus

Message delivery status.

**Namespace:** `Lynkbyte\EvolutionApi\Enums\MessageStatus`

### Values

| Value | Description |
|-------|-------------|
| `PENDING` | Message queued |
| `SENT` | Sent to server |
| `DELIVERED` | Delivered to recipient |
| `READ` | Read by recipient |
| `PLAYED` | Media played |
| `FAILED` | Delivery failed |
| `DELETED` | Message deleted |
| `UNKNOWN` | Unknown status |

### Methods

```php
use Lynkbyte\EvolutionApi\Enums\MessageStatus;

$status = MessageStatus::DELIVERED;

// Check status
$status->isSent();      // true
$status->isDelivered(); // true
$status->isRead();      // false
$status->isFailed();    // false

// Get label
$status->label(); // "Delivered"

// Create from API (numeric or string)
$status = MessageStatus::fromApi(2);          // DELIVERED
$status = MessageStatus::fromApi('read_ack'); // READ

// Safe parsing
$status = MessageStatus::tryFromString('invalid'); // null
```

---

## InstanceStatus

Instance connection status.

**Namespace:** `Lynkbyte\EvolutionApi\Enums\InstanceStatus`

### Values

| Value | Description |
|-------|-------------|
| `OPEN` | Connection open |
| `CLOSE` | Connection closed |
| `CONNECTING` | Currently connecting |
| `CONNECTED` | Fully connected |
| `DISCONNECTED` | Disconnected |
| `QRCODE` | Waiting for QR scan |
| `UNKNOWN` | Unknown status |

### Methods

```php
use Lynkbyte\EvolutionApi\Enums\InstanceStatus;

$status = InstanceStatus::CONNECTED;

// Check status
$status->isConnected();    // true
$status->isDisconnected(); // false
$status->requiresQrCode(); // false

// Get label
$status->label(); // "Connected"

// Create from API
$status = InstanceStatus::fromApi('open'); // CONNECTED

// Safe parsing
$status = InstanceStatus::tryFromString('invalid'); // null
```

---

## MediaType

Media types for file uploads.

**Namespace:** `Lynkbyte\EvolutionApi\Enums\MediaType`

### Values

| Value | Description |
|-------|-------------|
| `IMAGE` | Image files |
| `VIDEO` | Video files |
| `AUDIO` | Audio files |
| `DOCUMENT` | Document files |
| `STICKER` | Sticker files |

### Methods

```php
use Lynkbyte\EvolutionApi\Enums\MediaType;

$type = MediaType::IMAGE;

// Get allowed extensions
$type->allowedExtensions(); 
// ['jpg', 'jpeg', 'png', 'gif', 'webp']

// Get max file size (bytes)
$type->maxFileSize(); // 16777216 (16MB)

// Get MIME types
$type->mimeTypes(); 
// ['image/jpeg', 'image/png', 'image/gif', 'image/webp']

// Get label
$type->label(); // "Image"

// Detect from extension
$type = MediaType::fromExtension('pdf'); // DOCUMENT

// Detect from MIME type
$type = MediaType::fromMimeType('video/mp4'); // VIDEO
```

### File Size Limits

| Type | Max Size |
|------|----------|
| IMAGE | 16 MB |
| VIDEO | 64 MB |
| AUDIO | 16 MB |
| DOCUMENT | 100 MB |
| STICKER | 500 KB |

---

## PresenceStatus

Presence/availability status.

**Namespace:** `Lynkbyte\EvolutionApi\Enums\PresenceStatus`

### Values

| Value | Description |
|-------|-------------|
| `AVAILABLE` | Online |
| `UNAVAILABLE` | Offline |
| `COMPOSING` | Typing |
| `RECORDING` | Recording audio |
| `PAUSED` | Stopped typing |

### Methods

```php
use Lynkbyte\EvolutionApi\Enums\PresenceStatus;

$presence = PresenceStatus::COMPOSING;

// Check presence
$presence->isOnline();    // false
$presence->isTyping();    // true
$presence->isRecording(); // false

// Get label
$presence->label(); // "Typing..."
```

---

## Using Enums

### In Conditionals

```php
use Lynkbyte\EvolutionApi\Enums\MessageStatus;

if ($message->status === MessageStatus::DELIVERED) {
    // Message was delivered
}

// Using match
$icon = match ($message->status) {
    MessageStatus::SENT => '✓',
    MessageStatus::DELIVERED => '✓✓',
    MessageStatus::READ => '✓✓ (blue)',
    default => '⏳',
};
```

### In Database Queries

```php
use Lynkbyte\EvolutionApi\Enums\MessageStatus;

// Query by enum value
$failed = EvolutionMessage::where('status', MessageStatus::FAILED->value)->get();
```

### Type-Hinting

```php
use Lynkbyte\EvolutionApi\Enums\MessageType;

public function handleMessage(MessageType $type, array $data): void
{
    if ($type->isMedia()) {
        $this->processMedia($data);
    }
}
```

### Validation

```php
use Illuminate\Validation\Rules\Enum;
use Lynkbyte\EvolutionApi\Enums\MessageType;

$request->validate([
    'type' => [new Enum(MessageType::class)],
]);
```
