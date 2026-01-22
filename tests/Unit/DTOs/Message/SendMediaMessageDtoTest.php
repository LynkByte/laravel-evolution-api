<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Message\SendMediaMessageDto;
use Lynkbyte\EvolutionApi\Enums\MediaType;

describe('SendMediaMessageDto', function () {
    describe('constructor', function () {
        it('creates a media message DTO', function () {
            $dto = new SendMediaMessageDto(
                number: '5511999999999',
                mediatype: 'image',
                media: 'https://example.com/image.jpg'
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->mediatype)->toBe('image');
            expect($dto->media)->toBe('https://example.com/image.jpg');
        });

        it('creates with all optional parameters', function () {
            $dto = new SendMediaMessageDto(
                number: '5511999999999',
                mediatype: 'image',
                media: 'https://example.com/image.jpg',
                mimetype: 'image/jpeg',
                caption: 'Check this out!',
                fileName: 'photo.jpg',
                delay: 1000,
                quoted: ['key' => ['id' => 'msg-123']]
            );

            expect($dto->mimetype)->toBe('image/jpeg');
            expect($dto->caption)->toBe('Check this out!');
            expect($dto->fileName)->toBe('photo.jpg');
            expect($dto->delay)->toBe(1000);
            expect($dto->quoted)->toBe(['key' => ['id' => 'msg-123']]);
        });

        it('throws exception when number is empty', function () {
            new SendMediaMessageDto(number: '', mediatype: 'image');
        })->throws(InvalidArgumentException::class, 'The number field is required.');

        it('throws exception when mediatype is empty', function () {
            new SendMediaMessageDto(number: '5511999999999', mediatype: '');
        })->throws(InvalidArgumentException::class, 'The mediatype field is required.');
    });

    describe('image', function () {
        it('creates an image message', function () {
            $dto = SendMediaMessageDto::image(
                number: '5511999999999',
                url: 'https://example.com/image.jpg',
                caption: 'Beautiful photo'
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->mediatype)->toBe(MediaType::IMAGE->value);
            expect($dto->media)->toBe('https://example.com/image.jpg');
            expect($dto->caption)->toBe('Beautiful photo');
        });

        it('creates an image message without caption', function () {
            $dto = SendMediaMessageDto::image(
                number: '5511999999999',
                url: 'https://example.com/image.jpg'
            );

            expect($dto->caption)->toBeNull();
        });
    });

    describe('video', function () {
        it('creates a video message', function () {
            $dto = SendMediaMessageDto::video(
                number: '5511999999999',
                url: 'https://example.com/video.mp4',
                caption: 'Watch this'
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->mediatype)->toBe(MediaType::VIDEO->value);
            expect($dto->media)->toBe('https://example.com/video.mp4');
            expect($dto->caption)->toBe('Watch this');
        });
    });

    describe('document', function () {
        it('creates a document message', function () {
            $dto = SendMediaMessageDto::document(
                number: '5511999999999',
                url: 'https://example.com/file.pdf',
                fileName: 'report.pdf'
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->mediatype)->toBe(MediaType::DOCUMENT->value);
            expect($dto->media)->toBe('https://example.com/file.pdf');
            expect($dto->fileName)->toBe('report.pdf');
        });

        it('creates a document without custom filename', function () {
            $dto = SendMediaMessageDto::document(
                number: '5511999999999',
                url: 'https://example.com/file.pdf'
            );

            expect($dto->fileName)->toBeNull();
        });
    });

    describe('withDelay', function () {
        it('returns a new DTO with delay set', function () {
            $dto = SendMediaMessageDto::image('5511999999999', 'https://example.com/image.jpg');
            $newDto = $dto->withDelay(2000);

            expect($newDto->delay)->toBe(2000);
            expect($newDto->media)->toBe('https://example.com/image.jpg');
        });
    });

    describe('withMimeType', function () {
        it('returns a new DTO with custom MIME type', function () {
            $dto = SendMediaMessageDto::image('5511999999999', 'https://example.com/image.jpg');
            $newDto = $dto->withMimeType('image/png');

            expect($newDto->mimetype)->toBe('image/png');
        });
    });

    describe('toApiPayload', function () {
        it('returns required fields only when optionals are null', function () {
            $dto = new SendMediaMessageDto(
                number: '5511999999999',
                mediatype: 'image'
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toBe([
                'number' => '5511999999999',
                'mediatype' => 'image',
            ]);
        });

        it('includes all set optional fields', function () {
            $dto = new SendMediaMessageDto(
                number: '5511999999999',
                mediatype: 'image',
                media: 'https://example.com/image.jpg',
                mimetype: 'image/jpeg',
                caption: 'Check this!',
                fileName: 'photo.jpg',
                delay: 1000,
                quoted: ['key' => ['id' => 'msg-123']]
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('number');
            expect($payload)->toHaveKey('mediatype');
            expect($payload)->toHaveKey('media');
            expect($payload)->toHaveKey('mimetype');
            expect($payload)->toHaveKey('caption');
            expect($payload)->toHaveKey('fileName');
            expect($payload)->toHaveKey('delay');
            expect($payload)->toHaveKey('quoted');
        });
    });
});
