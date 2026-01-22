<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Console\Commands;

use Illuminate\Console\Command;
use Lynkbyte\EvolutionApi\Models\EvolutionMessage;
use Lynkbyte\EvolutionApi\Models\EvolutionWebhookLog;

/**
 * Command to prune old data from Evolution API tables.
 */
class PruneOldDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'evolution-api:prune 
                            {--days=30 : Number of days to keep data}
                            {--messages : Prune old messages}
                            {--webhooks : Prune old webhook logs}
                            {--all : Prune all data types}
                            {--dry-run : Show what would be deleted without deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Prune old data from Evolution API database tables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $pruneMessages = $this->option('messages') || $this->option('all');
        $pruneWebhooks = $this->option('webhooks') || $this->option('all');

        // Default to pruning all if no specific option is given
        if (! $pruneMessages && ! $pruneWebhooks) {
            $pruneMessages = true;
            $pruneWebhooks = true;
        }

        $this->info("Pruning data older than {$days} days...");

        if ($dryRun) {
            $this->warn('Dry run mode - no data will be deleted.');
        }

        $this->newLine();

        $totalDeleted = 0;

        if ($pruneMessages) {
            $totalDeleted += $this->pruneMessages($days, $dryRun);
        }

        if ($pruneWebhooks) {
            $totalDeleted += $this->pruneWebhooks($days, $dryRun);
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("Would delete {$totalDeleted} total records.");
        } else {
            $this->info("Deleted {$totalDeleted} total records.");
        }

        return self::SUCCESS;
    }

    /**
     * Prune old messages.
     */
    protected function pruneMessages(int $days, bool $dryRun): int
    {
        $this->comment('Pruning old messages...');

        $query = EvolutionMessage::where('created_at', '<', now()->subDays($days));
        $count = $query->count();

        if ($count === 0) {
            $this->line('  No old messages to prune.');

            return 0;
        }

        if (! $dryRun) {
            $deleted = $query->delete();
            $this->line("  Deleted <info>{$deleted}</info> messages.");

            return $deleted;
        }

        $this->line("  Would delete <info>{$count}</info> messages.");

        return $count;
    }

    /**
     * Prune old webhook logs.
     */
    protected function pruneWebhooks(int $days, bool $dryRun): int
    {
        $this->comment('Pruning old webhook logs...');

        $query = EvolutionWebhookLog::where('created_at', '<', now()->subDays($days));
        $count = $query->count();

        if ($count === 0) {
            $this->line('  No old webhook logs to prune.');

            return 0;
        }

        if (! $dryRun) {
            $deleted = $query->delete();
            $this->line("  Deleted <info>{$deleted}</info> webhook logs.");

            return $deleted;
        }

        $this->line("  Would delete <info>{$count}</info> webhook logs.");

        return $count;
    }
}
