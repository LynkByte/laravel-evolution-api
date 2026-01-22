<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Metrics\MetricsCollector;

describe('MetricsCollector', function () {

    describe('isEnabled', function () {
        it('returns false when metrics are disabled', function () {
            $collector = new MetricsCollector(['enabled' => false]);

            expect($collector->isEnabled())->toBeFalse();
        });

        it('returns true when metrics are enabled', function () {
            $collector = new MetricsCollector(['enabled' => true]);

            expect($collector->isEnabled())->toBeTrue();
        });

        it('defaults to disabled', function () {
            $collector = new MetricsCollector();

            expect($collector->isEnabled())->toBeFalse();
        });
    });

    describe('isTracking', function () {
        it('returns false when metrics are disabled', function () {
            $collector = new MetricsCollector([
                'enabled' => false,
                'track' => ['messages_sent' => true],
            ]);

            expect($collector->isTracking('messages_sent'))->toBeFalse();
        });

        it('returns true when metric is enabled and tracking', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'track' => ['messages_sent' => true],
            ]);

            expect($collector->isTracking('messages_sent'))->toBeTrue();
        });

        it('returns false when specific metric is disabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'track' => ['messages_sent' => false],
            ]);

            expect($collector->isTracking('messages_sent'))->toBeFalse();
        });

        it('returns false for unknown metrics', function () {
            $collector = new MetricsCollector(['enabled' => true]);

            expect($collector->isTracking('unknown_metric'))->toBeFalse();
        });

        it('tracks all default metrics when enabled', function () {
            $collector = new MetricsCollector(['enabled' => true]);

            expect($collector->isTracking('messages_sent'))->toBeTrue();
            expect($collector->isTracking('messages_received'))->toBeTrue();
            expect($collector->isTracking('api_calls'))->toBeTrue();
            expect($collector->isTracking('api_errors'))->toBeTrue();
            expect($collector->isTracking('webhook_events'))->toBeTrue();
            expect($collector->isTracking('response_times'))->toBeTrue();
            expect($collector->isTracking('queue_jobs'))->toBeTrue();
        });
    });

    describe('increment', function () {
        it('does nothing when disabled', function () {
            $collector = new MetricsCollector(['enabled' => false]);
            $collector->increment('test.counter');

            expect($collector->getMetrics())->toBe([]);
        });

        it('records counter metrics to buffer when enabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->increment('test.counter', 5, ['tag' => 'value']);

            $metrics = $collector->getMetrics();

            expect($metrics)->toBeArray();
            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['metric'])->toBe('test.counter');
            expect($metrics[0]['value'])->toBe(5);
            expect($metrics[0]['type'])->toBe('counter');
            expect($metrics[0]['tags'])->toBe(['tag' => 'value']);
        });

        it('defaults to increment by 1', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->increment('test.counter');

            $metrics = $collector->getMetrics();

            expect($metrics[0]['value'])->toBe(1);
        });
    });

    describe('gauge', function () {
        it('does nothing when disabled', function () {
            $collector = new MetricsCollector(['enabled' => false]);
            $collector->gauge('test.gauge', 100);

            expect($collector->getMetrics())->toBe([]);
        });

        it('records gauge metrics when enabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->gauge('test.gauge', 42.5, ['instance' => 'main']);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['metric'])->toBe('test.gauge');
            expect($metrics[0]['value'])->toBe(42.5);
            expect($metrics[0]['type'])->toBe('gauge');
        });

        it('accepts integer values', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->gauge('test.gauge', 100);

            $metrics = $collector->getMetrics();

            expect($metrics[0]['value'])->toBe(100);
        });
    });

    describe('timing', function () {
        it('does nothing when disabled', function () {
            $collector = new MetricsCollector(['enabled' => false]);
            $collector->timing('test.timing', 150.5);

            expect($collector->getMetrics())->toBe([]);
        });

        it('does nothing when response_times tracking is disabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'track' => ['response_times' => false],
                'driver' => 'null',
            ]);

            $collector->timing('test.timing', 150.5);

            expect($collector->getMetrics())->toBe([]);
        });

        it('records timing metrics when enabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'track' => ['response_times' => true],
                'driver' => 'null',
            ]);

            $collector->timing('test.timing', 250.75, ['endpoint' => '/api/test']);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['metric'])->toBe('test.timing');
            expect($metrics[0]['value'])->toBe(250.75);
            expect($metrics[0]['type'])->toBe('timing');
        });
    });

    describe('histogram', function () {
        it('does nothing when disabled', function () {
            $collector = new MetricsCollector(['enabled' => false]);
            $collector->histogram('test.histogram', 50.0);

            expect($collector->getMetrics())->toBe([]);
        });

        it('records histogram metrics when enabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->histogram('test.histogram', 99.9, ['bucket' => 'large']);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['metric'])->toBe('test.histogram');
            expect($metrics[0]['value'])->toBe(99.9);
            expect($metrics[0]['type'])->toBe('histogram');
        });
    });

    describe('trackMessageSent', function () {
        it('does nothing when messages_sent tracking is disabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'track' => ['messages_sent' => false],
                'driver' => 'null',
            ]);

            $collector->trackMessageSent('test-instance', 'text', true, 100.0);

            expect($collector->getMetrics())->toBe([]);
        });

        it('records successful message sent', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackMessageSent('main-instance', 'text', true);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['metric'])->toBe('evolution.messages.sent');
            expect($metrics[0]['tags']['instance'])->toBe('main-instance');
            expect($metrics[0]['tags']['type'])->toBe('text');
            expect($metrics[0]['tags']['status'])->toBe('success');
        });

        it('records failed message sent', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackMessageSent('main-instance', 'media', false);

            $metrics = $collector->getMetrics();

            expect($metrics[0]['tags']['status'])->toBe('failed');
        });

        it('records timing when duration is provided', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackMessageSent('main-instance', 'text', true, 150.5);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(2);
            expect($metrics[1]['metric'])->toBe('evolution.messages.send_time');
            expect($metrics[1]['value'])->toBe(150.5);
            expect($metrics[1]['type'])->toBe('timing');
        });
    });

    describe('trackMessageReceived', function () {
        it('does nothing when messages_received tracking is disabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'track' => ['messages_received' => false],
                'driver' => 'null',
            ]);

            $collector->trackMessageReceived('test-instance', 'text');

            expect($collector->getMetrics())->toBe([]);
        });

        it('records message received', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackMessageReceived('main-instance', 'image');

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['metric'])->toBe('evolution.messages.received');
            expect($metrics[0]['tags']['instance'])->toBe('main-instance');
            expect($metrics[0]['tags']['type'])->toBe('image');
        });
    });

    describe('trackApiCall', function () {
        it('does nothing when api_calls tracking is disabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'track' => ['api_calls' => false],
                'driver' => 'null',
            ]);

            $collector->trackApiCall('/api/test', 'GET', 200);

            expect($collector->getMetrics())->toBe([]);
        });

        it('records api call with normalized endpoint', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackApiCall('/instance/test-123/message', 'POST', 200);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['metric'])->toBe('evolution.api.calls');
            expect($metrics[0]['tags']['method'])->toBe('POST');
            expect($metrics[0]['tags']['status_code'])->toBe(200);
            expect($metrics[0]['tags']['status_class'])->toBe('2xx');
        });

        it('records timing when duration is provided', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackApiCall('/api/test', 'GET', 200, 45.5);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(2);
            expect($metrics[1]['metric'])->toBe('evolution.api.response_time');
            expect($metrics[1]['value'])->toBe(45.5);
        });

        it('tracks errors separately for 4xx status codes', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackApiCall('/api/test', 'POST', 400);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(2);
            expect($metrics[0]['metric'])->toBe('evolution.api.calls');
            expect($metrics[1]['metric'])->toBe('evolution.api.errors');
            expect($metrics[0]['tags']['status_class'])->toBe('4xx');
        });

        it('tracks errors separately for 5xx status codes', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackApiCall('/api/test', 'POST', 500);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(2);
            expect($metrics[1]['metric'])->toBe('evolution.api.errors');
            expect($metrics[0]['tags']['status_class'])->toBe('5xx');
        });

        it('does not track errors when api_errors tracking is disabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'track' => [
                    'api_calls' => true,
                    'api_errors' => false,
                    'response_times' => false,
                ],
                'driver' => 'null',
            ]);

            $collector->trackApiCall('/api/test', 'POST', 500);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['metric'])->toBe('evolution.api.calls');
        });
    });

    describe('trackWebhookEvent', function () {
        it('does nothing when webhook_events tracking is disabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'track' => ['webhook_events' => false],
                'driver' => 'null',
            ]);

            $collector->trackWebhookEvent('MESSAGES_UPSERT', 'test-instance');

            expect($collector->getMetrics())->toBe([]);
        });

        it('records webhook event', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackWebhookEvent('MESSAGES_UPSERT', 'main-instance', true);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['metric'])->toBe('evolution.webhooks.received');
            expect($metrics[0]['tags']['event'])->toBe('MESSAGES_UPSERT');
            expect($metrics[0]['tags']['instance'])->toBe('main-instance');
            expect($metrics[0]['tags']['status'])->toBe('processed');
        });

        it('records failed webhook processing', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackWebhookEvent('MESSAGES_UPSERT', 'main-instance', false);

            $metrics = $collector->getMetrics();

            expect($metrics[0]['tags']['status'])->toBe('failed');
        });

        it('records processing time when duration is provided', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackWebhookEvent('MESSAGES_UPSERT', 'main-instance', true, 25.5);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(2);
            expect($metrics[1]['metric'])->toBe('evolution.webhooks.processing_time');
            expect($metrics[1]['value'])->toBe(25.5);
        });
    });

    describe('trackQueueJob', function () {
        it('does nothing when queue_jobs tracking is disabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'track' => ['queue_jobs' => false],
                'driver' => 'null',
            ]);

            $collector->trackQueueJob('SendMessageJob', 'completed', 100.0);

            expect($collector->getMetrics())->toBe([]);
        });

        it('records queue job', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackQueueJob('Lynkbyte\\EvolutionApi\\Jobs\\SendMessageJob', 'queued');

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['metric'])->toBe('evolution.queue.jobs');
            expect($metrics[0]['tags']['job_type'])->toBe('SendMessageJob');
            expect($metrics[0]['tags']['status'])->toBe('queued');
        });

        it('records timing for completed jobs', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackQueueJob('SendMessageJob', 'completed', 500.0);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(2);
            expect($metrics[1]['metric'])->toBe('evolution.queue.processing_time');
            expect($metrics[1]['value'])->toBe(500.0);
        });

        it('records timing for failed jobs', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackQueueJob('SendMessageJob', 'failed', 200.0);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(2);
            expect($metrics[1]['metric'])->toBe('evolution.queue.processing_time');
        });

        it('does not record timing for queued status', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackQueueJob('SendMessageJob', 'queued', 100.0);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
        });

        it('does not record timing for processing status', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackQueueJob('SendMessageJob', 'processing', 100.0);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
        });
    });

    describe('trackInstanceStatus', function () {
        it('does nothing when disabled', function () {
            $collector = new MetricsCollector(['enabled' => false]);
            $collector->trackInstanceStatus('test-instance', 'open');

            expect($collector->getMetrics())->toBe([]);
        });

        it('records instance status gauge', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackInstanceStatus('main-instance', 'connected');

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['metric'])->toBe('evolution.instances.status');
            expect($metrics[0]['type'])->toBe('gauge');
            expect($metrics[0]['tags']['instance'])->toBe('main-instance');
            expect($metrics[0]['tags']['status'])->toBe('connected');
        });
    });

    describe('trackRateLimit', function () {
        it('does nothing when disabled', function () {
            $collector = new MetricsCollector(['enabled' => false]);
            $collector->trackRateLimit('messages', 50, 100);

            expect($collector->getMetrics())->toBe([]);
        });

        it('records rate limit remaining and usage', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackRateLimit('messages', 30, 100);

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(2);
            expect($metrics[0]['metric'])->toBe('evolution.rate_limit.remaining');
            expect($metrics[0]['value'])->toBe(30);
            expect($metrics[1]['metric'])->toBe('evolution.rate_limit.usage_percent');
            expect($metrics[1]['value'])->toBe(70.0);
        });

        it('handles zero limit gracefully', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackRateLimit('messages', 0, 0);

            $metrics = $collector->getMetrics();

            expect($metrics[1]['value'])->toBe(0);  // Returns int 0 when limit is 0
        });
    });

    describe('getMetrics', function () {
        it('returns empty array when disabled', function () {
            $collector = new MetricsCollector(['enabled' => false]);

            expect($collector->getMetrics())->toBe([]);
        });

        it('returns buffer for null driver', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->increment('test.one');
            $collector->increment('test.two');

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(2);
        });
    });

    describe('flush', function () {
        it('does nothing when disabled', function () {
            $collector = new MetricsCollector(['enabled' => false]);
            $collector->flush();

            // Should not throw
            expect(true)->toBeTrue();
        });

        it('clears buffer for null driver', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->increment('test.counter');
            expect($collector->getMetrics())->toHaveCount(1);

            $collector->flush();
            expect($collector->getMetrics())->toBe([]);
        });
    });

    describe('reset', function () {
        it('clears buffer', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->increment('test.counter');
            $collector->reset();

            expect($collector->getMetrics())->toBe([]);
        });

        it('clears buffer even when disabled', function () {
            $collector = new MetricsCollector(['enabled' => false]);

            // Should not throw
            $collector->reset();
            expect(true)->toBeTrue();
        });
    });

    describe('summary', function () {
        it('returns disabled status when metrics are disabled', function () {
            $collector = new MetricsCollector(['enabled' => false]);

            $summary = $collector->summary();

            expect($summary)->toBe(['enabled' => false]);
        });

        it('returns full summary when enabled', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',  // Use null driver for unit tests
            ]);

            $collector->increment('test.counter');

            $summary = $collector->summary();

            expect($summary['enabled'])->toBeTrue();
            expect($summary['driver'])->toBe('null');
            expect($summary['buffer_size'])->toBe(1);
            expect($summary)->toHaveKey('tracking');
            expect($summary)->toHaveKey('total_metrics');
        });
    });

    describe('measure', function () {
        it('executes callback and returns result', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $result = $collector->measure('test.operation', function () {
                return 'success';
            });

            expect($result)->toBe('success');
        });

        it('records timing with success status', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->measure('test.operation', function () {
                return 'done';
            });

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['metric'])->toBe('test.operation');
            expect($metrics[0]['type'])->toBe('timing');
            expect($metrics[0]['tags']['status'])->toBe('success');
        });

        it('records timing with error status on exception', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            try {
                $collector->measure('test.operation', function () {
                    throw new \Exception('Test error');
                });
            } catch (\Exception $e) {
                // Expected
            }

            $metrics = $collector->getMetrics();

            expect($metrics)->toHaveCount(1);
            expect($metrics[0]['tags']['status'])->toBe('error');
        });

        it('re-throws exception after recording', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            expect(function () use ($collector) {
                $collector->measure('test.operation', function () {
                    throw new \RuntimeException('Test error');
                });
            })->toThrow(\RuntimeException::class, 'Test error');
        });

        it('merges custom tags with status tag', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->measure('test.operation', function () {
                return true;
            }, ['instance' => 'main']);

            $metrics = $collector->getMetrics();

            expect($metrics[0]['tags'])->toBe([
                'instance' => 'main',
                'status' => 'success',
            ]);
        });
    });

    describe('auto-flush on buffer full', function () {
        it('auto-flushes when buffer reaches 100 items', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            // Add 100 items to trigger auto-flush
            for ($i = 0; $i < 100; $i++) {
                $collector->increment("test.counter.{$i}");
            }

            // After flush, buffer should be empty
            expect($collector->getMetrics())->toBe([]);
        });
    });

    describe('constructor defaults', function () {
        it('uses default config values', function () {
            $collector = new MetricsCollector();

            expect($collector->isEnabled())->toBeFalse();

            $summary = $collector->summary();
            expect($summary)->toBe(['enabled' => false]);
        });

        it('merges provided config with defaults', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                // Note: array_merge replaces nested arrays, so we need to provide all track options
                'track' => [
                    'messages_sent' => false,
                    'messages_received' => true,
                    'api_calls' => true,
                    'api_errors' => true,
                    'webhook_events' => true,
                    'response_times' => true,
                    'queue_jobs' => true,
                ],
            ]);

            expect($collector->isEnabled())->toBeTrue();
            expect($collector->isTracking('messages_sent'))->toBeFalse();
            // Other track options should be available when explicitly set
            expect($collector->isTracking('api_calls'))->toBeTrue();
        });
    });

    describe('supported drivers', function () {
        it('supports database driver', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'database',
            ]);

            // Just verify the collector can be created with this driver
            // Actual database functionality is tested in feature tests
            expect($collector->isEnabled())->toBeTrue();
        });

        it('supports cache driver', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'cache',
            ]);

            // Just verify the collector can be created with this driver
            // Actual cache functionality is tested in feature tests
            expect($collector->isEnabled())->toBeTrue();
        });

        it('supports prometheus driver', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'prometheus',
            ]);

            $summary = $collector->summary();
            expect($summary['driver'])->toBe('prometheus');
        });

        it('supports null driver', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $summary = $collector->summary();
            expect($summary['driver'])->toBe('null');
        });
    });

});
