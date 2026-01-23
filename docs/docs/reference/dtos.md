# DTOs Reference

Data Transfer Objects (DTOs) provide type-safe structures for API data.

## ApiResponse

Wrapper for all API responses.

**Namespace:** `Lynkbyte\EvolutionApi\DTOs\ApiResponse`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `success` | `bool` | Whether the request was successful |
| `statusCode` | `int` | HTTP status code |
| `data` | `array` | Response data |
| `message` | `?string` | Response message or error |
| `headers` | `array` | Response headers |
| `responseTime` | `?float` | Response time in seconds |

### Creating Responses

```php
use Lynkbyte\EvolutionApi\DTOs\ApiResponse;

// Success response
$response = ApiResponse::success(
    data: ['id' => 'MSG_123'],
    statusCode: 200,
    message: 'Message sent',
    responseTime: 0.145
);

// Failure response
$response = ApiResponse::failure(
    message: 'Instance not found',
    statusCode: 404,
    data: ['error' => 'INSTANCE_NOT_FOUND']
);
```

### Checking Response Status

```php
if ($response->isSuccessful()) {
    $data = $response->getData();
}

if ($response->isFailed()) {
    $error = $response->message;
}
```

### Accessing Data

```php
// Get all data
$data = $response->getData();

// Get specific key
$messageId = $response->get('key.id');

// With default value
$status = $response->get('status', 'pending');
```

### Throwing on Failure

```php
use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;

try {
    $response->throw(); // Throws if failed
} catch (EvolutionApiException $e) {
    // Handle error
}

// Chainable
$data = $response->throw()->getData();
```

### Converting to Array

```php
$array = $response->toArray();
// [
//     'success' => true,
//     'status_code' => 200,
//     'data' => [...],
//     'message' => null,
//     'response_time' => 0.145,
// ]
```

## BaseDto

Abstract base class for DTOs.

**Namespace:** `Lynkbyte\EvolutionApi\DTOs\BaseDto`

### Creating Custom DTOs

```php
use Lynkbyte\EvolutionApi\DTOs\BaseDto;

class MessageDto extends BaseDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $number,
        public readonly string $content,
        public readonly string $type = 'text',
    ) {}
}
```

## WebhookPayload

Represents incoming webhook data (documented in [Webhooks](../webhooks/payload-dto.md)).

## Common DTO Patterns

### Immutable DTOs

All DTOs use `readonly` properties for immutability:

```php
$response = ApiResponse::success(['id' => '123']);
$response->success = false; // Error: Cannot modify readonly property
```

### Factory Methods

DTOs provide static factory methods:

```php
// Named constructors for clarity
$success = ApiResponse::success($data);
$failure = ApiResponse::failure($message);

// From API response
$exception = EvolutionApiException::fromResponse($response, $statusCode);
```

### Serialization

DTOs can be converted to arrays:

```php
$array = $response->toArray();
$json = json_encode($response->toArray());
```

## Using DTOs in Your Code

### Type-Hinting

```php
use Lynkbyte\EvolutionApi\DTOs\ApiResponse;

class MessageService
{
    public function send(string $to, string $text): ApiResponse
    {
        return $this->api->message()->sendText($to, $text);
    }
}
```

### Checking Types

```php
if ($response instanceof ApiResponse) {
    // Handle API response
}
```

## Best Practices

1. **Always check response status** before accessing data
2. **Use the `throw()` method** for fail-fast error handling
3. **Access data via methods** rather than direct property access when available
4. **Type-hint DTOs** in method signatures for clarity
