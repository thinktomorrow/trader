# AGENTS Guide for `thinktomorrow/trader`

This file is for coding agents working in this repository.
It captures the actual tooling, architecture, and coding conventions used here.

## Project Snapshot

- Language: PHP (`>=8.4` from `composer.json`).
- Type: domain-heavy Laravel package (not a full standalone app).
- Main namespaces: `Thinktomorrow\Trader\Application`, `...\Domain`, `...\Infrastructure`.
- Tests are split into `Unit`, `Acceptance`, and `Infrastructure` suites.
- Autoload: PSR-4 from `src/`; test namespace `Tests\` from `tests/`.

## Environment and Setup

- Install dependencies: `composer install`.
- If dependencies changed: `composer update` (prefer targeted updates).
- PHPUnit bootstrap is `vendor/autoload.php` via `phpunit.xml.dist`.
- Many tests rely on test helpers from `thinktomorrow/trader-testing`.
- DB defaults in `phpunit.xml.dist` are MySQL (`DB_CONNECTION=mysql`).

## Build / Lint / Test Commands

There is no dedicated build artifact step in this package.
Use quality gates below before committing.

### Core Commands

- Run default tests (composer script): `composer test`
- Run all tests directly: `vendor/bin/phpunit`
- Static analysis (repo config): `vendor/bin/phpstan analyse src`
- Static analysis (CI style): `vendor/bin/phpstan analyse src --level=2`
- Architecture layering checks: `vendor/bin/deptrac analyse`
- Mutation tests (configured for `src/Domain`): `vendor/bin/infection`

### Formatting / Style

`php-cs-fixer` is configured in `.php-cs-fixer.dist.php`, but not installed as a local vendor binary here.
Use one of:

- If globally available: `php-cs-fixer fix --config=.php-cs-fixer.dist.php --allow-risky=yes`
- CI-equivalent containerized run:
  `docker run --rm -v "$PWD":/app -w /app oskarstark/php-cs-fixer-ga --config=.php-cs-fixer.dist.php --allow-risky=yes`

### Test Suite Commands

- Unit suite: `vendor/bin/phpunit tests/Unit`
- Acceptance suite: `vendor/bin/phpunit tests/Acceptance`
- Infrastructure suite: `vendor/bin/phpunit tests/Infrastructure`

### Running a Single Test (Important)

- Single file:
  `vendor/bin/phpunit tests/Unit/Model/Product/ProductTest.php`
- Single test method by filter:
  `vendor/bin/phpunit tests/Unit/Model/Product/ProductTest.php --filter test_it_can_create_a_product`
- Filter across suite:
  `vendor/bin/phpunit tests/Acceptance --filter ChoosePaymentMethodTest`

### DB-Specific Test Runs

Some suites are run with different DB settings in CI.

- Unit/Acceptance with non-MySQL test connection:
  `DB_CONNECTION=testing vendor/bin/phpunit tests/Unit`
  `DB_CONNECTION=testing vendor/bin/phpunit tests/Acceptance`
- Infrastructure with MySQL:
  `DB_CONNECTION=mysql DB_USERNAME=root DB_PASSWORD= DB_DATABASE=trader_test DB_HOST=127.0.0.1 DB_PORT=3306 vendor/bin/phpunit tests/Infrastructure`

## CI Workflows (What to Mirror Locally)

- `.github/workflows/test.yml`:
  - Matrix on PHP 8.4/8.5 and Laravel 11/12.
  - Runs Unit, Acceptance, Infrastructure suites.
- `.github/workflows/static.yml`:
  - Runs PHPStan at level 2.
- `.github/workflows/codestyle.yml`:
  - Runs PHP CS Fixer and commits style fixes.

When changing behavior, run at least targeted tests + phpstan.
Before finalizing substantial changes, run full PHPUnit suites.

## Architecture and Dependency Rules

Deptrac enforces these layers:

- `Infrastructure` may depend on `Application` and `Domain`.
- `Application` may depend on `Domain`.
- `Domain` should stay independent (no upward dependencies).

Keep new files in the correct layer and avoid leaking framework concerns into `Domain`.

## Code Style Guidelines

These are inferred from existing code and fixer rules; follow them unless a file clearly diverges.

### PHP File Structure

- Start files with `<?php` and `declare(strict_types=1);`.
- Use one class/enum/interface per file.
- Follow PSR-4 paths and namespace alignment.
- Prefer short arrays `[]`.

### Imports

- Import classes with `use`; avoid fully qualified names inline unless rare.
- Keep imports alphabetically ordered.
- Remove unused imports.

### Formatting

- 4-space indentation, LF line endings, final newline (`.editorconfig`).
- Follow PSR-2 baseline plus project fixer rules.
- Include trailing commas in multiline arrays/argument lists.
- Keep binary/unary operator spacing consistent.
- Insert blank line before `return`, `throw`, `break`, `continue`, `declare`, `try`.
- Test method names are snake_case (`php_unit_method_casing`).

### Types and Data Modeling

- Use strict typing everywhere.
- Type properties, parameters, and return values wherever practical.
- Prefer domain value objects and enums over primitive strings/ints.
- Use `readonly` for identity/value properties when appropriate.
- Use nullable types explicitly (`?Type`) instead of implicit null handling.

### Naming Conventions

- Classes: `PascalCase` and descriptive (`CreateProduct`, `OrderStateMachine`).
- Methods/properties: `camelCase`.
- Test methods: descriptive snake_case starting with `test_...`.
- Exceptions: expressive `CouldNot*`, `Invalid*`, or domain-specific names.
- IDs/value objects usually expose `fromString()` and `get()` patterns.

### Application Layer Patterns

- Command-like DTO classes in `Application/*` hold input data and helpers.
- Application services orchestrate repositories, domain objects, and events.
- Persist via repositories, then dispatch recorded domain events.

### Domain Layer Patterns

- Prefer immutable-ish aggregates with controlled mutation methods.
- Use named constructors like `create()` / `fromMappedData()`.
- Guard invariants early; throw meaningful exceptions.
- Record domain events with `RecordsEvents` and release after persistence.

### Error Handling

- Throw specific domain exceptions for business failures.
- Use `\InvalidArgumentException` for invalid inputs/contracts.
- Use `\LogicException` or domain logic exceptions for impossible states.
- Keep exception messages explicit and actionable.

### Testing Conventions

- Unit tests focus on domain behavior and invariants.
- Acceptance tests validate application flows with in-memory contexts.
- Infrastructure tests cover Laravel repositories, models, auth integrations.
- Assert emitted events and exception behavior where relevant.

## Agent Operating Notes

- Prefer minimal, targeted changes over broad refactors.
- Do not weaken layer boundaries to “quick-fix” dependencies.
- If changing persistence mapping, check both MySQL and in-memory repositories.
- If changing state machines/events, update tests that assert transitions/events.

## Cursor / Copilot Rules Check

Checked repository for agent-editor instruction files:

- `.cursorrules`: not found.
- `.cursor/rules/`: not found.
- `.github/copilot-instructions.md`: not found.

If any of these files are added later, treat them as higher-priority supplements to this guide.
