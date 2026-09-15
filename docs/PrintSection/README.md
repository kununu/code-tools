# `print-section` usage

This script prints a centered section header, a label surrounded by horizontal rules and wrapped in blank lines.

It is a generic output helper with no knowledge of any tool. Its purpose is to make long console output readable, 
most notably to separate the steps of a `composer ci` script so you can tell at a glance which tool produced which output.

## Usage

```bash
vendor/bin/print-section <label> [width]
```

| Argument  | Required | Default | Description                                                  |
|-----------|----------|---------|--------------------------------------------------------------|
| `<label>` | yes      |         | The text to center. Quote it if it contains spaces           |
| `[width]` | no       | `80`    | Total width of the header in characters, a positive integer  |

The label is trimmed, and its width is measured in UTF-8 characters, so accents and other multibyte characters do not 
shift the centering.

If the label does not fit in the requested width the rules are simply omitted and the label is printed on its own.

```bash
vendor/bin/print-section 'PHPStan'
```

```console

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ PHPStan ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

```

```bash
vendor/bin/print-section 'Short' 30
```

```console

━━━━━━━━━━━ Short ━━━━━━━━━━━━

```

### Wiring it into `composer.json`

Interleave a call before each step of a multistep script:

```json
{
  "scripts": {
    "ci": [
      "vendor/bin/print-section 'PHP CS Fixer'",
      "@cs-fix",
      "vendor/bin/print-section 'PHPStan'",
      "@phpstan",
      "vendor/bin/print-section 'Psalm'",
      "@psalm",
      "vendor/bin/print-section 'Tests & Coverage'",
      "@test-coverage"
    ]
  }
}
```

See the `ci` script in this repository's own `composer.json` for the complete version.

Nothing ties the script to Composer, so it works just as well in a shell script or a CI job:

```bash
vendor/bin/print-section 'Integration tests'
```

### Colors

The header is printed in bold cyan. Colors follow the [`NO_COLOR`](https://no-color.org/) convention, so setting the variable to any value,
including an empty one, gives you plain text:

```bash
NO_COLOR=1 vendor/bin/print-section 'PHPStan'
```

This is worth doing in CI logs that do not render escape sequences.

## Errors

Errors are written to `STDERR` and exit with status `1`. A successful run exits with status `0`:

| Message                                         | Cause                                           |
|-------------------------------------------------|-------------------------------------------------|
| `Usage: print-section <label> [width]`          | The label is missing, empty, or only whitespace |
| `The width must be a positive number, got "…".` | The width is not numeric, or is lower than `1`  |

---

[Back to Index](../../README.md)
