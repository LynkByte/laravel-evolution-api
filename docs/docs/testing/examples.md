# Test Examples

Complete, real-world examples of testing WhatsApp integrations with the Evolution API package.

## Setup

### Base Test Case

```php
<?php

namespace Tests;

use Lynkbyte\EvolutionApi\Testing\Fakes\EvolutionApiFake;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected EvolutionApiFake $evolutionFake;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->evolutionFake = new EvolutionApiFake();
        $this->app->instance('evolution-api', $this->evolutionFake);
    }

    protected function tearDown(): void
    {
        $this->evolutionFake->clear();
        parent::tearDown();
    }
}
```

### Pest Setup

```php
// tests/Pest.php
uses(Tests\TestCase::class)->in('Feature', 'Unit');

// Helper function
function evolutionFake(): EvolutionApiFake
{
    return app('evolution-api');
}
```

## Feature Tests

### Testing Notification Service

```php
<?php

namespace Tests\Feature;

use App\Services\NotificationService;
use App\Models\User;
use Lynkbyte\EvolutionApi\Testing\Fakes\EvolutionApiFake;

class NotificationServiceTest extends TestCase
{
    private NotificationService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotificationService::class);
    }

    public function test_sends_welcome_message_to_new_user(): void
    {
        $user = User::factory()->create([
            'phone' => '5511999999999',
            'name' => 'John Doe',
        ]);

        $this->service->sendWelcome($user);

        $this->evolutionFake->assertMessageSent('5511999999999');
        $this->evolutionFake->assertMessageContains('Welcome, John');
    }

    public function test_sends_order_confirmation(): void
    {
        $order = Order::factory()->create([
            'number' => 'ORD-12345',
            'total' => 99.99,
        ]);

        $this->service->sendOrderConfirmation($order);

        $this->evolutionFake->assertMessageSent($order->customer->phone, function ($msg) {
            $this->assertStringContainsString('ORD-12345', $msg['data']['text']);
            $this->assertStringContainsString('$99.99', $msg['data']['text']);
        });
    }

    public function test_sends_receipt_as_pdf(): void
    {
        $order = Order::factory()->create();

        $this->service->sendReceipt($order);

        $this->evolutionFake->assertMessageTypeWas('media');
        $this->evolutionFake->assertMessageSent($order->customer->phone, function ($msg) {
            $this->assertEquals('document', $msg['data']['media']['mediatype']);
            $this->assertStringEndsWith('.pdf', $msg['data']['media']['fileName']);
        });
    }

    public function test_does_not_send_to_opted_out_users(): void
    {
        $user = User::factory()->create([
            'phone' => '5511999999999',
            'whatsapp_opt_out' => true,
        ]);

        $this->service->sendPromotion($user, 'Sale!');

        $this->evolutionFake->assertMessageNotSent('5511999999999');
    }

    public function test_validates_phone_before_sending(): void
    {
        $this->evolutionFake->stubResponse('isWhatsApp', ['exists' => false]);

        $user = User::factory()->create(['phone' => 'invalid']);

        $result = $this->service->sendWelcome($user);

        $this->assertFalse($result);
        $this->evolutionFake->assertApiCalled('isWhatsApp');
        $this->evolutionFake->assertNothingSent();
    }
}
```

### Testing Batch Operations

```php
<?php

namespace Tests\Feature;

use App\Services\BroadcastService;
use App\Models\Campaign;

class BroadcastServiceTest extends TestCase
{
    public function test_broadcasts_to_all_subscribers(): void
    {
        $campaign = Campaign::factory()
            ->hasSubscribers(5)
            ->create();

        $service = app(BroadcastService::class);
        $service->send($campaign);

        $this->evolutionFake->assertMessageSentTimes(5);
        
        foreach ($campaign->subscribers as $subscriber) {
            $this->evolutionFake->assertMessageSent($subscriber->phone);
        }
    }

    public function test_respects_rate_limits(): void
    {
        $this->evolutionFake->stubResponse('sendText', function () {
            static $count = 0;
            if (++$count > 10) {
                throw new RateLimitException('Rate limit exceeded');
            }
            return ['status' => 'sent'];
        });

        $campaign = Campaign::factory()
            ->hasSubscribers(20)
            ->create();

        $service = app(BroadcastService::class);
        $result = $service->send($campaign);

        // Should have attempted 10, then backed off
        $this->assertEquals(10, $result['sent']);
        $this->assertEquals(10, $result['pending']);
    }

    public function test_tracks_delivery_status(): void
    {
        $campaign = Campaign::factory()
            ->hasSubscribers(3)
            ->create();

        $service = app(BroadcastService::class);
        $service->send($campaign);

        $this->assertDatabaseHas('campaign_deliveries', [
            'campaign_id' => $campaign->id,
            'status' => 'sent',
        ]);
    }
}
```

### Testing Webhook Handlers

