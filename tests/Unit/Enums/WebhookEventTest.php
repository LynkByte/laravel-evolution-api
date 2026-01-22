<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Enums\WebhookEvent;

describe('WebhookEvent Enum', function () {
    describe('cases', function () {
        it('has all expected cases', function () {
            $cases = WebhookEvent::cases();

            expect($cases)->toHaveCount(25);
            expect(array_map(fn ($case) => $case->value, $cases))->toContain(
                'APPLICATION_STARTUP',
                'QRCODE_UPDATED',
                'MESSAGES_SET',
                'MESSAGES_UPSERT',
                'MESSAGES_UPDATE',
                'MESSAGES_DELETE',
                'SEND_MESSAGE',
                'CONTACTS_SET',
                'CONTACTS_UPSERT',
                'CONTACTS_UPDATE',
                'PRESENCE_UPDATE',
                'CHATS_SET',
                'CHATS_UPSERT',
                'CHATS_UPDATE',
                'CHATS_DELETE',
                'GROUPS_UPSERT',
                'GROUP_UPDATE',
                'GROUP_PARTICIPANTS_UPDATE',
                'CONNECTION_UPDATE',
                'LABELS_EDIT',
                'LABELS_ASSOCIATION',
                'CALL',
                'TYPEBOT_START',
                'TYPEBOT_CHANGE_STATUS',
                'UNKNOWN'
            );
        });

        it('can be created from value', function () {
            expect(WebhookEvent::from('MESSAGES_UPSERT'))->toBe(WebhookEvent::MESSAGES_UPSERT);
            expect(WebhookEvent::from('CONNECTION_UPDATE'))->toBe(WebhookEvent::CONNECTION_UPDATE);
            expect(WebhookEvent::from('GROUP_PARTICIPANTS_UPDATE'))->toBe(WebhookEvent::GROUP_PARTICIPANTS_UPDATE);
        });

        it('throws exception for invalid value', function () {
            WebhookEvent::from('invalid');
        })->throws(ValueError::class);

        it('can try from value without throwing', function () {
            expect(WebhookEvent::tryFrom('MESSAGES_UPSERT'))->toBe(WebhookEvent::MESSAGES_UPSERT);
            expect(WebhookEvent::tryFrom('invalid'))->toBeNull();
        });
    });

    describe('isMessageEvent', function () {
        it('returns true for message events', function () {
            expect(WebhookEvent::MESSAGES_SET->isMessageEvent())->toBeTrue();
            expect(WebhookEvent::MESSAGES_UPSERT->isMessageEvent())->toBeTrue();
            expect(WebhookEvent::MESSAGES_UPDATE->isMessageEvent())->toBeTrue();
            expect(WebhookEvent::MESSAGES_DELETE->isMessageEvent())->toBeTrue();
            expect(WebhookEvent::SEND_MESSAGE->isMessageEvent())->toBeTrue();
        });

        it('returns false for non-message events', function () {
            expect(WebhookEvent::APPLICATION_STARTUP->isMessageEvent())->toBeFalse();
            expect(WebhookEvent::QRCODE_UPDATED->isMessageEvent())->toBeFalse();
            expect(WebhookEvent::CONNECTION_UPDATE->isMessageEvent())->toBeFalse();
            expect(WebhookEvent::CONTACTS_SET->isMessageEvent())->toBeFalse();
            expect(WebhookEvent::GROUPS_UPSERT->isMessageEvent())->toBeFalse();
            expect(WebhookEvent::CHATS_SET->isMessageEvent())->toBeFalse();
            expect(WebhookEvent::PRESENCE_UPDATE->isMessageEvent())->toBeFalse();
            expect(WebhookEvent::CALL->isMessageEvent())->toBeFalse();
            expect(WebhookEvent::UNKNOWN->isMessageEvent())->toBeFalse();
        });
    });

    describe('isConnectionEvent', function () {
        it('returns true for connection events', function () {
            expect(WebhookEvent::CONNECTION_UPDATE->isConnectionEvent())->toBeTrue();
            expect(WebhookEvent::QRCODE_UPDATED->isConnectionEvent())->toBeTrue();
            expect(WebhookEvent::APPLICATION_STARTUP->isConnectionEvent())->toBeTrue();
        });

        it('returns false for non-connection events', function () {
            expect(WebhookEvent::MESSAGES_UPSERT->isConnectionEvent())->toBeFalse();
            expect(WebhookEvent::MESSAGES_UPDATE->isConnectionEvent())->toBeFalse();
            expect(WebhookEvent::CONTACTS_SET->isConnectionEvent())->toBeFalse();
            expect(WebhookEvent::GROUPS_UPSERT->isConnectionEvent())->toBeFalse();
            expect(WebhookEvent::CHATS_SET->isConnectionEvent())->toBeFalse();
            expect(WebhookEvent::PRESENCE_UPDATE->isConnectionEvent())->toBeFalse();
            expect(WebhookEvent::CALL->isConnectionEvent())->toBeFalse();
            expect(WebhookEvent::UNKNOWN->isConnectionEvent())->toBeFalse();
        });
    });

    describe('isGroupEvent', function () {
        it('returns true for group events', function () {
            expect(WebhookEvent::GROUPS_UPSERT->isGroupEvent())->toBeTrue();
            expect(WebhookEvent::GROUP_UPDATE->isGroupEvent())->toBeTrue();
            expect(WebhookEvent::GROUP_PARTICIPANTS_UPDATE->isGroupEvent())->toBeTrue();
        });

        it('returns false for non-group events', function () {
            expect(WebhookEvent::APPLICATION_STARTUP->isGroupEvent())->toBeFalse();
            expect(WebhookEvent::QRCODE_UPDATED->isGroupEvent())->toBeFalse();
            expect(WebhookEvent::CONNECTION_UPDATE->isGroupEvent())->toBeFalse();
            expect(WebhookEvent::MESSAGES_UPSERT->isGroupEvent())->toBeFalse();
            expect(WebhookEvent::CONTACTS_SET->isGroupEvent())->toBeFalse();
            expect(WebhookEvent::CHATS_SET->isGroupEvent())->toBeFalse();
            expect(WebhookEvent::PRESENCE_UPDATE->isGroupEvent())->toBeFalse();
            expect(WebhookEvent::CALL->isGroupEvent())->toBeFalse();
            expect(WebhookEvent::UNKNOWN->isGroupEvent())->toBeFalse();
        });
    });

    describe('isContactEvent', function () {
        it('returns true for contact events', function () {
            expect(WebhookEvent::CONTACTS_SET->isContactEvent())->toBeTrue();
            expect(WebhookEvent::CONTACTS_UPSERT->isContactEvent())->toBeTrue();
            expect(WebhookEvent::CONTACTS_UPDATE->isContactEvent())->toBeTrue();
        });

        it('returns false for non-contact events', function () {
            expect(WebhookEvent::APPLICATION_STARTUP->isContactEvent())->toBeFalse();
            expect(WebhookEvent::MESSAGES_UPSERT->isContactEvent())->toBeFalse();
            expect(WebhookEvent::CONNECTION_UPDATE->isContactEvent())->toBeFalse();
            expect(WebhookEvent::GROUPS_UPSERT->isContactEvent())->toBeFalse();
            expect(WebhookEvent::CHATS_SET->isContactEvent())->toBeFalse();
            expect(WebhookEvent::UNKNOWN->isContactEvent())->toBeFalse();
        });
    });

    describe('isChatEvent', function () {
        it('returns true for chat events', function () {
            expect(WebhookEvent::CHATS_SET->isChatEvent())->toBeTrue();
            expect(WebhookEvent::CHATS_UPSERT->isChatEvent())->toBeTrue();
            expect(WebhookEvent::CHATS_UPDATE->isChatEvent())->toBeTrue();
            expect(WebhookEvent::CHATS_DELETE->isChatEvent())->toBeTrue();
        });

        it('returns false for non-chat events', function () {
            expect(WebhookEvent::APPLICATION_STARTUP->isChatEvent())->toBeFalse();
            expect(WebhookEvent::MESSAGES_UPSERT->isChatEvent())->toBeFalse();
            expect(WebhookEvent::CONNECTION_UPDATE->isChatEvent())->toBeFalse();
            expect(WebhookEvent::CONTACTS_SET->isChatEvent())->toBeFalse();
            expect(WebhookEvent::GROUPS_UPSERT->isChatEvent())->toBeFalse();
            expect(WebhookEvent::UNKNOWN->isChatEvent())->toBeFalse();
        });
    });

    describe('label', function () {
        it('returns human-readable labels', function () {
            expect(WebhookEvent::APPLICATION_STARTUP->label())->toBe('Application Startup');
            expect(WebhookEvent::QRCODE_UPDATED->label())->toBe('QR Code Updated');
            expect(WebhookEvent::MESSAGES_SET->label())->toBe('Messages Set');
            expect(WebhookEvent::MESSAGES_UPSERT->label())->toBe('Message Received');
            expect(WebhookEvent::MESSAGES_UPDATE->label())->toBe('Message Updated');
            expect(WebhookEvent::MESSAGES_DELETE->label())->toBe('Message Deleted');
            expect(WebhookEvent::SEND_MESSAGE->label())->toBe('Message Sent');
            expect(WebhookEvent::CONTACTS_SET->label())->toBe('Contacts Set');
            expect(WebhookEvent::CONTACTS_UPSERT->label())->toBe('Contact Added');
            expect(WebhookEvent::CONTACTS_UPDATE->label())->toBe('Contact Updated');
            expect(WebhookEvent::PRESENCE_UPDATE->label())->toBe('Presence Updated');
            expect(WebhookEvent::CHATS_SET->label())->toBe('Chats Set');
            expect(WebhookEvent::CHATS_UPSERT->label())->toBe('Chat Added');
            expect(WebhookEvent::CHATS_UPDATE->label())->toBe('Chat Updated');
            expect(WebhookEvent::CHATS_DELETE->label())->toBe('Chat Deleted');
            expect(WebhookEvent::GROUPS_UPSERT->label())->toBe('Group Added');
            expect(WebhookEvent::GROUP_UPDATE->label())->toBe('Group Updated');
            expect(WebhookEvent::GROUP_PARTICIPANTS_UPDATE->label())->toBe('Group Participants Updated');
            expect(WebhookEvent::CONNECTION_UPDATE->label())->toBe('Connection Updated');
            expect(WebhookEvent::LABELS_EDIT->label())->toBe('Labels Edited');
            expect(WebhookEvent::LABELS_ASSOCIATION->label())->toBe('Labels Associated');
            expect(WebhookEvent::CALL->label())->toBe('Call Received');
            expect(WebhookEvent::TYPEBOT_START->label())->toBe('Typebot Started');
            expect(WebhookEvent::TYPEBOT_CHANGE_STATUS->label())->toBe('Typebot Status Changed');
            expect(WebhookEvent::UNKNOWN->label())->toBe('Unknown Event');
        });
    });

    describe('fromString', function () {
        it('matches exact enum values', function () {
            expect(WebhookEvent::fromString('MESSAGES_UPSERT'))->toBe(WebhookEvent::MESSAGES_UPSERT);
            expect(WebhookEvent::fromString('CONNECTION_UPDATE'))->toBe(WebhookEvent::CONNECTION_UPDATE);
            expect(WebhookEvent::fromString('GROUP_PARTICIPANTS_UPDATE'))->toBe(WebhookEvent::GROUP_PARTICIPANTS_UPDATE);
        });

        it('is case-insensitive', function () {
            expect(WebhookEvent::fromString('messages_upsert'))->toBe(WebhookEvent::MESSAGES_UPSERT);
            expect(WebhookEvent::fromString('connection_update'))->toBe(WebhookEvent::CONNECTION_UPDATE);
            expect(WebhookEvent::fromString('Messages_Upsert'))->toBe(WebhookEvent::MESSAGES_UPSERT);
        });

        it('returns UNKNOWN for unrecognized values', function () {
            expect(WebhookEvent::fromString('invalid'))->toBe(WebhookEvent::UNKNOWN);
            expect(WebhookEvent::fromString(''))->toBe(WebhookEvent::UNKNOWN);
            expect(WebhookEvent::fromString('random_event'))->toBe(WebhookEvent::UNKNOWN);
        });
    });
});
