# Rate Limiting

The package includes built-in rate limiting to prevent overwhelming Evolution API and comply with WhatsApp messaging limits.

## Overview

Rate limiting helps:

- **Prevent API Abuse** - Avoid hitting Evolution API too hard
- **WhatsApp Compliance** - Stay within WhatsApp's messaging limits
- **Resource Management** - Control resource usage
- **Error Prevention** - Avoid 429 errors from the API

## Configuration

```php
// config/evolution-api.php

'rate_limiting' => [
    // Enable/disable rate limiting
    'enabled' => env('EVOLUTION_RATE_LIMIT_ENABLED', true),
    
    // Storage driver: cache, redis, array
    'driver' => env('EVOLUTION_RATE_LIMIT_DRIVER', 'cache'),
    
    // Rate limits by type
    'limits' => [
        'default' => [
            'max_attempts' => 60,
            'decay_seconds' => 60,
        ],
        'messages' => [
            'max_attempts' => 30,
            'decay_seconds' => 60,
        ],
        'media' => [
            'max_attempts' => 10,
            'decay_seconds' => 60,
        ],
    ],
    
    // What to do when limit is reached
    'on_limit_reached' => 'wait', // wait, throw, skip
],
```

## Limit Types

### Default Limit

General API calls (instances, settings, etc.):

```php
'default' => [
    'max_attempts' => 60,   // 60 requests
    'decay_seconds' => 60,  // per minute
],
```

### Message Limit

Text message sending:

```php
'messages' => [
    'max_attempts' => 30,   // 30 messages
    'decay_seconds' => 60,  // per minute
],
```

### Media Limit

Media uploads (images, videos, documents):

```php
'media' => [
    'max_attempts' => 10,   // 10 uploads
    'decay_seconds' => 60,  // per minute
],
```

## Behaviors

### Wait Mode (Default)

When limit is reached, wait and retry:

```php
'on_limit_reached' => 'wait',
```

The request will be delayed until the rate limit resets.

### Throw Mode

Throw an exception when limit is reached:

```php
'on_limit_reached' => 'throw',
```

```php
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;

try {
    $response = EvolutionApi::messages()->sendText('5511999999999', 'Hello!');
} catch (RateLimitException $e) {
    $retryAfter = $e->getRetryAfter(); // seconds until reset
    $limitType = $e->getLimitType();   // 'messages', 'media', etc.
    
    // Handle rate limit
    Log::warning("Rate limit hit: {$limitType}, retry after {$retryAfter}s");
}
```

### Skip Mode

Skip the request silently when limit is reached:

```php
'on_limit_reached' => 'skip',
```

Useful for non-critical operations.

## RateLimitException

When using 'throw' mode, catch the exception:

```php
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;

try {
    $response = EvolutionApi::for('my-instance')
        ->messages()
        ->sendText('5511999999999', 'Hello!');
} catch (RateLimitException $e) {
    // Exception details
    $e->getMessage();      // "Message rate limit exceeded. Retry after 60 seconds."
    $e->getRetryAfter();   // 60
    $e->getLimitType();    // "messages"
    $e->getInstanceName(); // "my-instance"
    
    // Queue for later
    SendMessageJob::text('my-instance', '5511999999999', 'Hello!')
        ->delay(now()->addSeconds($e->getRetryAfter()))
        ->dispatch();
}
```

## Per-Instance Rate Limiting

Rate limits can be applied per instance:

```php
// Each instance has its own rate limit counter
EvolutionApi::for('instance-1')->messages()->sendText(...); // Uses instance-1 limit
EvolutionApi::for('instance-2')->messages()->sendText(...); // Uses instance-2 limit
```

## Custom Rate Limiter

### Creating a Custom Limiter

```php
namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;

class CustomRateLimiter
{
    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $executed = RateLimiter::attempt(
            $key,
            $maxAttempts,
            fn() => true,
            $decaySeconds
        );
        
        if (!$executed) {
            $seconds = RateLimiter::availableIn($key);
            throw new RateLimitException(
                message: "Rate limit exceeded",
                retryAfter: $seconds,
                limitType: $key
            );
        }
        
        return true;
    }
    
    public function remaining(string $key, int $maxAttempts): int
    {
        return RateLimiter::remaining($key, $maxAttempts);
    }
    
    public function clear(string $key): void
    {
        RateLimiter::clear($key);
    }
}
```

