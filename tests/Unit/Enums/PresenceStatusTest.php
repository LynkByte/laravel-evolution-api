<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Enums\PresenceStatus;

describe('PresenceStatus Enum', function () {
    describe('cases', function () {
        it('has all expected cases', function () {
            $cases = PresenceStatus::cases();

            expect($cases)->toHaveCount(5);
            expect(array_map(fn ($case) => $case->value, $cases))->toContain(
                'available',
                'unavailable',
                'composing',
                'recording',
                'paused'
            );
        });

        it('can be created from value', function () {
            expect(PresenceStatus::from('available'))->toBe(PresenceStatus::AVAILABLE);
            expect(PresenceStatus::from('unavailable'))->toBe(PresenceStatus::UNAVAILABLE);
            expect(PresenceStatus::from('composing'))->toBe(PresenceStatus::COMPOSING);
            expect(PresenceStatus::from('recording'))->toBe(PresenceStatus::RECORDING);
            expect(PresenceStatus::from('paused'))->toBe(PresenceStatus::PAUSED);
        });

        it('throws exception for invalid value', function () {
            PresenceStatus::from('invalid');
        })->throws(ValueError::class);

        it('can try from value without throwing', function () {
            expect(PresenceStatus::tryFrom('available'))->toBe(PresenceStatus::AVAILABLE);
            expect(PresenceStatus::tryFrom('invalid'))->toBeNull();
        });
    });

    describe('isOnline', function () {
        it('returns true only for AVAILABLE status', function () {
            expect(PresenceStatus::AVAILABLE->isOnline())->toBeTrue();
        });

        it('returns false for all other statuses', function () {
            expect(PresenceStatus::UNAVAILABLE->isOnline())->toBeFalse();
            expect(PresenceStatus::COMPOSING->isOnline())->toBeFalse();
            expect(PresenceStatus::RECORDING->isOnline())->toBeFalse();
            expect(PresenceStatus::PAUSED->isOnline())->toBeFalse();
        });
    });

    describe('isTyping', function () {
        it('returns true only for COMPOSING status', function () {
            expect(PresenceStatus::COMPOSING->isTyping())->toBeTrue();
        });

        it('returns false for all other statuses', function () {
            expect(PresenceStatus::AVAILABLE->isTyping())->toBeFalse();
            expect(PresenceStatus::UNAVAILABLE->isTyping())->toBeFalse();
            expect(PresenceStatus::RECORDING->isTyping())->toBeFalse();
            expect(PresenceStatus::PAUSED->isTyping())->toBeFalse();
        });
    });

    describe('isRecording', function () {
        it('returns true only for RECORDING status', function () {
            expect(PresenceStatus::RECORDING->isRecording())->toBeTrue();
        });

        it('returns false for all other statuses', function () {
            expect(PresenceStatus::AVAILABLE->isRecording())->toBeFalse();
            expect(PresenceStatus::UNAVAILABLE->isRecording())->toBeFalse();
            expect(PresenceStatus::COMPOSING->isRecording())->toBeFalse();
            expect(PresenceStatus::PAUSED->isRecording())->toBeFalse();
        });
    });

    describe('label', function () {
        it('returns human-readable labels', function () {
            expect(PresenceStatus::AVAILABLE->label())->toBe('Online');
            expect(PresenceStatus::UNAVAILABLE->label())->toBe('Offline');
            expect(PresenceStatus::COMPOSING->label())->toBe('Typing...');
            expect(PresenceStatus::RECORDING->label())->toBe('Recording...');
            expect(PresenceStatus::PAUSED->label())->toBe('Paused');
        });
    });

    describe('value property', function () {
        it('has correct string values', function () {
            expect(PresenceStatus::AVAILABLE->value)->toBe('available');
            expect(PresenceStatus::UNAVAILABLE->value)->toBe('unavailable');
            expect(PresenceStatus::COMPOSING->value)->toBe('composing');
            expect(PresenceStatus::RECORDING->value)->toBe('recording');
            expect(PresenceStatus::PAUSED->value)->toBe('paused');
        });
    });

    describe('use cases', function () {
        it('can be used in match expressions', function () {
            $status = PresenceStatus::COMPOSING;

            $message = match ($status) {
                PresenceStatus::AVAILABLE => 'User is online',
                PresenceStatus::UNAVAILABLE => 'User is offline',
                PresenceStatus::COMPOSING => 'User is typing',
                PresenceStatus::RECORDING => 'User is recording',
                PresenceStatus::PAUSED => 'User paused',
            };

            expect($message)->toBe('User is typing');
        });

        it('can be compared with identical values', function () {
            $status1 = PresenceStatus::AVAILABLE;
            $status2 = PresenceStatus::AVAILABLE;
            $status3 = PresenceStatus::UNAVAILABLE;

            expect($status1 === $status2)->toBeTrue();
            expect($status1 === $status3)->toBeFalse();
        });

        it('can be used in arrays', function () {
            $activeStatuses = [
                PresenceStatus::AVAILABLE,
                PresenceStatus::COMPOSING,
                PresenceStatus::RECORDING,
            ];

            expect(in_array(PresenceStatus::AVAILABLE, $activeStatuses, true))->toBeTrue();
            expect(in_array(PresenceStatus::UNAVAILABLE, $activeStatuses, true))->toBeFalse();
        });
    });
});
