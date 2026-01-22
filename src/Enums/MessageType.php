<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Enums;

/**
 * Message types supported by Evolution API.
 */
enum MessageType: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case VIDEO = 'video';
    case AUDIO = 'audio';
    case DOCUMENT = 'document';
    case STICKER = 'sticker';
    case LOCATION = 'location';
    case CONTACT = 'contact';
    case CONTACT_ARRAY = 'contactArray';
    case POLL = 'poll';
    case LIST = 'list';
    case BUTTON = 'button';
    case TEMPLATE = 'template';
    case REACTION = 'reaction';
    case STATUS = 'status';
    case UNKNOWN = 'unknown';

    /**
     * Check if this is a media type.
     */
    public function isMedia(): bool
    {
        return in_array($this, [
            self::IMAGE,
            self::VIDEO,
            self::AUDIO,
            self::DOCUMENT,
            self::STICKER,
        ], true);
    }

    /**
     * Check if this is an interactive type.
     */
    public function isInteractive(): bool
    {
        return in_array($this, [
            self::POLL,
            self::LIST,
            self::BUTTON,
        ], true);
    }

    /**
     * Get the API endpoint suffix for this message type.
     */
    public function endpoint(): string
    {
        return match ($this) {
            self::TEXT => 'sendText',
            self::IMAGE, self::VIDEO, self::DOCUMENT => 'sendMedia',
            self::AUDIO => 'sendWhatsAppAudio',
            self::STICKER => 'sendSticker',
            self::LOCATION => 'sendLocation',
            self::CONTACT, self::CONTACT_ARRAY => 'sendContact',
            self::POLL => 'sendPoll',
            self::LIST => 'sendList',
            self::BUTTON => 'sendButtons',
            self::TEMPLATE => 'sendTemplate',
            self::REACTION => 'sendReaction',
            self::STATUS => 'sendStatus',
            default => 'sendText',
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text',
            self::IMAGE => 'Image',
            self::VIDEO => 'Video',
            self::AUDIO => 'Audio',
            self::DOCUMENT => 'Document',
            self::STICKER => 'Sticker',
            self::LOCATION => 'Location',
            self::CONTACT => 'Contact',
            self::CONTACT_ARRAY => 'Contact List',
            self::POLL => 'Poll',
            self::LIST => 'List',
            self::BUTTON => 'Buttons',
            self::TEMPLATE => 'Template',
            self::REACTION => 'Reaction',
            self::STATUS => 'Status',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Create from API response value.
     */
    public static function fromApi(string $value): self
    {
        return match (strtolower($value)) {
            'text', 'conversation', 'extendedtextmessage' => self::TEXT,
            'image', 'imagemessage' => self::IMAGE,
            'video', 'videomessage' => self::VIDEO,
            'audio', 'audiomessage', 'ptt' => self::AUDIO,
            'document', 'documentmessage' => self::DOCUMENT,
            'sticker', 'stickermessage' => self::STICKER,
            'location', 'locationmessage' => self::LOCATION,
            'contact', 'contactmessage' => self::CONTACT,
            'contactarray', 'contactsmessage' => self::CONTACT_ARRAY,
            'poll', 'pollcreationmessage' => self::POLL,
            'list', 'listmessage' => self::LIST,
            'button', 'buttonsmessage' => self::BUTTON,
            'template', 'templatemessage' => self::TEMPLATE,
            'reaction', 'reactionmessage' => self::REACTION,
            'status' => self::STATUS,
            default => self::UNKNOWN,
        };
    }
}
