# Contributing to Laravel Evolution API

Thank you for considering contributing to Laravel Evolution API! This document provides guidelines and instructions for contributing.

## Code of Conduct

By participating in this project, you agree to abide by our Code of Conduct. Please be respectful and constructive in your interactions with other contributors.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When you create a bug report, include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples** (code snippets, configuration, etc.)
- **Describe the behavior you observed and what you expected**
- **Include your environment details** (PHP version, Laravel version, package version)

### Suggesting Enhancements

Enhancement suggestions are welcome! Please provide:

- **A clear and descriptive title**
- **A detailed description of the proposed enhancement**
- **Explain why this enhancement would be useful**
- **Include code examples if applicable**

### Pull Requests

1. Fork the repository
2. Create a new branch from `main` for your feature or fix
3. Write your code following the coding standards below
4. Write or update tests as needed
5. Ensure all tests pass
6. Submit a pull request

## Development Setup

### Prerequisites

- PHP 8.3 or higher
- Composer
- Git

### Installation

```bash
# Clone your fork
git clone https://github.com/your-username/laravel-evolution-api.git
cd laravel-evolution-api

# Install dependencies
composer install

# Run tests to verify setup
composer test
```

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test file
./vendor/bin/pest tests/Unit/Enums/MessageTypeTest.php

# Run tests matching a filter
./vendor/bin/pest --filter="sends text message"
```

### Code Style

We use Laravel Pint for code formatting:

```bash
# Check code style
composer format

# Or run Pint directly
./vendor/bin/pint
```

### Static Analysis

We use PHPStan for static analysis:

```bash
composer analyse
```

## Coding Standards

### PHP Standards

- Follow PSR-12 coding style
- Use strict types: `declare(strict_types=1);`
- Use type hints for parameters and return types
- Document complex methods with PHPDoc blocks

### Naming Conventions

- **Classes**: PascalCase (`EvolutionClient`, `SendMessageJob`)
- **Methods/Functions**: camelCase (`sendText`, `fetchAll`)
- **Variables**: camelCase (`$instanceName`, `$messageData`)
- **Constants**: UPPER_SNAKE_CASE (`MESSAGE_TYPE_TEXT`)
- **Config keys**: snake_case (`base_url`, `api_key`)

### Code Organization

```
src/
├── Client/           # HTTP client and connection management
├── Console/          # Artisan commands
├── Contracts/        # Interfaces
├── DTOs/             # Data Transfer Objects
├── Enums/            # PHP enums
├── Events/           # Laravel events
├── Exceptions/       # Custom exceptions
├── Facades/          # Laravel facades
├── Http/             # Controllers and middleware
├── Jobs/             # Queue jobs
├── Logging/          # Logging utilities
├── Metrics/          # Metrics collection
├── Models/           # Eloquent models
├── Resources/        # API resource classes
├── Services/         # Business logic services
├── Testing/          # Testing utilities
└── Webhooks/         # Webhook processing
```

### Writing Tests

- Write tests for all new features and bug fixes
- Use Pest PHP with descriptive test names
- Group related tests using `describe()` blocks
- Use appropriate assertions

```php
describe('MessageType Enum', function () {
    describe('values', function () {
        it('has a text type', function () {
            expect(MessageType::TEXT->value)->toBe('text');
        });
    });
});
```

### Commit Messages

- Use the present tense ("Add feature" not "Added feature")
- Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
- Keep the first line under 72 characters
- Reference issues and pull requests when relevant

Examples:
```
Add support for template messages
Fix rate limiting not resetting after window
Update dependencies to latest versions
Refactor webhook processing for better performance
```

### Documentation

- Update the README.md if your changes affect usage
- Add PHPDoc blocks for public methods
- Include code examples where helpful

## Pull Request Process

1. **Update documentation** - If your changes affect the public API, update the README
2. **Add tests** - Ensure your code is covered by tests
3. **Follow coding standards** - Run `composer format` and `composer analyse`
4. **Keep PRs focused** - One feature or fix per PR
5. **Write a good description** - Explain what, why, and how

### PR Title Format

```
[Type] Short description

Types:
- feat: New feature
- fix: Bug fix
- docs: Documentation only
- style: Code style changes
- refactor: Code refactoring
- test: Adding tests
- chore: Maintenance tasks
```

Examples:
```
[feat] Add support for button messages
[fix] Handle null response from API
[docs] Update webhook configuration guide
```

## Release Process

Releases are managed by the maintainers. We follow semantic versioning:

- **Major** (1.0.0): Breaking changes
- **Minor** (0.1.0): New features, backward compatible
- **Patch** (0.0.1): Bug fixes, backward compatible

## Questions?

If you have questions, feel free to:

1. Open an issue for discussion
2. Email hello@lynkbyte.com

Thank you for contributing!
