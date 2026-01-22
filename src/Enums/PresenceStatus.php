<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Enums;

/**
 * Presence status types.
 */
enum PresenceStatus: string
{
    case AVAILABLE = 'available';
    case UNAVAILABLE = 'unavailable';
    case COMPOSING = 'composing';
    case RECORDING = 'recording';
    case PAUSED = 'paused';

    /**
     * Check if presence indicates user is online.
     */
    public function isOnline(): bool
    {
        return $this === self::AVAILABLE;
    }

    /**
     * Check if presence indicates typing activity.
     */
    public function isTyping(): bool
    {
        return $this === self::COMPOSING;
    }

    /**
     * Check if presence indicates recording.
     */
    public function isRecording(): bool
    {
        return $this === self::RECORDING;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Online',
            self::UNAVAILABLE => 'Offline',
            self::COMPOSING => 'Typing...',
            self::RECORDING => 'Recording...',
            self::PAUSED => 'Paused',
        };
    }
}
