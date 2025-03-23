# CLAUDE.md - Guidelines for Agentic Coding

## Build & Test Commands
- Install dependencies: `composer install`
- Run all tests: `composer test`
- Run single test: `./vendor/bin/phpunit --filter TestName`
- Lint code: `composer lint`
- Fix linting issues: `composer lint-fix`

## Code Style Guidelines
- Follow PSR-12 coding standards
- Use strict typing: add `declare(strict_types=1);` at the top of files
- Namespace structure: `FileouStore\{Component}`
- Class names: PascalCase
- Method/variable names: camelCase
- Constants: UPPER_SNAKE_CASE
- Use type hints for parameters and return types
- Document classes and methods with PHPDoc
- Use exceptions for error handling, not return codes
- Organize imports alphabetically and by type (PHP core first)
- Max line length: 120 characters
- Indent with 4 spaces, not tabs