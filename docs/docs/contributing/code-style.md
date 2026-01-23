# Code Style Guide

Coding standards and style guidelines for the Evolution API Laravel package.

## Overview

This package follows:

- **PSR-12** - Extended Coding Style Guide
- **Laravel conventions** - Framework patterns
- **PHP 8.2+ features** - Modern PHP syntax

We use [Laravel Pint](https://github.com/laravel/pint) for automatic code formatting.

## Running Code Style Checks

```bash
# Check for issues
composer cs-check
# or
./vendor/bin/pint --test

# Fix issues automatically
composer cs-fix
# or
./vendor/bin/pint
```

## PHP Version

- Minimum: PHP 8.2
- Use modern PHP features when appropriate

### Required Features

```php
// Readonly properties
public readonly string $instanceName;

// Constructor property promotion
public function __construct(
    public readonly string $name,
    public readonly ?string $description = null,
) {}

// Named arguments
ApiResponse::success(
    data: $result,
    statusCode: 200,
);

// Match expressions
$status = match ($code) {
    200 => 'success',
    400 => 'bad_request',
    default => 'error',
};

// Null-safe operator
$name = $user?->profile?->name;

// Enums
enum MessageType: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
}
```

## Strict Types

Always declare strict types:

```php
<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi;
```

## Naming Conventions

### Classes

```php
// PascalCase
class MessageService {}
class ApiResponse {}
class EvolutionApiException {}
```

### Methods and Functions

```php
// camelCase
public function sendMessage(): void {}
public function getConnectionState(): array {}
private function formatPhoneNumber(): string {}
```

### Variables and Properties

```php
// camelCase
private string $instanceName;
protected array $sentMessages = [];
$messageContent = $data['message'];
```

### Constants

```php
// SCREAMING_SNAKE_CASE
public const DEFAULT_TIMEOUT = 30;
private const MAX_RETRY_ATTEMPTS = 3;
```

### Database

```php
// snake_case for columns and tables
'evolution_messages'
'instance_name'
'created_at'
```

## Type Declarations

### Always use type hints:

```php
public function sendText(
    string $instanceName,
    string $number,
    string $text,
    array $options = []
): ApiResponse {
    // ...
}
```

### Use union types when appropriate:

```php
public function find(string|int $id): ?Model
{
    // ...
}
```

### Use nullable types:

```php
public function getError(): ?string
{
    return $this->error;
}
```

## Return Types

### Always declare return types:

```php
public function isConnected(): bool {}
public function getData(): array {}
public function process(): void {}
public function find(): ?Model {}
```

### Use `static` for fluent interfaces:

```php
public function stubResponse(string $operation, mixed $response): static
{
    $this->responses[$operation] = $response;
    return $this;
}
```

## DocBlocks

### When to use:

```php
/**
 * Use docblocks for:
 * - Complex array structures
 * - Generic collections
 * - Inherited methods that need clarification
 * - Public API methods
 */

/**
 * Send a text message to a WhatsApp number.
 *
 * @param  array<string, mixed>  $options  Additional options
 * @return array<string, mixed>  API response data
 *
 * @throws EvolutionApiException
 */
public function sendText(
    string $instanceName,
    string $number,
    string $text,
    array $options = []
): array {
    // ...
}
```

### When NOT to use:

```php
// Don't add redundant docblocks
public function getMessage(): string
{
    return $this->message;
}

// Type hint is sufficient, no docblock needed
```

### Array type annotations:

```php
/**
 * @param  array<string, mixed>  $data
 * @return array<int, Message>
 */

/**
 * @var array<string, callable>
 */
private array $handlers = [];
```

## Class Structure

Order class members as follows:

```php
class ExampleService
{
    // 1. Traits
    use HasEvents;
    use Loggable;

    // 2. Constants
    public const DEFAULT_TIMEOUT = 30;
    private const CACHE_PREFIX = 'evolution_';

    // 3. Static properties
    protected static array $instances = [];

    // 4. Properties (public, protected, private)
    public readonly string $name;
    protected Client $client;
    private array $cache = [];

    // 5. Constructor
    public function __construct(
        public readonly string $instanceName,
        protected Client $client,
    ) {}

    // 6. Static methods
    public static function create(string $name): static {}

    // 7. Public methods
    public function send(): void {}
    public function receive(): array {}

    // 8. Protected methods
    protected function validate(): bool {}

    // 9. Private methods
    private function formatData(): array {}
}
```

## Method Length

- Keep methods under 20 lines when possible
- Extract complex logic into private methods
- Each method should do one thing well

```php
// Good: Short, focused methods
public function send(Message $message): ApiResponse
{
    $this->validate($message);
    $formatted = $this->format($message);
    
    return $this->client->post($formatted);
}

private function validate(Message $message): void
{
    // Validation logic
}

private function format(Message $message): array
{
    // Formatting logic
}
```

## Conditionals

### Early returns:

```php
// Good: Early return
public function process(?string $data): void
{
    if ($data === null) {
        return;
    }

    // Process data...
}

// Avoid: Nested conditions
public function process(?string $data): void
{
    if ($data !== null) {
        // Deep nesting...
    }
}
```

### Match expressions over switch:

```php
// Good: Match expression
$result = match ($status) {
    'sent' => MessageStatus::SENT,
    'delivered' => MessageStatus::DELIVERED,
    default => MessageStatus::UNKNOWN,
};

// Avoid: Switch statement for simple cases
switch ($status) {
    case 'sent':
        $result = MessageStatus::SENT;
        break;
    // ...
}
```

## Spacing and Formatting

### Array formatting:

```php
// Short arrays on one line
$simple = ['a', 'b', 'c'];

// Long arrays with trailing commas
$config = [
    'timeout' => 30,
    'retries' => 3,
    'verify_ssl' => true,
];
```

### Method chaining:

```php
$result = $query
    ->where('status', 'active')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

### Closures:

```php
// Short closures (arrow functions)
$names = array_map(fn ($user) => $user->name, $users);

// Multi-line closures
$filtered = array_filter($items, function ($item) {
    return $item->isActive()
        && $item->hasPermission();
});
```

## Imports

### Order and grouping:

```php
<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Services;

// PHP classes
use Exception;
use InvalidArgumentException;

// Vendor classes
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

// Package classes
use Lynkbyte\EvolutionApi\DTOs\ApiResponse;
use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;
```

### Never use aliases unless necessary:

```php
// Good
use Lynkbyte\EvolutionApi\Exceptions\ValidationException;

// Only if there's a conflict
use Lynkbyte\EvolutionApi\Exceptions\ValidationException as EvolutionValidationException;
use Illuminate\Validation\ValidationException;
```

## Error Handling

### Use specific exceptions:

```php
// Good: Specific exception
throw new InstanceNotFoundException($instanceName);

// Avoid: Generic exception
throw new Exception('Instance not found');
```

### Exception messages:

```php
// Good: Descriptive message
throw new ValidationException(
    "Phone number '{$number}' is not a valid format. Expected format: +5511999999999"
);

// Avoid: Vague message
throw new ValidationException('Invalid phone');
```

## Pint Configuration

Our `pint.json` configuration:

```json
{
    "preset": "laravel",
    "rules": {
        "declare_strict_types": true,
        "final_class": false,
        "ordered_class_elements": {
            "order": [
                "use_trait",
                "constant_public",
                "constant_protected",
                "constant_private",
                "property_public_static",
                "property_protected_static",
                "property_private_static",
                "property_public",
                "property_protected",
                "property_private",
                "construct",
                "destruct",
                "method_public_static",
                "method_protected_static",
                "method_private_static",
                "method_public",
                "method_protected",
                "method_private"
            ]
        }
    }
}
```

## Static Analysis

We use PHPStan at level 8 for maximum strictness:

```bash
./vendor/bin/phpstan analyse --level=8
```

### Common fixes:

```php
// Add generic types
/** @var Collection<int, Message> */
private Collection $messages;

// Handle nullable values
$name = $user?->name ?? 'Unknown';

// Explicit array types
/** @param array<string, mixed> $data */
```

## Git Commit Messages

Follow conventional commits:

```
type(scope): description

feat(messages): add support for template messages
fix(webhooks): handle null payload gracefully
docs(readme): update installation instructions
test(services): add coverage for rate limiting
refactor(client): extract retry logic to trait
chore(deps): update laravel/pint to 1.x
```

Types: `feat`, `fix`, `docs`, `test`, `refactor`, `chore`, `style`, `perf`

## Next Steps

- [Testing Guide](testing.md) - Writing tests
- [Development Setup](development.md) - Environment setup
