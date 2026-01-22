<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;

describe('WebhookPayloadDto', function () {
    describe('constructor', function () {
        it('creates a webhook payload DTO', function () {
            $dto = new WebhookPayloadDto(
                event: 'MESSAGES_UPSERT',
                instanceName: 'test-instance',
                data: ['key' => 'value']
            );

            expect($dto->event)->toBe('MESSAGES_UPSERT');
            expect($dto->instanceName)->toBe('test-instance');
            expect($dto->data)->toBe(['key' => 'value']);
        });

        it('creates with all parameters', function () {
            $dto = new WebhookPayloadDto(
                event: 'CONNECTION_UPDATE',
                instanceName: 'my-instance',
                data: ['state' => 'open'],
                webhookEvent: WebhookEvent::CONNECTION_UPDATE,
                apiKey: 'api-key-123',
                receivedAt: 1234567890
            );

            expect($dto->webhookEvent)->toBe(WebhookEvent::CONNECTION_UPDATE);
            expect($dto->apiKey)->toBe('api-key-123');
            expect($dto->receivedAt)->toBe(1234567890);
        });
    });

    describe('fromPayload', function () {
        it('creates from raw webhook payload', function () {
            $payload = [
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => ['message' => 'Hello'],
                'apiKey' => 'key-123',
            ];

            $dto = WebhookPayloadDto::fromPayload($payload);

            expect($dto->event)->toBe('MESSAGES_UPSERT');
            expect($dto->instanceName)->toBe('test-instance');
            expect($dto->webhookEvent)->toBe(WebhookEvent::MESSAGES_UPSERT);
            expect($dto->receivedAt)->toBeGreaterThan(0);
        });

        it('handles instanceName field', function () {
            $payload = [
                'event' => 'CONNECTION_UPDATE',
                'instanceName' => 'my-instance',
            ];

            $dto = WebhookPayloadDto::fromPayload($payload);

            expect($dto->instanceName)->toBe('my-instance');
        });

        it('defaults to unknown for missing event', function () {
            $payload = ['instance' => 'test'];

            $dto = WebhookPayloadDto::fromPayload($payload);

            expect($dto->event)->toBe('UNKNOWN');
            expect($dto->webhookEvent)->toBe(WebhookEvent::UNKNOWN);
        });

        it('removes event and instance from data', function () {
            $payload = [
                'event' => 'TEST',
                'instance' => 'test',
                'message' => 'Hello',
                'extra' => 'data'
            ];

            $dto = WebhookPayloadDto::fromPayload($payload);

            expect($dto->data)->not->toHaveKey('event');
            expect($dto->data)->not->toHaveKey('instance');
            expect($dto->data)->toHaveKey('message');
            expect($dto->data)->toHaveKey('extra');
        });
    });

    describe('getEventType', function () {
        it('returns the webhook event', function () {
            $dto = new WebhookPayloadDto(
                event: 'MESSAGES_UPSERT',
                instanceName: 'test',
                data: [],
                webhookEvent: WebhookEvent::MESSAGES_UPSERT
            );

            expect($dto->getEventType())->toBe(WebhookEvent::MESSAGES_UPSERT);
        });

        it('returns UNKNOWN when webhook event is null', function () {
            $dto = new WebhookPayloadDto(
                event: 'CUSTOM_EVENT',
                instanceName: 'test',
                data: []
            );

            expect($dto->getEventType())->toBe(WebhookEvent::UNKNOWN);
        });
    });

    describe('isKnownEvent', function () {
        it('returns true for known events', function () {
            $dto = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test'
            ]);

            expect($dto->isKnownEvent())->toBeTrue();
        });

        it('returns false for unknown events', function () {
            $dto = new WebhookPayloadDto(
                event: 'UNKNOWN',
                instanceName: 'test',
                data: [],
                webhookEvent: WebhookEvent::UNKNOWN
            );

            expect($dto->isKnownEvent())->toBeFalse();
        });
    });

    describe('get', function () {
        it('returns value using dot notation', function () {
            $dto = new WebhookPayloadDto(
                event: 'TEST',
                instanceName: 'test',
                data: [
                    'data' => [
                        'key' => [
                            'remoteJid' => '5511999999999@s.whatsapp.net'
                        ]
                    ]
                ]
            );

            expect($dto->get('data.key.remoteJid'))->toBe('5511999999999@s.whatsapp.net');
        });

        it('returns default for missing key', function () {
            $dto = new WebhookPayloadDto(
                event: 'TEST',
                instanceName: 'test',
                data: []
            );

            expect($dto->get('missing'))->toBeNull();
            expect($dto->get('missing', 'default'))->toBe('default');
        });
    });

    describe('has', function () {
        it('returns true when key exists', function () {
            $dto = new WebhookPayloadDto(
                event: 'TEST',
                instanceName: 'test',
                data: ['message' => 'Hello']
            );

            expect($dto->has('message'))->toBeTrue();
        });

        it('returns false when key does not exist', function () {
            $dto = new WebhookPayloadDto(
                event: 'TEST',
                instanceName: 'test',
                data: []
            );

            expect($dto->has('message'))->toBeFalse();
        });
    });

    describe('getRemoteJid', function () {
        it('returns remote JID from data.key.remoteJid', function () {
            $dto = new WebhookPayloadDto(
                event: 'MESSAGES_UPSERT',
                instanceName: 'test',
                data: [
                    'data' => [
                        'key' => [
                            'remoteJid' => '5511999999999@s.whatsapp.net'
                        ]
                    ]
                ]
            );

            expect($dto->getRemoteJid())->toBe('5511999999999@s.whatsapp.net');
        });

        it('returns null when no remote JID found', function () {
            $dto = new WebhookPayloadDto(
                event: 'TEST',
                instanceName: 'test',
                data: []
            );

            expect($dto->getRemoteJid())->toBeNull();
        });
    });

    describe('isFromGroup', function () {
        it('returns true for group messages', function () {
            $dto = new WebhookPayloadDto(
                event: 'MESSAGES_UPSERT',
                instanceName: 'test',
                data: [
                    'data' => [
                        'key' => [
                            'remoteJid' => '120363123456789012@g.us'
                        ]
                    ]
                ]
            );

            expect($dto->isFromGroup())->toBeTrue();
        });

        it('returns false for individual messages', function () {
            $dto = new WebhookPayloadDto(
                event: 'MESSAGES_UPSERT',
                instanceName: 'test',
                data: [
                    'data' => [
                        'key' => [
                            'remoteJid' => '5511999999999@s.whatsapp.net'
                        ]
                    ]
                ]
            );

            expect($dto->isFromGroup())->toBeFalse();
        });
    });

    describe('event type checks', function () {
        it('isMessageEvent returns true for message events', function () {
            $dto = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test'
            ]);

            expect($dto->isMessageEvent())->toBeTrue();
        });

        it('isConnectionEvent returns true for connection events', function () {
            $dto = WebhookPayloadDto::fromPayload([
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test'
            ]);

            expect($dto->isConnectionEvent())->toBeTrue();
        });

        it('isGroupEvent returns true for group events', function () {
            $dto = WebhookPayloadDto::fromPayload([
                'event' => 'GROUP_PARTICIPANTS_UPDATE',
                'instance' => 'test'
            ]);

            expect($dto->isGroupEvent())->toBeTrue();
        });
    });

    describe('getQrCode', function () {
        it('returns QR code from various paths', function () {
            $dto = new WebhookPayloadDto(
                event: 'QRCODE_UPDATED',
                instanceName: 'test',
                data: [
                    'data' => [
                        'qrcode' => [
                            'base64' => 'base64-encoded-qr'
                        ]
                    ]
                ]
            );

            expect($dto->getQrCode())->toBe('base64-encoded-qr');
        });
    });

    describe('toArray', function () {
        it('converts to array representation', function () {
            $dto = new WebhookPayloadDto(
                event: 'MESSAGES_UPSERT',
                instanceName: 'test',
                data: ['message' => 'Hello'],
                webhookEvent: WebhookEvent::MESSAGES_UPSERT,
                receivedAt: 1234567890
            );

            $array = $dto->toArray();

            expect($array)->toHaveKey('event');
            expect($array)->toHaveKey('instance_name');
            expect($array)->toHaveKey('webhook_event');
            expect($array)->toHaveKey('is_known_event');
            expect($array)->toHaveKey('data');
            expect($array)->toHaveKey('received_at');
            expect($array['event'])->toBe('MESSAGES_UPSERT');
            expect($array['instance_name'])->toBe('test');
        });
    });
});
