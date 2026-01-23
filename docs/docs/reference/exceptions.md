# Exceptions Reference

Custom exceptions thrown by the Evolution API package.

## Exception Hierarchy

```
Exception
└── EvolutionApiException
    ├── AuthenticationException
    ├── ConnectionException
    ├── InstanceNotFoundException
    ├── MessageException
    ├── RateLimitException
    ├── ValidationException
    └── WebhookException
```

## EvolutionApiException

Base exception for all Evolution API errors.

**Namespace:** `Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `message` | `string` | Error message |
| `code` | `int` | Error code |
| `responseData` | `?array` | API response data |
| `statusCode` | `?int` | HTTP status code |
| `instanceName` | `?string` | Instance involved |

### Methods

```php
use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;

try {
    $api->message()->sendText(...);
} catch (EvolutionApiException $e) {
    $e->getMessage();       // Error message
    $e->getCode();          // Error code
    $e->getStatusCode();    // HTTP status code (e.g., 400, 500)
    $e->getInstanceName();  // Instance name if applicable
    $e->getResponseData();  // Raw API response
    
    // For logging
    $e->toArray();  // Full context array
    $e->context();  // Same as toArray()
}
```

### Creating from Response

```php
// Automatically created from API responses
$exception = EvolutionApiException::fromResponse(
    response: $apiResponse,
    statusCode: 400,
    instanceName: 'main-instance'
);
```

---

## RateLimitException

Thrown when API rate limits are exceeded.

**Namespace:** `Lynkbyte\EvolutionApi\Exceptions\RateLimitException`

### Additional Properties

| Property | Type | Description |
|----------|------|-------------|
| `retryAfter` | `int` | Seconds until limit resets |
| `limitType` | `string` | Type of limit exceeded |

### Methods

```php
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;

try {
    $api->message()->sendText(...);
} catch (RateLimitException $e) {
    $retryAfter = $e->getRetryAfter(); // e.g., 60
    $limitType = $e->getLimitType();   // 'api', 'messages', 'media'
    
    // Wait and retry
    sleep($retryAfter);
}
```

### Factory Methods

```php
// API rate limit
$e = RateLimitException::apiLimitExceeded(
    retryAfter: 60,
    instanceName: 'main'
);

// Message rate limit
$e = RateLimitException::messageLimitExceeded(60, 'main');

// Media upload rate limit
$e = RateLimitException::mediaLimitExceeded(120, 'main');
```

### Handling

```php
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;

try {
    $this->sendBatch($messages);
} catch (RateLimitException $e) {
    Log::warning('Rate limit hit', [
        'retry_after' => $e->getRetryAfter(),
        'type' => $e->getLimitType(),
    ]);
    
    // Queue for later
    SendMessages::dispatch($messages)
        ->delay(now()->addSeconds($e->getRetryAfter()));
}
```

---

## AuthenticationException

Thrown when API authentication fails.

**Namespace:** `Lynkbyte\EvolutionApi\Exceptions\AuthenticationException`

### Common Causes

- Invalid API key
- Expired credentials
- Missing authentication headers

### Handling

```php
use Lynkbyte\EvolutionApi\Exceptions\AuthenticationException;

try {
    $api->instance()->fetchAll();
} catch (AuthenticationException $e) {
    Log::error('Authentication failed', [
        'message' => $e->getMessage(),
    ]);
    
    // Notify admin to check API credentials
}
```

---

## ConnectionException

Thrown when connection to Evolution API server fails.

**Namespace:** `Lynkbyte\EvolutionApi\Exceptions\ConnectionException`

### Common Causes

- Server unreachable
- Network timeout
- DNS resolution failure
- SSL/TLS errors

### Handling

```php
use Lynkbyte\EvolutionApi\Exceptions\ConnectionException;

try {
    $api->message()->sendText(...);
} catch (ConnectionException $e) {
    Log::error('Connection failed', [
        'message' => $e->getMessage(),
    ]);
    
    // Queue message for retry
    SendMessage::dispatch($message)
        ->delay(now()->addMinutes(1));
}
```

---

## InstanceNotFoundException

Thrown when the specified instance doesn't exist.

**Namespace:** `Lynkbyte\EvolutionApi\Exceptions\InstanceNotFoundException`

### Common Causes

- Typo in instance name
- Instance was deleted
- Instance not yet created

### Handling

```php
use Lynkbyte\EvolutionApi\Exceptions\InstanceNotFoundException;

