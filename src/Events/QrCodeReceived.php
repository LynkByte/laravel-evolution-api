<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Events;

/**
 * Event fired when a QR code is generated for instance connection.
 */
class QrCodeReceived extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $data The raw QR code data from webhook
     */
    public function __construct(
        string $instanceName,
        public readonly string $qrCode,
        public readonly ?string $pairingCode = null,
        public readonly int $attempt = 1,
        public readonly array $data = []
    ) {
        parent::__construct($instanceName);
    }

    /**
     * Get the QR code string (for generating QR image).
     */
    public function getQrCode(): string
    {
        return $this->qrCode;
    }

    /**
     * Get the pairing code for code pairing method.
     */
    public function getPairingCode(): ?string
    {
        return $this->pairingCode;
    }

    /**
     * Check if pairing code is available.
     */
    public function hasPairingCode(): bool
    {
        return $this->pairingCode !== null;
    }

    /**
     * Get the current QR code attempt number.
     */
    public function getAttempt(): int
    {
        return $this->attempt;
    }

    /**
     * Check if this is a retry (not the first QR code).
     */
    public function isRetry(): bool
    {
        return $this->attempt > 1;
    }

    /**
     * Get the QR code as a base64 data URI for direct HTML embedding.
     */
    public function getQrCodeDataUri(): string
    {
        // If already a data URI, return as-is
        if (str_starts_with($this->qrCode, 'data:')) {
            return $this->qrCode;
        }

        // If it's base64 image data, wrap it
        if (preg_match('/^[A-Za-z0-9+\/=]+$/', $this->qrCode)) {
            return 'data:image/png;base64,' . $this->qrCode;
        }

        // Return the raw QR string (for QR code generation libraries)
        return $this->qrCode;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'qr_code' => $this->qrCode,
            'pairing_code' => $this->pairingCode,
            'attempt' => $this->attempt,
            'is_retry' => $this->isRetry(),
            'data' => $this->data,
        ]);
    }
}
