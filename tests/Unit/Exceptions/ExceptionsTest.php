<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Exceptions\AuthenticationException;
use Lynkbyte\EvolutionApi\Exceptions\ConnectionException;
use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;
use Lynkbyte\EvolutionApi\Exceptions\InstanceNotFoundException;
use Lynkbyte\EvolutionApi\Exceptions\MessageException;
use Lynkbyte\EvolutionApi\Exceptions\RateLimitException;
use Lynkbyte\EvolutionApi\Exceptions\ValidationException;
use Lynkbyte\EvolutionApi\Exceptions\WebhookException;

describe('EvolutionApiException', function () {
    describe('constructor', function () {
        it('creates exception with all parameters', function () {
            $previous = new RuntimeException('Previous error');
            $exception = new EvolutionApiException(
                message: 'Test error',
                code: 500,
                previous: $previous,
                responseData: ['error' => 'data'],
                statusCode: 500,
                instanceName: 'test-instance'
            );

            expect($exception->getMessage())->toBe('Test error');
            expect($exception->getCode())->toBe(500);
            expect($exception->getPrevious())->toBe($previous);
            expect($exception->getResponseData())->toBe(['error' => 'data']);
            expect($exception->getStatusCode())->toBe(500);
            expect($exception->getInstanceName())->toBe('test-instance');
        });

        it('creates exception with default values', function () {
            $exception = new EvolutionApiException;

            expect($exception->getMessage())->toBe('');
            expect($exception->getCode())->toBe(0);
            expect($exception->getPrevious())->toBeNull();
            expect($exception->getResponseData())->toBeNull();
            expect($exception->getStatusCode())->toBeNull();
            expect($exception->getInstanceName())->toBeNull();
        });
    });

    describe('fromResponse()', function () {
        it('creates exception from response with message key', function () {
            $response = ['message' => 'API error message', 'code' => 'ERR001'];

            $exception = EvolutionApiException::fromResponse($response, 400, 'my-instance');

            expect($exception->getMessage())->toBe('API error message');
            expect($exception->getCode())->toBe(400);
            expect($exception->getStatusCode())->toBe(400);
            expect($exception->getInstanceName())->toBe('my-instance');
            expect($exception->getResponseData())->toBe($response);
        });

        it('creates exception from response with error key', function () {
            $response = ['error' => 'Something went wrong'];

            $exception = EvolutionApiException::fromResponse($response, 500);

            expect($exception->getMessage())->toBe('Something went wrong');
        });

        it('uses default message when no message or error in response', function () {
            $response = ['data' => 'some data'];

            $exception = EvolutionApiException::fromResponse($response, 500);

            expect($exception->getMessage())->toBe('Unknown error');
        });
    });

    describe('toArray()', function () {
        it('converts exception to array', function () {
            $exception = new EvolutionApiException(
                message: 'Test error',
                code: 400,
                statusCode: 400,
                instanceName: 'test',
                responseData: ['key' => 'value']
            );

            $array = $exception->toArray();

            expect($array)->toHaveKey('message', 'Test error');
            expect($array)->toHaveKey('code', 400);
            expect($array)->toHaveKey('status_code', 400);
            expect($array)->toHaveKey('instance_name', 'test');
            expect($array)->toHaveKey('response_data', ['key' => 'value']);
        });
    });

    describe('context()', function () {
        it('returns same as toArray for logging', function () {
            $exception = new EvolutionApiException(
                message: 'Test',
                code: 500
            );

            expect($exception->context())->toBe($exception->toArray());
        });
    });
});

describe('AuthenticationException', function () {
    describe('invalidApiKey()', function () {
        it('creates exception for invalid API key', function () {
            $exception = AuthenticationException::invalidApiKey('test-instance');

            expect($exception)->toBeInstanceOf(AuthenticationException::class);
            expect($exception)->toBeInstanceOf(EvolutionApiException::class);
            expect($exception->getMessage())->toBe('Invalid API key provided');
            expect($exception->getCode())->toBe(401);
            expect($exception->getStatusCode())->toBe(401);
            expect($exception->getInstanceName())->toBe('test-instance');
        });

        it('creates exception without instance name', function () {
            $exception = AuthenticationException::invalidApiKey();

            expect($exception->getInstanceName())->toBeNull();
        });
    });

    describe('missingApiKey()', function () {
        it('creates exception for missing API key', function () {
            $exception = AuthenticationException::missingApiKey();

            expect($exception->getMessage())->toBe('API key is required but not configured');
            expect($exception->getCode())->toBe(401);
        });
    });

    describe('expiredApiKey()', function () {
        it('creates exception for expired API key', function () {
            $exception = AuthenticationException::expiredApiKey('my-instance');

            expect($exception->getMessage())->toBe('API key has expired');
            expect($exception->getCode())->toBe(401);
            expect($exception->getInstanceName())->toBe('my-instance');
        });
    });
});

