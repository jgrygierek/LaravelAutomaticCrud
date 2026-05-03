# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`JG/laravel-automatic-crud` is a standalone Laravel package providing automatic CRUD controllers with custom configurations.

## Environment Setup

- PHP 8.5
- Dependencies managed via Composer
- Tests run with PHPUnit 12 + Orchestra Testbench (SQLite in-memory)
- Supports Laravel 12 and 13
- Development can be run directly via `vendor/bin/` or inside Docker using Laravel Sail

## Project Structure

- `src/` — Package source code
    - `Http/Controllers/` — `CrudableController` base class
    - `Http/Interfaces/` — `SearchableInterface`
    - `Traits/` — `ConfigTrait`, `ModelTrait`, `PaginationTrait`, `RequestTrait`, `ResourceTrait`, `SearchTrait`
    - `AutomaticCrudServiceProvider.php` — Service provider
- `config/` — Publishable config file (`automatic-crud.php`)
- `tests/` — Test suite
    - `Feature/` — HTTP-level tests (controller, search/pagination/sorting/validation)
    - `Unit/` — Unit tests (config, model resolution)
    - `Support/` — Test helpers: models, controllers, resources, factories, filters, requests
- `docker-compose.yml` — Laravel Sail service definition (builds from `vendor/laravel/sail/runtimes/8.5`)

## Key Concepts

### CrudableController

Abstract base controller providing `index`, `store`, `show`, `update`, `destroy`. Extend it and optionally override:

- `getModelClass(): string` — return model class (falls back to convention: strips `Controller` suffix, looks up in `model_namespace`)
- `getModelNamespace(): string` — override model namespace without touching config
- `getResourceClass(): string` — override resource class entirely, bypassing convention
- `getResourceCollectionClass(): string` — override collection resource class entirely, bypassing convention
- `getResourceNamespace(): string` — override resource namespace without touching config
- `getRequestNamespace(): string` — override form request namespace without touching config
- `modifyQuery(Builder $query): Builder` — customize the query before pagination
- `defaultIsPaginationEnabled(): bool` — override pagination on/off (defaults to config value)
- `defaultItemsPerPage(): int` — override per-page count (defaults to config value)
- `isPaginationOverrideAllowedInQuery(): bool` — whether `?pagination=` query param is respected (defaults to config value)
- `isPerPageOverrideAllowedInQuery(): bool` — whether `?per_page=` query param is respected (defaults to config value)

Resources and form requests are resolved by convention, but resource classes can also be overridden via `getResourceClass()` / `getResourceCollectionClass()`:

