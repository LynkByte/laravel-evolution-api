<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lynkbyte\EvolutionApi\Models\EvolutionContact;

describe('EvolutionContact Model', function () {

    uses(RefreshDatabase::class);

    describe('fillable attributes', function () {
        it('has correct fillable attributes', function () {
            $contact = new EvolutionContact([
                'instance_name' => 'test-instance',
                'remote_jid' => '5511999999999@s.whatsapp.net',
                'phone_number' => '+5511999999999',
                'push_name' => 'John Doe',
                'profile_picture_url' => 'https://example.com/pic.jpg',
                'is_business' => false,
                'is_group' => false,
                'is_blocked' => false,
                'metadata' => ['key' => 'value'],
            ]);

            expect($contact->instance_name)->toBe('test-instance');
            expect($contact->remote_jid)->toBe('5511999999999@s.whatsapp.net');
            expect($contact->phone_number)->toBe('+5511999999999');
            expect($contact->push_name)->toBe('John Doe');
            expect($contact->profile_picture_url)->toBe('https://example.com/pic.jpg');
            expect($contact->is_business)->toBeFalse();
            expect($contact->is_group)->toBeFalse();
            expect($contact->is_blocked)->toBeFalse();
            expect($contact->metadata)->toBe(['key' => 'value']);
        });
    });

    describe('casts', function () {
        it('casts is_business to boolean', function () {
            $contact = new EvolutionContact(['is_business' => 1]);

            expect($contact->is_business)->toBeBool();
        });

        it('casts is_group to boolean', function () {
            $contact = new EvolutionContact(['is_group' => 1]);

            expect($contact->is_group)->toBeBool();
        });

        it('casts is_blocked to boolean', function () {
            $contact = new EvolutionContact(['is_blocked' => 1]);

            expect($contact->is_blocked)->toBeBool();
        });

        it('casts metadata to array', function () {
            $contact = new EvolutionContact;
            $contact->metadata = ['key' => 'value'];

            expect($contact->metadata)->toBeArray();
        });
    });

    describe('getDisplayName', function () {
        it('returns push_name when available', function () {
            $contact = new EvolutionContact([
                'push_name' => 'John Doe',
                'phone_number' => '+5511999999999',
                'remote_jid' => '5511999999999@s.whatsapp.net',
            ]);

            expect($contact->getDisplayName())->toBe('John Doe');
        });

        it('returns phone_number when push_name is null', function () {
            $contact = new EvolutionContact([
                'push_name' => null,
                'phone_number' => '+5511999999999',
                'remote_jid' => '5511999999999@s.whatsapp.net',
            ]);

            expect($contact->getDisplayName())->toBe('+5511999999999');
        });

        it('returns remote_jid when both are null', function () {
            $contact = new EvolutionContact([
                'push_name' => null,
                'phone_number' => null,
                'remote_jid' => '5511999999999@s.whatsapp.net',
            ]);

            expect($contact->getDisplayName())->toBe('5511999999999@s.whatsapp.net');
        });
    });

    describe('isGroup', function () {
        it('returns true when is_group flag is true', function () {
            $contact = new EvolutionContact([
                'is_group' => true,
                'remote_jid' => '5511999999999@s.whatsapp.net',
            ]);

            expect($contact->isGroup())->toBeTrue();
        });

        it('returns true when remote_jid contains @g.us', function () {
            $contact = new EvolutionContact([
                'is_group' => false,
                'remote_jid' => '120363123456789012@g.us',
            ]);

            expect($contact->isGroup())->toBeTrue();
        });

        it('returns false for individual contact', function () {
            $contact = new EvolutionContact([
                'is_group' => false,
                'remote_jid' => '5511999999999@s.whatsapp.net',
            ]);

            expect($contact->isGroup())->toBeFalse();
        });
    });

    describe('isBusiness', function () {
        it('returns true when is_business is true', function () {
            $contact = new EvolutionContact(['is_business' => true]);

            expect($contact->isBusiness())->toBeTrue();
        });

        it('returns false when is_business is false', function () {
            $contact = new EvolutionContact(['is_business' => false]);

            expect($contact->isBusiness())->toBeFalse();
        });
    });

    describe('isBlocked', function () {
        it('returns true when is_blocked is true', function () {
            $contact = new EvolutionContact(['is_blocked' => true]);

            expect($contact->isBlocked())->toBeTrue();
        });

        it('returns false when is_blocked is false', function () {
            $contact = new EvolutionContact(['is_blocked' => false]);

            expect($contact->isBlocked())->toBeFalse();
        });
    });

    describe('relationships', function () {
        it('defines instance relationship', function () {
            $contact = new EvolutionContact;

            expect($contact->instance())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        });

        it('defines messages relationship', function () {
            $contact = new EvolutionContact([
                'instance_name' => 'test-instance',
                'remote_jid' => '5511999999999@s.whatsapp.net',
            ]);

            expect($contact->messages())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        });
    });

    describe('scopes', function () {
        it('has forInstance scope', function () {
            $query = EvolutionContact::forInstance('test-instance');

            expect($query->toSql())->toContain('instance_name');
        });

        it('has groups scope', function () {
            $query = EvolutionContact::groups();

            expect($query->toSql())->toContain('is_group');
        });

        it('has individuals scope', function () {
            $query = EvolutionContact::individuals();

            expect($query->toSql())->toContain('is_group');
        });

        it('has business scope', function () {
            $query = EvolutionContact::business();

            expect($query->toSql())->toContain('is_business');
        });

        it('has blocked scope', function () {
            $query = EvolutionContact::blocked();

            expect($query->toSql())->toContain('is_blocked');
        });
    });

    describe('table configuration', function () {
        it('uses correct table name', function () {
            $contact = new EvolutionContact;

            expect($contact->getTable())->toBe('evolution_contacts');
        });
    });

    describe('touchLastMessage', function () {
        it('updates last_message_at timestamp', function () {
            $contact = EvolutionContact::create([
                'instance_name' => 'test-instance',
                'remote_jid' => '5511999999999@s.whatsapp.net',
                'is_group' => false,
            ]);

            expect($contact->last_message_at)->toBeNull();

            $result = $contact->touchLastMessage();

            expect($result)->toBeTrue();
            expect($contact->last_message_at)->not->toBeNull();

            // Verify database was updated
            $contact->refresh();
            expect($contact->last_message_at)->not->toBeNull();
        });

        it('updates last_message_at on subsequent calls', function () {
            $contact = EvolutionContact::create([
                'instance_name' => 'test-instance',
                'remote_jid' => '5511888888888@s.whatsapp.net',
                'is_group' => false,
            ]);

            $contact->touchLastMessage();
            $firstTimestamp = $contact->last_message_at;

            // Simulate time passing (at least 1 second)
            sleep(1);

            $contact->touchLastMessage();
            $secondTimestamp = $contact->last_message_at;

            expect($secondTimestamp->greaterThan($firstTimestamp))->toBeTrue();
        });
    });

    describe('block', function () {
        it('sets is_blocked to true', function () {
            $contact = EvolutionContact::create([
                'instance_name' => 'test-instance',
                'remote_jid' => '5511777777777@s.whatsapp.net',
                'is_blocked' => false,
            ]);

            $result = $contact->block();

            expect($result)->toBeTrue();
            expect($contact->is_blocked)->toBeTrue();

            // Verify database was updated
            $contact->refresh();
            expect($contact->is_blocked)->toBeTrue();
        });

        it('keeps is_blocked true when already blocked', function () {
            $contact = EvolutionContact::create([
                'instance_name' => 'test-instance',
                'remote_jid' => '5511666666666@s.whatsapp.net',
                'is_blocked' => true,
            ]);

            $result = $contact->block();

            expect($result)->toBeTrue();
            expect($contact->is_blocked)->toBeTrue();
        });
    });

    describe('unblock', function () {
        it('sets is_blocked to false', function () {
            $contact = EvolutionContact::create([
                'instance_name' => 'test-instance',
                'remote_jid' => '5511555555555@s.whatsapp.net',
                'is_blocked' => true,
            ]);

            $result = $contact->unblock();

            expect($result)->toBeTrue();
            expect($contact->is_blocked)->toBeFalse();

            // Verify database was updated
            $contact->refresh();
            expect($contact->is_blocked)->toBeFalse();
        });

        it('keeps is_blocked false when already unblocked', function () {
            $contact = EvolutionContact::create([
                'instance_name' => 'test-instance',
                'remote_jid' => '5511444444444@s.whatsapp.net',
                'is_blocked' => false,
            ]);

            $result = $contact->unblock();

            expect($result)->toBeTrue();
            expect($contact->is_blocked)->toBeFalse();
        });
    });

    describe('findByJid', function () {
        it('finds contact by remote_jid and instance_name', function () {
            EvolutionContact::create([
                'instance_name' => 'test-instance',
                'remote_jid' => '5511333333333@s.whatsapp.net',
                'push_name' => 'Test Contact',
            ]);

            $found = EvolutionContact::findByJid('5511333333333@s.whatsapp.net', 'test-instance');

            expect($found)->not->toBeNull();
            expect($found->remote_jid)->toBe('5511333333333@s.whatsapp.net');
            expect($found->push_name)->toBe('Test Contact');
        });

        it('returns null when contact does not exist', function () {
            $found = EvolutionContact::findByJid('nonexistent@s.whatsapp.net', 'test-instance');

            expect($found)->toBeNull();
        });

        it('returns null when instance_name does not match', function () {
            EvolutionContact::create([
                'instance_name' => 'instance-a',
                'remote_jid' => '5511222222222@s.whatsapp.net',
            ]);

            $found = EvolutionContact::findByJid('5511222222222@s.whatsapp.net', 'instance-b');

            expect($found)->toBeNull();
        });
    });

    describe('findOrCreateByJid', function () {
        it('finds existing contact', function () {
            $existing = EvolutionContact::create([
                'instance_name' => 'test-instance',
                'remote_jid' => '5511111111111@s.whatsapp.net',
                'push_name' => 'Existing Contact',
            ]);

            $found = EvolutionContact::findOrCreateByJid(
                '5511111111111@s.whatsapp.net',
                'test-instance'
            );

            expect($found->id)->toBe($existing->id);
            expect($found->push_name)->toBe('Existing Contact');
        });

        it('creates new contact when not found', function () {
            $contact = EvolutionContact::findOrCreateByJid(
                '5511000000000@s.whatsapp.net',
                'test-instance'
            );

            expect($contact)->toBeInstanceOf(EvolutionContact::class);
            expect($contact->exists)->toBeTrue();
            expect($contact->remote_jid)->toBe('5511000000000@s.whatsapp.net');
            expect($contact->instance_name)->toBe('test-instance');
            expect($contact->is_group)->toBeFalse();
        });

        it('creates new contact with additional attributes', function () {
            $contact = EvolutionContact::findOrCreateByJid(
                '5511999888777@s.whatsapp.net',
                'test-instance',
                ['push_name' => 'New User', 'is_business' => true]
            );

            expect($contact->push_name)->toBe('New User');
            expect($contact->is_business)->toBeTrue();
        });

        it('automatically sets is_group true for group JIDs', function () {
            $contact = EvolutionContact::findOrCreateByJid(
                '120363123456789012@g.us',
                'test-instance'
            );

            expect($contact->is_group)->toBeTrue();
        });

        it('does not overwrite existing contact with attributes', function () {
            EvolutionContact::create([
                'instance_name' => 'test-instance',
                'remote_jid' => '5511987654321@s.whatsapp.net',
                'push_name' => 'Original Name',
            ]);

            $contact = EvolutionContact::findOrCreateByJid(
                '5511987654321@s.whatsapp.net',
                'test-instance',
                ['push_name' => 'New Name']
            );

            expect($contact->push_name)->toBe('Original Name');
        });
    });

});
