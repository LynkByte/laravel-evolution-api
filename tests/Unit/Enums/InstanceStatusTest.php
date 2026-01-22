<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Enums\InstanceStatus;

describe('InstanceStatus Enum', function () {
    describe('cases', function () {
        it('has all expected cases', function () {
            $cases = InstanceStatus::cases();

            expect($cases)->toHaveCount(7);
            expect(array_map(fn ($case) => $case->value, $cases))->toContain(
                'open',
                'close',
                'connecting',
                'connected',
                'disconnected',
                'qrcode',
                'unknown'
            );
        });

        it('can be created from value', function () {
            expect(InstanceStatus::from('open'))->toBe(InstanceStatus::OPEN);
            expect(InstanceStatus::from('close'))->toBe(InstanceStatus::CLOSE);
            expect(InstanceStatus::from('connecting'))->toBe(InstanceStatus::CONNECTING);
            expect(InstanceStatus::from('connected'))->toBe(InstanceStatus::CONNECTED);
            expect(InstanceStatus::from('disconnected'))->toBe(InstanceStatus::DISCONNECTED);
            expect(InstanceStatus::from('qrcode'))->toBe(InstanceStatus::QRCODE);
            expect(InstanceStatus::from('unknown'))->toBe(InstanceStatus::UNKNOWN);
        });

        it('throws exception for invalid value', function () {
            InstanceStatus::from('invalid');
        })->throws(ValueError::class);

        it('can try from value without throwing', function () {
            expect(InstanceStatus::tryFrom('open'))->toBe(InstanceStatus::OPEN);
            expect(InstanceStatus::tryFrom('invalid'))->toBeNull();
        });
    });

    describe('isConnected', function () {
        it('returns true for connected statuses', function () {
            expect(InstanceStatus::OPEN->isConnected())->toBeTrue();
            expect(InstanceStatus::CONNECTED->isConnected())->toBeTrue();
        });

        it('returns false for non-connected statuses', function () {
            expect(InstanceStatus::CLOSE->isConnected())->toBeFalse();
            expect(InstanceStatus::CONNECTING->isConnected())->toBeFalse();
            expect(InstanceStatus::DISCONNECTED->isConnected())->toBeFalse();
            expect(InstanceStatus::QRCODE->isConnected())->toBeFalse();
            expect(InstanceStatus::UNKNOWN->isConnected())->toBeFalse();
        });
    });

    describe('isDisconnected', function () {
        it('returns true for disconnected statuses', function () {
            expect(InstanceStatus::CLOSE->isDisconnected())->toBeTrue();
            expect(InstanceStatus::DISCONNECTED->isDisconnected())->toBeTrue();
        });

        it('returns false for non-disconnected statuses', function () {
            expect(InstanceStatus::OPEN->isDisconnected())->toBeFalse();
            expect(InstanceStatus::CONNECTING->isDisconnected())->toBeFalse();
            expect(InstanceStatus::CONNECTED->isDisconnected())->toBeFalse();
            expect(InstanceStatus::QRCODE->isDisconnected())->toBeFalse();
            expect(InstanceStatus::UNKNOWN->isDisconnected())->toBeFalse();
        });
    });

    describe('requiresQrCode', function () {
        it('returns true only for QRCODE status', function () {
            expect(InstanceStatus::QRCODE->requiresQrCode())->toBeTrue();
        });

        it('returns false for all other statuses', function () {
            expect(InstanceStatus::OPEN->requiresQrCode())->toBeFalse();
            expect(InstanceStatus::CLOSE->requiresQrCode())->toBeFalse();
            expect(InstanceStatus::CONNECTING->requiresQrCode())->toBeFalse();
            expect(InstanceStatus::CONNECTED->requiresQrCode())->toBeFalse();
            expect(InstanceStatus::DISCONNECTED->requiresQrCode())->toBeFalse();
            expect(InstanceStatus::UNKNOWN->requiresQrCode())->toBeFalse();
        });
    });

    describe('label', function () {
        it('returns human-readable labels', function () {
            expect(InstanceStatus::OPEN->label())->toBe('Open');
            expect(InstanceStatus::CLOSE->label())->toBe('Closed');
            expect(InstanceStatus::CONNECTING->label())->toBe('Connecting');
            expect(InstanceStatus::CONNECTED->label())->toBe('Connected');
            expect(InstanceStatus::DISCONNECTED->label())->toBe('Disconnected');
            expect(InstanceStatus::QRCODE->label())->toBe('Awaiting QR Code Scan');
            expect(InstanceStatus::UNKNOWN->label())->toBe('Unknown');
        });
    });

    describe('fromApi', function () {
        it('maps open and connected to CONNECTED', function () {
            expect(InstanceStatus::fromApi('open'))->toBe(InstanceStatus::CONNECTED);
            expect(InstanceStatus::fromApi('OPEN'))->toBe(InstanceStatus::CONNECTED);
            expect(InstanceStatus::fromApi('connected'))->toBe(InstanceStatus::CONNECTED);
            expect(InstanceStatus::fromApi('CONNECTED'))->toBe(InstanceStatus::CONNECTED);
        });

        it('maps close, closed, and disconnected to DISCONNECTED', function () {
            expect(InstanceStatus::fromApi('close'))->toBe(InstanceStatus::DISCONNECTED);
            expect(InstanceStatus::fromApi('CLOSE'))->toBe(InstanceStatus::DISCONNECTED);
            expect(InstanceStatus::fromApi('closed'))->toBe(InstanceStatus::DISCONNECTED);
            expect(InstanceStatus::fromApi('disconnected'))->toBe(InstanceStatus::DISCONNECTED);
            expect(InstanceStatus::fromApi('DISCONNECTED'))->toBe(InstanceStatus::DISCONNECTED);
        });

        it('maps connecting correctly', function () {
            expect(InstanceStatus::fromApi('connecting'))->toBe(InstanceStatus::CONNECTING);
            expect(InstanceStatus::fromApi('CONNECTING'))->toBe(InstanceStatus::CONNECTING);
        });

        it('maps qrcode and qr to QRCODE', function () {
            expect(InstanceStatus::fromApi('qrcode'))->toBe(InstanceStatus::QRCODE);
            expect(InstanceStatus::fromApi('QRCODE'))->toBe(InstanceStatus::QRCODE);
            expect(InstanceStatus::fromApi('qr'))->toBe(InstanceStatus::QRCODE);
            expect(InstanceStatus::fromApi('QR'))->toBe(InstanceStatus::QRCODE);
        });

        it('returns unknown for unrecognized values', function () {
            expect(InstanceStatus::fromApi('invalid'))->toBe(InstanceStatus::UNKNOWN);
            expect(InstanceStatus::fromApi(''))->toBe(InstanceStatus::UNKNOWN);
            expect(InstanceStatus::fromApi('random'))->toBe(InstanceStatus::UNKNOWN);
        });
    });

    describe('fromString', function () {
        it('matches exact enum values first', function () {
            expect(InstanceStatus::fromString('open'))->toBe(InstanceStatus::OPEN);
            expect(InstanceStatus::fromString('close'))->toBe(InstanceStatus::CLOSE);
            expect(InstanceStatus::fromString('connecting'))->toBe(InstanceStatus::CONNECTING);
            expect(InstanceStatus::fromString('connected'))->toBe(InstanceStatus::CONNECTED);
            expect(InstanceStatus::fromString('disconnected'))->toBe(InstanceStatus::DISCONNECTED);
            expect(InstanceStatus::fromString('qrcode'))->toBe(InstanceStatus::QRCODE);
            expect(InstanceStatus::fromString('unknown'))->toBe(InstanceStatus::UNKNOWN);
        });

        it('falls back to API mapping for variations', function () {
            expect(InstanceStatus::fromString('closed'))->toBe(InstanceStatus::DISCONNECTED);
            expect(InstanceStatus::fromString('qr'))->toBe(InstanceStatus::QRCODE);
        });

        it('is case-insensitive for API mapping', function () {
            expect(InstanceStatus::fromString('OPEN'))->toBe(InstanceStatus::CONNECTED);
            expect(InstanceStatus::fromString('CLOSE'))->toBe(InstanceStatus::DISCONNECTED);
        });
    });

    describe('tryFromString', function () {
        it('returns the status for valid strings', function () {
            expect(InstanceStatus::tryFromString('open'))->toBe(InstanceStatus::OPEN);
            expect(InstanceStatus::tryFromString('connected'))->toBe(InstanceStatus::CONNECTED);
        });

        it('returns status for unknown strings (maps to UNKNOWN)', function () {
            expect(InstanceStatus::tryFromString('invalid'))->toBe(InstanceStatus::UNKNOWN);
        });
    });
});
