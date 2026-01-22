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

});
