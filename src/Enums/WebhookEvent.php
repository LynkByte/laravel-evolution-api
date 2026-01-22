<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Enums;

/**
 * Webhook event types from Evolution API.
 */
enum WebhookEvent: string
{
    case APPLICATION_STARTUP = 'APPLICATION_STARTUP';
    case QRCODE_UPDATED = 'QRCODE_UPDATED';
    case MESSAGES_SET = 'MESSAGES_SET';
    case MESSAGES_UPSERT = 'MESSAGES_UPSERT';
    case MESSAGES_UPDATE = 'MESSAGES_UPDATE';
    case MESSAGES_DELETE = 'MESSAGES_DELETE';
    case SEND_MESSAGE = 'SEND_MESSAGE';
    case CONTACTS_SET = 'CONTACTS_SET';
    case CONTACTS_UPSERT = 'CONTACTS_UPSERT';
    case CONTACTS_UPDATE = 'CONTACTS_UPDATE';
    case PRESENCE_UPDATE = 'PRESENCE_UPDATE';
    case CHATS_SET = 'CHATS_SET';
    case CHATS_UPSERT = 'CHATS_UPSERT';
    case CHATS_UPDATE = 'CHATS_UPDATE';
    case CHATS_DELETE = 'CHATS_DELETE';
    case GROUPS_UPSERT = 'GROUPS_UPSERT';
    case GROUP_UPDATE = 'GROUP_UPDATE';
    case GROUP_PARTICIPANTS_UPDATE = 'GROUP_PARTICIPANTS_UPDATE';
    case CONNECTION_UPDATE = 'CONNECTION_UPDATE';
    case LABELS_EDIT = 'LABELS_EDIT';
    case LABELS_ASSOCIATION = 'LABELS_ASSOCIATION';
    case CALL = 'CALL';
    case TYPEBOT_START = 'TYPEBOT_START';
    case TYPEBOT_CHANGE_STATUS = 'TYPEBOT_CHANGE_STATUS';
    case UNKNOWN = 'UNKNOWN';

    /**
     * Check if this is a message event.
     */
    public function isMessageEvent(): bool
    {
        return in_array($this, [
            self::MESSAGES_SET,
            self::MESSAGES_UPSERT,
            self::MESSAGES_UPDATE,
            self::MESSAGES_DELETE,
            self::SEND_MESSAGE,
        ], true);
    }

    /**
     * Check if this is a connection event.
     */
    public function isConnectionEvent(): bool
    {
        return in_array($this, [
            self::CONNECTION_UPDATE,
            self::QRCODE_UPDATED,
            self::APPLICATION_STARTUP,
        ], true);
    }

    /**
     * Check if this is a group event.
     */
    public function isGroupEvent(): bool
    {
        return in_array($this, [
            self::GROUPS_UPSERT,
            self::GROUP_UPDATE,
            self::GROUP_PARTICIPANTS_UPDATE,
        ], true);
    }

    /**
     * Check if this is a contact event.
     */
    public function isContactEvent(): bool
    {
        return in_array($this, [
            self::CONTACTS_SET,
            self::CONTACTS_UPSERT,
            self::CONTACTS_UPDATE,
        ], true);
    }

    /**
     * Check if this is a chat event.
     */
    public function isChatEvent(): bool
    {
        return in_array($this, [
            self::CHATS_SET,
            self::CHATS_UPSERT,
            self::CHATS_UPDATE,
            self::CHATS_DELETE,
        ], true);
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::APPLICATION_STARTUP => 'Application Startup',
            self::QRCODE_UPDATED => 'QR Code Updated',
            self::MESSAGES_SET => 'Messages Set',
            self::MESSAGES_UPSERT => 'Message Received',
            self::MESSAGES_UPDATE => 'Message Updated',
            self::MESSAGES_DELETE => 'Message Deleted',
            self::SEND_MESSAGE => 'Message Sent',
            self::CONTACTS_SET => 'Contacts Set',
            self::CONTACTS_UPSERT => 'Contact Added',
            self::CONTACTS_UPDATE => 'Contact Updated',
            self::PRESENCE_UPDATE => 'Presence Updated',
            self::CHATS_SET => 'Chats Set',
            self::CHATS_UPSERT => 'Chat Added',
            self::CHATS_UPDATE => 'Chat Updated',
            self::CHATS_DELETE => 'Chat Deleted',
            self::GROUPS_UPSERT => 'Group Added',
            self::GROUP_UPDATE => 'Group Updated',
            self::GROUP_PARTICIPANTS_UPDATE => 'Group Participants Updated',
            self::CONNECTION_UPDATE => 'Connection Updated',
            self::LABELS_EDIT => 'Labels Edited',
            self::LABELS_ASSOCIATION => 'Labels Associated',
            self::CALL => 'Call Received',
            self::TYPEBOT_START => 'Typebot Started',
            self::TYPEBOT_CHANGE_STATUS => 'Typebot Status Changed',
            self::UNKNOWN => 'Unknown Event',
        };
    }

    /**
     * Create from string value.
     */
    public static function fromString(string $value): self
    {
        $normalized = strtoupper($value);

        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case;
            }
        }

        return self::UNKNOWN;
    }
}
