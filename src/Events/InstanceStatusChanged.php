<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Events;

use Lynkbyte\EvolutionApi\Enums\InstanceStatus;

/**
 * Event fired when an instance status changes.
 */
class InstanceStatusChanged extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>  $data  Additional instance data
     */
    public function __construct(
        string $instanceName,
        public readonly InstanceStatus $status,
        public readonly ?InstanceStatus $previousStatus = null,
        public readonly ?string $phoneNumber = null,
        public readonly array $data = []
    ) {
        parent::__construct($instanceName);
    }

    /**
     * Check if the instance is ready to send messages.
     */
    public function isReady(): bool
    {
        return $this->status === InstanceStatus::OPEN
            || $this->status === InstanceStatus::CONNECTED;
    }

    /**
     * Check if the instance needs QR code scanning.
     */
    public function needsQrCode(): bool
    {
        return $this->status === InstanceStatus::QRCODE;
    }

    /**
     * Check if the instance is connecting.
     */
    public function isConnecting(): bool
    {
        return $this->status === InstanceStatus::CONNECTING;
    }

    /**
     * Check if the instance was logged out.
     */
    public function wasLoggedOut(): bool
    {
        return $this->status === InstanceStatus::DISCONNECTED
            && $this->previousStatus !== null
            && in_array($this->previousStatus, [
                InstanceStatus::OPEN,
                InstanceStatus::CONNECTED,
            ], true);
    }

    /**
     * Get the phone number associated with this instance.
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'status' => $this->status->value,
            'previous_status' => $this->previousStatus?->value,
            'phone_number' => $this->phoneNumber,
            'is_ready' => $this->isReady(),
            'needs_qr_code' => $this->needsQrCode(),
            'is_connecting' => $this->isConnecting(),
            'data' => $this->data,
        ]);
    }
}
