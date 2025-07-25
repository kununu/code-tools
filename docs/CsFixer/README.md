# PHP CS Fixer

## Commands
### `kununu:cs-fixer`
- Runs PHP CS Fixer on the specified directories or files using the default config.
  - Example:
    - `composer kununu:cs-fixer src/ tests/`
- Runs PHP CS Fixer with a **custom config** file.
  - Example:
    - `composer kununu:cs-fixer --config=/var/www/html/services/vendor/kununu/code-tools/php-cs-fixer.php src/ tests/`

### `kununu:cs-fixer-git-hook`
- Installs the Kununu pre-commit Git hook for coding standards enforcement
  - Run:
    - `composer kununu:cs-fixer-git-hook`

## Pre-commit hook
Since this project is a _composer-plugin_, the composer `kununu:cs-fixer-git-hook` command is automatically applied during install or update.

## Rules and Configuration
- PHP CS Fixer configuration rules can be found [here](https://cs.symfony.com/doc/rules/index.html).
- Kununu coding standards rules are located [here](../../php-cs-fixer.php).
