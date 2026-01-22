<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Enums\InstanceStatus;
use Lynkbyte\EvolutionApi\Enums\MessageType;
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;
use Lynkbyte\EvolutionApi\Events\ConnectionUpdated;
use Lynkbyte\EvolutionApi\Events\InstanceStatusChanged;
use Lynkbyte\EvolutionApi\Events\MessageDelivered;
use Lynkbyte\EvolutionApi\Events\MessageFailed;
use Lynkbyte\EvolutionApi\Events\MessageRead;
use Lynkbyte\EvolutionApi\Events\MessageReceived;
use Lynkbyte\EvolutionApi\Events\MessageSent;
use Lynkbyte\EvolutionApi\Events\QrCodeReceived;
use Lynkbyte\EvolutionApi\Events\WebhookReceived;

describe('MessageSent', function () {
    describe('constructor', function () {
        it('creates event with all parameters', function () {
            $message = ['number' => '5511999999999', 'text' => 'Hello'];
            $response = ['key' => ['id' => 'MSG123']];

            $event = new MessageSent(
                instanceName: 'test-instance',
                messageType: 'text',
                message: $message,
                response: $response
            );

            expect($event->instanceName)->toBe('test-instance');
            expect($event->messageType)->toBe('text');
            expect($event->message)->toBe($message);
            expect($event->response)->toBe($response);
            expect($event->timestamp)->toBeInt();
        });
    });

    describe('getMessageId()', function () {
        it('extracts message ID from key.id', function () {
            $event = new MessageSent(
                instanceName: 'test',
                messageType: 'text',
                message: [],
                response: ['key' => ['id' => 'MSG_ABC123']]
            );

            expect($event->getMessageId())->toBe('MSG_ABC123');
        });

        it('extracts message ID from messageId', function () {
            $event = new MessageSent(
                instanceName: 'test',
                messageType: 'text',
                message: [],
                response: ['messageId' => 'MSG_XYZ789']
            );

            expect($event->getMessageId())->toBe('MSG_XYZ789');
        });

        it('returns null when no message ID', function () {
            $event = new MessageSent(
                instanceName: 'test',
                messageType: 'text',
                message: [],
                response: []
            );

            expect($event->getMessageId())->toBeNull();
        });
    });

    describe('getRecipient()', function () {
        it('extracts recipient from message', function () {
            $event = new MessageSent(
                instanceName: 'test',
                messageType: 'text',
                message: ['number' => '5511999999999'],
                response: []
            );

            expect($event->getRecipient())->toBe('5511999999999');
        });

        it('returns null when no number in message', function () {
            $event = new MessageSent(
                instanceName: 'test',
                messageType: 'text',
                message: [],
                response: []
            );

            expect($event->getRecipient())->toBeNull();
        });
    });

    describe('toArray()', function () {
        it('includes all event data', function () {
            $event = new MessageSent(
                instanceName: 'test',
                messageType: 'image',
                message: ['number' => '5511999999999'],
                response: ['key' => ['id' => 'MSG123']]
            );

            $array = $event->toArray();

            expect($array)->toHaveKey('instance_name', 'test');
            expect($array)->toHaveKey('message_type', 'image');
            expect($array)->toHaveKey('message_id', 'MSG123');
            expect($array)->toHaveKey('recipient', '5511999999999');
            expect($array)->toHaveKey('timestamp');
        });
    });
});

