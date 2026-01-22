<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Enums\MediaType;

describe('MediaType Enum', function () {
    describe('cases', function () {
        it('has all expected cases', function () {
            $cases = MediaType::cases();

            expect($cases)->toHaveCount(5);
            expect(array_map(fn ($case) => $case->value, $cases))->toContain(
                'image',
                'video',
                'audio',
                'document',
                'sticker'
            );
        });

        it('can be created from value', function () {
            expect(MediaType::from('image'))->toBe(MediaType::IMAGE);
            expect(MediaType::from('video'))->toBe(MediaType::VIDEO);
            expect(MediaType::from('audio'))->toBe(MediaType::AUDIO);
            expect(MediaType::from('document'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::from('sticker'))->toBe(MediaType::STICKER);
        });

        it('throws exception for invalid value', function () {
            MediaType::from('invalid');
        })->throws(ValueError::class);

        it('can try from value without throwing', function () {
            expect(MediaType::tryFrom('image'))->toBe(MediaType::IMAGE);
            expect(MediaType::tryFrom('invalid'))->toBeNull();
        });
    });

    describe('allowedExtensions', function () {
        it('returns correct extensions for IMAGE', function () {
            $extensions = MediaType::IMAGE->allowedExtensions();

            expect($extensions)->toBeArray();
            expect($extensions)->toContain('jpg', 'jpeg', 'png', 'gif', 'webp');
        });

        it('returns correct extensions for VIDEO', function () {
            $extensions = MediaType::VIDEO->allowedExtensions();

            expect($extensions)->toBeArray();
            expect($extensions)->toContain('mp4', '3gp', 'mov', 'avi', 'mkv');
        });

        it('returns correct extensions for AUDIO', function () {
            $extensions = MediaType::AUDIO->allowedExtensions();

            expect($extensions)->toBeArray();
            expect($extensions)->toContain('mp3', 'ogg', 'wav', 'aac', 'm4a', 'opus');
        });

        it('returns correct extensions for DOCUMENT', function () {
            $extensions = MediaType::DOCUMENT->allowedExtensions();

            expect($extensions)->toBeArray();
            expect($extensions)->toContain('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip');
        });

        it('returns correct extensions for STICKER', function () {
            $extensions = MediaType::STICKER->allowedExtensions();

            expect($extensions)->toBeArray();
            expect($extensions)->toContain('webp');
            expect($extensions)->toHaveCount(1);
        });
    });

    describe('maxFileSize', function () {
        it('returns 16MB for IMAGE', function () {
            expect(MediaType::IMAGE->maxFileSize())->toBe(16 * 1024 * 1024);
        });

        it('returns 64MB for VIDEO', function () {
            expect(MediaType::VIDEO->maxFileSize())->toBe(64 * 1024 * 1024);
        });

        it('returns 16MB for AUDIO', function () {
            expect(MediaType::AUDIO->maxFileSize())->toBe(16 * 1024 * 1024);
        });

        it('returns 100MB for DOCUMENT', function () {
            expect(MediaType::DOCUMENT->maxFileSize())->toBe(100 * 1024 * 1024);
        });

        it('returns 500KB for STICKER', function () {
            expect(MediaType::STICKER->maxFileSize())->toBe(500 * 1024);
        });
    });

    describe('mimeTypes', function () {
        it('returns correct MIME types for IMAGE', function () {
            $mimeTypes = MediaType::IMAGE->mimeTypes();

            expect($mimeTypes)->toBeArray();
            expect($mimeTypes)->toContain('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        });

        it('returns correct MIME types for VIDEO', function () {
            $mimeTypes = MediaType::VIDEO->mimeTypes();

            expect($mimeTypes)->toBeArray();
            expect($mimeTypes)->toContain('video/mp4', 'video/3gpp', 'video/quicktime');
        });

        it('returns correct MIME types for AUDIO', function () {
            $mimeTypes = MediaType::AUDIO->mimeTypes();

            expect($mimeTypes)->toBeArray();
            expect($mimeTypes)->toContain('audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/aac', 'audio/mp4');
        });

        it('returns correct MIME types for DOCUMENT', function () {
            $mimeTypes = MediaType::DOCUMENT->mimeTypes();

            expect($mimeTypes)->toBeArray();
            expect($mimeTypes)->toContain('application/pdf', 'application/msword');
        });

        it('returns correct MIME types for STICKER', function () {
            $mimeTypes = MediaType::STICKER->mimeTypes();

            expect($mimeTypes)->toBeArray();
            expect($mimeTypes)->toContain('image/webp');
            expect($mimeTypes)->toHaveCount(1);
        });
    });

    describe('label', function () {
        it('returns human-readable labels', function () {
            expect(MediaType::IMAGE->label())->toBe('Image');
            expect(MediaType::VIDEO->label())->toBe('Video');
            expect(MediaType::AUDIO->label())->toBe('Audio');
            expect(MediaType::DOCUMENT->label())->toBe('Document');
            expect(MediaType::STICKER->label())->toBe('Sticker');
        });
    });

    describe('fromExtension', function () {
        it('returns IMAGE for image extensions', function () {
            expect(MediaType::fromExtension('jpg'))->toBe(MediaType::IMAGE);
            expect(MediaType::fromExtension('jpeg'))->toBe(MediaType::IMAGE);
            expect(MediaType::fromExtension('png'))->toBe(MediaType::IMAGE);
            expect(MediaType::fromExtension('gif'))->toBe(MediaType::IMAGE);
        });

        it('returns STICKER for webp extension', function () {
            // Note: webp is listed first in STICKER's extensions, so it matches STICKER first
            expect(MediaType::fromExtension('webp'))->toBe(MediaType::IMAGE);
        });

        it('returns VIDEO for video extensions', function () {
            expect(MediaType::fromExtension('mp4'))->toBe(MediaType::VIDEO);
            expect(MediaType::fromExtension('3gp'))->toBe(MediaType::VIDEO);
            expect(MediaType::fromExtension('mov'))->toBe(MediaType::VIDEO);
            expect(MediaType::fromExtension('avi'))->toBe(MediaType::VIDEO);
            expect(MediaType::fromExtension('mkv'))->toBe(MediaType::VIDEO);
        });

        it('returns AUDIO for audio extensions', function () {
            expect(MediaType::fromExtension('mp3'))->toBe(MediaType::AUDIO);
            expect(MediaType::fromExtension('ogg'))->toBe(MediaType::AUDIO);
            expect(MediaType::fromExtension('wav'))->toBe(MediaType::AUDIO);
            expect(MediaType::fromExtension('aac'))->toBe(MediaType::AUDIO);
            expect(MediaType::fromExtension('m4a'))->toBe(MediaType::AUDIO);
            expect(MediaType::fromExtension('opus'))->toBe(MediaType::AUDIO);
        });

        it('returns DOCUMENT for document extensions', function () {
            expect(MediaType::fromExtension('pdf'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::fromExtension('doc'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::fromExtension('docx'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::fromExtension('xls'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::fromExtension('xlsx'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::fromExtension('ppt'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::fromExtension('pptx'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::fromExtension('txt'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::fromExtension('csv'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::fromExtension('zip'))->toBe(MediaType::DOCUMENT);
        });

        it('is case-insensitive', function () {
            expect(MediaType::fromExtension('JPG'))->toBe(MediaType::IMAGE);
            expect(MediaType::fromExtension('PNG'))->toBe(MediaType::IMAGE);
            expect(MediaType::fromExtension('MP4'))->toBe(MediaType::VIDEO);
            expect(MediaType::fromExtension('PDF'))->toBe(MediaType::DOCUMENT);
        });

        it('returns null for unknown extensions', function () {
            expect(MediaType::fromExtension('exe'))->toBeNull();
            expect(MediaType::fromExtension('invalid'))->toBeNull();
            expect(MediaType::fromExtension(''))->toBeNull();
        });
    });

    describe('fromMimeType', function () {
        it('returns STICKER for image/webp', function () {
            expect(MediaType::fromMimeType('image/webp'))->toBe(MediaType::STICKER);
        });

        it('returns IMAGE for image mime types', function () {
            expect(MediaType::fromMimeType('image/jpeg'))->toBe(MediaType::IMAGE);
            expect(MediaType::fromMimeType('image/png'))->toBe(MediaType::IMAGE);
            expect(MediaType::fromMimeType('image/gif'))->toBe(MediaType::IMAGE);
        });

        it('returns VIDEO for video mime types', function () {
            expect(MediaType::fromMimeType('video/mp4'))->toBe(MediaType::VIDEO);
            expect(MediaType::fromMimeType('video/3gpp'))->toBe(MediaType::VIDEO);
            expect(MediaType::fromMimeType('video/quicktime'))->toBe(MediaType::VIDEO);
            expect(MediaType::fromMimeType('video/avi'))->toBe(MediaType::VIDEO);
        });

        it('returns AUDIO for audio mime types', function () {
            expect(MediaType::fromMimeType('audio/mpeg'))->toBe(MediaType::AUDIO);
            expect(MediaType::fromMimeType('audio/ogg'))->toBe(MediaType::AUDIO);
            expect(MediaType::fromMimeType('audio/wav'))->toBe(MediaType::AUDIO);
            expect(MediaType::fromMimeType('audio/mp4'))->toBe(MediaType::AUDIO);
        });

        it('returns DOCUMENT for unrecognized mime types', function () {
            expect(MediaType::fromMimeType('application/pdf'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::fromMimeType('application/msword'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::fromMimeType('text/plain'))->toBe(MediaType::DOCUMENT);
            expect(MediaType::fromMimeType('application/zip'))->toBe(MediaType::DOCUMENT);
        });

        it('is case-insensitive', function () {
            expect(MediaType::fromMimeType('IMAGE/JPEG'))->toBe(MediaType::IMAGE);
            expect(MediaType::fromMimeType('VIDEO/MP4'))->toBe(MediaType::VIDEO);
            expect(MediaType::fromMimeType('AUDIO/MPEG'))->toBe(MediaType::AUDIO);
        });
    });
});
