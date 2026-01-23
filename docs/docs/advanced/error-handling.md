# Error Handling

Comprehensive guide to handling errors in the Laravel Evolution API package.

## Exception Hierarchy

```
EvolutionApiException (Base)
├── AuthenticationException
├── ConnectionException
├── InstanceNotFoundException
├── MessageException
├── RateLimitException
├── ValidationException
└── WebhookException
```

## Base Exception

All package exceptions extend `EvolutionApiException`:

```php
use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;

try {
    $response = EvolutionApi::instances()->create([...]);
} catch (EvolutionApiException $e) {
    $e->getMessage();       // Error message
    $e->getCode();          // Error code
    $e->getStatusCode();    // HTTP status code
    $e->getInstanceName();  // Instance name (if applicable)
    $e->getResponseData();  // Raw API response
    $e->context();          // Full context for logging
}
```

## Exception Types

### AuthenticationException

Thrown when API authentication fails:

```php
use Lynkbyte\EvolutionApi\Exceptions\AuthenticationException;

try {
    $response = EvolutionApi::instances()->list();
} catch (AuthenticationException $e) {
    // Invalid or missing API key
    Log::error('Evolution API authentication failed', $e->context());
}
```

**Common Causes:**
- Invalid API key
- Expired API key
- Missing API key in configuration

### ConnectionException

Thrown when unable to connect to Evolution API:

```php
use Lynkbyte\EvolutionApi\Exceptions\ConnectionException;

try {
    $response = EvolutionApi::instances()->list();
} catch (ConnectionException $e) {
    // Server unreachable
    Log::error('Evolution API server unreachable', [
        'url' => config('evolution-api.server_url'),
        'error' => $e->getMessage(),
    ]);
}
```

**Common Causes:**
- Server is down
- Network issues
- Incorrect server URL
- SSL certificate issues

### InstanceNotFoundException

Thrown when specified instance doesn't exist:

```php
use Lynkbyte\EvolutionApi\Exceptions\InstanceNotFoundException;

try {
    $status = EvolutionApi::for('non-existent')
        ->instances()
        ->getStatus();
} catch (InstanceNotFoundException $e) {
    Log::warning("Instance not found: {$e->getInstanceName()}");
}
```

### MessageException

Thrown when message sending fails:

```php
use Lynkbyte\EvolutionApi\Exceptions\MessageException;

try {
    $response = EvolutionApi::for('my-instance')
        ->messages()
        ->sendText('invalid-number', 'Hello!');
} catch (MessageException $e) {
    Log::error('Message failed', [
        'instance' => $e->getInstanceName(),
        'error' => $e->getMessage(),
        'response' => $e->getResponseData(),
    ]);
}
```

**Common Causes:**
- Invalid phone number
- Instance not connected
- Media file too large
- Recipient blocked

### RateLimitException

Thrown when rate limit is exceeded:

```php
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;

try {
    $response = EvolutionApi::messages()->sendText(...);
} catch (RateLimitException $e) {
    $retryAfter = $e->getRetryAfter();  // Seconds until reset
    $limitType = $e->getLimitType();     // 'messages', 'media', etc.
    
    // Retry later
    dispatch(new SendMessageJob(...))->delay(now()->addSeconds($retryAfter));
}
```

### ValidationException

Thrown when request validation fails:

```php
use Lynkbyte\EvolutionApi\Exceptions\ValidationException;

try {
    $response = EvolutionApi::instances()->create([
        'instanceName' => '', // Invalid: empty name
    ]);
} catch (ValidationException $e) {
    // Handle validation error
    $errors = $e->getResponseData()['errors'] ?? [];
}
```

### WebhookException

Thrown when webhook processing fails:

```php
use Lynkbyte\EvolutionApi\Exceptions\WebhookException;

try {
    $processor->process($payload);
} catch (WebhookException $e) {
    Log::error('Webhook processing failed', [
        'event' => $e->context()['event'] ?? 'unknown',
        'error' => $e->getMessage(),
    ]);
}
```

## Handling in Controllers

### API Controller

```php
namespace App\Http\Controllers;

use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;
use Lynkbyte\EvolutionApi\Exceptions\InstanceNotFoundException;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

class WhatsAppController extends Controller
{
    public function send(Request $request)
    {
        try {
            $response = EvolutionApi::for($request->instance)
                ->messages()
                ->sendText($request->number, $request->message);
                
            return response()->json([
                'success' => true,
                'message_id' => $response->getData()['key']['id'] ?? null,
            ]);
            
        } catch (RateLimitException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded',
                'retry_after' => $e->getRetryAfter(),
            ], 429);
            
        } catch (InstanceNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Instance not found',
            ], 404);
            
        } catch (EvolutionApiException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

### Global Exception Handler

```php
// app/Exceptions/Handler.php
namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;
use Lynkbyte\EvolutionApi\Exceptions\AuthenticationException;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (RateLimitException $e) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => $e->getRetryAfter(),
            ], 429);
        });
        
        $this->renderable(function (AuthenticationException $e) {
            return response()->json([
                'error' => 'WhatsApp API authentication failed',
            ], 401);
        });
        
        $this->renderable(function (EvolutionApiException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'code' => $e->getStatusCode(),
            ], $e->getStatusCode() ?? 500);
        });
        
        // Log all Evolution API exceptions
        $this->reportable(function (EvolutionApiException $e) {
            Log::channel('evolution')->error($e->getMessage(), $e->context());
        });
    }
}
```

## Handling in Jobs

### With Retry Logic

```php
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;
use Lynkbyte\EvolutionApi\Exceptions\ConnectionException;

