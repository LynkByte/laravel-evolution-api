---
title: Chats
description: Managing chats and contacts with Laravel Evolution API
---

# Chats

The Chat resource provides methods for managing chats, contacts, and retrieving message history.

## Accessing the Resource

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$chats = EvolutionApi::for('my-instance')->chats();
```

## Number Validation

### Check if Number is on WhatsApp

```php
// Single number
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->isOnWhatsApp('5511999999999');

if ($response->isSuccessful()) {
    $result = $response->json()[0];
    $exists = $result['exists'] ?? false;
    $jid = $result['jid'] ?? null;
}

// Multiple numbers
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->checkNumber(['5511999999999', '5511888888888', '5511777777777']);

foreach ($response->json() as $result) {
    echo "{$result['number']}: " . ($result['exists'] ? 'Yes' : 'No');
}
```

## Fetching Chats

### Get All Chats

```php
$response = EvolutionApi::for('my-instance')->chats()->findAll();

foreach ($response->json() as $chat) {
    echo "Chat: {$chat['id']}\n";
    echo "Name: {$chat['name']}\n";
    echo "Unread: {$chat['unreadCount']}\n";
}
```

### Get Chats with Pagination

```php
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->findPaginated(page: 1, limit: 20);
```

### Find Specific Chat

```php
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->find('5511999999999@s.whatsapp.net');

$chat = $response->json();
```

## Contacts

### Get All Contacts

```php
$response = EvolutionApi::for('my-instance')->chats()->findContacts();

foreach ($response->json() as $contact) {
    echo "Name: {$contact['pushName']}\n";
    echo "Number: {$contact['id']}\n";
}
```

### Search Contacts

```php
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->searchContacts('John');

foreach ($response->json() as $contact) {
    echo $contact['pushName'];
}
```

### Update Contact Name

```php
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->updateContactName(
        remoteJid: '5511999999999@s.whatsapp.net',
        name: 'John Doe - VIP Customer'
    );
```

## Messages

### Get Chat Messages

```php
// Basic fetch
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->findMessages('5511999999999@s.whatsapp.net', limit: 50);

// With pagination cursor
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->findMessages(
        remoteJid: '5511999999999@s.whatsapp.net',
        limit: 20,
        cursor: 'cursor-from-previous-request'
    );
```

### Get All Messages (Filtered)

```php
// All messages with filter
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->findAllMessages(
        where: [
            'key' => [
                'fromMe' => true,
            ],
        ],
        limit: 100
    );
```

### Get Messages by Date Range

```php
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->findMessagesByDate(
        remoteJid: '5511999999999@s.whatsapp.net',
        startTimestamp: strtotime('-7 days'),
        endTimestamp: time(),
        limit: 200
    );
```

### Get Status Messages (Stories)

```php
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->findStatusMessages();
```

## Chat Operations

### Archive/Unarchive

```php
// Archive chat
EvolutionApi::for('my-instance')
    ->chats()
    ->archive('5511999999999@s.whatsapp.net');

// Unarchive chat
EvolutionApi::for('my-instance')
    ->chats()
    ->unarchive('5511999999999@s.whatsapp.net');
```

### Mark as Unread

```php
EvolutionApi::for('my-instance')
    ->chats()
    ->markChatUnread('5511999999999@s.whatsapp.net');
```

### Mute/Unmute

```php
// Mute indefinitely
EvolutionApi::for('my-instance')
    ->chats()
    ->mute('5511999999999@s.whatsapp.net');

// Mute for specific time (timestamp)
EvolutionApi::for('my-instance')
    ->chats()
    ->mute('5511999999999@s.whatsapp.net', strtotime('+24 hours'));

// Unmute
EvolutionApi::for('my-instance')
    ->chats()
    ->unmute('5511999999999@s.whatsapp.net');
```

### Pin/Unpin

```php
// Pin chat
EvolutionApi::for('my-instance')
    ->chats()
    ->pin('5511999999999@s.whatsapp.net');

// Unpin chat
EvolutionApi::for('my-instance')
    ->chats()
    ->unpin('5511999999999@s.whatsapp.net');
```

### Delete Chat

```php
// Delete chat
EvolutionApi::for('my-instance')
    ->chats()
    ->deleteChat('5511999999999@s.whatsapp.net');

