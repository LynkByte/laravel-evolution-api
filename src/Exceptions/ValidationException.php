<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Exceptions;

/**
 * Exception thrown when validation fails.
 */
class ValidationException extends EvolutionApiException
{
    /**
     * The validation errors.
     *
     * @var array<string, array<string>>
     */
    protected array $errors = [];

    /**
     * Create a new validation exception.
     *
     * @param  array<string, array<string>>  $errors
     */
    public function __construct(array $errors, ?string $instanceName = null)
    {
        $message = $this->buildMessage($errors);

        parent::__construct(
            message: $message,
            code: 422,
            statusCode: 422,
            instanceName: $instanceName
        );

        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Build the error message from errors array.
     *
     * @param  array<string, array<string>>  $errors
     */
    protected function buildMessage(array $errors): string
    {
        $messages = [];

        foreach ($errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = "{$field}: {$error}";
            }
        }

        return 'Validation failed: '.implode(', ', $messages);
    }

    /**
     * Create exception from errors array.
     *
     * @param  array<string, array<string>>  $errors
     */
    public static function withErrors(array $errors, ?string $instanceName = null): static
    {
        return new static($errors, $instanceName);
    }

    /**
     * Create exception for single field error.
     */
    public static function forField(string $field, string $error, ?string $instanceName = null): static
    {
        return new static([$field => [$error]], $instanceName);
    }

    /**
     * Create exception for required field.
     */
    public static function requiredField(string $field, ?string $instanceName = null): static
    {
        return static::forField($field, "The {$field} field is required.", $instanceName);
    }

    /**
     * Create exception for invalid format.
     */
    public static function invalidFormat(string $field, string $expected, ?string $instanceName = null): static
    {
        return static::forField($field, "The {$field} must be a valid {$expected}.", $instanceName);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'errors' => $this->errors,
        ]);
    }
}
