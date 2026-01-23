# SendMessageJob

The `SendMessageJob` handles queued message sending with automatic retries and event dispatching.

## Overview

```php
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;

// Queue a message
SendMessageJob::text('my-instance', '5511999999999', 'Hello!')
    ->dispatch();
```

## Class Reference

```php
namespace Lynkbyte\EvolutionApi\Jobs;

class SendMessageJob implements ShouldQueue
{
    public readonly string $instanceName;
    public readonly string $messageType;
    public readonly array $message;
    public readonly ?string $connectionName;
    
    public int $tries;
    public array $backoff;
    public int $maxExceptions;
}
```

## Static Constructors

### Text Messages

```php
SendMessageJob::text(
    instanceName: 'my-instance',
    number: '5511999999999',
    text: 'Hello, World!',
    options: [
        'delay' => 1000,
        'linkPreview' => true,
    ],
    connectionName: null
);
```

### Media Messages

```php
SendMessageJob::media(
    instanceName: 'my-instance',
    number: '5511999999999',
    mediatype: 'image',  // image, video, document
    media: 'https://example.com/image.jpg',
    options: [
        'caption' => 'Check this out!',
        'filename' => 'photo.jpg',
    ],
    connectionName: null
);
```

## Creating Jobs Directly

For more control, create jobs directly:

```php
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;

// Text message
$job = new SendMessageJob(
    instanceName: 'my-instance',
    messageType: 'text',
    message: [
        'number' => '5511999999999',
        'text' => 'Hello!',
    ],
    connectionName: 'production'
);

// Media message
$job = new SendMessageJob(
    instanceName: 'my-instance',
    messageType: 'media',
    message: [
        'number' => '5511999999999',
        'mediatype' => 'image',
        'media' => 'https://example.com/image.jpg',
        'caption' => 'Amazing photo!',
    ]
);

// Audio message
$job = new SendMessageJob(
    instanceName: 'my-instance',
    messageType: 'audio',
    message: [
        'number' => '5511999999999',
        'audio' => 'https://example.com/audio.mp3',
    ]
);

// Location message
$job = new SendMessageJob(
    instanceName: 'my-instance',
    messageType: 'location',
    message: [
        'number' => '5511999999999',
        'latitude' => -23.550520,
        'longitude' => -46.633308,
        'name' => 'São Paulo',
        'address' => 'São Paulo, Brazil',
    ]
);
```

## Dispatching Jobs

### Basic Dispatch

```php
// Dispatch to default queue
SendMessageJob::text('my-instance', '5511999999999', 'Hello!')
    ->dispatch();
```

### Delayed Dispatch

```php
// Send after 5 minutes
SendMessageJob::text('my-instance', '5511999999999', 'Reminder!')
    ->delay(now()->addMinutes(5))
    ->dispatch();
```

### Custom Queue

```php
// Send to specific queue
SendMessageJob::text('my-instance', '5511999999999', 'Priority!')
    ->onQueue('high-priority')
    ->dispatch();
```

### Chain Jobs

```php
use Illuminate\Support\Facades\Bus;

// Send multiple messages in sequence
Bus::chain([
    SendMessageJob::text('my-instance', '5511999999999', 'Message 1'),
    SendMessageJob::text('my-instance', '5511999999999', 'Message 2'),
    SendMessageJob::text('my-instance', '5511999999999', 'Message 3'),
])->dispatch();
```

### Batch Processing

```php
use Illuminate\Support\Facades\Bus;

// Send to multiple recipients
$jobs = collect($recipients)->map(function ($number) {
    return SendMessageJob::text('my-instance', $number, 'Broadcast message!');
});

Bus::batch($jobs)
    ->name('broadcast-campaign')
    ->allowFailures()
    ->dispatch();
```

## Message Types

### text

Text message with optional link preview:

```php
$job = new SendMessageJob(
    instanceName: 'my-instance',
    messageType: 'text',
    message: [
        'number' => '5511999999999',
        'text' => 'Check out https://example.com',
        'delay' => 1000,
        'linkPreview' => true,
        'mentionsEveryOne' => false,
    ]
);
```

### media

Image, video, or document:

```php
$job = new SendMessageJob(
    instanceName: 'my-instance',
    messageType: 'media',
    message: [
        'number' => '5511999999999',
        'mediatype' => 'image',  // image, video, document
        'media' => 'https://example.com/file.jpg',
        'mimetype' => 'image/jpeg',
        'caption' => 'Optional caption',
        'filename' => 'photo.jpg',
    ]
);
```

### audio

Audio file or voice recording:

```php
$job = new SendMessageJob(
    instanceName: 'my-instance',
    messageType: 'audio',
    message: [
        'number' => '5511999999999',
        'audio' => 'https://example.com/audio.mp3',
    ]
);
```

### location

Location with coordinates:

