<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Enums;

/**
 * Instance connection status.
 */
enum InstanceStatus: string
{
    case OPEN = 'open';
    case CLOSE = 'close';
    case CONNECTING = 'connecting';
    case CONNECTED = 'connected';
    case DISCONNECTED = 'disconnected';
    case QRCODE = 'qrcode';
    case UNKNOWN = 'unknown';

    /**
     * Check if the instance is connected.
     */
    public function isConnected(): bool
    {
        return $this === self::OPEN || $this === self::CONNECTED;
    }

    /**
     * Check if the instance is disconnected.
     */
    public function isDisconnected(): bool
    {
        return $this === self::CLOSE || $this === self::DISCONNECTED;
    }

    /**
     * Check if the instance requires QR code scan.
     */
    public function requiresQrCode(): bool
    {
        return $this === self::QRCODE;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::CLOSE => 'Closed',
            self::CONNECTING => 'Connecting',
            self::CONNECTED => 'Connected',
            self::DISCONNECTED => 'Disconnected',
            self::QRCODE => 'Awaiting QR Code Scan',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Create from API response value.
     */
    public static function fromApi(string $value): self
    {
        return match (strtolower($value)) {
            'open', 'connected' => self::CONNECTED,
            'close', 'closed', 'disconnected' => self::DISCONNECTED,
            'connecting' => self::CONNECTING,
            'qrcode', 'qr' => self::QRCODE,
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
