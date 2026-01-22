<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Enums;

/**
 * Media types for file uploads.
 */
enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case AUDIO = 'audio';
    case DOCUMENT = 'document';
    case STICKER = 'sticker';

    /**
     * Get allowed file extensions.
     *
     * @return array<string>
     */
    public function allowedExtensions(): array
    {
        return match ($this) {
            self::IMAGE => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            self::VIDEO => ['mp4', '3gp', 'mov', 'avi', 'mkv'],
            self::AUDIO => ['mp3', 'ogg', 'wav', 'aac', 'm4a', 'opus'],
            self::DOCUMENT => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip'],
            self::STICKER => ['webp'],
        };
    }

    /**
     * Get maximum file size in bytes.
     */
    public function maxFileSize(): int
    {
        return match ($this) {
            self::IMAGE => 16 * 1024 * 1024,  // 16MB
            self::VIDEO => 64 * 1024 * 1024,  // 64MB
            self::AUDIO => 16 * 1024 * 1024,  // 16MB
            self::DOCUMENT => 100 * 1024 * 1024, // 100MB
            self::STICKER => 500 * 1024,  // 500KB
        };
    }

    /**
     * Get MIME types for this media type.
     *
     * @return array<string>
     */
    public function mimeTypes(): array
    {
        return match ($this) {
            self::IMAGE => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            self::VIDEO => ['video/mp4', 'video/3gpp', 'video/quicktime'],
            self::AUDIO => ['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/aac', 'audio/mp4'],
            self::DOCUMENT => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.*'],
            self::STICKER => ['image/webp'],
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::IMAGE => 'Image',
            self::VIDEO => 'Video',
            self::AUDIO => 'Audio',
            self::DOCUMENT => 'Document',
            self::STICKER => 'Sticker',
        };
    }

    /**
     * Determine media type from file extension.
     */
    public static function fromExtension(string $extension): ?self
    {
        $ext = strtolower($extension);

        foreach (self::cases() as $type) {
            if (in_array($ext, $type->allowedExtensions(), true)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Determine media type from MIME type.
     */
    public static function fromMimeType(string $mimeType): ?self
    {
        $mime = strtolower($mimeType);

        return match (true) {
            str_starts_with($mime, 'image/webp') => self::STICKER,
            str_starts_with($mime, 'image/') => self::IMAGE,
            str_starts_with($mime, 'video/') => self::VIDEO,
            str_starts_with($mime, 'audio/') => self::AUDIO,
            default => self::DOCUMENT,
        };
    }
}
