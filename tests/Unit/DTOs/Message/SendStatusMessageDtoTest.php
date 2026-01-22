<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Message\SendStatusMessageDto;

describe('SendStatusMessageDto', function () {
    describe('constructor', function () {
        it('creates a status message DTO', function () {
            $dto = new SendStatusMessageDto(type: 'text');

            expect($dto->type)->toBe('text');
            expect($dto->allContacts)->toBeTrue();
        });

        it('creates with all parameters', function () {
            $dto = new SendStatusMessageDto(
                type: 'text',
                content: 'Hello World',
                caption: null,
                backgroundColor: '#FF5733',
                font: 2,
                allContacts: false,
                statusJidList: ['5511999999999@s.whatsapp.net']
            );

            expect($dto->content)->toBe('Hello World');
            expect($dto->backgroundColor)->toBe('#FF5733');
            expect($dto->font)->toBe(2);
            expect($dto->allContacts)->toBeFalse();
            expect($dto->statusJidList)->toBe(['5511999999999@s.whatsapp.net']);
        });

        it('throws exception when type is empty', function () {
            new SendStatusMessageDto(type: '');
        })->throws(InvalidArgumentException::class, 'The type field is required.');
    });

    describe('text', function () {
        it('creates a text status', function () {
            $dto = SendStatusMessageDto::text('Hello from my status!');

            expect($dto->type)->toBe('text');
            expect($dto->content)->toBe('Hello from my status!');
            expect($dto->backgroundColor)->toBe('#000000');
            expect($dto->font)->toBe(1);
        });

        it('creates text status with custom background', function () {
            $dto = SendStatusMessageDto::text('Custom styled', '#FF0000', 3);

            expect($dto->backgroundColor)->toBe('#FF0000');
            expect($dto->font)->toBe(3);
        });
    });

    describe('image', function () {
        it('creates an image status', function () {
            $dto = SendStatusMessageDto::image('https://example.com/image.jpg', 'My photo');

            expect($dto->type)->toBe('image');
            expect($dto->content)->toBe('https://example.com/image.jpg');
            expect($dto->caption)->toBe('My photo');
        });

        it('creates an image status without caption', function () {
            $dto = SendStatusMessageDto::image('https://example.com/image.jpg');

            expect($dto->caption)->toBeNull();
        });
    });

    describe('video', function () {
        it('creates a video status', function () {
            $dto = SendStatusMessageDto::video('https://example.com/video.mp4', 'Watch this!');

            expect($dto->type)->toBe('video');
            expect($dto->content)->toBe('https://example.com/video.mp4');
            expect($dto->caption)->toBe('Watch this!');
        });
    });

    describe('audio', function () {
        it('creates an audio status', function () {
            $dto = SendStatusMessageDto::audio('https://example.com/audio.mp3');

            expect($dto->type)->toBe('audio');
            expect($dto->content)->toBe('https://example.com/audio.mp3');
        });
    });

    describe('toContacts', function () {
        it('restricts status to specific contacts', function () {
            $jids = [
                '5511999999999@s.whatsapp.net',
                '5511888888888@s.whatsapp.net'
            ];
            
            $dto = SendStatusMessageDto::text('Private status')->toContacts($jids);

            expect($dto->allContacts)->toBeFalse();
            expect($dto->statusJidList)->toBe($jids);
        });

        it('preserves other properties', function () {
            $dto = SendStatusMessageDto::text('Hello', '#FF0000', 2)
                ->toContacts(['test@s.whatsapp.net']);

            expect($dto->content)->toBe('Hello');
            expect($dto->backgroundColor)->toBe('#FF0000');
            expect($dto->font)->toBe(2);
        });
    });

    describe('toApiPayload', function () {
        it('returns correct payload for text status', function () {
            $dto = SendStatusMessageDto::text('Hello World');

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('type');
            expect($payload['type'])->toBe('text');
            expect($payload)->toHaveKey('content');
            expect($payload)->toHaveKey('backgroundColor');
            expect($payload)->toHaveKey('font');
            expect($payload)->toHaveKey('allContacts');
            expect($payload['allContacts'])->toBeTrue();
        });

        it('includes statusJidList when specified', function () {
            $dto = SendStatusMessageDto::text('Private')
                ->toContacts(['test@s.whatsapp.net']);

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('statusJidList');
            expect($payload['allContacts'])->toBeFalse();
        });

        it('excludes null optional fields', function () {
            $dto = new SendStatusMessageDto(type: 'text');

            $payload = $dto->toApiPayload();

            expect($payload)->not->toHaveKey('content');
            expect($payload)->not->toHaveKey('caption');
            expect($payload)->not->toHaveKey('backgroundColor');
            expect($payload)->not->toHaveKey('font');
            expect($payload)->not->toHaveKey('statusJidList');
        });
    });
});
