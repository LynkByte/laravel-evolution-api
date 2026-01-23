# Testing Assertions

The `EvolutionApiFake` class provides a comprehensive set of assertion methods for verifying your WhatsApp integration behavior.

## Message Assertions

### assertMessageSent

Assert that a message was sent to a specific phone number.

```php
// Basic assertion
$fake->assertMessageSent('5511999999999');

// With callback for additional assertions
$fake->assertMessageSent('5511999999999', function (array $message) {
    $this->assertEquals('text', $message['type']);
    $this->assertEquals('Hello World', $message['data']['text']);
});
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$number` | `string` | Phone number to check |
| `$callback` | `callable\|null` | Optional callback for additional assertions |

**Message Array Structure:**

```php
[
    'type' => 'text',           // Message type
    'instance' => 'my-instance', // Instance name
    'number' => '5511999999999', // Recipient number
    'data' => [                  // Type-specific data
        'text' => 'Hello',
        'options' => [],
    ],
    'timestamp' => 1234567890.123,
]
```

---

### assertMessageNotSent

Assert that no message was sent to a specific number.

```php
$fake->assertMessageNotSent('5511999999999');
```

**Use Cases:**

```php
// Verify blocked numbers don't receive messages
public function test_blocked_numbers_are_skipped(): void
{
    $service = new NotificationService($this->fake);
    
    $service->notify('5511999999999', 'Hello'); // Blocked number
    
    $this->fake->assertMessageNotSent('5511999999999');
}
```

---

### assertMessageSentTimes

Assert that exactly N messages were sent.

```php
// Assert exactly 3 messages were sent
$fake->assertMessageSentTimes(3);
```

**Use Cases:**

```php
// Verify batch notifications
public function test_batch_sends_all_notifications(): void
{
    $service = new BatchNotificationService($this->fake);
    
    $service->sendToAll([
        '5511999999999',
        '5511888888888',
        '5511777777777',
    ], 'Hello!');
    
    $this->fake->assertMessageSentTimes(3);
}
```

---

### assertNothingSent

Assert that no messages were sent at all.

```php
$fake->assertNothingSent();
```

**Use Cases:**

```php
// Verify dry run mode
public function test_dry_run_sends_nothing(): void
{
    $service = new NotificationService($this->fake);
    
    $service->notify('5511999999999', 'Hello', dryRun: true);
    
    $this->fake->assertNothingSent();
}

// Verify validation prevents sending
public function test_invalid_message_is_not_sent(): void
{
    $service = new MessageService($this->fake);
    
    try {
        $service->send('5511999999999', ''); // Empty message
    } catch (ValidationException $e) {
        // Expected
    }
    
    $this->fake->assertNothingSent();
}
```

---

### assertMessageContains

Assert that at least one message contains specific text.

```php
$fake->assertMessageContains('order confirmed');
```

**Use Cases:**

```php
// Verify order confirmation content
public function test_order_confirmation_includes_details(): void
{
    $service = new OrderNotificationService($this->fake);
    
    $service->sendConfirmation($order);
    
    $this->fake->assertMessageContains('Order #12345');
    $this->fake->assertMessageContains('$99.99');
}
```

---

### assertMessageTypeWas

Assert that a message of a specific type was sent.

```php
$fake->assertMessageTypeWas('text');
$fake->assertMessageTypeWas('media');
$fake->assertMessageTypeWas('audio');
$fake->assertMessageTypeWas('location');
$fake->assertMessageTypeWas('contact');
$fake->assertMessageTypeWas('poll');
$fake->assertMessageTypeWas('list');
```

**Use Cases:**

```php
// Verify image attachment
public function test_sends_receipt_as_image(): void
{
    $service = new ReceiptService($this->fake);
    
    $service->sendReceipt($order);
    
    $this->fake->assertMessageTypeWas('media');
}

// Verify location sharing
public function test_sends_store_location(): void
{
    $service = new StoreLocatorService($this->fake);
    
    $service->sendNearestStore($customer);
    
    $this->fake->assertMessageTypeWas('location');
}
```

## API Call Assertions

### assertApiCalled

Assert that a specific API operation was called.

```php
// Basic assertion
$fake->assertApiCalled('createInstance');

// With callback for additional assertions
$fake->assertApiCalled('createInstance', function (array $call) {
    $this->assertEquals('new-instance', $call['data']['instanceName']);
});
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$operation` | `string` | Operation name |
| `$callback` | `callable\|null` | Optional callback for assertions |

**Available Operations:**

| Operation | Description |
|-----------|-------------|
| `createInstance` | Instance creation |
| `fetchInstances` | List instances |
| `getQrCode` | Get QR code |
| `connectionState` | Check connection |
| `isWhatsApp` | Verify number |
| `sendReaction` | Send reaction |

