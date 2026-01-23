---
title: Instances
description: Managing WhatsApp instances with Laravel Evolution API
---

# Instance Management

The Instance resource allows you to create, manage, and monitor WhatsApp instances on your Evolution API server.

## Accessing the Resource

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$instance = EvolutionApi::instances();

// Or via dependency injection
public function __construct(EvolutionService $evolution)
{
    $this->instances = $evolution->instances();
}
```

## Creating Instances

### Basic Creation

```php
$response = EvolutionApi::instances()->create('my-instance');

if ($response->isSuccessful()) {
    $instanceName = $response->json('instance.instanceName');
    $status = $response->json('instance.status');
}
```

### Advanced Creation

```php
$response = EvolutionApi::instances()->create(
    instanceName: 'my-instance',
    token: 'custom-token',        // Custom API token for this instance
    qrcode: true,                 // Return QR code immediately
    integration: 'WHATSAPP-BAILEYS',
    number: '5511999999999',      // Phone number (for API instances)
    businessId: 'business-123',   // Business ID
    options: [
        'webhookUrl' => 'https://your-app.com/webhook',
        'webhookByEvents' => true,
    ]
);
```

## Connecting Instances

### Get QR Code

```php
// Get QR code for scanning
$response = EvolutionApi::instances()->getQrCode('my-instance');

if ($response->isSuccessful()) {
    $qrCode = $response->json('qrcode');
    $base64 = $response->json('base64');
}

// Get QR code as base64 directly
$response = EvolutionApi::instances()->getQrCodeBase64('my-instance');
```

### Connect Instance

```php
// Initiate connection (returns QR code)
$response = EvolutionApi::instances()->connect('my-instance');
```

### Connect with Phone Number

For pairing code connections:

```php
$response = EvolutionApi::instances()->connectWithNumber(
    phoneNumber: '5511999999999',
    instanceName: 'my-instance'
);

// Verify the pairing code
$response = EvolutionApi::instances()->verifyCode(
    code: '123456',
    instanceName: 'my-instance'
);
```

## Connection State

### Check Connection

```php
// Get connection state
$response = EvolutionApi::instances()->connectionState('my-instance');
$state = $response->json('state'); // 'open', 'close', 'connecting'

// Simple boolean check
$isConnected = EvolutionApi::instances()->isConnected('my-instance');

if ($isConnected) {
    // Instance is ready to send messages
}
```

### Get Status as Enum

```php
use Lynkbyte\EvolutionApi\Enums\InstanceStatus;

$status = EvolutionApi::instances()->getStatus('my-instance');

match ($status) {
    InstanceStatus::CONNECTED => 'Ready to send messages',
    InstanceStatus::DISCONNECTED => 'Not connected',
    InstanceStatus::CONNECTING => 'Currently connecting...',
    InstanceStatus::UNKNOWN => 'Status unknown',
};
```

### Wait for Connection

Useful for scripts that need to wait for QR code scan:

```php
// Wait up to 60 seconds, polling every 2 seconds
$connected = EvolutionApi::instances()->waitForConnection(
    instanceName: 'my-instance',
    timeout: 60,
    interval: 2
);

if ($connected) {
    echo "Instance connected!";
} else {
    echo "Connection timed out";
}
```

## Fetching Instances

### Fetch All Instances

```php
$response = EvolutionApi::instances()->fetchAll();

foreach ($response->json() as $instance) {
    echo $instance['instanceName'] . ': ' . $instance['status'];
}
```

### Fetch Single Instance

```php
$response = EvolutionApi::instances()->fetch('my-instance');

$instanceData = $response->json();
```

## Managing Instances

### Restart Instance

```php
$response = EvolutionApi::instances()->restart('my-instance');
```

### Logout (Disconnect WhatsApp)

```php
// Disconnect from WhatsApp (keeps instance)
$response = EvolutionApi::instances()->logout('my-instance');
```

### Delete Instance

```php
// Completely remove instance
$response = EvolutionApi::instances()->remove('my-instance');
```

## Presence

Set the online presence status:

```php
// Set to online
EvolutionApi::instances()->setPresence('available', 'my-instance');

