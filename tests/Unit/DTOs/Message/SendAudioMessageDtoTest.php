<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Message\SendAudioMessageDto;

describe('SendAudioMessageDto', function () {
    describe('constructor', function () {
        it('creates an audio message DTO', function () {
            $dto = new SendAudioMessageDto(
                number: '5511999999999',
                audio: 'https://example.com/audio.mp3'
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->audio)->toBe('https://example.com/audio.mp3');
            expect($dto->delay)->toBeNull();
            expect($dto->quoted)->toBeNull();
        });

        it('creates with all optional parameters', function () {
            $quoted = ['key' => ['id' => 'msg-123']];
            
            $dto = new SendAudioMessageDto(
                number: '5511999999999',
                audio: 'https://example.com/audio.mp3',
                delay: 1000,
                quoted: $quoted
            );

            expect($dto->delay)->toBe(1000);
            expect($dto->quoted)->toBe($quoted);
        });

        it('throws exception when number is empty', function () {
            new SendAudioMessageDto(number: '', audio: 'https://example.com/audio.mp3');
        })->throws(InvalidArgumentException::class, 'The number field is required.');

        it('throws exception when audio is empty', function () {
            new SendAudioMessageDto(number: '5511999999999', audio: '');
        })->throws(InvalidArgumentException::class, 'The audio field is required.');
    });

    describe('to', function () {
        it('creates an audio message with static method', function () {
            $dto = SendAudioMessageDto::to('5511999999999', 'https://example.com/audio.mp3');

            expect($dto->number)->toBe('5511999999999');
            expect($dto->audio)->toBe('https://example.com/audio.mp3');
        });
    });

    describe('withDelay', function () {
        it('returns a new DTO with delay set', function () {
            $dto = SendAudioMessageDto::to('5511999999999', 'https://example.com/audio.mp3');
            $newDto = $dto->withDelay(2000);

            expect($newDto->delay)->toBe(2000);
            expect($newDto->audio)->toBe('https://example.com/audio.mp3');
            expect($newDto->number)->toBe('5511999999999');
        });

        it('preserves quoted property', function () {
            $quoted = ['key' => ['id' => 'msg-123']];
            $dto = new SendAudioMessageDto(
                number: '5511999999999',
                audio: 'https://example.com/audio.mp3',
                quoted: $quoted
            );
            $newDto = $dto->withDelay(2000);

            expect($newDto->quoted)->toBe($quoted);
        });
    });

    describe('toApiPayload', function () {
        it('returns required fields only when optionals are null', function () {
            $dto = new SendAudioMessageDto(
                number: '5511999999999',
                audio: 'https://example.com/audio.mp3'
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toBe([
                'number' => '5511999999999',
                'audio' => 'https://example.com/audio.mp3',
            ]);
        });

        it('includes delay when set', function () {
            $dto = SendAudioMessageDto::to('5511999999999', 'https://example.com/audio.mp3')
                ->withDelay(1000);

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('delay');
            expect($payload['delay'])->toBe(1000);
        });

        it('includes quoted when set', function () {
            $quoted = ['key' => ['id' => 'msg-123']];
            $dto = new SendAudioMessageDto(
                number: '5511999999999',
                audio: 'https://example.com/audio.mp3',
                quoted: $quoted
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('quoted');
            expect($payload['quoted'])->toBe($quoted);
        });
    });
});
