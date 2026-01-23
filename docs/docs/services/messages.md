---
title: Messages
description: Sending messages with Laravel Evolution API
---

# Messages

The Message resource provides comprehensive methods for sending all types of WhatsApp messages.

## Accessing the Resource

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

// Set instance first
$messages = EvolutionApi::for('my-instance')->messages();

// Or include instance in each call
$messages = EvolutionApi::messages();
```

## Text Messages

### Simple Text

```php
// Quick helper method
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->text('5511999999999', 'Hello, World!');

// With delay (milliseconds)
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->text('5511999999999', 'Hello!', delay: 1000);
```

### Using DTO

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendTextMessageDto;

$dto = new SendTextMessageDto(
    number: '5511999999999',
    text: 'Hello from Laravel!',
    delay: 1000
);

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendText($dto);

// Or from array
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendText([
        'number' => '5511999999999',
        'text' => 'Hello!',
    ]);
```

### With Formatting

WhatsApp supports basic formatting:

```php
$text = "*Bold text*\n";
$text .= "_Italic text_\n";
$text .= "~Strikethrough~\n";
$text .= "```Monospace```\n";
$text .= "> Quote\n";
$text .= "• Bullet point";

EvolutionApi::for('my-instance')
    ->messages()
    ->text('5511999999999', $text);
```

## Media Messages

### Image

```php
// Simple helper
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->image(
        number: '5511999999999',
        media: 'https://example.com/image.jpg',
        caption: 'Check out this image!'
    );

// Using DTO
use Lynkbyte\EvolutionApi\DTOs\Message\SendMediaMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendMedia([
        'number' => '5511999999999',
        'mediatype' => 'image',
        'media' => 'https://example.com/image.jpg',
        'caption' => 'Image caption',
    ]);
```

### Video

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->video(
        number: '5511999999999',
        media: 'https://example.com/video.mp4',
        caption: 'Watch this video!'
    );
```

### Document

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->document(
        number: '5511999999999',
        media: 'https://example.com/document.pdf',
        caption: 'Important document',
        fileName: 'report.pdf',
        mimetype: 'application/pdf'
    );
```

### Base64 Media

```php
$imageData = base64_encode(file_get_contents('image.jpg'));

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendMedia([
        'number' => '5511999999999',
        'mediatype' => 'image',
        'media' => "data:image/jpeg;base64,{$imageData}",
        'caption' => 'Uploaded image',
    ]);
```

## Audio Messages

```php
// Simple helper
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->audio('5511999999999', 'https://example.com/audio.mp3');

// Using DTO
use Lynkbyte\EvolutionApi\DTOs\Message\SendAudioMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendAudio([
        'number' => '5511999999999',
        'audio' => 'https://example.com/audio.ogg',
        'delay' => 1000,
    ]);
```

## Location Messages

```php
// Simple helper
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->location(
        number: '5511999999999',
        latitude: -23.5505,
        longitude: -46.6333,
        name: 'São Paulo',
        address: 'São Paulo, Brazil'
    );

// Using DTO
use Lynkbyte\EvolutionApi\DTOs\Message\SendLocationMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendLocation([
        'number' => '5511999999999',
        'latitude' => -23.5505,
        'longitude' => -46.6333,
        'name' => 'Meeting Point',
        'address' => '123 Main St, São Paulo',
    ]);
```

## Contact Messages

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
                'organization' => 'Acme Inc',
                'email' => 'john@example.com',
            ],
        ],
    ]);
```

## Poll Messages

```php
// Simple helper
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->poll(
        number: '5511999999999',
        name: 'What is your favorite color?',
        values: ['Red', 'Blue', 'Green', 'Yellow'],
        selectableCount: 1  // Single choice
    );

// Multiple choice poll
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->poll(
        number: '5511999999999',
        name: 'Select your interests:',
        values: ['Sports', 'Music', 'Movies', 'Technology', 'Travel'],
        selectableCount: 3  // Can select up to 3
    );
