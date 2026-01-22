# Laravel Evolution API

[![Tests](https://github.com/lynkbyte/laravel-evolution-api/actions/workflows/tests.yml/badge.svg)](https://github.com/lynkbyte/laravel-evolution-api/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/lynkbyte/laravel-evolution-api.svg)](https://packagist.org/packages/lynkbyte/laravel-evolution-api)
[![Total Downloads](https://img.shields.io/packagist/dt/lynkbyte/laravel-evolution-api.svg)](https://packagist.org/packages/lynkbyte/laravel-evolution-api)
[![License](https://img.shields.io/packagist/l/lynkbyte/laravel-evolution-api.svg)](https://packagist.org/packages/lynkbyte/laravel-evolution-api)

A production-ready Laravel package for seamless integration with [Evolution API](https://github.com/EvolutionAPI/evolution-api) - the powerful WhatsApp messaging solution. This package provides a clean, fluent API with queue support, webhook handling, rate limiting, metrics, and comprehensive logging.

## Features

- **Full API Coverage** - Instance management, messaging, groups, profiles, webhooks, and settings
- **Multiple Message Types** - Text, media, audio, location, contacts, polls, lists, reactions, stickers, and templates
- **Queue Support** - Send messages asynchronously with Laravel queues
- **Webhook Processing** - Built-in webhook controller with signature verification
- **Rate Limiting** - Configurable rate limits to respect API constraints
- **Metrics & Logging** - Track message counts, API calls, and errors
- **Database Models** - Eloquent models for instances, messages, contacts, and webhook logs
- **Testing Utilities** - Fake implementation for unit testing your application
- **Artisan Commands** - Health checks, instance status, and maintenance commands

## Requirements

- PHP 8.3+
- Laravel 12.x
- Evolution API server

## Installation

Install the package via Composer:

```bash
composer require lynkbyte/laravel-evolution-api
```

Run the installation command:

```bash
php artisan evolution-api:install
```

This will:
- Publish the configuration file
- Publish database migrations
- Run the migrations

Alternatively, you can publish assets manually:

```bash
# Publish configuration
php artisan vendor:publish --tag="evolution-api-config"

# Publish migrations
php artisan vendor:publish --tag="evolution-api-migrations"

# Run migrations
php artisan migrate
```

## Configuration

Add these environment variables to your `.env` file:

```env
EVOLUTION_API_BASE_URL=https://your-evolution-api-server.com
EVOLUTION_API_KEY=your-api-key
EVOLUTION_API_TIMEOUT=30
EVOLUTION_API_RETRY_ATTEMPTS=3
EVOLUTION_API_RATE_LIMIT=100
EVOLUTION_API_WEBHOOK_SECRET=your-webhook-secret
```

The configuration file will be published to `config/evolution-api.php`:

```php
return [
    'base_url' => env('EVOLUTION_API_BASE_URL'),
    'api_key' => env('EVOLUTION_API_KEY'),
    'timeout' => env('EVOLUTION_API_TIMEOUT', 30),
    'retry' => [
        'attempts' => env('EVOLUTION_API_RETRY_ATTEMPTS', 3),
        'delay' => env('EVOLUTION_API_RETRY_DELAY', 100),
    ],
    'rate_limit' => [
        'enabled' => env('EVOLUTION_API_RATE_LIMIT_ENABLED', true),
        'max_requests' => env('EVOLUTION_API_RATE_LIMIT', 100),
        'per_seconds' => env('EVOLUTION_API_RATE_LIMIT_WINDOW', 60),
    ],
    'webhook' => [
        'secret' => env('EVOLUTION_API_WEBHOOK_SECRET'),
        'verify_signature' => env('EVOLUTION_API_VERIFY_SIGNATURE', true),
    ],
    'queue' => [
        'enabled' => env('EVOLUTION_API_QUEUE_ENABLED', true),
        'connection' => env('EVOLUTION_API_QUEUE_CONNECTION', 'default'),
        'queue' => env('EVOLUTION_API_QUEUE_NAME', 'evolution-api'),
    ],
    'logging' => [
        'enabled' => env('EVOLUTION_API_LOGGING_ENABLED', true),
        'channel' => env('EVOLUTION_API_LOG_CHANNEL', 'stack'),
    ],
];
```

## Quick Start

### Using the Facade

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

// Send a text message
$response = EvolutionApi::message()->sendText('instance-name', [
    'number' => '5511999999999',
    'text' => 'Hello from Laravel!',
]);

// Check instance status
$status = EvolutionApi::instance()->status('instance-name');
```

### Using Dependency Injection

```php
use Lynkbyte\EvolutionApi\Services\EvolutionService;

class WhatsAppController extends Controller
{
    public function __construct(
        private EvolutionService $evolution
    ) {}

    public function sendMessage(Request $request)
    {
        $response = $this->evolution->message()->sendText('instance-name', [
            'number' => $request->phone,
            'text' => $request->message,
        ]);

        return response()->json($response);
    }
}
```

## Instance Management

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

// Create a new instance
$instance = EvolutionApi::instance()->create([
    'instanceName' => 'my-instance',
    'qrcode' => true,
    'integration' => 'WHATSAPP-BAILEYS',
]);

// List all instances
$instances = EvolutionApi::instance()->fetchAll();

// Get instance info
$info = EvolutionApi::instance()->fetch('my-instance');

// Get connection state
$state = EvolutionApi::instance()->status('my-instance');

// Connect instance (get QR code)
$qrCode = EvolutionApi::instance()->connect('my-instance');

// Disconnect instance
EvolutionApi::instance()->logout('my-instance');

// Delete instance
EvolutionApi::instance()->delete('my-instance');

// Restart instance
EvolutionApi::instance()->restart('my-instance');
```

## Sending Messages

### Text Messages

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;
use Lynkbyte\EvolutionApi\DTOs\Message\SendTextMessageDto;

// Simple way
EvolutionApi::message()->sendText('instance-name', [
    'number' => '5511999999999',
    'text' => 'Hello, World!',
]);

// With DTO
$dto = SendTextMessageDto::from([
    'number' => '5511999999999',
    'text' => 'Hello, World!',
    'delay' => 1000, // Optional delay in ms
]);

EvolutionApi::message()->sendText('instance-name', $dto);
```

### Media Messages

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendMediaMessageDto;

// Send image
EvolutionApi::message()->sendMedia('instance-name', [
    'number' => '5511999999999',
    'mediatype' => 'image',
    'media' => 'https://example.com/image.jpg',
    'caption' => 'Check this out!',
]);

// Send document
EvolutionApi::message()->sendMedia('instance-name', [
    'number' => '5511999999999',
    'mediatype' => 'document',
    'media' => 'https://example.com/document.pdf',
    'fileName' => 'report.pdf',
]);

// Send video
EvolutionApi::message()->sendMedia('instance-name', [
    'number' => '5511999999999',
    'mediatype' => 'video',
    'media' => 'https://example.com/video.mp4',
    'caption' => 'Watch this!',
]);
```

### Audio Messages

```php
// Send audio (voice message)
EvolutionApi::message()->sendAudio('instance-name', [
    'number' => '5511999999999',
    'audio' => 'https://example.com/audio.mp3',
]);

// Send as PTT (push-to-talk voice note)
EvolutionApi::message()->sendAudio('instance-name', [
    'number' => '5511999999999',
    'audio' => 'https://example.com/audio.ogg',
    'encoding' => true, // Convert to PTT format
]);
```

### Location Messages

```php
EvolutionApi::message()->sendLocation('instance-name', [
    'number' => '5511999999999',
    'latitude' => -23.5505,
    'longitude' => -46.6333,
    'name' => 'São Paulo',
    'address' => 'São Paulo, Brazil',
]);
```

### Contact Messages

```php
EvolutionApi::message()->sendContact('instance-name', [
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

### Poll Messages

```php
EvolutionApi::message()->sendPoll('instance-name', [
    'number' => '5511999999999',
    'name' => 'What is your favorite color?',
    'selectableCount' => 1,
    'values' => ['Red', 'Blue', 'Green', 'Yellow'],
]);
```

### List Messages

```php
EvolutionApi::message()->sendList('instance-name', [
    'number' => '5511999999999',
    'title' => 'Our Menu',
    'description' => 'Choose an option',
    'buttonText' => 'View Menu',
    'footerText' => 'Powered by Laravel',
    'sections' => [
        [
            'title' => 'Drinks',
            'rows' => [
                ['title' => 'Coffee', 'description' => '$3.00', 'rowId' => 'coffee'],
                ['title' => 'Tea', 'description' => '$2.50', 'rowId' => 'tea'],
            ],
        ],
    ],
]);
```

### Reaction Messages

```php
EvolutionApi::message()->sendReaction('instance-name', [
    'key' => [
        'remoteJid' => '5511999999999@s.whatsapp.net',
        'fromMe' => false,
        'id' => 'MESSAGE_ID',
    ],
    'reaction' => '👍',
]);
```

### Sticker Messages

```php
EvolutionApi::message()->sendSticker('instance-name', [
    'number' => '5511999999999',
    'sticker' => 'https://example.com/sticker.webp',
]);
```

### Status/Stories

```php
EvolutionApi::message()->sendStatus('instance-name', [
    'type' => 'text',
    'content' => 'Hello from my status!',
    'backgroundColor' => '#FF5733',
    'font' => 1,
    'statusJidList' => ['5511999999999@s.whatsapp.net'],
]);
```

## Queue Support

Send messages asynchronously using Laravel queues:

```php
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;
use Lynkbyte\EvolutionApi\DTOs\Message\SendTextMessageDto;

// Dispatch a message job
SendMessageJob::dispatch(
    'instance-name',
    'text',
    SendTextMessageDto::from([
        'number' => '5511999999999',
        'text' => 'This message is queued!',
    ])
);

// With delay
SendMessageJob::dispatch('instance-name', 'text', $dto)
    ->delay(now()->addMinutes(5));

// On specific queue
SendMessageJob::dispatch('instance-name', 'text', $dto)
    ->onQueue('whatsapp-messages');
```

## Chat Operations

```php
// Check if number exists on WhatsApp
$exists = EvolutionApi::chat()->checkNumber('instance-name', '5511999999999');

// Get all chats
$chats = EvolutionApi::chat()->fetchAll('instance-name');

// Find specific chat
$chat = EvolutionApi::chat()->find('instance-name', '5511999999999@s.whatsapp.net');

// Get messages from chat
$messages = EvolutionApi::chat()->fetchMessages('instance-name', [
    'where' => [
        'key' => ['remoteJid' => '5511999999999@s.whatsapp.net'],
    ],
    'limit' => 50,
]);

// Mark messages as read
EvolutionApi::chat()->readMessages('instance-name', [
    'readMessages' => [
        [
            'remoteJid' => '5511999999999@s.whatsapp.net',
            'fromMe' => false,
            'id' => 'MESSAGE_ID',
        ],
    ],
]);

// Archive/Unarchive chat
EvolutionApi::chat()->archive('instance-name', [
    'chat' => '5511999999999@s.whatsapp.net',
    'archive' => true,
]);

// Mark chat as unread
EvolutionApi::chat()->markUnread('instance-name', [
    'chat' => '5511999999999@s.whatsapp.net',
]);

// Delete message
EvolutionApi::chat()->deleteMessage('instance-name', [
    'remoteJid' => '5511999999999@s.whatsapp.net',
    'messageId' => 'MESSAGE_ID',
    'fromMe' => true,
]);

// Update presence (typing indicator)
EvolutionApi::chat()->updatePresence('instance-name', [
    'number' => '5511999999999',
    'presence' => 'composing', // composing, recording, paused
]);
```

## Group Management

```php
// Create a group
$group = EvolutionApi::group()->create('instance-name', [
    'subject' => 'My Group',
    'participants' => ['5511999999999', '5511888888888'],
    'description' => 'Welcome to our group!',
]);

// Fetch all groups
$groups = EvolutionApi::group()->fetchAll('instance-name', true); // true = get participants

// Get group info
$info = EvolutionApi::group()->find('instance-name', '123456789@g.us');

// Get invite code
$code = EvolutionApi::group()->inviteCode('instance-name', '123456789@g.us');

// Revoke invite code
EvolutionApi::group()->revokeInviteCode('instance-name', '123456789@g.us');

// Add participants
EvolutionApi::group()->updateParticipant('instance-name', [
    'groupJid' => '123456789@g.us',
    'action' => 'add',
    'participants' => ['5511777777777'],
]);

// Remove participants
EvolutionApi::group()->updateParticipant('instance-name', [
    'groupJid' => '123456789@g.us',
    'action' => 'remove',
    'participants' => ['5511777777777'],
]);

// Promote to admin
EvolutionApi::group()->updateParticipant('instance-name', [
    'groupJid' => '123456789@g.us',
    'action' => 'promote',
    'participants' => ['5511777777777'],
]);

// Demote from admin
EvolutionApi::group()->updateParticipant('instance-name', [
    'groupJid' => '123456789@g.us',
    'action' => 'demote',
    'participants' => ['5511777777777'],
]);

// Update group subject
EvolutionApi::group()->updateSubject('instance-name', [
    'groupJid' => '123456789@g.us',
    'subject' => 'New Group Name',
]);

// Update group description
EvolutionApi::group()->updateDescription('instance-name', [
    'groupJid' => '123456789@g.us',
    'description' => 'New description',
]);

// Update group picture
EvolutionApi::group()->updatePicture('instance-name', [
    'groupJid' => '123456789@g.us',
    'image' => 'https://example.com/group-picture.jpg',
]);

// Update group settings
EvolutionApi::group()->updateSetting('instance-name', [
    'groupJid' => '123456789@g.us',
    'action' => 'announcement', // announcement, not_announcement, locked, unlocked
]);

// Leave group
EvolutionApi::group()->leave('instance-name', '123456789@g.us');
```

## Profile Management

```php
// Get profile info
$profile = EvolutionApi::profile()->fetch('instance-name');

// Get business profile
$business = EvolutionApi::profile()->fetchBusiness('instance-name', '5511999999999');

// Update profile name
EvolutionApi::profile()->updateName('instance-name', 'My Business Name');

// Update status
EvolutionApi::profile()->updateStatus('instance-name', 'Available 24/7');

// Update profile picture
EvolutionApi::profile()->updatePicture('instance-name', 'https://example.com/avatar.jpg');

// Remove profile picture
EvolutionApi::profile()->removePicture('instance-name');

// Get profile picture URL
$pictureUrl = EvolutionApi::profile()->fetchPicture('instance-name', '5511999999999');

// Update privacy settings
EvolutionApi::profile()->updatePrivacy('instance-name', [
    'readreceipts' => 'all',
    'profile' => 'contacts',
    'status' => 'contacts',
    'online' => 'all',
    'last' => 'contacts',
    'groupadd' => 'contacts',
]);

// Fetch privacy settings
$privacy = EvolutionApi::profile()->fetchPrivacy('instance-name');
```

## Webhook Configuration

```php
// Set webhook URL
EvolutionApi::webhook()->set('instance-name', [
    'url' => 'https://your-app.com/webhooks/evolution',
    'webhook_by_events' => false,
    'events' => [
        'MESSAGES_UPSERT',
        'MESSAGES_UPDATE',
        'CONNECTION_UPDATE',
        'QRCODE_UPDATED',
    ],
]);

// Get webhook configuration
$config = EvolutionApi::webhook()->find('instance-name');
```

## Handling Webhooks

The package includes a built-in webhook controller. Register the route in your `routes/api.php`:

```php
// The package auto-registers routes, but you can customize:
Route::post('/webhooks/evolution/{instance}', [
    \Lynkbyte\EvolutionApi\Http\Controllers\WebhookController::class,
    'handle'
])->name('evolution.webhook');
```

### Listening to Webhook Events

```php
use Lynkbyte\EvolutionApi\Events\MessageReceived;
use Lynkbyte\EvolutionApi\Events\MessageSent;
use Lynkbyte\EvolutionApi\Events\QrCodeReceived;
use Lynkbyte\EvolutionApi\Events\ConnectionUpdated;
use Lynkbyte\EvolutionApi\Events\InstanceStatusChanged;

// In your EventServiceProvider
protected $listen = [
    MessageReceived::class => [
        HandleIncomingMessage::class,
    ],
    MessageSent::class => [
        LogOutgoingMessage::class,
    ],
    QrCodeReceived::class => [
        NotifyQrCodeAvailable::class,
    ],
    ConnectionUpdated::class => [
        HandleConnectionChange::class,
    ],
];

// Or in a listener
class HandleIncomingMessage
{
    public function handle(MessageReceived $event): void
    {
        $instance = $event->instance;
        $payload = $event->payload;
        
        // Process the incoming message
        Log::info('Message received', [
            'instance' => $instance,
            'from' => $payload->data['key']['remoteJid'] ?? null,
            'message' => $payload->data['message'] ?? null,
        ]);
    }
}
```

## Settings Management

```php
// Get all settings
$settings = EvolutionApi::settings()->find('instance-name');

// Update settings
EvolutionApi::settings()->set('instance-name', [
    'rejectCall' => true,
    'msgCall' => 'Sorry, I cannot take calls right now.',
    'groupsIgnore' => false,
    'alwaysOnline' => true,
    'readMessages' => false,
    'readStatus' => false,
    'syncFullHistory' => false,
]);
```

## Eloquent Models

The package provides Eloquent models for data persistence:

```php
use Lynkbyte\EvolutionApi\Models\EvolutionInstance;
use Lynkbyte\EvolutionApi\Models\EvolutionMessage;
use Lynkbyte\EvolutionApi\Models\EvolutionContact;
use Lynkbyte\EvolutionApi\Models\EvolutionWebhookLog;

// Find instance
$instance = EvolutionInstance::where('name', 'my-instance')->first();

// Get instance messages
$messages = $instance->messages()->latest()->paginate(20);

// Get instance contacts
$contacts = $instance->contacts;

// Query messages
$messages = EvolutionMessage::query()
    ->where('status', 'delivered')
    ->whereDate('created_at', today())
    ->get();

// Get webhook logs
$logs = EvolutionWebhookLog::query()
    ->where('event', 'MESSAGES_UPSERT')
    ->latest()
    ->limit(100)
    ->get();
```

## Artisan Commands

```bash
# Run health check on all instances
php artisan evolution-api:health-check

# Check specific instance
php artisan evolution-api:health-check --instance=my-instance

# Get instance status
php artisan evolution-api:instance-status

# Get status for specific instance
php artisan evolution-api:instance-status my-instance

# Retry failed messages
php artisan evolution-api:retry-failed

# Prune old data
php artisan evolution-api:prune --days=30
```

## Metrics & Monitoring

```php
use Lynkbyte\EvolutionApi\Metrics\MetricsCollector;

$metrics = app(MetricsCollector::class);

// Get all metrics
$allMetrics = $metrics->all();

// Get specific metrics
$messagesSent = $metrics->get('messages_sent');
$apiCalls = $metrics->get('api_calls');
$errorCount = $metrics->get('errors');

// Reset metrics
$metrics->reset();
```

## Testing

The package provides a fake implementation for testing:

```php
use Lynkbyte\EvolutionApi\Testing\Fakes\EvolutionApiFake;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

public function test_sends_whatsapp_message(): void
{
    // Replace with fake
    $fake = EvolutionApi::fake();
    
    // Your application code
    $this->post('/send-message', [
        'phone' => '5511999999999',
        'message' => 'Hello!',
    ]);
    
    // Assert message was sent
    $fake->assertSent('text', function ($instance, $data) {
        return $data['number'] === '5511999999999'
            && $data['text'] === 'Hello!';
    });
    
    // Assert message count
    $fake->assertSentCount('text', 1);
}
```

## Error Handling

The package throws specific exceptions for different error types:

```php
use Lynkbyte\EvolutionApi\Exceptions\AuthenticationException;
use Lynkbyte\EvolutionApi\Exceptions\ConnectionException;
use Lynkbyte\EvolutionApi\Exceptions\InstanceNotFoundException;
use Lynkbyte\EvolutionApi\Exceptions\MessageException;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;
use Lynkbyte\EvolutionApi\Exceptions\ValidationException;
use Lynkbyte\EvolutionApi\Exceptions\WebhookException;

try {
    EvolutionApi::message()->sendText('instance', $data);
} catch (AuthenticationException $e) {
    // Invalid API key
} catch (InstanceNotFoundException $e) {
    // Instance doesn't exist
} catch (RateLimitException $e) {
    // Too many requests - retry after $e->retryAfter seconds
} catch (ConnectionException $e) {
    // Network error
} catch (MessageException $e) {
    // Message sending failed
} catch (ValidationException $e) {
    // Invalid data
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email hello@lynkbyte.com instead of using the issue tracker.

## Credits

- [Lynkbyte](https://github.com/lynkbyte)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
