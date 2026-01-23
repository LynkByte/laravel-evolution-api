---
title: Groups
description: Managing WhatsApp groups with Laravel Evolution API
---

# Groups

The Group resource provides comprehensive methods for creating and managing WhatsApp groups.

## Accessing the Resource

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$groups = EvolutionApi::for('my-instance')->groups();
```

## Creating Groups

### Basic Creation

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->create(
        subject: 'Project Team',
        participants: ['5511999999999', '5511888888888']
    );

if ($response->isSuccessful()) {
    $groupJid = $response->json('groupJid');
    echo "Group created: {$groupJid}";
}
```

### With Description

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->create(
        subject: 'Project Team',
        participants: ['5511999999999', '5511888888888'],
        description: 'A group for our project discussions'
    );
```

## Fetching Groups

### Get All Groups

```php
// Without participants
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->fetchAll();

// With participants
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->fetchAll(getParticipants: true);

foreach ($response->json() as $group) {
    echo "Group: {$group['subject']}\n";
    echo "JID: {$group['id']}\n";
}
```

### Get Single Group

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->fetchOne('123456789@g.us');

$group = $response->json();
echo "Name: {$group['subject']}";
echo "Description: {$group['desc']}";
echo "Participants: " . count($group['participants']);
```

### Get Group Participants

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->participants('123456789@g.us');

foreach ($response->json() as $participant) {
    echo "{$participant['id']}: {$participant['admin'] ?? 'member'}";
}
```

## Updating Groups

### Update Name (Subject)

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->updateSubject('123456789@g.us', 'New Group Name');
```

### Update Description

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->updateDescription('123456789@g.us', 'Updated group description');
```

### Update Picture

```php
// From URL
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->updatePicture('123456789@g.us', 'https://example.com/image.jpg');

// From base64
$imageBase64 = base64_encode(file_get_contents('group-image.jpg'));
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->updatePicture('123456789@g.us', "data:image/jpeg;base64,{$imageBase64}");
```

### Remove Picture

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->removePicture('123456789@g.us');
```

## Managing Participants

### Add Participants

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->addParticipants('123456789@g.us', [
        '5511999999999',
        '5511888888888',
    ]);
```

### Remove Participants

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->removeParticipants('123456789@g.us', [
        '5511777777777',
    ]);
```

### Promote to Admin

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->promoteToAdmin('123456789@g.us', [
        '5511999999999',
    ]);
```

### Demote from Admin

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->demoteFromAdmin('123456789@g.us', [
        '5511999999999',
    ]);
```

## Group Invitations

### Get Invite Code

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->inviteCode('123456789@g.us');

$code = $response->json('inviteCode');
$link = "https://chat.whatsapp.com/{$code}";
```

### Revoke Invite Code

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->revokeInviteCode('123456789@g.us');

$newCode = $response->json('inviteCode');
```

### Accept Invite

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->acceptInvite('AbCdEfGhIjK'); // Invite code
```

### Get Invite Info

Get group information from invite code without joining:

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->inviteInfo('AbCdEfGhIjK');

$groupInfo = $response->json();
echo "Group: {$groupInfo['subject']}";
echo "Participants: {$groupInfo['size']}";
```

### Send Invite to Users

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->sendInvite(
        groupJid: '123456789@g.us',
        numbers: ['5511999999999', '5511888888888'],
        description: 'Join our team group!'
    );
```

## Group Settings

### Update Settings

```php
// Only admins can send messages (announcement mode)
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->updateSettings('123456789@g.us', 'announce');

// Everyone can send messages
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->updateSettings('123456789@g.us', 'not_announce');

// Only admins can edit group info
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->updateSettings('123456789@g.us', 'locked');

// Everyone can edit group info
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->updateSettings('123456789@g.us', 'unlocked');
```

### Helper Methods

```php
// Set announcement mode
EvolutionApi::for('my-instance')
    ->groups()
    ->setAnnouncementMode('123456789@g.us', enabled: true);

// Set locked mode
EvolutionApi::for('my-instance')
    ->groups()
    ->setLockedMode('123456789@g.us', enabled: true);
```

### Disappearing Messages

```php
// Enable (24 hours)
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->toggleEphemeral('123456789@g.us', 86400);

// Enable (7 days)
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->toggleEphemeral('123456789@g.us', 604800);

