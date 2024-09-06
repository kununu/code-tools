# `Rector` usage

## Table of Contents
- [Out of the box usage](#out-of-the-box-usage)
- [Customized usage](#customized-usage)
  - [Example of a customized `rector.php` to upgrade to PHP 8.3 and phpunit 10](#example-of-a-customized-rectorphp-to-upgrade-to-php-83-and-phpunit-10)
  - [Example of a customized `rector.php` to fix phpunit deprecation warnings](#example-of-a-customized-rectorphp-to-fix-phpunit-deprecation-warnings)
  - [Example of a customized `rector.php` to maintain the code quality and enforce it via a CI pipeline](#example-of-a-customized-rectorphp-to-maintain-the-code-quality-and-enforce-it-via-a-ci-pipeline)

## Out of the box usage
- It will check the code in `tests` directory and suggest or apply the necessary refactor to make it compatible with phpunit v10.
- The `--config` flag is used to specify the configuration to be used.

### Preview suggested changes
```console
vendor/bin/rector process --dry-run --config vendor/kununu/code-tools/rector.php tests
```

### Apply suggested changes
```console
vendor/bin/rector process --config vendor/kununu/code-tools/rector.php tests
```

<details>
  <summary>See it in action</summary>

- **git diff**
- ![kununu/code-tools](/docs/Rector/screenshots/diff-rector.png)
</details>

## Customized usage
- You can customize the `rector.php` file to include/exclude directories, files, or rules.
- You can create your own configuration file and use it with the `--config` flag.
- The easiest way to customize the rules is to copy the `rector.php` file to your project and modify it, for this we provide the following command:

```console
vendor/bin/code-tools publish:config rector
```

- The `rector.php` file will be copied to your project, and you can modify it to suit your needs.

<details>
  <summary>See some customization examples</summary>

### Example of a customized `rector.php` to upgrade to PHP 8.3 and phpunit 10:
```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php71\Rector\ClassConst\PublicConstantVisibilityRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\PHPUnit\Rector\StmtsAwareInterface\WithConsecutiveRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\ClassMethod\DataProviderAnnotationToAttributeRector;
use Rector\PHPUnit\PHPUnit100\Rector\Class_\StaticDataProviderClassMethodRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
->withPaths([
  __DIR__ . '/src',
  __DIR__ . '/tests',
])
->withRules([
    AddTypeToConstRector::class,
    ReadOnlyClassRector::class,
    DataProviderAnnotationToAttributeRector::class,
    StaticDataProviderClassMethodRector::class,
])
->withSets([
    LevelSetList::UP_TO_PHP_83
])
->withSkip([
  WithConsecutiveRector::class,
  ClosureToArrowFunctionRector::class,
  PublicConstantVisibilityRector::class,
  AddOverrideAttributeToOverriddenMethodsRector::class,
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
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\ClassMethod\DataProviderAnnotationToAttributeRector;
use Rector\PHPUnit\PHPUnit110\Rector\Class_\NamedArgumentForDataProviderRector;
use Rector\PHPUnit\Rector\StmtsAwareInterface\WithConsecutiveRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;

return RectorConfig::configure()
    ->withSets([
        PHPUnitSetList::PHPUNIT_110,
        LevelSetList::UP_TO_PHP_83,
        SetList::PHP_83,
        SymfonySetList::SYMFONY_64,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ])
    ->withRules([
        DataProviderAnnotationToAttributeRector::class,
        NamedArgumentForDataProviderRector::class,
        CompleteDynamicPropertiesRector::class,
    ])
    ->withSkip([
        WithConsecutiveRector::class,
        AddOverrideAttributeToOverriddenMethodsRector::class,
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
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSets([
        SetList::PHP_83,
        SymfonySetList::SYMFONY_64,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        PHPUnitSetList::PHPUNIT_100,
    ])
    ->withSymfonyContainerPhp(__DIR__ . '/var/cache/dev/App_KernelDevContainer.php')
    ->withTypeCoverageLevel(0)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true
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