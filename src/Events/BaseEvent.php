<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base event class for Evolution API events.
 */
abstract class BaseEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * The instance name.
     */
    public readonly string $instanceName;

    /**
     * Event timestamp.
     */
    public readonly int $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(string $instanceName)
    {
        $this->instanceName = $instanceName;
        $this->timestamp = time();
    }

    /**
     * Get the event as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'instance_name' => $this->instanceName,
            'timestamp' => $this->timestamp,
        ];
    }
}
