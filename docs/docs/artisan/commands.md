# Artisan Commands

The package provides several Artisan commands for managing your Evolution API integration.

## Available Commands

| Command | Description |
|---------|-------------|
| `evolution-api:install` | Install and configure the package |
| `evolution-api:health` | Check API server health |
| `evolution-api:instances` | Manage WhatsApp instances |
| `evolution-api:prune` | Clean up old data |
| `evolution-api:retry` | Retry failed messages |

## Installation Command

Install and configure the Evolution API package.

```bash
php artisan evolution-api:install
```

### Options

| Option | Description |
|--------|-------------|
| `--force` | Overwrite existing configuration files |

### What It Does

1. **Publishes configuration** - Copies `config/evolution-api.php` to your app
2. **Publishes migrations** - Copies database migrations
3. **Runs migrations** - Optionally runs `php artisan migrate`
4. **Configures environment** - Prompts for `.env` variables

### Interactive Setup

The command will prompt you for:

```
Enter your Evolution API server URL [http://localhost:8080]:
> https://api.example.com

Enter your Evolution API key (leave blank to skip):
> your-api-key

Enter your default instance name [default]:
> main-instance
```

### Example

```bash
# Fresh install
php artisan evolution-api:install

# Force overwrite existing files
php artisan evolution-api:install --force
```

---

## Health Check Command

Check Evolution API server connectivity and list instances.

```bash
php artisan evolution-api:health
```

### Options

| Option | Description |
|--------|-------------|
| `--connection=` | Specific connection to check (for multi-tenant) |

### Output Example

```
Checking Evolution API health...

Using connection: default
Checking server connectivity...
  Server URL: https://api.example.com
  Status: Connected
  Response time: 145ms

Fetching instances...

Instances:
+---------------+-----------+------------------+
| Instance      | Status    | Owner            |
+---------------+-----------+------------------+
| main-instance | Connected | 5511999999999    |
| backup        | Closed    | -                |
+---------------+-----------+------------------+

Health check completed successfully!
```

### Exit Codes

| Code | Description |
|------|-------------|
| `0` | Health check passed |
| `1` | Health check failed (connection error, etc.) |

---

## Instance Management Command

Manage and display Evolution API instance statuses.

```bash
php artisan evolution-api:instances {action} {instance?}
```

### Actions

| Action | Description |
|--------|-------------|
| `list` | List all instances (default) |
| `sync` | Sync instances from API to database |
| `connect` | Connect/reconnect an instance |
| `disconnect` | Disconnect an instance |

### Options

| Option | Description |
|--------|-------------|
| `--connection=` | Specific connection to use |

### List Instances

```bash
php artisan evolution-api:instances
# or
php artisan evolution-api:instances list
```

Output:
```
Fetching instances from Evolution API...

+---------------+-----------+------------------+---------------+
| Instance      | Status    | Owner            | Profile       |
+---------------+-----------+------------------+---------------+
| main-instance | open      | 5511999999999    | Company Name  |
| support       | qrcode    | -                | -             |
+---------------+-----------+------------------+---------------+
```

### Sync Instances

Synchronize instances from the API to your local database:

```bash
php artisan evolution-api:instances sync
```

Output:
```
Syncing instances from Evolution API to database...
  Synced: main-instance (open)
  Synced: support (qrcode)

Synced 2 instance(s) to database.
```

### Connect Instance

Connect or reconnect a WhatsApp instance:

```bash
php artisan evolution-api:instances connect main-instance
```

If QR code is needed:
```
Connecting instance: main-instance...

QR Code generated. Scan to connect:
data:image/png;base64,iVBORw0KGgo...

Pairing Code: ABC-123-DEF
```

### Disconnect Instance

Disconnect a WhatsApp instance:

```bash
php artisan evolution-api:instances disconnect main-instance
```

Output:
```
Are you sure you want to disconnect instance 'main-instance'? (yes/no) [no]:
> yes

Disconnecting instance: main-instance...
Instance disconnected successfully!
```

---

## Prune Data Command

Clean up old data from Evolution API database tables.

```bash
php artisan evolution-api:prune
```