describe('ConnectionException', function () {
    describe('constructor', function () {
        it('creates exception with all parameters', function () {
            $previous = new RuntimeException('Network error');
            $exception = new ConnectionException(
                message: 'Connection failed',
                connectionName: 'production',
                url: 'http://api.example.com',
                instanceName: 'test-instance',
                previous: $previous
            );

            expect($exception->getMessage())->toBe('Connection failed');
            expect($exception->getConnectionName())->toBe('production');
            expect($exception->getUrl())->toBe('http://api.example.com');
            expect($exception->getInstanceName())->toBe('test-instance');
            expect($exception->getPrevious())->toBe($previous);
        });
    });

    describe('timeout()', function () {
        it('creates exception for connection timeout', function () {
            $exception = ConnectionException::timeout('http://api.example.com', 30, 'prod');

            expect($exception->getMessage())->toContain('timed out');
            expect($exception->getMessage())->toContain('30 seconds');
            expect($exception->getUrl())->toBe('http://api.example.com');
            expect($exception->getConnectionName())->toBe('prod');
        });
    });

    describe('refused()', function () {
        it('creates exception for connection refused', function () {
            $exception = ConnectionException::refused('http://localhost:8080', 'local');

            expect($exception->getMessage())->toContain('refused');
            expect($exception->getUrl())->toBe('http://localhost:8080');
            expect($exception->getConnectionName())->toBe('local');
        });
    });

    describe('dnsFailure()', function () {
        it('creates exception for DNS failure', function () {
            $exception = ConnectionException::dnsFailure('http://invalid-host.example.com');

            expect($exception->getMessage())->toContain('Could not resolve host');
        });
    });

    describe('sslError()', function () {
        it('creates exception for SSL error', function () {
            $exception = ConnectionException::sslError(
                'https://api.example.com',
                'Certificate expired',
                'production'
            );

            expect($exception->getMessage())->toContain('SSL certificate error');
            expect($exception->getMessage())->toContain('Certificate expired');
        });
    });

    describe('unreachable()', function () {
        it('creates exception for unreachable server', function () {
            $exception = ConnectionException::unreachable('http://api.example.com');

            expect($exception->getMessage())->toContain('unreachable');
        });
    });

    describe('toArray()', function () {
        it('includes connection name and URL', function () {
            $exception = new ConnectionException(
                message: 'Error',
                connectionName: 'prod',
                url: 'http://example.com'
            );

            $array = $exception->toArray();

            expect($array)->toHaveKey('connection_name', 'prod');
            expect($array)->toHaveKey('url', 'http://example.com');
        });
    });
});

describe('InstanceNotFoundException', function () {
    describe('constructor', function () {
        it('creates exception with instance name', function () {
            $exception = new InstanceNotFoundException(instanceName: 'my-instance');

            expect($exception->getMessage())->toBe("Instance 'my-instance' not found");
            expect($exception->getCode())->toBe(404);
            expect($exception->getStatusCode())->toBe(404);
            expect($exception->getInstanceName())->toBe('my-instance');
        });

        it('creates exception with custom message', function () {
            $exception = new InstanceNotFoundException(message: 'Custom message');

            expect($exception->getMessage())->toBe('Custom message');
        });

        it('creates exception with default message', function () {
            $exception = new InstanceNotFoundException;

            expect($exception->getMessage())->toBe('Instance not found');
        });
    });

    describe('notFound()', function () {
        it('creates exception for instance not found', function () {
            $exception = InstanceNotFoundException::notFound('test-instance');

            expect($exception->getMessage())->toBe("Instance 'test-instance' not found");
            expect($exception->getInstanceName())->toBe('test-instance');
        });
    });
});

