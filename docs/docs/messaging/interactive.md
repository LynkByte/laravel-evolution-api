---
title: Interactive Messages
description: Sending buttons, lists, and polls with Laravel Evolution API
---

# Interactive Messages

Send interactive messages including buttons, lists, and polls to engage users.

## Button Messages

### Basic Buttons

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendButtons(
        number: '5511999999999',
        title: 'Choose an option',
        description: 'Please select one of the options below',
        buttons: [
            ['buttonId' => 'opt1', 'buttonText' => 'Option 1'],
            ['buttonId' => 'opt2', 'buttonText' => 'Option 2'],
            ['buttonId' => 'opt3', 'buttonText' => 'Option 3'],
        ],
        footer: 'Tap a button to continue'
    );
```

### Button Limits

- Maximum 3 buttons per message
- Button text: max 20 characters
- Button ID: used to identify which button was pressed

## List Messages

### Basic List

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendListMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendList([
        'number' => '5511999999999',
        'title' => 'Our Menu',
        'description' => 'Choose from our delicious options',
        'buttonText' => 'View Menu',
        'footerText' => 'Tap to select an item',
        'sections' => [
            [
                'title' => 'Main Dishes',
                'rows' => [
                    [
                        'title' => 'Burger',
                        'description' => 'Classic beef burger - $12.99',
                        'rowId' => 'burger',
                    ],
                    [
                        'title' => 'Pizza',
                        'description' => 'Margherita pizza - $14.99',
                        'rowId' => 'pizza',
                    ],
                ],
            ],
            [
                'title' => 'Drinks',
                'rows' => [
                    [
                        'title' => 'Soda',
                        'description' => 'Coca-Cola, Sprite, Fanta - $2.99',
                        'rowId' => 'soda',
                    ],
                    [
                        'title' => 'Coffee',
                        'description' => 'Fresh brewed coffee - $3.99',
                        'rowId' => 'coffee',
                    ],
                ],
            ],
        ],
    ]);
```

### List Limits

- Maximum 10 sections
- Maximum 10 rows per section
- Row title: max 24 characters
- Row description: max 72 characters

## Poll Messages

### Single Choice Poll

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->poll(
        number: '5511999999999',
        name: 'What is your favorite color?',
        values: ['Red', 'Blue', 'Green', 'Yellow'],
        selectableCount: 1
    );
```

### Multiple Choice Poll

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->poll(
        number: '5511999999999',
        name: 'Select your interests (up to 3)',
        values: [
            'Sports',
            'Music',
            'Movies',
            'Technology',
            'Travel',
            'Food',
        ],
        selectableCount: 3
    );
```

### Using DTO

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendPollMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendPoll([
        'number' => '5511999999999',
        'name' => 'Rate our service',
        'values' => ['Excellent', 'Good', 'Average', 'Poor'],
        'selectableCount' => 1,
    ]);
```

### Poll Limits

- Maximum 12 options
- Option text: max 100 characters
- Poll question: max 256 characters

## Template Messages

For WhatsApp Business API approved templates:

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
                'type' => 'header',
                'parameters' => [
                    ['type' => 'image', 'image' => ['link' => 'https://example.com/logo.png']],
                ],
            ],
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => 'John'],
                    ['type' => 'text', 'text' => '#12345'],
                    ['type' => 'text', 'text' => '$99.99'],
                ],
            ],
            [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => 0,
                'parameters' => [
                    ['type' => 'text', 'text' => '12345'],
                ],
            ],
        ],
    ]);
```

## Handling Responses

Button and list selections come as webhook events:

```php
use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;
use Lynkbyte\EvolutionApi\DTOs\WebhookPayloadDto;

class InteractiveResponseHandler extends AbstractWebhookHandler
{
    protected array $events = ['MESSAGES_UPSERT'];

    public function handle(WebhookPayloadDto $payload): void
    {
        $message = $payload->data['message'] ?? [];
        
        // Button response
        if (isset($message['buttonsResponseMessage'])) {
            $selectedId = $message['buttonsResponseMessage']['selectedButtonId'];
            $this->handleButtonResponse($payload->sender, $selectedId);
        }
        
        // List response
        if (isset($message['listResponseMessage'])) {
            $selectedId = $message['listResponseMessage']['singleSelectReply']['selectedRowId'];
            $this->handleListResponse($payload->sender, $selectedId);
        }
        
        // Poll vote
        if (isset($message['pollUpdateMessage'])) {
            $votes = $message['pollUpdateMessage']['vote'];
            $this->handlePollVote($payload->sender, $votes);
        }
    }
    
    private function handleButtonResponse(string $sender, string $buttonId): void
    {
        match ($buttonId) {
            'opt1' => $this->processOption1($sender),
            'opt2' => $this->processOption2($sender),
            'opt3' => $this->processOption3($sender),
            default => null,
        };
    }
}
```

## Examples

### Customer Service Menu

```php
public function sendServiceMenu(string $phone): void
{
    EvolutionApi::for('my-instance')
        ->messages()
        ->sendList([
            'number' => $phone,
            'title' => 'Customer Service',
            'description' => 'How can we help you today?',
            'buttonText' => 'Get Help',
            'footerText' => 'Available 24/7',
            'sections' => [
                [
                    'title' => 'Orders',
                    'rows' => [
                        ['title' => 'Track Order', 'description' => 'Check your order status', 'rowId' => 'track_order'],
                        ['title' => 'Cancel Order', 'description' => 'Request cancellation', 'rowId' => 'cancel_order'],
                        ['title' => 'Return Item', 'description' => 'Start a return', 'rowId' => 'return_item'],
                    ],
                ],
                [
                    'title' => 'Account',
                    'rows' => [
                        ['title' => 'Update Info', 'description' => 'Change your details', 'rowId' => 'update_info'],
                        ['title' => 'Payment Methods', 'description' => 'Manage payments', 'rowId' => 'payments'],
                    ],
                ],
                [
                    'title' => 'Support',
                    'rows' => [
                        ['title' => 'Talk to Agent', 'description' => 'Connect with support', 'rowId' => 'agent'],
                        ['title' => 'FAQ', 'description' => 'Common questions', 'rowId' => 'faq'],
                    ],
                ],
            ],
        ]);
}
```

### Confirmation Dialog

```php
public function sendConfirmation(string $phone, string $action): void
{
    EvolutionApi::for('my-instance')
        ->messages()
        ->sendButtons(
            number: $phone,
            title: 'Confirm Action',
            description: "Are you sure you want to {$action}?",
            buttons: [
                ['buttonId' => 'confirm_yes', 'buttonText' => 'Yes, proceed'],
                ['buttonId' => 'confirm_no', 'buttonText' => 'No, cancel'],
            ],
            footer: 'This action cannot be undone'
        );
}
```

### Feedback Survey

```php
public function sendFeedbackSurvey(string $phone, string $orderId): void
{
    EvolutionApi::for('my-instance')
        ->messages()
        ->poll(
            number: $phone,
            name: "How would you rate your experience with order #{$orderId}?",
            values: [
                'Excellent - Very satisfied',
                'Good - Satisfied', 
                'Average - Neutral',
                'Poor - Unsatisfied',
                'Very Poor - Very unsatisfied',
            ],
            selectableCount: 1
        );
}
```

---

## Next Steps

- [Location & Contacts](location-contact.md) - Send locations and contact cards
- [Reactions & Status](reactions-status.md) - Reactions and status updates
- [Webhooks](../webhooks/overview.md) - Handle interactive responses
