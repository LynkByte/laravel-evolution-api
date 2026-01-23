# Data Retention

Manage storage growth with automatic data pruning and archival.

## Configuration

```php
// config/evolution-api.php

'database' => [
    'enabled' => true,
    'prune_after_days' => env('EVOLUTION_PRUNE_DAYS', 30),
],
```

## Automatic Pruning

### Using Laravel's Prunable Trait

The models support Laravel's pruning:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('model:prune', [
        '--model' => [
            \Lynkbyte\EvolutionApi\Models\EvolutionMessage::class,
            \Lynkbyte\EvolutionApi\Models\EvolutionWebhookLog::class,
        ],
    ])->daily();
}
```

### Custom Pruning Command

```bash
php artisan evolution:prune --days=30
```

```php
// app/Console/Commands/PruneEvolutionData.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Lynkbyte\EvolutionApi\Models\EvolutionMessage;
use Lynkbyte\EvolutionApi\Models\EvolutionWebhookLog;

class PruneEvolutionData extends Command
{
    protected $signature = 'evolution:prune {--days=30}';
    protected $description = 'Prune old Evolution API data';

    public function handle(): void
    {
        $days = $this->option('days');
        $cutoff = now()->subDays($days);

        // Prune messages
        $messagesDeleted = EvolutionMessage::where('created_at', '<', $cutoff)
            ->whereIn('status', ['read', 'delivered', 'sent'])
            ->delete();

        $this->info("Deleted {$messagesDeleted} messages");

        // Prune webhook logs
        $webhooksDeleted = EvolutionWebhookLog::where('created_at', '<', $cutoff)
            ->where('processed', true)
            ->delete();

        $this->info("Deleted {$webhooksDeleted} webhook logs");
    }
}
```

## Retention Strategies

### By Status

Keep failed messages longer for debugging:

```php
// Delete successful messages after 7 days
EvolutionMessage::where('created_at', '<', now()->subDays(7))
    ->whereIn('status', ['read', 'delivered'])
    ->delete();

// Delete failed messages after 30 days
EvolutionMessage::where('created_at', '<', now()->subDays(30))
    ->where('status', 'failed')
    ->delete();
```

### By Type

Keep media messages shorter due to size:

```php
// Delete media messages after 7 days
EvolutionMessage::where('created_at', '<', now()->subDays(7))
    ->whereIn('message_type', ['image', 'video', 'audio', 'document'])
    ->delete();

// Delete text messages after 30 days
EvolutionMessage::where('created_at', '<', now()->subDays(30))
    ->where('message_type', 'text')
    ->delete();
```

### By Instance

Different retention for different instances:

```php
// Production instance - keep 90 days
EvolutionMessage::forInstance('production')
    ->where('created_at', '<', now()->subDays(90))
    ->delete();

// Development instance - keep 7 days
EvolutionMessage::forInstance('development')
    ->where('created_at', '<', now()->subDays(7))
    ->delete();
```

## Archival

### Archive to Separate Table

```php
// Create archive table
Schema::create('evolution_messages_archive', function (Blueprint $table) {
    // Same structure as evolution_messages
    $table->id();
    $table->string('message_id');
    $table->string('instance_name');
    // ... all other columns
    $table->timestamp('archived_at');
});