```php
<?php

namespace Tests\Feature;

use App\Listeners\HandleIncomingMessage;
use Lynkbyte\EvolutionApi\DTOs\WebhookPayload;

class WebhookHandlerTest extends TestCase
{
    public function test_auto_replies_to_keywords(): void
    {
        $payload = WebhookPayload::from([
            'event' => 'messages.upsert',
            'instance' => 'main',
            'data' => [
                'key' => ['remoteJid' => '5511999999999@s.whatsapp.net'],
                'message' => ['conversation' => 'HELP'],
            ],
        ]);

        $handler = app(HandleIncomingMessage::class);
        $handler->handle($payload);

        $this->evolutionFake->assertMessageSent('5511999999999');
        $this->evolutionFake->assertMessageContains('How can we help');
    }

    public function test_forwards_to_support_queue(): void
    {
        $payload = WebhookPayload::from([
            'event' => 'messages.upsert',
            'instance' => 'support',
            'data' => [
                'key' => ['remoteJid' => '5511999999999@s.whatsapp.net'],
                'message' => ['conversation' => 'I need help with my order'],
            ],
        ]);

        $handler = app(HandleIncomingMessage::class);
        $handler->handle($payload);

        $this->assertDatabaseHas('support_tickets', [
            'phone' => '5511999999999',
            'status' => 'open',
        ]);
    }
}
```

## Unit Tests

### Testing Message Builders

```php
<?php

namespace Tests\Unit;

use App\Messaging\OrderConfirmationMessage;
use App\Models\Order;

class OrderConfirmationMessageTest extends TestCase
{
    public function test_builds_correct_message(): void
    {
        $order = Order::factory()->make([
            'number' => 'ORD-123',
            'total' => 150.00,
            'items' => [
                ['name' => 'Widget', 'qty' => 2, 'price' => 75.00],
            ],
        ]);

        $message = new OrderConfirmationMessage($order);
        
        $result = $message->build();

        $this->assertStringContainsString('ORD-123', $result->text);
        $this->assertStringContainsString('$150.00', $result->text);
        $this->assertStringContainsString('Widget', $result->text);
    }

    public function test_includes_tracking_link(): void
    {
        $order = Order::factory()->make([
            'tracking_number' => 'TRACK123',
        ]);

        $message = new OrderConfirmationMessage($order);
        $result = $message->build();

        $this->assertStringContainsString('track.example.com/TRACK123', $result->text);
    }
}
```

### Testing Phone Number Validation

```php
<?php

namespace Tests\Unit;

use App\Services\PhoneValidator;

class PhoneValidatorTest extends TestCase
{
    public function test_validates_brazilian_numbers(): void
    {
        $this->evolutionFake->stubResponse('isWhatsApp', ['exists' => true]);

        $validator = app(PhoneValidator::class);
        
        $this->assertTrue($validator->isValid('5511999999999'));
        $this->evolutionFake->assertApiCalled('isWhatsApp');
    }

    public function test_rejects_invalid_format(): void
    {
        $validator = app(PhoneValidator::class);
        
        $this->assertFalse($validator->isValid('invalid'));
        $this->evolutionFake->assertApiNotCalled('isWhatsApp');
    }

    public function test_caches_validation_results(): void
    {
        $this->evolutionFake->stubResponse('isWhatsApp', ['exists' => true]);

        $validator = app(PhoneValidator::class);
        
        // First call
        $validator->isValid('5511999999999');
        
        // Clear to check if second call hits API
        $this->evolutionFake->clear();
        
        // Second call (should be cached)
        $validator->isValid('5511999999999');
        
        $this->evolutionFake->assertApiNotCalled('isWhatsApp');
    }
}
```

## Pest Examples

### Basic Pest Tests

```php
<?php

use App\Services\NotificationService;

it('sends welcome message to new users', function () {
    $user = User::factory()->create(['phone' => '5511999999999']);
    
    app(NotificationService::class)->sendWelcome($user);
    
    evolutionFake()->assertMessageSent('5511999999999');
    evolutionFake()->assertMessageContains('Welcome');
});

it('does not send to unverified phones', function () {
    evolutionFake()->stubResponse('isWhatsApp', ['exists' => false]);
    
    $user = User::factory()->create();
    
    app(NotificationService::class)->sendWelcome($user);
    
    evolutionFake()->assertNothingSent();
});
```

### Pest Datasets

```php
<?php

dataset('message_types', [
    'text' => ['sendText', 'text'],
    'media' => ['sendMedia', 'media'],
    'audio' => ['sendAudio', 'audio'],
    'location' => ['sendLocation', 'location'],
]);

it('records correct message type for {type}', function ($method, $type) {
    $fake = evolutionFake();
    
    match ($method) {
        'sendText' => $fake->sendText('inst', '5511999999999', 'Test'),
        'sendMedia' => $fake->sendMedia('inst', '5511999999999', ['mediatype' => 'image']),
        'sendAudio' => $fake->sendAudio('inst', '5511999999999', 'audio.mp3'),
        'sendLocation' => $fake->sendLocation('inst', '5511999999999', -23.5, -46.6),
    };
    
    $fake->assertMessageTypeWas($type);
})->with('message_types');
```