// Enable (90 days)
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->toggleEphemeral('123456789@g.us', 7776000);

// Disable
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->toggleEphemeral('123456789@g.us', 0);
```

## Join Requests

### Get Pending Requests

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->pendingParticipants('123456789@g.us');

foreach ($response->json() as $request) {
    echo "Request from: {$request['id']}";
}
```

### Accept Requests

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->acceptJoinRequests('123456789@g.us', [
        '5511999999999@s.whatsapp.net',
    ]);
```

### Reject Requests

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->rejectJoinRequests('123456789@g.us', [
        '5511888888888@s.whatsapp.net',
    ]);
```

## Other Operations

### Check Admin Status

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->isAdmin('123456789@g.us');

$isAdmin = $response->json('isAdmin');
```

### Leave Group

```php
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->leave('123456789@g.us');
```

## Method Reference

| Method | Description |
|--------|-------------|
| `create($subject, $participants, $description)` | Create new group |
| `fetchAll($getParticipants)` | Get all groups |
| `fetchOne($groupJid)` | Get single group |
| `participants($groupJid)` | Get group participants |
| `updateSubject($groupJid, $subject)` | Update group name |
| `updateDescription($groupJid, $description)` | Update description |
| `updatePicture($groupJid, $image)` | Update group picture |
| `removePicture($groupJid)` | Remove group picture |
| `addParticipants($groupJid, $participants)` | Add members |
| `removeParticipants($groupJid, $participants)` | Remove members |
| `promoteToAdmin($groupJid, $participants)` | Promote to admin |
| `demoteFromAdmin($groupJid, $participants)` | Demote from admin |
| `inviteCode($groupJid)` | Get invite code |
| `revokeInviteCode($groupJid)` | Revoke invite code |
| `acceptInvite($inviteCode)` | Join via invite code |
| `inviteInfo($inviteCode)` | Get invite info |
| `sendInvite($groupJid, $numbers, $description)` | Send invites |
| `updateSettings($groupJid, $action)` | Update settings |
| `setAnnouncementMode($groupJid, $enabled)` | Set announcement mode |
| `setLockedMode($groupJid, $enabled)` | Set locked mode |
| `toggleEphemeral($groupJid, $expiration)` | Set disappearing messages |
| `pendingParticipants($groupJid)` | Get join requests |
| `acceptJoinRequests($groupJid, $participants)` | Accept requests |
| `rejectJoinRequests($groupJid, $participants)` | Reject requests |
| `isAdmin($groupJid)` | Check admin status |
| `leave($groupJid)` | Leave group |

## Examples

### Create and Configure Group

```php
// Create group
$response = EvolutionApi::for('my-instance')
    ->groups()
    ->create('Support Team', ['5511999999999']);

$groupJid = $response->json('groupJid');

// Update description
EvolutionApi::for('my-instance')
    ->groups()
    ->updateDescription($groupJid, 'Official support channel');

// Set announcement mode (only admins can send)
EvolutionApi::for('my-instance')
    ->groups()
    ->setAnnouncementMode($groupJid, true);

// Enable 7-day disappearing messages
EvolutionApi::for('my-instance')
    ->groups()
    ->toggleEphemeral($groupJid, 604800);

// Get invite link
$inviteResponse = EvolutionApi::for('my-instance')
    ->groups()
    ->inviteCode($groupJid);

$inviteLink = "https://chat.whatsapp.com/" . $inviteResponse->json('inviteCode');
```

### Bulk Add Participants

```php
public function addMembersToGroup(string $groupJid, array $numbers): array
{
    // Validate numbers first
    $validatedResponse = EvolutionApi::for('my-instance')
        ->chats()
        ->checkNumber($numbers);
    
    $validNumbers = collect($validatedResponse->json())
        ->filter(fn($n) => $n['exists'])
        ->pluck('number')
        ->toArray();
    
    // Add in batches of 10
    $results = [];
    foreach (array_chunk($validNumbers, 10) as $batch) {
        $response = EvolutionApi::for('my-instance')
            ->groups()
            ->addParticipants($groupJid, $batch);
        
        $results[] = $response->json();
        
        // Rate limit
        sleep(1);
    }
    
    return $results;
}
```

---

## Next Steps

- [Messages](messages.md) - Send messages to groups
- [Profiles](profiles.md) - Manage profiles
- [Settings](settings.md) - Instance settings
