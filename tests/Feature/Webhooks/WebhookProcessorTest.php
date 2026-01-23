<?php

declare(strict_types=1);

use Illuminate\Contracts\Events\Dispatcher;
use Lynkbyte\EvolutionApi\Contracts\WebhookHandlerInterface;
use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;
use Lynkbyte\EvolutionApi\Events\ConnectionUpdated;
use Lynkbyte\EvolutionApi\Events\InstanceStatusChanged;
use Lynkbyte\EvolutionApi\Events\MessageDelivered;
use Lynkbyte\EvolutionApi\Events\MessageRead;
use Lynkbyte\EvolutionApi\Events\MessageReceived;
use Lynkbyte\EvolutionApi\Events\MessageSent;
use Lynkbyte\EvolutionApi\Events\QrCodeReceived;
use Lynkbyte\EvolutionApi\Events\WebhookReceived;
use Lynkbyte\EvolutionApi\Exceptions\WebhookException;
use Lynkbyte\EvolutionApi\Webhooks\WebhookProcessor;
use Psr\Log\LoggerInterface;

describe('WebhookProcessor', function () {

    beforeEach(function () {
        $this->events = Mockery::mock(Dispatcher::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('info')->byDefault();
        $this->logger->shouldReceive('error')->byDefault();

        $this->processor = new WebhookProcessor($this->events, $this->logger);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('process', function () {
        it('processes webhook payload and dispatches generic event', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => ['key' => ['id' => 'msg-123']],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(MessageReceived::class))
                ->once();

            $this->processor->process($payload);
        });

        it('logs webhook processing', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ];

            $this->logger->shouldReceive('info')
                ->with('Processing webhook', Mockery::on(function ($context) {
                    return $context['event'] === 'MESSAGES_UPSERT' &&
                        $context['instance'] === 'test-instance';
                }))
                ->once();

            $this->events->shouldReceive('dispatch')->byDefault();

            $this->processor->process($payload);
        });

        it('throws WebhookException on processing failure', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ];

            $this->events->shouldReceive('dispatch')
                ->andThrow(new \RuntimeException('Event dispatch failed'));

            $this->processor->process($payload);
        })->throws(WebhookException::class);
    });

    describe('message events', function () {
        it('handles MESSAGES_UPSERT event', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['conversation' => 'Hello'],
                    'pushName' => 'John',
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->instanceName === 'test-instance';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('handles MESSAGES_UPDATE event with delivered status', function () {
            $payload = [
                'event' => 'MESSAGES_UPDATE',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'status' => 3,
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(MessageDelivered::class))
                ->once();

            $this->processor->process($payload);
        });

        it('handles MESSAGES_UPDATE event with read status', function () {
            $payload = [
                'event' => 'MESSAGES_UPDATE',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'status' => 4,
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(MessageRead::class))
                ->once();

            $this->processor->process($payload);
        });

        it('handles SEND_MESSAGE event', function () {
            $payload = [
                'event' => 'SEND_MESSAGE',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123'],
                    'message' => ['conversation' => 'Hello'],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(MessageSent::class))
                ->once();

            $this->processor->process($payload);
        });
    });

    describe('connection events', function () {
        it('handles CONNECTION_UPDATE event', function () {
            $payload = [
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test-instance',
                'data' => [
                    'state' => 'open',
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(ConnectionUpdated::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(InstanceStatusChanged::class))
                ->once();

            $this->processor->process($payload);
        });

        it('handles QRCODE_UPDATED event', function () {
            $payload = [
                'event' => 'QRCODE_UPDATED',
                'instance' => 'test-instance',
                'data' => [
                    'qrcode' => [
                        'base64' => 'data:image/png;base64,ABC123',
                    ],
                    'count' => 1,
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof QrCodeReceived &&
                        $event->instanceName === 'test-instance' &&
                        $event->attempt === 1;
                }))
                ->once();

            $this->processor->process($payload);
        });
    });

    describe('custom handlers', function () {
        it('registers and calls custom handler', function () {
            $handler = Mockery::mock(WebhookHandlerInterface::class);
            $handler->shouldReceive('handle')
                ->with(Mockery::type(WebhookPayloadDto::class))
                ->once();

            $this->processor->registerHandler('MESSAGES_UPSERT', $handler);

            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ];

            $this->events->shouldReceive('dispatch')->byDefault();

            $this->processor->process($payload);
        });

        it('registers and calls wildcard handler', function () {
            $handler = Mockery::mock(WebhookHandlerInterface::class);
            $handler->shouldReceive('handle')
                ->with(Mockery::type(WebhookPayloadDto::class))
                ->once();

            $this->processor->registerWildcardHandler($handler);

            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ];

            $this->events->shouldReceive('dispatch')->byDefault();

            $this->processor->process($payload);
        });

        it('removes registered handler', function () {
            $handler = Mockery::mock(WebhookHandlerInterface::class);
            $handler->shouldNotReceive('handle');

            $this->processor->registerHandler('MESSAGES_UPSERT', $handler);
            $this->processor->removeHandler('MESSAGES_UPSERT');

            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ];

            $this->events->shouldReceive('dispatch')->byDefault();

            $this->processor->process($payload);
        });
    });

    describe('event dispatching control', function () {
        it('disables event dispatching', function () {
            $this->events->shouldNotReceive('dispatch');

            $this->processor->disableEvents();

            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ];

            $this->processor->process($payload);
        });

        it('enables event dispatching', function () {
            $this->processor->disableEvents();
            $this->processor->enableEvents();

            $this->events->shouldReceive('dispatch')->atLeast()->once();

            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ];

            $this->processor->process($payload);
        });

        it('reports events enabled status', function () {
            expect($this->processor->eventsEnabled())->toBeTrue();

            $this->processor->disableEvents();
            expect($this->processor->eventsEnabled())->toBeFalse();

            $this->processor->enableEvents();
            expect($this->processor->eventsEnabled())->toBeTrue();
        });
    });

    describe('message type detection', function () {
        it('detects text message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['conversation' => 'Hello'],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'text';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects image message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['imageMessage' => ['url' => 'https://example.com/image.jpg']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'image';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects audio message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['audioMessage' => ['url' => 'https://example.com/audio.mp3']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'audio';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects document message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['documentMessage' => ['url' => 'https://example.com/doc.pdf']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'document';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects location message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['locationMessage' => ['degreesLatitude' => -23.5505]],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'location';
                }))
                ->once();

            $this->processor->process($payload);
        });
    });

    describe('connection state mapping', function () {
        it('maps open state to OPEN status', function () {
            $payload = [
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test-instance',
                'data' => ['state' => 'open'],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof ConnectionUpdated &&
                        $event->status->value === 'open';
                }))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(InstanceStatusChanged::class))
                ->once();

            $this->processor->process($payload);
        });

        it('maps close state to CLOSE status', function () {
            $payload = [
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test-instance',
                'data' => ['state' => 'close'],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof ConnectionUpdated &&
                        $event->status->value === 'close';
                }))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(InstanceStatusChanged::class))
                ->once();

            $this->processor->process($payload);
        });
    });

    describe('setLogger', function () {
        it('sets custom logger', function () {
            $customLogger = Mockery::mock(LoggerInterface::class);
            $customLogger->shouldReceive('info')
                ->with('Processing webhook', Mockery::any())
                ->once();

            $this->processor->setLogger($customLogger);

            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ];

            $this->events->shouldReceive('dispatch')->byDefault();

            $this->processor->process($payload);
        });
    });

    describe('constructor', function () {
        it('creates processor with null logger (uses NullLogger)', function () {
            $events = Mockery::mock(Dispatcher::class);
            $processor = new WebhookProcessor($events, null);

            $events->shouldReceive('dispatch')->byDefault();

            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ];

            // Should not throw - NullLogger silently ignores logs
            $processor->process($payload);

            expect($processor)->toBeInstanceOf(WebhookProcessor::class);
        });
    });

    describe('additional message type detection', function () {
        it('detects video message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['videoMessage' => ['url' => 'https://example.com/video.mp4']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'video';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects sticker message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['stickerMessage' => ['url' => 'https://example.com/sticker.webp']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'sticker';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects contact message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['contactMessage' => ['displayName' => 'John Doe']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'contact';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects contacts array message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['contactsArrayMessage' => ['contacts' => []]],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'contact';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects reaction message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['reactionMessage' => ['text' => '👍']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'reaction';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects poll message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['pollCreationMessage' => ['name' => 'Poll Question']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'poll';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects list message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['listMessage' => ['title' => 'Options']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'list';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects list response message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['listResponseMessage' => ['selectedRowId' => '1']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'list';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects button message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['buttonsMessage' => ['contentText' => 'Choose']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'button';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects button response message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['buttonsResponseMessage' => ['selectedButtonId' => 'btn1']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'button';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects template message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['templateMessage' => ['hydratedTemplate' => []]],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'template';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('detects extended text message', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['extendedTextMessage' => ['text' => 'Hello with link']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType->value === 'text';
                }))
                ->once();

            $this->processor->process($payload);
        });

        it('returns null for unknown message type', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'message' => ['unknownMessage' => ['data' => 'test']],
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof MessageReceived &&
                        $event->messageType === null;
                }))
                ->once();

            $this->processor->process($payload);
        });
    });

    describe('additional connection state mapping', function () {
        it('maps connecting state to CONNECTING status', function () {
            $payload = [
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test-instance',
                'data' => ['state' => 'connecting'],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof ConnectionUpdated &&
                        $event->status->value === 'connecting';
                }))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(InstanceStatusChanged::class))
                ->once();

            $this->processor->process($payload);
        });

        it('maps qrcode state to QRCODE status', function () {
            $payload = [
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test-instance',
                'data' => ['state' => 'qrcode'],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof ConnectionUpdated &&
                        $event->status->value === 'qrcode';
                }))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(InstanceStatusChanged::class))
                ->once();

            $this->processor->process($payload);
        });

        it('maps qr state to QRCODE status', function () {
            $payload = [
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test-instance',
                'data' => ['state' => 'qr'],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof ConnectionUpdated &&
                        $event->status->value === 'qrcode';
                }))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(InstanceStatusChanged::class))
                ->once();

            $this->processor->process($payload);
        });

        it('maps connected state to OPEN status', function () {
            $payload = [
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test-instance',
                'data' => ['state' => 'connected'],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof ConnectionUpdated &&
                        $event->status->value === 'open';
                }))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(InstanceStatusChanged::class))
                ->once();

            $this->processor->process($payload);
        });

        it('maps disconnected state to CLOSE status', function () {
            $payload = [
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test-instance',
                'data' => ['state' => 'disconnected'],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof ConnectionUpdated &&
                        $event->status->value === 'close';
                }))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(InstanceStatusChanged::class))
                ->once();

            $this->processor->process($payload);
        });

        it('maps closed state to CLOSE status', function () {
            $payload = [
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test-instance',
                'data' => ['state' => 'closed'],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof ConnectionUpdated &&
                        $event->status->value === 'close';
                }))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(InstanceStatusChanged::class))
                ->once();

            $this->processor->process($payload);
        });

        it('maps unknown state to UNKNOWN status', function () {
            $payload = [
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test-instance',
                'data' => ['state' => 'some_unknown_state'],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof ConnectionUpdated &&
                        $event->status->value === 'unknown';
                }))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(InstanceStatusChanged::class))
                ->once();

            $this->processor->process($payload);
        });

        it('does not dispatch events when connection state is null', function () {
            $payload = [
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test-instance',
                'data' => [],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            // ConnectionUpdated and InstanceStatusChanged should NOT be dispatched
            $this->events->shouldNotReceive('dispatch')
                ->with(Mockery::type(ConnectionUpdated::class));

            $this->processor->process($payload);
        });
    });

    describe('message update edge cases', function () {
        it('handles DELIVERY_ACK string status', function () {
            $payload = [
                'event' => 'MESSAGES_UPDATE',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'status' => 'DELIVERY_ACK',
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(MessageDelivered::class))
                ->once();

            $this->processor->process($payload);
        });

        it('handles READ string status', function () {
            $payload = [
                'event' => 'MESSAGES_UPDATE',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                    'status' => 'READ',
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(MessageRead::class))
                ->once();

            $this->processor->process($payload);
        });

        it('does not dispatch events when messageId is null', function () {
            $payload = [
                'event' => 'MESSAGES_UPDATE',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['remoteJid' => '5511999999999@s.whatsapp.net'],
                    'status' => 3,
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            // MessageDelivered should NOT be dispatched when messageId is null
            $this->events->shouldNotReceive('dispatch')
                ->with(Mockery::type(MessageDelivered::class));

            $this->processor->process($payload);
        });

        it('does not dispatch events when remoteJid is null', function () {
            $payload = [
                'event' => 'MESSAGES_UPDATE',
                'instance' => 'test-instance',
                'data' => [
                    'key' => ['id' => 'msg-123'],
                    'status' => 4,
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            // MessageRead should NOT be dispatched when remoteJid is null
            $this->events->shouldNotReceive('dispatch')
                ->with(Mockery::type(MessageRead::class));

            $this->processor->process($payload);
        });
    });

    describe('QR code edge cases', function () {
        it('does not dispatch event when qrCode is null', function () {
            $payload = [
                'event' => 'QRCODE_UPDATED',
                'instance' => 'test-instance',
                'data' => [
                    'count' => 1,
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            // QrCodeReceived should NOT be dispatched when qrCode is null
            $this->events->shouldNotReceive('dispatch')
                ->with(Mockery::type(QrCodeReceived::class));

            $this->processor->process($payload);
        });

        it('handles pairing code in QR code event', function () {
            $payload = [
                'event' => 'QRCODE_UPDATED',
                'instance' => 'test-instance',
                'data' => [
                    'qrcode' => [
                        'base64' => 'data:image/png;base64,ABC123',
                    ],
                    'pairingCode' => '12345678',
                    'count' => 2,
                ],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::on(function ($event) {
                    return $event instanceof QrCodeReceived &&
                        $event->pairingCode === '12345678' &&
                        $event->attempt === 2;
                }))
                ->once();

            $this->processor->process($payload);
        });
    });

    describe('handler combinations', function () {
        it('calls both event-specific and wildcard handlers', function () {
            $specificHandler = Mockery::mock(WebhookHandlerInterface::class);
            $specificHandler->shouldReceive('handle')
                ->with(Mockery::type(WebhookPayloadDto::class))
                ->once();

            $wildcardHandler = Mockery::mock(WebhookHandlerInterface::class);
            $wildcardHandler->shouldReceive('handle')
                ->with(Mockery::type(WebhookPayloadDto::class))
                ->once();

            $this->processor->registerHandler('MESSAGES_UPSERT', $specificHandler);
            $this->processor->registerWildcardHandler($wildcardHandler);

            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ];

            $this->events->shouldReceive('dispatch')->byDefault();

            $this->processor->process($payload);
        });
    });

    describe('unhandled events', function () {
        it('processes unhandled event types without error', function () {
            $payload = [
                'event' => 'UNKNOWN_EVENT',
                'instance' => 'test-instance',
                'data' => ['some' => 'data'],
            ];

            $this->events->shouldReceive('dispatch')
                ->with(Mockery::type(WebhookReceived::class))
                ->once();

            // Should complete without throwing
            $this->processor->process($payload);

            expect(true)->toBeTrue();
        });
    });

});
