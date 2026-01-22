<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Evolution API Contact model.
 *
 * @property int $id
 * @property string $instance_name
 * @property string $remote_jid
 * @property string|null $phone_number
 * @property string|null $push_name
 * @property string|null $profile_picture_url
 * @property bool $is_business
 * @property bool $is_group
 * @property bool $is_blocked
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $last_message_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EvolutionContact extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'evolution_contacts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'instance_name',
        'remote_jid',
        'phone_number',
        'push_name',
        'profile_picture_url',
        'is_business',
        'is_group',
        'is_blocked',
        'metadata',
        'last_message_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_business' => 'boolean',
        'is_group' => 'boolean',
        'is_blocked' => 'boolean',
        'metadata' => 'array',
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the instance that owns the contact.
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(EvolutionInstance::class, 'instance_name', 'name');
    }

    /**
     * Get the messages for the contact.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(EvolutionMessage::class, 'remote_jid', 'remote_jid')
            ->where('instance_name', $this->instance_name);
    }

    /**
     * Get the display name for the contact.
     */
    public function getDisplayName(): string
    {
        return $this->push_name ?? $this->phone_number ?? $this->remote_jid;
    }

    /**
     * Check if this is a group contact.
     */
    public function isGroup(): bool
    {
        return $this->is_group || str_contains($this->remote_jid, '@g.us');
    }

    /**
     * Check if this is a business contact.
     */
    public function isBusiness(): bool
    {
        return $this->is_business;
    }

    /**
     * Check if this contact is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->is_blocked;
    }

    /**
     * Update the last message timestamp.
     */
    public function touchLastMessage(): bool
    {
        $this->last_message_at = now();

        return $this->save();
    }

    /**
     * Block the contact.
     */
    public function block(): bool
    {
        $this->is_blocked = true;

        return $this->save();
    }

    /**
     * Unblock the contact.
     */
    public function unblock(): bool
    {
        $this->is_blocked = false;

        return $this->save();
    }

    /**
     * Scope to get contacts by instance.
     */
    public function scopeForInstance($query, string $instanceName)
    {
        return $query->where('instance_name', $instanceName);
    }

    /**
     * Scope to get groups.
     */
    public function scopeGroups($query)
    {
        return $query->where('is_group', true);
    }

    /**
     * Scope to get non-group contacts.
     */
    public function scopeIndividuals($query)
    {
        return $query->where('is_group', false);
    }

    /**
     * Scope to get business contacts.
     */
    public function scopeBusiness($query)
    {
        return $query->where('is_business', true);
    }

    /**
     * Scope to get blocked contacts.
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /**
     * Find contact by remote JID and instance.
     */
    public static function findByJid(string $remoteJid, string $instanceName): ?self
    {
        return static::where('remote_jid', $remoteJid)
            ->where('instance_name', $instanceName)
            ->first();
    }

    /**
     * Find or create contact by remote JID.
     */
    public static function findOrCreateByJid(
        string $remoteJid,
        string $instanceName,
        array $attributes = []
    ): self {
        return static::firstOrCreate(
            [
                'remote_jid' => $remoteJid,
                'instance_name' => $instanceName,
            ],
            array_merge([
                'is_group' => str_contains($remoteJid, '@g.us'),
            ], $attributes)
        );
    }
}
