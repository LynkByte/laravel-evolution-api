<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lynkbyte\EvolutionApi\Jobs\ProcessWebhookJob;
use Lynkbyte\EvolutionApi\Webhooks\WebhookProcessor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Controller for handling incoming webhooks from Evolution API.
 *
 * This controller receives webhooks and either processes them immediately
 * or queues them for background processing.
 */
class WebhookController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected WebhookProcessor $processor,
        protected ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger;
    }

    /**
     * Handle incoming webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Validate payload has minimum required data
        if (! $this->isValidPayload($payload)) {
            $this->logger->warning('Invalid webhook payload received', [
                'payload' => $payload,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid payload',
            ], 400);
        }

        $this->logger->info('Webhook received', [
            'event' => $payload['event'] ?? 'unknown',
            'instance' => $payload['instance'] ?? $payload['instanceName'] ?? 'unknown',
        ]);

        // Check if webhook should be queued
        if ($this->shouldQueue()) {
            return $this->queueWebhook($payload);
        }

        return $this->processWebhook($payload);
    }

    /**
     * Handle webhook for a specific instance.
     * Route: POST /webhook/{instance}
     */
    public function handleInstance(Request $request, string $instance): JsonResponse
    {
        $payload = $request->all();

        // Add instance to payload if not present
        if (! isset($payload['instance']) && ! isset($payload['instanceName'])) {
            $payload['instance'] = $instance;
        }

        return $this->handle($request->merge($payload));
    }

    /**
     * Process the webhook immediately.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function processWebhook(array $payload): JsonResponse
    {
        try {
            $this->processor->process($payload);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook processed',
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            // Return success to Evolution API to prevent retries
            // Log the error for debugging
            return response()->json([
                'status' => 'error',
                'message' => 'Processing failed',
            ], 500);
        }
    }

    /**
     * Queue the webhook for background processing.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function queueWebhook(array $payload): JsonResponse
    {
        try {
            $instanceName = $payload['instance'] ?? $payload['instanceName'] ?? 'default';

            ProcessWebhookJob::dispatch($payload, $instanceName)
                ->onQueue($this->getQueueName());

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook queued',
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to queue webhook', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            // Fall back to synchronous processing
            return $this->processWebhook($payload);
        }
    }

    /**
     * Validate the webhook payload.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function isValidPayload(array $payload): bool
    {
        // At minimum, we need an event type
        if (empty($payload['event'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if webhooks should be queued.
     */
    protected function shouldQueue(): bool
    {
        return (bool) config('evolution-api.webhook.queue', false);
    }

    /**
     * Get the queue name for webhook jobs.
     */
    protected function getQueueName(): string
    {
        return config('evolution-api.queue.webhook_queue', 'default');
    }

    /**
     * Health check endpoint.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'evolution-api-webhook',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
