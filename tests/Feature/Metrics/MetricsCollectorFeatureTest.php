<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Lynkbyte\EvolutionApi\Metrics\MetricsCollector;
use Lynkbyte\EvolutionApi\Tests\TestCase;

uses(TestCase::class);

describe('MetricsCollector Feature Tests', function () {

    describe('cache driver', function () {
        beforeEach(function () {
            Cache::flush();
        });

        it('flushes metrics to cache', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'cache',
            ]);

            $collector->increment('test.counter', 5, ['tag' => 'value']);
            $collector->increment('test.counter', 3, ['tag' => 'value']);
            $collector->flush();

            $cached = Cache::get('evolution_api_metrics');

            expect($cached)->toBeArray();
            expect($cached)->not->toBeEmpty();
        });

        it('retrieves metrics from cache', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'cache',
            ]);

            $collector->increment('evolution.test.metric', 10, ['instance' => 'main']);
            $collector->flush();

            $metrics = $collector->getMetrics();

            expect($metrics)->toBeArray();
            expect($metrics)->not->toBeEmpty();
        });

        it('retrieves metrics from cache with prefix filter', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'cache',
            ]);

            $collector->increment('evolution.messages.sent', 1);
            $collector->increment('evolution.api.calls', 1);
            $collector->increment('other.metric', 1);
            $collector->flush();

            $metrics = $collector->getMetrics('evolution.messages');

            expect($metrics)->toBeArray();
            // Should only contain metrics starting with 'evolution.messages'
            foreach (array_keys($metrics) as $key) {
                expect($key)->toStartWith('evolution.messages');
            }
        });

        it('resets cache metrics', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'cache',
            ]);

            $collector->increment('test.counter', 5);
            $collector->flush();

            expect(Cache::get('evolution_api_metrics'))->not->toBeEmpty();

            $collector->reset();

            expect(Cache::get('evolution_api_metrics'))->toBeNull();
        });

        it('handles counter metrics correctly', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'cache',
            ]);

            $collector->increment('test.counter', 5, ['tag' => 'a']);
            $collector->increment('test.counter', 3, ['tag' => 'a']);
            $collector->flush();

            $metrics = $collector->getMetrics();
            $key = 'test.counter:tag=a';

            expect($metrics[$key]['value'])->toBe(8);
            expect($metrics[$key]['count'])->toBe(2);
        });

        it('handles gauge metrics correctly', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'cache',
            ]);

            $collector->gauge('test.gauge', 50, ['tag' => 'b']);
            $collector->gauge('test.gauge', 75, ['tag' => 'b']);
            $collector->flush();

            $metrics = $collector->getMetrics();
            $key = 'test.gauge:tag=b';

            // Gauge should have latest value
            expect($metrics[$key]['value'])->toBe(75);
        });

        it('handles timing/histogram metrics with averaging', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'cache',
            ]);

            $collector->timing('test.timing', 100.0, ['endpoint' => 'api']);
            $collector->timing('test.timing', 200.0, ['endpoint' => 'api']);
            $collector->flush();

            $metrics = $collector->getMetrics();
            $key = 'test.timing:endpoint=api';

            // Should be average: (100 + 200) / 2 = 150
            expect($metrics[$key]['value'])->toBe(150.0);
            expect($metrics[$key]['count'])->toBe(2);
        });

        it('builds metric key with sorted tags', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'cache',
            ]);

            // Tags in different order should produce same key
            $collector->increment('test.metric', 1, ['z' => '1', 'a' => '2']);
            $collector->flush();

            $metrics = $collector->getMetrics();

            // Key should have tags in alphabetical order
            expect($metrics)->toHaveKey('test.metric:a=2&z=1');
        });
    });

    describe('normalizeEndpoint', function () {
        it('removes query strings from endpoints', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $collector->trackApiCall('/api/messages?page=1&limit=50', 'GET', 200);

            $metrics = $collector->getMetrics();

            // Query string should be removed
            expect($metrics[0]['tags']['endpoint'])->not->toContain('?');
            expect($metrics[0]['tags']['endpoint'])->not->toContain('page=1');
        });
    });

    describe('buffer management', function () {
        it('includes timestamp in recorded metrics', function () {
            $collector = new MetricsCollector([
                'enabled' => true,
                'driver' => 'null',
            ]);

            $beforeTime = microtime(true);
            $collector->increment('test.counter');
            $afterTime = microtime(true);

            $metrics = $collector->getMetrics();

            expect($metrics[0]['timestamp'])->toBeGreaterThanOrEqual($beforeTime);
            expect($metrics[0]['timestamp'])->toBeLessThanOrEqual($afterTime);
        });
    });

});
