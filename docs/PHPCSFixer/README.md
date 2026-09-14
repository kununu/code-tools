# PHP CS Fixer

## Commands
### `kununu:cs-fixer`
- Runs PHP CS Fixer on the specified directories or files using the default config.
  - Example:
    - `composer kununu:cs-fixer src/ tests/`
- Runs PHP CS Fixer with a **custom config** file.
  - Example:
    - `composer kununu:cs-fixer --config=php-cs-fixer.php src/ tests/`
  - The custom config is your own, published into the project with
    `vendor/bin/code-tools publish:config cs-fixer` and then edited.
  - Do not point `--config` into `vendor/kununu/code-tools/`. Both the package's own
    `php-cs-fixer.php` and the `dist/php-cs-fixer.php.dist` template resolve the directory they scan
    relative to their own location, so without explicit paths the first one formats this package
    inside your `vendor/` and the second matches nothing at all. Passing `src/ tests/` hides the
    difference, because paths given on the command line override the ones from the config.

### `kununu:cs-fixer-git-hook`
- Installs the Kununu pre-commit Git hook for coding standards enforcement
  - Run:
    - `composer kununu:cs-fixer-git-hook`

## Pre-commit hook
Since this project is a _composer-plugin_, the composer `kununu:cs-fixer-git-hook` command is automatically applied during install or update.

## Rules and Configuration
- PHP CS Fixer configuration rules can be found [here](https://cs.symfony.com/doc/rules/index.html).
- Kununu coding standards rules are located [here](../../dist/php-cs-fixer.php.dist).

---

[Back to Index](../../README.md)
