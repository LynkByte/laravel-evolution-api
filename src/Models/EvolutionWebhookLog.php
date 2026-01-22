<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evolution API Webhook Log model.
 *
 * @property int $id
 * @property string $instance_name
 * @property string $event
 * @property array $payload
 * @property string $status
 * @property string|null $error_message
 * @property int|null $processing_time_ms
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EvolutionWebhookLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'evolution_webhook_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'instance_name',
        'event',
        'payload',
        'status',
        'error_message',
        'processing_time_ms',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'processing_time_ms' => 'integer',
    ];

    /**
     * Get the instance that owns the webhook log.
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(EvolutionInstance::class, 'instance_name', 'name');
    }

    /**
     * Check if the webhook was processed successfully.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'processed';
    }

    /**
     * Check if the webhook processing failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark the webhook as processed.
     */
    public function markAsProcessed(?int $processingTimeMs = null): bool
    {
        $this->status = 'processed';
        $this->processing_time_ms = $processingTimeMs;

        return $this->save();
    }

    /**
     * Mark the webhook as failed.
     */
    public function markAsFailed(string $errorMessage, ?int $processingTimeMs = null): bool
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;
        $this->processing_time_ms = $processingTimeMs;

        return $this->save();
    }

    /**
     * Scope to get logs by instance.
     */
    public function scopeForInstance($query, string $instanceName)
    {
        return $query->where('instance_name', $instanceName);
    }

    /**
     * Scope to get logs by event.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to get successful logs.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope to get failed logs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Create a log entry from webhook data.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function createFromWebhook(
        string $instanceName,
        string $event,
        array $payload,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return static::create([
            'instance_name' => $instanceName,
            'event' => $event,
            'payload' => $payload,
            'status' => 'received',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Prune old logs.
     */
    public static function pruneOlderThan(int $days): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
