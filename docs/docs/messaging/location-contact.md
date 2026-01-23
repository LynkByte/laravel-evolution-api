---
title: Location & Contacts
description: Sending location and contact messages with Laravel Evolution API
---

# Location & Contact Messages

Send location pins and contact cards (vCards) through WhatsApp.

## Location Messages

### Simple Location

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->location(
        number: '5511999999999',
        latitude: -23.5505,
        longitude: -46.6333,
        name: 'Sao Paulo',
        address: 'Sao Paulo, Brazil'
    );
```

### Using DTO

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendLocationMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendLocation([
        'number' => '5511999999999',
        'latitude' => -23.5505,
        'longitude' => -46.6333,
        'name' => 'Our Office',
        'address' => '123 Business Ave, Sao Paulo, SP 01310-100',
    ]);

// Or with constructor
$dto = new SendLocationMessageDto(
    number: '5511999999999',
    latitude: -23.5505,
    longitude: -46.6333,
    name: 'Meeting Point',
    address: 'Central Park, New York'
);

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendLocation($dto);
```

### Location Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `number` | string | Yes | Recipient phone number |
| `latitude` | float | Yes | Latitude coordinate |
| `longitude` | float | Yes | Longitude coordinate |
| `name` | string | No | Location name/title |
| `address` | string | No | Full address text |

## Contact Messages

### Single Contact

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendContactMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendContact([
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

### Contact with Full Details

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendContact([
        'number' => '5511999999999',
        'contact' => [
            [
                'fullName' => 'John Doe',
                'wuid' => '5511888888888',
                'phoneNumber' => '+55 11 88888-8888',
                'organization' => 'Acme Corporation',
                'email' => 'john.doe@acme.com',
                'url' => 'https://acme.com',
            ],
        ],
    ]);
```

### Multiple Contacts

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendContact([
        'number' => '5511999999999',
        'contact' => [
            [
                'fullName' => 'Sales Team',
                'wuid' => '5511111111111',
                'phoneNumber' => '+55 11 1111-1111',
                'organization' => 'Acme Corp',
            ],
            [
                'fullName' => 'Support Team',
                'wuid' => '5511222222222',
                'phoneNumber' => '+55 11 2222-2222',
                'organization' => 'Acme Corp',
            ],
            [
                'fullName' => 'Billing Department',
                'wuid' => '5511333333333',
                'phoneNumber' => '+55 11 3333-3333',
                'organization' => 'Acme Corp',
            ],
        ],
    ]);
```

### Contact Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `fullName` | string | Yes | Contact display name |
| `wuid` | string | No | WhatsApp User ID (phone number) |
| `phoneNumber` | string | No | Formatted phone number |
| `organization` | string | No | Company/organization name |
| `email` | string | No | Email address |
| `url` | string | No | Website URL |

## Examples

### Send Store Location

```php
public function sendStoreLocation(string $phone, Store $store): void
{
    EvolutionApi::for('my-instance')
        ->messages()
        ->location(
            number: $phone,
            latitude: $store->latitude,
            longitude: $store->longitude,
            name: $store->name,
            address: $store->full_address
        );
}
```

### Send Delivery Location

```php
public function sendDeliveryLocation(Order $order): void
{
    // First send text
    EvolutionApi::for('my-instance')
        ->messages()
        ->text(
            $order->customer_phone,
            "Your order #{$order->id} is on the way! Here's the delivery location:"
        );
    
    // Then send location
    EvolutionApi::for('my-instance')
        ->messages()
        ->location(
            number: $order->customer_phone,
            latitude: $order->delivery_latitude,
            longitude: $order->delivery_longitude,
            name: 'Delivery Address',
            address: $order->delivery_address
        );
}
```

### Send Business Contact Card

```php
public function sendBusinessCard(string $phone): void
{
    EvolutionApi::for('my-instance')
        ->messages()
        ->sendContact([
            'number' => $phone,
            'contact' => [
                [
                    'fullName' => 'Acme Support',
                    'wuid' => config('services.whatsapp.support_number'),
                    'phoneNumber' => '+1 800 123 4567',
                    'organization' => 'Acme Corporation',
                    'email' => 'support@acme.com',
                    'url' => 'https://acme.com/support',
                ],
            ],
        ]);
}
```

### Send Agent Contact

```php
public function assignAgent(string $customerPhone, User $agent): void
{
    // Notify customer
    EvolutionApi::for('my-instance')
        ->messages()
        ->text(
            $customerPhone,
            "You've been assigned to {$agent->name}. Here's their contact:"
        );
    
    // Send agent contact card
    EvolutionApi::for('my-instance')
        ->messages()
        ->sendContact([
            'number' => $customerPhone,
            'contact' => [
                [
                    'fullName' => $agent->name,
                    'wuid' => $agent->phone,
                    'phoneNumber' => $agent->formatted_phone,
                    'organization' => config('app.name'),
                    'email' => $agent->email,
                ],
            ],
        ]);
}
```

### Location from Google Maps URL

```php
public function parseAndSendLocation(string $phone, string $googleMapsUrl): void
{
    // Extract coordinates from Google Maps URL
    // URL format: https://www.google.com/maps?q=-23.5505,-46.6333
    preg_match('/q=([-\d.]+),([-\d.]+)/', $googleMapsUrl, $matches);
    
    if (count($matches) === 3) {
        EvolutionApi::for('my-instance')
            ->messages()
            ->location(
                number: $phone,
                latitude: (float) $matches[1],
                longitude: (float) $matches[2],
                name: 'Shared Location'
            );
    }
}
```

### Send Meeting Location with Context

```php
public function sendMeetingDetails(string $phone, Meeting $meeting): void
{
    $message = "*Meeting: {$meeting->title}*\n\n";
    $message .= "Date: {$meeting->date->format('M d, Y')}\n";
    $message .= "Time: {$meeting->time->format('H:i')}\n";
    $message .= "Duration: {$meeting->duration} minutes\n\n";
    $message .= "Location details below:";
    
    // Send text first
    EvolutionApi::for('my-instance')
        ->messages()
        ->text($phone, $message);
    
    // Send location
    EvolutionApi::for('my-instance')
        ->messages()
        ->location(
            number: $phone,
            latitude: $meeting->latitude,
            longitude: $meeting->longitude,
            name: $meeting->location_name,
            address: $meeting->address
        );
    
    // Send organizer contact
    EvolutionApi::for('my-instance')
        ->messages()
        ->sendContact([
            'number' => $phone,
            'contact' => [
                [
                    'fullName' => $meeting->organizer->name,
                    'phoneNumber' => $meeting->organizer->phone,
                    'email' => $meeting->organizer->email,
                ],
            ],
        ]);
}
```

---

## Next Steps

- [Reactions & Status](reactions-status.md) - Message reactions and status updates
- [Text Messages](text-messages.md) - Basic text messaging
- [Interactive Messages](interactive.md) - Buttons and lists
