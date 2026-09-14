# Upgrade guide

## From 4.x to 5.0

Most of this release is internal restructuring, but a few things need a hand:

- Work through [Required changes](#required-changes) first
  - Until those are done PHP_CodeSniffer, PHPStan and the pre-commit hook will all fail.
- Then skim [Behaviour changes](#behaviour-changes) for things that still work but work differently.

Publishing a config never overwrites a file you already have (`code-tools` warns and skips), so nothing below is fixed by re-running `publish:config`.

### Required changes

#### 1. Update `installed_paths` in your `phpcs.xml`

The custom sniffs moved inside the package, so the standard now sits one level deeper. Whatever
value your published `phpcs.xml` has today, append `/src/PHPCodeSniffer` to it.

Both of the spellings below are in use, and both need the same edit:

```diff
-<config name="installed_paths" value="vendor/kununu/code-tools"/>
+<config name="installed_paths" value="vendor/kununu/code-tools/src/PHPCodeSniffer"/>
```

```diff
-<config name="installed_paths" value="../../kununu/code-tools"/>
+<config name="installed_paths" value="../../kununu/code-tools/src/PHPCodeSniffer"/>
```

They differ only in what they are relative to. A value starting with `.` is resolved against
`vendor/squizlabs/php_codesniffer`, which is why the shipped template uses `../../`. Any other
value is used as given, so it resolves from the directory you run `phpcs` in, normally the
project root.

Without this edit PHP_CodeSniffer cannot find the `Kununu` standard and every run aborts with:

```console
ERROR: Referenced sniff "Kununu" does not exist.
```

#### 2. Update the Architecture Sniffer class in your `phpstan.neon`

```diff
 services:
-    - class: Kununu\ArchitectureSniffer\ArchitectureSniffer
+    - class: Kununu\CodeTools\ArchitectureSniffer\ArchitectureSniffer
```

Every class in the package except the sniffs moved from `Kununu\` to `Kununu\CodeTools\`.
This is the only one that consumers normally name explicitly.

#### 3. Stop extending the sniffs

`LineLengthSniff`, `EmptyLineAfterClassElementsSniff`, `MethodSignatureArgumentsSniff` and `NoNewLineBeforeDeclareStrictSniff` are now `final`.
If you subclassed one, copy it into your own project instead.

#### 4. Make sure `ext-mbstring` and `ext-tokenizer` are available

Both are now required in `composer.json`.
They were always used indirectly: the requirement is now explicit, so installs fail on hosts without them.

The PHP requirement itself is unchanged (`>=8.4`).

#### 5. Repair the pre-commit hook symlink

If you use the git hook, `.git/kununu/.php-cs-fixer.php` currently points at `vendor/kununu/code-tools/php-cs-fixer.php`, which no longer exists.

A dangling link makes the hook print `Missing php-cs-fixer rules file` and **exit 1**, blocking every commit that touches a `.php` file.

A normal `composer install` or `composer update` repairs it, because the plugin re-installs the hook afterwards.

If you install with `--no-plugins`, as the README recommends, run it yourself once:

```console
composer kununu:cs-fixer-git-hook
```

### Behaviour changes

#### The pre-commit hook now respects your own PHP-CS-Fixer config

Previously the hook always used the package's internal config, so anything your project excluded was reformatted anyway.

Now:

| Your project                        | Hook uses                                    |
|-------------------------------------|----------------------------------------------|
| has a published `php-cs-fixer.php`  | that file, and honours the paths it excludes |
| has none                            | the shipped `dist/php-cs-fixer.php.dist`     |

When your own config is used, the installer writes a marker at `.git/kununu/filter-by-config` and the hook narrows the staged files through the config's finder.

This is needed because PHP-CS-Fixer ignores its own finder as soon as explicit paths are passed on the command line, which is how the hook invokes it.

Nothing to configure, but if you previously relied on the hook formatting files your config excludes, it will now leave them alone.

#### `dist/rector.php.dist` changed

The template now uses `withPhpSets()` and `withComposerBased(phpunit: true)` instead of a pinned `PHPUnitSetList::PHPUNIT_100`, and no longer skips `AddOverrideAttributeToOverriddenMethodsRector`.

An existing `rector.php` in your project is untouched; this only affects projects publishing it for the first time. If you adopt it, expect a larger first run.

#### Minimum tool versions raised

| Tool                        | Version Constraint |
|-----------------------------|--------------------|
| `composer/composer`         | `^2.10`            |
| `friendsofphp/php-cs-fixer` | `^3.95`            |
| `phpstan/phpstan`           | `^2.2`             |
| `rector/rector`             | `^2.6`             |
| `squizlabs/php_codesniffer` | `^3.13`            |
| `vimeo/psalm`               | `^6.17`            |

Plus these new dependencies were added

| Tool                            | Version Constraint |
|---------------------------------|--------------------|
| `phpstan/phpstan-phpunit`       | `^2.10`            |
| `jetbrains/phpstorm-attributes` | `^2.0`             |


See `composer.json` for the authoritative list.

### New in 5.0

- **PHPStan config template.**
  - `vendor/bin/code-tools publish:config phpstan`
    - Writes a `phpstan.neon` starting at level 1 with the PHPUnit extension wired in
     See [docs/PHPStan](docs/PHPStan/README.md).
- **`.editorconfig`**
  - Covers `.neon` files**, in the same 2-space group as JSON and YAML.
- **Hook installation without plugins.**
  - `Kununu\CodeTools\PHPCSFixer\CsFixerPlugin::installGitHooks`
    - Can be wired into your own `post-install-cmd`/`post-update-cmd` if you install with `--no-plugins`.

### What did not change

Worth stating, because the namespace move suggests otherwise:

- **Your `phpcs.xml` rule references.**
  - The standard is still called `Kununu` and the sniffs still live in `Kununu\Sniffs\`
  - So `<rule ref="Kununu.Files.LineLength"/>` and friends keep working  untouched
  - Only `installed_paths` moved.
- **The `code-tools` binary**
  - Same `publish:config` command, same tool names, same published filenames
  - `phpstan` is added alongside them.
- **The composer commands**
  - `kununu:cs-fixer`
  - `kununu:cs-fixer-git-hook`
  - Including their arguments and aliases.
- **`dist/psalm.xml.dist`**
  - Is byte-identical to 4.x.
- **`dist/php-cs-fixer.php.dist` rules.**
  - The only edits are cosmetic.

### Changes

- Documentation folders were renamed
  - `CodeSniffer` → `PHPCodeSniffer`
  - `CsFixer` → `PHPCSFixer`
  - `PhpInK8s` → `PHPInK8s`
  - Bookmarks and deep links need updating, but no code references them.
