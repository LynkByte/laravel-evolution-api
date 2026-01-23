---
title: Webhooks Service
description: Configuring webhooks for Evolution API instances
---

# Webhooks Service

The Webhook resource allows you to configure webhook endpoints for receiving events from your WhatsApp instances.

## Accessing the Resource

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$webhooks = EvolutionApi::for('my-instance')->webhooks();
```

## Setting Up Webhooks

### Basic Setup

```php
$response = EvolutionApi::for('my-instance')
    ->webhooks()
    ->set(
        url: 'https://your-app.com/evolution/webhook/my-instance',
        events: ['MESSAGES_UPSERT', 'CONNECTION_UPDATE'],
        enabled: true
    );
```

### Enable Webhook

```php
$response = EvolutionApi::for('my-instance')
    ->webhooks()
    ->enable(
        url: 'https://your-app.com/webhook',
        events: ['MESSAGES_UPSERT', 'MESSAGES_UPDATE']
    );
```

### Disable Webhook

```php
$response = EvolutionApi::for('my-instance')
    ->webhooks()
    ->disable();
```

## Getting Webhook Configuration

```php
$response = EvolutionApi::for('my-instance')
    ->webhooks()
    ->find();

$config = $response->json();
echo "URL: " . $config['url'];
echo "Enabled: " . ($config['enabled'] ? 'Yes' : 'No');
print_r($config['events']);
```

## Event Subscriptions

### Subscribe to Specific Events

```php
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;

$response = EvolutionApi::for('my-instance')
    ->webhooks()
    ->subscribeToEvents([
        WebhookEvent::MESSAGES_UPSERT,
        WebhookEvent::MESSAGES_UPDATE,
        WebhookEvent::CONNECTION_UPDATE,
    ]);

// Or with strings
$response = EvolutionApi::for('my-instance')
    ->webhooks()
    ->subscribeToEvents([
        'MESSAGES_UPSERT',
        'MESSAGES_UPDATE',
        'CONNECTION_UPDATE',
    ]);
```

### Subscribe to All Events

```php
$response = EvolutionApi::for('my-instance')
    ->webhooks()
    ->subscribeToAll();
```

### Subscribe to Message Events Only

```php
$response = EvolutionApi::for('my-instance')
    ->webhooks()
    ->subscribeToMessages();
```

Subscribes to: `MESSAGES_SET`, `MESSAGES_UPSERT`, `MESSAGES_UPDATE`, `MESSAGES_DELETE`, `SEND_MESSAGE`

### Subscribe to Connection Events Only

```php
$response = EvolutionApi::for('my-instance')
    ->webhooks()
    ->subscribeToConnection();
```

Subscribes to: `CONNECTION_UPDATE`, `QRCODE_UPDATED`

## Updating Webhook URL

```php
$response = EvolutionApi::for('my-instance')
    ->webhooks()
    ->updateUrl('https://new-url.com/webhook');
```

## Available Events

Get list of all available webhook events:

```php
$events = EvolutionApi::for('my-instance')
    ->webhooks()
    ->availableEvents();

// Returns array of event names
```

### Event Categories

| Category | Events |
|----------|--------|
| **Messages** | `MESSAGES_SET`, `MESSAGES_UPSERT`, `MESSAGES_UPDATE`, `MESSAGES_DELETE`, `SEND_MESSAGE` |
| **Connection** | `CONNECTION_UPDATE`, `QRCODE_UPDATED`, `APPLICATION_STARTUP` |
| **Contacts** | `CONTACTS_SET`, `CONTACTS_UPSERT`, `CONTACTS_UPDATE` |
| **Chats** | `CHATS_SET`, `CHATS_UPSERT`, `CHATS_UPDATE`, `CHATS_DELETE` |
| **Groups** | `GROUPS_UPSERT`, `GROUP_UPDATE`, `GROUP_PARTICIPANTS_UPDATE` |
| **Presence** | `PRESENCE_UPDATE` |
| **Labels** | `LABELS_EDIT`, `LABELS_ASSOCIATION` |
| **Calls** | `CALL` |
| **Typebot** | `TYPEBOT_START`, `TYPEBOT_CHANGE_STATUS` |

## Advanced Options

### With Base64 Media

```php
$response = EvolutionApi::for('my-instance')
    ->webhooks()
    ->set(
        url: 'https://your-app.com/webhook',
        events: ['MESSAGES_UPSERT'],
        enabled: true,
        webhookBase64: true  // Include media as base64
    );
```

### Webhook By Events

```php
$response = EvolutionApi::for('my-instance')
    ->webhooks()
    ->set(
        url: 'https://your-app.com/webhook',
        events: ['MESSAGES_UPSERT'],
        enabled: true,
        webhookByEvents: true  // Separate endpoint per event
    );
```

## Method Reference

| Method | Description |
|--------|-------------|
| `set($url, $events, $enabled, ...)` | Configure webhook |
| `find()` | Get current configuration |
| `enable($url, $events)` | Enable webhook |
| `disable()` | Disable webhook |
| `updateUrl($url)` | Update webhook URL |
| `subscribeToEvents($events, $url)` | Subscribe to specific events |
| `subscribeToAll($url)` | Subscribe to all events |
| `subscribeToMessages($url)` | Subscribe to message events |
| `subscribeToConnection($url)` | Subscribe to connection events |
| `availableEvents()` | Get list of available events |

---

## Next Steps

- [Webhooks Overview](../webhooks/overview.md) - Processing incoming webhooks
- [Webhook Events](../webhooks/events.md) - Event reference
- [Webhook Handlers](../webhooks/handlers.md) - Creating handlers
