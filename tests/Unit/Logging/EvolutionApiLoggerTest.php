<?php

declare(strict_types=1);

use Illuminate\Log\LogManager;
use Lynkbyte\EvolutionApi\Logging\EvolutionApiLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Create a mock LogManager that returns a mock logger.
 */
function createMockLogManager(array &$logs = []): LogManager
{
    $mockLogger = new class($logs) implements LoggerInterface {
        public array $logged = [];
        private array $logsRef;

        public function __construct(array &$logs)
        {
            $this->logsRef = &$logs;
        }

        public function emergency(string|\Stringable $message, array $context = []): void
        {
            $this->log(LogLevel::EMERGENCY, $message, $context);
        }

        public function alert(string|\Stringable $message, array $context = []): void
        {
            $this->log(LogLevel::ALERT, $message, $context);
        }

        public function critical(string|\Stringable $message, array $context = []): void
        {
            $this->log(LogLevel::CRITICAL, $message, $context);
        }

        public function error(string|\Stringable $message, array $context = []): void
        {
            $this->log(LogLevel::ERROR, $message, $context);
        }

        public function warning(string|\Stringable $message, array $context = []): void
        {
            $this->log(LogLevel::WARNING, $message, $context);
        }

        public function notice(string|\Stringable $message, array $context = []): void
        {
            $this->log(LogLevel::NOTICE, $message, $context);
        }

        public function info(string|\Stringable $message, array $context = []): void
        {
            $this->log(LogLevel::INFO, $message, $context);
        }

        public function debug(string|\Stringable $message, array $context = []): void
        {
            $this->log(LogLevel::DEBUG, $message, $context);
        }

        public function log($level, string|\Stringable $message, array $context = []): void
        {
            $this->logged[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
            $this->logsRef[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
        }
    };

    // Create a mock LogManager
    $mockManager = new class($mockLogger) extends LogManager {
        private LoggerInterface $mockLogger;

        public function __construct(LoggerInterface $mockLogger)
        {
            $this->mockLogger = $mockLogger;
        }

        public function driver($driver = null)
        {
            return $this->mockLogger;
        }

        public function channel($channel = null)
        {
            return $this->mockLogger;
        }
    };

    return $mockManager;
}

describe('EvolutionApiLogger', function () {
    describe('constructor', function () {
        it('creates logger with default configuration', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            expect($logger->isEnabled())->toBeTrue();
            expect($logger->isEnabled('requests'))->toBeTrue();
            expect($logger->isEnabled('responses'))->toBeTrue();
            expect($logger->isEnabled('webhooks'))->toBeTrue();
        });

        it('creates logger with custom configuration', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager, [
                'enabled' => false,
                'log_requests' => false,
            ]);

            expect($logger->isEnabled())->toBeFalse();
            expect($logger->isEnabled('requests'))->toBeFalse();
        });

        it('uses specified channel', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager, [
                'channel' => 'evolution',
            ]);

            expect($logger->getLogger())->toBeInstanceOf(LoggerInterface::class);
        });
    });

    describe('isEnabled()', function () {
        it('returns false when logging is disabled', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager, ['enabled' => false]);

            expect($logger->isEnabled())->toBeFalse();
            expect($logger->isEnabled('requests'))->toBeFalse();
            expect($logger->isEnabled('responses'))->toBeFalse();
            expect($logger->isEnabled('webhooks'))->toBeFalse();
        });

        it('returns specific type status', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager, [
                'enabled' => true,
                'log_requests' => true,
                'log_responses' => false,
                'log_webhooks' => true,
            ]);

            expect($logger->isEnabled('requests'))->toBeTrue();
            expect($logger->isEnabled('responses'))->toBeFalse();
            expect($logger->isEnabled('webhooks'))->toBeTrue();
        });
    });

    describe('log()', function () {
        it('logs messages with package identifier', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->log(LogLevel::INFO, 'Test message', ['key' => 'value']);

            expect($logs)->toHaveCount(1);
            expect($logs[0]['message'])->toContain('[EvolutionApi]');
            expect($logs[0]['message'])->toContain('Test message');
            expect($logs[0]['context']['_package'])->toBe('evolution-api');
        });

        it('does not log when disabled', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager, ['enabled' => false]);

            $logger->log(LogLevel::INFO, 'Test message');

            expect($logs)->toHaveCount(0);
        });
    });

    describe('convenience methods', function () {
        it('logs debug messages', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->debug('Debug message');

            expect($logs[0]['level'])->toBe(LogLevel::DEBUG);
        });

        it('logs info messages', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->info('Info message');

            expect($logs[0]['level'])->toBe(LogLevel::INFO);
        });

        it('logs warning messages', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->warning('Warning message');

            expect($logs[0]['level'])->toBe(LogLevel::WARNING);
        });

        it('logs error messages', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->error('Error message');

            expect($logs[0]['level'])->toBe(LogLevel::ERROR);
        });
    });

    describe('logRequest()', function () {
        it('logs API requests', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->logRequest('POST', 'http://api.example.com/message/send', [
                'json' => ['number' => '5511999999999'],
            ]);

            expect($logs)->toHaveCount(1);
            expect($logs[0]['context']['method'])->toBe('POST');
            expect($logs[0]['context']['url'])->toContain('api.example.com');
        });

        it('does not log when log_requests is disabled', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager, [
                'log_requests' => false,
            ]);

            $logger->logRequest('GET', 'http://api.example.com/test');

            expect($logs)->toHaveCount(0);
        });

        it('redacts sensitive data from options', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager, [
                'redact_sensitive' => true,
            ]);

            $logger->logRequest('POST', 'http://api.example.com/test', [
                'headers' => ['Authorization' => 'Bearer secret-token'],
                'json' => ['apikey' => 'my-api-key'],
            ]);

            $options = $logs[0]['context']['options'];

            expect($options['headers']['Authorization'])->toBe('[REDACTED]');
            expect($options['json']['apikey'])->toBe('[REDACTED]');
        });
    });

    describe('logResponse()', function () {
        it('logs API responses', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->logResponse('POST', 'http://api.example.com/test', 200, ['data' => 'value'], 0.5);

            expect($logs)->toHaveCount(1);
            expect($logs[0]['context']['status_code'])->toBe(200);
            expect($logs[0]['context']['duration_ms'])->toBe(500.0);
        });

        it('does not log when log_responses is disabled', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager, [
                'log_responses' => false,
            ]);

            $logger->logResponse('GET', 'http://api.example.com/test', 200);

            expect($logs)->toHaveCount(0);
        });

        it('logs at warning level for 4xx responses', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->logResponse('POST', 'http://api.example.com/test', 400, ['error' => 'Bad Request']);

            expect($logs[0]['level'])->toBe(LogLevel::WARNING);
        });

        it('logs at error level for 5xx responses', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->logResponse('POST', 'http://api.example.com/test', 500, ['error' => 'Server Error']);

            expect($logs[0]['level'])->toBe(LogLevel::ERROR);
        });
    });

    describe('logWebhook()', function () {
        it('logs webhook events', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->logWebhook('MESSAGES_UPSERT', 'test-instance', [
                'data' => 'value',
            ]);

            expect($logs)->toHaveCount(1);
            expect($logs[0]['context']['event'])->toBe('MESSAGES_UPSERT');
            expect($logs[0]['context']['instance'])->toBe('test-instance');
        });

        it('does not log when log_webhooks is disabled', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager, [
                'log_webhooks' => false,
            ]);

            $logger->logWebhook('MESSAGES_UPSERT', 'test-instance');

            expect($logs)->toHaveCount(0);
        });
    });

    describe('logError()', function () {
        it('logs errors with exception details', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);
            $exception = new RuntimeException('Test error', 500);

            $logger->logError('Operation failed', ['operation' => 'test'], $exception);

            expect($logs)->toHaveCount(1);
            expect($logs[0]['level'])->toBe(LogLevel::ERROR);
            expect($logs[0]['context']['exception']['class'])->toBe(RuntimeException::class);
            expect($logs[0]['context']['exception']['message'])->toBe('Test error');
            expect($logs[0]['context']['exception']['code'])->toBe(500);
        });

        it('logs errors without exception', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->logError('Something went wrong', ['key' => 'value']);

            expect($logs)->toHaveCount(1);
            expect($logs[0]['level'])->toBe(LogLevel::ERROR);
            expect($logs[0]['context'])->not->toHaveKey('exception');
        });
    });

    describe('logStatusChange()', function () {
        it('logs instance status changes', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->logStatusChange('test-instance', 'connecting', 'open');

            expect($logs)->toHaveCount(1);
            expect($logs[0]['context']['instance'])->toBe('test-instance');
            expect($logs[0]['context']['old_status'])->toBe('connecting');
            expect($logs[0]['context']['new_status'])->toBe('open');
        });
    });

    describe('logMessageSend()', function () {
        it('logs successful message sends', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->logMessageSend('test-instance', '5511999999999', 'text', true, 'MSG123');

            expect($logs)->toHaveCount(1);
            expect($logs[0]['level'])->toBe(LogLevel::INFO);
            expect($logs[0]['context']['success'])->toBeTrue();
            expect($logs[0]['context']['message_id'])->toBe('MSG123');
        });

        it('logs failed message sends', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->logMessageSend('test-instance', '5511999999999', 'text', false);

            expect($logs)->toHaveCount(1);
            expect($logs[0]['level'])->toBe(LogLevel::WARNING);
            expect($logs[0]['context']['success'])->toBeFalse();
        });

        it('masks phone numbers', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->logMessageSend('test-instance', '5511999999999', 'text', true);

            expect($logs[0]['context']['recipient'])->not->toBe('5511999999999');
            expect($logs[0]['context']['recipient'])->toContain('****');
        });
    });

    describe('sensitive data redaction', function () {
        it('redacts sensitive fields recursively', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->logRequest('POST', 'http://api.example.com', [
                'data' => [
                    'user' => 'test',
                    'nested' => [
                        'apikey' => 'secret-key',
                        'token' => 'bearer-token',
                    ],
                ],
            ]);

            $options = $logs[0]['context']['options'];

            expect($options['data']['nested']['apikey'])->toBe('[REDACTED]');
            expect($options['data']['nested']['token'])->toBe('[REDACTED]');
        });

        it('redacts sensitive query parameters from URLs', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            $logger->logRequest('GET', 'http://api.example.com?apikey=secret&user=test');

            $url = $logs[0]['context']['url'];

            expect($url)->toContain('apikey=%5BREDACTED%5D');
            expect($url)->toContain('user=test');
        });

        it('does not redact when disabled', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager, [
                'redact_sensitive' => false,
            ]);

            $logger->logRequest('POST', 'http://api.example.com', [
                'json' => ['apikey' => 'secret-key'],
            ]);

            $options = $logs[0]['context']['options'];

            expect($options['json']['apikey'])->toBe('secret-key');
        });

        it('handles custom sensitive fields', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager, [
                'sensitive_fields' => ['custom_secret'],
            ]);

            $logger->logRequest('POST', 'http://api.example.com', [
                'json' => ['custom_secret' => 'my-secret'],
            ]);

            $options = $logs[0]['context']['options'];

            expect($options['json']['custom_secret'])->toBe('[REDACTED]');
        });
    });

    describe('getLogger()', function () {
        it('returns the underlying logger instance', function () {
            $logs = [];
            $logManager = createMockLogManager($logs);

            $logger = new EvolutionApiLogger($logManager);

            expect($logger->getLogger())->toBeInstanceOf(LoggerInterface::class);
        });
    });
});
