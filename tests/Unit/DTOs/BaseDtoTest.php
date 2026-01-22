<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\BaseDto;

// Create a concrete implementation for testing
class TestDto extends BaseDto
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email = null,
        public readonly ?int $age = null,
        public readonly ?TestDto $nested = null,
    ) {}
}

class TestDtoWithValidation extends BaseDto
{
    public function __construct(
        public readonly string $requiredField,
        public readonly ?string $optionalField = null,
    ) {
        $this->validateRequired(['requiredField']);
    }
}

describe('BaseDto', function () {
    describe('make', function () {
        it('creates a DTO instance from array', function () {
            $dto = TestDto::make(['name' => 'John', 'email' => 'john@example.com']);

            expect($dto)->toBeInstanceOf(TestDto::class);
            expect($dto->name)->toBe('John');
            expect($dto->email)->toBe('john@example.com');
        });

        it('creates a DTO with null for unspecified optional properties', function () {
            $dto = TestDto::make(['name' => 'John']);

            expect($dto->name)->toBe('John');
            expect($dto->email)->toBeNull();
            expect($dto->age)->toBeNull();
        });
    });

    describe('fromArray', function () {
        it('is an alias for make', function () {
            $dto = TestDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);

            expect($dto)->toBeInstanceOf(TestDto::class);
            expect($dto->name)->toBe('John');
            expect($dto->email)->toBe('john@example.com');
        });
    });

    describe('toArray', function () {
        it('converts DTO to array excluding null values', function () {
            $dto = new TestDto(name: 'John', email: 'john@example.com');

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'John',
                'email' => 'john@example.com',
            ]);
        });

        it('includes only non-null properties', function () {
            $dto = new TestDto(name: 'John', age: 25);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'John',
                'age' => 25,
            ]);
            expect($array)->not->toHaveKey('email');
        });

        it('recursively converts nested DTOs', function () {
            $nested = new TestDto(name: 'Nested', email: 'nested@example.com');
            $dto = new TestDto(name: 'Parent', nested: $nested);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'Parent',
                'nested' => [
                    'name' => 'Nested',
                    'email' => 'nested@example.com',
                ],
            ]);
        });
    });

    describe('toApiPayload', function () {
        it('returns the same as toArray by default', function () {
            $dto = new TestDto(name: 'John', email: 'john@example.com');

            expect($dto->toApiPayload())->toBe($dto->toArray());
        });
    });

    describe('jsonSerialize', function () {
        it('returns array representation for JSON serialization', function () {
            $dto = new TestDto(name: 'John', email: 'john@example.com');

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });

        it('can be encoded to JSON', function () {
            $dto = new TestDto(name: 'John', email: 'john@example.com');

            $json = json_encode($dto);

            expect($json)->toBe('{"name":"John","email":"john@example.com"}');
        });
    });

    describe('validateRequired', function () {
        it('does not throw when required field is provided', function () {
            $dto = new TestDtoWithValidation(requiredField: 'value');

            expect($dto->requiredField)->toBe('value');
        });

        it('throws exception when required field is empty', function () {
            new TestDtoWithValidation(requiredField: '');
        })->throws(InvalidArgumentException::class, 'The requiredField field is required.');
    });
});
