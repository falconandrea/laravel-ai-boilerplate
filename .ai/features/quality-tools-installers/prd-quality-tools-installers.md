# Feature: Quality Tools Installers (Pint, Larastan, Pest)

## Overview
Add specialized installers for Laravel Pint, Larastan, and Pest to the boilerplate CLI. These tools are essential for maintaining code quality, static analysis, and testing in modern Laravel projects.

## User Stories
- As a developer, I want to easily add Pint to my project so that my code is automatically formatted.
- As a developer, I want to add Larastan to my project so that I can catch potential bugs via static analysis.
- As a developer, I want to add Pest to my project so that I can write expressive and functional tests.
- As a user of the CLI, I want Pint and Larastan to be selected by default during the installation process as they are highly recommended.

## Acceptance Criteria
- [ ] `PintInstaller` class created and functional.
- [ ] `LarastanInstaller` class created and functional.
- [ ] `PestInstaller` class created and functional.
- [ ] All three tools registered in `InstallCommand::installerMap`.
- [ ] Pint and Larastan are selected by default in the `multiselect` prompt.
- [ ] `PestInstaller` runs `php artisan pest:install` after installing the package.
- [ ] `LarastanInstaller` provides a default `phpstan.neon.dist` file.

## Out of Scope
- Configurable rules for Pint/Larastan within this CLI (will use defaults).
- Migration of existing PHPUnit tests to Pest.

## Technical Notes
- **Pint**: `composer require laravel/pint --dev`.
- **Larastan**: `composer require larastan/larastan --dev`.
- **Pest**: `composer require pestphp/pest --dev --with-all-dependencies` followed by `php artisan pest:install`.
- `InstallCommand::handle()` needs enhancement to support default values for the `multiselect` prompt.

## UI/UX Notes
- New items in the "Which components do you want to install?" list:
    - Laravel Pint (Code style fixer) [Selected by default]
    - Larastan (Static analysis) [Selected by default]
    - Pest (Testing framework)