```php
$job = new SendMessageJob(
    instanceName: 'my-instance',
    messageType: 'location',
    message: [
        'number' => '5511999999999',
        'latitude' => -23.550520,
        'longitude' => -46.633308,
        'name' => 'Location Name',
        'address' => 'Full address',
    ]
);
```

## Job Configuration

### Retry Behavior

The job automatically configures retries from config:

```php
// From config/evolution-api.php
'queue' => [
    'max_exceptions' => 3,
    'backoff' => [60, 300, 900], // 1min, 5min, 15min
],
```

Override per-job:

```php
$job = SendMessageJob::text('my-instance', '5511999999999', 'Hello!');
$job->tries = 5;
$job->backoff = [30, 60, 120];
```

### Custom Configuration

```php
$job = SendMessageJob::text('my-instance', '5511999999999', 'Hello!')
    ->onQueue('priority-messages')
    ->onConnection('redis')
    ->delay(now()->addSeconds(30));

dispatch($job);
```

## Events

The job dispatches events:

### MessageSent

On successful send:

```php
use Lynkbyte\EvolutionApi\Events\MessageSent;

Event::listen(MessageSent::class, function ($event) {
    $event->instanceName;  // 'my-instance'
    $event->messageType;   // 'text'
    $event->message;       // ['number' => '...', 'text' => '...']
    $event->response;      // API response data
});
```

### MessageFailed

On failure (including after retries):

```php
use Lynkbyte\EvolutionApi\Events\MessageFailed;

Event::listen(MessageFailed::class, function ($event) {
    $event->instanceName;  // 'my-instance'
    $event->messageType;   // 'text'
    $event->message;       // ['number' => '...', 'text' => '...']
    $event->exception;     // The exception that occurred
    
    // Log or notify
    Log::error("Message to {$event->message['number']} failed", [
        'error' => $event->exception->getMessage(),
    ]);
});
```

## Error Handling

### In the Job

The job handles errors internally:

```php
public function handle(): void
{
    try {
        $response = match ($this->messageType) {
            'text' => $service->messages()->sendText(...),
            // ...
        };

        if ($response->isSuccessful()) {
            event(new MessageSent(...));
        } else {
            $this->handleFailure(new Exception($response->message));
        }
    } catch (\Throwable $e) {
        $this->handleFailure($e);
        throw $e; // Re-throw for queue retry
    }
}
```

### Custom Failure Handling

Override the `failed` method:

```php
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;

class CustomSendMessageJob extends SendMessageJob
{
    public function failed(\Throwable $exception): void
    {
        parent::failed($exception);
        
        // Custom logic
        DB::table('failed_messages')->insert([
            'instance' => $this->instanceName,
            'number' => $this->message['number'] ?? null,
            'type' => $this->messageType,
            'error' => $exception->getMessage(),
            'failed_at' => now(),
        ]);
    }
}
```

## Job Tags

Jobs are tagged for Horizon filtering:

```php
public function tags(): array
{
    return [
        'evolution-api',
        'message',
        "instance:{$this->instanceName}",
        "type:{$this->messageType}",
    ];
}
```

View in Horizon:
- Filter by `evolution-api` - All Evolution API jobs
- Filter by `instance:my-instance` - Jobs for specific instance
- Filter by `type:text` - Text message jobs only

## Multi-Connection Support

Send via different Evolution API connections:

```php
// Using secondary connection
SendMessageJob::text(
    instanceName: 'my-instance',
    number: '5511999999999',
    text: 'Hello!',
    connectionName: 'secondary'
)->dispatch();
```

## Example: Broadcast Service

```php
namespace App\Services;

use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;
use Illuminate\Support\Facades\Bus;

class BroadcastService
{
    public function sendCampaign(
        string $instanceName,
        array $recipients,
        string $message,
        ?string $mediaUrl = null
    ): void {
        $jobs = collect($recipients)->map(function ($number) use ($instanceName, $message, $mediaUrl) {
            if ($mediaUrl) {
                return SendMessageJob::media(
                    instanceName: $instanceName,
                    number: $number,
                    mediatype: 'image',
                    media: $mediaUrl,
                    options: ['caption' => $message]
                );
            }
            
            return SendMessageJob::text(
                instanceName: $instanceName,
                number: $number,
                text: $message
            );
        });

        Bus::batch($jobs)
            ->name("campaign-{$instanceName}")
            ->allowFailures()
            ->onQueue('broadcasts')
            ->dispatch();
    }
}
```

## Example: Scheduled Messages

```php
namespace App\Console\Commands;

use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;

class SendScheduledMessages extends Command
{
    public function handle(): void
    {
        $messages = ScheduledMessage::where('send_at', '<=', now())
            ->where('status', 'pending')
            ->get();

        foreach ($messages as $message) {
            SendMessageJob::text(
                instanceName: $message->instance,
                number: $message->number,
                text: $message->content
            )->dispatch();
            
            $message->update(['status' => 'queued']);
        }
    }
}
```
