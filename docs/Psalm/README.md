# `Psalm` usage

## Table of Contents
- [Out of the box usage](#out-of-the-box-usage)
- [Customized usage](#customized-usage)
  - [Adding stubs for external libraries](#adding-stubs-for-external-libraries)
  - [Adding issue handlers](#adding-issue-handlers)
  - [Using a baseline](#using-a-baseline)
- [Configuration options](#configuration-options)
- [Baseline management](#baseline-management)
- [Further resources](#further-resources)

## Out of the box usage
- The default configuration analyzes the `src` directory with error level 2 (strict).
- The default configuration includes the Symfony and PHPUnit plugins.
- The `--config` flag is used to specify the configuration to be used.

### Run Psalm analysis
```console
vendor/bin/psalm --config=vendor/kununu/code-tools/dist/psalm.xml.dist
```

### Run Psalm with no cache
```console
vendor/bin/psalm --config=vendor/kununu/code-tools/dist/psalm.xml.dist --no-cache
```

## Customized usage
- You can customize the `psalm.xml` file to include/exclude directories, add stubs, configure issue handlers, or use a baseline.
- The easiest way to customize the configuration is to copy the `psalm.xml.dist` file to your project and modify it, for this we provide the following command:

```console
vendor/bin/code-tools publish:config psalm
```

- The `psalm.xml` file will be copied to your project root, and you can modify it to suit your needs.

<details>
  <summary>See some customization examples</summary>

### Adding stubs for external libraries
Stubs help Psalm understand types from external libraries that lack proper type declarations:
```xml
<?xml version="1.0"?>
<psalm
    errorLevel="2"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
>
    <projectFiles>
        <directory name="src"/>
        <ignoreFiles>
            <directory name="vendor"/>
        </ignoreFiles>
    </projectFiles>

    <plugins>
        <pluginClass class="Psalm\PhpUnitPlugin\Plugin"/>
        <pluginClass class="Psalm\SymfonyPsalmPlugin\Plugin"/>
    </plugins>

    <stubs>
        <file name="vendor/squizlabs/php_codesniffer/src/Sniffs/Sniff.php"/>
        <file name="vendor/squizlabs/php_codesniffer/src/Files/File.php"/>
    </stubs>
</psalm>
```

### Adding issue handlers
Suppress specific issues for certain directories or files:
```xml
<?xml version="1.0"?>
<psalm
    errorLevel="2"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
>
    <projectFiles>
        <directory name="src"/>
        <ignoreFiles>
            <directory name="vendor"/>
        </ignoreFiles>
    </projectFiles>

    <plugins>
        <pluginClass class="Psalm\PhpUnitPlugin\Plugin"/>
        <pluginClass class="Psalm\SymfonyPsalmPlugin\Plugin"/>
    </plugins>

    <issueHandlers>
        <ClassMustBeFinal>
            <errorLevel type="suppress">
                <directory name="src/Sniffs"/>
            </errorLevel>
        </ClassMustBeFinal>
        <PropertyNotSetInConstructor>
            <errorLevel type="suppress">
                <directory name="src/Entity"/>
            </errorLevel>
        </PropertyNotSetInConstructor>
    </issueHandlers>
</psalm>
```

### Using a baseline
Baselines allow you to suppress existing issues while enforcing strict analysis on new code:
```xml
<?xml version="1.0"?>
<psalm
    errorLevel="2"
    errorBaseline="psalm-baseline.xml"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
>
    <projectFiles>
        <directory name="src"/>
        <ignoreFiles>
            <directory name="vendor"/>
        </ignoreFiles>
    </projectFiles>

    <plugins>
        <pluginClass class="Psalm\PhpUnitPlugin\Plugin"/>
        <pluginClass class="Psalm\SymfonyPsalmPlugin\Plugin"/>
    </plugins>
</psalm>
```

</details>

### Run Psalm with custom config
```console
vendor/bin/psalm --config=psalm.xml
```

## Configuration options

| Option | Default | Description |
|--------|---------|-------------|
| `errorLevel` | `2` | Strictness level (1=strictest, 8=most lenient) |
| `findUnusedCode` | `false` | Detect unused classes, methods, and variables |
| `ensureOverrideAttribute` | `false` | Require `#[Override]` attribute on overridden methods |
| `errorBaseline` | `—` | Path to baseline file for suppressing existing issues |

## Baseline management

Baselines allow you to suppress existing issues while enforcing strict analysis on new code.

### Generate a baseline
```console
vendor/bin/psalm --config=psalm.xml --set-baseline=psalm-baseline.xml
```

### Update the baseline
After fixing issues, regenerate to remove resolved entries:
```console
vendor/bin/psalm --config=psalm.xml --update-baseline
```

### Ignore the baseline temporarily
Run analysis without baseline to see all issues:
```console
vendor/bin/psalm --config=psalm.xml --ignore-baseline
```

## Further resources

- [Psalm Documentation](https://psalm.dev/docs/)
- [Psalm Configuration Reference](https://psalm.dev/docs/running_psalm/configuration/)
- [Psalm Plugins](https://psalm.dev/plugins)
- [Psalm Issue Types](https://psalm.dev/docs/running_psalm/issues/)
