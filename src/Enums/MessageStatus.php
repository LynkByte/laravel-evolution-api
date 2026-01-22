<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Enums;

/**
 * Message delivery status.
 */
enum MessageStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case READ = 'read';
    case PLAYED = 'played';
    case FAILED = 'failed';
    case DELETED = 'deleted';
    case UNKNOWN = 'unknown';

    /**
     * Check if message was successfully sent.
     */
    public function isSent(): bool
    {
        return in_array($this, [
            self::SENT,
            self::DELIVERED,
            self::READ,
            self::PLAYED,
        ], true);
    }

    /**
     * Check if message was delivered.
     */
    public function isDelivered(): bool
    {
        return in_array($this, [
            self::DELIVERED,
            self::READ,
            self::PLAYED,
        ], true);
    }

    /**
     * Check if message was read.
     */
    public function isRead(): bool
    {
        return $this === self::READ || $this === self::PLAYED;
    }

    /**
     * Check if message failed.
     */
    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SENT => 'Sent',
            self::DELIVERED => 'Delivered',
            self::READ => 'Read',
            self::PLAYED => 'Played',
            self::FAILED => 'Failed',
            self::DELETED => 'Deleted',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Create from API response value.
     */
    public static function fromApi(int|string $value): self
    {
        if (is_int($value)) {
            return match ($value) {
                0 => self::PENDING,
                1 => self::SENT,
                2 => self::DELIVERED,
                3 => self::READ,
                4 => self::PLAYED,
                5 => self::FAILED,
                default => self::UNKNOWN,
            };
        }

        return match (strtolower($value)) {
            'pending', 'server_ack' => self::PENDING,
            'sent' => self::SENT,
            'delivered', 'delivery_ack' => self::DELIVERED,
            'read', 'read_ack' => self::READ,
            'played', 'play_ack' => self::PLAYED,
            'failed', 'error' => self::FAILED,
            'deleted' => self::DELETED,
            default => self::UNKNOWN,
        };
    }

    /**
     * Create from string value.
     */
    public static function fromString(string $value): self
    {
        // Try to match exact enum value first
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        // Fall back to API mapping
        return self::fromApi($value);
    }

    /**
     * Try to create from string, returning null if not found.
     */
    public static function tryFromString(string $value): ?self
    {
        try {
            return self::fromString($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
