<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lynkbyte\EvolutionApi\Enums\InstanceStatus;

/**
 * Evolution API Instance model.
 *
 * @property int $id
 * @property string $name
 * @property string|null $display_name
 * @property string $connection_name
 * @property string|null $phone_number
 * @property string $status
 * @property string|null $profile_name
 * @property string|null $profile_picture_url
 * @property array|null $settings
 * @property array|null $webhook_config
 * @property \Carbon\Carbon|null $connected_at
 * @property \Carbon\Carbon|null $disconnected_at
 * @property \Carbon\Carbon|null $last_seen_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class EvolutionInstance extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'evolution_instances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'connection_name',
        'phone_number',
        'status',
        'profile_name',
        'profile_picture_url',
        'settings',
        'webhook_config',
        'connected_at',
        'disconnected_at',
        'last_seen_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'webhook_config' => 'array',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Get the messages for the instance.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(EvolutionMessage::class, 'instance_name', 'name');
    }

    /**
     * Get the contacts for the instance.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(EvolutionContact::class, 'instance_name', 'name');
    }

    /**
     * Get the webhook logs for the instance.
     */
    public function webhookLogs(): HasMany
    {
        return $this->hasMany(EvolutionWebhookLog::class, 'instance_name', 'name');
    }

    /**
     * Get the status as enum.
     */
    public function getStatusEnum(): InstanceStatus
    {
        return InstanceStatus::fromString($this->status);
    }

    /**
     * Check if the instance is connected.
     */
    public function isConnected(): bool
    {
        return in_array($this->status, ['open', 'connected'], true);
    }

    /**
     * Check if the instance is disconnected.
     */
    public function isDisconnected(): bool
    {
        return in_array($this->status, ['close', 'disconnected'], true);
    }

    /**
     * Check if the instance needs QR code scanning.
     */
    public function needsQrCode(): bool
    {
        return $this->status === 'qrcode';
    }

    /**
     * Update the instance status.
     */
    public function updateStatus(string $status): bool
    {
        $oldStatus = $this->status;
        $this->status = $status;

        if ($this->isConnected() && !in_array($oldStatus, ['open', 'connected'], true)) {
            $this->connected_at = now();
        }

        if ($this->isDisconnected() && !in_array($oldStatus, ['close', 'disconnected'], true)) {
            $this->disconnected_at = now();
        }

        $this->last_seen_at = now();

        return $this->save();
    }

    /**
     * Scope to get connected instances.
     */
    public function scopeConnected($query)
    {
        return $query->whereIn('status', ['open', 'connected']);
    }

    /**
     * Scope to get disconnected instances.
     */
    public function scopeDisconnected($query)
    {
        return $query->whereIn('status', ['close', 'disconnected']);
    }

    /**
     * Scope to get instances by connection name.
     */
    public function scopeForConnection($query, string $connectionName)
    {
        return $query->where('connection_name', $connectionName);
    }

    /**
     * Find instance by name.
     */
    public static function findByName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }

    /**
     * Find or create instance by name.
     */
    public static function findOrCreateByName(string $name, array $attributes = []): self
    {
        return static::firstOrCreate(
            ['name' => $name],
            array_merge(['status' => 'disconnected'], $attributes)
        );
    }
}
