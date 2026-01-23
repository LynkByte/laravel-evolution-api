# Testing Fakes

The package provides a comprehensive fake implementation for testing your WhatsApp integrations without making actual API calls.

## Overview

`EvolutionApiFake` is a test double that:

- Records all messages and API calls
- Provides customizable response stubs
- Includes built-in assertion methods
- Supports all message types and API operations

## Basic Usage

### Setting Up the Fake

```php
use Lynkbyte\EvolutionApi\Testing\Fakes\EvolutionApiFake;

class SendNotificationTest extends TestCase
{
    protected EvolutionApiFake $fake;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fake = new EvolutionApiFake();
        
        // Bind the fake to the container
        $this->app->instance('evolution-api', $this->fake);
    }
}
```

### Using with Laravel's Service Container

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;
use Lynkbyte\EvolutionApi\Testing\Fakes\EvolutionApiFake;

// In your test
$fake = new EvolutionApiFake();
EvolutionApi::swap($fake);

// Now all calls go through the fake
EvolutionApi::sendText('instance', '5511999999999', 'Hello!');

// Assert messages were sent
$fake->assertMessageSent('5511999999999');
```

## Sending Messages

The fake supports all message types:

```php
// Text messages
$fake->sendText('instance', '5511999999999', 'Hello World');

// Media messages
$fake->sendMedia('instance', '5511999999999', [
    'mediatype' => 'image',
    'media' => 'https://example.com/image.jpg',
]);

// Audio messages
$fake->sendAudio('instance', '5511999999999', 'https://example.com/audio.mp3');

// Location messages
$fake->sendLocation('instance', '5511999999999', -23.5505, -46.6333);

// Contact messages
$fake->sendContact('instance', '5511999999999', [
    'fullName' => 'John Doe',
    'phoneNumber' => '5511888888888',
]);

// Poll messages
$fake->sendPoll('instance', '5511999999999', 'Favorite color?', ['Red', 'Blue', 'Green']);

// List messages
$fake->sendList('instance', '5511999999999', [
    'title' => 'Menu',
    'sections' => [...],
]);
```

## Using Fake Resources

The fake provides resource classes that mirror the real API:

```php
// Instance resource
$fake->instance('my-instance')->create([...]);
$fake->instance('my-instance')->getQrCode();
$fake->instance('my-instance')->connectionState();

// Message resource
$fake->message('my-instance')->sendText('5511999999999', 'Hello');
$fake->message('my-instance')->sendMedia('5511999999999', [...]);

// Chat resource
$fake->chat('my-instance')->isWhatsApp('5511999999999');

// Group resource
$fake->group('my-instance')->create('Group Name', ['5511999999999']);

// Profile resource
$fake->profile('my-instance')->fetchProfile();
$fake->profile('my-instance')->updateName('New Name');

// Webhook resource
$fake->webhook('my-instance')->set([...]);
$fake->webhook('my-instance')->get();

// Settings resource
$fake->settings('my-instance')->get();
$fake->settings('my-instance')->set([...]);
```

## Connection Chaining

Set a default instance for chained operations:

```php
$fake->connection('my-instance')
    ->message()
    ->sendText('5511999999999', 'Hello');

