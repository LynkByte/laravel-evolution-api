<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Message\SendTextMessageDto;

describe('SendTextMessageDto', function () {
    describe('constructor', function () {
        it('creates a text message DTO', function () {
            $dto = new SendTextMessageDto(
                number: '5511999999999',
                text: 'Hello World'
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->text)->toBe('Hello World');
            expect($dto->delay)->toBeNull();
            expect($dto->linkPreview)->toBeTrue();
        });

        it('creates with all optional parameters', function () {
            $quoted = ['key' => ['remoteJid' => 'test@s.whatsapp.net', 'fromMe' => false, 'id' => 'msg-123']];
            
            $dto = new SendTextMessageDto(
                number: '5511999999999',
                text: 'Hello World',
                delay: 1000,
                linkPreview: false,
                mentionsEveryOne: 'true',
                mentioned: ['5511888888888@s.whatsapp.net'],
                quoted: $quoted
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->text)->toBe('Hello World');
            expect($dto->delay)->toBe(1000);
            expect($dto->linkPreview)->toBeFalse();
            expect($dto->mentionsEveryOne)->toBe('true');
            expect($dto->mentioned)->toBe(['5511888888888@s.whatsapp.net']);
            expect($dto->quoted)->toBe($quoted);
        });

        it('throws exception when number is empty', function () {
            new SendTextMessageDto(number: '', text: 'Hello');
        })->throws(InvalidArgumentException::class, 'The number field is required.');

        it('throws exception when text is empty', function () {
            new SendTextMessageDto(number: '5511999999999', text: '');
        })->throws(InvalidArgumentException::class, 'The text field is required.');
    });

    describe('to', function () {
        it('creates a DTO with fluent interface', function () {
            $dto = SendTextMessageDto::to('5511999999999');

            expect($dto)->toBeInstanceOf(SendTextMessageDto::class);
            expect($dto->number)->toBe('5511999999999');
            expect($dto->text)->toBe('');
        });
    });

    describe('withText', function () {
        it('returns a new DTO with the text set', function () {
            $dto = SendTextMessageDto::to('5511999999999')->withText('Hello World');

            expect($dto->text)->toBe('Hello World');
            expect($dto->number)->toBe('5511999999999');
        });

        it('preserves other properties', function () {
            $dto = new SendTextMessageDto(
                number: '5511999999999',
                text: 'Original',
                delay: 1000,
                linkPreview: false
            );

            $newDto = $dto->withText('Updated');

            expect($newDto->text)->toBe('Updated');
            expect($newDto->delay)->toBe(1000);
            expect($newDto->linkPreview)->toBeFalse();
        });
    });

    describe('withDelay', function () {
        it('returns a new DTO with delay set', function () {
            $dto = new SendTextMessageDto(number: '5511999999999', text: 'Hello');
            $newDto = $dto->withDelay(2000);

            expect($newDto->delay)->toBe(2000);
            expect($newDto->text)->toBe('Hello');
        });
    });

    describe('withLinkPreview', function () {
        it('enables link preview', function () {
            $dto = new SendTextMessageDto(number: '5511999999999', text: 'Hello', linkPreview: false);
            $newDto = $dto->withLinkPreview(true);

            expect($newDto->linkPreview)->toBeTrue();
        });

        it('disables link preview', function () {
            $dto = new SendTextMessageDto(number: '5511999999999', text: 'Hello');
            $newDto = $dto->withLinkPreview(false);

            expect($newDto->linkPreview)->toBeFalse();
        });
    });

    describe('quoting', function () {
        it('sets quoted message', function () {
            $quoted = ['key' => ['remoteJid' => 'test@s.whatsapp.net', 'fromMe' => false, 'id' => 'msg-123']];
            $dto = new SendTextMessageDto(number: '5511999999999', text: 'Hello');
            $newDto = $dto->quoting($quoted);

            expect($newDto->quoted)->toBe($quoted);
        });
    });

    describe('toApiPayload', function () {
        it('returns required fields only when optionals are null', function () {
            $dto = new SendTextMessageDto(number: '5511999999999', text: 'Hello');

            $payload = $dto->toApiPayload();

            expect($payload)->toBe([
                'number' => '5511999999999',
                'text' => 'Hello',
                'linkPreview' => true,
            ]);
        });

        it('includes all set optional fields', function () {
            $quoted = ['key' => ['remoteJid' => 'test@s.whatsapp.net', 'fromMe' => false, 'id' => 'msg-123']];
            
            $dto = new SendTextMessageDto(
                number: '5511999999999',
                text: 'Hello',
                delay: 1000,
                linkPreview: false,
                mentionsEveryOne: 'true',
                mentioned: ['5511888888888@s.whatsapp.net'],
                quoted: $quoted
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toBe([
                'number' => '5511999999999',
                'text' => 'Hello',
                'delay' => 1000,
                'linkPreview' => false,
                'mentionsEveryOne' => 'true',
                'mentioned' => ['5511888888888@s.whatsapp.net'],
                'quoted' => $quoted,
            ]);
        });
    });

    describe('fluent chain', function () {
        it('supports method chaining', function () {
            $dto = SendTextMessageDto::to('5511999999999')
                ->withText('Hello World')
                ->withDelay(1000)
                ->withLinkPreview(false);

            expect($dto->number)->toBe('5511999999999');
            expect($dto->text)->toBe('Hello World');
            expect($dto->delay)->toBe(1000);
            expect($dto->linkPreview)->toBeFalse();
        });
    });
});
