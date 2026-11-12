# `architecture-check` usage

This script checks a project against the **company-wide architecture rules**.

The rules are the same for every kununu PHP service and they are not configurable: a project cannot switch one
off, exclude files from it or silence a violation. They are a minimum. A project that wants more can add its own
rules on top, see [Adding stricter rules](#adding-stricter-rules).

It is separate from the [Architecture Sniffer](../ArchitectureSniffer/README.md), which checks the rules a project
defines for itself in `architecture.yaml`. Both can run in the same project.

## Usage

Run it from the directory that holds the project's `src` and `vendor` directories:

```bash
vendor/bin/architecture-check
```

| Option                    | Description                                                                             |
|---------------------------|-----------------------------------------------------------------------------------------|
| `--error-format=<format>` | Any [PHPStan error format](https://phpstan.org/user-guide/output-format), e.g. `github` |
| `--memory-limit=<limit>`  | Passed to PHPStan, e.g. `1G`                                                            |

There are no other options. In particular there is no way to pass a configuration file, paths or excludes.

| Exit code | Meaning                                                                                       |
|-----------|-----------------------------------------------------------------------------------------------|
| `0`       | No violations                                                                                 |
| `1`       | At least one violation, or PHPStan stopped with an error of its own (it prints which)         |
| `2`       | The check did not start: unsupported option, or a project the rules cannot check (see below)  |

Any other exit code means the run was cancelled or PHP itself failed. Either way the check has not passed.

Which rules are in use is printed first, on the error output:

```
Checking /var/www/html/services/src against /var/www/html/services/vendor/kununu/code-tools/architecture/cqrs.neon
```

## How it works

The rules are [PHPAT](https://www.phpat.dev) tests, run by PHPStan with a configuration that ships inside this
package (`architecture/`). Only the architecture rules run, and the check does not need the project to have a
PHPStan configuration of its own. The one regular PHPStan error that can show up is a file it cannot parse.

A violation looks like this with `--error-format=raw`:

```
src/Domain/Company.php:12:testDomainDoesNotDependOnMessaging: App\Domain\Company should not depend on App\Messaging\Producer
```

The part before the first colon of the message is the rule name. The rule classes in `src/Architecture/Rules/`
state the reason for each rule.

### Which rules apply

Every project is checked against the common rules. The layout of `src` decides which additional set applies:

| `src` contains | Layout          | Rule sets                    |
|----------------|-----------------|------------------------------|
| `UseCase/`     | CQRS            | `CommonRules` + `CqrsRules`  |
| `Application/` | Hexagonal       | `CommonRules`                |
| `Service/`     | Service-layered | `CommonRules`                |
| none of these  | Unknown         | `CommonRules`                |

The first match wins, from top to bottom.

### Rules cannot be ignored

- Every rule is marked as non-ignorable: an inline `@phpstan-ignore`, an `ignoreErrors` entry or a baseline
  does not silence it.
- The project's PHPStan configuration is not read.
- Extensions registered through `phpstan/extension-installer` are not loaded, so one cannot bring parameters
  such as `excludePaths` into the check.

The only way to make the check pass is to fix the code.

### What the rules can and cannot see

- Only `src` is analysed, and the rules select classes by namespace. The check therefore refuses to start
  unless `composer.json` maps the PSR-4 namespace `App\` to `src` and to nothing else.
- The rules expect the layers directly under `App\` (`App\Controller`, `App\Domain`, `App\UseCase` and so
  on). Code that sits somewhere else, for example `App\Company\Controller`, is not matched by a layer rule.
- `testControllersAreFinal` applies to classes in `App\Controller` whose name ends in `Controller`.
- A dependency is only reported when PHPStan can find the class that is depended on. Run the check with the
  project's dependencies installed.
- Each run works in a temporary directory of its own and removes it when it finishes, so no result is reused
  between runs. A run that is killed leaves that directory behind in the system's temporary directory.

## Adding stricter rules

Write your own PHPAT test classes and register them in your project's PHPStan configuration, as described in the
[PHPAT documentation](https://www.phpat.dev). They run with your regular PHPStan analysis. The company-wide
rules keep running through `architecture-check` regardless of what your configuration contains.

---

[Back to Index](../../README.md)
