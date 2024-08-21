# `PHP_CodeSniffer` usage

## Table of Contents
- [Out of the box usage](#out-of-the-box-usage)
- [Customized usage](#customized-usage)
- [CI usage](#ci-usage)
- [Kununu Custom Rules](#kununu-custom-rules)
  - [Kununu.Files.LineLength](#kununufileslinelength)
  - [Kununu.Formatting.MethodSignatureArguments](#kununuformattingmethodsignaturearguments)
  - [Kununu.PHP.NoNewLineBeforeDeclareStrict](#kununuphpnonewlinebeforedeclarestrict)

## Out of the box usage
- It will check the code standards for the `src` and `tests` directories.
- The `--standard=vendor/kununu/code-tools/phpcs.xml` flag is used to specify the coding standard to be used.
- Applied rules are listed below in the [Kununu Custom Rules](#kununu-custom-rules) section.

### Analyze and detect violations
```console
vendor/bin/phpcs --standard=vendor/kununu/code-tools/phpcs.xml
```

### Automatically fix violations
```console
vendor/bin/phpcbf --standard=vendor/kununu/code-tools/phpcs.xml
```

#### Optionally you can add it to your project's composer.json
```json
{
    "scripts": {
        "cs-check": "vendor/bin/phpcs --standard=vendor/kununu/code-tools/phpcs.xml",
        "cs-fix": "vendor/bin/phpcbf --standard=vendor/kununu/code-tools/phpcs.xml"
    }
}
```

## Customized usage
- You can customize the `phpcs.xml` file to include/exclude directories, files, or rules.
- You can create your own ruleset.xml file and use it with the `--standard` flag.
- The easiest way to customize the rules is to copy the `phpcs.xml` file to your project and modify it, for this we provide the following command:

```console
vendor/bin/code-tools pubish:config code-sniffer
```

- The `phpcs.xml` file will be copied to your project, and you can modify it to suit your needs.

<details>
  <summary>See some customization examples</summary>

Example of a customized `phpcs.xml` to include only the `Kununu.PHP.NoNewLineBeforeDeclareStrict` rule:
```xml
<?xml version="1.0"?>
<ruleset name="Custom">
    <config name="installed_paths" value="../../kununu/code-tools"/>

    <arg name="basepath" value="."/>
    <arg name="colors"/>
    <arg name="parallel" value="75"/>
    <arg value="p"/>
    <arg value="n"/>

    <description>Custom coding standard</description>

    <file>src</file>
    <file>tests</file>

    <exclude-pattern>*/tests/.results/*</exclude-pattern>

    <!-- Include only NoNewLineBeforeDeclareStrict sniff -->
    <rule ref="Kununu.PHP.NoNewLineBeforeDeclareStrict"/>
</ruleset>
```

Example of a customized `phpcs.xml` to include all Kununu rules except the `Kununu.Files.LineLength` rule:
```xml
<?xml version="1.0"?>
<ruleset name="code-tools">
    <config name="installed_paths" value="../../kununu/code-tools"/>

    <file>src</file>
    <file>tests</file>

    <arg name="basepath" value="."/>
    <arg name="colors"/>
    <arg name="parallel" value="75"/>
    <arg value="p"/>
    <arg value="n"/>

    <!-- Include all Kununu rules except Kununu.Files.LineLength -->
    <rule ref="Kununu">
        <exclude name="Kununu.Files.LineLength"/>
    </rule>
</ruleset>
```

Example of a customized `phpcs.xml` to include all Kununu rules + rules from other standards:
```xml
<?xml version="1.0"?>
<ruleset name="code-tools">
    <config name="installed_paths" value="../../kununu/code-tools"/>

    <file>src</file>
    <file>tests</file>

    <arg name="basepath" value="."/>
    <arg name="colors"/>
    <arg name="parallel" value="75"/>
    <arg value="p"/>
    <arg value="n"/>

    <!-- Include all Kununu rules -->
    <rule ref="Kununu"/>
    
    <!-- Include PSR12 rules -->
    <rule ref="PSR12"/>
</ruleset>
```

> [!WARNING]
> Be careful when including rules, as some rules may conflict with other rules or standards.

</details>

### Analyze and detect violations
```console
vendor/bin/phpcs --standard=phpcs.xml
```

### Automatically fix violations
```console
vendor/bin/phpcbf --standard=phpcs.xml
```

#### Optionally you can add it to your project's composer.json
```json
{
    "scripts": {
        "cs-check": "vendor/bin/phpcs --standard=phpcs.xml",
        "cs-fix": "vendor/bin/phpcbf --standard=phpcs.xml"
    }
}
```

## CI usage
- Though this is optional, if you include this in your project, you can/should use it in your CI pipeline to enforce the code standards on every PR.
- You can use the following GH action in your `.github/workflows` directory to run the code standards check on every PR:

<details>
  <summary>Example of a GH action</summary>

  ```yaml
  name: CI
  on:
    pull_request:

  jobs:
    cs:
      name: cs
      runs-on: ubuntu-latest
      steps:
        - uses: actions/checkout@v4

        - name: Setup PHP
          uses: shivammathur/setup-php@v2
          with:
            php-version: '8.3'
            coverage: none

        - name: Cache Composer dependencies
          uses: actions/cache@v4
          with:
            path: /tmp/composer-cache
            key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
            restore-keys: ${{ runner.os }}-composer

        - name: Install composer dependencies
          uses: php-actions/composer@v6
          with:
            php_version: '8.3'
            version: 2
            args: --optimize-autoloader --no-progress --no-interaction
            ssh_key: ${{ secrets.CLONE_SSH_KEY }}
            ssh_key_pub: ${{ secrets.CLONE_SSH_KEY_PUB }}

        - name: Run code style sniffers
          run: vendor/bin/phpcs --standard=vendor/kununu/code-tools/phpcs.xml #adjust the path to your phpcs.xml file if needed
 ```
</details>

## Kununu Custom Rules

| Rule Name                                                                               | Is automatically fixable by phpcbf? |
|-----------------------------------------------------------------------------------------|-------------------------------------|
| [Kununu.Files.LineLength](#kununufileslinelength)                                       | No                                  |
| [Kununu.Formatting.MethodSignatureArguments](#kununuformattingmethodsignaturearguments) | Yes                                 |
| [Kununu.PHP.NoNewLineBeforeDeclareStrict](#kununuphpnonewlinebeforedeclarestrict)       | Yes                                 |

-----

### Kununu.Files.LineLength

| Property Name       | Type | Default | 
|---------------------|------|---------|
| absoluteLineLimit   | int  | 120     |
| ignoreComments      | bool | false   |
| lineLimit           | int  | 100     |
| ignoreUseStatements | bool | false   |

This sniff extends `Generic.Files.LineLength` sniff to provide ability to ignore use statements when calculating line lengths.

If the `ignoreUseStatements` property is set to `true`, any use statements will be ignored when calculating line lengths. This also ensures that no error or warning will be thrown for a line that only contains a use statement, no matter how long the line is.

Is automatically fixable by phpcbf: `No`

```xml
<rule ref="Kununu.Files.LineLength">
    <properties>
        <property name="ignoreUseStatements" value="true" />
    </properties>
</rule>
```

See more details about the extended `Generic.Files.LineLength` sniff [here](https://github.com/PHPCSStandards/PHP_CodeSniffer/wiki/Customisable-Sniff-Properties#genericfileslinelength)

-----

### Kununu.Formatting.MethodSignatureArguments

| Property Name                           | Type | Default | 
|-----------------------------------------|------|---------|
| methodSignatureLengthHardBreak          | int  | 120     |
| methodSignatureLengthSoftBreak          | int  | 80      |
| methodSignatureNumberParameterSoftBreak | int  | 3       |

This sniff checks the method signature and  prevents the usage of multiline for short method signatures and single lines for long ones.

`methodSignatureLengthHardBreak`: Maximum length for a single-line method signature.  
`methodSignatureLengthSoftBreak`: Length threshold for considering a method signature to be multiline.  
`methodSignatureNumberParameterSoftBreak`: Maximum number of parameters allowed on a single line

Is automatically fixable by phpcbf: `Yes`

```xml
<rule ref="Kununu.Formatting.MethodSignatureArguments">
    <properties>
        <property name="methodSignatureLengthSoftBreak" value="80" />
        <property name="methodSignatureLengthHardBreak" value="120" />
        <property name="methodSignatureNumberParameterSoftBreak" value="3" />
    </properties>
</rule>
```
-----

### Kununu.PHP.NoNewLineBeforeDeclareStrict
| Property Name | Type | Default | 
|---------------|------|---------|
| none          | -    | -       |

This sniff checks that there is no new line before the `declare(strict_types=1);` statement.

Is automatically fixable by phpcbf: `Yes`

```xml
<rule ref="Kununu.PHP.NoNewLineBeforeDeclareStrict" />
```