# Webhook Events Reference

Complete reference of all webhook events from Evolution API.

## Event Categories

Evolution API sends webhooks for various event categories:

- **Message Events** - Message sent, received, updated, deleted
- **Connection Events** - Connection status, QR codes
- **Contact Events** - Contact created, updated
- **Chat Events** - Chat created, updated, deleted
- **Group Events** - Group created, updated, participants changed
- **Other Events** - Calls, labels, presence updates

## All Webhook Events

### Message Events

#### MESSAGES_UPSERT

Fired when a new message is received.

```php
WebhookEvent::MESSAGES_UPSERT
```

**Payload Example:**
```json
{
  "event": "MESSAGES_UPSERT",
  "instance": "my-instance",
  "data": {
    "key": {
      "remoteJid": "5511999999999@s.whatsapp.net",
      "fromMe": false,
      "id": "3EB0B430A3B8C3B1D4E2"
    },
    "pushName": "John Doe",
    "message": {
      "conversation": "Hello, how are you?"
    },
    "messageType": "conversation",
    "messageTimestamp": 1698765432
  }
}
```

**Laravel Event:** `MessageReceived`

---

#### MESSAGES_UPDATE

Fired when a message status is updated (delivered, read).

```php
WebhookEvent::MESSAGES_UPDATE
```

**Payload Example:**
```json
{
  "event": "MESSAGES_UPDATE",
  "instance": "my-instance",
  "data": {
    "key": {
      "remoteJid": "5511999999999@s.whatsapp.net",
      "fromMe": true,
      "id": "3EB0B430A3B8C3B1D4E2"
    },
    "status": 3
  }
}
```

**Status Codes:**
| Code | Status |
|------|--------|
| 1 | Pending |
| 2 | Sent (Server ACK) |
| 3 | Delivered |
| 4 | Read |
| 5 | Played (for audio) |

**Laravel Events:** `MessageDelivered`, `MessageRead`

---

#### SEND_MESSAGE

Fired when your instance sends a message.

```php
WebhookEvent::SEND_MESSAGE
```

**Payload Example:**
```json
{
  "event": "SEND_MESSAGE",
  "instance": "my-instance",
  "data": {
    "key": {
      "remoteJid": "5511999999999@s.whatsapp.net",
      "fromMe": true,
      "id": "3EB0B430A3B8C3B1D4E2"
    },
    "message": {
      "conversation": "Hello from my app!"
    },
    "messageTimestamp": 1698765432,
    "status": "PENDING"
  }
}
```

**Laravel Event:** `MessageSent`

---

#### MESSAGES_DELETE

Fired when a message is deleted.

```php
WebhookEvent::MESSAGES_DELETE
```

**Payload Example:**
```json
{
  "event": "MESSAGES_DELETE",
  "instance": "my-instance",
  "data": {
    "key": {
      "remoteJid": "5511999999999@s.whatsapp.net",
      "fromMe": false,
      "id": "3EB0B430A3B8C3B1D4E2"
    }
  }
}
```

---

#### MESSAGES_SET

Fired on initial sync when messages are loaded.

```php
WebhookEvent::MESSAGES_SET
```

---

### Connection Events

#### CONNECTION_UPDATE

Fired when connection status changes.

```php
WebhookEvent::CONNECTION_UPDATE
```

**Payload Example:**
```json
{
  "event": "CONNECTION_UPDATE",
  "instance": "my-instance",
  "data": {
    "state": "open",
    "statusReason": 200
  }
}
```

**Connection States:**
| State | Description |
|-------|-------------|
| `open` | Connected and ready |
| `close` | Disconnected |
| `connecting` | Attempting to connect |

**Laravel Events:** `ConnectionUpdated`, `InstanceStatusChanged`

---

#### QRCODE_UPDATED

Fired when a new QR code is generated for pairing.

```php
WebhookEvent::QRCODE_UPDATED
```

