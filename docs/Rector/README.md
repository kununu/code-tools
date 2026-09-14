# `Rector` usage

## Table of Contents
- [Out of the box usage](#out-of-the-box-usage)
- [Customized usage](#customized-usage)
  - [Example of a customized `rector.php` to upgrade PHP syntax and PHPUnit usage](#example-of-a-customized-rectorphp-to-upgrade-php-syntax-and-phpunit-usage)
  - [Example of a customized `rector.php` to fix phpunit deprecation warnings](#example-of-a-customized-rectorphp-to-fix-phpunit-deprecation-warnings)
  - [Example of a customized `rector.php` to maintain the code quality and enforce it via a CI pipeline](#example-of-a-customized-rectorphp-to-maintain-the-code-quality-and-enforce-it-via-a-ci-pipeline)

## Out of the box usage
- It will check the code in `tests` directory and suggest or apply the necessary refactor to make it compatible with the PHPUnit version installed in your project.
- Rector only accepts a config file whose extension is `.php`, so `--config` cannot be pointed at
  `dist/rector.php.dist` inside `vendor/`. Publish the template into your project first:

```console
vendor/bin/code-tools publish:config rector
```

- The `--config` flag then refers to the published `rector.php`. Do not point it at
  `vendor/kununu/code-tools/rector-ci.php`: that is this package's own CI config, whose skip list is
  resolved relative to *this* repository rather than to your project.

### Preview suggested changes
```console
vendor/bin/rector process --dry-run --config rector.php tests
```

### Apply suggested changes
```console
vendor/bin/rector process --config rector.php tests
```

<details>
  <summary>See it in action</summary>

- **git diff**
- ![kununu/code-tools](/docs/Rector/screenshots/diff-rector.png)
</details>

## Customized usage
- You can customize the `rector.php` file to include/exclude directories, files, or rules.
- You can create your own configuration file and use it with the `--config` flag.
- The `rector.php` published above is yours to edit. If you do not have it yet, publish it with:

```console
vendor/bin/code-tools publish:config rector
```

- The `rector.php` file will be copied to your project, and you can modify it to suit your needs.

<details>
  <summary>See some customization examples</summary>

### Example of a customized `rector.php` to upgrade PHP syntax and PHPUnit usage:
```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\PHPUnit\PHPUnit100\Rector\StmtsAwareInterface\WithConsecutiveRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // Picks the PHP level up from your composer.json `require.php` constraint
    ->withPhpSets()
    // Picks the PHPUnit rules up from your installed phpunit/phpunit version
    ->withComposerBased(phpunit: true)
    ->withSkip([
        WithConsecutiveRector::class,
        ClosureToArrowFunctionRector::class,
    ]);
```

### Example of a customized `rector.php` to fix phpunit deprecation warnings:
```php
<?php
declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\Config\RectorConfig;
use Rector\Php70\Rector\Ternary\TernaryToNullCoalescingRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\PHPUnit\PHPUnit100\Rector\StmtsAwareInterface\WithConsecutiveRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;

return RectorConfig::configure()
    ->withPhpSets()
    ->withComposerBased(phpunit: true)
    ->withPreparedSets(
        phpunitCodeQuality: true,
        phpunitNarrowAsserts: true,
        phpunitMockToStub: true
    )
    ->withRules([
        CompleteDynamicPropertiesRector::class,
    ])
    ->withSkip([
        WithConsecutiveRector::class,
        ReturnNeverTypeRector::class,
        TernaryToNullCoalescingRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        __DIR__ . '/tests/bootstrap.php',
    ])
    ->withImportNames();
```

### Example of a customized `rector.php` to maintain the code quality and enforce it via a CI pipeline:
```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets()
    ->withComposerBased(phpunit: true, symfony: true)
    ->withSymfonyContainerPhp(__DIR__ . '/var/cache/dev/App_KernelDevContainer.php')
    ->withTypeCoverageLevel(0)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        symfonyCodeQuality: true
    )
    ->withSkip([
        __DIR__ . '/src/Migrations',
    ]);
```
```yaml
name: Rector Check

on:
  pull_request:

jobs:
  rector-check:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: none

      - name: Install composer dependencies
        uses: php-actions/composer@v6
        with:
          php_version: '8.3'
          version: 2
          args: --working-dir=./services
          ssh_key: ${{ secrets.CLONE_SSH_KEY }}
          ssh_key_pub: ${{ secrets.CLONE_SSH_KEY_PUB }}

      - name: Set up Directory Permissions
        run: |
          mkdir -p services/var/cache
          mkdir -p services/var/log
          sudo chmod -R 777 services/var/cache
          sudo chown -R $USER services/var/cache
          sudo chmod -R 777 services/var/log
          sudo chown -R $USER services/var/log

      - name: Check Code
        run: |
          cd services
          php bin/console cache:clear -q
          vendor/bin/rector process --dry-run
```

</details>

### Preview suggested changes
```console
vendor/bin/rector process --dry-run --config rector.php
```

### Apply suggested changes
```console
vendor/bin/rector process --config rector.php
```

## Notes
- There are many rules available, you can use them to upgrade your codebase to the latest PHP version, framework version (e.g. symfony), or package version (e.g. phpunit).
- Rector is a powerful tool but some manual intervention may be required to make the code work as expected.
- Learn more about Rector at official page [here](https://getrector.com/documentation).

---

[Back to Index](../../README.md)
