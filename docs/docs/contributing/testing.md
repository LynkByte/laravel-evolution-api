# Testing Guide

Guidelines for writing tests for the Evolution API Laravel package.

## Testing Stack

- **Pest PHP** - Testing framework
- **PHPUnit** - Underlying test runner
- **Mockery** - Mocking library (via Pest)
- **Orchestra Testbench** - Laravel package testing

## Test Structure

```
tests/
├── Feature/
│   ├── Services/
│   │   ├── InstanceServiceTest.php
│   │   └── MessageServiceTest.php
│   ├── Webhooks/
│   │   └── WebhookControllerTest.php
│   └── Commands/
│       └── HealthCheckCommandTest.php
├── Unit/
│   ├── DTOs/
│   │   └── ApiResponseTest.php
│   ├── Enums/
│   │   └── MessageTypeTest.php
│   └── Support/
│       └── PhoneFormatterTest.php
├── Pest.php
└── TestCase.php
```

## Base Test Case

```php
// tests/TestCase.php
<?php

namespace Lynkbyte\EvolutionApi\Tests;

use Lynkbyte\EvolutionApi\EvolutionApiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup code
    }

    protected function getPackageProviders($app): array
    {
        return [
            EvolutionApiServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('evolution-api.server_url', 'http://localhost:8080');
        config()->set('evolution-api.api_key', 'test-key');
        config()->set('evolution-api.default_instance', 'test-instance');
    }
}
```

## Pest Configuration

```php
// tests/Pest.php
<?php

use Lynkbyte\EvolutionApi\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

// Global helpers
function fakeEvolutionApi(): \Lynkbyte\EvolutionApi\Testing\Fakes\EvolutionApiFake
{
    $fake = new \Lynkbyte\EvolutionApi\Testing\Fakes\EvolutionApiFake();
    app()->instance('evolution-api', $fake);
    return $fake;
}
```

## Writing Unit Tests

### Testing DTOs

```php
<?php

use Lynkbyte\EvolutionApi\DTOs\ApiResponse;

describe('ApiResponse', function () {
    it('creates success response', function () {
        $response = ApiResponse::success(['id' => '123']);
        
        expect($response->isSuccessful())->toBeTrue();
        expect($response->statusCode)->toBe(200);
        expect($response->get('id'))->toBe('123');
    });

    it('creates failure response', function () {
        $response = ApiResponse::failure('Error message', 400);
        
        expect($response->isFailed())->toBeTrue();
        expect($response->message)->toBe('Error message');
        expect($response->statusCode)->toBe(400);
    });

    it('throws on failure when requested', function () {
        $response = ApiResponse::failure('Error', 400);
        
        expect(fn() => $response->throw())
            ->toThrow(\Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException::class);
    });
});
```

### Testing Enums

```php
<?php

use Lynkbyte\EvolutionApi\Enums\MessageType;
use Lynkbyte\EvolutionApi\Enums\MessageStatus;

describe('MessageType', function () {
    it('identifies media types', function () {
        expect(MessageType::IMAGE->isMedia())->toBeTrue();
        expect(MessageType::VIDEO->isMedia())->toBeTrue();
        expect(MessageType::TEXT->isMedia())->toBeFalse();
    });

    it('creates from API response', function () {
        expect(MessageType::fromApi('imagemessage'))->toBe(MessageType::IMAGE);
        expect(MessageType::fromApi('conversation'))->toBe(MessageType::TEXT);
    });
});

describe('MessageStatus', function () {
    it('creates from numeric value', function () {
        expect(MessageStatus::fromApi(2))->toBe(MessageStatus::DELIVERED);
        expect(MessageStatus::fromApi(3))->toBe(MessageStatus::READ);
    });
});
```

## Writing Feature Tests

### Testing Services with Fakes

```php
<?php

use Lynkbyte\EvolutionApi\Testing\Fakes\EvolutionApiFake;

describe('Message Service', function () {
    beforeEach(function () {
        $this->fake = fakeEvolutionApi();
    });

    it('sends text message', function () {
        $result = app('evolution-api')
            ->message('test-instance')
            ->sendText('5511999999999', 'Hello World');

        $this->fake->assertMessageSent('5511999999999');
        $this->fake->assertMessageContains('Hello World');
    });

    it('sends media message', function () {
        app('evolution-api')
            ->message('test-instance')
            ->sendMedia('5511999999999', [
                'mediatype' => 'image',
                'media' => 'https://example.com/image.jpg',
            ]);

        $this->fake->assertMessageTypeWas('media');
    });

    it('stubs custom responses', function () {
        $this->fake->stubResponse('sendText', [
            'key' => ['id' => 'CUSTOM_ID'],
            'status' => 'SENT',
        ]);

        $result = app('evolution-api')
            ->sendText('test-instance', '5511999999999', 'Test');

        expect($result['key']['id'])->toBe('CUSTOM_ID');
    });
});
```

### Testing HTTP Client

```php
<?php

use Illuminate\Support\Facades\Http;

describe('API Client', function () {
    it('sends requests with correct headers', function () {
        Http::fake([
            '*' => Http::response(['success' => true]),
        ]);

        app('evolution-api')->instance()->fetchAll();

        Http::assertSent(function ($request) {
            return $request->hasHeader('apikey', 'test-key');
        });
    });

    it('handles connection errors', function () {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        expect(fn() => app('evolution-api')->instance()->fetchAll())
            ->toThrow(\Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException::class);
    });
});
```

