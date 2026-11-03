# `download-phar` usage

This script keeps a tool's PHAR on the **latest release** of its GitHub repository, inside your project.

It is meant for tools that should not be project dependencies. Installed through `require-dev`, a tool's own requirements
constrain what your project can install (for example, a recent Composer Require Checker needs `symfony/console` ^7,
which blocks a Symfony 6.4 test leg). Some tools, Composer Require Checker among them, also recommend the PHAR outright.

## Usage

```bash
vendor/bin/download-phar <tool> <owner/repository> <release-asset> [directory]
```

| Argument             | Required | Default | Description                                                                   |
|----------------------|----------|---------|-------------------------------------------------------------------------------|
| `<tool>`             | yes      |         | Local name of the tool, used for `<tool>.phar` and `<tool>.version`           |
| `<owner/repository>` | yes      |         | The GitHub repository that publishes the releases                            |
| `<release-asset>`    | yes      |         | File name of the PHAR attached to each release                                |
| `[directory]`        | no       | `tools` | Where to keep the PHAR, relative to the **working directory** unless absolute |

`<tool>` and `<release-asset>` must be plain file names (letters, digits, `.`, `_` and `-`), so they can never point
outside the directory.

```bash
vendor/bin/download-phar composer-require-checker maglnet/ComposerRequireChecker composer-require-checker.phar
```

Composer runs scripts from the project root, so inside a `composer.json` script the default directory is the project's
own `tools/`. Add it to your `.gitignore`:

```gitignore
/tools/
```

## How it works

1. It sends a `HEAD` request to `https://github.com/<owner/repository>/releases/latest`, without following the redirect,
   and reads the tag from the `Location` header (`…/releases/tag/<tag>`). Nothing is downloaded, and it does not use the
   GitHub API, so there is no API rate limit and no token is needed.
2. If `<directory>/<tool>.version` already holds that tag and `<directory>/<tool>.phar` exists, it does nothing.
3. Otherwise it downloads `https://github.com/<owner/repository>/releases/download/<tag>/<release-asset>` to
   `<directory>/<tool>.phar.tmp`, renames it to `<directory>/<tool>.phar` and writes the tag to `<tool>.version`.
   Because of the rename, an interrupted download never replaces a working PHAR.

The directory is created when needed. Running the script before every use of the tool is cheap: when nothing changed it
costs a single `HEAD` request.

The script only downloads the PHAR. It does not run it, and it does not verify signatures.

## Wiring it into `composer.json`

This is how the package itself runs Composer Require Checker and Composer Normalize. One `tool:*` script keeps each PHAR
current, and the scripts that use the tool call it first:

```json
{
  "scripts": {
    "tool:composer-normalize": "vendor/bin/download-phar composer-normalize ergebnis/composer-normalize composer-normalize.phar",
    "tool:composer-require-checker": "vendor/bin/download-phar composer-require-checker maglnet/ComposerRequireChecker composer-require-checker.phar",
    "normalize-check": [
      "@tool:composer-normalize",
      "@php tools/composer-normalize.phar --dry-run --indent-size 2 --indent-style space --no-check-lock --no-update-lock"
    ],
    "normalize-fix": [
      "@tool:composer-normalize",
      "@php tools/composer-normalize.phar --indent-size 2 --indent-style space --no-check-lock --no-update-lock"
    ],
    "require-checker": [
      "@tool:composer-require-checker",
      "@putenv XDEBUG_MODE=off",
      "@php tools/composer-require-checker.phar check composer.json"
    ],
    "ci": [
      "@require-checker",
      "@normalize-check"
    ]
  }
}
```

- `XDEBUG_MODE=off` is what Composer Require Checker's own README recommends.
- The Composer Require Checker PHAR picks up a `composer-require-checker.json` in the working directory on its own.
- Once the tools run as PHARs, remove `maglnet/composer-require-checker` and `ergebnis/composer-normalize` from
  `require-dev`, and `ergebnis/composer-normalize` from `config.allow-plugins`.

See the `scripts` section of this repository's own `composer.json` for the complete version.

## Output

Nothing when the PHAR is already on the latest release. When it downloads, a single line on `STDOUT`:

```console
Downloading composer-require-checker 4.24.0
```

## Offline and errors

When the latest release cannot be determined (no network, GitHub unreachable, or an unknown repository) and a PHAR is
already there, the script warns on `STDERR` and exits with status `0`, so the tool still runs with the PHAR you have:

```console
Could not check the latest composer-require-checker release, using tools/composer-require-checker.phar.
```

Every other problem is written to `STDERR` and exits with status `1`. A failed download leaves no `.tmp` file behind,
and the PHAR that was there before, if any, is untouched.

| Message                                                                      | Cause                                                                  |
|------------------------------------------------------------------------------|------------------------------------------------------------------------|
| `Usage: download-phar <tool> <owner/repository> <release-asset> [directory]` | One of the three required arguments is missing                         |
| `Invalid tool name "…".` / `Invalid release asset name "…".`                 | The name is not a plain file name                                      |
| `The repository must be in the "owner/repository" format, got "…".`          | The second argument is not `owner/repository`                          |
| `The directory must not be empty.`                                           | An empty directory argument was given                                  |
| `Could not find the latest … release.`                                       | The latest release could not be determined and there is no PHAR yet    |
| `Could not create the directory "…".`                                        | The directory does not exist and cannot be created                     |
| `Could not download …`                                                       | The asset does not exist in the latest release, or the download failed |
| `Could not move the download to …` / `Could not write …`                     | The directory is not writable                                          |

---

[Back to Index](../../README.md)