class CustomSendMessageJob extends SendMessageJob
{
    public function handle(): void
    {
        try {
            parent::handle();
        } catch (RateLimitException $e) {
            // Release with delay based on rate limit
            $this->release($e->getRetryAfter());
        } catch (ConnectionException $e) {
            // Connection issues - retry with exponential backoff
            $this->release($this->attempts() * 60);
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        // Store failed message for manual retry
        DB::table('failed_messages')->insert([
            'instance' => $this->instanceName,
            'message' => json_encode($this->message),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'failed_at' => now(),
        ]);
        
        // Notify team
        if ($exception instanceof AuthenticationException) {
            Notification::route('slack', config('services.slack.ops'))
                ->notify(new CriticalError("Evolution API auth failed for {$this->instanceName}"));
        }
    }
}
```

### Conditional Retry

```php
public function handle(): void
{
    try {
        parent::handle();
    } catch (EvolutionApiException $e) {
        if ($this->shouldRetry($e)) {
            throw $e; // Will be retried by queue
        }
        
        // Don't retry - log and fail
        $this->fail($e);
    }
}

private function shouldRetry(EvolutionApiException $e): bool
{
    // Retry server errors and rate limits
    $retryableCodes = [408, 429, 500, 502, 503, 504];
    
    return in_array($e->getStatusCode(), $retryableCodes);
}
```

## Logging Errors

### Dedicated Log Channel

```php
// config/logging.php
'channels' => [
    'evolution' => [
        'driver' => 'daily',
        'path' => storage_path('logs/evolution.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

### Package Configuration

```php
// config/evolution-api.php
'logging' => [
    'enabled' => true,
    'channel' => 'evolution',
    'level' => 'info',
    'log_requests' => true,
    'log_responses' => true,
    'redact_sensitive' => true,
],
```

### Custom Error Logger

```php
namespace App\Services;

use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;
use Illuminate\Support\Facades\Log;

class EvolutionErrorLogger
{
    public function log(EvolutionApiException $e, array $extra = []): void
    {
        $context = array_merge($e->context(), $extra, [
            'trace' => $e->getTraceAsString(),
        ]);
        
        $level = $this->getLogLevel($e);
        
        Log::channel('evolution')->{$level}($e->getMessage(), $context);
    }
    
    private function getLogLevel(EvolutionApiException $e): string
    {
        return match (true) {
            $e instanceof AuthenticationException => 'critical',
            $e instanceof ConnectionException => 'error',
            $e instanceof RateLimitException => 'warning',
            default => 'error',
        };
    }
}
```

## Notifications

### Slack Notifications for Critical Errors

```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;
use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;

class EvolutionApiError extends Notification
{
    public function __construct(
        protected EvolutionApiException $exception,
        protected ?string $instance = null
    ) {}

    public function via($notifiable): array
    {
        return ['slack'];
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->error()
            ->content('Evolution API Error')
            ->attachment(function ($attachment) {
                $attachment
                    ->title(class_basename($this->exception))
                    ->content($this->exception->getMessage())
                    ->fields([
                        'Instance' => $this->instance ?? 'N/A',
                        'Status Code' => $this->exception->getStatusCode() ?? 'N/A',
                        'Time' => now()->toDateTimeString(),
                    ]);
            });
    }
}
```

### Send Notification

```php
use App\Notifications\EvolutionApiError;

try {
    // API call
} catch (EvolutionApiException $e) {
    Notification::route('slack', config('services.slack.webhook'))
        ->notify(new EvolutionApiError($e, 'my-instance'));
}
```

## Best Practices

### 1. Always Catch Specific Exceptions First

```php
try {
    $response = EvolutionApi::messages()->sendText(...);
} catch (RateLimitException $e) {
    // Handle rate limit specifically
} catch (InstanceNotFoundException $e) {
    // Handle missing instance
} catch (EvolutionApiException $e) {
    // Handle other API errors
} catch (\Exception $e) {
    // Handle unexpected errors
}
```

### 2. Use Context for Debugging

```php
catch (EvolutionApiException $e) {
    Log::error('Evolution API error', [
        ...$e->context(),
        'user_id' => auth()->id(),
        'request_id' => request()->header('X-Request-ID'),
    ]);
}
```

### 3. Implement Circuit Breaker

```php
use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    public function call(string $key, callable $callback)
    {
        if ($this->isOpen($key)) {
            throw new ConnectionException("Circuit is open for {$key}");
        }
        
        try {
            $result = $callback();
            $this->recordSuccess($key);
            return $result;
        } catch (ConnectionException $e) {
            $this->recordFailure($key);
            throw $e;
        }
    }
    
    private function isOpen(string $key): bool
    {
        return Cache::get("circuit:{$key}:open", false);
    }
    
    private function recordFailure(string $key): void
    {
        $failures = Cache::increment("circuit:{$key}:failures");
        
        if ($failures >= 5) {
            Cache::put("circuit:{$key}:open", true, 60);
        }
    }
    
    private function recordSuccess(string $key): void
    {
        Cache::forget("circuit:{$key}:failures");
        Cache::forget("circuit:{$key}:open");
    }
}
```

### 4. Graceful Degradation

```php
public function sendMessage(string $number, string $text): ?string
{
    try {
        $response = EvolutionApi::messages()->sendText($number, $text);
        return $response->getData()['key']['id'] ?? null;
    } catch (EvolutionApiException $e) {
        // Queue for later retry
        SendMessageJob::text('instance', $number, $text)
            ->delay(now()->addMinutes(5))
            ->dispatch();
        
        Log::warning('Message queued due to API error', [
            'error' => $e->getMessage(),
        ]);
        
        return null; // Gracefully return null instead of crashing
    }
}
```
