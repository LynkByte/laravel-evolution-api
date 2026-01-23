---
title: Installation
description: How to install and set up the Laravel Evolution API package
---

# Installation

This guide walks you through installing and setting up the Laravel Evolution API package in your Laravel application.

!!! warning "Unofficial WhatsApp Integration"
    This package uses **Evolution API**, which connects to WhatsApp through the unofficial [Baileys library](https://github.com/WhiskeySockets/Baileys). This is **not** the official WhatsApp Business API and may violate [WhatsApp's Terms of Service](https://www.whatsapp.com/legal/terms-of-service).
    
    **Before proceeding, please be aware:**
    
    - Your WhatsApp number could be **temporarily or permanently banned**
    - WhatsApp can **block unofficial methods at any time** without notice
    - There is **no official support** from Meta/WhatsApp
    - Use a **dedicated phone number** - never your personal number
    
    Consider the official [WhatsApp Business Platform](https://business.whatsapp.com/products/business-platform) for mission-critical applications. By installing this package, you acknowledge these risks and accept full responsibility.

## Requirements

Before installing, ensure your environment meets these requirements:

| Requirement | Version |
|-------------|---------|
| PHP | 8.2 or higher |
| Laravel | 11.x or 12.x |
| Evolution API Server | 2.x |

!!! tip "Evolution API Server"
    You need a running Evolution API server to use this package. See the [Evolution API documentation](https://doc.evolution-api.com/) for setup instructions.

## Installation Steps

### Step 1: Install via Composer

```bash
composer require lynkbyte/laravel-evolution-api
```

### Step 2: Run the Install Command

The easiest way to set up the package is using the install command:

```bash
php artisan evolution-api:install
```

This command will:

- [x] Publish the configuration file to `config/evolution-api.php`
- [x] Publish database migrations
- [x] Run the migrations to create required tables
- [x] Display next steps for configuration

!!! info "What Gets Installed"
    The install command is interactive and will ask for confirmation before running migrations. You can also run each step manually if you prefer more control.

### Alternative: Manual Installation

If you prefer to install manually or need more control over the process:

#### Publish Configuration

```bash
php artisan vendor:publish --tag="evolution-api-config"
```

This creates `config/evolution-api.php` with all available options.

#### Publish Migrations

```bash
php artisan vendor:publish --tag="evolution-api-migrations"
```

This publishes migration files to your `database/migrations` directory:

| Migration | Table | Purpose |
|-----------|-------|---------|
| `create_evolution_instances_table` | `evolution_instances` | Store WhatsApp instance information |
| `create_evolution_messages_table` | `evolution_messages` | Log sent and received messages |
| `create_evolution_contacts_table` | `evolution_contacts` | Store contact information |
| `create_evolution_webhook_logs_table` | `evolution_webhook_logs` | Log incoming webhooks |

#### Run Migrations

```bash
php artisan migrate
```

## Environment Configuration

Add the following variables to your `.env` file:

```env
# Required
EVOLUTION_API_URL=https://your-evolution-api-server.com
EVOLUTION_API_KEY=your-global-api-key

# Optional - Default Instance
EVOLUTION_DEFAULT_INSTANCE=my-instance

# Optional - Webhook
EVOLUTION_WEBHOOK_SECRET=your-webhook-secret
EVOLUTION_WEBHOOK_ROUTE_PREFIX=evolution/webhook

# Optional - Queue
EVOLUTION_QUEUE_ENABLED=true
EVOLUTION_QUEUE_NAME=evolution-api

# Optional - Database
EVOLUTION_DB_ENABLED=true
EVOLUTION_STORE_MESSAGES=true
EVOLUTION_STORE_WEBHOOKS=true

# Optional - Logging
EVOLUTION_LOGGING_ENABLED=true
EVOLUTION_LOG_REQUESTS=true
```

!!! warning "Security"
    Never commit your `.env` file or expose your API keys. The `EVOLUTION_API_KEY` is your global authentication key for the Evolution API server.

## Verify Installation

After installation, verify everything is working:

### Check Package Health

```bash
php artisan evolution-api:health
```

This command checks:

- [x] Configuration is valid
- [x] Evolution API server is reachable
- [x] API key is valid
- [x] Database tables exist

### List Instances

```bash
php artisan evolution-api:instances
```

This displays all WhatsApp instances on your Evolution API server.

### Test in Code

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

// Check if we can reach the server
$instances = EvolutionApi::instance()->fetchAll();

if ($instances->successful()) {
    dump($instances->json());
} else {
    dump('Error: ' . $instances->status());
}
```

## Disabling Features

You can disable features you don't need:

### Disable Database Storage

If you don't want to store messages and webhooks in the database:

```env
EVOLUTION_DB_ENABLED=false
```

Or selectively disable:

```env
EVOLUTION_STORE_MESSAGES=false
EVOLUTION_STORE_WEBHOOKS=false
```

### Disable Queues

To process messages synchronously instead of via queues:

```env
EVOLUTION_QUEUE_ENABLED=false
```

### Disable Webhooks

If you don't need to receive webhooks:

```env
EVOLUTION_WEBHOOK_ENABLED=false
```

## Upgrading

When upgrading to a new version:

```bash
# Update the package
composer update lynkbyte/laravel-evolution-api

# Publish any new migrations
php artisan vendor:publish --tag="evolution-api-migrations"

# Run migrations
php artisan migrate

# Clear config cache
php artisan config:clear
```

!!! tip "Check the Changelog"
    Always review the [changelog](https://github.com/lynkbyte/laravel-evolution-api/blob/main/CHANGELOG.md) for breaking changes before upgrading.

## Troubleshooting

### Common Issues

#### "Class not found" Error

Clear your autoloader cache:

```bash
composer dump-autoload
```

#### Configuration Not Loading

Clear the config cache:

```bash
php artisan config:clear
php artisan cache:clear
```

#### Migration Errors

If you get table already exists errors, the migrations may have already run. Check your `migrations` table:

```bash
php artisan migrate:status
```

#### Connection Refused

Ensure your Evolution API server is running and the URL is correct:

```bash
curl -I https://your-evolution-api-server.com
```

---

## Next Steps

- [Configuration Reference](configuration.md) - Learn about all configuration options
- [Quick Start Guide](quick-start.md) - Send your first message
- [Architecture Overview](../core-concepts/architecture.md) - Understand how the package works