try {
    $api->for('non-existent')->message()->sendText(...);
} catch (InstanceNotFoundException $e) {
    $instanceName = $e->getInstanceName();
    
    Log::warning("Instance not found: {$instanceName}");
    
    // Create instance or use fallback
}
```

---

## MessageException

Thrown when message sending fails.

**Namespace:** `Lynkbyte\EvolutionApi\Exceptions\MessageException`

### Common Causes

- Invalid phone number
- Number not on WhatsApp
- Instance not connected
- Message content validation failure

### Handling

```php
use Lynkbyte\EvolutionApi\Exceptions\MessageException;

try {
    $api->message()->sendText($number, $text);
} catch (MessageException $e) {
    Log::error('Message failed', [
        'error' => $e->getMessage(),
        'response' => $e->getResponseData(),
    ]);
    
    // Mark message as failed in database
    $message->markAsFailed($e->getMessage());
}
```

---

## ValidationException

Thrown when request validation fails.

**Namespace:** `Lynkbyte\EvolutionApi\Exceptions\ValidationException`

### Common Causes

- Missing required fields
- Invalid field formats
- Constraint violations

### Handling

```php
use Lynkbyte\EvolutionApi\Exceptions\ValidationException;

try {
    $api->message()->sendMedia($number, $invalidMedia);
} catch (ValidationException $e) {
    // Get validation errors
    $errors = $e->getResponseData();
    
    return response()->json([
        'error' => 'Validation failed',
        'details' => $errors,
    ], 422);
}
```

---

## WebhookException

Thrown when webhook processing fails.

**Namespace:** `Lynkbyte\EvolutionApi\Exceptions\WebhookException`

### Common Causes

- Invalid webhook signature
- Malformed payload
- Processing error

### Handling

```php
use Lynkbyte\EvolutionApi\Exceptions\WebhookException;

try {
    $handler->process($payload);
} catch (WebhookException $e) {
    Log::error('Webhook processing failed', [
        'error' => $e->getMessage(),
        'payload' => $payload,
    ]);
    
    // Return error response
    return response()->json(['error' => 'Processing failed'], 500);
}
```

---

## Global Exception Handling

### In Exception Handler

```php
// app/Exceptions/Handler.php

use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;
use Lynkbyte\EvolutionApi\Exceptions\AuthenticationException;

public function register(): void
{
    $this->renderable(function (RateLimitException $e, $request) {
        return response()->json([
            'error' => 'Rate limit exceeded',
            'retry_after' => $e->getRetryAfter(),
        ], 429)->header('Retry-After', $e->getRetryAfter());
    });
    
    $this->renderable(function (AuthenticationException $e, $request) {
        return response()->json([
            'error' => 'Authentication failed',
        ], 401);
    });
    
    $this->renderable(function (EvolutionApiException $e, $request) {
        return response()->json([
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
        ], $e->getStatusCode() ?? 500);
    });
}
```

### Logging Context

All exceptions provide context for logging:

```php
try {
    // API call
} catch (EvolutionApiException $e) {
    Log::error('Evolution API error', $e->context());
    // Logs: message, code, status_code, instance_name, response_data
}
```

## Best Practices

### 1. Catch Specific Exceptions First

```php
try {
    $api->message()->sendText(...);
} catch (RateLimitException $e) {
    // Handle rate limiting specifically
} catch (AuthenticationException $e) {
    // Handle auth errors
} catch (EvolutionApiException $e) {
    // Handle other API errors
} catch (\Exception $e) {
    // Handle unexpected errors
}
```

### 2. Use Retry Logic for Transient Errors

```php
use Lynkbyte\EvolutionApi\Exceptions\ConnectionException;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;

$maxRetries = 3;
$attempt = 0;

while ($attempt < $maxRetries) {
    try {
        return $api->message()->sendText(...);
    } catch (ConnectionException | RateLimitException $e) {
        $attempt++;
        if ($attempt >= $maxRetries) throw $e;
        sleep($e instanceof RateLimitException ? $e->getRetryAfter() : 5);
    }
}
```

### 3. Log Exception Context

```php
catch (EvolutionApiException $e) {
    Log::error('WhatsApp API error', array_merge(
        $e->context(),
        ['user_id' => auth()->id()]
    ));
}
```