describe('MessageReceived', function () {
    describe('constructor', function () {
        it('creates event with all parameters', function () {
            $message = [
                'key' => ['id' => 'MSG123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                'message' => ['conversation' => 'Hello'],
            ];
            $sender = ['pushName' => 'John'];

            $event = new MessageReceived(
                instanceName: 'test-instance',
                message: $message,
                sender: $sender,
                messageType: MessageType::TEXT,
                isGroup: false,
                groupId: null
            );

            expect($event->instanceName)->toBe('test-instance');
            expect($event->message)->toBe($message);
            expect($event->sender)->toBe($sender);
            expect($event->messageType)->toBe(MessageType::TEXT);
            expect($event->isGroup)->toBeFalse();
        });
    });

    describe('getMessageId()', function () {
        it('extracts message ID from key.id', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: ['key' => ['id' => 'MSG_ABC']],
            );

            expect($event->getMessageId())->toBe('MSG_ABC');
        });

        it('extracts message ID from id field', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: ['id' => 'MSG_XYZ'],
            );

            expect($event->getMessageId())->toBe('MSG_XYZ');
        });
    });

    describe('getSenderNumber()', function () {
        it('extracts sender from pushName', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: [],
                sender: ['pushName' => '5511999999999']
            );

            expect($event->getSenderNumber())->toBe('5511999999999');
        });

        it('extracts sender from remoteJid', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: ['key' => ['remoteJid' => '5511999999999@s.whatsapp.net']],
            );

            expect($event->getSenderNumber())->toBe('5511999999999@s.whatsapp.net');
        });
    });

    describe('getSenderName()', function () {
        it('returns push name', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: [],
                sender: ['pushName' => 'John Doe']
            );

            expect($event->getSenderName())->toBe('John Doe');
        });
    });

    describe('getContent()', function () {
        it('extracts content from conversation', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: ['message' => ['conversation' => 'Hello World']],
            );

            expect($event->getContent())->toBe('Hello World');
        });

        it('extracts content from extendedTextMessage', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: ['message' => ['extendedTextMessage' => ['text' => 'Extended text']]],
            );

            expect($event->getContent())->toBe('Extended text');
        });

        it('extracts content from body', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: ['body' => 'Body content'],
            );

            expect($event->getContent())->toBe('Body content');
        });
    });

    describe('isFromGroup()', function () {
        it('returns true when message is from group', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: [],
                isGroup: true,
                groupId: '123456789@g.us'
            );

            expect($event->isFromGroup())->toBeTrue();
        });

        it('returns false for direct messages', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: [],
                isGroup: false
            );

            expect($event->isFromGroup())->toBeFalse();
        });
    });

    describe('isReply()', function () {
        it('returns true when message has quoted message', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: [
                    'message' => [
                        'extendedTextMessage' => [
                            'text' => 'Reply',
                            'contextInfo' => [
                                'quotedMessage' => ['conversation' => 'Original'],
                            ],
                        ],
                    ],
                ],
            );

            expect($event->isReply())->toBeTrue();
            expect($event->getQuotedMessage())->toBe(['conversation' => 'Original']);
        });

        it('returns false when not a reply', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: ['message' => ['conversation' => 'Hello']],
            );

            expect($event->isReply())->toBeFalse();
            expect($event->getQuotedMessage())->toBeNull();
        });
    });

    describe('toArray()', function () {
        it('includes all event data', function () {
            $event = new MessageReceived(
                instanceName: 'test',
                message: ['key' => ['id' => 'MSG123']],
                messageType: MessageType::TEXT,
                isGroup: true,
                groupId: '123@g.us'
            );

            $array = $event->toArray();

            expect($array)->toHaveKey('instance_name');
            expect($array)->toHaveKey('message_id');
            expect($array)->toHaveKey('message_type', 'text');
            expect($array)->toHaveKey('is_group', true);
            expect($array)->toHaveKey('group_id', '123@g.us');
            expect($array)->toHaveKey('is_reply');
            expect($array)->toHaveKey('raw_message');
        });
    });
});

