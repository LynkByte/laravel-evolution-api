<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Message\SendTemplateMessageDto;

describe('SendTemplateMessageDto', function () {
    describe('constructor', function () {
        it('creates a template message DTO', function () {
            $dto = new SendTemplateMessageDto(
                number: '5511999999999',
                name: 'hello_world',
                language: 'en'
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->name)->toBe('hello_world');
            expect($dto->language)->toBe('en');
            expect($dto->components)->toBeNull();
        });

        it('creates with components', function () {
            $components = [
                ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => 'John']]]
            ];
            
            $dto = new SendTemplateMessageDto(
                number: '5511999999999',
                name: 'welcome',
                language: 'en',
                components: $components
            );

            expect($dto->components)->toBe($components);
        });

        it('throws exception when number is empty', function () {
            new SendTemplateMessageDto(number: '', name: 'hello', language: 'en');
        })->throws(InvalidArgumentException::class, 'The number field is required.');

        it('throws exception when name is empty', function () {
            new SendTemplateMessageDto(number: '5511999999999', name: '', language: 'en');
        })->throws(InvalidArgumentException::class, 'The name field is required.');

        it('throws exception when language is empty', function () {
            new SendTemplateMessageDto(number: '5511999999999', name: 'hello', language: '');
        })->throws(InvalidArgumentException::class, 'The language field is required.');
    });

    describe('create', function () {
        it('creates a template message with static method', function () {
            $dto = SendTemplateMessageDto::create(
                number: '5511999999999',
                templateName: 'order_confirmation',
                language: 'pt_BR'
            );

            expect($dto->number)->toBe('5511999999999');
            expect($dto->name)->toBe('order_confirmation');
            expect($dto->language)->toBe('pt_BR');
        });

        it('uses default language en', function () {
            $dto = SendTemplateMessageDto::create('5511999999999', 'hello');

            expect($dto->language)->toBe('en');
        });
    });

    describe('withHeader', function () {
        it('adds header component', function () {
            $dto = SendTemplateMessageDto::create('5511999999999', 'welcome')
                ->withHeader([
                    ['type' => 'image', 'image' => ['link' => 'https://example.com/image.jpg']]
                ]);

            expect($dto->components)->toHaveCount(1);
            expect($dto->components[0]['type'])->toBe('header');
            expect($dto->components[0]['parameters'])->toBeArray();
        });
    });

    describe('withBody', function () {
        it('adds body component', function () {
            $dto = SendTemplateMessageDto::create('5511999999999', 'order')
                ->withBody([
                    ['type' => 'text', 'text' => 'John Doe'],
                    ['type' => 'text', 'text' => 'Order #12345']
                ]);

            expect($dto->components)->toHaveCount(1);
            expect($dto->components[0]['type'])->toBe('body');
            expect($dto->components[0]['parameters'])->toHaveCount(2);
        });
    });

    describe('withButtons', function () {
        it('adds button components', function () {
            $dto = SendTemplateMessageDto::create('5511999999999', 'confirmation')
                ->withButtons([
                    [
                        'sub_type' => 'quick_reply',
                        'index' => 0,
                        'parameters' => [['type' => 'payload', 'payload' => 'yes']]
                    ],
                    [
                        'sub_type' => 'quick_reply',
                        'index' => 1,
                        'parameters' => [['type' => 'payload', 'payload' => 'no']]
                    ]
                ]);

            expect($dto->components)->toHaveCount(2);
            expect($dto->components[0]['type'])->toBe('button');
            expect($dto->components[1]['type'])->toBe('button');
        });
    });

    describe('chaining components', function () {
        it('supports method chaining', function () {
            $dto = SendTemplateMessageDto::create('5511999999999', 'order_details')
                ->withHeader([['type' => 'text', 'text' => 'Order Update']])
                ->withBody([
                    ['type' => 'text', 'text' => 'John'],
                    ['type' => 'text', 'text' => '#12345']
                ])
                ->withButtons([
                    ['sub_type' => 'url', 'index' => 0, 'parameters' => [['type' => 'text', 'text' => 'order/12345']]]
                ]);

            expect($dto->components)->toHaveCount(3);
        });
    });

    describe('toApiPayload', function () {
        it('returns required fields only when no components', function () {
            $dto = SendTemplateMessageDto::create('5511999999999', 'hello');

            $payload = $dto->toApiPayload();

            expect($payload)->toBe([
                'number' => '5511999999999',
                'name' => 'hello',
                'language' => 'en',
            ]);
        });

        it('includes components when set', function () {
            $dto = SendTemplateMessageDto::create('5511999999999', 'welcome')
                ->withBody([['type' => 'text', 'text' => 'Test']]);

            $payload = $dto->toApiPayload();

            expect($payload)->toHaveKey('components');
            expect($payload['components'])->toHaveCount(1);
        });
    });
});
