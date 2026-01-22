<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Message\SendContactMessageDto;

describe('SendContactMessageDto', function () {
    describe('constructor', function () {
        it('creates a contact message DTO', function () {
            $contact = [
                ['fullName' => 'John Doe', 'wuid' => '5511999999999', 'phoneNumber' => '+5511999999999'],
            ];

            $dto = new SendContactMessageDto(
                number: '5511888888888',
                contact: $contact
            );

            expect($dto->number)->toBe('5511888888888');
            expect($dto->contact)->toBe($contact);
            expect($dto->delay)->toBeNull();
            expect($dto->quoted)->toBeNull();
        });

        it('throws exception when number is empty', function () {
            new SendContactMessageDto(
                number: '',
                contact: [['fullName' => 'John', 'wuid' => '123', 'phoneNumber' => '123']]
            );
        })->throws(InvalidArgumentException::class, 'The number field is required.');

        it('throws exception when contact is empty', function () {
            new SendContactMessageDto(
                number: '5511999999999',
                contact: []
            );
        })->throws(InvalidArgumentException::class, 'The contact field is required.');
    });

    describe('single', function () {
        it('creates a single contact message', function () {
            $dto = SendContactMessageDto::single(
                number: '5511888888888',
                fullName: 'John Doe',
                wuid: '5511999999999',
                phoneNumber: '+5511999999999'
            );

            expect($dto->number)->toBe('5511888888888');
            expect($dto->contact)->toHaveCount(1);
            expect($dto->contact[0]['fullName'])->toBe('John Doe');
            expect($dto->contact[0]['wuid'])->toBe('5511999999999');
            expect($dto->contact[0]['phoneNumber'])->toBe('+5511999999999');
        });

        it('includes optional fields when provided', function () {
            $dto = SendContactMessageDto::single(
                number: '5511888888888',
                fullName: 'John Doe',
                wuid: '5511999999999',
                phoneNumber: '+5511999999999',
                organization: 'Acme Inc',
                email: 'john@example.com',
                url: 'https://example.com'
            );

            expect($dto->contact[0]['organization'])->toBe('Acme Inc');
            expect($dto->contact[0]['email'])->toBe('john@example.com');
            expect($dto->contact[0]['url'])->toBe('https://example.com');
        });

        it('excludes null optional fields', function () {
            $dto = SendContactMessageDto::single(
                number: '5511888888888',
                fullName: 'John Doe',
                wuid: '5511999999999',
                phoneNumber: '+5511999999999'
            );

            expect($dto->contact[0])->not->toHaveKey('organization');
            expect($dto->contact[0])->not->toHaveKey('email');
            expect($dto->contact[0])->not->toHaveKey('url');
        });
    });

    describe('multiple', function () {
        it('creates a multiple contacts message', function () {
            $contacts = [
                ['fullName' => 'John Doe', 'wuid' => '5511999999999', 'phoneNumber' => '+5511999999999'],
                ['fullName' => 'Jane Doe', 'wuid' => '5511888888888', 'phoneNumber' => '+5511888888888'],
            ];

            $dto = SendContactMessageDto::multiple('5511777777777', $contacts);

            expect($dto->number)->toBe('5511777777777');
            expect($dto->contact)->toHaveCount(2);
            expect($dto->contact[0]['fullName'])->toBe('John Doe');
            expect($dto->contact[1]['fullName'])->toBe('Jane Doe');
        });
    });

    describe('toApiPayload', function () {
        it('returns required fields only when optionals are null', function () {
            $dto = SendContactMessageDto::single(
                number: '5511888888888',
                fullName: 'John Doe',
                wuid: '5511999999999',
                phoneNumber: '+5511999999999'
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('number');
            expect($payload)->toHaveKey('contact');
            expect($payload)->not->toHaveKey('delay');
            expect($payload)->not->toHaveKey('quoted');
        });

        it('includes delay and quoted when set', function () {
            $dto = new SendContactMessageDto(
                number: '5511888888888',
                contact: [['fullName' => 'John', 'wuid' => '123', 'phoneNumber' => '123']],
                delay: 1000,
                quoted: ['key' => ['id' => 'msg-123']]
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('delay');
            expect($payload['delay'])->toBe(1000);
            expect($payload)->toHaveKey('quoted');
        });
    });
});