describe('MessageDelivered', function () {
    describe('constructor', function () {
        it('creates event with all parameters', function () {
            $event = new MessageDelivered(
                instanceName: 'test',
                messageId: 'MSG123',
                remoteJid: '5511999999999@s.whatsapp.net',
                data: ['messageTimestamp' => 1699000000]
            );

            expect($event->instanceName)->toBe('test');
            expect($event->messageId)->toBe('MSG123');
            expect($event->remoteJid)->toBe('5511999999999@s.whatsapp.net');
        });
    });

    describe('getRecipient()', function () {
        it('returns the remote JID', function () {
            $event = new MessageDelivered(
                instanceName: 'test',
                messageId: 'MSG123',
                remoteJid: '5511999999999@s.whatsapp.net'
            );

            expect($event->getRecipient())->toBe('5511999999999@s.whatsapp.net');
        });
    });

    describe('getDeliveredAt()', function () {
        it('returns timestamp from data', function () {
            $event = new MessageDelivered(
                instanceName: 'test',
                messageId: 'MSG123',
                remoteJid: '5511999999999@s.whatsapp.net',
                data: ['messageTimestamp' => 1699000000]
            );

            expect($event->getDeliveredAt())->toBe(1699000000);
        });

        it('returns event timestamp as fallback', function () {
            $event = new MessageDelivered(
                instanceName: 'test',
                messageId: 'MSG123',
                remoteJid: '5511999999999@s.whatsapp.net'
            );

            expect($event->getDeliveredAt())->toBe($event->timestamp);
        });
    });

    describe('toArray()', function () {
        it('includes all event data', function () {
            $event = new MessageDelivered(
                instanceName: 'test',
                messageId: 'MSG123',
                remoteJid: '5511999999999@s.whatsapp.net'
            );

            $array = $event->toArray();

            expect($array)->toHaveKey('message_id', 'MSG123');
            expect($array)->toHaveKey('remote_jid', '5511999999999@s.whatsapp.net');
            expect($array)->toHaveKey('recipient');
            expect($array)->toHaveKey('delivered_at');
        });
    });
});

describe('MessageRead', function () {
    describe('constructor', function () {
        it('creates event with single message ID', function () {
            $event = new MessageRead(
                instanceName: 'test',
                messageIds: 'MSG123',
                remoteJid: '5511999999999@s.whatsapp.net'
            );

            expect($event->messageIds)->toBe('MSG123');
        });

        it('creates event with multiple message IDs', function () {
            $event = new MessageRead(
                instanceName: 'test',
                messageIds: ['MSG1', 'MSG2', 'MSG3'],
                remoteJid: '5511999999999@s.whatsapp.net'
            );

            expect($event->messageIds)->toBe(['MSG1', 'MSG2', 'MSG3']);
        });
    });

    describe('getMessageIds()', function () {
        it('returns array for single message ID', function () {
            $event = new MessageRead(
                instanceName: 'test',
                messageIds: 'MSG123',
                remoteJid: '5511999999999@s.whatsapp.net'
            );

            expect($event->getMessageIds())->toBe(['MSG123']);
        });

        it('returns array as-is for multiple message IDs', function () {
            $event = new MessageRead(
                instanceName: 'test',
                messageIds: ['MSG1', 'MSG2'],
                remoteJid: '5511999999999@s.whatsapp.net'
            );

            expect($event->getMessageIds())->toBe(['MSG1', 'MSG2']);
        });
    });

    describe('getRecipient()', function () {
        it('returns the remote JID', function () {
            $event = new MessageRead(
                instanceName: 'test',
                messageIds: 'MSG123',
                remoteJid: '5511999999999@s.whatsapp.net'
            );

            expect($event->getRecipient())->toBe('5511999999999@s.whatsapp.net');
        });
    });

    describe('getReadAt()', function () {
        it('returns timestamp from data', function () {
            $event = new MessageRead(
                instanceName: 'test',
                messageIds: 'MSG123',
                remoteJid: '5511999999999@s.whatsapp.net',
                data: ['readTimestamp' => 1699000000]
            );

            expect($event->getReadAt())->toBe(1699000000);
        });

        it('returns event timestamp as fallback', function () {
            $event = new MessageRead(
                instanceName: 'test',
                messageIds: 'MSG123',
                remoteJid: '5511999999999@s.whatsapp.net'
            );

            expect($event->getReadAt())->toBe($event->timestamp);
        });
    });

    describe('toArray()', function () {
        it('includes all event data', function () {
            $event = new MessageRead(
                instanceName: 'test',
                messageIds: ['MSG1', 'MSG2'],
                remoteJid: '5511999999999@s.whatsapp.net'
            );

            $array = $event->toArray();

            expect($array)->toHaveKey('message_ids', ['MSG1', 'MSG2']);
            expect($array)->toHaveKey('remote_jid');
            expect($array)->toHaveKey('recipient');
            expect($array)->toHaveKey('read_at');
        });
    });
});