// Archive old messages
class ArchiveMessagesCommand extends Command
{
    public function handle(): void
    {
        $cutoff = now()->subDays(30);
        
        // Move to archive
        DB::statement("
            INSERT INTO evolution_messages_archive 
            SELECT *, NOW() as archived_at
            FROM evolution_messages 
            WHERE created_at < ?
            AND status IN ('read', 'delivered')
        ", [$cutoff]);
        
        // Delete from main table
        EvolutionMessage::where('created_at', '<', $cutoff)
            ->whereIn('status', ['read', 'delivered'])
            ->delete();
    }
}
```

### Archive to Cold Storage

Export to S3 or similar:

```php
use Illuminate\Support\Facades\Storage;

class ExportToS3Command extends Command
{
    public function handle(): void
    {
        $date = now()->subDays(30)->format('Y-m-d');
        
        // Export to JSON
        $messages = EvolutionMessage::where('created_at', '<', $date)
            ->get()
            ->toJson();
        
        // Upload to S3
        Storage::disk('s3')->put(
            "archives/messages/{$date}.json",
            $messages
        );
        
        // Delete local data
        EvolutionMessage::where('created_at', '<', $date)->delete();
    }
}
```

## Soft Deletes

Use soft deletes for instances:

```php
use Lynkbyte\EvolutionApi\Models\EvolutionInstance;

// Soft delete
$instance = EvolutionInstance::find(1);
$instance->delete();

// Query including soft deleted
EvolutionInstance::withTrashed()->get();

// Restore
$instance->restore();

// Force delete
$instance->forceDelete();

// Prune soft deleted after 90 days
EvolutionInstance::onlyTrashed()
    ->where('deleted_at', '<', now()->subDays(90))
    ->forceDelete();
```

## Scheduled Pruning

### Schedule Configuration

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Daily pruning at 3 AM
    $schedule->command('evolution:prune --days=30')
        ->dailyAt('03:00')
        ->withoutOverlapping()
        ->runInBackground();
    
    // Weekly archival on Sunday
    $schedule->command('evolution:archive')
        ->weeklyOn(0, '04:00');
    
    // Monthly cleanup of archives
    $schedule->command('evolution:cleanup-archives --months=12')
        ->monthlyOn(1, '05:00');
}
```

### With Notifications

```php
$schedule->command('evolution:prune --days=30')
    ->dailyAt('03:00')
    ->onSuccess(function () {
        Log::info('Evolution data pruned successfully');
    })
    ->onFailure(function () {
        Notification::route('slack', config('services.slack.ops'))
            ->notify(new PruneFailedNotification());
    });
```

## Monitoring Storage

### Check Table Sizes

```php
// app/Console/Commands/CheckStorageCommand.php
class CheckStorageCommand extends Command
{
    protected $signature = 'evolution:storage-check';

    public function handle(): void
    {
        $tables = [
            'evolution_messages',
            'evolution_webhook_logs',
            'evolution_instances',
            'evolution_contacts',
        ];

        foreach ($tables as $table) {
            $size = DB::selectOne("
                SELECT 
                    table_name,
                    ROUND(data_length / 1024 / 1024, 2) as size_mb,
                    table_rows
                FROM information_schema.tables 
                WHERE table_name = ?
            ", [$table]);

            $this->info("{$table}: {$size->size_mb} MB ({$size->table_rows} rows)");
        }
    }
}
```

### Alert on Growth

```php
// In scheduled check
$messageCount = EvolutionMessage::count();

if ($messageCount > 1000000) {
    Notification::route('slack', config('services.slack.ops'))
        ->notify(new HighMessageCount($messageCount));
}
```

## Best Practices

### 1. Run Pruning Off-Peak

Schedule during low-traffic hours to minimize impact.

### 2. Use Chunked Deletes

For large datasets, delete in chunks:

```php
do {
    $deleted = EvolutionMessage::where('created_at', '<', $cutoff)
        ->limit(1000)
        ->delete();
    
    usleep(100000); // 100ms pause between batches
} while ($deleted > 0);
```

### 3. Keep Audit Trail

Before deleting, consider logging statistics:

```php
$stats = [
    'date' => now()->toDateString(),
    'messages_pruned' => $messagesDeleted,
    'webhooks_pruned' => $webhooksDeleted,
    'storage_freed_mb' => $storageFreed,
];

DB::table('evolution_prune_logs')->insert($stats);
```

### 4. Test Retention Policies

Verify your retention meets compliance requirements:

```php
// Test that no messages older than retention period exist
test('messages are pruned after retention period', function () {
    EvolutionMessage::factory()
        ->create(['created_at' => now()->subDays(31)]);
    
    Artisan::call('evolution:prune --days=30');
    
    expect(EvolutionMessage::where('created_at', '<', now()->subDays(30))->count())
        ->toBe(0);
});
```