```

## List Messages

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendListMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendList([
        'number' => '5511999999999',
        'title' => 'Our Services',
        'description' => 'Choose a service to learn more',
        'buttonText' => 'View Services',
        'footerText' => 'Tap to select',
        'sections' => [
            [
                'title' => 'Main Services',
                'rows' => [
                    [
                        'title' => 'Consulting',
                        'description' => 'Professional consulting services',
                        'rowId' => 'consulting',
                    ],
                    [
                        'title' => 'Development',
                        'description' => 'Custom software development',
                        'rowId' => 'development',
                    ],
                ],
            ],
            [
                'title' => 'Support',
                'rows' => [
                    [
                        'title' => 'Technical Support',
                        'description' => '24/7 technical assistance',
                        'rowId' => 'support',
                    ],
                ],
            ],
        ],
    ]);
```

## Button Messages

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendButtons(
        number: '5511999999999',
        title: 'Choose an option',
        description: 'Please select one of the options below',
        buttons: [
            ['buttonId' => 'yes', 'buttonText' => 'Yes, I agree'],
            ['buttonId' => 'no', 'buttonText' => 'No, thanks'],
            ['buttonId' => 'info', 'buttonText' => 'More info'],
        ],
        footer: 'Powered by Evolution API'
    );
```

## Reaction Messages

### Add Reaction

```php
// Simple helper
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->react(
        remoteJid: '5511999999999@s.whatsapp.net',
        messageId: 'ABC123',
        reaction: '👍',
        fromMe: false
    );

// Using DTO
use Lynkbyte\EvolutionApi\DTOs\Message\SendReactionMessageDto;

$dto = SendReactionMessageDto::react(
    remoteJid: '5511999999999@s.whatsapp.net',
    messageId: 'ABC123',
    fromMe: false,
    reaction: '❤️'
);

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendReaction($dto);
```

### Remove Reaction

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->unreact(
        remoteJid: '5511999999999@s.whatsapp.net',
        messageId: 'ABC123',
        fromMe: false
    );
```

## Sticker Messages

```php
// Simple helper
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sticker('5511999999999', 'https://example.com/sticker.webp');

// Using DTO
use Lynkbyte\EvolutionApi\DTOs\Message\SendStickerMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendSticker([
        'number' => '5511999999999',
        'sticker' => 'https://example.com/sticker.webp',
    ]);
```

## Status/Story Messages

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendStatusMessageDto;

// Text status
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendStatus([
        'type' => 'text',
        'content' => 'Hello from my status!',
        'backgroundColor' => '#25D366',
        'font' => 1,
        'allContacts' => true,
    ]);

// Image status
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendStatus([
        'type' => 'image',
        'content' => 'https://example.com/image.jpg',
        'caption' => 'Check this out!',
        'allContacts' => true,
    ]);

// Video status
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendStatus([
        'type' => 'video',
        'content' => 'https://example.com/video.mp4',
        'allContacts' => true,
    ]);
```

## Template Messages

For WhatsApp Business API:

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendTemplateMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendTemplate([
        'number' => '5511999999999',
        'name' => 'order_confirmation',
        'language' => 'en',
        'components' => [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => 'John'],
                    ['type' => 'text', 'text' => '#12345'],
                ],
            ],
        ],
    ]);
```

## Message Operations

### Reply to a Message

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->reply(
        number: '5511999999999',
        text: 'This is my reply',
        quotedMessageId: 'original-message-id'
    );
```

### Forward a Message

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->forward(
        number: '5511888888888',        // Destination
        messageId: 'ABC123',            // Message to forward
        remoteJid: '5511999999999@s.whatsapp.net'  // Original chat
    );
```

### Mark as Read

