<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Contracts;

/**
 * Interface for queueable message operations.
 */
interface QueueableInterface
{
    /**
     * Enable queue processing for this operation.
     */
    public function queue(): self;

    /**
     * Specify the queue connection.
     */
    public function onConnection(string $connection): self;

    /**
     * Specify the queue name.
     */
    public function onQueue(string $queue): self;

    /**
     * Delay the operation.
     */
    public function delay(\DateTimeInterface|\DateInterval|int $delay): self;

    /**
     * Check if this operation should be queued.
     */
    public function shouldQueue(): bool;
}