### Testing Webhooks

```php
<?php

describe('Webhook Controller', function () {
    it('processes valid webhook', function () {
        $payload = [
            'event' => 'MESSAGES_UPSERT',
            'instance' => 'test-instance',
            'data' => [
                'key' => ['id' => 'MSG_123'],
                'message' => ['conversation' => 'Hello'],
            ],
        ];

        $response = $this->postJson('/api/evolution-api/webhook', $payload);

        $response->assertOk();
    });

    it('rejects invalid signature', function () {
        config(['evolution-api.webhook.verify_signature' => true]);
        config(['evolution-api.webhook.secret' => 'secret']);

        $response = $this->postJson('/api/evolution-api/webhook', [
            'event' => 'MESSAGES_UPSERT',
        ], ['X-Webhook-Signature' => 'invalid']);

        $response->assertUnauthorized();
    });
});
```

### Testing Commands

```php
<?php

describe('Health Check Command', function () {
    it('reports healthy server', function () {
        $fake = fakeEvolutionApi();
        $fake->stubResponse('fetchInstances', [
            ['instance' => ['instanceName' => 'test', 'status' => 'open']],
        ]);

        $this->artisan('evolution-api:health')
            ->assertSuccessful()
            ->expectsOutput('Health check completed successfully!');
    });

    it('reports failed connection', function () {
        $fake = fakeEvolutionApi();
        $fake->stubResponse('fetchInstances', function () {
            throw new \Exception('Connection failed');
        });

        $this->artisan('evolution-api:health')
            ->assertFailed();
    });
});
```

## Testing Events

```php
<?php

use Illuminate\Support\Facades\Event;
use Lynkbyte\EvolutionApi\Events\MessageReceived;

describe('Webhook Events', function () {
    it('dispatches MessageReceived event', function () {
        Event::fake([MessageReceived::class]);

        $this->postJson('/api/evolution-api/webhook', [
            'event' => 'MESSAGES_UPSERT',
            'instance' => 'test',
            'data' => ['key' => ['id' => '123']],
        ]);

        Event::assertDispatched(MessageReceived::class, function ($event) {
            return $event->instanceName === 'test';
        });
    });
});
```

## Testing Jobs

```php
<?php

use Illuminate\Support\Facades\Queue;
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;

describe('SendMessageJob', function () {
    it('queues message for sending', function () {
        Queue::fake();

        SendMessageJob::dispatch('test-instance', '5511999999999', 'Hello');

        Queue::assertPushed(SendMessageJob::class);
    });

    it('sends message when processed', function () {
        $fake = fakeEvolutionApi();

        $job = new SendMessageJob('test-instance', '5511999999999', 'Hello');
        $job->handle(app('evolution-api'));

        $fake->assertMessageSent('5511999999999');
    });
});
```

## Test Data Factories

```php
// tests/Factories/MessageFactory.php
<?php

namespace Lynkbyte\EvolutionApi\Tests\Factories;

class MessageFactory
{
    public static function webhookPayload(array $overrides = []): array
    {
        return array_merge([
            'event' => 'MESSAGES_UPSERT',
            'instance' => 'test-instance',
            'data' => [
                'key' => [
                    'remoteJid' => '5511999999999@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'MSG_' . uniqid(),
                ],
                'message' => [
                    'conversation' => 'Test message',
                ],
                'messageTimestamp' => time(),
            ],
        ], $overrides);
    }
}
```

## Code Coverage

```bash
# Generate coverage report
./vendor/bin/pest --coverage

# HTML coverage report
./vendor/bin/pest --coverage --coverage-html=coverage

# Minimum coverage requirement
./vendor/bin/pest --coverage --min=80
```

## Best Practices

### 1. Test Behavior, Not Implementation

```php
// Good: Tests behavior
it('sends notification to user', function () {
    $service->notify($user);
    $this->fake->assertMessageSent($user->phone);
});

// Avoid: Tests implementation details
it('calls sendText method', function () {
    // Too coupled to implementation
});
```

### 2. Use Descriptive Test Names

```php
// Good
it('rejects messages to numbers not on WhatsApp');
it('retries failed messages up to 3 times');

// Avoid
it('test1');
it('works');
```

### 3. One Assertion Focus Per Test

```php
// Good: Focused test
it('validates phone number format', function () {
    expect(PhoneFormatter::isValid('invalid'))->toBeFalse();
});

// If multiple assertions, they should relate to one concept
it('formats Brazilian phone numbers', function () {
    expect(PhoneFormatter::format('11999999999'))->toBe('5511999999999');
    expect(PhoneFormatter::format('+5511999999999'))->toBe('5511999999999');
});
```

### 4. Use Test Datasets

```php
dataset('invalid_phones', [
    'empty' => [''],
    'letters' => ['abc'],
    'too_short' => ['123'],
    'special_chars' => ['!@#$'],
]);

it('rejects invalid phone numbers', function ($phone) {
    expect(PhoneFormatter::isValid($phone))->toBeFalse();
})->with('invalid_phones');
```

## Next Steps

- [Code Style Guide](code-style.md) - Coding standards
- [Development Setup](development.md) - Environment setup
