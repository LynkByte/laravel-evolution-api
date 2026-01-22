<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lynkbyte\EvolutionApi\Enums\MessageStatus;
use Lynkbyte\EvolutionApi\Enums\MessageType;
use Lynkbyte\EvolutionApi\Models\EvolutionMessage;

describe('EvolutionMessage Model', function () {

    uses(RefreshDatabase::class);

    describe('fillable attributes', function () {
        it('has correct fillable attributes', function () {
            $message = new EvolutionMessage([
                'message_id' => 'msg-123',
                'instance_name' => 'test-instance',
                'remote_jid' => '5511999999999@s.whatsapp.net',
                'from_me' => true,
                'message_type' => 'text',
                'status' => 'sent',
                'content' => 'Hello World',
                'media' => ['url' => 'https://example.com/media.jpg'],
                'payload' => ['key' => 'value'],
                'response' => ['id' => 'msg-123'],
                'error_message' => null,
                'retry_count' => 0,
            ]);

            expect($message->message_id)->toBe('msg-123');
            expect($message->instance_name)->toBe('test-instance');
            expect($message->remote_jid)->toBe('5511999999999@s.whatsapp.net');
            expect($message->from_me)->toBeTrue();
            expect($message->message_type)->toBe('text');
            expect($message->status)->toBe('sent');
            expect($message->content)->toBe('Hello World');
            expect($message->media)->toBe(['url' => 'https://example.com/media.jpg']);
            expect($message->payload)->toBe(['key' => 'value']);
            expect($message->response)->toBe(['id' => 'msg-123']);
        });
    });

    describe('casts', function () {
        it('casts from_me to boolean', function () {
            $message = new EvolutionMessage(['from_me' => 1]);

            expect($message->from_me)->toBeBool();
        });

        it('casts media to array', function () {
            $message = new EvolutionMessage;
            $message->media = ['url' => 'https://example.com'];

            expect($message->media)->toBeArray();
        });

        it('casts payload to array', function () {
            $message = new EvolutionMessage;
            $message->payload = ['key' => 'value'];

            expect($message->payload)->toBeArray();
        });

        it('casts response to array', function () {
            $message = new EvolutionMessage;
            $message->response = ['id' => 'msg-123'];

            expect($message->response)->toBeArray();
        });

        it('casts retry_count to integer', function () {
            $message = new EvolutionMessage(['retry_count' => '5']);

            expect($message->retry_count)->toBeInt();
        });
    });

    describe('getMessageTypeEnum', function () {
        it('returns MessageType enum for text', function () {
            $message = new EvolutionMessage(['message_type' => 'text']);

            $type = $message->getMessageTypeEnum();

            expect($type)->toBe(MessageType::TEXT);
        });

        it('returns MessageType enum for image', function () {
            $message = new EvolutionMessage(['message_type' => 'image']);

            $type = $message->getMessageTypeEnum();

            expect($type)->toBe(MessageType::IMAGE);
        });

        it('returns MessageType enum for audio', function () {
            $message = new EvolutionMessage(['message_type' => 'audio']);

            $type = $message->getMessageTypeEnum();

            expect($type)->toBe(MessageType::AUDIO);
        });
    });

    describe('getStatusEnum', function () {
        it('returns MessageStatus enum for pending', function () {
            $message = new EvolutionMessage(['status' => 'pending']);

            $status = $message->getStatusEnum();

            expect($status)->toBe(MessageStatus::PENDING);
        });

        it('returns MessageStatus enum for sent', function () {
            $message = new EvolutionMessage(['status' => 'sent']);

            $status = $message->getStatusEnum();

            expect($status)->toBe(MessageStatus::SENT);
        });

        it('returns MessageStatus enum for delivered', function () {
            $message = new EvolutionMessage(['status' => 'delivered']);

            $status = $message->getStatusEnum();

            expect($status)->toBe(MessageStatus::DELIVERED);
        });

        it('returns MessageStatus enum for read', function () {
            $message = new EvolutionMessage(['status' => 'read']);

            $status = $message->getStatusEnum();

            expect($status)->toBe(MessageStatus::READ);
        });

        it('returns MessageStatus enum for failed', function () {
            $message = new EvolutionMessage(['status' => 'failed']);

            $status = $message->getStatusEnum();

            expect($status)->toBe(MessageStatus::FAILED);
        });
    });

    describe('status checks', function () {
        describe('isSent', function () {
            it('returns true for sent status', function () {
                $message = new EvolutionMessage(['status' => 'sent']);

                expect($message->isSent())->toBeTrue();
            });

            it('returns false for pending status', function () {
                $message = new EvolutionMessage(['status' => 'pending']);

                expect($message->isSent())->toBeFalse();
            });
        });

        describe('isDelivered', function () {
            it('returns true for delivered status', function () {
                $message = new EvolutionMessage(['status' => 'delivered']);

                expect($message->isDelivered())->toBeTrue();
            });

            it('returns false for sent status', function () {
                $message = new EvolutionMessage(['status' => 'sent']);

                expect($message->isDelivered())->toBeFalse();
            });
        });

        describe('isRead', function () {
            it('returns true for read status', function () {
                $message = new EvolutionMessage(['status' => 'read']);

                expect($message->isRead())->toBeTrue();
            });

            it('returns false for delivered status', function () {
                $message = new EvolutionMessage(['status' => 'delivered']);

                expect($message->isRead())->toBeFalse();
            });
        });

        describe('isFailed', function () {
            it('returns true for failed status', function () {
                $message = new EvolutionMessage(['status' => 'failed']);

                expect($message->isFailed())->toBeTrue();
            });

            it('returns false for sent status', function () {
                $message = new EvolutionMessage(['status' => 'sent']);

                expect($message->isFailed())->toBeFalse();
            });
        });

        describe('isPending', function () {
            it('returns true for pending status', function () {
                $message = new EvolutionMessage(['status' => 'pending']);

                expect($message->isPending())->toBeTrue();
            });

            it('returns false for sent status', function () {
                $message = new EvolutionMessage(['status' => 'sent']);

                expect($message->isPending())->toBeFalse();
            });
        });
    });

    describe('relationships', function () {
        it('defines instance relationship', function () {
            $message = new EvolutionMessage;

            expect($message->instance())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        });
    });

    describe('scopes', function () {
        it('has forInstance scope', function () {
            $query = EvolutionMessage::forInstance('test-instance');

            expect($query->toSql())->toContain('instance_name');
        });

        it('has withStatus scope', function () {
            $query = EvolutionMessage::withStatus('sent');

            expect($query->toSql())->toContain('status');
        });

        it('has pending scope', function () {
            $query = EvolutionMessage::pending();

            expect($query->toSql())->toContain('status');
        });

        it('has failed scope', function () {
            $query = EvolutionMessage::failed();

            expect($query->toSql())->toContain('status');
        });

        it('has retryable scope', function () {
            $query = EvolutionMessage::retryable(3);

            expect($query->toSql())->toContain('status');
            expect($query->toSql())->toContain('retry_count');
        });

        it('has outgoing scope', function () {
            $query = EvolutionMessage::outgoing();

            expect($query->toSql())->toContain('from_me');
        });

        it('has incoming scope', function () {
            $query = EvolutionMessage::incoming();

            expect($query->toSql())->toContain('from_me');
        });
    });

    describe('table configuration', function () {
        it('uses correct table name', function () {
            $message = new EvolutionMessage;

            expect($message->getTable())->toBe('evolution_messages');
        });
    });

    describe('markAsSent', function () {
        it('updates status to sent and sets sent_at timestamp', function () {
            $message = EvolutionMessage::create([
                'message_id' => 'msg-123',
                'instance_name' => 'test-instance',
                'remote_jid' => '5511999999999@s.whatsapp.net',
                'from_me' => true,
                'message_type' => 'text',
                'status' => 'pending',
                'content' => 'Hello',
            ]);

            $result = $message->markAsSent(['id' => 'response-123']);

            expect($result)->toBeTrue();
            expect($message->status)->toBe('sent');
            expect($message->sent_at)->not->toBeNull();
            expect($message->response)->toBe(['id' => 'response-123']);

            // Verify database was updated
            $message->refresh();
            expect($message->status)->toBe('sent');
        });

        it('marks as sent without response', function () {
            $message = EvolutionMessage::create([
                'message_id' => 'msg-456',
                'instance_name' => 'test-instance',
                'remote_jid' => '5511999999999@s.whatsapp.net',
                'from_me' => true,
                'message_type' => 'text',
                'status' => 'pending',
            ]);

            $result = $message->markAsSent();

            expect($result)->toBeTrue();
            expect($message->status)->toBe('sent');
            expect($message->response)->toBe([]);
        });
    });

    describe('markAsDelivered', function () {
        it('updates status to delivered and sets delivered_at timestamp', function () {
            $message = EvolutionMessage::create([
                'message_id' => 'msg-789',
                'instance_name' => 'test-instance',
                'remote_jid' => '5511999999999@s.whatsapp.net',
                'from_me' => true,
                'message_type' => 'text',
                'status' => 'sent',
            ]);

            $result = $message->markAsDelivered();

            expect($result)->toBeTrue();
            expect($message->status)->toBe('delivered');
            expect($message->delivered_at)->not->toBeNull();

            // Verify database was updated
            $message->refresh();
            expect($message->status)->toBe('delivered');
        });
    });

    describe('markAsRead', function () {
        it('updates status to read and sets read_at timestamp', function () {
            $message = EvolutionMessage::create([
                'message_id' => 'msg-101',
                'instance_name' => 'test-instance',
                'remote_jid' => '5511999999999@s.whatsapp.net',
                'from_me' => true,
                'message_type' => 'text',
                'status' => 'delivered',
            ]);

            $result = $message->markAsRead();

            expect($result)->toBeTrue();
            expect($message->status)->toBe('read');
            expect($message->read_at)->not->toBeNull();

            // Verify database was updated
            $message->refresh();
            expect($message->status)->toBe('read');
        });
    });

    describe('markAsFailed', function () {
        it('updates status to failed and sets error message and failed_at timestamp', function () {
            $message = EvolutionMessage::create([
                'message_id' => 'msg-102',
                'instance_name' => 'test-instance',
                'remote_jid' => '5511999999999@s.whatsapp.net',
                'from_me' => true,
                'message_type' => 'text',
                'status' => 'pending',
            ]);

            $result = $message->markAsFailed('Connection timeout');

            expect($result)->toBeTrue();
            expect($message->status)->toBe('failed');
            expect($message->failed_at)->not->toBeNull();
            expect($message->error_message)->toBe('Connection timeout');

            // Verify database was updated
            $message->refresh();
            expect($message->status)->toBe('failed');
            expect($message->error_message)->toBe('Connection timeout');
        });
    });

    describe('incrementRetry', function () {
        it('increments retry_count by one', function () {
            $message = EvolutionMessage::create([
                'message_id' => 'msg-103',
                'instance_name' => 'test-instance',
                'remote_jid' => '5511999999999@s.whatsapp.net',
                'from_me' => true,
                'message_type' => 'text',
                'status' => 'failed',
                'retry_count' => 0,
            ]);

            $result = $message->incrementRetry();

            expect($result)->toBeTrue();
            expect($message->retry_count)->toBe(1);

            // Verify database was updated
            $message->refresh();
            expect($message->retry_count)->toBe(1);
        });

        it('increments retry_count multiple times', function () {
            $message = EvolutionMessage::create([
                'message_id' => 'msg-104',
                'instance_name' => 'test-instance',
                'remote_jid' => '5511999999999@s.whatsapp.net',
                'from_me' => true,
                'message_type' => 'text',
                'status' => 'failed',
                'retry_count' => 2,
            ]);

            $message->incrementRetry();
            $message->incrementRetry();

            expect($message->retry_count)->toBe(4);
        });
    });

    describe('findByMessageId', function () {
        it('finds message by message_id and instance_name', function () {
            EvolutionMessage::create([
                'message_id' => 'unique-msg-id',
                'instance_name' => 'test-instance',
                'remote_jid' => '5511999999999@s.whatsapp.net',
                'from_me' => true,
                'message_type' => 'text',
                'status' => 'sent',
            ]);

            $found = EvolutionMessage::findByMessageId('unique-msg-id', 'test-instance');

            expect($found)->not->toBeNull();
            expect($found->message_id)->toBe('unique-msg-id');
            expect($found->instance_name)->toBe('test-instance');
        });

        it('returns null when message does not exist', function () {
            $found = EvolutionMessage::findByMessageId('nonexistent', 'test-instance');

            expect($found)->toBeNull();
        });

        it('returns null when instance_name does not match', function () {
            EvolutionMessage::create([
                'message_id' => 'another-msg-id',
                'instance_name' => 'instance-a',
                'remote_jid' => '5511999999999@s.whatsapp.net',
                'from_me' => true,
                'message_type' => 'text',
                'status' => 'sent',
            ]);

            $found = EvolutionMessage::findByMessageId('another-msg-id', 'instance-b');

            expect($found)->toBeNull();
        });
    });

    describe('isSent with sent_at', function () {
        it('returns true when sent_at is set even if status is not sent', function () {
            $message = new EvolutionMessage([
                'status' => 'pending',
                'sent_at' => now(),
            ]);

            expect($message->isSent())->toBeTrue();
        });
    });

    describe('isDelivered with delivered_at', function () {
        it('returns true when delivered_at is set even if status is not delivered', function () {
            $message = new EvolutionMessage([
                'status' => 'sent',
                'delivered_at' => now(),
            ]);

            expect($message->isDelivered())->toBeTrue();
        });
    });

    describe('isRead with read_at', function () {
        it('returns true when read_at is set even if status is not read', function () {
            $message = new EvolutionMessage([
                'status' => 'delivered',
                'read_at' => now(),
            ]);

            expect($message->isRead())->toBeTrue();
        });
    });

    describe('isFailed with failed_at', function () {
        it('returns true when failed_at is set even if status is not failed', function () {
            $message = new EvolutionMessage([
                'status' => 'pending',
                'failed_at' => now(),
            ]);

            expect($message->isFailed())->toBeTrue();
        });
    });

});