### Options

| Option | Description |
|--------|-------------|
| `--days=30` | Number of days to keep data (default: 30) |
| `--messages` | Only prune old messages |
| `--webhooks` | Only prune old webhook logs |
| `--all` | Prune all data types |
| `--dry-run` | Show what would be deleted without deleting |

### Examples

```bash
# Prune all data older than 30 days
php artisan evolution-api:prune

# Prune data older than 7 days
php artisan evolution-api:prune --days=7

# Only prune messages
php artisan evolution-api:prune --messages

# Only prune webhook logs
php artisan evolution-api:prune --webhooks

# Preview what would be deleted
php artisan evolution-api:prune --dry-run
```

### Output Example

```
Pruning data older than 30 days...

Pruning old messages...
  Deleted 1,234 messages.
Pruning old webhook logs...
  Deleted 5,678 webhook logs.

Deleted 6,912 total records.
```

### Dry Run Mode

```bash
php artisan evolution-api:prune --dry-run --days=7
```

Output:
```
Pruning data older than 7 days...
Dry run mode - no data will be deleted.

Pruning old messages...
  Would delete 456 messages.
Pruning old webhook logs...
  Would delete 1,234 webhook logs.

Would delete 1,690 total records.
```

### Scheduling

Add to your scheduler for automatic cleanup:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Prune data older than 30 days, daily at midnight
    $schedule->command('evolution-api:prune --days=30')
        ->daily()
        ->at('00:00');
}
```

---

## Retry Failed Messages Command

Retry sending failed messages.

```bash
php artisan evolution-api:retry
```

### Options

| Option | Description |
|--------|-------------|
| `--instance=` | Specific instance to retry messages for |
| `--max-retries=3` | Maximum retry attempts (default: 3) |
| `--limit=100` | Maximum messages to retry (default: 100) |
| `--dry-run` | Show what would be retried without retrying |

### Examples

```bash
# Retry all failed messages
php artisan evolution-api:retry

# Retry for specific instance
php artisan evolution-api:retry --instance=main-instance

# Retry with custom limits
php artisan evolution-api:retry --max-retries=5 --limit=50

# Preview what would be retried
php artisan evolution-api:retry --dry-run
```

### Output Example

```
Finding failed messages to retry...

Found 3 message(s) to retry.

Retrying message: MSG_abc123
  Instance: main-instance
  Recipient: 5511999999999@s.whatsapp.net
  Type: text
  Retry count: 1
  Success!

Retrying message: MSG_def456
  Instance: main-instance
  Recipient: 5511888888888@s.whatsapp.net
  Type: image
  Retry count: 2
  Failed: Connection timeout

Retrying message: MSG_ghi789
  Instance: support
  Recipient: 5511777777777@s.whatsapp.net
  Type: text
  Retry count: 1
  Success!

Completed: 2 succeeded, 1 failed.
```

### Scheduling

Add to your scheduler for automatic retries:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Retry failed messages every 15 minutes
    $schedule->command('evolution-api:retry --limit=50')
        ->everyFifteenMinutes();
}
```

## Command Reference

### All Commands

```bash
# Installation
php artisan evolution-api:install [--force]

# Health check
php artisan evolution-api:health [--connection=]

# Instance management
php artisan evolution-api:instances [action] [instance] [--connection=]

# Data pruning
php artisan evolution-api:prune [--days=] [--messages] [--webhooks] [--all] [--dry-run]

# Retry failed messages
php artisan evolution-api:retry [--instance=] [--max-retries=] [--limit=] [--dry-run]
```

### Recommended Scheduler Setup

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Daily cleanup
    $schedule->command('evolution-api:prune --days=30')
        ->daily()
        ->at('00:00');

    // Retry failed messages
    $schedule->command('evolution-api:retry --limit=100')
        ->everyFifteenMinutes();

    // Sync instances hourly
    $schedule->command('evolution-api:instances sync')
        ->hourly();

    // Health check (for monitoring)
    $schedule->command('evolution-api:health')
        ->everyFiveMinutes()
        ->onFailure(function () {
            // Alert on failure
        });
}
```
