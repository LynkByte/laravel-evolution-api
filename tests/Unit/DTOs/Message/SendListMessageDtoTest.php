<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Message\ListMessageBuilder;
use Lynkbyte\EvolutionApi\DTOs\Message\SendListMessageDto;

describe('SendListMessageDto', function () {
    describe('constructor', function () {
        it('creates a list message DTO', function () {
            $sections = [
                [
                    'title' => 'Section 1',
                    'rows' => [
                        ['title' => 'Option 1', 'rowId' => 'opt1', 'description' => 'First option'],
                    ],
                ],
            ];

            $dto = new SendListMessageDto(
                number: '5511999999999',
                title: 'Choose an option',
                description: 'Please select from the list',
                buttonText: 'View Options',
                footerText: 'Footer text',
                sections: $sections
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->title)->toBe('Choose an option');
            expect($dto->description)->toBe('Please select from the list');
            expect($dto->buttonText)->toBe('View Options');
            expect($dto->footerText)->toBe('Footer text');
            expect($dto->sections)->toBe($sections);
        });

        it('throws exception when number is empty', function () {
            new SendListMessageDto(
                number: '',
                title: 'Title',
                description: 'Desc',
                buttonText: 'Button',
                footerText: 'Footer',
                sections: [['title' => 'Section', 'rows' => []]]
            );
        })->throws(InvalidArgumentException::class, 'The number field is required.');

        it('throws exception when title is empty', function () {
            new SendListMessageDto(
                number: '5511999999999',
                title: '',
                description: 'Desc',
                buttonText: 'Button',
                footerText: 'Footer',
                sections: [['title' => 'Section', 'rows' => []]]
            );
        })->throws(InvalidArgumentException::class, 'The title field is required.');

        it('throws exception when buttonText is empty', function () {
            new SendListMessageDto(
                number: '5511999999999',
                title: 'Title',
                description: 'Desc',
                buttonText: '',
                footerText: 'Footer',
                sections: [['title' => 'Section', 'rows' => []]]
            );
        })->throws(InvalidArgumentException::class, 'The buttonText field is required.');

        it('throws exception when sections is empty', function () {
            new SendListMessageDto(
                number: '5511999999999',
                title: 'Title',
                description: 'Desc',
                buttonText: 'Button',
                footerText: 'Footer',
                sections: []
            );
        })->throws(InvalidArgumentException::class, 'The sections field is required.');
    });

    describe('create', function () {
        it('returns a ListMessageBuilder', function () {
            $builder = SendListMessageDto::create('5511999999999', 'Choose option');

            expect($builder)->toBeInstanceOf(ListMessageBuilder::class);
        });
    });

    describe('toApiPayload', function () {
        it('returns correct payload structure', function () {
            $sections = [
                ['title' => 'Section 1', 'rows' => [['title' => 'Row 1', 'rowId' => 'r1']]],
            ];

            $dto = new SendListMessageDto(
                number: '5511999999999',
                title: 'Title',
                description: 'Description',
                buttonText: 'Button',
                footerText: 'Footer',
                sections: $sections
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('number');
            expect($payload)->toHaveKey('title');
            expect($payload)->toHaveKey('description');
            expect($payload)->toHaveKey('buttonText');
            expect($payload)->toHaveKey('footerText');
            expect($payload)->toHaveKey('sections');
        });

        it('includes delay when set', function () {
            $dto = new SendListMessageDto(
                number: '5511999999999',
                title: 'Title',
                description: 'Desc',
                buttonText: 'Button',
                footerText: 'Footer',
                sections: [['title' => 'Sect', 'rows' => []]],
                delay: 1000
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('delay');
            expect($payload['delay'])->toBe(1000);
        });
    });
});

describe('ListMessageBuilder', function () {
    it('builds a list message with sections', function () {
        $dto = SendListMessageDto::create('5511999999999', 'Menu')
            ->description('Please choose')
            ->buttonText('Open Menu')
            ->footerText('Powered by Bot')
            ->addSection('Food', [
                ['title' => 'Pizza', 'rowId' => 'pizza', 'description' => 'Delicious pizza'],
                ['title' => 'Burger', 'rowId' => 'burger', 'description' => 'Juicy burger'],
            ])
            ->addSection('Drinks', [
                ['title' => 'Cola', 'rowId' => 'cola'],
                ['title' => 'Water', 'rowId' => 'water'],
            ])
            ->build();

        expect($dto)->toBeInstanceOf(SendListMessageDto::class);
        expect($dto->number)->toBe('5511999999999');
        expect($dto->title)->toBe('Menu');
        expect($dto->description)->toBe('Please choose');
        expect($dto->buttonText)->toBe('Open Menu');
        expect($dto->footerText)->toBe('Powered by Bot');
        expect($dto->sections)->toHaveCount(2);
        expect($dto->sections[0]['title'])->toBe('Food');
        expect($dto->sections[0]['rows'])->toHaveCount(2);
        expect($dto->sections[1]['title'])->toBe('Drinks');
    });

    it('adds rows to the last section', function () {
        $dto = SendListMessageDto::create('5511999999999', 'Menu')
            ->buttonText('Open')
            ->addSection('Options', [])
            ->addRow('Option A', 'opt-a', 'Description A')
            ->addRow('Option B', 'opt-b')
            ->build();

        expect($dto->sections[0]['rows'])->toHaveCount(2);
        expect($dto->sections[0]['rows'][0]['title'])->toBe('Option A');
        expect($dto->sections[0]['rows'][0]['description'])->toBe('Description A');
        expect($dto->sections[0]['rows'][1]['title'])->toBe('Option B');
        expect($dto->sections[0]['rows'][1])->not->toHaveKey('description');
    });

    it('creates a default section when adding rows without existing sections', function () {
        $dto = SendListMessageDto::create('5511999999999', 'Menu')
            ->buttonText('Open')
            ->addRow('Option A', 'opt-a')
            ->build();

        expect($dto->sections)->toHaveCount(1);
        expect($dto->sections[0]['rows'])->toHaveCount(1);
    });

    it('includes delay when set', function () {
        $dto = SendListMessageDto::create('5511999999999', 'Menu')
            ->buttonText('Open')
            ->addSection('Opts', [['title' => 'A', 'rowId' => 'a']])
            ->delay(2000)
            ->build();

        expect($dto->delay)->toBe(2000);
    });

    it('uses default button text', function () {
        $dto = SendListMessageDto::create('5511999999999', 'Menu')
            ->addSection('Opts', [['title' => 'A', 'rowId' => 'a']])
            ->build();

        expect($dto->buttonText)->toBe('Options');
    });
});