**Payload Example:**
```json
{
  "event": "QRCODE_UPDATED",
  "instance": "my-instance",
  "data": {
    "qrcode": {
      "base64": "data:image/png;base64,iVBORw0KGgo...",
      "code": "2@abc123..."
    },
    "pairingCode": "ABCD-EFGH",
    "count": 1
  }
}
```

**Laravel Event:** `QrCodeReceived`

---

#### APPLICATION_STARTUP

Fired when Evolution API starts.

```php
WebhookEvent::APPLICATION_STARTUP
```

---

### Contact Events

#### CONTACTS_UPSERT

Fired when a new contact is added.

```php
WebhookEvent::CONTACTS_UPSERT
```

**Payload Example:**
```json
{
  "event": "CONTACTS_UPSERT",
  "instance": "my-instance",
  "data": {
    "id": "5511999999999@s.whatsapp.net",
    "name": "John Doe",
    "notify": "John"
  }
}
```

---

#### CONTACTS_UPDATE

Fired when a contact is updated.

```php
WebhookEvent::CONTACTS_UPDATE
```

---

#### CONTACTS_SET

Fired on initial sync when contacts are loaded.

```php
WebhookEvent::CONTACTS_SET
```

---

### Chat Events

#### CHATS_UPSERT

Fired when a new chat is created.

```php
WebhookEvent::CHATS_UPSERT
```

**Payload Example:**
```json
{
  "event": "CHATS_UPSERT",
  "instance": "my-instance",
  "data": {
    "id": "5511999999999@s.whatsapp.net",
    "name": "John Doe",
    "unreadCount": 1,
    "conversationTimestamp": 1698765432
  }
}
```

---

#### CHATS_UPDATE

Fired when a chat is updated.

```php
WebhookEvent::CHATS_UPDATE
```

---

#### CHATS_DELETE

Fired when a chat is deleted.

```php
WebhookEvent::CHATS_DELETE
```

---

#### CHATS_SET

Fired on initial sync when chats are loaded.

```php
WebhookEvent::CHATS_SET
```

---

### Group Events

#### GROUPS_UPSERT

Fired when a group is created or you join a group.

```php
WebhookEvent::GROUPS_UPSERT
```

**Payload Example:**
```json
{
  "event": "GROUPS_UPSERT",
  "instance": "my-instance",
  "data": {
    "id": "123456789@g.us",
    "subject": "My Group",
    "subjectOwner": "5511999999999@s.whatsapp.net",
    "subjectTime": 1698765432,
    "size": 10,
    "creation": 1698765432,
    "owner": "5511999999999@s.whatsapp.net",
    "desc": "Group description",
    "participants": [
      {
        "id": "5511999999999@s.whatsapp.net",
        "admin": "superadmin"
      }
    ]
  }
}
```

---

#### GROUP_UPDATE

Fired when group metadata is updated.

```php
WebhookEvent::GROUP_UPDATE
```

**Payload Example:**
```json
{
  "event": "GROUP_UPDATE",
  "instance": "my-instance",
  "data": {
    "id": "123456789@g.us",
    "subject": "New Group Name",
    "desc": "Updated description"
  }
}
```

---

#### GROUP_PARTICIPANTS_UPDATE

Fired when participants join, leave, or are promoted/demoted.

```php
WebhookEvent::GROUP_PARTICIPANTS_UPDATE
```

**Payload Example:**
```json
{
  "event": "GROUP_PARTICIPANTS_UPDATE",
  "instance": "my-instance",
  "data": {
    "id": "123456789@g.us",
    "participants": ["5511999999999@s.whatsapp.net"],
    "action": "add"
  }
}
```

**Actions:**
| Action | Description |
|--------|-------------|
| `add` | Participant joined |
| `remove` | Participant left/removed |
| `promote` | Promoted to admin |
| `demote` | Demoted from admin |

---

### Other Events

#### PRESENCE_UPDATE

Fired when a contact's online status changes.

```php
WebhookEvent::PRESENCE_UPDATE
```

