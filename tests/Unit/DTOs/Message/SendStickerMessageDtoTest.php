<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Message\SendStickerMessageDto;

describe('SendStickerMessageDto', function () {
    describe('constructor', function () {
        it('creates a sticker message DTO', function () {
            $dto = new SendStickerMessageDto(
                number: '5511999999999',
                sticker: 'https://example.com/sticker.webp'
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->sticker)->toBe('https://example.com/sticker.webp');
            expect($dto->delay)->toBeNull();
            expect($dto->quoted)->toBeNull();
        });

        it('creates with all optional parameters', function () {
            $quoted = ['key' => ['id' => 'msg-123']];

            $dto = new SendStickerMessageDto(
                number: '5511999999999',
                sticker: 'https://example.com/sticker.webp',
                delay: 1000,
                quoted: $quoted
            );

            expect($dto->delay)->toBe(1000);
            expect($dto->quoted)->toBe($quoted);
        });

        it('throws exception when number is empty', function () {
            new SendStickerMessageDto(number: '', sticker: 'https://example.com/sticker.webp');
        })->throws(InvalidArgumentException::class, 'The number field is required.');

        it('throws exception when sticker is empty', function () {
            new SendStickerMessageDto(number: '5511999999999', sticker: '');
        })->throws(InvalidArgumentException::class, 'The sticker field is required.');
    });

    describe('to', function () {
        it('creates a sticker message with static method', function () {
            $dto = SendStickerMessageDto::to('5511999999999', 'https://example.com/sticker.webp');

            expect($dto->number)->toBe('5511999999999');
            expect($dto->sticker)->toBe('https://example.com/sticker.webp');
        });
    });

    describe('withDelay', function () {
        it('returns a new DTO with delay set', function () {
            $dto = SendStickerMessageDto::to('5511999999999', 'https://example.com/sticker.webp');
            $newDto = $dto->withDelay(2000);

            expect($newDto->delay)->toBe(2000);
            expect($newDto->sticker)->toBe('https://example.com/sticker.webp');
            expect($newDto->number)->toBe('5511999999999');
        });

        it('preserves quoted property', function () {
            $quoted = ['key' => ['id' => 'msg-123']];
            $dto = new SendStickerMessageDto(
                number: '5511999999999',
                sticker: 'https://example.com/sticker.webp',
                quoted: $quoted
            );
            $newDto = $dto->withDelay(2000);

            expect($newDto->quoted)->toBe($quoted);
        });
    });

    describe('toApiPayload', function () {
        it('returns required fields only when optionals are null', function () {
            $dto = new SendStickerMessageDto(
                number: '5511999999999',
                sticker: 'https://example.com/sticker.webp'
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toBe([
                'number' => '5511999999999',
                'sticker' => 'https://example.com/sticker.webp',
            ]);
        });

        it('includes delay when set', function () {
            $dto = SendStickerMessageDto::to('5511999999999', 'https://example.com/sticker.webp')
                ->withDelay(1000);

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('delay');
            expect($payload['delay'])->toBe(1000);
        });

        it('includes quoted when set', function () {
            $quoted = ['key' => ['id' => 'msg-123']];
            $dto = new SendStickerMessageDto(
                number: '5511999999999',
                sticker: 'https://example.com/sticker.webp',
                quoted: $quoted
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('quoted');
            expect($payload['quoted'])->toBe($quoted);
        });
    });
});