describe('MessageException', function () {
    describe('constructor', function () {
        it('creates exception with all parameters', function () {
            $previous = new RuntimeException('Underlying error');
            $exception = new MessageException(
                message: 'Send failed',
                messageId: 'MSG123',
                recipientNumber: '5511999999999',
                messageType: 'text',
                instanceName: 'test',
                statusCode: 400,
                previous: $previous
            );

            expect($exception->getMessage())->toBe('Send failed');
            expect($exception->getMessageId())->toBe('MSG123');
            expect($exception->getRecipientNumber())->toBe('5511999999999');
            expect($exception->getMessageType())->toBe('text');
            expect($exception->getInstanceName())->toBe('test');
            expect($exception->getStatusCode())->toBe(400);
            expect($exception->getPrevious())->toBe($previous);
        });

        it('creates exception with default values', function () {
            $exception = new MessageException;

            expect($exception->getMessage())->toBe('Message operation failed');
            expect($exception->getMessageId())->toBeNull();
            expect($exception->getRecipientNumber())->toBeNull();
            expect($exception->getMessageType())->toBeNull();
        });
    });

    describe('sendFailed()', function () {
        it('creates exception for send failure', function () {
            $exception = MessageException::sendFailed(
                recipientNumber: '5511999999999',
                reason: 'Number not on WhatsApp',
                messageType: 'text',
                instanceName: 'my-instance'
            );

            expect($exception->getMessage())->toContain('Failed to send message');
            expect($exception->getMessage())->toContain('5511999999999');
            expect($exception->getMessage())->toContain('Number not on WhatsApp');
            expect($exception->getRecipientNumber())->toBe('5511999999999');
            expect($exception->getMessageType())->toBe('text');
        });
    });

    describe('invalidRecipient()', function () {
        it('creates exception for invalid recipient', function () {
            $exception = MessageException::invalidRecipient('invalid-number');

            expect($exception->getMessage())->toContain('Invalid recipient');
            expect($exception->getMessage())->toContain('invalid-number');
            expect($exception->getRecipientNumber())->toBe('invalid-number');
            expect($exception->getStatusCode())->toBe(400);
        });
    });

    describe('notWhatsApp()', function () {
        it('creates exception for non-WhatsApp number', function () {
            $exception = MessageException::notWhatsApp('5511123456789');

            expect($exception->getMessage())->toContain('not registered on WhatsApp');
            expect($exception->getRecipientNumber())->toBe('5511123456789');
            expect($exception->getStatusCode())->toBe(400);
        });
    });

    describe('mediaUploadFailed()', function () {
        it('creates exception for media upload failure', function () {
            $exception = MessageException::mediaUploadFailed('File too large', 'my-instance');

            expect($exception->getMessage())->toContain('Media upload failed');
            expect($exception->getMessage())->toContain('File too large');
            expect($exception->getMessageType())->toBe('media');
            expect($exception->getInstanceName())->toBe('my-instance');
        });
    });

    describe('toArray()', function () {
        it('includes message-specific fields', function () {
            $exception = new MessageException(
                message: 'Error',
                messageId: 'MSG123',
                recipientNumber: '5511999999999',
                messageType: 'image'
            );

            $array = $exception->toArray();

            expect($array)->toHaveKey('message_id', 'MSG123');
            expect($array)->toHaveKey('recipient_number', '5511999999999');
            expect($array)->toHaveKey('message_type', 'image');
        });
    });
});

