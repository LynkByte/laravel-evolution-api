# Development Setup

Guide for setting up a local development environment for the Evolution API Laravel package.

## Prerequisites

- PHP 8.2 or higher
- Composer 2.x
- Git
- Node.js (for docs development)

## Clone the Repository

```bash
git clone https://github.com/lynkbyte/laravel-evolution-api.git
cd laravel-evolution-api
```

## Install Dependencies

```bash
composer install
```

## Running Tests

### All Tests

```bash
composer test
# or
./vendor/bin/pest
```

### With Coverage

```bash
composer test:coverage
# or
./vendor/bin/pest --coverage
```

### Specific Tests

```bash
# Run a specific test file
./vendor/bin/pest tests/Unit/Services/MessageServiceTest.php

# Run tests matching a pattern
./vendor/bin/pest --filter="sends text message"

# Run only unit tests
./vendor/bin/pest tests/Unit

# Run only feature tests
./vendor/bin/pest tests/Feature
```

## Code Quality

### Static Analysis

```bash
composer analyse
# or
./vendor/bin/phpstan analyse --level=8
```

### Code Style

```bash
# Check code style
composer cs-check
# or
./vendor/bin/pint --test

# Fix code style
composer cs-fix
# or
./vendor/bin/pint
```

### All Checks

```bash
composer check
# Runs: cs-check, analyse, test
```

## Project Structure

```
laravel-evolution-api/
├── config/
│   └── evolution-api.php       # Package configuration
├── database/
│   └── migrations/             # Database migrations
├── docs/                       # MkDocs documentation
├── src/
│   ├── Console/
│   │   └── Commands/           # Artisan commands
│   ├── Contracts/              # Interfaces
│   ├── DTOs/                   # Data Transfer Objects
│   ├── Enums/                  # Enum classes
│   ├── Events/                 # Laravel events
│   ├── Exceptions/             # Custom exceptions
│   ├── Facades/                # Laravel facades
│   ├── Http/
│   │   ├── Controllers/        # Webhook controllers
│   │   └── Middleware/         # HTTP middleware
│   ├── Jobs/                   # Queue jobs
│   ├── Listeners/              # Event listeners
│   ├── Models/                 # Eloquent models
│   ├── Services/               # Core services
│   │   └── Resources/          # API resources
│   ├── Support/                # Helper classes
│   ├── Testing/                # Testing utilities
│   │   └── Fakes/              # Test fakes
│   └── EvolutionApiServiceProvider.php
├── tests/
│   ├── Feature/                # Feature tests
│   ├── Unit/                   # Unit tests
│   ├── Pest.php                # Pest configuration
│   └── TestCase.php            # Base test case
├── .github/
│   └── workflows/              # GitHub Actions
├── composer.json
├── phpstan.neon
├── pint.json
└── README.md
```

## Creating a Test Application

For testing the package in a real Laravel application:

```bash
# Create a new Laravel app
composer create-project laravel/laravel test-app
cd test-app

# Link the package locally
composer config repositories.local path ../laravel-evolution-api
composer require lynkbyte/laravel-evolution-api:@dev

# Publish assets
php artisan evolution-api:install
```

## Documentation Development

### Install MkDocs

```bash
pip install mkdocs-material
```

### Serve Locally

```bash
cd docs
mkdocs serve
```

Visit `http://localhost:8000` to preview documentation.

### Build Documentation

```bash
cd docs
mkdocs build
```

## Git Workflow

### Branch Naming

- `feature/description` - New features
- `fix/description` - Bug fixes
- `docs/description` - Documentation updates
- `refactor/description` - Code refactoring
- `test/description` - Test additions/updates

### Commit Messages

Follow conventional commits:

```
feat: add support for template messages
fix: handle null response in webhook handler
docs: update installation instructions
test: add coverage for rate limiting
refactor: extract message builder to separate class
```

### Pull Request Process

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Ensure tests pass
5. Submit a pull request

## Environment Variables for Development

Create a `.env.testing` file:

```bash
EVOLUTION_API_SERVER_URL=http://localhost:8080
EVOLUTION_API_KEY=test-api-key
EVOLUTION_API_DEFAULT_INSTANCE=test-instance
```

## Debugging

### Enable Debug Logging

```php
// In tests
config(['evolution-api.logging.log_requests' => true]);
config(['evolution-api.logging.log_responses' => true]);
```

### Using Ray

```bash
composer require spatie/ray --dev
```

```php
ray($response)->label('API Response');
```

## IDE Setup

### PHPStorm

1. Enable Laravel plugin
2. Configure PHP interpreter (8.2+)
3. Set up PHPStan inspection

### VS Code

Recommended extensions:
- PHP Intelephense
- Laravel Blade Snippets
- PHPStan

## Useful Commands

```bash
# Run all checks before committing
composer check

# Generate IDE helper files (in test app)
php artisan ide-helper:generate
php artisan ide-helper:models

# Clear caches
php artisan cache:clear
php artisan config:clear
```

## Next Steps

- [Testing Guide](testing.md) - Writing tests
- [Code Style Guide](code-style.md) - Coding standards
