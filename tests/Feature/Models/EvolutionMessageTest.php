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

});
