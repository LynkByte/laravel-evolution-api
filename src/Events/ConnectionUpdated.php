<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Events;

use Lynkbyte\EvolutionApi\Enums\InstanceStatus;

/**
 * Event fired when the connection status changes.
 */
class ConnectionUpdated extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>  $data  The raw connection data from webhook
     */
    public function __construct(
        string $instanceName,
        public readonly InstanceStatus $status,
        public readonly ?InstanceStatus $previousStatus = null,
        public readonly array $data = []
    ) {
        parent::__construct($instanceName);
    }

    /**
     * Check if the connection is now open/connected.
     */
    public function isConnected(): bool
    {
        return $this->status === InstanceStatus::OPEN
            || $this->status === InstanceStatus::CONNECTED;
    }

    /**
     * Check if the connection was lost/closed.
     */
    public function isDisconnected(): bool
    {
        return $this->status === InstanceStatus::CLOSE
            || $this->status === InstanceStatus::DISCONNECTED;
    }

    /**
     * Check if the status changed from connected to disconnected.
     */
    public function wasDisconnected(): bool
    {
        if ($this->previousStatus === null) {
            return false;
        }

        $wasConnected = in_array($this->previousStatus, [
            InstanceStatus::OPEN,
            InstanceStatus::CONNECTED,
        ], true);

        return $wasConnected && $this->isDisconnected();
    }

    /**
     * Check if the status changed from disconnected to connected.
     */
    public function wasReconnected(): bool
    {
        if ($this->previousStatus === null) {
            return false;
        }

        $wasDisconnected = in_array($this->previousStatus, [
            InstanceStatus::CLOSE,
            InstanceStatus::DISCONNECTED,
            InstanceStatus::CONNECTING,
        ], true);

        return $wasDisconnected && $this->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'status' => $this->status->value,
            'previous_status' => $this->previousStatus?->value,
            'is_connected' => $this->isConnected(),
            'is_disconnected' => $this->isDisconnected(),
            'data' => $this->data,
        ]);
    }
}
