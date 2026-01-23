---
title: Settings
description: Managing instance settings with Laravel Evolution API
---

# Settings

The Settings resource allows you to configure instance-level settings like auto-read messages, call handling, and online status.

## Accessing the Resource

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$settings = EvolutionApi::for('my-instance')->settings();
```

## Getting Settings

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->find();

$settings = $response->json();
```

## Configuring Settings

### Set Multiple Settings

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->configure([
        'rejectCall' => true,
        'msgCall' => 'Sorry, I cannot take calls.',
        'groupsIgnore' => false,
        'alwaysOnline' => true,
        'readMessages' => true,
        'readStatus' => false,
        'syncFullHistory' => true,
    ]);
```

### Set Individual Settings

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->set(['alwaysOnline' => true]);
```

## Call Handling

### Reject Calls with Message

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->rejectCallsWithMessage('Sorry, I cannot take calls. Please send a message.');
```

### Allow Calls

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->allowCalls();
```

### Custom Reject Setting

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->setRejectCalls(
        reject: true,
        message: 'Please text me instead'
    );
```

## Message Reading

### Enable Auto-Read

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->enableAutoRead();
```

### Disable Auto-Read

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->disableAutoRead();
```

### Set Read Messages

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->setReadMessages(true);
```

### Set Read Status

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->setReadStatus(true);
```

## Online Status

### Enable Always Online

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->enableAlwaysOnline();
```

### Disable Always Online

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->disableAlwaysOnline();
```

## Group Messages

### Ignore Group Messages

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->ignoreGroups();
```

### Process Group Messages

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->processGroups();
```

## History Sync

### Enable Full History Sync

```php
$response = EvolutionApi::for('my-instance')
    ->settings()
    ->setSyncFullHistory(true);
```

## Settings Reference

| Setting | Type | Description |
|---------|------|-------------|
| `rejectCall` | bool | Auto-reject incoming calls |
| `msgCall` | string | Message to send when rejecting calls |
| `groupsIgnore` | bool | Ignore messages from groups |
| `alwaysOnline` | bool | Always show online status |
| `readMessages` | bool | Auto-mark messages as read |
| `readStatus` | bool | Auto-view status updates |
| `syncFullHistory` | bool | Sync full chat history on connect |

## Method Reference

| Method | Description |
|--------|-------------|
| `find()` | Get current settings |
| `set($settings)` | Set settings array |
| `configure($options)` | Configure multiple settings |
| `setRejectCalls($reject, $message)` | Configure call rejection |
| `rejectCallsWithMessage($message)` | Reject calls with message |
| `allowCalls()` | Allow incoming calls |
| `setReadMessages($read)` | Set auto-read messages |
| `enableAutoRead()` | Enable auto-read |
| `disableAutoRead()` | Disable auto-read |
| `setReadStatus($read)` | Set auto-view status |
| `setAlwaysOnline($online)` | Set always online |
| `enableAlwaysOnline()` | Enable always online |
| `disableAlwaysOnline()` | Disable always online |
| `setGroupsIgnore($ignore)` | Set groups ignore |
| `ignoreGroups()` | Ignore group messages |
| `processGroups()` | Process group messages |
| `setSyncFullHistory($sync)` | Set history sync |

## Examples

### Business Hours Setup

```php
// During business hours - active
EvolutionApi::for('my-instance')
    ->settings()
    ->configure([
        'alwaysOnline' => true,
        'readMessages' => true,
        'rejectCall' => true,
        'msgCall' => 'Please send a WhatsApp message for faster response.',
    ]);

// Outside business hours - away
EvolutionApi::for('my-instance')
    ->settings()
    ->configure([
        'alwaysOnline' => false,
        'readMessages' => false,
        'rejectCall' => true,
        'msgCall' => 'We are closed. Business hours: Mon-Fri 9am-6pm.',
    ]);
```

### Bot Setup

```php
// Configure for chatbot usage
EvolutionApi::for('my-instance')
    ->settings()
    ->configure([
        'alwaysOnline' => true,
        'readMessages' => true,
        'readStatus' => false,
        'groupsIgnore' => true,  // Only process direct messages
        'rejectCall' => true,
        'msgCall' => 'This is an automated service. Please send a text message.',
    ]);
```

---

## Next Steps

- [Instances](instances.md) - Instance management
- [Profiles](profiles.md) - Profile management
- [Messages](messages.md) - Send messages