```php
// Single message
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->markAsRead('5511999999999@s.whatsapp.net', 'message-id');

// Multiple messages
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->markMultipleAsRead([
        ['remoteJid' => '5511999999999@s.whatsapp.net', 'id' => 'msg-1'],
        ['remoteJid' => '5511999999999@s.whatsapp.net', 'id' => 'msg-2'],
    ]);
```

### Delete Message

```php
// Delete for everyone
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->deleteMessage('5511999999999@s.whatsapp.net', 'message-id');

// Delete only for me
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->deleteForMe('5511999999999@s.whatsapp.net', 'message-id');
```

### Edit Message

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->updateMessage(
        remoteJid: '5511999999999@s.whatsapp.net',
        messageId: 'message-id',
        text: 'Updated message text'
    );
```

### Star/Unstar Message

```php
// Star
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->starMessage('5511999999999@s.whatsapp.net', 'message-id');

// Unstar
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->unstarMessage('5511999999999@s.whatsapp.net', 'message-id');
```

### Get Message by ID

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->getMessageById('5511999999999@s.whatsapp.net', 'message-id');
```

## Presence Indicators

### Show Typing

```php
// Show typing indicator
EvolutionApi::for('my-instance')->messages()->typing('5511999999999');

// Show recording indicator
EvolutionApi::for('my-instance')->messages()->recording('5511999999999');

// Stop presence indicator
EvolutionApi::for('my-instance')->messages()->stopPresence('5511999999999');
```

### Send with Typing Simulation

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendWithTyping(
        number: '5511999999999',
        text: 'Hello!',
        delay: 2000  // Show typing for 2 seconds before sending
    );
```

## Chat Operations

### Archive/Unarchive Chat

```php
// Archive
EvolutionApi::for('my-instance')
    ->messages()
    ->archiveChat('5511999999999@s.whatsapp.net');

// Unarchive
EvolutionApi::for('my-instance')
    ->messages()
    ->unarchiveChat('5511999999999@s.whatsapp.net');
```

## Method Reference

| Method | Description |
|--------|-------------|
| `sendText($dto)` | Send text message with DTO |
| `text($number, $text, $delay)` | Quick text message |
| `sendMedia($dto)` | Send media message with DTO |
| `image($number, $media, $caption)` | Quick image message |
| `video($number, $media, $caption)` | Quick video message |
| `document($number, $media, $caption, $fileName)` | Quick document message |
| `sendAudio($dto)` | Send audio message with DTO |
| `audio($number, $audio, $delay)` | Quick audio message |
| `sendLocation($dto)` | Send location message with DTO |
| `location($number, $lat, $lng, $name, $address)` | Quick location message |
| `sendContact($dto)` | Send contact message |
| `sendPoll($dto)` | Send poll message |
| `poll($number, $name, $values, $selectableCount)` | Quick poll message |
| `sendList($dto)` | Send list message |
| `sendButtons($number, $title, $desc, $buttons)` | Send button message |
| `sendReaction($dto)` | Send reaction |
| `react($remoteJid, $messageId, $reaction)` | Quick reaction |
| `unreact($remoteJid, $messageId)` | Remove reaction |
| `sendSticker($dto)` | Send sticker message |
| `sticker($number, $sticker)` | Quick sticker message |
| `sendStatus($dto)` | Send status/story |
| `sendTemplate($dto)` | Send template message |
| `reply($number, $text, $quotedMessageId)` | Reply to message |
| `forward($number, $messageId, $remoteJid)` | Forward message |
| `markAsRead($remoteJid, $messageId)` | Mark as read |
| `deleteMessage($remoteJid, $messageId)` | Delete message |
| `updateMessage($remoteJid, $messageId, $text)` | Edit message |
| `typing($number)` | Show typing indicator |
| `recording($number)` | Show recording indicator |

---

## Next Steps

- [Text Messages](../messaging/text-messages.md) - Detailed text message guide
- [Media Messages](../messaging/media-messages.md) - Working with media
- [Interactive Messages](../messaging/interactive.md) - Buttons and lists
