<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Enums\MessageType;

describe('MessageType Enum', function () {
    describe('cases', function () {
        it('has all expected cases', function () {
            $cases = MessageType::cases();

            expect($cases)->toHaveCount(16);
            expect(array_map(fn ($case) => $case->value, $cases))->toContain(
                'text',
                'image',
                'video',
                'audio',
                'document',
                'sticker',
                'location',
                'contact',
                'contactArray',
                'poll',
                'list',
                'button',
                'template',
                'reaction',
                'status',
                'unknown'
            );
        });

        it('can be created from value', function () {
            expect(MessageType::from('text'))->toBe(MessageType::TEXT);
            expect(MessageType::from('image'))->toBe(MessageType::IMAGE);
            expect(MessageType::from('video'))->toBe(MessageType::VIDEO);
            expect(MessageType::from('audio'))->toBe(MessageType::AUDIO);
            expect(MessageType::from('document'))->toBe(MessageType::DOCUMENT);
            expect(MessageType::from('sticker'))->toBe(MessageType::STICKER);
            expect(MessageType::from('location'))->toBe(MessageType::LOCATION);
            expect(MessageType::from('contact'))->toBe(MessageType::CONTACT);
            expect(MessageType::from('contactArray'))->toBe(MessageType::CONTACT_ARRAY);
            expect(MessageType::from('poll'))->toBe(MessageType::POLL);
            expect(MessageType::from('list'))->toBe(MessageType::LIST);
            expect(MessageType::from('button'))->toBe(MessageType::BUTTON);
            expect(MessageType::from('template'))->toBe(MessageType::TEMPLATE);
            expect(MessageType::from('reaction'))->toBe(MessageType::REACTION);
            expect(MessageType::from('status'))->toBe(MessageType::STATUS);
            expect(MessageType::from('unknown'))->toBe(MessageType::UNKNOWN);
        });

        it('throws exception for invalid value', function () {
            MessageType::from('invalid');
        })->throws(ValueError::class);

        it('can try from value without throwing', function () {
            expect(MessageType::tryFrom('text'))->toBe(MessageType::TEXT);
            expect(MessageType::tryFrom('invalid'))->toBeNull();
        });
    });

    describe('isMedia', function () {
        it('returns true for media types', function () {
            expect(MessageType::IMAGE->isMedia())->toBeTrue();
            expect(MessageType::VIDEO->isMedia())->toBeTrue();
            expect(MessageType::AUDIO->isMedia())->toBeTrue();
            expect(MessageType::DOCUMENT->isMedia())->toBeTrue();
            expect(MessageType::STICKER->isMedia())->toBeTrue();
        });

        it('returns false for non-media types', function () {
            expect(MessageType::TEXT->isMedia())->toBeFalse();
            expect(MessageType::LOCATION->isMedia())->toBeFalse();
            expect(MessageType::CONTACT->isMedia())->toBeFalse();
            expect(MessageType::CONTACT_ARRAY->isMedia())->toBeFalse();
            expect(MessageType::POLL->isMedia())->toBeFalse();
            expect(MessageType::LIST->isMedia())->toBeFalse();
            expect(MessageType::BUTTON->isMedia())->toBeFalse();
            expect(MessageType::TEMPLATE->isMedia())->toBeFalse();
            expect(MessageType::REACTION->isMedia())->toBeFalse();
            expect(MessageType::STATUS->isMedia())->toBeFalse();
            expect(MessageType::UNKNOWN->isMedia())->toBeFalse();
        });
    });

    describe('isInteractive', function () {
        it('returns true for interactive types', function () {
            expect(MessageType::POLL->isInteractive())->toBeTrue();
            expect(MessageType::LIST->isInteractive())->toBeTrue();
            expect(MessageType::BUTTON->isInteractive())->toBeTrue();
        });

        it('returns false for non-interactive types', function () {
            expect(MessageType::TEXT->isInteractive())->toBeFalse();
            expect(MessageType::IMAGE->isInteractive())->toBeFalse();
            expect(MessageType::VIDEO->isInteractive())->toBeFalse();
            expect(MessageType::AUDIO->isInteractive())->toBeFalse();
            expect(MessageType::DOCUMENT->isInteractive())->toBeFalse();
            expect(MessageType::STICKER->isInteractive())->toBeFalse();
            expect(MessageType::LOCATION->isInteractive())->toBeFalse();
            expect(MessageType::CONTACT->isInteractive())->toBeFalse();
            expect(MessageType::CONTACT_ARRAY->isInteractive())->toBeFalse();
            expect(MessageType::TEMPLATE->isInteractive())->toBeFalse();
            expect(MessageType::REACTION->isInteractive())->toBeFalse();
            expect(MessageType::STATUS->isInteractive())->toBeFalse();
            expect(MessageType::UNKNOWN->isInteractive())->toBeFalse();
        });
    });

    describe('endpoint', function () {
        it('returns correct endpoint for text', function () {
            expect(MessageType::TEXT->endpoint())->toBe('sendText');
        });

        it('returns correct endpoint for media types', function () {
            expect(MessageType::IMAGE->endpoint())->toBe('sendMedia');
            expect(MessageType::VIDEO->endpoint())->toBe('sendMedia');
            expect(MessageType::DOCUMENT->endpoint())->toBe('sendMedia');
        });

        it('returns correct endpoint for audio', function () {
            expect(MessageType::AUDIO->endpoint())->toBe('sendWhatsAppAudio');
        });

        it('returns correct endpoint for sticker', function () {
            expect(MessageType::STICKER->endpoint())->toBe('sendSticker');
        });

        it('returns correct endpoint for location', function () {
            expect(MessageType::LOCATION->endpoint())->toBe('sendLocation');
        });

        it('returns correct endpoint for contact types', function () {
            expect(MessageType::CONTACT->endpoint())->toBe('sendContact');
            expect(MessageType::CONTACT_ARRAY->endpoint())->toBe('sendContact');
        });

        it('returns correct endpoint for interactive types', function () {
            expect(MessageType::POLL->endpoint())->toBe('sendPoll');
            expect(MessageType::LIST->endpoint())->toBe('sendList');
            expect(MessageType::BUTTON->endpoint())->toBe('sendButtons');
        });

        it('returns correct endpoint for template', function () {
            expect(MessageType::TEMPLATE->endpoint())->toBe('sendTemplate');
        });

        it('returns correct endpoint for reaction', function () {
            expect(MessageType::REACTION->endpoint())->toBe('sendReaction');
        });

        it('returns correct endpoint for status', function () {
            expect(MessageType::STATUS->endpoint())->toBe('sendStatus');
        });

        it('returns sendText for unknown type', function () {
            expect(MessageType::UNKNOWN->endpoint())->toBe('sendText');
        });
    });

    describe('label', function () {
        it('returns human-readable labels', function () {
            expect(MessageType::TEXT->label())->toBe('Text');
            expect(MessageType::IMAGE->label())->toBe('Image');
            expect(MessageType::VIDEO->label())->toBe('Video');
            expect(MessageType::AUDIO->label())->toBe('Audio');
            expect(MessageType::DOCUMENT->label())->toBe('Document');
            expect(MessageType::STICKER->label())->toBe('Sticker');
            expect(MessageType::LOCATION->label())->toBe('Location');
            expect(MessageType::CONTACT->label())->toBe('Contact');
            expect(MessageType::CONTACT_ARRAY->label())->toBe('Contact List');
            expect(MessageType::POLL->label())->toBe('Poll');
            expect(MessageType::LIST->label())->toBe('List');
            expect(MessageType::BUTTON->label())->toBe('Buttons');
            expect(MessageType::TEMPLATE->label())->toBe('Template');
            expect(MessageType::REACTION->label())->toBe('Reaction');
            expect(MessageType::STATUS->label())->toBe('Status');
            expect(MessageType::UNKNOWN->label())->toBe('Unknown');
        });
    });

    describe('fromApi', function () {
        it('maps text variations correctly', function () {
            expect(MessageType::fromApi('text'))->toBe(MessageType::TEXT);
            expect(MessageType::fromApi('TEXT'))->toBe(MessageType::TEXT);
            expect(MessageType::fromApi('conversation'))->toBe(MessageType::TEXT);
            expect(MessageType::fromApi('extendedTextMessage'))->toBe(MessageType::TEXT);
        });

        it('maps image variations correctly', function () {
            expect(MessageType::fromApi('image'))->toBe(MessageType::IMAGE);
            expect(MessageType::fromApi('IMAGE'))->toBe(MessageType::IMAGE);
            expect(MessageType::fromApi('imageMessage'))->toBe(MessageType::IMAGE);
        });

        it('maps video variations correctly', function () {
            expect(MessageType::fromApi('video'))->toBe(MessageType::VIDEO);
            expect(MessageType::fromApi('VIDEO'))->toBe(MessageType::VIDEO);
            expect(MessageType::fromApi('videoMessage'))->toBe(MessageType::VIDEO);
        });

        it('maps audio variations correctly', function () {
            expect(MessageType::fromApi('audio'))->toBe(MessageType::AUDIO);
            expect(MessageType::fromApi('AUDIO'))->toBe(MessageType::AUDIO);
            expect(MessageType::fromApi('audioMessage'))->toBe(MessageType::AUDIO);
            expect(MessageType::fromApi('ptt'))->toBe(MessageType::AUDIO);
        });

        it('maps document variations correctly', function () {
            expect(MessageType::fromApi('document'))->toBe(MessageType::DOCUMENT);
            expect(MessageType::fromApi('documentMessage'))->toBe(MessageType::DOCUMENT);
        });

        it('maps sticker variations correctly', function () {
            expect(MessageType::fromApi('sticker'))->toBe(MessageType::STICKER);
            expect(MessageType::fromApi('stickerMessage'))->toBe(MessageType::STICKER);
        });

        it('maps location variations correctly', function () {
            expect(MessageType::fromApi('location'))->toBe(MessageType::LOCATION);
            expect(MessageType::fromApi('locationMessage'))->toBe(MessageType::LOCATION);
        });

        it('maps contact variations correctly', function () {
            expect(MessageType::fromApi('contact'))->toBe(MessageType::CONTACT);
            expect(MessageType::fromApi('contactMessage'))->toBe(MessageType::CONTACT);
        });

        it('maps contact array variations correctly', function () {
            expect(MessageType::fromApi('contactArray'))->toBe(MessageType::CONTACT_ARRAY);
            expect(MessageType::fromApi('contactsMessage'))->toBe(MessageType::CONTACT_ARRAY);
        });

        it('maps poll variations correctly', function () {
            expect(MessageType::fromApi('poll'))->toBe(MessageType::POLL);
            expect(MessageType::fromApi('pollCreationMessage'))->toBe(MessageType::POLL);
        });

        it('maps list variations correctly', function () {
            expect(MessageType::fromApi('list'))->toBe(MessageType::LIST);
            expect(MessageType::fromApi('listMessage'))->toBe(MessageType::LIST);
        });

        it('maps button variations correctly', function () {
            expect(MessageType::fromApi('button'))->toBe(MessageType::BUTTON);
            expect(MessageType::fromApi('buttonsMessage'))->toBe(MessageType::BUTTON);
        });

        it('maps template variations correctly', function () {
            expect(MessageType::fromApi('template'))->toBe(MessageType::TEMPLATE);
            expect(MessageType::fromApi('templateMessage'))->toBe(MessageType::TEMPLATE);
        });

        it('maps reaction variations correctly', function () {
            expect(MessageType::fromApi('reaction'))->toBe(MessageType::REACTION);
            expect(MessageType::fromApi('reactionMessage'))->toBe(MessageType::REACTION);
        });

        it('maps status correctly', function () {
            expect(MessageType::fromApi('status'))->toBe(MessageType::STATUS);
        });

        it('returns unknown for unrecognized values', function () {
            expect(MessageType::fromApi('invalid'))->toBe(MessageType::UNKNOWN);
            expect(MessageType::fromApi('random'))->toBe(MessageType::UNKNOWN);
            expect(MessageType::fromApi(''))->toBe(MessageType::UNKNOWN);
        });
    });
});
