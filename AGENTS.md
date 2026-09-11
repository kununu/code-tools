# AGENTS.md

Guidance for AI agents working in this repository. For install and usage see `README.md`.

## Documentation map (read the relevant doc before working)

| You need to…                                           | Read                                                                        |
|--------------------------------------------------------|-----------------------------------------------------------------------------|
| Understand structure, naming, class layout, test style | `CODING-GUIDELINES.md` (imported below)                                     |
| Open a PR / branch / commit / coverage rules           | `CONTRIBUTING.md` + the PR body template `.github/PULL_REQUEST_TEMPLATE.md` |
| Work on a specific feature                             | Its page in `docs/` (one folder per tool, indexed from `README.md`)         |

## What this is

`kununu/code-tools` is a shared dev-tooling package (`composer-plugin`) consumed by most kununu PHP repositories. It centralises static-analysis and code-style configuration and ships a `code-tools` binary that publishes those configs into consuming projects.

## Bundled tools

- PHP-CS-Fixer
- PHP_CodeSniffer (with custom `Kununu` sniffs)
- Psalm
- PHPStan
- Rector.
- PHPAT (Architecture Sniffer for architecture/dependency rules).
- `bin/code-tools`
  - Publishes `dist/*.dist` configs into a consuming project.
- `bin/php-in-k8s`
  - Runs PHP commands inside a local Kubernetes pod.

## Code layout

