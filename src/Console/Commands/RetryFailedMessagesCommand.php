<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Console\Commands;

use Illuminate\Console\Command;
use Lynkbyte\EvolutionApi\Models\EvolutionMessage;
use Lynkbyte\EvolutionApi\Services\EvolutionService;

/**
 * Command to retry failed messages.
 */
class RetryFailedMessagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'evolution-api:retry 
                            {--instance= : Specific instance to retry messages for}
                            {--max-retries=3 : Maximum retry attempts}
                            {--limit=100 : Maximum number of messages to retry}
                            {--dry-run : Show what would be retried without actually retrying}';

    /**
     * The console command description.
     */
    protected $description = 'Retry failed messages';

    /**
     * Execute the console command.
     */
    public function handle(EvolutionService $evolution): int
    {
        $instance = $this->option('instance');
        $maxRetries = (int) $this->option('max-retries');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info('Finding failed messages to retry...');

        if ($dryRun) {
            $this->warn('Dry run mode - no messages will be sent.');
        }

        $this->newLine();

        // Build query
        $query = EvolutionMessage::retryable($maxRetries)
            ->orderBy('created_at', 'asc')
            ->limit($limit);

        if ($instance) {
            $query->forInstance($instance);
        }

        $messages = $query->get();

        if ($messages->isEmpty()) {
            $this->info('No failed messages found to retry.');

            return self::SUCCESS;
        }

        $this->info("Found {$messages->count()} message(s) to retry.");
        $this->newLine();

        $successCount = 0;
        $failCount = 0;

        foreach ($messages as $message) {
            $this->line("Retrying message: <info>{$message->message_id}</info>");
            $this->line("  Instance: {$message->instance_name}");
            $this->line("  Recipient: {$message->remote_jid}");
            $this->line("  Type: {$message->message_type}");
            $this->line("  Retry count: {$message->retry_count}");

            if ($dryRun) {
                $this->line('  <comment>Would retry...</comment>');
                $successCount++;
                $this->newLine();

                continue;
            }

            try {
                // Increment retry count
                $message->incrementRetry();

                // Get the original payload
                $payload = $message->payload;

                if (empty($payload)) {
                    $this->line('  <fg=red>No payload found, skipping...</>');
                    $failCount++;
                    $this->newLine();

                    continue;
                }

                // Retry based on message type
                $response = $this->retryMessage($evolution, $message);

                if ($response && $response->isSuccess()) {
                    $message->markAsSent($response->getData());
                    $this->line('  <fg=green>Success!</>');
                    $successCount++;
                } else {
                    $errorMessage = $response?->getError() ?? 'Unknown error';
                    $message->markAsFailed($errorMessage);
                    $this->line("  <fg=red>Failed: {$errorMessage}</>");
                    $failCount++;
                }

            } catch (\Throwable $e) {
                $message->markAsFailed($e->getMessage());
                $this->line("  <fg=red>Exception: {$e->getMessage()}</>");
                $failCount++;
            }

            $this->newLine();
        }

        $this->newLine();
        $this->info("Completed: {$successCount} succeeded, {$failCount} failed.");

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Retry a message based on its type.
     */
    protected function retryMessage(EvolutionService $evolution, EvolutionMessage $message): ?\Lynkbyte\EvolutionApi\DTOs\ApiResponse
    {
        $payload = $message->payload;
        $instanceService = $evolution->for($message->instance_name);

        return match ($message->message_type) {
            'text' => $instanceService->message()->sendText(
                $message->remote_jid,
                $message->content ?? $payload['text'] ?? ''
            ),
            'image', 'video', 'audio', 'document' => $instanceService->message()->sendMedia(
                $message->remote_jid,
                $payload['media'] ?? $payload['mediaUrl'] ?? '',
                $payload['caption'] ?? null,
                $payload['fileName'] ?? null
            ),
            default => null,
        };
    }
}