- Resource: `{resource_namespace}\{ModelName}Resource` (falls back to `JsonResource`)
- Collection resource: `{resource_namespace}\{ModelName}CollectionResource` (falls back to `{ModelName}Resource`, then `JsonResource`)
- Form request: `{request_namespace}\{ModelNamePlural}\{Action}{ModelName}Request` (optional — skipped if class doesn't exist)

Form request validation is applied inside each action via `RequestTrait::applyRequest()`. For all five CRUD actions this happens automatically.
For custom actions, validation must be triggered manually or by declaring a `FormRequest` parameter (Laravel's own injection handles it).

### Pagination & Per-Page Priority

Both settings follow the same priority order (highest to lowest):

1. Query parameter (`?pagination=true/false`, `?per_page=25`)
2. Controller method (`defaultIsPaginationEnabled()`, `defaultItemsPerPage()`)
3. Config (`automatic-crud.configs.default.paginate`, `automatic-crud.configs.default.per_page`)

### Custom Configurations

Controllers can set `protected string $configName = 'config_name'` to use a named config from `config/automatic-crud.php`, which overrides default values.

### Sorting

`?sort_by=field` triggers sorting. `?sort_direction=asc|desc` is optional — defaults to `asc` when omitted. 
Sorting is skipped when the query already has an `orderBy` clause (e.g., applied inside `modifyQuery()`).

### Searchable Interface

Models implementing `SearchableInterface` must define `searchFilters(): array` returning filter pipeline classes. These are applied automatically in `index`.

`searchFilters()` can return either a flat array of filter classes or a keyed array where each key is a config name. When keyed, the controller's `$configName` selects the matching set; falls back to `default`, then `[]`.

## Tests

Tests use PHPUnit with Orchestra Testbench. An SQLite in-memory database is created in `TestCase::setUp()`.

When adding new tests:

- Feature tests go in `tests/Feature/`, unit tests in `tests/Unit/`
- Support classes (models, controllers, resources) go in `tests/Support/`
- Follow the order: success → not found → validation error
- Use `assertJsonCount`, `assertJsonPath`, `assertDatabaseHas`, `assertDatabaseMissing`
- No comments inside test files — test method names must be self-explanatory

## Code Style

- Use the best code practices and patterns for Laravel applications
- Remember about valid namespaces, class names, method names, and variable names
- Use all features for a currently used version of PHP, Laravel, and PHPUnit
- Use PHP attributes instead of annotations
- Use `declare(strict_types=1);` in every PHP file
- All methods must have explicit return types
- Always use `use` imports instead of fully qualified class names — both in code and in PHPDoc comments (Pint enforces this automatically)
- Do not add descriptive comments to methods — use only PHPDoc tags (`@param`, `@return`, etc.) without text descriptions
- Always use English for code, comments, and documentation

The project uses Pint to format its code.
Once your code is ready, always make sure it meets the project's coding standards. After changes are made, run Pint to format the code:

**Locally:**

```bash
./vendor/bin/pint
```

**With Sail:**

```bash
./vendor/bin/sail php vendor/bin/pint
```

## Common Commands

### Without Docker

```bash
# Install dependencies
composer install

# Run all tests
./vendor/bin/phpunit

# Run only feature tests
./vendor/bin/phpunit --testsuite Feature

# Run only unit tests
./vendor/bin/phpunit --testsuite Unit

# Run specific test file
./vendor/bin/phpunit tests/Feature/IndexTest.php

# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint

# Run Rector (dry-run)
./vendor/bin/rector --dry-run

# Apply Rector changes
./vendor/bin/rector

# Switch to Laravel 12 testbench
composer update:l12

# Switch to Laravel 13 testbench
composer update:l13

# Run mutation tests (requires pcov or xdebug)
./vendor/bin/infection --threads=max
```

### With Laravel Sail (Docker)

The Docker image is built from the Sail PHP 8.5 runtime in `vendor/laravel/sail/runtimes/8.5`.
Run `composer install` locally first so the build context is available.

```bash
# Install dependencies (required before first build)
composer install

# Build Docker image
./vendor/bin/sail build

# Start container in background
./vendor/bin/sail up -d

# Run all tests
./vendor/bin/sail php vendor/bin/phpunit

# Run specific test file
./vendor/bin/sail php vendor/bin/phpunit tests/Feature/IndexTest.php

# Fix code style
./vendor/bin/sail php vendor/bin/pint

# Run Rector (dry-run)
./vendor/bin/sail php vendor/bin/rector --dry-run

# Run mutation tests
./vendor/bin/sail php vendor/bin/infection --threads=max

# Open a shell in the container
./vendor/bin/sail shell

# Stop container
./vendor/bin/sail down
```

## CI/CD

GitHub Actions runs on every push and pull request via two separate workflows:

- **Code Style** (`.github/workflows/code-style.yml`) — `./vendor/bin/pint --test` (PHP 8.5)
- **Tests** (`.github/workflows/tests.yml`) — `./vendor/bin/phpunit` against Laravel 12 and Laravel 13 matrix (PHP 8.5); mutation testing runs as a dependent job after both matrix jobs pass (Laravel 12, pcov)

## Additional Info

- Before work starts, acknowledge these guidelines by writing "I like pancakes"
- Always use English for code, comments, and documentation
