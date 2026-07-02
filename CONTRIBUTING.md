# Contributing

How to develop, test, and ship changes to `kununu/code-tools`.

## Development setup

Requires PHP as pinned in `composer.json` (`require.php`) and Composer.

```console
composer install
```

## Testing

Run the unit test suite:

```console
composer test-unit
```

## Quality checks

Run the full check suite before opening a PR:

```console
composer ci-checks
```

This runs PHP-CS-Fixer, PHP_CodeSniffer, PHPStan, Rector, Psalm, and PHPUnit. Auto-fixers are available for style and refactors:

```console
composer cs-fixer-fix
composer cs-fix
composer rector-fix
```

See the `scripts` section of `composer.json` for the complete list of commands.

## Pull requests

- Branch from `main`.
- Fill in `.github/PULL_REQUEST_TEMPLATE.md`, including the linked JIRA issue.
- Ensure CI is green; the Continuous Integration workflow runs the checks above plus composer dependency and normalization checks, and reports coverage to SonarCloud.
- `@kununu/backend-libraries` is the code owner and reviews all changes.

## Releasing

The package is distributed via Composer and versioned with Git tags following SemVer. Because most kununu PHP repos depend on this package, and `dist/*.dist` templates and the `code-tools` binary form its public API, treat any change to them as breaking unless proven otherwise.
