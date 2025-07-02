# `PHP-CS-Fixer` usage

## Table of Contents
- [Out of the box usage](#out-of-the-box-usage)


## Out of the box usage
- It will check the PHP Coding Standards issues for the `src` and `tests` directories.
- The `--config=vendor/kununu/code-tools/php-cs-fixer.php` flag is used to specify the config to be used.

### Analyze and detect violations
```console
vendor/bin/php-cs-fixer check --config=vendor/kununu/code-tools/php-cs-fixer.php src/ tests/
```

### Automatically fix violations
```console
vendor/bin/php-cs-fixer fix --config=vendor/kununu/code-tools/php-cs-fixer.php src/ tests/
```

### Add the pre-commit hook to your project
```console
vendor/kununu/code-tools/bin/code-tools publish:config cs-fixer-pre-commit
```

#### Optionally you can add it to your project's composer.json
```json
{
    "scripts": {
        "cs-fixer-check": "php-cs-fixer check --config=vendor/kununu/code-tools/php-cs-fixer.php src/ tests/",
        "cs-fixer-fix": "php-cs-fixer fix --config=vendor/kununu/code-tools/php-cs-fixer.php src/ tests/"
    }
}
```
