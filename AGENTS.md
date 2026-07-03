# AGENTS.md

Guidance for AI agents working in this repository. For install and usage see `README.md`; for dev workflow see `CONTRIBUTING.md`.

## What this is

`kununu/code-tools` is a shared dev-tooling package (`composer-plugin`) consumed by most kununu PHP repositories. It centralises static-analysis and code-style configuration and ships a `code-tools` binary that publishes those configs into consuming projects.

## Bundled tools

- PHP-CS-Fixer, PHP_CodeSniffer (with custom `Kununu` sniffs), Psalm, PHPStan, Rector.
- Architecture Sniffer, built on PHPAT, for architecture/dependency rules.
- `bin/code-tools`: publishes `dist/*.dist` configs into a consuming project.
- `bin/php-in-k8s`: runs PHP commands inside a local Kubernetes pod.

## Code layout

- `Kununu/` — PSR-4 source (`Kununu\` namespace): `CsFixer/` (composer plugin, command, git hook, provider), `Sniffs/` (custom PHP_CodeSniffer sniffs), `ArchitectureSniffer/`.
- `bin/` — the `code-tools` and `php-in-k8s` executables.
- `dist/` — `*.dist` config templates published into consuming projects.
- `docs/` — per-tool deep documentation, one folder per tool.
- `tests/` — PHPUnit tests under `tests/Unit/`, mirroring the `Kununu/` tree.

## Conventions

- `declare(strict_types=1);` in every PHP file.
- Follow the repo's own PHP-CS-Fixer and PHP_CodeSniffer rulesets; changes must stay clean under both.
- Custom sniffs live under `Kununu\Sniffs`; register them in `Kununu/Sniffs/ruleset.xml`.
- Tests mirror the source namespace under `tests/Unit/`.
- PHP version is pinned in `composer.json` (`require.php`).

## Quality gates

Run `composer ci-checks` (cs-fixer, code sniffer, PHPStan, Rector, Psalm, PHPUnit). PHPStan runs at max level and Psalm must pass with no cache. See `scripts` in `composer.json` for individual commands and `CONTRIBUTING.md` for details.

## Hard constraints

- Do not edit `vendor/`.
- Do not hardcode tool versions in docs; reference `composer.json`.
- Changes to `dist/*.dist` affect every consuming repo — treat them as public API.
- Keep the `code-tools` binary interface backward compatible.