// Set to offline
EvolutionApi::instances()->setPresence('unavailable', 'my-instance');

// Set to composing (typing)
EvolutionApi::instances()->setPresence('composing', 'my-instance');

// Set to recording
EvolutionApi::instances()->setPresence('recording', 'my-instance');
```

## Instance Settings

### Get Settings

```php
$response = EvolutionApi::instances()->getSettings('my-instance');
$settings = $response->json();
```

### Update Settings

```php
$response = EvolutionApi::instances()->updateSettings([
    'rejectCall' => true,
    'msgCall' => 'Sorry, I cannot take calls right now.',
    'groupsIgnore' => false,
    'alwaysOnline' => true,
], 'my-instance');
```

## Using Default Instance

If you've configured a default instance:

```php
// config/evolution-api.php
'default_instance' => env('EVOLUTION_DEFAULT_INSTANCE', 'my-instance'),
```

You can omit the instance name:

```php
// Uses default instance
$response = EvolutionApi::instances()->connectionState();
$isConnected = EvolutionApi::instances()->isConnected();
```

Or use the fluent `for()` method:

```php
$isConnected = EvolutionApi::for('my-instance')
    ->instances()
    ->isConnected();
```

## Method Reference

| Method | Description | Returns |
|--------|-------------|---------|
| `create($name, ...)` | Create new instance | `ApiResponse` |
| `connect($name)` | Initiate connection | `ApiResponse` |
| `connectionState($name)` | Get connection state | `ApiResponse` |
| `fetchAll()` | Get all instances | `ApiResponse` |
| `fetch($name)` | Get single instance | `ApiResponse` |
| `isConnected($name)` | Check if connected | `bool` |
| `getStatus($name)` | Get status as enum | `InstanceStatus` |
| `waitForConnection($name, $timeout)` | Poll until connected | `bool` |
| `getQrCode($name)` | Get QR code | `ApiResponse` |
| `getQrCodeBase64($name)` | Get QR as base64 | `ApiResponse` |
| `refreshQrCode($name)` | Refresh QR code | `ApiResponse` |
| `setPresence($presence, $name)` | Set presence status | `ApiResponse` |
| `restart($name)` | Restart instance | `ApiResponse` |
| `logout($name)` | Disconnect WhatsApp | `ApiResponse` |
| `remove($name)` | Delete instance | `ApiResponse` |
| `getSettings($name)` | Get instance settings | `ApiResponse` |
| `updateSettings($settings, $name)` | Update settings | `ApiResponse` |

## Examples

### Instance Lifecycle

```php
// 1. Create instance
$response = EvolutionApi::instances()->create('new-instance');
$instanceName = $response->json('instance.instanceName');

// 2. Get QR code
$qr = EvolutionApi::instances()->getQrCode($instanceName);
displayQrCode($qr->json('base64'));

// 3. Wait for connection
$connected = EvolutionApi::instances()
    ->waitForConnection($instanceName, timeout: 120);

if ($connected) {
    // 4. Send first message
    EvolutionApi::for($instanceName)
        ->messages()
        ->text('5511999999999', 'Instance is ready!');
}
```

### Health Check Script

```php
$instances = EvolutionApi::instances()->fetchAll()->json();

foreach ($instances as $instance) {
    $name = $instance['instanceName'];
    $connected = EvolutionApi::instances()->isConnected($name);
    
    if (!$connected) {
        // Alert or auto-reconnect
        Log::warning("Instance {$name} is disconnected");
        
        // Try to reconnect
        EvolutionApi::instances()->connect($name);
    }
}
```

---

## Next Steps

- [Messages](messages.md) - Send messages through instances
- [Settings](settings.md) - Configure instance settings
- [Webhooks](webhooks.md) - Set up webhooks for instances