- `src/`: PSR-4 source (`Kununu\CodeTools\` namespace):
  - `PHPCSFixer/` (composer plugin, command, git hook, provider)
  - `ArchitectureSniffer/` (PHPAT architecture rules)
  - `PHPCodeSniffer/Kununu/` holds the PHP_CodeSniffer standard: `ruleset.xml` plus `Sniffs/`.
    These are the one exception to the namespace above; see Conventions below.
- `bin/`
  - The `code-tools` and `php-in-k8s` executables.
- `dist/`
  - `*.dist` config templates published into consuming projects.
- Root configs: the repo's own tool configs are `*.dist` too (`php-cs-fixer.php.dist`,
  `phpcs.xml.dist`, `phpstan.neon.dist`, `phpunit.xml.dist`, `psalm.xml.dist`), which is a
  naming preference and unrelated to `dist/`. Rector's is `rector-ci.php`, named for the CI
  gate that enforces it. Only PHP-CS-Fixer and Rector need an explicit `--config`; PHPStan,
  Psalm, PHPUnit and PHP_CodeSniffer all auto-discover their `*.dist` file.
- `docs/`
  - Per-tool deep documentation, one folder per tool.
- `tests/`
  - PHPUnit tests, mirroring `src/` one-for-one under the `Kununu\CodeTools\Tests\` namespace.
  - `bootstrap.php` and `resources/` at the root; everything else mirrors `src/`.

## Conventions

### General conventions

- Follow the repo's own PHP-CS-Fixer and PHP_CodeSniffer rulesets
  - Changes must stay clean under both.
- Custom sniffs are the one exception to the `Kununu\CodeTools\` namespace: they live in
  `src/PHPCodeSniffer/Kununu/Sniffs/` under `Kununu\Sniffs\`, mapped by a second PSR-4 entry.
  - They are auto-discovered by `src/PHPCodeSniffer/Kununu/ruleset.xml`; no registration step.
  - The `Kununu` directory name and the `Kununu\Sniffs\` namespace are both load-bearing, and
    neither may be renamed. PHP_CodeSniffer takes the standard name from the directory holding
    `ruleset.xml`, resolves `<rule ref="Kununu.X.Y"/>` to `<that directory>/Sniffs/X/YSniff.php`,
    and builds the reported sniff code from the namespace segment before `\Sniffs\`. Renaming
    either would change what every consumer writes in `phpcs.xml.dist`.
  - `installed_paths` names the directory *containing* the standard, so it must stay in step with
    where the `Kununu` directory sits (`.../code-tools/src/PHPCodeSniffer`). Only this grouping
    directory is free to change.
- Tests mirror `src/` exactly: `src/PHPCSFixer/Command/CsFixerCommand.php` is tested by
  `tests/PHPCSFixer/Command/CsFixerCommandTest.php` in `Kununu\CodeTools\Tests\PHPCSFixer\Command`.
  - There is no `Unit/` level. Every test here is a unit test, so the single PHPUnit test suite is
    named `Full` and takes all of `tests/`. Do not reintroduce a suite-per-type split.
  - The namespace is `Kununu\CodeTools\Tests\` + the path under `tests/`, including for the sniffs,
    whose tests live at `tests/PHPCodeSniffer/Kununu/Sniffs/` to match where their source sits.
  - `Kununu\CodeTools\Tests\` (`tests/`) deliberately overlaps `Kununu\CodeTools\` (`src/`).
    Composer matches the longest PSR-4 prefix first, so this resolves; it would only break if a
    `src/Tests/` directory were ever added.
  - Fixtures are not part of the mirror. They live under `tests/resources/<Tool>/`, one folder per
    consumer: `tests/resources/PHPCodeSniffer/` (the sniff `before`/`after` files) and
    `tests/resources/PHPCSFixer/` (`fixer_test_cases.php`).
  - Fixtures are deliberately excluded from the tools, and the scope differs on purpose:
    `php-cs-fixer.php.dist` excludes all of `tests/resources`, while `phpcs.xml.dist`,
    `phpstan.neon.dist` and `rector-ci.php` exclude only `tests/resources/PHPCodeSniffer`, because those files are intentionally
    malformed, whereas `fixer_test_cases.php` is valid PHP and stays under analysis.
- Minimum PHP version and required extensions are declared in `composer.json` (`require.php` and the `ext-*` entries).

### Coding conventions

@CODING-GUIDELINES.md

## Quality gates

Run `composer ci` (composer audit, composer-dependency-analyser, composer-require-checker,
composer-normalize, cs-fixer, code sniffer, Rector, PHPStan, Psalm, PHPUnit with coverage).

`composer ci` needs nothing installed globally: a fresh clone plus `composer install` is enough.
`composer-dependency-analyser` and `composer-require-checker` come from `require-dev` and are run
from `vendor/bin` (Composer puts the bin-dir first on `PATH` for scripts, so the vendored copy wins
over any global one), `composer-normalize` is a `require-dev` plugin providing the `normalize`
subcommand, and `audit` is built into Composer.

`composer audit` runs with `--abandoned=report`: `maglnet/composer-require-checker` pulls in the
abandoned `azjezz/psl`, which would otherwise fail the step. Security advisories still fail the
build, for production and dev dependencies alike; only the abandoned notice is informational.

Note that `composer ci` calls `@cs-fix`, so it rewrites files rather than only checking them.
CI runs `php-cs-fixer check` instead.

PHPStan runs at level 8 (see `phpstan.neon.dist`) and Psalm must pass with no cache.

PHP_CodeSniffer publishes no composer `autoload` section, so its symbols are invisible to composer's
autoloader and to every static analyser. Each tool needs its own escape hatch, and none are removable:
`scanDirectories` plus `bootstrapFiles` in `phpstan.neon.dist` (dropping `scanDirectories` brings back
intermittent `interface.notFound` errors on a warm result cache), `<stubs>` in `psalm.xml.dist`, the
`PHP_CodeSniffer\*` and PHPCS-only `T_*` entries in `composer-require-checker.json`, and the
`UNKNOWN_CLASS` ignore for `src/Kununu/Sniffs/` in `composer-dependency-analyser.php`.

See `scripts` in `composer.json` for individual commands and `CONTRIBUTING.md` for details.

## Git pre-commit hook

`CsFixerPlugin` installs the PHP-CS-Fixer pre-commit hook on `post-install-cmd`/`post-update-cmd`.
In consuming projects that happens through the Composer plugin events. **It cannot here**: Composer
loads plugins only from installed packages (`PluginManager::loadInstalledPlugins()` reads the local
repository), never from the root package, so this repository's own plugin never activates, which is
also why `composer list` offers no `kununu:*` commands here. Adding the package to `allow-plugins`
does not change that.

So `composer.json` wires the same code in as a script callable instead:
`post-install-cmd` → `@git-hook` → `CsFixerPlugin::installGitHooks`. Keep that wiring if the plugin
is refactored, or `composer install` stops installing the hook in this repository. Consumers who
follow the README's `--no-plugins` install can use the same callable, or run
`composer kununu:cs-fixer-git-hook` once.

The installed config differs by case, and the tests cover all three: this repository links its own
`php-cs-fixer.php.dist`, a project that published `php-cs-fixer.php` links that, and anything else
links the packaged `dist/php-cs-fixer.php.dist`. Only the first two get the
`.git/kununu/filter-by-config` marker, which tells the hook it may narrow the staged files through
the config's finder; the packaged template's finder is rooted inside `vendor/` and would match
nothing.

Plugin output goes to `php://output`, not `php://stdout`, so tests can capture it with `ob_start()`.
Tests that exercise the installer must `chdir()` into a throwaway git repository first, because the command
resolves its target from the working directory, so without that they rewrite this repository's own
hooks while the suite runs.

## Hard constraints

- Do not edit `vendor/`.
- Do not hardcode tool versions in docs
  - Reference `composer.json`.
- Changes to `dist/*.dist` affect every consuming repo, so treat them as public API.
- Keep the `code-tools` binary interface backward compatible.