describe('RateLimitException', function () {
    describe('constructor', function () {
        it('creates exception with all parameters', function () {
            $exception = new RateLimitException(
                message: 'Too many requests',
                retryAfter: 120,
                limitType: 'messages',
                instanceName: 'test-instance'
            );

            expect($exception->getMessage())->toBe('Too many requests');
            expect($exception->getCode())->toBe(429);
            expect($exception->getStatusCode())->toBe(429);
            expect($exception->getRetryAfter())->toBe(120);
            expect($exception->getLimitType())->toBe('messages');
            expect($exception->getInstanceName())->toBe('test-instance');
        });

        it('creates exception with default values', function () {
            $exception = new RateLimitException;

            expect($exception->getMessage())->toBe('Rate limit exceeded');
            expect($exception->getRetryAfter())->toBe(60);
            expect($exception->getLimitType())->toBe('default');
        });
    });

    describe('apiLimitExceeded()', function () {
        it('creates exception for API limit exceeded', function () {
            $exception = RateLimitException::apiLimitExceeded(30, 'my-instance');

            expect($exception->getMessage())->toContain('API rate limit exceeded');
            expect($exception->getMessage())->toContain('30 seconds');
            expect($exception->getRetryAfter())->toBe(30);
            expect($exception->getLimitType())->toBe('api');
        });
    });

    describe('messageLimitExceeded()', function () {
        it('creates exception for message limit exceeded', function () {
            $exception = RateLimitException::messageLimitExceeded(45);

            expect($exception->getMessage())->toContain('Message rate limit exceeded');
            expect($exception->getRetryAfter())->toBe(45);
            expect($exception->getLimitType())->toBe('messages');
        });
    });

    describe('mediaLimitExceeded()', function () {
        it('creates exception for media limit exceeded', function () {
            $exception = RateLimitException::mediaLimitExceeded(90);

            expect($exception->getMessage())->toContain('Media upload rate limit exceeded');
            expect($exception->getRetryAfter())->toBe(90);
            expect($exception->getLimitType())->toBe('media');
        });
    });

    describe('toArray()', function () {
        it('includes rate limit specific fields', function () {
            $exception = new RateLimitException(
                retryAfter: 60,
                limitType: 'api'
            );

            $array = $exception->toArray();

            expect($array)->toHaveKey('retry_after', 60);
            expect($array)->toHaveKey('limit_type', 'api');
        });
    });
});

describe('ValidationException', function () {
    describe('constructor', function () {
        it('creates exception with errors array', function () {
            $errors = [
                'number' => ['The number field is required.', 'The number must be valid.'],
                'message' => ['The message field is required.'],
            ];

            $exception = new ValidationException($errors, 'test-instance');

            expect($exception->getErrors())->toBe($errors);
            expect($exception->getCode())->toBe(422);
            expect($exception->getStatusCode())->toBe(422);
            expect($exception->getInstanceName())->toBe('test-instance');
            expect($exception->getMessage())->toContain('Validation failed');
            expect($exception->getMessage())->toContain('number:');
            expect($exception->getMessage())->toContain('message:');
        });
    });

    describe('withErrors()', function () {
        it('creates exception with errors array', function () {
            $errors = ['field' => ['Error 1']];

            $exception = ValidationException::withErrors($errors);

            expect($exception->getErrors())->toBe($errors);
        });
    });

    describe('forField()', function () {
        it('creates exception for single field error', function () {
            $exception = ValidationException::forField('email', 'Invalid email format');

            expect($exception->getErrors())->toBe(['email' => ['Invalid email format']]);
            expect($exception->getMessage())->toContain('email: Invalid email format');
        });
    });

    describe('requiredField()', function () {
        it('creates exception for required field', function () {
            $exception = ValidationException::requiredField('phone_number');

            expect($exception->getErrors())->toBe(['phone_number' => ['The phone_number field is required.']]);
        });
    });

    describe('invalidFormat()', function () {
        it('creates exception for invalid format', function () {
            $exception = ValidationException::invalidFormat('phone', 'phone number');

            expect($exception->getErrors())->toBe(['phone' => ['The phone must be a valid phone number.']]);
        });
    });

    describe('toArray()', function () {
        it('includes errors array', function () {
            $errors = ['field' => ['error']];
            $exception = new ValidationException($errors);

            $array = $exception->toArray();

            expect($array)->toHaveKey('errors', $errors);
        });
    });
});