**Payload Example:**
```json
{
  "event": "PRESENCE_UPDATE",
  "instance": "my-instance",
  "data": {
    "id": "5511999999999@s.whatsapp.net",
    "presence": "available",
    "lastSeen": 1698765432
  }
}
```

**Presence States:**
| State | Description |
|-------|-------------|
| `available` | Online |
| `unavailable` | Offline |
| `composing` | Typing |
| `recording` | Recording audio |

---

#### CALL

Fired when a call is received.

```php
WebhookEvent::CALL
```

**Payload Example:**
```json
{
  "event": "CALL",
  "instance": "my-instance",
  "data": {
    "id": "call-id",
    "from": "5511999999999@s.whatsapp.net",
    "status": "ringing",
    "isVideo": false,
    "isGroup": false
  }
}
```

---

#### LABELS_EDIT

Fired when labels are created or edited.

```php
WebhookEvent::LABELS_EDIT
```

---

#### LABELS_ASSOCIATION

Fired when labels are associated with chats.

```php
WebhookEvent::LABELS_ASSOCIATION
```

---

#### TYPEBOT_START / TYPEBOT_CHANGE_STATUS

Fired for Typebot integration events.

```php
WebhookEvent::TYPEBOT_START
WebhookEvent::TYPEBOT_CHANGE_STATUS
```

---

## WebhookEvent Enum

The package provides a `WebhookEvent` enum for type-safe event handling:

```php
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;

// All available events
WebhookEvent::APPLICATION_STARTUP
WebhookEvent::QRCODE_UPDATED
WebhookEvent::MESSAGES_SET
WebhookEvent::MESSAGES_UPSERT
WebhookEvent::MESSAGES_UPDATE
WebhookEvent::MESSAGES_DELETE
WebhookEvent::SEND_MESSAGE
WebhookEvent::CONTACTS_SET
WebhookEvent::CONTACTS_UPSERT
WebhookEvent::CONTACTS_UPDATE
WebhookEvent::PRESENCE_UPDATE
WebhookEvent::CHATS_SET
WebhookEvent::CHATS_UPSERT
WebhookEvent::CHATS_UPDATE
WebhookEvent::CHATS_DELETE
WebhookEvent::GROUPS_UPSERT
WebhookEvent::GROUP_UPDATE
WebhookEvent::GROUP_PARTICIPANTS_UPDATE
WebhookEvent::CONNECTION_UPDATE
WebhookEvent::LABELS_EDIT
WebhookEvent::LABELS_ASSOCIATION
WebhookEvent::CALL
WebhookEvent::TYPEBOT_START
WebhookEvent::TYPEBOT_CHANGE_STATUS
WebhookEvent::UNKNOWN
```

### Helper Methods

```php
$event = WebhookEvent::MESSAGES_UPSERT;

// Check event category
$event->isMessageEvent();      // true
$event->isConnectionEvent();   // false
$event->isGroupEvent();        // false
$event->isContactEvent();      // false
$event->isChatEvent();         // false

// Get human-readable label
$event->label(); // "Message Received"

// Create from string
$event = WebhookEvent::fromString('MESSAGES_UPSERT');
```

## Filtering Events

### In Configuration

Configure which events Evolution API should send:

```php
// When creating/updating instance
$evolution->instances()->create([
    'instanceName' => 'my-instance',
    'webhook' => [
        'url' => 'https://your-app.com/evolution-api/webhook',
        'events' => [
            'MESSAGES_UPSERT',
            'MESSAGES_UPDATE',
            'CONNECTION_UPDATE',
            'QRCODE_UPDATED',
        ],
    ],
]);
```

### In Custom Handlers

Filter events in your webhook handler:

```php
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;
use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;

class MessageHandler extends AbstractWebhookHandler
{
    protected array $allowedEvents = [
        WebhookEvent::MESSAGES_UPSERT,
        WebhookEvent::MESSAGES_UPDATE,
        WebhookEvent::SEND_MESSAGE,
    ];
    
    // Or use the helper method
    public function __construct()
    {
        $this->onlyMessageEvents();
    }
}
```
