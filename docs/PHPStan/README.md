# `PHPStan` usage

## Table of Contents
- [Out of the box usage](#out-of-the-box-usage)
- [Customized usage](#customized-usage)
  - [Raising the level and excluding paths](#raising-the-level-and-excluding-paths)
  - [Ignoring specific errors](#ignoring-specific-errors)
  - [Using a baseline](#using-a-baseline)
- [Configuration options](#configuration-options)
- [Baseline management](#baseline-management)
- [Further resources](#further-resources)

## Out of the box usage
- The default configuration analyzes the `src` directory at level 1, a lenient starting point meant to be raised per project.
- A commented-out `tests` entry sits below it. Every `paths` entry must exist or PHPStan aborts, and `paths` has no optional marker, so uncomment it once the directory is there.
- The PHPUnit extension and its extra rules are registered, so assertions narrow types and redundant ones are reported.
- The `--configuration` (`-c`) flag is used to specify the configuration to be used.

### Run PHPStan analysis
```console
vendor/bin/phpstan analyse --configuration=vendor/kununu/code-tools/dist/phpstan.neon.dist
```

### Run PHPStan without the result cache
```console
vendor/bin/phpstan analyse --configuration=vendor/kununu/code-tools/dist/phpstan.neon.dist --no-progress
vendor/bin/phpstan clear-result-cache
```

## Customized usage
- You can customize the `phpstan.neon` file to change the level, include/exclude paths, ignore errors, or use a baseline.
- The easiest way to customize the configuration is to copy the `phpstan.neon.dist` file to your project and modify it, for this we provide the following command:

```console
vendor/bin/code-tools publish:config phpstan
```

- The `phpstan.neon` file will be copied to your project root, and you can modify it to suit your needs.

<details>
  <summary>See some customization examples</summary>

### Raising the level and excluding paths
Raise `level` as the codebase gets cleaner, and skip generated code that never will be:
```neon
parameters:
    level: 8

    paths:
        - src/

    excludePaths:
        - src/Migrations/ (?)
        - src/Generated/ (?)
```

A plain `excludePaths` entry must exist on disk or PHPStan fails with *"is neither a directory, nor a file path, nor a fnmatch pattern"*. Append `(?)` to mark it optional, or use an fnmatch pattern such as `*/Migrations/*`.

### Ignoring specific errors
Prefer `identifier` over message regexes, as identifiers are stable across releases:
```neon
parameters:
    level: 8

    paths:
        - src/

    ignoreErrors:
        - identifier: missingType.iterableValue
        -
            identifier: offsetAccess.nonOffsetAccessible
            path: src/Legacy/*
```

An `ignoreErrors` entry that matches nothing is itself reported as an error (`reportUnmatchedIgnoredErrors` defaults to `true`). Add `reportUnmatched: false` to a single entry to opt it out.

### Using a baseline
Baselines allow you to suppress existing errors while enforcing the level on new code:
```neon
includes:
    - phpstan-baseline.neon

parameters:
    level: 8

    paths:
        - src/
```

</details>

### Run PHPStan with custom config
```console
vendor/bin/phpstan analyse --configuration=phpstan.neon
```

## Configuration options

| Option                      | Default | Description                                                        |
|-----------------------------|---------|--------------------------------------------------------------------|
| `level`                     | `1`     | Strictness level (0=most lenient, 10/`max`=strictest)              |
| `paths`                     | `src`   | Paths to analyze (`tests` ships commented out)                     |
| `excludePaths`              | `—`     | Paths skipped during analysis                                      |
| `ignoreErrors`              | `—`     | Errors to suppress, ideally matched by `identifier`                |
| `treatPhpDocTypesAsCertain` | `true`  | Whether PHPDoc types are trusted as certain                        |

## Baseline management

Baselines allow you to suppress existing errors while enforcing the level on new code.

### Generate a baseline
```console
vendor/bin/phpstan analyse --configuration=phpstan.neon --generate-baseline
```

Then include the generated file:
```neon
includes:
    - phpstan-baseline.neon
```

### Update the baseline
After fixing errors, regenerate to remove resolved entries:
```console
vendor/bin/phpstan analyse --configuration=phpstan.neon --generate-baseline
```

### Report unmatched baseline entries
Fail when the baseline lists errors that no longer occur:
```neon
parameters:
    reportUnmatchedIgnoredErrors: true
```

## Further resources

- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHPStan Config Reference](https://phpstan.org/config-reference)
- [PHPStan Rule Levels](https://phpstan.org/user-guide/rule-levels)
- [PHPStan Baseline](https://phpstan.org/user-guide/baseline)
- [PHPStan Error Identifiers](https://phpstan.org/error-identifiers)

---

[Back to Index](../../README.md)