**API Call Array Structure:**

```php
[
    'operation' => 'createInstance',
    'data' => [
        'instanceName' => 'test',
        // ... other parameters
    ],
    'timestamp' => 1234567890.123,
]
```

---

### assertApiNotCalled

Assert that a specific API operation was NOT called.

```php
$fake->assertApiNotCalled('createInstance');
```

**Use Cases:**

```php
// Verify caching prevents API calls
public function test_cached_connection_state_skips_api(): void
{
    $service = new ConnectionService($this->fake);
    
    // First call hits API
    $service->getConnectionState('instance');
    $this->fake->assertApiCalled('connectionState');
    
    $this->fake->clear();
    
    // Second call uses cache
    $service->getConnectionState('instance');
    $this->fake->assertApiNotCalled('connectionState');
}
```

## Combining Assertions

### Complex Test Scenarios

```php
public function test_order_workflow_sends_correct_messages(): void
{
    $workflow = new OrderWorkflow($this->fake);
    
    $workflow->process($order);
    
    // Verify total messages
    $this->fake->assertMessageSentTimes(3);
    
    // Verify customer receives confirmation
    $this->fake->assertMessageSent($order->customerPhone, function ($msg) {
        $this->assertEquals('text', $msg['type']);
        $this->assertStringContains('confirmed', $msg['data']['text']);
    });
    
    // Verify warehouse receives notification
    $this->fake->assertMessageSent($warehousePhone);
    
    // Verify receipt was sent as image
    $this->fake->assertMessageTypeWas('media');
}
```

### Testing Error Scenarios

```php
public function test_handles_invalid_number_gracefully(): void
{
    $service = new NotificationService($this->fake);
    
    // Stub isWhatsApp to return false
    $this->fake->stubResponse('isWhatsApp', ['exists' => false]);
    
    $result = $service->notify('invalid-number', 'Hello');
    
    // Verify no message was sent
    $this->fake->assertNothingSent();
    
    // Verify validation was attempted
    $this->fake->assertApiCalled('isWhatsApp');
}
```

### Testing with Multiple Instances

```php
public function test_routes_to_correct_instance(): void
{
    $router = new MessageRouter($this->fake);
    
    $router->send('5511999999999', 'Hello'); // Brazil -> instance-br
    $router->send('1234567890', 'Hello');    // USA -> instance-us
    
    $messages = $this->fake->getSentMessages();
    
    $this->assertEquals('instance-br', $messages[0]['instance']);
    $this->assertEquals('instance-us', $messages[1]['instance']);
}
```

## Custom Assertions

### Creating Custom Assertion Helpers

```php
trait WhatsAppAssertions
{
    protected function assertMediaSentTo(string $number, string $mediaType): void
    {
        $this->fake->assertMessageSent($number, function ($msg) use ($mediaType) {
            $this->assertEquals('media', $msg['type']);
            $this->assertEquals($mediaType, $msg['data']['media']['mediatype']);
        });
    }
    
    protected function assertLocationSentTo(
        string $number, 
        float $lat, 
        float $lng
    ): void {
        $this->fake->assertMessageSent($number, function ($msg) use ($lat, $lng) {
            $this->assertEquals('location', $msg['type']);
            $this->assertEquals($lat, $msg['data']['latitude']);
            $this->assertEquals($lng, $msg['data']['longitude']);
        });
    }
}
```

### Using Custom Assertions

```php
class DeliveryNotificationTest extends TestCase
{
    use WhatsAppAssertions;
    
    public function test_sends_delivery_location(): void
    {
        $service = new DeliveryService($this->fake);
        
        $service->notifyDeliveryLocation($order);
        
        $this->assertLocationSentTo(
            $order->customerPhone,
            $order->deliveryLat,
            $order->deliveryLng
        );
    }
}
```

## Assertion Reference

| Method | Description |
|--------|-------------|
| `assertMessageSent($number, $callback)` | Assert message sent to number |
| `assertMessageNotSent($number)` | Assert no message to number |
| `assertMessageSentTimes($count)` | Assert exact message count |
| `assertNothingSent()` | Assert no messages sent |
| `assertMessageContains($text)` | Assert message contains text |
| `assertMessageTypeWas($type)` | Assert message type was sent |
| `assertApiCalled($operation, $callback)` | Assert API operation called |
| `assertApiNotCalled($operation)` | Assert API operation not called |

## Next Steps

- [Test Examples](examples.md) - Complete test examples
- [Testing Fakes](fakes.md) - Learn more about the fake implementation
