<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Enums\MessageStatus;

describe('MessageStatus Enum', function () {
    describe('cases', function () {
        it('has all expected cases', function () {
            $cases = MessageStatus::cases();

            expect($cases)->toHaveCount(8);
            expect(array_map(fn ($case) => $case->value, $cases))->toContain(
                'pending',
                'sent',
                'delivered',
                'read',
                'played',
                'failed',
                'deleted',
                'unknown'
            );
        });

        it('can be created from value', function () {
            expect(MessageStatus::from('pending'))->toBe(MessageStatus::PENDING);
            expect(MessageStatus::from('sent'))->toBe(MessageStatus::SENT);
            expect(MessageStatus::from('delivered'))->toBe(MessageStatus::DELIVERED);
            expect(MessageStatus::from('read'))->toBe(MessageStatus::READ);
            expect(MessageStatus::from('played'))->toBe(MessageStatus::PLAYED);
            expect(MessageStatus::from('failed'))->toBe(MessageStatus::FAILED);
            expect(MessageStatus::from('deleted'))->toBe(MessageStatus::DELETED);
            expect(MessageStatus::from('unknown'))->toBe(MessageStatus::UNKNOWN);
        });

        it('throws exception for invalid value', function () {
            MessageStatus::from('invalid');
        })->throws(ValueError::class);

        it('can try from value without throwing', function () {
            expect(MessageStatus::tryFrom('sent'))->toBe(MessageStatus::SENT);
            expect(MessageStatus::tryFrom('invalid'))->toBeNull();
        });
    });

    describe('isSent', function () {
        it('returns true for sent statuses', function () {
            expect(MessageStatus::SENT->isSent())->toBeTrue();
            expect(MessageStatus::DELIVERED->isSent())->toBeTrue();
            expect(MessageStatus::READ->isSent())->toBeTrue();
            expect(MessageStatus::PLAYED->isSent())->toBeTrue();
        });

        it('returns false for non-sent statuses', function () {
            expect(MessageStatus::PENDING->isSent())->toBeFalse();
            expect(MessageStatus::FAILED->isSent())->toBeFalse();
            expect(MessageStatus::DELETED->isSent())->toBeFalse();
            expect(MessageStatus::UNKNOWN->isSent())->toBeFalse();
        });
    });

    describe('isDelivered', function () {
        it('returns true for delivered statuses', function () {
            expect(MessageStatus::DELIVERED->isDelivered())->toBeTrue();
            expect(MessageStatus::READ->isDelivered())->toBeTrue();
            expect(MessageStatus::PLAYED->isDelivered())->toBeTrue();
        });

        it('returns false for non-delivered statuses', function () {
            expect(MessageStatus::PENDING->isDelivered())->toBeFalse();
            expect(MessageStatus::SENT->isDelivered())->toBeFalse();
            expect(MessageStatus::FAILED->isDelivered())->toBeFalse();
            expect(MessageStatus::DELETED->isDelivered())->toBeFalse();
            expect(MessageStatus::UNKNOWN->isDelivered())->toBeFalse();
        });
    });

    describe('isRead', function () {
        it('returns true for read statuses', function () {
            expect(MessageStatus::READ->isRead())->toBeTrue();
            expect(MessageStatus::PLAYED->isRead())->toBeTrue();
        });

        it('returns false for non-read statuses', function () {
            expect(MessageStatus::PENDING->isRead())->toBeFalse();
            expect(MessageStatus::SENT->isRead())->toBeFalse();
            expect(MessageStatus::DELIVERED->isRead())->toBeFalse();
            expect(MessageStatus::FAILED->isRead())->toBeFalse();
            expect(MessageStatus::DELETED->isRead())->toBeFalse();
            expect(MessageStatus::UNKNOWN->isRead())->toBeFalse();
        });
    });

    describe('isFailed', function () {
        it('returns true only for failed status', function () {
            expect(MessageStatus::FAILED->isFailed())->toBeTrue();
        });

        it('returns false for all other statuses', function () {
            expect(MessageStatus::PENDING->isFailed())->toBeFalse();
            expect(MessageStatus::SENT->isFailed())->toBeFalse();
            expect(MessageStatus::DELIVERED->isFailed())->toBeFalse();
            expect(MessageStatus::READ->isFailed())->toBeFalse();
            expect(MessageStatus::PLAYED->isFailed())->toBeFalse();
            expect(MessageStatus::DELETED->isFailed())->toBeFalse();
            expect(MessageStatus::UNKNOWN->isFailed())->toBeFalse();
        });
    });

    describe('label', function () {
        it('returns human-readable labels', function () {
            expect(MessageStatus::PENDING->label())->toBe('Pending');
            expect(MessageStatus::SENT->label())->toBe('Sent');
            expect(MessageStatus::DELIVERED->label())->toBe('Delivered');
            expect(MessageStatus::READ->label())->toBe('Read');
            expect(MessageStatus::PLAYED->label())->toBe('Played');
            expect(MessageStatus::FAILED->label())->toBe('Failed');
            expect(MessageStatus::DELETED->label())->toBe('Deleted');
            expect(MessageStatus::UNKNOWN->label())->toBe('Unknown');
        });
    });

    describe('fromApi', function () {
        describe('with integer values', function () {
            it('maps integer 0 to PENDING', function () {
                expect(MessageStatus::fromApi(0))->toBe(MessageStatus::PENDING);
            });

            it('maps integer 1 to SENT', function () {
                expect(MessageStatus::fromApi(1))->toBe(MessageStatus::SENT);
            });

            it('maps integer 2 to DELIVERED', function () {
                expect(MessageStatus::fromApi(2))->toBe(MessageStatus::DELIVERED);
            });

            it('maps integer 3 to READ', function () {
                expect(MessageStatus::fromApi(3))->toBe(MessageStatus::READ);
            });

            it('maps integer 4 to PLAYED', function () {
                expect(MessageStatus::fromApi(4))->toBe(MessageStatus::PLAYED);
            });

            it('maps integer 5 to FAILED', function () {
                expect(MessageStatus::fromApi(5))->toBe(MessageStatus::FAILED);
            });

            it('maps unknown integers to UNKNOWN', function () {
                expect(MessageStatus::fromApi(6))->toBe(MessageStatus::UNKNOWN);
                expect(MessageStatus::fromApi(99))->toBe(MessageStatus::UNKNOWN);
                expect(MessageStatus::fromApi(-1))->toBe(MessageStatus::UNKNOWN);
            });
        });

        describe('with string values', function () {
            it('maps pending variations correctly', function () {
                expect(MessageStatus::fromApi('pending'))->toBe(MessageStatus::PENDING);
                expect(MessageStatus::fromApi('PENDING'))->toBe(MessageStatus::PENDING);
                expect(MessageStatus::fromApi('server_ack'))->toBe(MessageStatus::PENDING);
            });

            it('maps sent correctly', function () {
                expect(MessageStatus::fromApi('sent'))->toBe(MessageStatus::SENT);
                expect(MessageStatus::fromApi('SENT'))->toBe(MessageStatus::SENT);
            });

            it('maps delivered variations correctly', function () {
                expect(MessageStatus::fromApi('delivered'))->toBe(MessageStatus::DELIVERED);
                expect(MessageStatus::fromApi('DELIVERED'))->toBe(MessageStatus::DELIVERED);
                expect(MessageStatus::fromApi('delivery_ack'))->toBe(MessageStatus::DELIVERED);
            });

            it('maps read variations correctly', function () {
                expect(MessageStatus::fromApi('read'))->toBe(MessageStatus::READ);
                expect(MessageStatus::fromApi('READ'))->toBe(MessageStatus::READ);
                expect(MessageStatus::fromApi('read_ack'))->toBe(MessageStatus::READ);
            });

            it('maps played variations correctly', function () {
                expect(MessageStatus::fromApi('played'))->toBe(MessageStatus::PLAYED);
                expect(MessageStatus::fromApi('PLAYED'))->toBe(MessageStatus::PLAYED);
                expect(MessageStatus::fromApi('play_ack'))->toBe(MessageStatus::PLAYED);
            });

            it('maps failed variations correctly', function () {
                expect(MessageStatus::fromApi('failed'))->toBe(MessageStatus::FAILED);
                expect(MessageStatus::fromApi('FAILED'))->toBe(MessageStatus::FAILED);
                expect(MessageStatus::fromApi('error'))->toBe(MessageStatus::FAILED);
            });

            it('maps deleted correctly', function () {
                expect(MessageStatus::fromApi('deleted'))->toBe(MessageStatus::DELETED);
                expect(MessageStatus::fromApi('DELETED'))->toBe(MessageStatus::DELETED);
            });

            it('returns unknown for unrecognized strings', function () {
                expect(MessageStatus::fromApi('invalid'))->toBe(MessageStatus::UNKNOWN);
                expect(MessageStatus::fromApi(''))->toBe(MessageStatus::UNKNOWN);
            });
        });
    });

    describe('fromString', function () {
        it('matches exact enum values first', function () {
            expect(MessageStatus::fromString('pending'))->toBe(MessageStatus::PENDING);
            expect(MessageStatus::fromString('sent'))->toBe(MessageStatus::SENT);
            expect(MessageStatus::fromString('delivered'))->toBe(MessageStatus::DELIVERED);
            expect(MessageStatus::fromString('read'))->toBe(MessageStatus::READ);
            expect(MessageStatus::fromString('played'))->toBe(MessageStatus::PLAYED);
            expect(MessageStatus::fromString('failed'))->toBe(MessageStatus::FAILED);
            expect(MessageStatus::fromString('deleted'))->toBe(MessageStatus::DELETED);
            expect(MessageStatus::fromString('unknown'))->toBe(MessageStatus::UNKNOWN);
        });

        it('falls back to API mapping for variations', function () {
            expect(MessageStatus::fromString('server_ack'))->toBe(MessageStatus::PENDING);
            expect(MessageStatus::fromString('delivery_ack'))->toBe(MessageStatus::DELIVERED);
            expect(MessageStatus::fromString('read_ack'))->toBe(MessageStatus::READ);
        });

        it('returns unknown for unrecognized strings', function () {
            expect(MessageStatus::fromString('invalid'))->toBe(MessageStatus::UNKNOWN);
        });
    });

    describe('tryFromString', function () {
        it('returns the status for valid strings', function () {
            expect(MessageStatus::tryFromString('sent'))->toBe(MessageStatus::SENT);
            expect(MessageStatus::tryFromString('delivery_ack'))->toBe(MessageStatus::DELIVERED);
        });

        it('returns status for unknown strings (maps to UNKNOWN)', function () {
            // Note: tryFromString doesn't return null for invalid values,
            // it returns UNKNOWN because fromString maps unknown values to UNKNOWN
            expect(MessageStatus::tryFromString('invalid'))->toBe(MessageStatus::UNKNOWN);
        });
    });
});
