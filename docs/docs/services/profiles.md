---
title: Profiles
description: Managing WhatsApp profiles with Laravel Evolution API
---

# Profiles

The Profile resource allows you to manage your WhatsApp profile, including name, status, picture, and privacy settings.

## Accessing the Resource

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$profile = EvolutionApi::for('my-instance')->profile();
```

## Profile Information

### Get Full Profile

```php
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->fetch();

$profile = $response->json();
echo "Name: " . $profile['name'];
echo "Status: " . $profile['status'];
```

### Get Profile Name

```php
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->getName();
```

### Update Profile Name

```php
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->updateName('My Business Name');
```

## Status (About)

### Get Status

```php
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->getStatus();
```

### Update Status

```php
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->updateStatus('Available for support 9am-6pm');
```

## Profile Picture

### Get Picture

```php
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->getPicture();

$pictureUrl = $response->json('profilePictureUrl');
```

### Update Picture

```php
// From URL
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->updatePicture('https://example.com/profile.jpg');

// From base64
$imageBase64 = base64_encode(file_get_contents('profile.jpg'));
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->updatePicture("data:image/jpeg;base64,{$imageBase64}");
```

### Remove Picture

```php
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->removePicture();
```

## Privacy Settings

### Get Privacy Settings

```php
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->getPrivacySettings();

$settings = $response->json();
// Keys: readreceipts, profile, status, online, last, groupadd
```

### Update Privacy Settings

```php
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->updatePrivacySettings([
        'readreceipts' => 'all',
        'profile' => 'contacts',
        'status' => 'contacts',
        'online' => 'all',
        'last' => 'contacts',
        'groupadd' => 'contacts',
    ]);
```

### Privacy Setting Values

| Setting | Allowed Values |
|---------|---------------|
| `readreceipts` | `all`, `none` |
| `profile` | `all`, `contacts`, `contact_blacklist`, `none` |
| `status` | `all`, `contacts`, `contact_blacklist`, `none` |
| `online` | `all`, `match_last_seen` |
| `last` | `all`, `contacts`, `contact_blacklist`, `none` |
| `groupadd` | `all`, `contacts`, `contact_blacklist` |

### Individual Privacy Helpers

```php
// Read receipts
EvolutionApi::for('my-instance')
    ->profile()
    ->setReadReceiptsPrivacy('all');

// Profile picture visibility
EvolutionApi::for('my-instance')
    ->profile()
    ->setProfilePicturePrivacy('contacts');

// Status visibility
EvolutionApi::for('my-instance')
    ->profile()
    ->setStatusPrivacy('contacts');

// Online status visibility
EvolutionApi::for('my-instance')
    ->profile()
    ->setOnlinePrivacy('all');

// Last seen visibility
EvolutionApi::for('my-instance')
    ->profile()
    ->setLastSeenPrivacy('contacts');

// Who can add to groups
EvolutionApi::for('my-instance')
    ->profile()
    ->setGroupAddPrivacy('contacts');
```

## Business Profile

### Get Business Profile

```php
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->getBusinessProfile();

$business = $response->json();
```

### Update Business Profile

```php
$response = EvolutionApi::for('my-instance')
    ->profile()
    ->updateBusinessProfile([
        'name' => 'My Business',
        'description' => 'We provide excellent services',
        'category' => 'Business',
        'email' => 'contact@mybusiness.com',
        'website' => 'https://mybusiness.com',
        'address' => '123 Main St, City',
    ]);
```

## Method Reference

| Method | Description |
|--------|-------------|
| `fetch()` | Get full profile info |
| `getName()` | Get profile name |
| `updateName($name)` | Update profile name |
| `getStatus()` | Get status/about |
| `updateStatus($status)` | Update status/about |
| `getPicture()` | Get profile picture URL |
| `updatePicture($image)` | Update profile picture |
| `removePicture()` | Remove profile picture |
| `getPrivacySettings()` | Get all privacy settings |
| `updatePrivacySettings($settings)` | Update privacy settings |
| `getBusinessProfile()` | Get business profile |
| `updateBusinessProfile($data)` | Update business profile |

---

## Next Steps

- [Settings](settings.md) - Instance settings
- [Instances](instances.md) - Instance management