// Clear all messages in chat (keep chat)
EvolutionApi::for('my-instance')
    ->chats()
    ->clearMessages('5511999999999@s.whatsapp.net');
```

## Blocking

### Block/Unblock Contacts

```php
// Block contact
EvolutionApi::for('my-instance')
    ->chats()
    ->block('5511999999999');

// Unblock contact
EvolutionApi::for('my-instance')
    ->chats()
    ->unblock('5511999999999');
```

## Profile Information

### Get Profile Picture

```php
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->fetchProfilePicture('5511999999999');

$pictureUrl = $response->json('profilePictureUrl');
```

### Get Business Profile

```php
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->getBusinessProfile('5511999999999');

$business = $response->json();
echo "Business Name: {$business['name']}";
echo "Description: {$business['description']}";
echo "Category: {$business['category']}";
```

## Presence

### Get Contact Presence

```php
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->getPresence('5511999999999');

$presence = $response->json();
// 'available', 'unavailable', 'composing', 'recording'
```

## Labels

### Get Labels

```php
$response = EvolutionApi::for('my-instance')
    ->chats()
    ->findLabels();

foreach ($response->json() as $label) {
    echo "Label: {$label['name']} ({$label['color']})";
}
```

## Method Reference

| Method | Description |
|--------|-------------|
| `checkNumber($numbers)` | Check if numbers are on WhatsApp |
| `isOnWhatsApp($number)` | Check single number |
| `findAll()` | Get all chats |
| `findPaginated($page, $limit)` | Get chats with pagination |
| `find($remoteJid)` | Find specific chat |
| `findContacts()` | Get all contacts |
| `searchContacts($query)` | Search contacts by name |
| `updateContactName($remoteJid, $name)` | Update contact name |
| `findMessages($remoteJid, $limit, $cursor)` | Get chat messages |
| `findAllMessages($where, $limit)` | Get filtered messages |
| `findMessagesByDate($remoteJid, $start, $end)` | Get messages in date range |
| `findStatusMessages()` | Get status/stories |
| `findLabels()` | Get all labels |
| `archive($remoteJid)` | Archive chat |
| `unarchive($remoteJid)` | Unarchive chat |
| `markChatUnread($remoteJid)` | Mark as unread |
| `mute($remoteJid, $expiration)` | Mute chat |
| `unmute($remoteJid)` | Unmute chat |
| `pin($remoteJid)` | Pin chat |
| `unpin($remoteJid)` | Unpin chat |
| `deleteChat($remoteJid)` | Delete chat |
| `clearMessages($remoteJid)` | Clear chat messages |
| `block($number)` | Block contact |
| `unblock($number)` | Unblock contact |
| `fetchProfilePicture($number)` | Get profile picture URL |
| `getBusinessProfile($number)` | Get business profile |
| `getPresence($number)` | Get online presence |

## Examples

### Validate Numbers Before Sending

```php
public function sendToValidNumbers(array $numbers, string $message): array
{
    $response = EvolutionApi::for('my-instance')
        ->chats()
        ->checkNumber($numbers);
    
    $results = [];
    
    foreach ($response->json() as $result) {
        if ($result['exists']) {
            $sendResponse = EvolutionApi::for('my-instance')
                ->messages()
                ->text($result['number'], $message);
            
            $results[$result['number']] = $sendResponse->isSuccessful();
        } else {
            $results[$result['number']] = false;
        }
    }
    
    return $results;
}
```

### Export Chat History

```php
public function exportChatHistory(string $remoteJid): array
{
    $allMessages = [];
    $cursor = null;
    
    do {
        $response = EvolutionApi::for('my-instance')
            ->chats()
            ->findMessages($remoteJid, limit: 100, cursor: $cursor);
        
        $data = $response->json();
        $messages = $data['messages'] ?? [];
        $cursor = $data['cursor'] ?? null;
        
        $allMessages = array_merge($allMessages, $messages);
        
    } while ($cursor !== null && count($messages) > 0);
    
    return $allMessages;
}
```

---

## Next Steps

- [Groups](groups.md) - Group management
- [Messages](messages.md) - Sending messages
- [Profiles](profiles.md) - Profile management