// Equivalent to
$fake->message('my-instance')->sendText('5511999999999', 'Hello');
```

## Stubbing Responses

### Simple Response Stubbing

```php
$fake->stubResponse('sendText', [
    'key' => [
        'remoteJid' => '5511999999999@s.whatsapp.net',
        'fromMe' => true,
        'id' => 'custom-message-id',
    ],
    'status' => 'SENT',
]);
```

### Callable Response Stubbing

```php
$fake->stubResponse('sendText', function () {
    return [
        'key' => ['id' => 'MSG_' . uniqid()],
        'status' => 'SENT',
    ];
});
```

### Global Callback Stubbing

```php
$fake->stubUsing(function (string $operation) {
    return match ($operation) {
        'sendText' => ['status' => 'sent'],
        'sendMedia' => ['status' => 'uploaded'],
        default => ['status' => 'ok'],
    };
});
```

## Default Responses

The fake provides sensible defaults for common operations:

| Operation | Default Response |
|-----------|------------------|
| `sendText` | Message key with random ID, PENDING status |
| `sendMedia` | Message key with random ID, PENDING status |
| `createInstance` | Instance created with 'test-instance' name |
| `fetchInstances` | Array with one open instance |
| `connectionState` | Instance in 'open' state |
| `getQrCode` | Fake base64 QR code data |
| `isWhatsApp` | `true` (number exists on WhatsApp) |

## Recording Control

### Disable Recording

```php
$fake->disableRecording();

// These won't be recorded
$fake->sendText('instance', '5511999999999', 'Test');

$fake->enableRecording();

// This will be recorded
$fake->sendText('instance', '5511999999999', 'Recorded');
```

### Clear Recorded Data

```php
$fake->sendText('instance', '5511999999999', 'Test 1');
$fake->sendText('instance', '5511999999999', 'Test 2');

$fake->clear();

$fake->assertNothingSent(); // Passes
```

## Retrieving Recorded Data

### Get All Sent Messages

```php
$messages = $fake->getSentMessages();

// Returns array of:
// [
//     'type' => 'text',
//     'instance' => 'my-instance',
//     'number' => '5511999999999',
//     'data' => ['text' => 'Hello', 'options' => []],
//     'timestamp' => 1234567890.123,
// ]
```

### Get Last Message

```php
$lastMessage = $fake->getLastMessage();

if ($lastMessage) {
    echo $lastMessage['data']['text'];
}
```

### Get All API Calls

```php
$calls = $fake->getApiCalls();

// Returns array of:
// [
//     'operation' => 'createInstance',
//     'data' => ['instanceName' => 'test'],
//     'timestamp' => 1234567890.123,
// ]
```

### Get Last API Call

```php
$lastCall = $fake->getLastApiCall();
```

### Get Message Count by Type

```php
$fake->sendText('instance', '5511999999999', 'Hello');
$fake->sendMedia('instance', '5511999999999', [...]);
$fake->sendText('instance', '5511999999999', 'World');

$counts = $fake->getMessageCountByType();
// ['text' => 2, 'media' => 1]
```

## Fake Resource Classes

The package includes fake resource classes for all services:

| Class | Purpose |
|-------|---------|
| `FakeInstanceResource` | Instance management operations |
| `FakeMessageResource` | Message sending operations |
| `FakeChatResource` | Chat-related operations |
| `FakeGroupResource` | Group management |
| `FakeProfileResource` | Profile operations |
| `FakeWebhookResource` | Webhook configuration |
| `FakeSettingsResource` | Settings management |

Each resource class delegates to the main fake while providing the same fluent interface as the real resources.

## Best Practices

### 1. Always Use Fakes in Tests

```php
// Good: Use fake in tests
$fake = new EvolutionApiFake();
$this->app->instance('evolution-api', $fake);

// Bad: Don't use real API in tests
// $api = new EvolutionApi($config);
```

### 2. Assert After Actions

```php
// Trigger action
$this->artisan('notifications:send');

// Assert results
$fake->assertMessageSent('5511999999999');
$fake->assertMessageContains('Notification');
```

### 3. Stub Error Responses When Testing Error Handling

```php
$fake->stubResponse('sendText', function () {
    throw new \Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException(
        'Instance not connected'
    );
});
```

### 4. Clear Between Tests

```php
protected function setUp(): void
{
    parent::setUp();
    
    $this->fake = new EvolutionApiFake();
    $this->fake->clear(); // Start fresh
}
```

## Next Steps

- [Assertions](assertions.md) - Learn about available assertion methods
- [Test Examples](examples.md) - See complete test examples