describe('MessageFailed', function () {
    describe('constructor', function () {
        it('creates event with all parameters', function () {
            $exception = new RuntimeException('Send failed');
            $message = ['number' => '5511999999999', 'text' => 'Hello'];

            $event = new MessageFailed(
                instanceName: 'test',
                messageType: 'text',
                message: $message,
                exception: $exception
            );

            expect($event->instanceName)->toBe('test');
            expect($event->messageType)->toBe('text');
            expect($event->message)->toBe($message);
            expect($event->exception)->toBe($exception);
        });
    });

    describe('getErrorMessage()', function () {
        it('returns exception message', function () {
            $exception = new RuntimeException('Network error occurred');

            $event = new MessageFailed(
                instanceName: 'test',
                messageType: 'text',
                message: [],
                exception: $exception
            );

            expect($event->getErrorMessage())->toBe('Network error occurred');
        });
    });

    describe('getRecipient()', function () {
        it('returns recipient from message', function () {
            $event = new MessageFailed(
                instanceName: 'test',
                messageType: 'text',
                message: ['number' => '5511999999999'],
                exception: new RuntimeException('Error')
            );

            expect($event->getRecipient())->toBe('5511999999999');
        });

        it('returns null when no number in message', function () {
            $event = new MessageFailed(
                instanceName: 'test',
                messageType: 'text',
                message: [],
                exception: new RuntimeException('Error')
            );

            expect($event->getRecipient())->toBeNull();
        });
    });

    describe('toArray()', function () {
        it('includes all event data', function () {
            $exception = new RuntimeException('Failed');

            $event = new MessageFailed(
                instanceName: 'test',
                messageType: 'image',
                message: ['number' => '5511999999999'],
                exception: $exception
            );

            $array = $event->toArray();

            expect($array)->toHaveKey('message_type', 'image');
            expect($array)->toHaveKey('error', 'Failed');
            expect($array)->toHaveKey('recipient', '5511999999999');
        });
    });
});

describe('InstanceStatusChanged', function () {
    describe('constructor', function () {
        it('creates event with all parameters', function () {
            $event = new InstanceStatusChanged(
                instanceName: 'test',
                status: InstanceStatus::OPEN,
                previousStatus: InstanceStatus::CONNECTING,
                phoneNumber: '5511999999999',
                data: ['key' => 'value']
            );

            expect($event->instanceName)->toBe('test');
            expect($event->status)->toBe(InstanceStatus::OPEN);
            expect($event->previousStatus)->toBe(InstanceStatus::CONNECTING);
            expect($event->phoneNumber)->toBe('5511999999999');
        });
    });

    describe('isReady()', function () {
        it('returns true for OPEN status', function () {
            $event = new InstanceStatusChanged(
                instanceName: 'test',
                status: InstanceStatus::OPEN
            );

            expect($event->isReady())->toBeTrue();
        });

        it('returns true for CONNECTED status', function () {
            $event = new InstanceStatusChanged(
                instanceName: 'test',
                status: InstanceStatus::CONNECTED
            );

            expect($event->isReady())->toBeTrue();
        });

        it('returns false for other statuses', function () {
            $event = new InstanceStatusChanged(
                instanceName: 'test',
                status: InstanceStatus::CONNECTING
            );

            expect($event->isReady())->toBeFalse();
        });
    });

    describe('needsQrCode()', function () {
        it('returns true for QRCODE status', function () {
            $event = new InstanceStatusChanged(
                instanceName: 'test',
                status: InstanceStatus::QRCODE
            );

            expect($event->needsQrCode())->toBeTrue();
        });

        it('returns false for other statuses', function () {
            $event = new InstanceStatusChanged(
                instanceName: 'test',
                status: InstanceStatus::OPEN
            );

            expect($event->needsQrCode())->toBeFalse();
        });
    });

    describe('isConnecting()', function () {
        it('returns true for CONNECTING status', function () {
            $event = new InstanceStatusChanged(
                instanceName: 'test',
                status: InstanceStatus::CONNECTING
            );

            expect($event->isConnecting())->toBeTrue();
        });
    });

    describe('wasLoggedOut()', function () {
        it('returns true when disconnected from connected state', function () {
            $event = new InstanceStatusChanged(
                instanceName: 'test',
                status: InstanceStatus::DISCONNECTED,
                previousStatus: InstanceStatus::OPEN
            );

            expect($event->wasLoggedOut())->toBeTrue();
        });

        it('returns false when no previous status', function () {
            $event = new InstanceStatusChanged(
                instanceName: 'test',
                status: InstanceStatus::DISCONNECTED
            );

            expect($event->wasLoggedOut())->toBeFalse();
        });

        it('returns false when disconnected from non-connected state', function () {
            $event = new InstanceStatusChanged(
                instanceName: 'test',
                status: InstanceStatus::DISCONNECTED,
                previousStatus: InstanceStatus::CONNECTING
            );

            expect($event->wasLoggedOut())->toBeFalse();
        });
    });

    describe('getPhoneNumber()', function () {
        it('returns the phone number', function () {
            $event = new InstanceStatusChanged(
                instanceName: 'test',
                status: InstanceStatus::OPEN,
                phoneNumber: '5511999999999'
            );

            expect($event->getPhoneNumber())->toBe('5511999999999');
        });
    });

    describe('toArray()', function () {
        it('includes all event data', function () {
            $event = new InstanceStatusChanged(
                instanceName: 'test',
                status: InstanceStatus::OPEN,
                previousStatus: InstanceStatus::CONNECTING,
                phoneNumber: '5511999999999'
            );

            $array = $event->toArray();

            expect($array)->toHaveKey('status', 'open');
            expect($array)->toHaveKey('previous_status', 'connecting');
            expect($array)->toHaveKey('phone_number', '5511999999999');
            expect($array)->toHaveKey('is_ready', true);
            expect($array)->toHaveKey('needs_qr_code', false);
            expect($array)->toHaveKey('is_connecting', false);
        });
    });
});

