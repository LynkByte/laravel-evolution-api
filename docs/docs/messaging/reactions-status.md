---
title: Reactions & Status
description: Message reactions and status updates with Laravel Evolution API
---

# Reactions & Status Messages

Add emoji reactions to messages and post status/story updates.

## Message Reactions

### Add Reaction

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->react(
        remoteJid: '5511999999999@s.whatsapp.net',
        messageId: 'ABCD1234567890',
        reaction: '👍',
        fromMe: false  // Was the message sent by us?
    );
```

### Using DTO

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendReactionMessageDto;

// Add reaction
$dto = SendReactionMessageDto::react(
    remoteJid: '5511999999999@s.whatsapp.net',
    messageId: 'ABCD1234567890',
    fromMe: false,
    reaction: '❤️'
);

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendReaction($dto);
```

### Remove Reaction

```php
// Simple helper
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->unreact(
        remoteJid: '5511999999999@s.whatsapp.net',
        messageId: 'ABCD1234567890',
        fromMe: false
    );

// Using DTO
$dto = SendReactionMessageDto::remove(
    remoteJid: '5511999999999@s.whatsapp.net',
    messageId: 'ABCD1234567890',
    fromMe: false
);

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendReaction($dto);
```

### Common Reactions

| Emoji | Meaning |
|-------|---------|
| 👍 | Like / Thumbs up |
| ❤️ | Love |
| 😂 | Laugh |
| 😮 | Wow / Surprised |
| 😢 | Sad |
| 🙏 | Thanks / Prayer |

### React to Your Own Message

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->react(
        remoteJid: '5511999999999@s.whatsapp.net',
        messageId: 'my-message-id',
        reaction: '✅',
        fromMe: true  // Message was sent by us
    );
```

## Status/Story Messages

### Text Status

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendStatusMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendStatus([
        'type' => 'text',
        'content' => 'Hello from my status!',
        'backgroundColor' => '#25D366',
        'font' => 1,
        'allContacts' => true,
    ]);
```

### Image Status

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendStatus([
        'type' => 'image',
        'content' => 'https://example.com/image.jpg',
        'caption' => 'Check this out!',
        'allContacts' => true,
    ]);
```

### Video Status

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendStatus([
        'type' => 'video',
        'content' => 'https://example.com/video.mp4',
        'caption' => 'New video!',
        'allContacts' => true,
    ]);
```

### Status to Specific Contacts

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendStatus([
        'type' => 'text',
        'content' => 'Private status update',
        'backgroundColor' => '#128C7E',
        'allContacts' => false,
        'statusJidList' => [
            '5511999999999@s.whatsapp.net',
            '5511888888888@s.whatsapp.net',
        ],
    ]);
```

### Status Options

| Option | Type | Description |
|--------|------|-------------|
| `type` | string | `text`, `image`, `video`, `audio` |
| `content` | string | Text or media URL |
| `caption` | string | Caption for media |
| `backgroundColor` | string | Hex color for text status |
| `font` | int | Font style (1-5) |
| `allContacts` | bool | Share with all contacts |
| `statusJidList` | array | Specific contacts (when allContacts=false) |

### Background Colors

Common WhatsApp status colors:

| Color | Hex Code |
|-------|----------|
| Green (WhatsApp) | #25D366 |
| Teal | #128C7E |
| Dark Teal | #075E54 |
| Blue | #34B7F1 |
| Red | #E74C3C |
| Purple | #9B59B6 |

### Font Styles

| Font ID | Style |
|---------|-------|
| 1 | Sans Serif |
| 2 | Serif |
| 3 | Norican Script |
| 4 | Bryndan Write |
| 5 | Bebasneue |

## Stickers

### Send Sticker

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sticker('5511999999999', 'https://example.com/sticker.webp');
```

### Using DTO

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendStickerMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendSticker([
        'number' => '5511999999999',
        'sticker' => 'https://example.com/sticker.webp',
    ]);
```

### Sticker Requirements

- Format: WebP
- Recommended size: 512x512 pixels
- Max file size: 100KB (static), 500KB (animated)
- Animated stickers must be WebP format

## Examples

### Auto-React to Messages

```php
use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;
use Lynkbyte\EvolutionApi\DTOs\WebhookPayloadDto;

class AutoReactHandler extends AbstractWebhookHandler
{
    protected array $events = ['MESSAGES_UPSERT'];

    public function handle(WebhookPayloadDto $payload): void
    {
        $message = $payload->data['message'] ?? [];
        $key = $payload->data['key'] ?? [];
        
        // Don't react to our own messages
        if ($key['fromMe'] ?? false) {
            return;
        }
        
        $text = $message['conversation'] ?? '';
        
        // React based on keywords
        $reaction = $this->getReaction($text);
        
        if ($reaction) {
            EvolutionApi::for($payload->instance)
                ->messages()
                ->react(
                    remoteJid: $key['remoteJid'],
                    messageId: $key['id'],
                    reaction: $reaction,
                    fromMe: false
                );
        }
    }
    
    private function getReaction(string $text): ?string
    {
        $text = strtolower($text);
        
        return match (true) {
            str_contains($text, 'thank') => '🙏',
            str_contains($text, 'love') => '❤️',
            str_contains($text, 'haha') => '😂',
            str_contains($text, 'wow') => '😮',
            str_contains($text, 'great') => '👍',
            str_contains($text, 'help') => '🆘',
            default => null,
        };
    }
}
```

### Daily Status Update

```php
public function postDailyStatus(): void
{
    $quote = DailyQuote::today();
    
    EvolutionApi::for('my-instance')
        ->messages()
        ->sendStatus([
            'type' => 'text',
            'content' => "\"{$quote->text}\"\n\n- {$quote->author}",
            'backgroundColor' => '#128C7E',
            'font' => 3,
            'allContacts' => true,
        ]);
}
```

### Promotional Status

```php
public function postPromoStatus(Promotion $promo): void
{
    EvolutionApi::for('my-instance')
        ->messages()
        ->sendStatus([
            'type' => 'image',
            'content' => $promo->banner_url,
            'caption' => "*{$promo->title}*\n\n" .
                        "{$promo->description}\n\n" .
                        "Valid until: {$promo->ends_at->format('M d')}\n" .
                        "Use code: {$promo->code}",
            'allContacts' => true,
        ]);
}
```

### Confirm Receipt with Reaction

```php
public function confirmOrderReceived(string $remoteJid, string $messageId): void
{
    // React to the order message
    EvolutionApi::for('my-instance')
        ->messages()
        ->react(
            remoteJid: $remoteJid,
            messageId: $messageId,
            reaction: '✅',
            fromMe: false
        );
    
    // Send confirmation text
    EvolutionApi::for('my-instance')
        ->messages()
        ->text(
            str_replace('@s.whatsapp.net', '', $remoteJid),
            'Order received! We will process it shortly.'
        );
}
```

---

## Next Steps

- [Text Messages](text-messages.md) - Basic text messaging
- [Media Messages](media-messages.md) - Images, videos, documents
- [Webhooks](../webhooks/overview.md) - Handle incoming messages
