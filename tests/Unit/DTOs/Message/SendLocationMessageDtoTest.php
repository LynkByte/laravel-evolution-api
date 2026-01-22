<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Message\SendLocationMessageDto;

describe('SendLocationMessageDto', function () {
    describe('constructor', function () {
        it('creates a location message DTO', function () {
            $dto = new SendLocationMessageDto(
                number: '5511999999999',
                latitude: -23.5505,
                longitude: -46.6333
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->latitude)->toBe(-23.5505);
            expect($dto->longitude)->toBe(-46.6333);
            expect($dto->name)->toBeNull();
            expect($dto->address)->toBeNull();
        });

        it('creates with all optional parameters', function () {
            $dto = new SendLocationMessageDto(
                number: '5511999999999',
                latitude: -23.5505,
                longitude: -46.6333,
                name: 'São Paulo',
                address: 'Praça da Sé, São Paulo - SP',
                delay: 1000,
                quoted: ['key' => ['id' => 'msg-123']]
            );

            expect($dto->name)->toBe('São Paulo');
            expect($dto->address)->toBe('Praça da Sé, São Paulo - SP');
            expect($dto->delay)->toBe(1000);
            expect($dto->quoted)->toBe(['key' => ['id' => 'msg-123']]);
        });

        it('throws exception when number is empty', function () {
            new SendLocationMessageDto(number: '', latitude: -23.5505, longitude: -46.6333);
        })->throws(InvalidArgumentException::class, 'The number field is required.');
    });

    describe('to', function () {
        it('creates a location message with static method', function () {
            $dto = SendLocationMessageDto::to('5511999999999', -23.5505, -46.6333);

            expect($dto->number)->toBe('5511999999999');
            expect($dto->latitude)->toBe(-23.5505);
            expect($dto->longitude)->toBe(-46.6333);
        });
    });

    describe('withName', function () {
        it('returns a new DTO with name set', function () {
            $dto = SendLocationMessageDto::to('5511999999999', -23.5505, -46.6333);
            $newDto = $dto->withName('São Paulo');

            expect($newDto->name)->toBe('São Paulo');
            expect($newDto->latitude)->toBe(-23.5505);
            expect($newDto->longitude)->toBe(-46.6333);
        });
    });

    describe('withAddress', function () {
        it('returns a new DTO with address set', function () {
            $dto = SendLocationMessageDto::to('5511999999999', -23.5505, -46.6333);
            $newDto = $dto->withAddress('Praça da Sé, São Paulo - SP');

            expect($newDto->address)->toBe('Praça da Sé, São Paulo - SP');
        });
    });

    describe('toApiPayload', function () {
        it('returns required fields only when optionals are null', function () {
            $dto = new SendLocationMessageDto(
                number: '5511999999999',
                latitude: -23.5505,
                longitude: -46.6333
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toBe([
                'number' => '5511999999999',
                'latitude' => -23.5505,
                'longitude' => -46.6333,
            ]);
        });

        it('includes all set optional fields', function () {
            $dto = new SendLocationMessageDto(
                number: '5511999999999',
                latitude: -23.5505,
                longitude: -46.6333,
                name: 'São Paulo',
                address: 'Praça da Sé',
                delay: 1000,
                quoted: ['key' => ['id' => 'msg-123']]
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('name');
            expect($payload)->toHaveKey('address');
            expect($payload)->toHaveKey('delay');
            expect($payload)->toHaveKey('quoted');
        });
    });

    describe('fluent chain', function () {
        it('supports method chaining', function () {
            $dto = SendLocationMessageDto::to('5511999999999', -23.5505, -46.6333)
                ->withName('São Paulo')
                ->withAddress('Praça da Sé');

            expect($dto->name)->toBe('São Paulo');
            expect($dto->address)->toBe('Praça da Sé');
        });
    });
});