describe('QrCodeReceived', function () {
    describe('constructor', function () {
        it('creates event with all parameters', function () {
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: 'qr-code-data',
                pairingCode: '1234-5678',
                attempt: 2,
                data: ['key' => 'value']
            );

            expect($event->instanceName)->toBe('test');
            expect($event->qrCode)->toBe('qr-code-data');
            expect($event->pairingCode)->toBe('1234-5678');
            expect($event->attempt)->toBe(2);
        });
    });

    describe('getQrCode()', function () {
        it('returns the QR code string', function () {
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: 'qr-data'
            );

            expect($event->getQrCode())->toBe('qr-data');
        });
    });

    describe('getPairingCode()', function () {
        it('returns pairing code when set', function () {
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: 'qr-data',
                pairingCode: 'ABCD-1234'
            );

            expect($event->getPairingCode())->toBe('ABCD-1234');
        });

        it('returns null when not set', function () {
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: 'qr-data'
            );

            expect($event->getPairingCode())->toBeNull();
        });
    });

    describe('hasPairingCode()', function () {
        it('returns true when pairing code is set', function () {
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: 'qr-data',
                pairingCode: 'CODE'
            );

            expect($event->hasPairingCode())->toBeTrue();
        });

        it('returns false when pairing code is not set', function () {
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: 'qr-data'
            );

            expect($event->hasPairingCode())->toBeFalse();
        });
    });

    describe('getAttempt()', function () {
        it('returns the attempt number', function () {
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: 'qr-data',
                attempt: 3
            );

            expect($event->getAttempt())->toBe(3);
        });
    });

    describe('isRetry()', function () {
        it('returns false for first attempt', function () {
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: 'qr-data',
                attempt: 1
            );

            expect($event->isRetry())->toBeFalse();
        });

        it('returns true for subsequent attempts', function () {
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: 'qr-data',
                attempt: 2
            );

            expect($event->isRetry())->toBeTrue();
        });
    });

    describe('getQrCodeDataUri()', function () {
        it('returns data URI as-is', function () {
            $dataUri = 'data:image/png;base64,iVBORw0KGgo=';
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: $dataUri
            );

            expect($event->getQrCodeDataUri())->toBe($dataUri);
        });

        it('wraps base64 data with data URI prefix', function () {
            $base64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: $base64
            );

            expect($event->getQrCodeDataUri())->toBe('data:image/png;base64,'.$base64);
        });

        it('returns raw QR string for generation libraries', function () {
            $qrString = '2@ABCDEfghIJklmnOPQRstuvWXyz1234';
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: $qrString
            );

            expect($event->getQrCodeDataUri())->toBe($qrString);
        });
    });

    describe('toArray()', function () {
        it('includes all event data', function () {
            $event = new QrCodeReceived(
                instanceName: 'test',
                qrCode: 'qr-data',
                pairingCode: 'CODE',
                attempt: 2
            );

            $array = $event->toArray();

            expect($array)->toHaveKey('qr_code', 'qr-data');
            expect($array)->toHaveKey('pairing_code', 'CODE');
            expect($array)->toHaveKey('attempt', 2);
            expect($array)->toHaveKey('is_retry', true);
        });
    });
});