describe('WebhookException', function () {
    describe('constructor', function () {
        it('creates exception with all parameters', function () {
            $previous = new RuntimeException('Handler error');
            $payload = ['event' => 'MESSAGES_UPSERT', 'data' => []];

            $exception = new WebhookException(
                message: 'Webhook failed',
                eventType: 'MESSAGES_UPSERT',
                payload: $payload,
                instanceName: 'test',
                previous: $previous
            );

            expect($exception->getMessage())->toBe('Webhook failed');
            expect($exception->getEventType())->toBe('MESSAGES_UPSERT');
            expect($exception->getPayload())->toBe($payload);
            expect($exception->getInstanceName())->toBe('test');
            expect($exception->getPrevious())->toBe($previous);
        });

        it('creates exception with default values', function () {
            $exception = new WebhookException;

            expect($exception->getMessage())->toBe('Webhook processing failed');
            expect($exception->getEventType())->toBeNull();
            expect($exception->getPayload())->toBeNull();
        });
    });

    describe('invalidSignature()', function () {
        it('creates exception for invalid signature', function () {
            $exception = WebhookException::invalidSignature('my-instance');

            expect($exception->getMessage())->toContain('signature verification failed');
            expect($exception->getInstanceName())->toBe('my-instance');
        });
    });

    describe('invalidPayload()', function () {
        it('creates exception for invalid payload', function () {
            $payload = ['malformed' => 'data'];

            $exception = WebhookException::invalidPayload($payload, 'test');

            expect($exception->getMessage())->toBe('Invalid webhook payload');
            expect($exception->getPayload())->toBe($payload);
            expect($exception->getInstanceName())->toBe('test');
        });
    });

    describe('unknownEvent()', function () {
        it('creates exception for unknown event', function () {
            $exception = WebhookException::unknownEvent('UNKNOWN_EVENT', ['data' => 'value']);

            expect($exception->getMessage())->toContain('Unknown webhook event');
            expect($exception->getMessage())->toContain('UNKNOWN_EVENT');
            expect($exception->getEventType())->toBe('UNKNOWN_EVENT');
        });
    });

    describe('handlerFailed()', function () {
        it('creates exception for handler failure', function () {
            $previous = new RuntimeException('Database error');

            $exception = WebhookException::handlerFailed(
                eventType: 'MESSAGES_UPSERT',
                previous: $previous,
                payload: ['event' => 'MESSAGES_UPSERT'],
                instanceName: 'test'
            );

            expect($exception->getMessage())->toContain('Webhook handler failed');
            expect($exception->getMessage())->toContain('MESSAGES_UPSERT');
            expect($exception->getMessage())->toContain('Database error');
            expect($exception->getPrevious())->toBe($previous);
        });
    });

    describe('processingFailed()', function () {
        it('creates exception for processing failure', function () {
            $previous = new RuntimeException('Parse error');

            $exception = WebhookException::processingFailed('MESSAGES_UPDATE', $previous);

            expect($exception->getMessage())->toContain('Failed to process webhook event');
            expect($exception->getMessage())->toContain('MESSAGES_UPDATE');
            expect($exception->getMessage())->toContain('Parse error');
            expect($exception->getEventType())->toBe('MESSAGES_UPDATE');
        });
    });

    describe('toArray()', function () {
        it('includes webhook specific fields', function () {
            $payload = ['event' => 'TEST'];
            $exception = new WebhookException(
                eventType: 'MESSAGES_UPSERT',
                payload: $payload
            );

            $array = $exception->toArray();

            expect($array)->toHaveKey('event_type', 'MESSAGES_UPSERT');
            expect($array)->toHaveKey('payload', $payload);
        });
    });
});

describe('Exception Hierarchy', function () {
    it('all exceptions extend EvolutionApiException', function () {
        expect(new AuthenticationException('test'))->toBeInstanceOf(EvolutionApiException::class);
        expect(new ConnectionException('test'))->toBeInstanceOf(EvolutionApiException::class);
        expect(new InstanceNotFoundException)->toBeInstanceOf(EvolutionApiException::class);
        expect(new MessageException)->toBeInstanceOf(EvolutionApiException::class);
        expect(new RateLimitException)->toBeInstanceOf(EvolutionApiException::class);
        expect(new ValidationException([]))->toBeInstanceOf(EvolutionApiException::class);
        expect(new WebhookException)->toBeInstanceOf(EvolutionApiException::class);
    });

    it('all exceptions extend base Exception', function () {
        expect(new EvolutionApiException)->toBeInstanceOf(Exception::class);
        expect(new AuthenticationException('test'))->toBeInstanceOf(Exception::class);
        expect(new ConnectionException('test'))->toBeInstanceOf(Exception::class);
    });

    it('exceptions can be caught by base exception type', function () {
        $caught = false;

        try {
            throw AuthenticationException::invalidApiKey();
        } catch (EvolutionApiException $e) {
            $caught = true;
            expect($e)->toBeInstanceOf(AuthenticationException::class);
        }

        expect($caught)->toBeTrue();
    });
});