### Using Custom Limiter

```php
$limiter = new CustomRateLimiter();

// Before sending message
$limiter->attempt("messages:{$instanceName}", 30, 60);

// Send message
$response = EvolutionApi::for($instanceName)
    ->messages()
    ->sendText($number, $text);
```

## Handling Rate Limits in Jobs

### With Automatic Retry

```php
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;

class CustomSendMessageJob extends SendMessageJob
{
    public function handle(): void
    {
        try {
            parent::handle();
        } catch (RateLimitException $e) {
            // Release job back to queue with delay
            $this->release($e->getRetryAfter());
        }
    }
}
```

### Bulk Processing with Rate Limiting

```php
use Illuminate\Support\Facades\RateLimiter;

class BroadcastService
{
    public function sendBulk(string $instance, array $recipients, string $message): void
    {
        foreach ($recipients as $number) {
            // Check rate limit before dispatching
            $key = "broadcast:{$instance}";
            
            if (RateLimiter::tooManyAttempts($key, 30)) {
                $delay = RateLimiter::availableIn($key);
                
                SendMessageJob::text($instance, $number, $message)
                    ->delay(now()->addSeconds($delay))
                    ->dispatch();
            } else {
                RateLimiter::hit($key, 60);
                SendMessageJob::text($instance, $number, $message)->dispatch();
            }
        }
    }
}
```

## Monitoring Rate Limits

### Check Current Usage

```php
use Illuminate\Support\Facades\RateLimiter;

$key = "evolution:messages:my-instance";
$maxAttempts = 30;

$remaining = RateLimiter::remaining($key, $maxAttempts);
$resetIn = RateLimiter::availableIn($key);

Log::info("Rate limit status", [
    'remaining' => $remaining,
    'reset_in' => $resetIn,
]);
```

### Rate Limit Headers

Include rate limit info in API responses:

```php
// In your controller
public function sendMessage(Request $request)
{
    $key = "api:messages:{$request->user()->id}";
    $maxAttempts = 100;
    
    return response()->json(['status' => 'sent'])
        ->header('X-RateLimit-Limit', $maxAttempts)
        ->header('X-RateLimit-Remaining', RateLimiter::remaining($key, $maxAttempts))
        ->header('X-RateLimit-Reset', RateLimiter::availableIn($key));
}
```

## WhatsApp Rate Limits

WhatsApp has its own limits. Configure accordingly:

### Business API Limits (Approximate)

| Tier | Messages/day | Recommendation |
|------|--------------|----------------|
| Tier 1 | 1,000 | 1/second |
| Tier 2 | 10,000 | 5/second |
| Tier 3 | 100,000 | 30/second |
| Tier 4 | Unlimited | 80/second |

### Configuration for Tier 1

```php
'rate_limiting' => [
    'limits' => [
        'messages' => [
            'max_attempts' => 1,     // 1 message
            'decay_seconds' => 1,    // per second
        ],
    ],
],
```

### Configuration for Tier 3

```php
'rate_limiting' => [
    'limits' => [
        'messages' => [
            'max_attempts' => 30,    // 30 messages
            'decay_seconds' => 1,    // per second
        ],
    ],
],
```

## Best Practices

### 1. Use Redis for Production

```php
'rate_limiting' => [
    'driver' => 'redis',
],
```

Redis provides atomic operations and distributed rate limiting.

### 2. Separate Limits by Instance

```php
// Each instance gets its own limit
$key = "evolution:messages:{$instanceName}";
```

### 3. Implement Backoff in Jobs

```php
public array $backoff = [10, 30, 60, 120, 300];
```

Exponential backoff prevents thundering herd.

### 4. Monitor and Alert

```php
if ($remaining < $maxAttempts * 0.1) {
    Log::warning("Rate limit nearly exhausted", [
        'key' => $key,
        'remaining' => $remaining,
    ]);
}
```

### 5. Use Queue Throttling

```php
// config/horizon.php
'environments' => [
    'production' => [
        'message-supervisor' => [
            'queue' => ['evolution-messages'],
            'balance' => 'auto',
            'processes' => 3,  // Limit concurrent workers
        ],
    ],
],
```

## Disabling Rate Limiting

For testing or specific scenarios:

```php
// Globally
'rate_limiting' => [
    'enabled' => false,
],

// Or per-request (not recommended for production)
config(['evolution-api.rate_limiting.enabled' => false]);
```
