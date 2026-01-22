<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Metrics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Metrics collector for monitoring Evolution API usage and performance.
 *
 * Tracks:
 * - Messages sent/received
 * - API calls and errors
 * - Webhook events
 * - Response times
 * - Queue job statistics
 */
class MetricsCollector
{
    /**
     * Metrics configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * In-memory metrics buffer.
     *
     * @var array<string, mixed>
     */
    protected array $buffer = [];

    /**
     * Supported metrics drivers.
     *
     * @var array<string>
     */
    protected array $supportedDrivers = ['database', 'cache', 'prometheus', 'null'];

    /**
     * Create a new metrics collector instance.
     *
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => false,
            'driver' => 'database',
            'track' => [
                'messages_sent' => true,
                'messages_received' => true,
                'api_calls' => true,
                'api_errors' => true,
                'webhook_events' => true,
                'response_times' => true,
                'queue_jobs' => true,
            ],
        ], $config);
    }

    /**
     * Check if metrics collection is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->config['enabled'];
    }

    /**
     * Check if a specific metric type is being tracked.
     */
    public function isTracking(string $metric): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        return $this->config['track'][$metric] ?? false;
    }

    /**
     * Increment a counter metric.
     *
     * @param  string  $metric  Metric name
     * @param  int  $value  Value to increment by
     * @param  array<string, mixed>  $tags  Additional tags
     */
    public function increment(string $metric, int $value = 1, array $tags = []): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->record($metric, $value, 'counter', $tags);
    }

    /**
     * Record a gauge metric (current value).
     *
     * @param  string  $metric  Metric name
     * @param  float|int  $value  Current value
     * @param  array<string, mixed>  $tags  Additional tags
     */
    public function gauge(string $metric, float|int $value, array $tags = []): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->record($metric, $value, 'gauge', $tags);
    }

    /**
     * Record a timing metric.
     *
     * @param  string  $metric  Metric name
     * @param  float  $milliseconds  Duration in milliseconds
     * @param  array<string, mixed>  $tags  Additional tags
     */
    public function timing(string $metric, float $milliseconds, array $tags = []): void
    {
        if (! $this->isEnabled() || ! $this->isTracking('response_times')) {
            return;
        }

        $this->record($metric, $milliseconds, 'timing', $tags);
    }

    /**
     * Record a histogram metric.
     *
     * @param  string  $metric  Metric name
     * @param  float  $value  Value to record
     * @param  array<string, mixed>  $tags  Additional tags
     */
    public function histogram(string $metric, float $value, array $tags = []): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->record($metric, $value, 'histogram', $tags);
    }

    /**
     * Track a message sent.
     *
     * @param  string  $instanceName  Instance name
     * @param  string  $messageType  Message type
     * @param  bool  $success  Whether send was successful
     * @param  float|null  $duration  Duration in milliseconds
     */
    public function trackMessageSent(
        string $instanceName,
        string $messageType,
        bool $success,
        ?float $duration = null
    ): void {
        if (! $this->isTracking('messages_sent')) {
            return;
        }

        $tags = [
            'instance' => $instanceName,
            'type' => $messageType,
            'status' => $success ? 'success' : 'failed',
        ];

        $this->increment('evolution.messages.sent', 1, $tags);

        if ($duration !== null) {
            $this->timing('evolution.messages.send_time', $duration, $tags);
        }
    }

    /**
     * Track a message received.
     *
     * @param  string  $instanceName  Instance name
     * @param  string  $messageType  Message type
     */
    public function trackMessageReceived(string $instanceName, string $messageType): void
    {
        if (! $this->isTracking('messages_received')) {
            return;
        }

        $this->increment('evolution.messages.received', 1, [
            'instance' => $instanceName,
            'type' => $messageType,
        ]);
    }

    /**
     * Track an API call.
     *
     * @param  string  $endpoint  API endpoint
     * @param  string  $method  HTTP method
     * @param  int  $statusCode  Response status code
     * @param  float|null  $duration  Duration in milliseconds
     */
    public function trackApiCall(
        string $endpoint,
        string $method,
        int $statusCode,
        ?float $duration = null
    ): void {
        if (! $this->isTracking('api_calls')) {
            return;
        }

        $tags = [
            'endpoint' => $this->normalizeEndpoint($endpoint),
            'method' => $method,
            'status_code' => $statusCode,
            'status_class' => (string) floor($statusCode / 100).'xx',
        ];

        $this->increment('evolution.api.calls', 1, $tags);

        if ($duration !== null) {
            $this->timing('evolution.api.response_time', $duration, $tags);
        }

        // Track errors separately
        if ($statusCode >= 400 && $this->isTracking('api_errors')) {
            $this->increment('evolution.api.errors', 1, $tags);
        }
    }

    /**
     * Track a webhook event.
     *
     * @param  string  $eventType  Webhook event type
     * @param  string  $instanceName  Instance name
     * @param  bool  $processed  Whether processing was successful
     * @param  float|null  $duration  Processing duration in milliseconds
     */
    public function trackWebhookEvent(
        string $eventType,
        string $instanceName,
        bool $processed = true,
        ?float $duration = null
    ): void {
        if (! $this->isTracking('webhook_events')) {
            return;
        }

        $tags = [
            'event' => $eventType,
            'instance' => $instanceName,
            'status' => $processed ? 'processed' : 'failed',
        ];

        $this->increment('evolution.webhooks.received', 1, $tags);

        if ($duration !== null) {
            $this->timing('evolution.webhooks.processing_time', $duration, $tags);
        }
    }

    /**
     * Track a queue job.
     *
     * @param  string  $jobType  Job class name
     * @param  string  $status  Job status (queued, processing, completed, failed)
     * @param  float|null  $duration  Processing duration in milliseconds
     */
    public function trackQueueJob(
        string $jobType,
        string $status,
        ?float $duration = null
    ): void {
        if (! $this->isTracking('queue_jobs')) {
            return;
        }

        $tags = [
            'job_type' => class_basename($jobType),
            'status' => $status,
        ];

        $this->increment('evolution.queue.jobs', 1, $tags);

        if ($duration !== null && in_array($status, ['completed', 'failed'])) {
            $this->timing('evolution.queue.processing_time', $duration, $tags);
        }
    }

    /**
     * Track instance connection status.
     *
     * @param  string  $instanceName  Instance name
     * @param  string  $status  Connection status
     */
    public function trackInstanceStatus(string $instanceName, string $status): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->gauge('evolution.instances.status', 1, [
            'instance' => $instanceName,
            'status' => $status,
        ]);
    }

    /**
     * Track rate limit status.
     *
     * @param  string  $operation  Operation type
     * @param  int  $remaining  Remaining attempts
     * @param  int  $limit  Total limit
     */
    public function trackRateLimit(string $operation, int $remaining, int $limit): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $usage = $limit > 0 ? (($limit - $remaining) / $limit) * 100 : 0;

        $this->gauge('evolution.rate_limit.remaining', $remaining, [
            'operation' => $operation,
        ]);

        $this->gauge('evolution.rate_limit.usage_percent', $usage, [
            'operation' => $operation,
        ]);
    }

    /**
     * Get current metrics.
     *
     * @param  string|null  $prefix  Optional prefix filter
     * @return array<string, mixed>
     */
    public function getMetrics(?string $prefix = null): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        return match ($this->config['driver']) {
            'cache' => $this->getMetricsFromCache($prefix),
            'database' => $this->getMetricsFromDatabase($prefix),
            default => $this->buffer,
        };
    }

    /**
     * Flush metrics buffer.
     */
    public function flush(): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        match ($this->config['driver']) {
            'database' => $this->flushToDatabase(),
            'cache' => $this->flushToCache(),
            'prometheus' => $this->flushToPrometheus(),
            default => null,
        };

        $this->buffer = [];
    }

    /**
     * Reset all metrics.
     */
    public function reset(): void
    {
        $this->buffer = [];

        if (! $this->isEnabled()) {
            return;
        }

        match ($this->config['driver']) {
            'cache' => Cache::forget('evolution_api_metrics'),
            'database' => $this->resetDatabase(),
            default => null,
        };
    }

    /**
     * Record a metric.
     *
     * @param  string  $metric  Metric name
     * @param  float|int  $value  Value
     * @param  string  $type  Metric type
     * @param  array<string, mixed>  $tags  Tags
     */
    protected function record(
        string $metric,
        float|int $value,
        string $type,
        array $tags = []
    ): void {
        $entry = [
            'metric' => $metric,
            'value' => $value,
            'type' => $type,
            'tags' => $tags,
            'timestamp' => microtime(true),
        ];

        $this->buffer[] = $entry;

        // Auto-flush if buffer is too large
        if (count($this->buffer) >= 100) {
            $this->flush();
        }
    }

    /**
     * Normalize endpoint for consistent metric tagging.
     */
    protected function normalizeEndpoint(string $endpoint): string
    {
        // Remove instance names and IDs from endpoint for aggregation
        $normalized = preg_replace('/\/[a-zA-Z0-9_-]+\//', '/{param}/', $endpoint);

        // Remove query strings
        $normalized = preg_replace('/\?.*$/', '', $normalized);

        return $normalized;
    }

    /**
     * Get metrics from cache driver.
     *
     * @return array<string, mixed>
     */
    protected function getMetricsFromCache(?string $prefix = null): array
    {
        $metrics = Cache::get('evolution_api_metrics', []);

        if ($prefix !== null) {
            return array_filter($metrics, function ($key) use ($prefix) {
                return str_starts_with($key, $prefix);
            }, ARRAY_FILTER_USE_KEY);
        }

        return $metrics;
    }

    /**
     * Get metrics from database driver.
     *
     * @return array<string, mixed>
     */
    protected function getMetricsFromDatabase(?string $prefix = null): array
    {
        $table = config('evolution-api.database.table_prefix', 'evolution_').'metrics';

        $query = DB::table($table);

        if ($prefix !== null) {
            $query->where('metric', 'like', $prefix.'%');
        }

        return $query->get()->keyBy('metric')->toArray();
    }

    /**
     * Flush metrics to cache.
     */
    protected function flushToCache(): void
    {
        $existing = Cache::get('evolution_api_metrics', []);

        foreach ($this->buffer as $entry) {
            $key = $this->buildMetricKey($entry);

            if (! isset($existing[$key])) {
                $existing[$key] = [
                    'value' => 0,
                    'count' => 0,
                    'type' => $entry['type'],
                    'tags' => $entry['tags'],
                ];
            }

            if ($entry['type'] === 'counter') {
                $existing[$key]['value'] += $entry['value'];
            } elseif ($entry['type'] === 'gauge') {
                $existing[$key]['value'] = $entry['value'];
            } else {
                // For timing/histogram, calculate average
                $existing[$key]['value'] =
                    (($existing[$key]['value'] * $existing[$key]['count']) + $entry['value'])
                    / ($existing[$key]['count'] + 1);
            }

            $existing[$key]['count']++;
            $existing[$key]['last_updated'] = $entry['timestamp'];
        }

        Cache::put('evolution_api_metrics', $existing, 86400); // 24 hours
    }

    /**
     * Flush metrics to database.
     */
    protected function flushToDatabase(): void
    {
        if (empty($this->buffer)) {
            return;
        }

        $table = config('evolution-api.database.table_prefix', 'evolution_').'metrics';

        $records = [];

        foreach ($this->buffer as $entry) {
            $records[] = [
                'metric' => $entry['metric'],
                'value' => $entry['value'],
                'type' => $entry['type'],
                'tags' => json_encode($entry['tags']),
                'recorded_at' => date('Y-m-d H:i:s', (int) $entry['timestamp']),
            ];
        }

        try {
            DB::table($table)->insert($records);
        } catch (\Exception $e) {
            // Silently fail to avoid disrupting main operations
            // Could log this if logging is available
        }
    }

    /**
     * Flush metrics to Prometheus format.
     */
    protected function flushToPrometheus(): void
    {
        // Prometheus integration would go here
        // This is a placeholder for custom implementations
    }

    /**
     * Reset database metrics.
     */
    protected function resetDatabase(): void
    {
        $table = config('evolution-api.database.table_prefix', 'evolution_').'metrics';

        try {
            DB::table($table)->truncate();
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    /**
     * Build a unique key for a metric entry.
     */
    protected function buildMetricKey(array $entry): string
    {
        $key = $entry['metric'];

        if (! empty($entry['tags'])) {
            ksort($entry['tags']);
            $key .= ':'.http_build_query($entry['tags']);
        }

        return $key;
    }

    /**
     * Get metrics summary.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        if (! $this->isEnabled()) {
            return ['enabled' => false];
        }

        $metrics = $this->getMetrics();

        return [
            'enabled' => true,
            'driver' => $this->config['driver'],
            'total_metrics' => count($metrics),
            'buffer_size' => count($this->buffer),
            'tracking' => $this->config['track'],
        ];
    }

    /**
     * Time a callback and record the duration.
     *
     * @param  string  $metric  Metric name
     * @param  callable  $callback  Callback to time
     * @param  array<string, mixed>  $tags  Additional tags
     * @return mixed Callback result
     */
    public function measure(string $metric, callable $callback, array $tags = []): mixed
    {
        $start = microtime(true);

        try {
            $result = $callback();
            $duration = (microtime(true) - $start) * 1000;
            $this->timing($metric, $duration, array_merge($tags, ['status' => 'success']));

            return $result;
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $start) * 1000;
            $this->timing($metric, $duration, array_merge($tags, ['status' => 'error']));
            throw $e;
        }
    }
}
