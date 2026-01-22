<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Message\SendPollMessageDto;

describe('SendPollMessageDto', function () {
    describe('constructor', function () {
        it('creates a poll message DTO', function () {
            $dto = new SendPollMessageDto(
                number: '5511999999999',
                name: 'What is your favorite color?',
                values: ['Red', 'Blue', 'Green']
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->name)->toBe('What is your favorite color?');
            expect($dto->values)->toBe(['Red', 'Blue', 'Green']);
            expect($dto->selectableCount)->toBe(1);
        });

        it('creates with custom selectable count', function () {
            $dto = new SendPollMessageDto(
                number: '5511999999999',
                name: 'Select your favorites',
                values: ['Option 1', 'Option 2', 'Option 3'],
                selectableCount: 2
            );

            expect($dto->selectableCount)->toBe(2);
        });

        it('throws exception when number is empty', function () {
            new SendPollMessageDto(
                number: '',
                name: 'Question',
                values: ['Option']
            );
        })->throws(InvalidArgumentException::class, 'The number field is required.');

        it('throws exception when name is empty', function () {
            new SendPollMessageDto(
                number: '5511999999999',
                name: '',
                values: ['Option']
            );
        })->throws(InvalidArgumentException::class, 'The name field is required.');

        it('throws exception when values is empty', function () {
            new SendPollMessageDto(
                number: '5511999999999',
                name: 'Question',
                values: []
            );
        })->throws(InvalidArgumentException::class, 'The values field is required.');
    });

    describe('create', function () {
        it('creates a poll with static method', function () {
            $dto = SendPollMessageDto::create(
                number: '5511999999999',
                question: 'What is your favorite color?',
                options: ['Red', 'Blue', 'Green']
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->name)->toBe('What is your favorite color?');
            expect($dto->values)->toBe(['Red', 'Blue', 'Green']);
            expect($dto->selectableCount)->toBe(1);
        });
    });

    describe('multipleChoice', function () {
        it('sets maximum selections', function () {
            $dto = SendPollMessageDto::create(
                number: '5511999999999',
                question: 'Select your favorites',
                options: ['A', 'B', 'C', 'D']
            )->multipleChoice(3);

            expect($dto->selectableCount)->toBe(3);
        });

        it('preserves other properties', function () {
            $dto = new SendPollMessageDto(
                number: '5511999999999',
                name: 'Question',
                values: ['A', 'B'],
                delay: 1000,
                quoted: ['key' => ['id' => 'msg-123']]
            );

            $newDto = $dto->multipleChoice(2);

            expect($newDto->delay)->toBe(1000);
            expect($newDto->quoted)->toBe(['key' => ['id' => 'msg-123']]);
        });
    });

    describe('toApiPayload', function () {
        it('returns correct payload structure', function () {
            $dto = SendPollMessageDto::create(
                number: '5511999999999',
                question: 'Favorite color?',
                options: ['Red', 'Blue', 'Green']
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toBe([
                'number' => '5511999999999',
                'name' => 'Favorite color?',
                'values' => ['Red', 'Blue', 'Green'],
                'selectableCount' => 1,
            ]);
        });

        it('includes delay when set', function () {
            $dto = new SendPollMessageDto(
                number: '5511999999999',
                name: 'Question',
                values: ['A', 'B'],
                delay: 1000
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('delay');
            expect($payload['delay'])->toBe(1000);
        });

        it('includes quoted when set', function () {
            $quoted = ['key' => ['id' => 'msg-123']];
            $dto = new SendPollMessageDto(
                number: '5511999999999',
                name: 'Question',
                values: ['A', 'B'],
                quoted: $quoted
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('quoted');
            expect($payload['quoted'])->toBe($quoted);
        });
    });
});