### Pest Higher Order Tests

```php
<?php

test('notification service')
    ->tap(fn () => User::factory()->create(['phone' => '5511999999999']))
    ->expect(fn () => app(NotificationService::class)->sendWelcome(User::first()))
    ->toBeTrue()
    ->and(fn () => evolutionFake()->getSentMessages())
    ->toHaveCount(1);
```

## Integration Tests

### Testing with Queues

```php
<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use Illuminate\Support\Facades\Queue;

class QueuedMessagesTest extends TestCase
{
    public function test_messages_are_queued(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        
        app(NotificationService::class)->queueWelcome($user);

        Queue::assertPushed(SendWhatsAppMessage::class, function ($job) use ($user) {
            return $job->phone === $user->phone;
        });
    }

    public function test_queued_job_sends_message(): void
    {
        $user = User::factory()->create(['phone' => '5511999999999']);
        
        $job = new SendWhatsAppMessage(
            $user->phone,
            'Hello from queue!'
        );
        
        $job->handle(app('evolution-api'));

        $this->evolutionFake->assertMessageSent('5511999999999');
        $this->evolutionFake->assertMessageContains('Hello from queue!');
    }
}
```

### Testing with Events

```php
<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Events\MessageFailed;
use Illuminate\Support\Facades\Event;

class MessageEventsTest extends TestCase
{
    public function test_dispatches_sent_event(): void
    {
        Event::fake([MessageSent::class]);

        app(NotificationService::class)->send('5511999999999', 'Hello');

        Event::assertDispatched(MessageSent::class, function ($event) {
            return $event->phone === '5511999999999';
        });
    }

    public function test_dispatches_failed_event_on_error(): void
    {
        Event::fake([MessageFailed::class]);
        
        $this->evolutionFake->stubResponse('sendText', function () {
            throw new \Exception('Connection failed');
        });

        try {
            app(NotificationService::class)->send('5511999999999', 'Hello');
        } catch (\Exception $e) {
            // Expected
        }

        Event::assertDispatched(MessageFailed::class);
    }
}
```

## Testing Instance Management

```php
<?php

namespace Tests\Feature;

use App\Services\InstanceManager;

class InstanceManagementTest extends TestCase
{
    public function test_creates_new_instance(): void
    {
        $manager = app(InstanceManager::class);
        
        $result = $manager->create('new-instance', [
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
        ]);

        $this->evolutionFake->assertApiCalled('createInstance', function ($call) {
            $this->assertEquals('new-instance', $call['data']['instanceName']);
        });
    }

    public function test_checks_connection_state(): void
    {
        $this->evolutionFake->stubResponse('connectionState', [
            'instance' => ['instanceName' => 'main', 'state' => 'open'],
        ]);

        $manager = app(InstanceManager::class);
        $state = $manager->getState('main');

        $this->assertEquals('open', $state);
        $this->evolutionFake->assertApiCalled('connectionState');
    }

    public function test_generates_qr_code(): void
    {
        $this->evolutionFake->stubResponse('getQrCode', [
            'base64' => 'data:image/png;base64,iVBORw0KGgo...',
            'code' => '2@abc123...',
        ]);

        $manager = app(InstanceManager::class);
        $qr = $manager->getQrCode('main');

        $this->assertStringStartsWith('data:image/png', $qr['base64']);
        $this->evolutionFake->assertApiCalled('getQrCode');
    }
}
```

## Debugging Tips

### Inspecting Sent Messages

```php
public function test_debug_sent_messages(): void
{
    // Send some messages
    app(NotificationService::class)->sendBatch($users);

    // Dump all sent messages for debugging
    dump($this->evolutionFake->getSentMessages());

    // Get counts by type
    dump($this->evolutionFake->getMessageCountByType());

    // Get last message
    dump($this->evolutionFake->getLastMessage());
}
```

### Inspecting API Calls

```php
public function test_debug_api_calls(): void
{
    // Perform operations
    app(InstanceManager::class)->setup('new-instance');

    // Dump all API calls
    dump($this->evolutionFake->getApiCalls());

    // Get last call
    dump($this->evolutionFake->getLastApiCall());
}
```

## Best Practices Summary

1. **Always use fakes** - Never call real APIs in tests
2. **Clear between tests** - Use `$fake->clear()` in setUp/tearDown
3. **Test both success and failure** - Stub error responses too
4. **Use callbacks for complex assertions** - Validate message content
5. **Test queue integration** - Verify jobs dispatch and execute correctly
6. **Test event dispatching** - Verify events fire appropriately
7. **Keep tests focused** - One behavior per test

## Next Steps

- [Testing Fakes](fakes.md) - Deep dive into fake implementation
- [Assertions](assertions.md) - Complete assertion reference