describe('ConnectionUpdated', function () {
    describe('constructor', function () {
        it('creates event with all parameters', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::OPEN,
                previousStatus: InstanceStatus::CONNECTING,
                data: ['key' => 'value']
            );

            expect($event->instanceName)->toBe('test');
            expect($event->status)->toBe(InstanceStatus::OPEN);
            expect($event->previousStatus)->toBe(InstanceStatus::CONNECTING);
        });
    });

    describe('isConnected()', function () {
        it('returns true for OPEN status', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::OPEN
            );

            expect($event->isConnected())->toBeTrue();
        });

        it('returns true for CONNECTED status', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::CONNECTED
            );

            expect($event->isConnected())->toBeTrue();
        });

        it('returns false for disconnected status', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::DISCONNECTED
            );

            expect($event->isConnected())->toBeFalse();
        });
    });

    describe('isDisconnected()', function () {
        it('returns true for CLOSE status', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::CLOSE
            );

            expect($event->isDisconnected())->toBeTrue();
        });

        it('returns true for DISCONNECTED status', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::DISCONNECTED
            );

            expect($event->isDisconnected())->toBeTrue();
        });
    });

    describe('wasDisconnected()', function () {
        it('returns true when connection was lost', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::DISCONNECTED,
                previousStatus: InstanceStatus::OPEN
            );

            expect($event->wasDisconnected())->toBeTrue();
        });

        it('returns false when no previous status', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::DISCONNECTED
            );

            expect($event->wasDisconnected())->toBeFalse();
        });

        it('returns false when was not connected before', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::DISCONNECTED,
                previousStatus: InstanceStatus::CONNECTING
            );

            expect($event->wasDisconnected())->toBeFalse();
        });
    });

    describe('wasReconnected()', function () {
        it('returns true when reconnected from disconnected', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::OPEN,
                previousStatus: InstanceStatus::DISCONNECTED
            );

            expect($event->wasReconnected())->toBeTrue();
        });

        it('returns true when connected from connecting', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::OPEN,
                previousStatus: InstanceStatus::CONNECTING
            );

            expect($event->wasReconnected())->toBeTrue();
        });

        it('returns false when no previous status', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::OPEN
            );

            expect($event->wasReconnected())->toBeFalse();
        });
    });

    describe('toArray()', function () {
        it('includes all event data', function () {
            $event = new ConnectionUpdated(
                instanceName: 'test',
                status: InstanceStatus::OPEN,
                previousStatus: InstanceStatus::CONNECTING
            );

            $array = $event->toArray();

            expect($array)->toHaveKey('status', 'open');
            expect($array)->toHaveKey('previous_status', 'connecting');
            expect($array)->toHaveKey('is_connected', true);
            expect($array)->toHaveKey('is_disconnected', false);
        });
    });
});

