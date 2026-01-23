---
title: Text Messages
description: Sending text messages with Laravel Evolution API
---

# Text Messages

This guide covers all aspects of sending text messages with Laravel Evolution API.

## Basic Text Message

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->text('5511999999999', 'Hello, World!');
```

## Using DTOs

For more control, use the `SendTextMessageDto`:

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendTextMessageDto;

// Constructor
$dto = new SendTextMessageDto(
    number: '5511999999999',
    text: 'Hello from Laravel!',
    delay: 1000  // Optional delay in milliseconds
);

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendText($dto);

// From array
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendText([
        'number' => '5511999999999',
        'text' => 'Hello!',
        'delay' => 1000,
    ]);
```

## Text Formatting

WhatsApp supports rich text formatting:

```php
$text = "*Bold text*\n";
$text .= "_Italic text_\n";
$text .= "~Strikethrough text~\n";
$text .= "```Monospace text```\n";
$text .= "> Quoted text\n";
$text .= "• Bullet point\n";
$text .= "1. Numbered list";

EvolutionApi::for('my-instance')
    ->messages()
    ->text('5511999999999', $text);
```

### Formatting Reference

| Format | Syntax | Example |
|--------|--------|---------|
| Bold | `*text*` | **text** |
| Italic | `_text_` | *text* |
| Strikethrough | `~text~` | ~~text~~ |
| Monospace | ` ```text``` ` | `text` |
| Quote | `> text` | > text |

## Message with Delay

Add a delay before sending (simulates typing):

```php
// 2 second delay
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->text('5511999999999', 'Hello!', delay: 2000);
```

## Send with Typing Indicator

Show typing indicator before sending:

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendWithTyping(
        number: '5511999999999',
        text: 'Hello!',
        delay: 2000  // Show typing for 2 seconds
    );
```

## Reply to a Message

Quote/reply to a specific message:

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->reply(
        number: '5511999999999',
        text: 'This is my reply',
        quotedMessageId: 'ABCD1234567890'
    );
```

## Mentions

### Mention in Private Chat

```php
$text = "Hello @5511999999999!";

EvolutionApi::for('my-instance')
    ->messages()
    ->sendText([
        'number' => '5511999999999',
        'text' => $text,
        'options' => [
            'mentions' => [
                'everyOne' => false,
                'mentioned' => ['5511999999999'],
            ],
        ],
    ]);
```

### Mention Everyone in Group

```php
$text = "Attention everyone!";

EvolutionApi::for('my-instance')
    ->messages()
    ->sendText([
        'number' => '123456789@g.us',
        'text' => $text,
        'options' => [
            'mentions' => [
                'everyOne' => true,
            ],
        ],
    ]);
```

## Link Preview

WhatsApp automatically generates link previews:

```php
$text = "Check out this link: https://example.com";

EvolutionApi::for('my-instance')
    ->messages()
    ->text('5511999999999', $text);
```

To disable link preview:

```php
EvolutionApi::for('my-instance')
    ->messages()
    ->sendText([
        'number' => '5511999999999',
        'text' => 'Visit https://example.com',
        'options' => [
            'linkPreview' => false,
        ],
    ]);
```

## Phone Number Formats

The package accepts various phone number formats:

```php
// All these formats work
EvolutionApi::for('my-instance')->messages()->text('5511999999999', 'Hi');
EvolutionApi::for('my-instance')->messages()->text('+5511999999999', 'Hi');
EvolutionApi::for('my-instance')->messages()->text('55 11 99999-9999', 'Hi');
EvolutionApi::for('my-instance')->messages()->text('5511999999999@s.whatsapp.net', 'Hi');
```

## Sending to Groups

```php
// Use the group JID
EvolutionApi::for('my-instance')
    ->messages()
    ->text('123456789@g.us', 'Hello group!');
```

## Response Handling

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->text('5511999999999', 'Hello!');

if ($response->isSuccessful()) {
    $messageId = $response->json('key.id');
    $remoteJid = $response->json('key.remoteJid');
    $fromMe = $response->json('key.fromMe');
    $status = $response->json('status');
    
    echo "Message sent! ID: {$messageId}";
} else {
    $error = $response->json('message');
    echo "Failed: {$error}";
}
```

## Bulk Messaging

```php
$recipients = [
    '5511999999999',
    '5511888888888',
    '5511777777777',
];

$results = [];

foreach ($recipients as $number) {
    $response = EvolutionApi::for('my-instance')
        ->messages()
        ->text($number, 'Hello!');
    
    $results[$number] = $response->isSuccessful();
    
    // Add delay between messages to avoid rate limiting
    usleep(500000); // 500ms
}
```

## Using Queues for Bulk

For better performance, use queues:

```php
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;
use Lynkbyte\EvolutionApi\DTOs\Message\SendTextMessageDto;

$recipients = ['5511999999999', '5511888888888'];

foreach ($recipients as $number) {
    $dto = new SendTextMessageDto(
        number: $number,
        text: 'Hello from queue!'
    );
    
    SendMessageJob::dispatch('my-instance', $dto);
}
```

## Error Handling

```php
use Lynkbyte\EvolutionApi\Exceptions\ValidationException;
use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;

try {
    $response = EvolutionApi::for('my-instance')
        ->messages()
        ->text('invalid-number', 'Hello!');
} catch (ValidationException $e) {
    // Invalid number format
    echo "Validation error: " . $e->getMessage();
} catch (EvolutionApiException $e) {
    // Other API errors
    echo "API error: " . $e->getMessage();
}
```

## Templates for Common Messages

```php
class MessageTemplates
{
    public static function welcome(string $name): string
    {
        return "*Welcome, {$name}!*\n\n" .
               "Thank you for contacting us.\n" .
               "How can we help you today?";
    }
    
    public static function orderConfirmation(string $orderId, float $total): string
    {
        return "*Order Confirmed!*\n\n" .
               "Order: #{$orderId}\n" .
               "Total: $" . number_format($total, 2) . "\n\n" .
               "Thank you for your purchase!";
    }
}

// Usage
EvolutionApi::for('my-instance')
    ->messages()
    ->text($phone, MessageTemplates::welcome('John'));
```

---

## Next Steps

- [Media Messages](media-messages.md) - Send images, videos, documents
- [Audio Messages](audio-messages.md) - Send voice messages
- [Interactive Messages](interactive.md) - Buttons and lists
