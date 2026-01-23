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

class TestDtoWithGet extends BaseDto
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email = null,
    ) {}

    public function getProperty(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }
}

class TestDtoWithMultipleRequired extends BaseDto
{
    public function __construct(
        public readonly string $field1,
        public readonly string $field2,
    ) {
        $this->validateRequired(['field1', 'field2']);
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

    describe('get', function () {
        it('returns property value when it exists', function () {
            $dto = new TestDtoWithGet(name: 'John', email: 'john@example.com');

            expect($dto->getProperty('name'))->toBe('John');
            expect($dto->getProperty('email'))->toBe('john@example.com');
        });

        it('returns default when property is null', function () {
            $dto = new TestDtoWithGet(name: 'John');

            expect($dto->getProperty('email', 'default@example.com'))->toBe('default@example.com');
        });

        it('returns null as default when property is null and no default provided', function () {
            $dto = new TestDtoWithGet(name: 'John');

            expect($dto->getProperty('email'))->toBeNull();
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

        it('validates multiple required fields', function () {
            $dto = new TestDtoWithMultipleRequired(field1: 'value1', field2: 'value2');

            expect($dto->field1)->toBe('value1');
            expect($dto->field2)->toBe('value2');
        });

        it('throws on first empty field when validating multiple fields', function () {
            new TestDtoWithMultipleRequired(field1: '', field2: 'value2');
        })->throws(InvalidArgumentException::class, 'The field1 field is required.');

        it('throws on second empty field when first is valid', function () {
            new TestDtoWithMultipleRequired(field1: 'value1', field2: '');
        })->throws(InvalidArgumentException::class, 'The field2 field is required.');
    });
});
