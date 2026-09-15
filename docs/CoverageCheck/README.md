# `coverage-check` usage

This script checks the **total line coverage** of a test run against a minimum threshold.

It reads a [Cobertura](https://cobertura.github.io/cobertura/) XML report, takes the `lines-covered` and `lines-valid` attributes of its root `<coverage>`
element, and prints the resulting percentage next to the minimum you asked for.

## Usage

```bash
vendor/bin/coverage-check <cobertura-report-file> <minimum-percentage>
```

| Argument                  | Required | Description                                                      |
|---------------------------|----------|------------------------------------------------------------------|
| `<cobertura-report-file>` | yes      | Path to a Cobertura XML report produced by a previous test run   |
| `<minimum-percentage>`    | yes      | The threshold to compare against, a number between `0` and `100` |

Both arguments are mandatory and there are no options or flags.

### Generating the report

PHPUnit writes Cobertura reports with `--coverage-cobertura`, and coverage collection requires a coverage driver
(Xdebug with `XDEBUG_MODE=coverage`, or PCOV):

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-cobertura tests/.results/tests-cobertura.xml
```

The report only has to exist before the check runs, so the two steps are usually separate:
- Run the suite once with coverage
- Then check the result

### Wiring it into `composer.json`

This is how the package itself uses it. The test run writes the Cobertura report, and a dedicated script checks it:

```json
{
  "scripts": {
    "coverage-check": "vendor/bin/coverage-check tests/.results/tests-cobertura.xml 95",
    "test-coverage": [
      "@putenv XDEBUG_MODE=coverage",
      "@php vendor/bin/phpunit --coverage-cobertura tests/.results/tests-cobertura.xml"
    ],
    "ci": [
      "@test-coverage",
      "@coverage-check"
    ]
  },
  "scripts-descriptions": {
    "coverage-check": "Check that the line coverage of the last coverage run is at least 95%"
  }
}
```

```bash
composer coverage-check
```

See the `scripts` section of this repository's own `composer.json` for the complete version.

## Output

A single line, green when the coverage is at or above the minimum:

```console
Line coverage: 97.18% (792/815 lines) | Minimum: 95.00%
```

and red when it is below it:

```console
Line coverage: 91.04% (742/815 lines) | Minimum: 95.00%
```

The exit status follows the result, `0` when the coverage is at or above the minimum and `1` when it is below it, which
is what makes the script usable as a gate in `composer ci` or in a CI job.

Percentages are rounded to two decimals, and the comparison is inclusive: a coverage exactly equal to the minimum passes,
including one that only reaches it after rounding.

### Colors

Colors follow the [`NO_COLOR`](https://no-color.org/) convention. Set the variable to any value, including an empty one,
to get plain text:

```bash
NO_COLOR=1 vendor/bin/coverage-check tests/.results/tests-cobertura.xml 95
```

## Errors

Argument and report errors are written to `STDERR` and also exit with status `1`, but without printing a coverage line:

| Message                                                              | Cause                                                                                                                                       |
|----------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------|
| `Usage: coverage-check <cobertura-report-file> <minimum-percentage>` | One of the two arguments is missing                                                                                                         |
| `The minimum percentage must be a number, got "…".`                  | The second argument is not numeric                                                                                                          |
| `The minimum percentage must be between 0 and 100, got ….`           | The second argument is out of range                                                                                                         |
| `Coverage report "…" not found!`                                     | The path does not point to an existing file                                                                                                 |
| `Could not read the coverage report "…".`                            | The file exists but is not parseable XML                                                                                                    |
| `No line coverage found in report "…".`                              | The root element carries no `lines-valid`, which is also what you get when the file is valid XML in another format, such as a Clover report |

---

[Back to Index](../../README.md)
