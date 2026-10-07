<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Artisan & Tinker

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`).
- Inspect routes with `php artisan route:list`.
- Run tests with `php artisan test`.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion.
- Use explicit return type declarations and type hints for all method parameters.
- Prefer PHPDoc blocks over inline comments.

=== pint/core rules ===

# Laravel Pint Code Formatter

- Run `vendor/bin/pint` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- Run tests with `php artisan test`.
</laravel-boost-guidelines>