describe('WebhookReceived', function () {
    describe('constructor', function () {
        it('creates event with all parameters', function () {
            $payload = ['event' => 'MESSAGES_UPSERT', 'data' => []];

            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'MESSAGES_UPSERT',
                payload: $payload,
                webhookEvent: WebhookEvent::MESSAGES_UPSERT
            );

            expect($event->instanceName)->toBe('test');
            expect($event->event)->toBe('MESSAGES_UPSERT');
            expect($event->payload)->toBe($payload);
            expect($event->webhookEvent)->toBe(WebhookEvent::MESSAGES_UPSERT);
        });
    });

    describe('getEventType()', function () {
        it('returns the event string', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'CUSTOM_EVENT',
                payload: []
            );

            expect($event->getEventType())->toBe('CUSTOM_EVENT');
        });
    });

    describe('getWebhookEvent()', function () {
        it('returns the webhook event enum', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'MESSAGES_UPSERT',
                payload: [],
                webhookEvent: WebhookEvent::MESSAGES_UPSERT
            );

            expect($event->getWebhookEvent())->toBe(WebhookEvent::MESSAGES_UPSERT);
        });
    });

    describe('isKnownEvent()', function () {
        it('returns true when webhook event is set', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'MESSAGES_UPSERT',
                payload: [],
                webhookEvent: WebhookEvent::MESSAGES_UPSERT
            );

            expect($event->isKnownEvent())->toBeTrue();
        });

        it('returns false for unknown events', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'CUSTOM_EVENT',
                payload: []
            );

            expect($event->isKnownEvent())->toBeFalse();
        });
    });

    describe('get()', function () {
        it('retrieves value using dot notation', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'TEST',
                payload: [
                    'data' => [
                        'nested' => [
                            'value' => 'found',
                        ],
                    ],
                ]
            );

            expect($event->get('data.nested.value'))->toBe('found');
        });

        it('returns default for missing keys', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'TEST',
                payload: []
            );

            expect($event->get('missing.key', 'default'))->toBe('default');
        });
    });

    describe('has()', function () {
        it('returns true for existing keys', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'TEST',
                payload: ['data' => 'value']
            );

            expect($event->has('data'))->toBeTrue();
        });

        it('returns false for missing keys', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'TEST',
                payload: []
            );

            expect($event->has('missing'))->toBeFalse();
        });
    });

    describe('isMessageEvent()', function () {
        it('returns true for message events', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'MESSAGES_UPSERT',
                payload: [],
                webhookEvent: WebhookEvent::MESSAGES_UPSERT
            );

            expect($event->isMessageEvent())->toBeTrue();
        });

        it('returns false for non-message events', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'CONNECTION_UPDATE',
                payload: [],
                webhookEvent: WebhookEvent::CONNECTION_UPDATE
            );

            expect($event->isMessageEvent())->toBeFalse();
        });
    });

    describe('isConnectionEvent()', function () {
        it('returns true for connection events', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'CONNECTION_UPDATE',
                payload: [],
                webhookEvent: WebhookEvent::CONNECTION_UPDATE
            );

            expect($event->isConnectionEvent())->toBeTrue();
        });

        it('returns true for QR code events', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'QRCODE_UPDATED',
                payload: [],
                webhookEvent: WebhookEvent::QRCODE_UPDATED
            );

            expect($event->isConnectionEvent())->toBeTrue();
        });
    });

    describe('isGroupEvent()', function () {
        it('returns true for group events', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'GROUPS_UPSERT',
                payload: [],
                webhookEvent: WebhookEvent::GROUPS_UPSERT
            );

            expect($event->isGroupEvent())->toBeTrue();
        });
    });

    describe('toArray()', function () {
        it('includes all event data', function () {
            $event = new WebhookReceived(
                instanceName: 'test',
                event: 'MESSAGES_UPSERT',
                payload: ['data' => 'value'],
                webhookEvent: WebhookEvent::MESSAGES_UPSERT
            );

            $array = $event->toArray();

            expect($array)->toHaveKey('event', 'MESSAGES_UPSERT');
            expect($array)->toHaveKey('webhook_event', 'MESSAGES_UPSERT');
            expect($array)->toHaveKey('is_known_event', true);
            expect($array)->toHaveKey('payload');
        });
    });
});
