# Database Migrations

The package includes migrations for storing messages, webhooks, and instance data.

## Publishing Migrations

Publish migrations to your application:

```bash
php artisan vendor:publish --tag=evolution-api-migrations
```

This copies migrations to `database/migrations/`.

## Running Migrations

```bash
php artisan migrate
```

## Migration Files

### evolution_instances

Stores WhatsApp instance information:

```php
Schema::create('evolution_instances', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('display_name')->nullable();
    $table->string('connection_name')->default('default');
    $table->string('phone_number')->nullable();
    $table->string('status')->default('disconnected');
    $table->string('profile_name')->nullable();
    $table->string('profile_picture_url')->nullable();
    $table->json('settings')->nullable();
    $table->json('webhook_config')->nullable();
    $table->timestamp('connected_at')->nullable();
    $table->timestamp('disconnected_at')->nullable();
    $table->timestamp('last_seen_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('status');
    $table->index('connection_name');
    $table->index('phone_number');
});
```

### evolution_messages

Stores message history:

```php
Schema::create('evolution_messages', function (Blueprint $table) {
    $table->id();
    $table->string('message_id')->index();
    $table->string('instance_name')->index();
    $table->string('remote_jid');
    $table->boolean('from_me')->default(false);
    $table->string('message_type')->default('text');
    $table->string('status')->default('pending');
    $table->text('content')->nullable();
    $table->json('media')->nullable();
    $table->json('payload')->nullable();
    $table->json('response')->nullable();
    $table->string('error_message')->nullable();
    $table->unsignedInteger('retry_count')->default(0);
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->timestamp('read_at')->nullable();
    $table->timestamp('failed_at')->nullable();
    $table->timestamps();

    $table->index(['instance_name', 'remote_jid']);
    $table->index(['instance_name', 'status']);
    $table->index('created_at');
    $table->unique(['message_id', 'instance_name']);
});
```

### evolution_webhook_logs

Stores webhook history:

```php
Schema::create('evolution_webhook_logs', function (Blueprint $table) {
    $table->id();
    $table->string('instance_name')->index();
    $table->string('event')->index();
    $table->json('payload');
    $table->boolean('processed')->default(false);
    $table->string('error')->nullable();
    $table->timestamps();

    $table->index(['instance_name', 'event']);
    $table->index('created_at');
});
```

### evolution_contacts

Stores contact information:

```php
Schema::create('evolution_contacts', function (Blueprint $table) {
    $table->id();
    $table->string('instance_name')->index();
    $table->string('jid')->index();
    $table->string('name')->nullable();
    $table->string('push_name')->nullable();
    $table->string('profile_picture_url')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->unique(['instance_name', 'jid']);
});
```

## Custom Table Prefix

Use a custom table prefix:

```php
// config/evolution-api.php
'database' => [
    'table_prefix' => 'wa_',  // Tables: wa_instances, wa_messages, etc.
],
```

## Custom Database Connection

Use a different database connection:

```php
// config/evolution-api.php
'database' => [
    'connection' => 'whatsapp',  // Use 'whatsapp' connection
],

// config/database.php
'connections' => [
    'whatsapp' => [
        'driver' => 'mysql',
        'host' => env('WA_DB_HOST'),
        'database' => env('WA_DB_DATABASE'),
        // ...
    ],
],
```

## Extending Migrations

### Adding Custom Columns

Create a new migration to add columns:

```bash
php artisan make:migration add_tenant_id_to_evolution_tables
```

```php
// database/migrations/xxxx_add_tenant_id_to_evolution_tables.php
public function up(): void
{
    Schema::table('evolution_instances', function (Blueprint $table) {
        $table->foreignId('tenant_id')->nullable()->after('id')->constrained();
        $table->index('tenant_id');
    });

    Schema::table('evolution_messages', function (Blueprint $table) {
        $table->foreignId('tenant_id')->nullable()->after('id')->constrained();
        $table->index('tenant_id');
    });
}
```

### Adding Custom Indexes

```php
// Improve query performance for your use case
public function up(): void
{
    Schema::table('evolution_messages', function (Blueprint $table) {
        $table->index(['remote_jid', 'created_at']);
        $table->index(['status', 'retry_count', 'failed_at']);
    });
}
```

## Multi-Tenant Migrations

For multi-tenant applications:

```php
Schema::create('evolution_instances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    // ... other columns
    
    // Name unique per tenant
    $table->unique(['tenant_id', 'name']);
});

Schema::create('evolution_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    // ... other columns
    
    $table->index(['tenant_id', 'instance_name']);
});
```

## Rollback

Rollback migrations:

```bash
# Rollback last batch
php artisan migrate:rollback

# Rollback specific migrations
php artisan migrate:rollback --step=4

# Fresh start (drops all tables)
php artisan migrate:fresh
```

## Skip Migrations

If you don't need database storage:

```php
// config/evolution-api.php
'database' => [
    'enabled' => false,
],
```

Or selectively disable:

```php
'database' => [
    'enabled' => true,
    'store_messages' => false,  // Don't store messages
    'store_webhooks' => true,   // Still store webhooks
    'store_instances' => true,  // Still store instances
],
```

## Production Considerations

### 1. Index Strategy

The default indexes cover common queries. Monitor slow queries and add indexes as needed:

```sql
-- Check query performance
EXPLAIN ANALYZE SELECT * FROM evolution_messages 
WHERE instance_name = 'my-instance' 
AND status = 'pending'
ORDER BY created_at DESC;
```

### 2. Partitioning (High Volume)

For high-volume installations, consider table partitioning:

```sql
-- MySQL 8.0+
ALTER TABLE evolution_messages 
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202401 VALUES LESS THAN (202402),
    PARTITION p202402 VALUES LESS THAN (202403),
    -- ...
);
```

### 3. Archive Strategy

Move old data to archive tables:

```php
// Archive messages older than 30 days
DB::statement("
    INSERT INTO evolution_messages_archive 
    SELECT * FROM evolution_messages 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
");

DB::table('evolution_messages')
    ->where('created_at', '<', now()->subDays(30))
    ->delete();
```
