<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lynkbyte\EvolutionApi\Enums\MessageStatus;
use Lynkbyte\EvolutionApi\Enums\MessageType;

/**
 * Evolution API Message model.
 *
 * @property int $id
 * @property string $message_id
 * @property string $instance_name
 * @property string $remote_jid
 * @property bool $from_me
 * @property string $message_type
 * @property string $status
 * @property string|null $content
 * @property array|null $media
 * @property array|null $payload
 * @property array|null $response
 * @property string|null $error_message
 * @property int $retry_count
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon|null $read_at
 * @property \Carbon\Carbon|null $failed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EvolutionMessage extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'evolution_messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'message_id',
        'instance_name',
        'remote_jid',
        'from_me',
        'message_type',
        'status',
        'content',
        'media',
        'payload',
        'response',
        'error_message',
        'retry_count',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'from_me' => 'boolean',
        'media' => 'array',
        'payload' => 'array',
        'response' => 'array',
        'retry_count' => 'integer',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the instance that owns the message.
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(EvolutionInstance::class, 'instance_name', 'name');
    }

    /**
     * Get the message type as enum.
     */
    public function getMessageTypeEnum(): MessageType
    {
        return MessageType::fromApi($this->message_type);
    }

    /**
     * Get the status as enum.
     */
    public function getStatusEnum(): MessageStatus
    {
        return MessageStatus::fromString($this->status);
    }

    /**
     * Check if the message was sent successfully.
     */
    public function isSent(): bool
    {
        return $this->status === 'sent' || $this->sent_at !== null;
    }

    /**
     * Check if the message was delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered' || $this->delivered_at !== null;
    }

    /**
     * Check if the message was read.
     */
    public function isRead(): bool
    {
        return $this->status === 'read' || $this->read_at !== null;
    }

    /**
     * Check if the message failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed' || $this->failed_at !== null;
    }

    /**
     * Check if the message is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Mark the message as sent.
     */
    public function markAsSent(array $response = []): bool
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->response = $response;

        return $this->save();
    }

    /**
     * Mark the message as delivered.
     */
    public function markAsDelivered(): bool
    {
        $this->status = 'delivered';
        $this->delivered_at = now();

        return $this->save();
    }

    /**
     * Mark the message as read.
     */
    public function markAsRead(): bool
    {
        $this->status = 'read';
        $this->read_at = now();

        return $this->save();
    }

    /**
     * Mark the message as failed.
     */
    public function markAsFailed(string $errorMessage): bool
    {
        $this->status = 'failed';
        $this->failed_at = now();
        $this->error_message = $errorMessage;

        return $this->save();
    }

    /**
     * Increment retry count.
     */
    public function incrementRetry(): bool
    {
        $this->retry_count++;

        return $this->save();
    }

    /**
     * Scope to get messages by instance.
     */
    public function scopeForInstance($query, string $instanceName)
    {
        return $query->where('instance_name', $instanceName);
    }

    /**
     * Scope to get messages by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending messages.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get failed messages.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get messages that can be retried.
     */
    public function scopeRetryable($query, int $maxRetries = 3)
    {
        return $query->where('status', 'failed')
            ->where('retry_count', '<', $maxRetries);
    }

    /**
     * Scope to get outgoing messages.
     */
    public function scopeOutgoing($query)
    {
        return $query->where('from_me', true);
    }

    /**
     * Scope to get incoming messages.
     */
    public function scopeIncoming($query)
    {
        return $query->where('from_me', false);
    }

    /**
     * Find message by message ID and instance.
     */
    public static function findByMessageId(string $messageId, string $instanceName): ?self
    {
        return static::where('message_id', $messageId)
            ->where('instance_name', $instanceName)
            ->first();
    }
}
